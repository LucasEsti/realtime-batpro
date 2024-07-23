<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chatbot WebSocket</title>
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div id="chat"></div>
    <div id="choices"></div>
    <input type="text" id="response" placeholder="Entrez votre réponse" class="hidden" />
    <button id="sendButton" onclick="sendResponse()" class="hidden">Envoyer</button>
    <input type="text" id="simpleMessage" placeholder="Entrez un message simple" class="hidden" />
    <button id="sendSimpleMessageButton" onclick="sendSimpleMessage()" class="hidden">Envoyer Message Simple</button>

    <script>
        var conn = new WebSocket('ws://localhost:8080');
        var chat = document.getElementById('chat');
        var choicesDiv = document.getElementById('choices');
        var responseInput = document.getElementById('response');
        var sendButton = document.getElementById('sendButton');
        var simpleMessageInput = document.getElementById('simpleMessage');
        var sendSimpleMessageButton = document.getElementById('sendSimpleMessageButton');
        var currentQuestionId = null;

        conn.onmessage = function(e) {
            var data = JSON.parse(e.data);
            if (data.question) {
                currentQuestionId = data.question_id;  // Mise à jour de currentQuestionId
                chat.innerHTML += '<p>' + data.question + '</p>';
                choicesDiv.innerHTML = ''; // Clear previous choices
                if (Object.keys(data.choices).length > 0) {
                    for (var choice in data.choices) {
                        var button = document.createElement('button');
                        button.innerHTML = data.choices[choice];
                        button.onclick = (function(choice) {
                            return function() {
                                sendChoice(choice);
                            };
                        })(choice);
                        choicesDiv.appendChild(button);
                    }
                    responseInput.classList.add('hidden');
                    sendButton.classList.add('hidden');
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                } else {
                    responseInput.classList.remove('hidden');
                    sendButton.classList.remove('hidden');
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                }
            } else if (data.message) {
                chat.innerHTML += '<p>' + data.message + '</p>';
                // Show simple message input and button if the questionnaire is complete
                if (data.message.includes('Merci pour vos réponses!')) {
                    simpleMessageInput.classList.remove('hidden');
                    sendSimpleMessageButton.classList.remove('hidden');
                    choicesDiv.innerHTML = '';
                }
            }
        };

        function sendChoice(choice) {
            if (currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: choice }));
                responseInput.classList.add('hidden');
                sendButton.classList.add('hidden');
            }
        }

        function sendResponse() {
            var response = responseInput.value;
            if (currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: response }));
                responseInput.value = '';
                responseInput.classList.add('hidden');
                sendButton.classList.add('hidden');
            } else {
                alert("Aucune question actuelle. Veuillez attendre la prochaine question.");
            }
        }

        function sendSimpleMessage() {
            var message = simpleMessageInput.value;
            if (message.trim() !== '') {
                conn.send(JSON.stringify({ simple_message: message }));
                simpleMessageInput.value = '';
            }
        }
    </script>
</body>
</html>
