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
<html>
<head>
    <title>Client Chat</title>
    <style>
        #chatBox {
            height: 200px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            width: 100%;
        }
        #messageContainer {
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .hidden {
            display: none;
        }
        
    </style>
</head>
<body>
    <div id="chatBox" readonly>
        <div id="chat"></div>
        <div id="choices"></div>
        <input type="text" id="response" placeholder="Entrez votre réponse" class="hidden" />
        <button id="sendButton" onclick="sendResponse()" class="hidden">Envoyer</button>
        <input type="text" id="simpleMessage" placeholder="Entrez un message simple" class="hidden" />
        <input type="file" id="fileInput" class="hidden" />
        <button id="sendSimpleMessageButton" onclick="sendMessage()" class="hidden">Envoyer Message</button>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>

        <!--<button id="sendFileButton" onclick="sendFile()" class="hidden">Envoyer Fichier</button>-->
    </div>
    <script>
        var conn = new WebSocket('ws://localhost:8080?type=client');
        var clientId = null;
        
        // Définir l'URL des uploads depuis PHP
        const uploadsUrl = '<?php echo $uploadsUrl; ?>';
        function isObject(value) {
            return value !== null && typeof value === 'object' && value.constructor === Object;
        }
        
        
        var chat = document.getElementById('chat');
        var choicesDiv = document.getElementById('choices');
        var responseInput = document.getElementById('response');
        var sendButton = document.getElementById('sendButton');
        var simpleMessageInput = document.getElementById('simpleMessage');
        var sendSimpleMessageButton = document.getElementById('sendSimpleMessageButton');
        var fileInput = document.getElementById('fileInput');
//        var sendFileButton = document.getElementById('sendFileButton');
        var currentQuestionId = null;
        const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        conn.onopen = function() {
            console.log('WebSocket connection opened');
        };
        
        conn.onmessage = function(e) {
            var data = JSON.parse(e.data);
            
//            var chatBox = document.getElementById('chatBox');
            if (data.type === 'id') {
                clientId = data.id;
                $.cookie('clientId', clientId, { expires: 7, path: '/' });
//                $.cookie('clientId');

            } 
//            else if (data.type === 'message') {
//                var chatMessage = document.createElement('div');
//                chatMessage.textContent = data.message;
//                chatBox.appendChild(chatMessage);
//                chatBox.scrollTop = chatBox.scrollHeight;
//            }
            
            if (data.questionOld) {
                if (Object.keys(data.choicesOld).length > 0) {
                    for (var choice in data.choicesOld) {
                        var chatMessage = document.createElement('div');
                        chatMessage.textContent = data.choicesOld[choice];
                        chat.appendChild(chatMessage);
                    }
                } 
                var chatMessage = document.createElement('div');
                chatMessage.textContent = "reponse :" + data.reponseQuestion;
                chat.appendChild(chatMessage);
            }
            
            if (data.question) {
                console.log('question');
                currentQuestionId = data.question_id;  // Mise à jour de currentQuestionId
                chat.innerHTML += '<p>' + data.question + '</p>';
                choicesDiv.innerHTML = ''; // Clear previous choices
                if (Object.keys(data.choices).length > 0) {
                    console.log('choices');
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
//                    sendFileButton.classList.add('hidden');
                } else {
                    console.log('not choices');
                    responseInput.classList.remove('hidden');
                    sendButton.classList.remove('hidden');
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                    fileInput.classList.add('hidden');
//                    sendFileButton.classList.add('hidden');
                }
            } else if (data.message) {
                
                if (isObject(data.message)) {
                    if (imageTypes.includes(data.message["type"])) {
                        chat.innerHTML += '<p> <img class="img-fluid" src="' + uploadsUrl + data.message["file-name"] + '"/></p>' ;
                    } else {
                        chat.innerHTML += '<p> <a href="' + uploadsUrl + data.message["file-name"] + '" target="_blank">' + data.message["file-name"] + '<a></p>';
                    }
                } else {
                    
                    chat.innerHTML += '<p>' + data.message + '</p>';
                    // Show input for simple message and file upload if the questionnaire is complete
                    if (data.message.includes('Merci pour vos réponses!')) {
                        simpleMessageInput.classList.remove('hidden');
                        sendSimpleMessageButton.classList.remove('hidden');
                        fileInput.classList.remove('hidden');
//                        sendFileButton.classList.remove('hidden');
                        choicesDiv.innerHTML = ''; // Clear choices div when done
                        responseInput.classList.add('hidden');
                        sendButton.classList.add('hidden');
                    }
                }
            }
            
            
        };

        function sendMessage() {
            var message2 = simpleMessageInput.value;
            var file = fileInput.files[0];
            if (message2) {
                conn.send(JSON.stringify({ simple_message: message2 }));
                simpleMessageInput.value = '';
            }
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
        
        function sendSimpleMessage() {
            
        }
        
        
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

        

        function sendFile() {
            
        }
        
    </script>
</body>
</html>
