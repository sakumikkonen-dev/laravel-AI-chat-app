<?php

namespace Tests\Feature;

use App\Services\AI\AIProviderFactory;
use App\Services\AI\AIService;
use App\Commands\AI\AICommandHandler;
use App\Commands\AI\HelpCommand;
use App\Commands\AI\StatusCommand;
use App\Commands\AI\ClearContextCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_provider_factory_creates_mock_provider()
    {
        $provider = AIProviderFactory::create('groq');
        $this->assertEquals('Groq', $provider->getProviderName());
    }

    public function test_ai_service_generates_response()
    {
        $provider = AIProviderFactory::create('groq');
        $aiService = new AIService($provider);
        
        $response = $aiService->generateResponse('Hello');
        
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_command_handler_processes_help_command()
    {
        $provider = AIProviderFactory::create('groq');
        $aiService = new AIService($provider);
        
        $commandHandler = new AICommandHandler();
        $commandHandler->registerCommand(new HelpCommand());
        
        $response = $commandHandler->handleMessage('@ai help');
        
        $this->assertStringContainsString('AI Assistant Commands', $response);
    }

    public function test_command_handler_processes_status_command()
    {
        $provider = AIProviderFactory::create('groq');
        $aiService = new AIService($provider);
        
        $commandHandler = new AICommandHandler();
        $commandHandler->registerCommand(new StatusCommand($aiService));
        
        $response = $commandHandler->handleMessage('@ai status');
        
        $this->assertStringContainsString('AI Assistant Status', $response);
    }

    public function test_command_handler_processes_clear_command()
    {
        $provider = AIProviderFactory::create('mock');
        $aiService = new AIService($provider);
        
        $commandHandler = new AICommandHandler();
        $commandHandler->registerCommand(new ClearContextCommand($aiService));
        
        $response = $commandHandler->handleMessage('@ai clear');
        
        $this->assertStringContainsString('cleared my conversation context', $response);
    }

    public function test_command_handler_processes_general_chat()
    {
        $provider = AIProviderFactory::create('mock');
        $aiService = new AIService($provider);
        
        $commandHandler = new AICommandHandler();
        $commandHandler->registerCommand(new HelpCommand());
        $commandHandler->registerCommand(new StatusCommand($aiService));
        $commandHandler->registerCommand(new ClearContextCommand($aiService));
        
        $response = $commandHandler->handleMessage('Hello, how are you?');
        
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function test_ai_chat_api_endpoint_requires_authentication()
    {
        $response = $this->postJson('/api/ai/chat', [
            'room' => 'test-room',
            'message' => 'Hello AI'
        ]);

        $response->assertStatus(401);
    }

    public function test_ai_status_api_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/ai/status');

        $response->assertStatus(401);
    }
}
