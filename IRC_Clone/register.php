<?php
require 'include/database.php';

// Set content type for JSON callback
header("Content-type:application/json");

if(isset($_POST['username']) && isset($_POST['password'])) {
    $cfg = require 'config.php';
    $db = new Database($cfg['db_ip'], $cfg['db_database'], $cfg['db_username'], $cfg['db_password']);
    $db->connect();
    
    // Allow null emails?
	$email = isset($_POST['email']) ? $_POST['email'] : null;
    
    // Check if username is valid
    if (!preg_match('/\A[a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]{2,15}\z/i', $_POST['username'])) {
        echo json_encode(['success' => false, 'message' => 'invalid_format']);
        
        return;
    }

    // Check if username already exists
	$userinfo = $db->getUserInfo($_POST['username']);
    
	if($userinfo) {
        echo json_encode(['success' => false, 'message' => 'user_exists']);
    } 
    else {
        if ($db->registerUser($_POST['username'], password_hash($_POST['password'], PASSWORD_BCRYPT), $email))
            echo json_encode(['success' => true]);
        else
            echo json_encode(['success' => false, 'message' => 'unknown_error']);
    }

} else {
	echo json_encode(['success' => false, 'message' => 'no_input']);
}

?>	