<?php

use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require_once 'vendor/autoload.php';

class ChatServer implements MessageComponentInterface
{
    protected $clients;

    protected $rooms;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (isset($data['type']) && $data['type'] === 'join') {
            $room = $data['room'];
            if (! isset($this->rooms[$room])) {
                $this->rooms[$room] = [];
            }
            $this->rooms[$room][$from->resourceId] = $from;
            $from->room = $room;

            // Notify others in the room
            foreach ($this->rooms[$room] as $client) {
                if ($client !== $from) {
                    $client->send(json_encode([
                        'type' => 'user_joined',
                        'user' => $data['user'] ?? 'Anonymous',
                    ]));
                }
            }
        } elseif (isset($data['type']) && $data['type'] === 'message') {
            $room = $from->room ?? 'default';
            if (isset($this->rooms[$room])) {
                foreach ($this->rooms[$room] as $client) {
                    $client->send(json_encode([
                        'type' => 'message',
                        'user' => $data['user'],
                        'message' => $data['message'],
                    ]));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        if (isset($conn->room)) {
            unset($this->rooms[$conn->room][$conn->resourceId]);
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer
        )
    ),
    6001
);

echo "WebSocket server running on port 6001\n";
$server->run();


