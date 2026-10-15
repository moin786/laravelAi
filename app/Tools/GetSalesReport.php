<?php

namespace App\Tools;

use App\Models\SalesRecord;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetSalesReport implements Tool
{
    public function description(): Stringable|string
    {
        return 'Get total sales and order count for a date range.';
    }

    public function handle(Request $request): Stringable|string
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);
        $report = SalesRecord::query()->whereBetween('sold_at', [$data['from'], $data['to']])->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue')->first();

        return json_encode(['from' => $data['from'], 'to' => $data['to'], 'orders' => (int) $report->orders, 'revenue' => (float) $report->revenue]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['from' => $schema->string()->required(), 'to' => $schema->string()->required()];
    }
}
