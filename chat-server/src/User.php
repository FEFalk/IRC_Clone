<?php

namespace IRCClone;

use IRCClone\Chat;
use IRCClone\Connection\ChatConnection;
use IRCClone\Channel;

class User
{
    private $connection;
    private $chat;
    
    private $channels;
    
    private $id;
    private $name;
    private $email;
    private $permissions; // Server permissions
    private $last_login;
    private $last_logout;
    
    public function __construct(array $userinfo, ChatConnection $conn, Chat $chat)
    {
        $this->connection = $conn;
        $this->chat = $chat;
        $this->channels = new \SplObjectStorage;
        
        $this->id = $userinfo['id'];
        $this->name = $userinfo['name'];
        $this->email = $userinfo['email'];
        $this->permissions = $userinfo['permissions'];
        $this->last_login = time();
        $this->last_logout = $userinfo['last_logout'];
        
        foreach($userinfo['channels'] as $c => $p) {
            $this->joinChannel($this->chat->getChannelOrCreate($c), $p);
        }
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
        $chan->addUser($this, $permissions);
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
    
    public function getChannels($name_only = false)
    {
        $chans = array();
        foreach($this->channels as $ch) {
            $chans[$ch->chan->getName()] = array(
                'permissions' => $ch->permissions,
                'modes' => $ch->chan->getModes(),
                'topic' => $ch->chan->getTopic());
                
            if (!$name_only)
                $chans[$ch->chan->getName()]['chan'] = $ch->chan;
        }
        return $chans;
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
    
    public function getLastLogin()
    {
        return $this->last_login;
    }
    
    public function getLastLogout()
    {
        return $this->last_logout;
    }
}