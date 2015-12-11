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
        // TODO: Functionize! parseLogin(...), parseMessage(...), ...?
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
                    'type' => 'rlogin',
                    'success' => false,
                    'message' => 'Username or password is incorrect!' // Consider moving to error codes
                ]);
                return;
            }
            
            // Send success string to client
            $client->send([
                'type' => 'rlogin',
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
                'message' => null
            ]);
            
            // TODO: Feed past history to client
            $offlinemsgs = $this->db->getOfflineMessages($client->getUser());
            $client->send([
                'type' => 'loginmsgs',
                'message' => $offlinemsgs
            ]);
            
            echo "User login: Logged in!\n";
        }
        
        // Other events require a logged in user
        if (!$client->getUser())
            return;
        
        // Handle events
        if ($obj->type === 'message') {
            // Sent to a channel?
            if ($obj->to[0] == '#') {
                $chan = $this->getChannelByName($obj->to);
                if (!$chan) {
                    $error = 'Channel does not exist';
                }
                else if (!$chan->hasUser($client->getUser())) {
                    $error = 'You are not in this channel';
                }
                
                if ($error) {
                    $client->send([
                        'type' => 'rmessage',
                        'success' => false,
                        'message' => $error
                    ]);
                    return;
                }
                
                // Send message to channel
                $chan->send([
                    'type' => 'message',
                    'from' => $client->getUser()->getName(),
                    'message' => $obj->message
                ]);
                $success = true;
            }
            // User
            else {
                $receiver = $this->getClientByName($obj->to);
                if (!$receiver) {
                    // User might be offline
                    /* if ($this->db->sendMessageToUser($obj->to, $obj->message)) {
                        $success = true;
                    }*/
                    $client->send([
                        'type' => 'rmessage',
                        'success' => false,
                        'message' => 'User does not exist'
                    ]);
                    return;
                }
                
                // Send message to receiver
                $receiver->send([
                    'type' => 'message',
                    'from' => $client->getUser()->getName(),
                    'to' => '',
                    'message' => $obj->message
                ]);
                $success = true;
            }
            
            if ($success) {
                // Success string to client
                $client->send([
                    'type' => 'rmessage',
                    'success' => true,
                    'message' => null
                ]);
            }
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
    
    // Get client by Connection from Client Storage
    private function getClient(ConnectionInterface $conn)
    {
        foreach($this->clients as $client) {
            if ($client->getConnection() === $conn) {
                return $client;
            }
        }
        return null;
    }
    
    private function getClientByName($name)
    {
        foreach($this->clients as $client) {
            if ($client->getUser()->getName() === $name) {
                return $client;
            }
        }
        return null;
    }
    
    private function getChannelByName($name)
    {
        foreach($this->channels as $chan) {
            if ($chan->getName() === $name) {
                return $chan;
            }
        }
        return null;
    }
}