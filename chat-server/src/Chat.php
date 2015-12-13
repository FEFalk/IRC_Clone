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
        
        // Initialise the default channel
        $defaultchan = new Channel("#Default", $this);
        $defaultchan->setTopic('Welcome to the Default channel! Type /help for help with the chat.');
        $this->channels->attach($defaultchan);
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
        if (!$obj)
            return;
        
        // Login
        if ($obj->type === 'login' && !$client->getUser()) {
            if ($this->parseLogin($client, $obj))
                echo "User login: Logged in!\n";
        }
        
        // Other events require a logged in user
        if (!$client->getUser())
            return;
        
        /**
         * Handle events
         */
         
        // Messages
        if ($obj->type === 'message') {
            if ($this->parseMessage($client, $obj))
                echo "Message parsed successfully\n";
            else
                echo "Message parse failed\n";
        }
        
        // Server operations
        else if ($obj->type === 'quit') {
            if ($this->parseQuit($client, $obj))
                echo "User quit {$client->getUser()->getName()}\n";
            else
                echo "User unable to quit??? {$client->getUser()->getName()}\n";
        }
        
        // Channel operations
        else if ($obj->type === 'join') {
            if ($this->parseJoin($client, $obj))
                echo "User joined channel {$obj->message}\n";
            else
                echo "User unable to join channel {$obj->message}\n";
        }
        else if ($obj->type === 'part') {
            // TODO: Leave channel
        }
        else if ($obj->type === 'topic') {
            // TODO: Change channel topic
        }
        else if ($obj->type === 'mode') {
            // TODO: Change user permissions in a channel
        }
        else if ($obj->type === 'kick') {
            // TODO: Kick user from channel
        }
        else if ($obj->type === 'ban') {
            // TODO: (Un-)Ban user from channel
        }
        
        // TODO: More events?
    }
    
    public function parseLogin($client, $obj)
    {
        $user = $client->login($obj->message->username, $obj->message->password);
        if (!$user) {
            // Username or password is incorrect!
            echo "User login: name or password incorrect\n";
            $client->send([
                'type' => 'rlogin',
                'success' => false,
                'message' => ErrorCodes::LOGIN_INCORRECT
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
        
        $offlinemsgs = $this->db->getOfflineMessages($client->getUser());
        $client->send([
            'type' => 'loginmsgs',
            'message' => $offlinemsgs
        ]);
        
        // TODO: Add login event to database
    }

    public function parseMessage($client, $obj)
    {
        // Sent to a channel
        if ($obj->to[0] == '#') {
            $chan = $this->getChannelByName($obj->to);
            if (!$chan)
                $error = ErrorCodes::CHANNEL_NOT_EXIST;
            else if (!$chan->hasUser($client->getUser()))
                $error = ErrorCodes::USER_NOT_IN_CHANNEL;
            
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
        }
        // User
        else {
            $receiver = $this->getClientByName($obj->to);
            if (!$receiver) {
                // User might be offline
                if (!$this->db->sendMessageToUser($client->getUser(), $obj->to, $obj->message)) {
                    $client->send([
                        'type' => 'rmessage',
                        'success' => false,
                        'message' => ErrorCodes::USER_NOT_EXIST
                    ]);
                    return;
                }
            }
            else {
                // Send message to receiver
                $receiver->send([
                    'type' => 'message',
                    'from' => $client->getUser()->getName(),
                    'to' => '',
                    'message' => $obj->message
                ]);
            }
        }
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rmessage',
            'success' => true,
            'message' => null
        ]);
    }
    
    public function parseQuit($client, $obj)
    {
        // Do we need this?
    }
    
    public function parseJoin($client, $obj)
    {
        $chan = $this->getChannelByName($obj->message);
        if (!$chan) {
            // Channel does not exist or has currently no active users (destructed)
            $ch = $this->db->getChannelInfo($obj->message->chan);
            if (!$ch) {
                // Channel does not exist, create it
                if ($this->db->createChannel($obj->message->chan))
                    $chan = new Channel($obj->message->chan, $this);
            }
            else {
                // Channel exists but is destructed
                $chan = new Channel($ch['name'], $this);
                $chan->setTopic($ch['topic']);
                $chan->addMode($ch['modes']);
                $chan->setUserLimit($ch['userlimit']);
                $chan->setPassword($ch['password']);
            }
        }
        
        if (!$chan)
            $error = ErrorCodes::UNKNOWN_ERROR;
        else if (!$chan->userHasPermissions($client->getUser(), CHANNEL_OPERATOR | CHANNEL_VOICE)
                && ($chan->hasPassword() && $chan->getPassword() != $obj->message->password))
            $error = ErrorCodes::CHANNEL_PASSWORD_MISMATCH;
        else if (!$chan->userHasPermissions($client->getUser(), CHANNEL_OPERATOR | CHANNEL_VOICE)
                && $chan->getUserCount() < $chan->getUserLimit())
            $error = ErrorCodes::CHANNEL_USERLIMIT_REACHED;
        
        if ($error) {
            $client->send([
                'type' => 'rjoin',
                'success' => false,
                'message' => $error
            ]);
            return;
        }
        
        // We have a channel and the user has permissions to join
        $permissions = $this->db->getUserChannelPermissions($client->getUser()->getUserId(), $chan->getName());
        $chan->addUser($client->getUser(), $permissions);
        
        // Send join event to users in channel
        $chan->send([
            'type' => 'join',
            'from' => $client->getUser()->getName(),
            'message' => null
        ]);
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rjoin',
            'success' => true,
            'message' => null
        ]);
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