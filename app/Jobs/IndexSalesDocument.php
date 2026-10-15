<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Embeddings;

class IndexSalesDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public KnowledgeDocument $document) {}

    public function handle(): void
    {
        $response = Embeddings::for([$this->document->content])->generate();
        $this->document->update(['embedding' => $response->first()]);
    }
}
