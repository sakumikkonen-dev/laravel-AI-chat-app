<?php

namespace App\Commands\AI;

interface AICommandInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function execute(string $message, array $context = []): string;

    public function getCommandName(): string;

    public function getDescription(): string;

    public function matches(string $message): bool;
}
