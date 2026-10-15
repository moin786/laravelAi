<?php

namespace App\Tools;

use App\Models\InventoryItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CheckInventory implements Tool
{
    public function description(): Stringable|string
    {
        return 'Check available inventory for a SKU or product name.';
    }

    public function handle(Request $request): Stringable|string
    {
        $term = $request->validate(['query' => ['required', 'string', 'max:100']])['query'];

        return InventoryItem::query()->where('sku', $term)->orWhere('name', 'like', "%{$term}%")->limit(10)->get(['sku', 'name', 'quantity'])->toJson();
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->required()];
    }
}
