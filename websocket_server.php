<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require 'vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $conversations = [];  // conversation_id => [conn_ids]

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        if ($data->type === 'join') {
            $this->conversations[$data->conversation_id][] = $from;
        } elseif ($data->type === 'message') {
            // Save to DB (similar to send_message.php)
            // Then broadcast to conversation
            foreach ($this->conversations[$data->conversation_id] ?? [] as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                }
            }
        } // Add typing similarly
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();