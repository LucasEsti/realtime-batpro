<?php
namespace MyApp;
error_reporting(E_ALL & ~E_DEPRECATED);

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $admin;
    protected $admins;
    protected $questions;
    protected $userData;
    protected $userStates;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->admins = new \SplObjectStorage;
        $this->questions = json_decode(file_get_contents(dirname(__DIR__) . '/src/questions.json'), true)['questions'];
        $this->userData = [];
        $this->userStates = [];
        $this->ensureConnection();
        
        
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->ensureConnection();
        // Ajouter la connexion à la liste des clients
//        $this->clients->attach($conn);

        // Identifier le type de client
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $params);
        
        if (isset($params['type'])) {
            if ($params['type'] === 'admin') {
                if (!$this->admins->contains($conn)) {
                    $adminId = uniqid();
                    $this->admins->attach($conn, ['userId' => $adminId]);
                    $conn->send(json_encode(['type' => 'id', 'id' => $adminId]));
                }
                
                $conn->send(json_encode(['type' => 'listMessages', 'message' => $this->getListMessagesClients()]));
            } elseif ($params['type'] === 'client') {
                //check if client exist before and send the last question if exist
                if (isset($params['userId'])) {
                    $id = $params['userId'];
                    $this->clients->attach($conn, ['userId' => $id]);
                    
                    $result = $this->getMessageByClient($id);

                    //si des messages sont dans la base
                    if (count($result) == 0) {
                        $this->sendQuestion($conn, 1); // Start with the first question
                        // Envoyer un identifiant unique au client ---
                        $conn->send(json_encode(['type' => 'id', 'id' => $id ]));

                        $this->userStates[$id] = [
                            'current_question' => 1,
                            'completed' => []
                        ];

                    } else {
                        $conn->send(json_encode(['type' => 'listMessages', 'messageClient' => $result]));
                        $lastQuestionSave = $result[count($result) - 1]["lastQuestion"];
                        $lastReponseSave = $result[count($result) - 1]["message"];
                        if ($lastQuestionSave != null) {
                            $idByResponse = $this->getIDByResponse($lastQuestionSave, $lastReponseSave);

                            $nextQuestion = $this->getQuestionById($lastQuestionSave)['next_question']["1"];
                            $this->userStates[$id]['completed'][] = $nextQuestion;
                            $this->userStates[$id]['current_question'] = $nextQuestion;
                            if ($idByResponse != null) {
                                $this->sendQuestion($conn, $nextQuestion);
                            } else {
                                if ($nextQuestion != null) {
                                    $this->sendQuestion($conn, $nextQuestion);
                                }
                            }
                        } else {
                            $this->userStates[$id]['completed'] = ['completed'];
                            $conn->send(json_encode(['type' => 'listMessages', 'lastQuestionSave' => $lastQuestionSave]));
                        }
                    }

                } else {
                    $this->sendQuestion($conn, 1); // Start with the first question
                    // Envoyer un identifiant unique au client ---
                    $clientId = uniqid();
                    $this->clients->attach($conn, ['userId' => $clientId]);
                    $conn->send(json_encode(['type' => 'id', 'id' => $clientId]));
                    
                    
                }
            }
        }
        
        
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {

        $this->ensureConnection();
        $userId = null;
        
        if ($this->clients->contains($from)) {
            $clientData = $this->clients[$from];
            $userId = $clientData['userId'];
        }
        
        $data = json_decode($msg, true);
        
        
        $dataFile = null;
        $rep = null;
        if (isset($data['file'])) {
            $dataFile = $this->fileTreatment($data['file']);
            $rep = [
                'type' => 'message',
                'message' => $dataFile
            ];
        }
        
        if (isset($data['type'])) {
            if ($data['type'] === 'admin') {
                // Si le message vient de l'admin, envoyez-le au client spécifié
                if (isset($data['clientId'])) {
                    $client = $this->getConnectionInClientList($data['clientId']);
                    if (isset($data['isReadAdmin'])) {
                        $this->insertIsRead($data['isReadAdmin'], $data['clientId'] );
                    } elseif ($dataFile != null) {
                        /// file avy any @ admin
                        $rep["from"] = $data['clientId'];

                        $this->insertMessage($data['clientId'], 1, '', null, $data['file']['name'], $dataFile["type"]);
                        if ($client != null) {
                            $client->send(json_encode($rep));
                        }
                        
                        $rep["self"] = "self";
                        $this->sendMessageToAdmins(json_encode($rep));
                    } else {
                        //mila alefa @ admin rehetra ko ny valiny
                        $this->insertMessage($data['clientId'], 1, $data['message']);
                        
                        if ($client != null) {
                            $client->send(json_encode(['type' => 'message', 'message' => $data['message']]));
                        }
                    }
                    
                }
            } elseif ($data['type'] === 'client' && $userId != null) {
                if (isset($data['question_id']) && isset($data['response'])) {
                    if (!isset($this->userStates[$userId])) {
                        $this->userStates[$userId] = [
                            'current_question' => 1,
                            'completed' => []
                        ];
                    }
                    $currentQuestionId = $this->userStates[$userId]['current_question'];
                    $currentQuestion = $this->getQuestionById($currentQuestionId);
                    if ($currentQuestion) {
                        if (isset($data['file'])) {
                            $rep["from"] = $userId;
                            $this->insertMessage($userId, 0, '', null, $data['file']['name'], $dataFile["type"]);
                            $from->send(json_encode($rep));
                        }
                        if (empty($currentQuestion['choices'])) {

                            $this->saveUserData($userId, $userId, $currentQuestionId, $data['response']);
                            $nextQuestionId = array_values($currentQuestion['next_question'])[0] ?? null;

                        } else {
                            $nextQuestionId = $currentQuestion['next_question'][$data['response']] ?? null;
                        }

                        if ($nextQuestionId === null || in_array($nextQuestionId, $this->userStates[$userId]['completed'])) {
                            $this->sendOldQuestion($from, $userId, $currentQuestionId, $data['response']);
                            $this->userStates[$userId]['completed'][] = $currentQuestionId;
                            $repAdmin = 'Bienvenue au service commercial de BATPRO. Un agent vous contactera dans peu';

                            //envoie reponse by  admin
                            $this->insertMessage($userId, 1, $repAdmin);

                            $from->send(json_encode(['message' => $repAdmin]));
                                
                            $this->sendMessageToAdmins(json_encode(['type' => 'message', 'from' => $userId, 'message' => $repAdmin]));
                            
                            $this->userStates[$userId]['completed'] = ['completed']; // Mark the questionnaire as completed
                        } else {
                            $this->userStates[$userId]['completed'][] = $currentQuestionId;
                            $this->userStates[$userId]['current_question'] = $nextQuestionId;
                            //send question sans possibilité de click à l'admin et au client
                            $this->sendOldQuestion($from, $userId, $currentQuestionId, $data['response']);

                            $this->sendQuestion($from, $nextQuestionId);
                        }
                    } else {
                        $from->send(json_encode(['message' => 'Question non trouvée.']));
                    }
                } else if (isset($data['simple_message'])) {
                    if (isset($this->userStates[$userId]) && $this->userStates[$userId]['completed'] === ['completed']) {
                        $repClient = $data['simple_message'];
                        //envoie reponse user
                        $this->insertMessage($userId, 0, $repClient);
                        $from->send(json_encode(['message' => $repClient, "self" => "self"]));
                        
                        $this->sendMessageToAdmins(json_encode(['type' => 'message', 'from' => $userId, 'message' => $repClient]));
                        
                    } else {
                        $from->send(json_encode(['message' => 'Envoyez un message après avoir complété le questionnaire.']));
                    }
                } elseif (isset($data['file'])) {
                    $rep["from"] = $userId;
                    
                    $this->sendMessageToAdmins(json_encode($rep));
                    
                    $rep["self"] = "self";
                    $this->insertMessage($userId, 0, '', null, $data['file']['name'], $dataFile["type"]);
                    $from->send(json_encode($rep));
                } elseif (isset($data['isReadClient'])) {
                    $this->insertIsReadClient($userId);
                } else {
                    // Si le message vient d'un client, envoyez-le à l'admin
                    $this->sendMessageToAdmins(json_encode(['type' => 'message', 'message' => $data['message'], 'from' => $userId]));
                }
            } 
        } 
        
    }

    public function onClose(ConnectionInterface $conn) {
        // Déconnecter le client
        $this->clients->detach($conn);
        $this->admins->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected \n";
        
        
    }
   
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred 2: {$e->getMessage()} and {$e->getCode()} \n";
        if ($e->getCode() == "HY000") {
            // Handle MySQL server has gone away
            try {
                // Attempt to reconnect
                $this->pdo = $this->connectToDatabase();
                echo " Reconnected to the database. 1 \n";
            } catch (PDOException $reconnectException) {
                error_log("Failed to reconnect to the database 3: " . $reconnectException->getMessage()) . "\n";
                $conn->close();
            }
        } else {
            // Handle other types of exceptions
            echo 'connecton close. \n';
            $conn->close();
        }
    }
    
    protected function getConnectionInClientList($userId) {
        foreach ($this->clients as $conn) {
            if ($this->clients[$conn]["userId"] == $userId) {
                return $conn;
            }
        }
        return null;
    }
    
    protected function sendMessageToAdmins($message) {
        foreach ($this->admins as $admin) {
            $admin->send($message);
        }
    }
    
    protected function sendOldQuestion(ConnectionInterface $conn, $userId, $questionId, $reponse) {
        $question = $this->getQuestionById($questionId);
        $responseById = $this->getResponseById($questionId, $reponse);
        if ($question) {
            //envoie question
            $this->insertMessage($userId, 1, $question, $questionId);
            //envoie reponse user
            $this->insertMessage($userId, 0, $responseById, $questionId);
            $conn->send(json_encode(['reponseQuestion' => $responseById,  'questionOld' => $question, 'choicesOld' => $question['choices']]));
            
            $this->sendMessageToAdmins(json_encode(['type' => 'message', 'from' => $userId, 'reponseQuestion' => $responseById,  'questionOld' => $question, 'choicesOld' => $question['choices']]));
            
            
        } else {
            $conn->send(json_encode(['message' => 'Question non trouvée.']));
        }
    }
    
    protected function sendQuestion(ConnectionInterface $conn, $questionId) {
        $question = $this->getQuestionById($questionId);
        if ($question) {
            $questionToSend = ['question_id' => $questionId, 'question' => $question['question'], 'choices' => $question['choices']];
            if (isset($question['libelle'])) {
                if ($question['libelle'] == "lien") {
                    $questionToSend["lien"] = true;
                }
            }
            $conn->send(json_encode($questionToSend));
        } else {
            $conn->send(json_encode(['message' => 'Question non trouvée.']));
        }
    }
    
    protected function getResponseById($id, $idReponse) {
        if (isset($this->questions[$id])) {
            if (isset($this->questions[$id]['choices'][$idReponse])) {
                return $this->questions[$id]['choices'][$idReponse];
            } else {
                return $idReponse;
            }
        }
        return null;
    }
    
    protected function getQuestionById($id) {
        if (isset($this->questions[$id])) {
            return $this->questions[$id];
        }
        return null;
    }
    
    protected function getQuestionLibelle($id) {
        if (isset($this->questions[$id])) {
            return $this->questions[$id]['libelle'];
        }
        return null;
    }

    protected function saveUserData($idClient, $resourceId, $questionId, $response) {
        $libelle = $this->getQuestionLibelle($questionId);
        
        if (!isset($this->userData[$resourceId])) {
            $this->userData[$resourceId] = [];
        }
        
        if (in_array($libelle, ["nom", "mail"])) {
            $stmt = $this->pdo->prepare("UPDATE Message SET " . $libelle . " = ? WHERE idClient = ?");
            $stmt->execute([$response, $idClient]);

            $this->userData[$resourceId][$questionId] = $response;
        }
        
    }
    
    protected function connectToDatabase() {
        try {
            $bdd = json_decode(file_get_contents(dirname(__DIR__) . '/src/config.json'), true);
            $dsn = 'mysql:host=localhost;dbname=' . $bdd ["database"] . ';charset=utf8';
            $username = $bdd ["username"];
            $password = $bdd ["password"];
            return new \PDO($dsn, $username, $password);
        } catch (PDOException $e) {
            error_log("Initial DB connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    protected function getListMessagesClients() {
        $sql = "
            SELECT 
                m.idClient, 
                c.message,
                c.filePath,
                c.fileType,
                m.dateEnvoi,
                c.isAdmin,
                m.isReadAdmin,
                m.isReadClient,
                m.nom,
                m.mail
            FROM 
                Message m
            JOIN 
                Contenu c ON m.id = c.idMessage
            ORDER BY 
                m.dateEnvoi DESC, c.id ASC;
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        // Récupérer les résultats
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $listMessageClients = [];
        foreach ($result as $row) {
            $listMessageClients[$row['idClient']][] = $row;
        }
        return $listMessageClients;
    }
    
    protected function getMessageByClient($idClient) {
        $sql = "
            SELECT 
                m.idClient, 
                c.message, 
                m.dateEnvoi,
                c.isAdmin,
                m.isReadAdmin,
                m.isReadClient,
                m.nom,
                m.mail,
                c.lastQuestion,
                c.filePath,
                c.fileType
            FROM 
                Message m
            JOIN 
                Contenu c ON m.id = c.idMessage
            WHERE
                m.idClient = :idClient
            ORDER BY 
                m.dateEnvoi DESC, c.id ASC;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idClient', $idClient, \PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
    
    protected function insertMessage($idClient, $isAdmin, $message, $lastQuestion = null, $file = null, $fileType = null) {
        // Insérer le message dans la table Message
        $messageTemporaire = "";
        if (is_array($message)) {
            if (isset($message["question"])) {
                $messageTemporaire .= $message["question"] . "<br>";
                foreach ($message["choices"] as $choice) {
                    $messageTemporaire .=  '<button type="button" class="btn btn-primary mb-1 me-1 mt-1"> '. $choice .' </button>';
                }
                $message = $messageTemporaire;
            }
            
        }
        
        
        $isReadClient = 1;
        $isReadAdmin = 0;
        if ($isAdmin == 1) {
            $isReadClient = 0;
            $isReadAdmin = 1;
        }
        // Correctly prepare and execute the SELECT query
        $checkStmt = $this->pdo->prepare("SELECT * FROM Message WHERE idClient = ?");
        $checkStmt->execute([$idClient]);
        $exists = $checkStmt->fetch(\PDO::FETCH_ASSOC);
        $idMessage = 0;

//        // If the idClient does not exist, perform the INSERT
        if ($exists == null) {
            $insertStmt = $this->pdo->prepare("INSERT INTO Message (idClient, isReadClient, isReadAdmin, nom) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$idClient, $isReadClient, $isReadAdmin, $idClient]);
            // Récupérer l"ID du message inséré
            $idMessage = $this->pdo->lastInsertId();
        } else {
            $stmt = $this->pdo->prepare("UPDATE Message SET isReadClient = ?, isReadAdmin = ? WHERE idClient = ?");
            // Execute the statement with the bound values
            $stmt->execute([$isReadClient, $isReadAdmin, $idClient]);
            $idMessage = $exists["id"];
        }
        
        
        // Insérer des informations dans la table Contenu
        $stmt = $this->pdo->prepare("INSERT INTO Contenu (message, filePath, fileType, idMessage, isAdmin, lastQuestion) VALUES (?, ?, ?, ?,?, ?)");
        $stmt->execute([$message, $file, $fileType, $idMessage, $isAdmin, $lastQuestion]);
        
    }
    
     protected function insertIsRead($isReadAdmin, $idClient) {
            $stmt = $this->pdo->prepare("UPDATE Message SET isReadAdmin = ? WHERE idClient = ?");
            $stmt->execute([$isReadAdmin, $idClient]);
     }
     
     protected function insertIsReadClient($idClient) {
            $stmt = $this->pdo->prepare("UPDATE Message SET isReadClient = 1 WHERE idClient = ?");
            $stmt->execute([$idClient]);
     }
    
    protected function getIDByResponse($id, $reponse) {
        $keyFound = null;
        if (isset($this->questions[$id])) {
            if (count($this->questions[$id]['choices']) != 0) {
                foreach ($this->questions[$id]['choices'] as $key => $value) {
                    if ($value === $reponse) {
                      $keyFound = $key;
                      break;
                    }
                }
            }


        }
        return $keyFound;
    }
    
    private function ensureConnection() {
        echo 'ensureConnection \n';
        if ($this->pdo === null) {
            try {
                $this->pdo = $this->connectToDatabase();
                echo "Reconnected to the database 3. \n";
            } catch (PDOException $reconnectException) {
                error_log("Failed to reconnect to the database 1: " . $reconnectException->getMessage()) . "\n";
                throw $reconnectException;
            }
        }
        
        try {
            // Check if the connection is alive
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            echo "Reconnected to the database: {$e->getCode()} \n";
            if ($e->getCode() == 2006) {
                // Attempt to reconnect if the connection has gone away
                try {
                    $this->pdo = $this->connectToDatabase();
                    echo "Reconnected to the database 2. \n";
                } catch (PDOException $reconnectException) {
                    error_log("Failed to reconnect to the database 2: " . $reconnectException->getMessage()) . "\n";
                    throw $reconnectException;  // Re-throw the exception to handle it appropriately
                }
            } else {
                // Handle other exceptions
                throw $e;
            }
        }
    }
    
    protected function fileTreatment($data) {
        $fileData = base64_decode($data['data']);
        $filePath = __DIR__ . '/../uploads/' . $data['name'];

        // Make sure the upload directory exists
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, $fileData);
        $fileType = mime_content_type($filePath);
        return [
                "file-name" => $data['name'],
                "type" => $fileType, 
            ];
    }
}

