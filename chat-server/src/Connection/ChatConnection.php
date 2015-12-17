<?php

namespace IRCClone\Connection;

use IRCClone\Chat;
use IRCClone\User;
use Ratchet\ConnectionInterface;

class ChatConnection implements ChatConnectionInterface
{
    private $connection;
    private $chat;
    private $user;

    public function __construct(ConnectionInterface $conn, Chat $chat)
    {
        $this->connection = $conn;
        $this->chat = $chat;
        $this->user = null;
    }

    public function getConnection()
    {
        return $this->connection;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function login($username, $password)
    {
        // Already logged in?
        if ($this->user)
            return;
        
        // Get user info by username
        $userinfo = $this->chat->db->getUserInfo($username);
        
        // Username does not exist or password is incorrect
        if (!$userinfo || !password_verify($password, $userinfo['password']))
            return null;
        
        // Get user channels
        $chans = $this->chat->db->getUserChannels($userinfo['id']);
        
        $userinfo['channels'] = $chans;
        unset($userinfo['password']);
        
        // Create User object
        $this->user = new User($userinfo, $this, $this->chat);
        
        // Join default channel if empty
        if (count($chans) == 0)
            $this->user->joinChannel($this->chat->getChannelByName('#Default'), 0);
        return $this->user;
    }
    
    public function logout()
    {
        if (!$this->user)
            return;
        
        /*foreach($this->getUser()->getChannels() as $chan) {
            // Send logout to channel users
            $chan->chan->send([
                    'type' => 'offline',
                    'from' => $this->name,
                    'message' => null
                ]);
                
            // Remove user from active users in channel
            $chan->removeUser($this->getUser());
        }*/
    }

    public function setName($name, $bot = false)
    {
        // TODO: Move to onMessage
        $error = false;
        // Check if the name is invalid or already exists
        if (!preg_match('/\A[a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]{2,15}\z/i', $name))
        {
            $error = 'invalid';
        }
        else if ($this->repository->getClientByName($name) !== null)
        {
            $error = 'exists';
        }
        
        if ($error)
        {
            $this->send([
                'type'    => 'setname',
                'success' => false,
                'message' => $error
            ]);
        }
        else
        {
            $this->name = $name;

            $this->send([
                'type'  => 'setname',
                'success' => true,
                'message' => $this->name
            ]);
            
            
        }
    }

    public function send(array $data)
    {
        $this->connection->send(json_encode($data));
    }
}
?>