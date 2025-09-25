<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $room,
        public string $message,
        public string $aiProvider = 'AI Assistant'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('room.'.$this->room),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ai.message';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'user' => [
                'id' => 'ai-'.$this->aiProvider,
                'name' => $this->aiProvider,
                'is_ai' => true,
            ],
            'timestamp' => now()->timestamp,
        ];
    }
}

