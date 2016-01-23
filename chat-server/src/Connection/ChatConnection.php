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
        $this->chat->db->loginUser($this->user);
        
        // Join default channel if empty
        if (count($chans) == 0) {
            $perms = $this->chat->db->getUserChannelPermissions($this->user->getName(), $this->chat->getDefaultChannel()->getName());
            $this->chat->getDefaultChannel()->addUser($this->user, $perms);
            $this->user->joinChannel($this->chat->getDefaultChannel(), $perms);
            $this->chat->db->addUserToChannel($this->user, $this->chat->getDefaultChannel()->getName(), $perms);
        }
        return $this->user;
    }
    
    public function logout($msg)
    {
        if (!$this->user)
            return;
        
        foreach($this->user->getChannels() as $chan) {
            // Send logout to channel users
            $chan['chan']->send([
                'type' => 'offline',
                'from' => $this->user->getName(),
                'message' => $msg
            ]);
                
            // Remove user from active users in channel
            $chan['chan']->removeUser($this->user);
        }
        
        $this->chat->db->logoutUser($this->user);
    }

    public function send(array $data)
    {
        $this->connection->send(json_encode($data));
    }
}
?>