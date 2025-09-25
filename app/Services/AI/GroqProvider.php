<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqProvider implements AIProviderInterface
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.groq.api_key', '');
        $this->baseUrl = (string) config('ai.groq.base_url', 'https://api.groq.com/openai/v1');
        $this->model = (string) config('ai.groq.model', 'llama-3.3-70b-versatile');
    }

    public function generateResponse(string $message, array $context = []): string
    {
        if (! $this->isAvailable()) {
            return "I'm sorry, but I'm currently unavailable. Please try again later.";
        }

        try {
            $response = Http::withOptions(['verify' => (bool) config('ai.http.verify_ssl', true)])
                ->timeout((int) config('ai.response.timeout', 20))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl.'/chat/completions', [
                    'model' => $this->model,
                    'messages' => $this->buildMessages($message, $context),
                    'max_tokens' => (int) config('ai.groq.max_tokens', 256),
                    'temperature' => (float) config('ai.groq.temperature', 0.7),
                ]);

            $data = $response->json();
            if ($data) {
                return $data['choices'][0]['message']['content'] ?? 'I apologize, but I could not generate a response.';
            }

            Log::error('Groq API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return "I'm experiencing technical difficulties. Please try again later.";
        } catch (\Exception $e) {
            Log::error('Groq API exception', ['error' => $e->getMessage()]);

            return "I'm experiencing technical difficulties. Please try again later.";
        }
    }

    public function getProviderName(): string
    {
        return 'Groq';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    private function buildMessages(string $message, array $context = []): array
    {
        $systemMessage = 'You are a helpful AI assistant in a chat room. Be friendly, concise, and helpful. Keep responses under 100 words.';

        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
        ];

        if (! empty($context)) {
            $contextMessage = 'Context: '.implode(' ', array_map('strval', $context));
            $messages[] = ['role' => 'user', 'content' => $contextMessage];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }
}


