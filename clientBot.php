<?php
// Déterminez le schéma (http/https) et le nom d'hôte
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Déterminez le chemin de base de votre application
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Définir l'URL de base
$uploadsUrl = $scheme . '://' . $host . $scriptName . '/uploads/';

?>

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
    <input type="file" id="fileInput" class="hidden" />
    <button id="sendFileButton" onclick="sendFile()" class="hidden">Envoyer Fichier</button>

    <script>
        // Définir l'URL des uploads depuis PHP
        const uploadsUrl = '<?php echo $uploadsUrl; ?>';
        function isObject(value) {
            return value !== null && typeof value === 'object' && value.constructor === Object;
        }
        
        var conn = new WebSocket('ws://localhost:8080');
        var chat = document.getElementById('chat');
        var choicesDiv = document.getElementById('choices');
        var responseInput = document.getElementById('response');
        var sendButton = document.getElementById('sendButton');
        var simpleMessageInput = document.getElementById('simpleMessage');
        var sendSimpleMessageButton = document.getElementById('sendSimpleMessageButton');
        var fileInput = document.getElementById('fileInput');
        var sendFileButton = document.getElementById('sendFileButton');
        var currentQuestionId = null;
        const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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
                    fileInput.classList.add('hidden');
                    sendFileButton.classList.add('hidden');
                } else {
                    responseInput.classList.remove('hidden');
                    sendButton.classList.remove('hidden');
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                    fileInput.classList.add('hidden');
                    sendFileButton.classList.add('hidden');
                }
            } else if (data.message) {
                
                if (isObject(data.message)) {
                    if (imageTypes.includes(data.message["type"])) {
                        chat.innerHTML += '<p> <img src="' + uploadsUrl + data.message["file-name"] + '"/></p>' ;
                    } else {
                        chat.innerHTML += '<p> <a href="' + uploadsUrl + data.message["file-name"] + '" target="_blank">file<a></p>';
                    }
                } else {
                    
                    chat.innerHTML += '<p>' + data.message + '</p>';
                    // Show input for simple message and file upload if the questionnaire is complete
                    if (data.message.includes('Merci pour vos réponses!')) {
                        simpleMessageInput.classList.remove('hidden');
                        sendSimpleMessageButton.classList.remove('hidden');
                        fileInput.classList.remove('hidden');
                        sendFileButton.classList.remove('hidden');
                        choicesDiv.innerHTML = ''; // Clear choices div when done
                        responseInput.classList.add('hidden');
                        sendButton.classList.add('hidden');
                    }
                }
            }
        };

        function sendChoice(choice) {
            if (currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: choice }));
            }
        }

        function sendResponse() {
            var response = responseInput.value;
            if (response && currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: response }));
                responseInput.value = '';
            }
        }

        function sendSimpleMessage() {
            var message = simpleMessageInput.value;
            if (message) {
                conn.send(JSON.stringify({ simple_message: message }));
                simpleMessageInput.value = '';
            }
        }

        function sendFile() {
            var file = fileInput.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var base64File = e.target.result.split(',')[1];
                    conn.send(JSON.stringify({ file: { name: file.name, data: base64File } }));
                };
                reader.readAsDataURL(file);
                fileInput.value = ''; // Clear the file input
            }
        }
    </script>
</body>
</html>
