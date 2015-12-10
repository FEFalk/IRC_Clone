<?php
namespace IRCClone\Connection;

interface ChatConnectionInterface
{
    public function getConnection();
    public function getUser();
    public function login($username, $password);
    public function logout();
    public function send(array $data);
}