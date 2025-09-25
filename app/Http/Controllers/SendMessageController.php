<?php

namespace App\Http\Controllers;

use App\Events\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SendMessageController extends Controller
{
    /**
     * Send the message to a room.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'room' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:140'],
        ]);

        broadcast(new Message(
            $request->user(),
            $request->room,
            $request->message,
        ));

        return Response::json(['ok' => true]);
    }
}
