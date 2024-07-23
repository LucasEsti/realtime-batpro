<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatBot implements MessageComponentInterface {
    protected $clients;
    protected $questions;
    protected $userData;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->questions = json_decode(file_get_contents(dirname(__DIR__) . '/src/questions.json'), true)['questions'];
        $this->userData = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        $this->sendQuestion($conn, 1);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (isset($data['question_id']) && isset($data['response'])) {
            $currentQuestion = $this->getQuestionById($data['question_id']);
            if ($currentQuestion) {
                if (empty($currentQuestion['choices'])) {
                    $this->saveUserData($from->resourceId, $data['question_id'], $data['response']);
                    $nextQuestionId = array_values($currentQuestion['next_question'])[0] ?? null;
                } else {
                    $nextQuestionId = $currentQuestion['next_question'][$data['response']] ?? null;
                }

                if ($nextQuestionId) {
                    $this->sendQuestion($from, $nextQuestionId);
                } else {
                    $from->send(json_encode(['message' => 'Merci pour vos réponses!']));
                    var_dump($this->userData);
                }
            } else {
                $from->send(json_encode(['message' => 'Question non trouvée.']));
            }
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
