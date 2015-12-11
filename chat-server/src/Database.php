<?php

namespace IRCClone;

use \PDO;
use IRCClone\User;

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
    
    public function getUserInfo($username)
    {
        $stmt = $this->db->prepare('SELECT `id`, `name`, `password`, `email`, `permissions` FROM `users` WHERE `name` = ? OR `email` = ? LIMIT 1;');
        $stmt->execute(array($username, $username));
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;
    }
    
    public function getUserChannels($userid)
    {
        $chans = array();
        $stmt = $this->db->prepare('SELECT `channel`, `permissions` FROM `user_channels` WHERE `user` = ?;');
        $stmt->execute(array($userid));
        while ($res = $stmt->fetch()) {
            $chans[$res['channel']] = $res['permissions'];
        }
        $stmt->closeCursor();
        return $chans;
    }
    
    public function getOfflineMessages(User $user, $limit = 30)
    {
        $result = array();
        $stmt = $this->db->prepare('
            SELECT `users`.`name` AS `from`, `message`, `date`
            FROM `events`
            INNER JOIN `users` ON `users`.`id` = `userid`
            WHERE `type` = ? AND `to` = ?
            LIMIT ?;');
        $stmt->execute(array('message', $user->getName(), PHP_INT_MAX));
        $result[$user->getName()] = $stmt->fetchAll();
        $stmt->closeCursor();
        foreach($user->getChannels() as $chan) {
            $stmt->execute(array('message', $chan->getName(), $limit));
            $result[$chan->getName()] = $stmt->fetchAll();
            $stmt->closeCursor();
        }
        return $result;
    }
}