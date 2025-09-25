<?php

namespace App\Commands\AI;

use Illuminate\Support\Facades\Log;

class AICommandHandler
{
    private array $commands;

    public function __construct()
    {
        $this->commands = [];
    }

    public function registerCommand(AICommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function handleMessage(string $message, array $context = []): string
    {
        Log::info('AI Command Handler processing message', [
            'message' => $message,
            'available_commands' => count($this->commands),
        ]);

        // Check for specific commands first
        foreach ($this->commands as $command) {
            if ($command->matches($message)) {
                Log::info('AI Command matched', [
                    'command' => $command->getCommandName(),
                    'message' => $message,
                ]);

                return $command->execute($message, $context);
            }
        }

        // If no specific command matches, use general chat
        $generalChat = new GeneralChatCommand(app(\App\Services\AI\AIService::class));

        return $generalChat->execute($message, $context);
    }

    public function getAvailableCommands(): array
    {
        return array_map(
            fn ($command) => [
                'name' => $command->getCommandName(),
                'description' => $command->getDescription(),
            ],
            $this->commands
        );
    }
}

