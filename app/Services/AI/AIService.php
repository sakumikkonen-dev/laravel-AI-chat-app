<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class AIService
{
    private AIProviderInterface $provider;

    private array $context = [];

    public function __construct(?AIProviderInterface $provider = null)
    {
        $this->provider = $provider ?? AIProviderFactory::create();
    }

    public function generateResponse(string $message, array $additionalContext = []): string
    {
        $fullContext = array_merge($this->context, $additionalContext);

        Log::info('AI Service generating response', [
            'message' => $message,
            'provider' => $this->provider->getProviderName(),
            'context_count' => count($fullContext),
        ]);

        return $this->provider->generateResponse($message, $fullContext);
    }

    public function addContext(string $context): void
    {
        $this->context[] = $context;
        
        if (count($this->context) > 10) {
            $this->context = array_slice($this->context, -10);
        }
    }

    public function clearContext(): void
    {
        $this->context = [];
    }

    public function getProviderName(): string
    {
        return $this->provider->getProviderName();
    }

    public function isAvailable(): bool
    {
        return $this->provider->isAvailable();
    }
}
