<?php

namespace App\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FindCustomer implements Tool
{
    public function description(): Stringable|string
    {
        return 'Find a customer by name or email.';
    }

    public function handle(Request $request): Stringable|string
    {
        $term = $request->validate(['query' => ['required', 'string', 'max:100']])['query'];

        return Customer::query()->where('email', $term)->orWhere('name', 'like', "%{$term}%")->limit(10)->get(['name', 'email', 'company'])->toJson();
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->required()];
    }
}
