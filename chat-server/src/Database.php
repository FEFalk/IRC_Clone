<?php

namespace IRCClone;

use \PDO;

class Database
{
    private $db;
    private $username, $password, $ip, $database;
    
    public function __construct($ip, $db, $user, $pass)
    {
        $this->ip = $ip;
        $this->database = $db;
        $this->username = $user;
        $this->password = $pass;
    }
    
    public function connect()
    {
        $connstring = 'mysql:host='. $this->ip .';dbname='. $this->database .';';
        $this->db = new PDO($connstring, $this->username, $this->password,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
    }
    
    public function getUserInfo($user)
    {
        $stmt = $this->db->prepare('SELECT `id`, `name`, `password`, `email`, `permissions` FROM `users` WHERE `name` = ? LIMIT 1;');
        $stmt->execute(array($user));
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;
    }
    
    public function getUserChannels($user)
    {
        $chans = array();
        $stmt = $this->db->prepare('SELECT `channel`, `permissions` FROM `user_channels` WHERE `user` = ?;');
        $stmt->execute(array($user));
        while ($res = $stmt->fetch()) {
            $chans[$res['channel']] = $res['permissions'];
        }
        $stmt->closeCursor();
        return $chans;
    }
}