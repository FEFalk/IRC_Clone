<?php

namespace IRCClone\Channel;

interface ChannelInterface
{
    public function send(array $msg)
    
    public function getName();
    public function getTopic();
    public function setTopic($topic);
    
    public function isPassword();
    public function getPassword();
    public function setPassword($password);
    
    public function getModes();
    public function addMode($flag);
    public function removeMode($flag);
    public function hasMode($flag);
    
}