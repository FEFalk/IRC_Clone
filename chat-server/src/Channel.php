<?php

namespace IRCClone\Channel;

use IRCClone\Chat;
use IRCClone\User;

class Channel
{
    private $chat;
    private $users;
    
    private $name;
    private $modes;
    private $topic;
    private $password;
    private $userlimit;
    
    public function __construct($name, Chat $chat)
    {
        $this->chat = $chat;
        $this->name = $name;
        $this->users = new \SplObjectStorage;
    }
    
    // Broadcast to active users
    public function send(array $msg)
    {
        $msg['to'] = $this->name;
        foreach($this->users as $user)
        {
            $user->send($msg);
        }
    }
    
    public function addUser(User $user)
    {
        $this->users->attach($user);
    }
    
    public function removeUser(User $user)
    {
        $this->users->detach($user);
    }
    
    public function hasUser(User $user)
    {
        return $this->users->contains($user);
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getTopic()
    {
        return $this->topic;
    }
    
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }
    
    public function isPassword()
    {
        return !!$this->password;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    public function setPassword($password)
    {
        $this->password = $password;
    }
    
    public function getModes()
    {
        return $this->modes;
    }
    
    public function addMode($flag)
    {
        $this->modes |= $flag;
    }
    
    public function removeMode($flag)
    {
        $this->modes &= (~ $flag);
    }
    
    public function hasMode($flag)
    {
        return $this->modes & $flag;
    }
}