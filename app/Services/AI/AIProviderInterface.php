<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function generateResponse(string $message, array $context = []): string;

    public function getProviderName(): string;

    public function isAvailable(): bool;
}

