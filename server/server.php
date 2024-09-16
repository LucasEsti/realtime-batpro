<?php
    require dirname(__DIR__) . '/vendor/autoload.php';

    //Utilisation de Ratchet   
    use Ratchet\Server\IoServer;
    use Ratchet\Http\HttpServer;
    use Ratchet\WebSocket\WsServer;
    use MyApp\ChatServer;

    $port = 8080;
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new ChatServer()
            )
        ),
        $port
    );

    echo "Serveur en marche sur le port " . $port . "...\n";
    $server->run();
?>