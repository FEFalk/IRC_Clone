<?php

namespace IRCClone;

use \PDO;
use IRCClone\User;

class Database
{
    private $db;
    private $username, $password, $ip, $database;
    
    /**
     * @param string $ip IP address to the database
     * @param string $db Database name
     * @param string $user Login name
     * @param string $pass Password
     */
    public function __construct($ip, $db, $user, $pass)
    {
        $this->ip = $ip;
        $this->database = $db;
        $this->username = $user;
        $this->password = $pass;
    }
    
    /**
     * Connect to the database
     * @throws PDOException if a connection could not be established
     */
    public function connect()
    {
        $connstring = 'mysql:host='. $this->ip .';dbname='. $this->database .';';
        $this->db = new PDO($connstring, $this->username, $this->password,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
    }
    
    /**
     * Get user info
     * @param string $username Users name
     * @return array id, name, password, email and permissions of the user
     */
    public function getUserInfo($username)
    {
        $stmt = $this->db->prepare('SELECT `id`, `name`, `password`, `email`, `permissions`, `last_login`, `last_logout` FROM `users` WHERE `name` = ? OR `email` = ? LIMIT 1;');
        $stmt->execute(array($username, $username));
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;
    }
    
    /**
     * Get the channels a user is subscribed to
     * @param int @userid A users ID
     * @return array The users channels
     */
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
    
    /**
     * Get an array of all users in a channel, active or inactive
     * @param string $channel Channel name
     * @return array List of users
     */
    public function getChannelUsers($channel)
    {
        $users = array();
        $stmt = $this->db->prepare('SELECT `users`.`name`, `user_channels`.`permissions` FROM `user_channels` INNER JOIN `users` ON `user_channels`.`user` = `users`.`id` WHERE `channel` = ?;');
        $stmt->execute(array($channel));
        while ($res = $stmt->fetch()) {
            $users[$res['name']] = array('permissions' => $res['permissions'], 'active' => false);
        }
        $stmt->closeCursor();
        return $users;
    }
    
    /**
     * Get offline messages of a user
     * @param User $user The user
     * @param int $limit Number of messages to fetch from each channel
     * @return array Requested messages
     */
    public function getOfflineMessages(User $user, $limit = 30)
    {
        $result = array();
        $stmt = $this->db->prepare('
            SELECT `users`.`name` AS `from`, `message`, `date`
            FROM `events`
            INNER JOIN `users` ON `users`.`id` = `userid`
            WHERE `type` = ? AND `to` = ? AND `date` < ?
            LIMIT ?;');
        $stmt->execute(array('message', $user->getName(), $user->getLastLogout(), PHP_INT_MAX));
        $result[$user->getName()] = $stmt->fetchAll();
        $stmt->closeCursor();
        foreach($user->getChannels() as $name => $arr) {
            $stmt->execute(array('message', $name, $user->getLastLogout(), $limit));
            $result[$name] = $stmt->fetchAll();
            $stmt->closeCursor();
        }
        return $result;
    }
    
    /**
     * Get channel information
     * @param string $chan Channel name
     * @return array|null Result set (name, modes, topic, password, userlimit)
     */
    public function getChannelInfo($chan)
    {
        $stmt = $this->db->prepare('SELECT `name`, `modes`, `topic`, `password`, `userlimit` FROM `channels` WHERE `name` = ? LIMIT 1;');
        $stmt->execute(array($chan));
        $chan = $stmt->fetch();
        $stmt->closeCursor();
        return $chan;
    }
    
    /**
     * Create a new channel
     * @param string $name Channel name
     * @return boolean True on success
     */
    public function createChannel($name)
    {
        $stmt = $this->db->prepare('
            INSERT INTO `channels` (`name`, `modes`, `userlimit`)
            VALUES(?, 0, 0);');
        $stmt->execute(array($name));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Get user permissions in a channel
     * @param int $userid User ID
     * @param string $channel Channel name
     * @return int The users permissions
     */
    public function getUserChannelPermissions($userid, $channel)
    {
        $stmt = $this->db->prepare('SELECT `permissions` FROM `user_channels` WHERE `user` = ? AND `channel` = ?');
        $stmt->execute(array($userid, $channel));
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result != null ? $result['permissions'] : 0;
    }
    
    /**
     * Login a user
     * @param User $user User object
     */
    public function loginUser(User $user) 
    {
        // Update last_login
        $stmt = $this->db->prepare('UPDATE `users` SET `last_login` = ? WHERE `id` = ?;');
        $stmt->execute(array(time(), $user->getUserId()));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Logout a user
     * @param User $user User object
     */
    public function logoutUser(User $user)
    {
        // Update last_logout
        $stmt = $this->db->prepare('UPDATE `users` SET `last_logout` = ? WHERE `id` = ?;');
        $stmt->execute(array(time(), $user->getUserId()));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    public function changeUserName(User $user, $newname)
    {
        $stmt = $this->db->prepare('UPDATE `users` SET `name` = ? WHERE `id` = ?;');
        $stmt->execute(array($newname, $user->getUserId()));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Log an event to the database
     * @param int $userid User ID (0 for SERVER)
     * @param string $to #Channel or Username
     * @param string $type Event type
     * @param string $message The message
     * @param int $date (OPTIONAL) Event date
     */
    public function addEvent($userid, $to, $type, $message, $date = 0)
    {
        if ($date === 0)
            $date =  time();
        $stmt = $this->db->prepare('INSERT INTO `events` (`userid`, `to`, `type`, `message`, `date`) VALUES(?, ?, ?, ?, ?)');
        $stmt->execute(array($userid, $to, $type, $message, $date));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
}