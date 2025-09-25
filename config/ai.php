<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used when
    | no specific provider is requested. Supported providers: "openai", "mock"
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'groq'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI API integration
    |
    */

    // OpenAI block removed (Groq-only)

    /*
    |--------------------------------------------------------------------------
    | Groq Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Groq API integration (OpenAI-compatible endpoint)
    |
    */

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'max_tokens' => env('GROQ_MAX_TOKENS', 256),
        'temperature' => env('GROQ_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Response Settings
    |--------------------------------------------------------------------------
    |
    | Global settings for AI responses
    |
    */

    'response' => [
        'max_length' => env('AI_MAX_RESPONSE_LENGTH', 500),
        'timeout' => env('AI_RESPONSE_TIMEOUT', 30),
        'retry_attempts' => env('AI_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Management
    |--------------------------------------------------------------------------
    |
    | Settings for managing conversation context
    |
    */

    'context' => [
        'max_context_items' => env('AI_MAX_CONTEXT_ITEMS', 10),
        'context_ttl' => env('AI_CONTEXT_TTL', 3600), // 1 hour in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | SSL verification can fail on some Windows environments without a CA bundle.
    | For development only, you can set AI_VERIFY_SSL=false to bypass SSL verify.
    |
    */

    'http' => [
        'verify_ssl' => env('AI_VERIFY_SSL', true),
    ],
];
