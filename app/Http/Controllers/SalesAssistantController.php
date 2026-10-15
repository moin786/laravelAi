<?php

namespace App\Http\Controllers;

use App\Agents\SalesAssistantAgent;
use App\Jobs\IndexSalesDocument;
use App\Models\AiUsage;
use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Files\LocalDocument;

class SalesAssistantController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('sales-assistant', [
            'conversationId' => null,
            'documents' => $request->user()->knowledgeDocuments()->latest()->get(['id', 'name', 'created_at']),
        ]);
    }

    public function chat(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'uuid'],
            'document' => ['nullable', 'file', 'mimes:pdf,txt,md', 'max:10240'],
        ]);

        $agent = isset($data['conversation_id'])
            ? SalesAssistantAgent::make()->continue($data['conversation_id'], $request->user())
            : SalesAssistantAgent::make()->continueLastConversation($request->user());
        $attachments = [];
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('sales-documents');
            $attachments[] = new LocalDocument(Storage::path($path));
        }

        $response = $agent->stream($this->withRelevantKnowledge($data['message'], $request->user()->id), $attachments);
        $response->then(function ($result) use ($request): void {
            AiUsage::create([
                'user_id' => $request->user()->id,
                'conversation_id' => $result->conversationId,
                'input_tokens' => $result->usage->inputTokens,
                'output_tokens' => $result->usage->outputTokens,
                'cost' => 0,
            ]);
        });

        return $response;
    }

    public function storeDocument(Request $request)
    {
        $data = $request->validate(['document' => ['required', 'file', 'mimes:pdf,txt,md', 'max:10240']]);
        $file = $data['document'];
        $content = $file->getMimeType() === 'application/pdf' ? '' : file_get_contents($file->getRealPath());
        $document = $request->user()->knowledgeDocuments()->create(['name' => $file->getClientOriginalName(), 'content' => $content]);
        if (filled($content)) {
            IndexSalesDocument::dispatch($document);
        }

        return back()->with('success', 'Document queued for analysis.');
    }

    private function withRelevantKnowledge(string $message, int $userId): string
    {
        $documents = KnowledgeDocument::where('user_id', $userId)->whereNotNull('embedding')->get();

        if ($documents->isEmpty()) {
            return $message;
        }

        $queryEmbedding = Embeddings::for([$message])->generate()->first();
        $documents = $documents->sortByDesc(
            fn(KnowledgeDocument $document): float => $this->cosineSimilarity($queryEmbedding, $document->embedding ?? [])
        )->take(3)->pluck('content');

        return "Relevant internal notes:\n" . $documents->join("\n---\n") . "\n\nQuestion: {$message}";
    }

    /** @param array<float> $left @param array<float> $right */
    private function cosineSimilarity(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) {
            return 0;
        }

        $dot = $leftLength = $rightLength = 0;
        foreach ($left as $index => $value) {
            $dot += $value * $right[$index];
            $leftLength += $value ** 2;
            $rightLength += $right[$index] ** 2;
        }

        return $leftLength && $rightLength ? $dot / sqrt($leftLength * $rightLength) : 0;
    }
}
