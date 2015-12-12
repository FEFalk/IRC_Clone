<?php
$ip = 'localhost';
$database = 'ircclone';
$username = 'root';
$password = '';

/*FOR TESTING ONLY*/
/*$_POST['username'] = 'alvinGrande';
$_POST['password'] = 'qwe';*/

if(isset($_POST['username']) && isset($_POST['password'])) {
	$user = strtolower($_POST['username']);
	$pass = $_POST['password'];
	$email = isset($_POST['email']) ? $_POST['email'] : null;

	$connstring = 'mysql:host='. $ip .';dbname='. $database .';';
	$db = new PDO($connstring, $username, $password,
	array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

	//$user = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
	$stmt = $db->prepare('SELECT name FROM users WHERE name = ?');
	$stmt->execute(array($user));

	if($stmt->rowCount() > 0) {
        echo "user exists";
    } 
    else {
        $stmt = $db->prepare('INSERT INTO users(name,password,email,permissions) VALUES(?,?,?,?);');
        $stmt->execute(array($user,$pass,$email,0));
        echo 'added!';
    }

} else {
	echo 'username/password cant be empty!';
}

?>	