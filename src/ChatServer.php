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

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->admin = null;
        $this->questions = json_decode(file_get_contents(dirname(__DIR__) . '/src/questions.json'), true)['questions'];
        $this->userData = [];
        $this->userStates = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        // Ajouter la connexion à la liste des clients
        $this->clients->attach($conn);

        // Identifier le type de client
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $params);
        
        if (isset($params['type']) && $params['type'] === 'admin') {
            $this->admin = $conn;
        } else {
            $this->sendQuestion($conn, 1); // Start with the first question
            // Envoyer un identifiant unique au client
            $clientId = uniqid();
            $conn->send(json_encode(['type' => 'id', 'id' => $clientId]));

            $conn->clientId = $clientId;
            $this->listClients[$clientId] = $conn;
        }
        
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (isset($data['type'])) {
            if ($data['type'] === 'admin') {
                // Si le message vient de l'admin, envoyez-le au client spécifié
                if ($from === $this->admin && isset($data['clientId'])) {
                    $cli = $this->listClients[$data['clientId']];
                    $cli->send(json_encode(['type' => 'message', 'message' => $data['message']]));
                    
                }
            } else {
                // Si le message vient d'un client, envoyez-le à l'admin
                if ($this->admin !== null) {
                    $this->admin->send(json_encode(['type' => 'message', 'message' => $data['message'], 'from' => $from->clientId]));
                }
            }
        } 
        
        $userId = $from->resourceId;

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
                var_dump("currentQuestion");
                if (empty($currentQuestion['choices'])) {
                    var_dump("empty currentQuestion");
                    $this->saveUserData($userId, $currentQuestionId, $data['response']);
                    $nextQuestionId = array_values($currentQuestion['next_question'])[0] ?? null;
                } else {
                    var_dump("not empty currentQuestion");
                    $nextQuestionId = $currentQuestion['next_question'][$data['response']] ?? null;
                }

                if ($nextQuestionId === null || in_array($nextQuestionId, $this->userStates[$userId]['completed'])) {
                    var_dump("question suppelementaire");
                    $this->sendOldQuestion($from, $currentQuestionId, $data['response']);
                    $this->userStates[$userId]['completed'][] = $currentQuestionId;
                    $from->send(json_encode(['message' => 'Merci pour vos réponses! Vous pouvez maintenant poser des questions supplémentaires.']));
                    if ($this->admin !== null) {
                        $this->admin->send(json_encode(['type' => 'message', 'from' => $from->clientId, 'message' => 'Merci pour vos réponses! Vous pouvez maintenant poser des questions supplémentaires.']));
                    }
                    $this->userStates[$userId]['completed'] = ['completed']; // Mark the questionnaire as completed
                } else {
                    $this->userStates[$userId]['completed'][] = $currentQuestionId;
                    $this->userStates[$userId]['current_question'] = $nextQuestionId;
                    var_dump("send question: current_question: " . $currentQuestionId . "reponse: " . $data['response']);
                    //send question sans possibilité de click à l'admin et au client
                    $this->sendOldQuestion($from, $currentQuestionId, $data['response']);
                    
                    $this->sendQuestion($from, $nextQuestionId);
                }
            } else {
                var_dump("Question non trouvée.");
                $from->send(json_encode(['message' => 'Question non trouvée.']));
            }
        } else if (isset($data['simple_message'])) {
            if (isset($this->userStates[$userId]) && $this->userStates[$userId]['completed'] === ['completed']) {
                $from->send(json_encode(['message' => 'Message reçu: ' . $data['simple_message']]));
                if ($this->admin !== null) {
                        $this->admin->send(json_encode(['type' => 'message', 'from' => $from->clientId, 'message' => 'Message reçu: ' . $data['simple_message']]));
                    }
            } else {
                $from->send(json_encode(['message' => 'Envoyez un message après avoir complété le questionnaire.']));
            }
        } else if (isset($data['file'])) {
            // Handle file upload
            var_dump("file");
            $fileData = base64_decode($data['file']['data']);
            $filePath = __DIR__ . '/../uploads/' . $data['file']['name'];

            // Make sure the upload directory exists
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0777, true);
            }

            file_put_contents($filePath, $fileData);
            $fileType = mime_content_type($filePath);
            
            if ($data['clientId']) {
                /// file avy any @ admin
                $client = $this->listClients[$data['clientId']];
                $rep = json_encode([
                                'type' => 'message',
                                'message' => [
                                    "file-name" => $data['file']['name'],
                                    "type" => $fileType, 
                                ],    
                                'from' => $client->clientId
                            ]);
                $client->send($rep);
                if ($this->admin !== null) {
                    $this->admin->send($rep);
                }
            } else {
                $rep = json_encode([
                                'type' => 'message',
                                'message' => [
                                    "file-name" => $data['file']['name'],
                                    "type" => $fileType, 
                                ],    
                                'from' => $from->clientId
                            ]);
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
        var_dump($question);
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

    protected function saveUserData($resourceId, $questionId, $response) {
        if (!isset($this->userData[$resourceId])) {
            $this->userData[$resourceId] = [];
        }
        $this->userData[$resourceId][$questionId] = $response;
    }
}

