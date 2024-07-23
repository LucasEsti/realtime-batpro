<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatBot implements MessageComponentInterface {
    protected $clients;
    protected $questions;
    protected $userData;
    protected $userStates;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->questions = json_decode(file_get_contents(dirname(__DIR__) . '/src/questions.json'), true)['questions'];
        $this->userData = [];
        $this->userStates = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        $this->sendQuestion($conn, 1); // Start with the first question
        
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
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
                if (empty($currentQuestion['choices'])) {
                    $this->saveUserData($userId, $currentQuestionId, $data['response']);
                    $nextQuestionId = array_values($currentQuestion['next_question'])[0] ?? null;
                } else {
                    $nextQuestionId = $currentQuestion['next_question'][$data['response']] ?? null;
                }

                if ($nextQuestionId === null || in_array($nextQuestionId, $this->userStates[$userId]['completed'])) {
                    $this->userStates[$userId]['completed'][] = $currentQuestionId;
                    $from->send(json_encode(['message' => 'Merci pour vos réponses! Vous pouvez maintenant poser des questions supplémentaires.']));
                    $this->userStates[$userId]['completed'] = ['completed']; // Mark the questionnaire as completed
                } else {
                    $this->userStates[$userId]['completed'][] = $currentQuestionId;
                    $this->userStates[$userId]['current_question'] = $nextQuestionId;
                    $this->sendQuestion($from, $nextQuestionId);
                }
            } else {
                $from->send(json_encode(['message' => 'Question non trouvée.']));
            }
        } else if (isset($data['simple_message'])) {
            if (isset($this->userStates[$userId]) && $this->userStates[$userId]['completed'] === ['completed']) {
                $from->send(json_encode(['message' => 'Message reçu: ' . $data['simple_message']]));
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
            $rep = json_encode(['message' => [
                            "file-name" => $data['file']['name'],
                            "type" => $fileType
                        ],    
                    ]);
                    var_dump($rep);
            $from->send($rep);
        } else {
            $from->send(json_encode(['message' => 'Données invalides.']));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function sendQuestion(ConnectionInterface $conn, $questionId) {
        $question = $this->getQuestionById($questionId);
        if ($question) {
            $conn->send(json_encode(['question_id' => $questionId, 'question' => $question['question'], 'choices' => $question['choices']]));
        } else {
            $conn->send(json_encode(['message' => 'Question non trouvée.']));
        }
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