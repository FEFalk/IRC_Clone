<?php

namespace IRCClone;

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
    
    /*
     * Send message to all users in the channel
     * @param array $msg The event-based message array
     */
    public function send(array $msg)
    {
        // TODO: Add message to database
        $msg['to'] = $this->name;
        foreach($this->users as $user)
        {
            $user->user->send($msg);
        }
    }
    
    /*
     * Get the usercount of the channel
     * @return int Usercount
     */
    public function getUserCount()
    {
        return $this->users->count();
    }
    
    /*
     * Check if a user has permissions
     * @param User $user The user
     * @param int $permissions Permissions
     * @return boolean True if the user has provided permissions
     */
    public function userHasPermissions(User $user, $permissions)
    {
        foreach($this->users as $u)
        {
            if ($u->user === $user)
                return $u->permissions & $permissions;
        }
        return false;
    }
    
    /*
     * Add user to channel
     * @param User $user The user
     * @param int $permissions User permissions
     */
    public function addUser(User $user, $permissions)
    {
        $this->users->attach((object) array('user' => $user, 'permissions' => $permissions));
    }
    
    /*
     * Set a users permissions
     * @param User $user The user
     * @param int $permissions User permissions
     */
    public function setUserPermissions(User $user, $permissions)
    {
        foreach($this->users as $u) {
            if ($u->user === $user) {
                $u->permissions = $permissions;
                return;
            }
        }
    }
    
    /*
     * Get a users permissions
     * @param User $user The user
     * @param int $permissions User permissions
     */
    public function getUserPermissions(User $user)
    {
        foreach($this->users as $u) {
            if ($u->user === $user)
                return $u->permissions;
        }
        return 0;
    }
    
    /*
     * Remove a user from the channel
     * @param User $user The user
     */
    public function removeUser(User $user)
    {
        foreach($this->users as $u) {
            if ($u->user === $user) {
                $this->users->detach($u);
                return;
            }
        }
    }
    
    /*
     * Check if a user is in a channel
     * @param User $user The user
     * @return boolean True if the user is in the channel
     */
    public function hasUser(User $user)
    {
        foreach($this->users as $u) {
            if ($u->user === $user)
                return true;
        }
        return false;
    }
    
    /**
     * Get an array of active users in the channel
     * @return array name => permissions
     */
    public function getUsers()
    {
        $users = array();
        foreach($this->users as $u) {
            $users[$u->user->getName()] = $u->permissions;
        }
        return $users;
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
    
    public function hasPassword()
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
        return $this->modes ? $this->modes : 0;
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
    
    public function getUserLimit()
    {
        return $this->userlimit;
    }
    
    public function setUserLimit($limit)
    {
        $this->userlimit = $limit;
    }
}