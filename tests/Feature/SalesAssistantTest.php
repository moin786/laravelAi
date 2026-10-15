<?php

use App\Agents\SalesAssistantAgent;
use App\Agents\SalesSummaryAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

uses(RefreshDatabase::class);

test('sales assistant page requires an authenticated verified user', function () {
    $this->get('/sales-assistant')->assertRedirect('/login');
});

test('sales assistant page is available to verified users', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get('/sales-assistant')->assertOk();
});

test('sales agent exposes its business tools and structured schema', function () {
    $agent = SalesAssistantAgent::make();

    expect(iterator_to_array($agent->tools()))->toHaveCount(3)
        ->and(SalesSummaryAgent::make()->schema(new JsonSchemaTypeFactory))->toHaveKey('answer');
});

test('chat endpoint streams an agent response', function () {
    SalesAssistantAgent::fake(['Sales are up.']);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->post('/sales-assistant/chat', ['message' => 'How are sales?'])
        ->assertOk()
        ->assertHeader('content-type', 'text/event-stream; charset=UTF-8');
});
