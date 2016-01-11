<?php
class Database
{
    private $db;
    private $ip, $database, $username, $password;
    
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
    
    public function registerUser($username, $password, $email)
    {
        $stmt = $this->db->prepare('INSERT INTO `users` (`name`, `password`, `email`, `permissions`) VALUES(?, ?, ?, 0);');
        $stmt->execute(array($username, $password, $email));
        $rc = $stmt->rowCount();
        $stmt->closeCursor();
        return $rc == 1;
    }
	   
	
	public function search($searchWord)
	{
		$stmt = $this->db->prepare('SELECT `name` FROM `channels` WHERE `name` = ?;');
        $stmt->execute(array($searchWord));
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;		
	}
}