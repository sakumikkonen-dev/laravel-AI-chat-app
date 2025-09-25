<?php

namespace App\Services\AI;

class AIProviderFactory
{
    public static function create(?string $provider = null): AIProviderInterface
    {
        return new GroqProvider;
    }

    public static function getAvailableProviders(): array
    {
        return ['groq'];
    }
}
