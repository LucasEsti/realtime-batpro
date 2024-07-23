<?php
namespace MyApp;
/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $admin;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->admin = null;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Ajouter la connexion à la liste des clients
        $this->clients->attach($conn);

        // Identifier le type de client
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $params);
        
        if (isset($params['type']) && $params['type'] === 'admin') {
            $this->admin = $conn;
        }
        
        // Envoyer un identifiant unique au client
        $clientId = uniqid();
        $conn->send(json_encode(['type' => 'id', 'id' => $clientId]));
        
        $conn->clientId = $clientId;
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        var_dump("ato");
        var_dump($data);
        if (isset($data['type'])) {
            var_dump("type");
            if ($data['type'] === 'admin') {
                // Si le message vient de l'admin, envoyez-le au client spécifié
                if ($from === $this->admin && isset($data['clientId'])) {
                    foreach ($this->clients as $client) {
                        if ($client->clientId === $data['clientId']) {
                            $client->send(json_encode(['type' => 'message', 'message' => $data['message']]));
                            break;
                        }
                    }
                }
                }else {
                // Si le message vient d'un client, envoyez-le à l'admin
                var_dump("client");
                if ($this->admin !== null) {
                    $this->admin->send(json_encode(['type' => 'message', 'message' => $data['message'], 'from' => $from->clientId]));
                }
            }
        } 
    }

    public function onClose(ConnectionInterface $conn) {
        // Déconnecter le client
        $this->clients->detach($conn);
        
        // Vérifier si l'administrateur s'est déconnecté
        if ($conn === $this->admin) {
            $this->admin = null;
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

