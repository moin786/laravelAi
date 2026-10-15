<?php

namespace App\Agents;

use App\Tools\CheckInventory;
use App\Tools\FindCustomer;
use App\Tools\GetSalesReport;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

#[Model('gpt-4.1-mini')]
#[MaxTokens(800)]
class SalesAssistantAgent implements Agent, HasTools
{
    use Promptable, RemembersConversations;

    public function instructions(): Stringable|string
    {
        return 'You are a concise sales assistant. Use tools for sales, inventory, and customer facts. '
            .'Never invent business data. Answer in plain language and include useful next actions.';
    }

    /** @return list<Agent|Tool|ProviderTool> */
    public function tools(): iterable
    {
        return [new GetSalesReport, new CheckInventory, new FindCustomer];
    }
}
