<?php

namespace App\Commands\AI;

use App\Services\AI\AIService;

class StatusCommand implements AICommandInterface
{
    public function __construct(
        private AIService $aiService
    ) {}

    public function execute(string $message, array $context = []): string
    {
        $status = $this->aiService->isAvailable() ? '🟢 Online' : '🔴 Offline';
        $provider = $this->aiService->getProviderName();

        return "🤖 **AI Assistant Status**\n\n".
               "Status: {$status}\n".
               "Provider: {$provider}\n".
               'Ready to help!';
    }

    public function getCommandName(): string
    {
        return 'status';
    }

    public function getDescription(): string
    {
        return 'Check AI assistant status';
    }

    public function matches(string $message): bool
    {
        $message = strtolower(trim($message));

        return str_contains($message, '@ai status') || str_contains($message, '/status');
    }
}


