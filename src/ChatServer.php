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
    protected $listClients;
    protected $questions;
    protected $userData;
    protected $userStates;
    
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->admin = null;
        $this->questions = json_decode(file_get_contents(dirname(__DIR__) . '/src/questions.json'), true)['questions'];
        $this->userData = [];
        $this->userStates = [];
        
        $dsn = 'mysql:host=localhost;dbname=chatbot;charset=utf8';
        $username = 'root';
        $password = '';
        $this->pdo = new \PDO($dsn, $username, $password);
        $test = $this->getListMessagesClients();
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
                    $messageTemporaire .= $choice . "<br>";
                }
                $message = $messageTemporaire;
            }
            
        }
        
        
        $isReadClient = true;
        $isReadAdmin = false;
        if ($isAdmin == true) {
            $isReadClient = false;
            $isReadAdmin = true;
        }
        // Correctly prepare and execute the SELECT query
        $checkStmt = $this->pdo->prepare("SELECT * FROM Message WHERE idClient = ?");
        $checkStmt->execute([$idClient]);
        $exists = $checkStmt->fetch(\PDO::FETCH_ASSOC);
        $idMessage = 0;

//        // If the idClient does not exist, perform the INSERT
        if ($exists == null) {
            $insertStmt = $this->pdo->prepare("INSERT INTO Message (idClient, isReadClient, isReadAdmin) VALUES (?, ?, ?)");
            $insertStmt->execute([$idClient, $isReadClient, $isReadAdmin]);
            // Récupérer l'ID du message inséré
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
    
    protected function getIDByResponse($id, $reponse) {
        $keyFound = null;
        foreach ($this->questions as $question) {
            if ($question['id'] == $id) {
                if (count($question['choices']) != 0) {
                    foreach ($question['choices'] as $key => $value) {
                        if ($value === $reponse) {
                          $keyFound = $key;
                          break;
                        }
                    }
                }
                
                
            }
        }
        return $keyFound;
    }
    

    public function onOpen(ConnectionInterface $conn) {
        // Ajouter la connexion à la liste des clients
        $this->clients->attach($conn);

        // Identifier le type de client
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $params);
        
        if (isset($params['type']) && $params['type'] === 'admin') {
            $this->admin = $conn;
            $this->admin->send(json_encode(['type' => 'listMessages', 'message' => $this->getListMessagesClients()]));
        } else {
            //check if client exist before and send the last question if exist
            if (isset($params['userId'])) {
                $id = $params['userId'];
                $conn->clientId = $id;
                $this->listClients[$id] = $conn;
                
                $result = $this->getMessageByClient($id);
                
                //si des messages sont dans la base
                if (count($result) == 0) {
                    $this->sendQuestion($conn, 1); // Start with the first question
                    // Envoyer un identifiant unique au client ---
                    $conn->send(json_encode(['type' => 'id', 'id' => $id ]));

                    $conn->clientId = $id ;
                    $this->listClients[$id] = $conn;
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
                            $this->sendQuestion($conn, $idByResponse);
                        } else {
                            $this->sendQuestion($conn, $nextQuestion);
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
                $conn->send(json_encode(['type' => 'id', 'id' => $clientId]));

                $conn->clientId = $clientId;
                $this->listClients[$clientId] = $conn;
            }
            
        }
        
    }
    

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (isset($data['type'])) {
            if ($data['type'] === 'admin') {
                // Si le message vient de l'admin, envoyez-le au client spécifié
                if ($from === $this->admin && isset($data['clientId'])) {
                    $cli = $this->listClients[$data['clientId']];
                    $this->insertMessage($data['clientId'], true, $data['message']);
                    $cli->send(json_encode(['type' => 'message', 'message' => $data['message']]));
                    
                }
            } else {
                // Si le message vient d'un client, envoyez-le à l'admin
                
                if ($this->admin !== null) {
                    $this->admin->send(json_encode(['type' => 'message', 'message' => $data['message'], 'from' => $from->clientId]));
                }
            }
        } 
        
        $userId = $from->clientId;

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
                if (empty($currentQuestion['choices'])) {
                    $this->saveUserData($from->clientId, $userId, $currentQuestionId, $data['response']);
                    $nextQuestionId = array_values($currentQuestion['next_question'])[0] ?? null;
                } else {
                    $nextQuestionId = $currentQuestion['next_question'][$data['response']] ?? null;
                }

                if ($nextQuestionId === null || in_array($nextQuestionId, $this->userStates[$userId]['completed'])) {
                    $this->sendOldQuestion($from, $currentQuestionId, $data['response']);
                    $this->userStates[$userId]['completed'][] = $currentQuestionId;
                    $repAdmin = 'Merci pour vos réponses! Vous pouvez maintenant poser des questions supplémentaires.';
                    
                    //envoie reponse by  admin
                    $this->insertMessage($from->clientId, true, $repAdmin);
                    
                    $from->send(json_encode(['message' => $repAdmin]));
                    if ($this->admin !== null) {
                        $this->admin->send(json_encode(['type' => 'message', 'from' => $from->clientId, 'message' => $repAdmin]));
                    }
                    $this->userStates[$userId]['completed'] = ['completed']; // Mark the questionnaire as completed
                } else {
                    $this->userStates[$userId]['completed'][] = $currentQuestionId;
                    $this->userStates[$userId]['current_question'] = $nextQuestionId;
                    //send question sans possibilité de click à l'admin et au client
                    $this->sendOldQuestion($from, $currentQuestionId, $data['response']);
                    
                    $this->sendQuestion($from, $nextQuestionId);
                }
            } else {
                $from->send(json_encode(['message' => 'Question non trouvée.']));
            }
        } else if (isset($data['simple_message'])) {
            if (isset($this->userStates[$userId]) && $this->userStates[$userId]['completed'] === ['completed']) {
                
                $repClient = 'Message reçu: ' . $data['simple_message'];
                //envoie reponse user
                $this->insertMessage($from->clientId, false, $repClient);
                
                $from->send(json_encode(['message' => $repClient]));
                if ($this->admin !== null) {
                        $this->admin->send(json_encode(['type' => 'message', 'from' => $from->clientId, 'message' => $repClient]));
                    }
            } else {
                $from->send(json_encode(['message' => 'Envoyez un message après avoir complété le questionnaire.']));
            }
        } else if (isset($data['file'])) {
            // Handle file upload
            $fileData = base64_decode($data['file']['data']);
            $filePath = __DIR__ . '/../uploads/' . $data['file']['name'];

            // Make sure the upload directory exists
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0777, true);
            }

            file_put_contents($filePath, $fileData);
            $fileType = mime_content_type($filePath);
            $array = [
                    "file-name" => $data['file']['name'],
                    "type" => $fileType, 
                ];
            
            if (isset($data['clientId']) && $data['clientId']) {
                /// file avy any @ admin
                $client = $this->listClients[$data['clientId']];
                
                $rep = json_encode([
                                'type' => 'message',
                                'message' => $array,    
                                'from' => $client->clientId
                            ]);
                
                $this->insertMessage($client->clientId, true, '', null, $data['file']['name'], $fileType);
                
                $client->send($rep);
                if ($this->admin !== null) {
                    $this->admin->send($rep);
                }
            } else {
                
                $rep = json_encode([
                                'type' => 'message',
                                'message' => $array,    
                                'from' => $from->clientId
                            ]);
                $this->insertMessage($from->clientId, true, '', null,$data['file']['name'], $fileType);
                $from->send($rep);
                if ($this->admin !== null) {
                    $this->admin->send($rep);
                }
            }
            
        } else {
            $from->send(json_encode(['message' => 'Données invalides.']));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Déconnecter le client
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        unset($this->listClients[$conn->clientId]);
        // Vérifier si l'administrateur s'est déconnecté
        if ($conn === $this->admin) {
            $this->admin = null;
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function sendOldQuestion(ConnectionInterface $conn, $questionId, $reponse) {
        $question = $this->getQuestionById($questionId);
        $responseById = $this->getResponseById($questionId, $reponse);
        if ($question) {
            //envoie question
            $this->insertMessage($conn->clientId, true, $question, $questionId);
            //envoie reponse user
            $this->insertMessage($conn->clientId, false, $responseById, $questionId);
            $conn->send(json_encode(['reponseQuestion' => $responseById,  'questionOld' => $question, 'choicesOld' => $question['choices']]));
            if ($this->admin !== null) {
                $this->admin->send(json_encode(['type' => 'message', 'from' => $conn->clientId, 'reponseQuestion' => $responseById,  'questionOld' => $question, 'choicesOld' => $question['choices']]));
            }
            
        } else {
            $conn->send(json_encode(['message' => 'Question non trouvée.']));
        }
    }
    
    protected function sendQuestion(ConnectionInterface $conn, $questionId) {
        $question = $this->getQuestionById($questionId);
        if ($question) {
            $conn->send(json_encode(['question_id' => $questionId, 'question' => $question['question'], 'choices' => $question['choices']]));
        } else {
            $conn->send(json_encode(['message' => 'Question non trouvée.']));
        }
    }
    
    protected function getResponseById($id, $idReponse) {
        foreach ($this->questions as $question) {
            if ($question['id'] == $id) {
                if (isset($question['choices'][$idReponse])) {
                    return $question['choices'][$idReponse];
                } else {
                    return $idReponse;
                }
                
            }
        }
        return null;
    }
    
    protected function getQuestionById($id) {
        foreach ($this->questions as $question) {
            if ($question['id'] == $id) {
                return $question;
            }
        }
        return null;
    }
    
    protected function getQuestionLibelle($id) {
        foreach ($this->questions as $question) {
            if ($question['id'] == $id) {
                return $question['libelle'];
            }
        }
        return null;
    }

    protected function saveUserData($idClient, $resourceId, $questionId, $response) {
        $libelle = $this->getQuestionLibelle($questionId);
        
        if (!isset($this->userData[$resourceId])) {
            $this->userData[$resourceId] = [];
        }
        
        $stmt = $this->pdo->prepare("UPDATE Message SET " . $libelle . " = ? WHERE idClient = ?");
        $stmt->execute([$response, $idClient]);
        
        $this->userData[$resourceId][$questionId] = $response;
    }
}

