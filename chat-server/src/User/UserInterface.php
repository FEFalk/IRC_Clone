<?php

namespace IRCClone\User;

use IRCClone\Channel;

interface UserInterface
{
    public function getUserId();
    
    public function getName();
    public function setName($name);
    
    public function getPermissions();
    public function hasPermission($flag);
    public function addPermission($flag);
    public function removePermission($flag);
    
    public function broadcast(array $msg);
    public function getChannels();
    public function inChannel(Channel $chan);
    public function joinChannel(Channel $chan, $permissions);
    public function leaveChannel(Channel $chan);
    
    
}