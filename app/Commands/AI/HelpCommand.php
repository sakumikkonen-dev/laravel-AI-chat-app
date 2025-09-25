<?php

namespace App\Commands\AI;

class HelpCommand implements AICommandInterface
{
    public function execute(string $message, array $context = []): string
    {
        return "🤖 **AI Assistant Commands:**\n\n".
               "• Just type normally to chat with me!\n".
               "• Use `@ai help` to see this message\n".
               "• Use `@ai clear` to clear conversation context\n".
               "• Use `@ai status` to check my status\n\n".
               "I'm here to help with questions, have conversations, and provide assistance!";
    }

    public function getCommandName(): string
    {
        return 'help';
    }

    public function getDescription(): string
    {
        return 'Show available AI commands';
    }

    public function matches(string $message): bool
    {
        $message = strtolower(trim($message));

        return str_contains($message, '@ai help') || str_contains($message, '/help');
    }
}


