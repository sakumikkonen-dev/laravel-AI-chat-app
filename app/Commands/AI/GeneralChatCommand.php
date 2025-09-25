<?php

namespace App\Commands\AI;

use App\Services\AI\AIService;

class GeneralChatCommand implements AICommandInterface
{
    public function __construct(
        private AIService $aiService
    ) {}

    public function execute(string $message, array $context = []): string
    {
        return $this->aiService->generateResponse($message, $context);
    }

    public function getCommandName(): string
    {
        return 'general_chat';
    }

    public function getDescription(): string
    {
        return 'General conversation with AI';
    }

    public function matches(string $message): bool
    {
        // This command matches all messages that don't match other specific commands
        return true;
    }
}


