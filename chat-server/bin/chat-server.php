<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use IRCClone\Chat;
use IRCClone\Database;

    require dirname(__DIR__) . '/vendor/autoload.php';
    
    try {
        $db = new Database('localhost', 'ircclone', 'root', 'coolpassword');
        $db->connect();
    } catch(PDOException $e) {
        die('Unable to connect to database! ' . $e->getMessage());
    }

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Chat($db)
            )
        ),
        8080
    );

    $server->run();
?>