<?php
namespace IRCClone;

use IRCClone\Connection\ChatConnection;

use SplObjectStorage;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    private $clients;
    private $channels;
    public $db;

    public function __construct($db)
    {
        $this->clients = new \SplObjectStorage;
        $this->channels = new \SplObjectStorage;
        $this->db = $db;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";
        
        $this->clients->attach(new ChatConnection($conn, $this));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $client = $this->getClient($from);
        if (!$client) {
            $from->close();
            return;
        }
        
        $obj = json_decode($msg);
        print_r($obj);
        
        // Login
        if ($obj->type === 'login' && !$client->getUser()) {
            $user = $client->login($obj->message->username, $obj->message->password);
            if (!$user) {
                // User does not exist!
                echo "User login: does not exist\n";
                $client->send([
                    'type' => 'login',
                    'success' => false,
                    'message' => 'Username or password is incorrect!'
                ]);
            }
            else {
                // Send success string to client
                $client->send([
                    'type' => 'login',
                    'success' => true,
                    'message' => [
                        'name' => $client->getUser()->getName(),
                        'permissions' => $client->getUser()->getPermissions(),
                        'channels' => $client->getUser()->getChannels()
                    ]
                ]);
                
                // Broadcast login to the users channels
                $client->getUser()->broadcast([
                    'type' => 'online',
                    'from' => $client->getUser()->getName(),
                    'message' => null
                ]);
                
                // TODO: Feed past history to client
                
                echo "User login: Logged in!\n";
            }
        }
        
        if (!$client->getUser())
            return;
        
        // Handle events
        if ($obj->type === 'message') {
            
        }
        else if ($obj->type === 'join') {
            
        }
    }

    public function onClose(ConnectionInterface $conn) {
        echo "Connection {$conn->resourceId} has disconnected\n";
        
        $client = $this->getClient($conn);
        if ($client) {
            $client->logout();
        }
        
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
        $this->clients->detach($conn);
    }
    
    // Get client from Client Storage
    private function getClient(ConnectionInterface $conn)
    {
        foreach($this->clients as $client) {
            if ($client->getConnection() === $conn) {
                return $client;
            }
        }
        return null;
    }
}