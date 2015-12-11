<?php

namespace IRCClone\User;

use IRCClone\Chat;
use IRCClone\Connection\ChatConnection;
use IRCClone\Channel;

class User implements UserInterface
{
    private $connection;
    private $chat;
    
    private $channels;
    
    private $id;
    private $name;
    private $email;
    private $permissions; // Server permissions
    
    public function __construct(array $userinfo, ChatConnection $conn, Chat $chat)
    {
        $this->connection = $conn;
        $this->chat = $chat;
        $this->channels = new \SplObjectStorage;
        
        $this->id = $userinfo['id'];
        $this->name = $userinfo['name'];
        $this->email = $userinfo['email'];
        $this->permissions = $userinfo['permissions'];
    }
    
    // Broadcast to user channels
    public function broadcast(array $msg)
    {
        $msg['from'] = $this->name;
        foreach($this->channels as $obj) {
            $obj->chan->send($msg);
        }
    }
    
    public function send(array $msg)
    {
        $this->connection->send($msg);
    }
    
    public function joinChannel(Channel $chan, $permissions)
    {
        $this->channels->attach((object) array('chan' => $chan, 'permissions' => $permissions));
    }
    
    public function leaveChannel(Channel $chan)
    {
        foreach($this->channels as $ch) {
            if ($ch->chan === $chan) {
                $this->channels->detach($ch);
                return;
            }
        }
    }
    
    public function inChannel(Channel $chan)
    {
        foreach($this->channels as $ch) {
            if ($ch->chan === $chan)
                return true;
        }
        return false;
    }
    
    public function getChannels()
    {
        return $this->channels;
    }
    
    public function getUserId()
    {
        return $this->id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getPermissions()
    {
        return $this->permissions;
    }
    
    public function addPermission($flag)
    {
        $this->permissions |= $flag;
    }
    
    public function removePermission($flag)
    {
        $this->permissions &= (~ $flag);
    }
    
    public function hasPermission($flag)
    {
        return $this->permissions & $flag;
    }
}