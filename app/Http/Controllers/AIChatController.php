<?php

namespace App\Http\Controllers;

use App\Commands\AI\AICommandHandler;
use App\Commands\AI\ClearContextCommand;
use App\Commands\AI\HelpCommand;
use App\Commands\AI\StatusCommand;
use App\Events\AIMessage;
use App\Events\Message;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class AIChatController extends Controller
{
    private AICommandHandler $commandHandler;

    private AIService $aiService;

    public function __construct()
    {
        $this->commandHandler = new AICommandHandler;
        $this->aiService = new AIService(AIProviderFactory::create());

        // Register AI commands
        $this->commandHandler->registerCommand(new HelpCommand);
        $this->commandHandler->registerCommand(new StatusCommand($this->aiService));
        $this->commandHandler->registerCommand(new ClearContextCommand($this->aiService));
    }

    public function processMessage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'room' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $room = $request->input('room');
        $message = $request->input('message');
        $user = $request->user();

        Log::info('AI Chat processing message', [
            'room' => $room,
            'user' => $user->name,
            'message' => $message,
        ]);

        try {
            // Persist the user's question (legacy chat_room_*)
            $messages = Cache::get('chat_room_'.$room, []);
            $messages[] = [
                'user' => $user->only('id', 'name'),
                'message' => $message,
                'timestamp' => now()->timestamp,
            ];
            Cache::put('chat_room_'.$room, $messages, now()->addHours(1));

            // Also persist to room history used by the UI
            $history = Cache::get("room_messages_{$room}", []);
            $history[] = [
                'id' => uniqid(),
                'user' => $user->only('id', 'name'),
                'message' => $message,
                'timestamp' => now()->toISOString(),
            ];
            if (count($history) > 50) {
                $history = array_slice($history, -50);
            }
            Cache::put("room_messages_{$room}", $history, 3600);

            // Broadcast the user's AI question to others in real-time
            try {
                broadcast(new Message($user, $room, $message))->toOthers();
            } catch (\Throwable $e) {
                Log::warning('AI question broadcast failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Add user context to AI service
            $this->aiService->addContext("User {$user->name} said: {$message}");

            // Process message through AI command handler
            $aiResponse = $this->commandHandler->handleMessage($message, [
                'user_name' => $user->name,
                'room' => $room,
                'timestamp' => now()->toISOString(),
            ]);

            // Persist AI response to both caches so polling/newcomers receive it
            $messages = Cache::get('chat_room_'.$room, []);
            $messages[] = [
                'user' => [
                    'id' => 'ai-'.$this->aiService->getProviderName(),
                    'name' => $this->aiService->getProviderName(),
                    'is_ai' => true,
                ],
                'message' => $aiResponse,
                'timestamp' => now()->timestamp,
            ];
            Cache::put('chat_room_'.$room, $messages, now()->addHours(1));

            $history = Cache::get("room_messages_{$room}", []);
            $history[] = [
                'id' => uniqid(),
                'user' => [
                    'id' => 'ai-'.$this->aiService->getProviderName(),
                    'name' => $this->aiService->getProviderName(),
                    'is_ai' => true,
                ],
                'message' => $aiResponse,
                'timestamp' => now()->toISOString(),
            ];
            if (count($history) > 50) {
                $history = array_slice($history, -50);
            }
            Cache::put("room_messages_{$room}", $history, 3600);

            // Try to broadcast in real-time; ignore failures if websocket not available
            try {
                broadcast(new AIMessage($room, $aiResponse, $this->aiService->getProviderName()));
            } catch (\Throwable $e) {
                Log::warning('AI broadcast failed; falling back to polling', [
                    'error' => $e->getMessage(),
                ]);
            }

            return Response::json([
                'success' => true,
                'ai_response' => $aiResponse,
                'provider' => $this->aiService->getProviderName(),
            ]);

        } catch (\Throwable $e) {
            Log::error('AI Chat error', [
                'error' => $e->getMessage(),
                'room' => $room,
                'user' => $user->name,
            ]);

            return Response::json([
                'success' => false,
                'error' => 'AI service temporarily unavailable',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStatus(): \Illuminate\Http\JsonResponse
    {
        return Response::json([
            'available' => $this->aiService->isAvailable(),
            'provider' => $this->aiService->getProviderName(),
            'commands' => $this->commandHandler->getAvailableCommands(),
        ]);
    }
}
