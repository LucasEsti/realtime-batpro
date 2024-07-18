<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface as WebSocketMessageComponentInterface;

// Initialisation de la session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class MessageServer implements WebSocketMessageComponentInterface {
    protected $clients;
    protected $lastClientId = 0; //var pour suivre le dernier ID
    protected $adminConnections; // Stocke les connexions des administrateurs
    protected $pdo;
    
    public function __construct() {
        // Connexion à la base de données
        $dsn = 'mysql:host=localhost;dbname=bd_chatlive;charset=utf8';
        $username = 'root';
        $password = '';
        $this->pdo = new \PDO($dsn, $username, $password);

        $this->clients = new \SplObjectStorage();
        $this->adminConnections = new \SplObjectStorage();   
        // Initialisation de la session si ce n'est pas déjà fait
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        // Récupérer l'identifiant unique du client à partir des paramètres de l'URL
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $params);
        $clientId = isset($params['clientId']) ? $params['clientId'] : null;

        // Si un identifiant est présent, utilisez-le pour identifier le client
        if ($clientId !== null) {
            // Utiliser l'ID du client comme adresse e-mail
            $email = $clientId; 
            // Assigner l'ID du client à resourceId
            $conn->resourceId = $clientId;
        } else {
            // Vérifier s'il existe une ID stockée dans la session
            if (isset($_SESSION['admin_id'])) {
                // Utiliser l'ID stockée dans la session
                $clientId = $_SESSION['admin_id'];
                // Utiliser l'ID de l'admin comme adresse e-mail
                $email = $_SESSION['admin_id']; 
                // Assigner l'ID de l'admin à resourceId
                $conn->resourceId = $clientId;
            } else {
                // Sinon, générer un nouvel identifiant pour l'administrateur
                $this->lastClientId++;
                $clientId = $this->lastClientId;
                $email = $this->lastClientId;
                // Assigner le nouvel ID à resourceId
                $conn->resourceId = $this->lastClientId;
                // Enregistrer l'ID dans la session
                $_SESSION['admin_id'] = $this->lastClientId;
            }
        }    
        // Insérer les données de l'utilisateur dans la base de données
        $userId = $clientId;
        $userType = isset($_SESSION['admin_id']) ? 'admin' : 'client';
        // Insérer les données dans la base de données
        $stmt = $this->pdo->prepare("INSERT INTO user_connections (user_id, user_type, email) VALUES (:user_id, :user_type, :email)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':user_type', $userType);
        $stmt->bindParam(':email', $email);
        $stmt->execute();           
        $stmt = $this->pdo->prepare("SELECT * FROM message WHERE (sender_id = :user_id OR receiver_id = :user_id) AND sender_id != receiver_id AND content != ''");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        
        // Tableau pour stocker les messages par expéditeur et destinataire
        $conversation = [];
        foreach ($messages as $message) {
            $senderId = $message['sender_id'];
            $receiverId = $message['receiver_id'];
            $content = $message['content'];
        
            // Vérifier si l'expéditeur est l'administrateur ou le client
            if ($senderId == 1) {
                // Si l'expéditeur est l'administrateur, envoyer le message au client
                $tab = [
                    'adminMessage' => [
                        'senderId' => $senderId,
                        'receiverId' => $receiverId,
                        'content' => $content
                    ],
                    'clientId' => $receiverId,
                    'adminId' => $senderId,
                    'content' => $content
                ];
                $conn->send(json_encode($tab));
            } else {
                // Si l'expéditeur est un client, envoyer le message à l'administrateur
                $tab = [
                    'clientMessage' => [
                        'senderId' => $senderId,
                        'receiverId' => $receiverId,
                        'content' => $content
                    ],
                    'clientId' => $senderId,
                    'adminId' => $receiverId,
                    'content' => $content
                ];
                $conn->send(json_encode($tab));
            }
            // Ajouter le message dans le tableau de conversation
            if (!isset($conversation[$senderId][$receiverId])) {
                $conversation[$senderId][$receiverId] = [];
            }
            $conversation[$senderId][$receiverId][] = $content;
        }    
        
        // Envoi du message de connexion au serveur
        $this->clients->attach($conn);
        echo "Client connecté ({$userId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        // Initialisation de la session si ce n'est pas déjà fait
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $message = json_decode($msg, true);

        // Gérer les messages envoyés par le chatbot
        if (isset($message['adminMessage'])) {
            // Gérer les messages destinés au client
            $clientConnection = $this->getClientConnection($message['clientId']);
            if ($clientConnection !== null) {
                // Mettre à jour le sender_id et le destinataire
                $senderId = 1; // ID de l'administrateur (chatbot)
                $receiverId = $message['clientId']; // ID du client
                $content = $message['content'];

                if (!empty($content)) {
                   // Enregistrer le message dans la base de données
                    $stmt = $this->pdo->prepare("INSERT INTO message (sender_id, receiver_id, content) VALUES (:sender_id, :receiver_id, :content)");
                    $stmt->bindParam(':sender_id', $senderId);
                    $stmt->bindParam(':receiver_id', $receiverId);
                    $stmt->bindParam(':content', $content);
                    $stmt->execute();
        
                    // Envoyer le message au client
                    $clientConnection->send(json_encode([
                        'adminMessage' => 'Admin',
                        'content' => $content
                    ]));
                }
                
                // Envoyer le message à l'administrateur également
                $adminConnection = $this->getAdminConnection(); // Récupérer la connexion de l'administrateur
                if ($adminConnection !== null) {
                    $adminConnection->send(json_encode([
                    'adminMessage' => true, // Utiliser le nom du client
                    'clientId' => $from->resourceId,
                    'content' => $message['content']
                    ]));
                }
            } 
        }
        if (isset($message['type']) && $message['type'] === 'image') {
            // Envoyer l'image à l'administrateur sauf si elle provient de l'administrateur lui-même
            if ($from->resourceId !== 1) {
                $adminConnection = $this->getAdminConnection();
                if ($adminConnection !== null) {
                    $message['clientId'] = $from->resourceId; // Ajouter l'ID du client au message
                    $adminConnection->send(json_encode($message));
                }
            } else {
                // Si l'image provient de l'administrateur, envoyer l'image au client et à l'administrateur
                $clientConnection = $this->getClientConnection($message['clientId']);
                if ($clientConnection !== null) {
                    $clientConnection->send(json_encode($message));
                }        
            }
            // Envoi également l'image à l'expéditeur lui-même
            $from->send(json_encode($message));           
        }
        elseif (isset($message['toAdmin']) && $message['toAdmin'] === true) {
            // Gérer les messages destinés à l'administrateur
            $adminConnection = $this->getAdminConnection();
            if ($adminConnection !== null) {
                $messageWithClientId = [
                    'adminMessage' => $message['clientName'], // Utiliser le nom du client
                    'clientId' => $from->resourceId,
                    'content' => $message['content']
                ];
                $adminConnection->send(json_encode($messageWithClientId));
            }           
        }
        elseif (isset($message['toClient']) && $message['toClient'] === true) {
            // Gérer les messages destinés aux clients
            $clientConnection = $this->getClientConnection($message['clientId']);
            if ($clientConnection !== null) {
                $messageWithAdmin = [
                    'adminMessage' => 'Admin', // Message de l'admin
                    'content' => $message['content']
                ];
                $clientConnection->send(json_encode($messageWithAdmin));
            }    
        }
        //section qui gère l'envoi de fichiers
        if (isset($message['fileToClient']) && $message['fileToClient'] === true) {
            $clientConnection = $this->getClientConnection($message['clientId']);
            if ($clientConnection !== null) {
                $fileName = basename($message['fileName']);
                $filePath = 'http://localhost/chat/wp-content/uploads/' . $fileName; // URL accessible depuis le client
                
                //le répertoire "uploads" existe et est accessible
                $fullFilePath = 'C:/wamp64/www/chat/wp-content/uploads/' . $fileName;
                $fileContent = base64_decode(preg_replace('#^data:application/\w+;base64,#i', '', $message['fileContent']));
                file_put_contents($fullFilePath, $fileContent);
                
                // Enregistrement des détails du fichier dans la base de données
                $senderId = 1; // ID de l'administrateur (chatbot)
                $receiverId = $message['clientId']; // ID du client
                $content = '<a href="' . $filePath . '" download="' . $fileName . '">' . $fileName . '</a>'; // Contenu pour le fichier avec le lien complet
                
                $stmt = $this->pdo->prepare("INSERT INTO message (sender_id, receiver_id, content) VALUES (:sender_id, :receiver_id, :content)");
                $stmt->bindParam(':sender_id', $senderId);
                $stmt->bindParam(':receiver_id', $receiverId);
                $stmt->bindParam(':content', $content);
                $stmt->execute();
                
                // Envoyer le chemin du fichier au client
                $clientConnection->send(json_encode([
                    'type' => 'file',
                    'adminMessage' => 'Admin',
                    'filePath' => $filePath,
                    'fileName' => $fileName
                ]));
            }
        }
        
        // Enregistrement du message de l'administrateur dans la base de données
        $senderId = ($from->resourceId == 1) ? $_SESSION['admin_id'] : $from->resourceId;
        $receiverId = isset($message['clientId']) ? $message['clientId'] : 1; // Par défaut, l'admin est le destinataire
        $content = isset($message['content']) ? $message['content'] : '';

        $stmt = $this->pdo->prepare("INSERT INTO message (sender_id, receiver_id, content) VALUES (:sender_id, :receiver_id, :content)");
        $stmt->bindParam(':sender_id', $senderId);
        $stmt->bindParam(':receiver_id', $receiverId);
        $stmt->bindParam(':content', $content);
        $stmt->execute();
    }  

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Client ({$conn->resourceId}) déconnecté\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occured: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function getAdminConnection() {
        // Rechercher le client avec l'ID 1
        foreach ($this->clients as $client) {
            if ($client->resourceId === 1) {
                return $client;
            }
        }
        // Si aucun client avec l'ID 1 n'est trouvé, retourner null
        return null;
    }

    protected function getClientConnection($clientId) {
        foreach ($this->clients as $client) {
            if ($client->resourceId == $clientId) {
                return $client;
            }
        }
        return null;
    }
}