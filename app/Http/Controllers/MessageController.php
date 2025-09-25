<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    /**
     * Store a message in cache for the room
     */
    public function store(Request $request)
    {
        $request->validate([
            'room' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:140'],
        ]);

        $room = $request->room;
        $message = [
            'id' => uniqid(),
            'user' => $request->user()->only('id', 'name'),
            'message' => $request->message,
            'timestamp' => now()->toISOString(),
        ];

        // Store message in cache (in a real app, you'd use a database)
        $messages = Cache::get("room_messages_{$room}", []);
        $messages[] = $message;

        // Keep only last 50 messages per room
        if (count($messages) > 50) {
            $messages = array_slice($messages, -50);
        }

        Cache::put("room_messages_{$room}", $messages, 3600); // 1 hour

        return response()->json(['ok' => true, 'message' => $message]);
    }

    /**
     * Get messages for a room
     */
    public function index(Request $request, $room)
    {
        $messages = Cache::get("room_messages_{$room}", []);

        return response()->json(['messages' => $messages]);
    }
}


