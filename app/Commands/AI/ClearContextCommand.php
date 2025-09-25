<?php

namespace App\Commands\AI;

use App\Services\AI\AIService;

class ClearContextCommand implements AICommandInterface
{
    public function __construct(
        private AIService $aiService
    ) {}

    public function execute(string $message, array $context = []): string
    {
        $this->aiService->clearContext();

        return "🧹 I've cleared my conversation context. Starting fresh!";
    }

    public function getCommandName(): string
    {
        return 'clear_context';
    }

    public function getDescription(): string
    {
        return 'Clear AI conversation context';
    }

    public function matches(string $message): bool
    {
        $message = strtolower(trim($message));

        return str_contains($message, '@ai clear') || str_contains($message, '/clear');
    }
}


