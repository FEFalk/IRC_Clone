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
        echo "Created default channel {$defaultchan->getName()}\n";
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
            else
                echo "User login failed\n";
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
                echo "{$client->getUser()->getName()} -> {$obj->to} ({$obj->message})\n";
            else
                echo "Message failed\n";
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
                echo "User joined channel {$obj->message->chan}\n";
            else
                echo "User unable to join channel {$obj->message->chan}\n";
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
        if ($this->getClientByName($obj->message->username)) {
            $client->send([
                'type' => 'rlogin',
                'success' => false,
                'message' => ErrorCodes::LOGIN_ALREADY_LOGGEDIN
            ]);
            return false;
        }
        
        $user = $client->login($obj->message->username, $obj->message->password);
        if (!$user) {
            // Username or password is incorrect!
            echo "User login: name or password incorrect\n";
            $client->send([
                'type' => 'rlogin',
                'success' => false,
                'message' => ErrorCodes::LOGIN_INCORRECT
            ]);
            return false;
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
        return true;
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
            else if ($chan->hasMode(Permissions::MODE_MODERATED)
                    && !$chan->userHasPermissions($client->getUser(), Permissions::CHANNEL_OPERATOR | CHANNEL_VOICE))
                $error = ErrorCodes::CHANNEL_MODERATED;
            
            if (isset($error)) {
                $client->send([
                    'type' => 'rmessage',
                    'success' => false,
                    'message' => $error
                ]);
                return false;
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
                    return false;
                }
            }
            else {
                // Send message to receiver
                $receiver->send([
                    'type' => 'message',
                    'from' => $client->getUser()->getName(),
                    'to' => $receiver->getUser()->getName(),
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
        return true;
    }
    
    public function parseQuit($client, $obj)
    {
        // Do we need this?
    }
    
    public function parseJoin($client, $obj)
    {
        $chan = $this->getChannelByName($obj->message->chan);
        if (!$chan) {
            // Channel does not exist or has currently no active users (destructed)
            $ch = $this->db->getChannelInfo($obj->message->chan);
            if (!$ch) {
                // Channel does not exist, create it
                if ($this->db->createChannel($obj->message->chan))
                    $chan = new Channel($obj->message->chan, $this);
                else
                    $error = ErrorCodes::UNKNOWN_ERROR;
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
        
        // Check password and user limit if user is not a server operator
        if ($chan && !$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR)) {
            if ($chan->hasPassword() && $chan->getPassword() != $obj->message->password)
                $error = ErrorCodes::CHANNEL_PASSWORD_MISMATCH;
            else if ($chan->getUserCount() < $chan->getUserLimit())
                $error = ErrorCodes::CHANNEL_USERLIMIT_REACHED;
        }
        
        if (isset($error)) {
            $client->send([
                'type' => 'rjoin',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        // We have a channel and the user has provided the correct password/userlimit not reached
        $permissions = $this->db->getUserChannelPermissions($client->getUser()->getUserId(), $chan->getName());
        $chan->addUser($client->getUser(), $permissions);
        
        // Send join event to users in channel
        $chan->send([
            'type' => 'join',
            'from' => $client->getUser()->getName(),
            'message' => null
        ]);
        
        // Populate user list
        $users = $this->db->getChannelUsers($chan->getName());
        foreach($chan->getUsers() as $u => $p) {
            $users[$u]['active'] = true;
        }
        
        // Send success string
        $client->send([
            'type' => 'rjoin',
            'success' => true,
            'message' => [
                'name' => $chan->getName(),
                'topic' => $chan->getTopic(),
                'modes' => $chan->getModes(),
                'userlimit' => $chan->getUserLimit(),
                'permissions' => $permissions,
                'users' => $users
            ]
        ]);
        return true;
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
    
    // Get client by Connection
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
            if ($client->getUser() && strtolower($client->getUser()->getName()) === strtolower($name)) {
                return $client;
            }
        }
        return null;
    }
    
    private function getChannelByName($name)
    {
        foreach($this->channels as $chan) {
            if (strtolower($chan->getName()) === strtolower($name)) {
                return $chan;
            }
        }
        return null;
    }
}