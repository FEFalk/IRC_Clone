<?php

namespace IRCClone;

use \PDO;
use IRCClone\User;
use IRCClone\Permissions;

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
        $this->db = new PDO($connstring, $this->username, $this->password, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
    }
    
    // Fix for timed out connections
    public function prepare($query)
    {
        try {
            $stmt = $this->db->prepare($query);
        }
        catch (PDOException $ex) {
            // Connection has timed out. Reconnect
            if (strpos($ex->getMessage(), 'server has gone away') !== false) {
                $this->connect();
                $stmt = $this->db->prepare($query);
            }
            else {
                throw $ex;
            }
        }
        return $stmt;
    }
    
    /**
     * Get user info
     * @param string $username Users name
     * @return array id, name, password, email and permissions of the user
     */
    public function getUserInfo($username)
    {
        $stmt = $this->prepare('SELECT `id`, `name`, `password`, `email`, `permissions`, `last_login`, `last_logout` FROM `users` WHERE `name` = ? OR `email` = ? LIMIT 1;');
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
        $stmt = $this->prepare('SELECT `channel`, `permissions` FROM `user_channels` WHERE `user` = ?;');
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
        $stmt = $this->prepare('SELECT `users`.`name`, `user_channels`.`permissions` FROM `user_channels` INNER JOIN `users` ON `user_channels`.`user` = `users`.`id` WHERE `channel` = ?;');
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
        $stmt = $this->prepare("
            SELECT `users`.`name` AS `from`, `message`, `date`
            FROM `events`
            INNER JOIN `users` ON `users`.`id` = `userid`
            WHERE `type` = 'message' AND `to` = :to AND `date` > :date
            LIMIT :limit;");
        $stmt->bindParam(':to', $user->getName(), PDO::PARAM_STR);
        $stmt->bindParam(':date', $user->getLastLogout(), PDO::PARAM_INT);
        $temp = PHP_INT_MAX;
        $stmt->bindParam(':limit', $temp, PDO::PARAM_INT);
        $stmt->execute();
        $result[$user->getName()] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        foreach($user->getChannels(true) as $name => $arr) {
            $stmt->bindParam(':to', $name, PDO::PARAM_STR);
            $stmt->bindParam(':date', $user->getLastLogout(), PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $result[$name] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt = $this->prepare('SELECT `name`, `modes`, `topic`, `password`, `userlimit` FROM `channels` WHERE `name` = ? LIMIT 1;');
        $stmt->execute(array($chan));
        $chan = $stmt->fetch();
        $stmt->closeCursor();
        return $chan;
    }
    
    public function searchChannel($chan)
    {
        $chans = array();
        $stmt = $this->prepare('SELECT `name`, `topic` FROM `channels` WHERE `name` LIKE ? AND NOT `modes` & '. Permissions::MODE_PRIVATE .';');
        $stmt->execute(array('%'.$chan.'%'));
        while ($res = $stmt->fetch()) {
            $chans[$res['name']] = $res['topic'];
        }
        $stmt->closeCursor();
        return $chans;
    }
    
    /**
     * Change channel info
     * @param string $chan The channel name
     * @param array $info (modes, topic, password, userlimit)
     */
    public function setChannelInfo($chan, $info)
    {
        $query = 'UPDATE `channels` SET ';
        foreach($info as $key => $val)
            $query .= '`' . $key . '` = :'.$key.' ';
        $query .= 'WHERE `name` = :name;';
        $stmt = $this->prepare($query);
        foreach($info as $key => $val)
            $stmt->bindParam(':'.$key, $val, gettype($val) == 'integer' ? PDO::PARAM_INT : PDO::PARAM_STR);
        $stmt->bindParam(':name', $chan, PDO::PARAM_STR);
        $stmt->execute();
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Create a new channel
     * @param string $name Channel name
     * @return boolean True on success
     */
    public function createChannel($name)
    {
        $stmt = $this->prepare('
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
        $stmt = $this->prepare('SELECT `permissions` FROM `user_channels` WHERE `user` = ? AND `channel` = ?');
        $stmt->execute(array($userid, $channel));
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result != null ? $result['permissions'] : 0;
    }
    
    public function setUserChannelPermissions($chan, $userid, $permissions)
    {
        $stmt = $this->prepare('UPDATE `user_channels` SET `permissions` = ? WHERE `user` = ? AND `channel` = ?;');
        $stmt->execute(array($permissions, $userid, $chan));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Login a user
     * @param User $user User object
     */
    public function loginUser(User $user) 
    {
        // Update last_login
        $stmt = $this->prepare('UPDATE `users` SET `last_login` = ? WHERE `id` = ?;');
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
        $stmt = $this->prepare('UPDATE `users` SET `last_logout` = ? WHERE `id` = ?;');
        $stmt->execute(array(time(), $user->getUserId()));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Change user name
     * @param User $user User object
     * @param string $newname The new name
     */
    public function changeUserName(User $user, $newname)
    {
        $stmt = $this->prepare('UPDATE `users` SET `name` = ? WHERE `id` = ?;');
        $stmt->execute(array($newname, $user->getUserId()));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Add user to channel
     * @param User $user User object
     * @param string $chan channel
     */
    public function addUserToChannel(User $user, $chan, $permissions = 0)
    {
        $stmt = $this->prepare('INSERT INTO `user_channels` (`user`, `channel`, `permissions`) VALUES(?, ?, ?);');
        $stmt->execute(array($user->getUserId(), $chan, $permissions));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
    
    /**
     * Add user to channel
     * @param User $user User object
     * @param string $chan channel
     */
    public function removeUserFromChannel(User $user, $chan)
    {
        $stmt = $this->prepare('DELETE FROM `user_channels` WHERE `user` = ? AND `channel` = ?;');
        $stmt->execute(array($user->getUserId(), $chan));
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
        $stmt = $this->prepare('INSERT INTO `events` (`userid`, `to`, `type`, `message`, `date`) VALUES(?, ?, ?, ?, ?)');
        $stmt->execute(array($userid, $to, $type, $message, $date));
        $c = $stmt->rowCount();
        $stmt->closeCursor();
        return $c == 1;
    }
}