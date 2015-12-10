<?php

namespace IRCClone\Channel;

interface ChannelInterface
{
    public function send(array $msg)
    
    public function getName();
    public function getTopic();
    public function setTopic(string $topic);
    
    public function isPassword();
    public function getPassword();
    public function setPassword(string $password);
    
    public function getModes();
    public function addMode(int $flag);
    public function removeMode(int $flag);
    public function hasMode(int $flag);
    
}