<?php
require 'include/database.php';

// Set content type for JSON callback
header("Content-type:application/json");

if(isset($_POST['searchWord'])) {
    $cfg = require 'config.php';
    $db = new Database($cfg['db_ip'], $cfg['db_database'], $cfg['db_username'], $cfg['db_password']);
    $db->connect();

	$searchWord = $db->search($_POST['searchWord']);
    
	if(!$searchWord) {
        echo json_encode(['success' => false, 'message' => 'no_channel!']);
    } 
    else {
        echo json_encode(['success' => true, 'message' => $searchWord]);
    }

} 
else {
	echo json_encode(['success' => false, 'message' => 'no_input']);
}

?>	