<?php
// Déterminez le schéma (http/https) et le nom d'hôte
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Déterminez le chemin de base de votre application
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Définir l'URL de base
$uploadsUrl = $scheme . '://' . $host . $scriptName . '/uploads/';
$source = $scheme . '://' . $host . $scriptName . '/';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Chat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="<?php echo $source; ?>style/chatbox.css">
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
        
        input[type='file'] {
            color: transparent;
          }
        
    </style>
</head>
<body>
        <div id="chatBox" readonly>
        <div ></div>
        <div id="choices"></div>
        
        
        <div class="floating-chat">
            <i class="fa fa-comments" aria-hidden="true"></i>
            <div class="chat container-fluid">
                <div class="header">
                    <span class="title">
                        ChatLive
                    </span>
                    <button>
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>

                </div>
                <ul id="chat" class="messages">
                </ul>
                <div class="row footer ">
                    <input type="text" id="response" placeholder="Entrez votre réponse" class="col-7 text-box hidden" />
                    <button id="sendButton" onclick="sendResponse()" class="col-1 hidden">Send</button>
                    <input type="text" id="simpleMessage" placeholder="Entrez un message simple" class="col-7 text-box hidden" />
                    <input type="file" id="fileInput" class="col-3 hidden" title=" "/>
                    <button id="sendSimpleMessageButton" onclick="sendMessage()" class="col-1 hidden">Send</button>
                    
                    <div class="text-box hidden" contenteditable="true" disabled="true"></div>
                    <button id="sendMessage" class="hidden">send</button>
                </div>
            </div>
        </div>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="<?php echo $source; ?>style/chatbox.js"></script>

        <!--<button id="sendFileButton" onclick="sendFile()" class="hidden">Envoyer Fichier</button>-->
    </div>
    <script>
        
        var clientId = $.cookie('clientId');
        
        let connex = "";
        if (clientId !== undefined) {
            connex = 'ws://localhost:8080?type=client&userId=' + clientId;
        } else {
            connex = 'ws://localhost:8080?type=client';
        }
        var conn = new WebSocket(connex);
        
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
                $.cookie('clientId', data.id, { expires: 7, path: '/' });
            } 
            
            if (data.type === 'listMessages') {
                console.log("listMessages");
                if (data.messageClient) {
                    data.messageClient.forEach(message => {
                        
                        isClient = 'self';
                        if (message.isAdmin) {
                            isClient = 'other';
                        }

                        if (message.filePath) {
                            if (imageTypes.includes(message.fileType)) {
                                let img = document.createElement('img');
                                img.src = uploadsUrl + message.filePath;
                                img.className = "img-fluid";

                                var li = document.createElement('li');
                                li.className = isClient;
                                li.appendChild(img);
                                
                                chat.appendChild(li);
                                
                            } else {
                                let link = document.createElement('a');
                                link.href = uploadsUrl + message.filePath;
                                link.textContent = message.filePath;

                                var li = document.createElement('li');
                                li.className = isClient;
                                li.appendChild(link);
                                
                                chat.appendChild(li);
                                
                            }
                        }

                        if (message.message) {
                            var li = document.createElement('li');
                            li.className = isClient;
                            li.textContent = message.message;
                            chat.appendChild(li);
                        }


                    });
                }
                if (data.lastQuestionSave == null) {
                    console.log("affiche");
                    simpleMessageInput.classList.remove('hidden');
                    sendSimpleMessageButton.classList.remove('hidden');
                    fileInput.classList.remove('hidden');
                    $(".choices").remove();
                    
                    responseInput.classList.add('hidden');
                    sendButton.classList.add('hidden');
                    
                } else {
                    console.log("affiche 2");
                    responseInput.classList.remove('hidden');
                    sendButton.classList.remove('hidden');
                    $(".choices").remove();
                    
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                    fileInput.classList.add('hidden');
                }
                
            }
            
            if (data.questionOld) {
                console.log("questionOld");
                if (Object.keys(data.choicesOld).length > 0) {
                    var li = document.createElement('li');
                    li.className = 'other';
                    
                    for (var choice in data.choicesOld) {
                        var chatMessage = document.createElement('div');
                        chatMessage.textContent = data.choicesOld[choice];
                        
                        li.appendChild(chatMessage);
                        
                    }
                    
                    chat.appendChild(li);
                    
                } 
                
                var li = document.createElement('li');
                li.className = 'self';
                li.textContent = data.reponseQuestion;
                chat.appendChild(li);
            }
            
            let self = "other";
            if (data.self) {
                self = "self";
            }
            
            if (data.question) {
                
                console.log('question');
                currentQuestionId = data.question_id;  // Mise à jour de currentQuestionId
                
                var li = document.createElement('li');
                li.className = "other";
                console.log(data.question);
                li.textContent = data.question;
                chat.appendChild(li);
                
                $(".choices").remove();
//                choicesDiv.innerHTML = ''; // Clear previous choices
                if (Object.keys(data.choices).length > 0) {
                    
                    var li = document.createElement('li');
                    li.classList.add('other', 'choices', 'class3');
                    
                    for (var choice in data.choices) {
                        
                        var button = document.createElement('button');
                        button.innerHTML = data.choices[choice];
                        button.onclick = (function(choice) {
                            return function() {
                                sendChoice(choice);
                            };
                        })(choice);
                        
                        li.appendChild(button);
//                        choicesDiv.appendChild(button);
                    }
                    
                    chat.appendChild(li);
                    
                    responseInput.classList.add('hidden');
                    sendButton.classList.add('hidden');
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                    fileInput.classList.add('hidden');
//                    sendFileButton.classList.add('hidden');
                } else {
                    console.log('not choices 1');
                    responseInput.classList.remove('hidden');
                    sendButton.classList.remove('hidden');
                    simpleMessageInput.classList.add('hidden');
                    sendSimpleMessageButton.classList.add('hidden');
                    fileInput.classList.add('hidden');
//                    sendFileButton.classList.add('hidden');
                }
            } else if (data.message) {
                console.log("message");
                console.log(data.message);
                
                
                if (isObject(data.message)) {
                    if (imageTypes.includes(data.message["type"])) {
                        let img = document.createElement('img');
                        img.src = uploadsUrl + data.message["file-name"];
                        img.className = "img-fluid";

                        var li = document.createElement('li');
                        li.className = self;
                        li.appendChild(img);

                        chat.appendChild(li);
                    } else {
                        
                        let link = document.createElement('a');
                        link.href = uploadsUrl + data.message["file-name"];
                        link.target = '_blank';
                        link.textContent = data.message["file-name"];

                        var li = document.createElement('li');
                        li.className = self;
                        li.appendChild(link);

                        chat.appendChild(li);
                        
                    }
                } else {
                    
                    var li = document.createElement('li');
                    li.className = self;

                    var chatMessage = document.createElement('div');
                    chatMessage.textContent = data.message;

                    li.appendChild(chatMessage);
                    chat.appendChild(li);
                    
                    // Show input for simple message and file upload if the questionnaire is complete
                    if (data.message.includes('Bienvenue au service commercial')) {
                        simpleMessageInput.classList.remove('hidden');
                        sendSimpleMessageButton.classList.remove('hidden');
                        fileInput.classList.remove('hidden');
//                        sendFileButton.classList.remove('hidden');
                        $(".choices").remove();
                        choicesDiv.innerHTML = ''; // Clear choices div when done
                        responseInput.classList.add('hidden');
                        sendButton.classList.add('hidden');
                    }
                }
            }
            
            
        };

        function sendMessage() {
            console.log('sendMessage');
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
        
        
        function sendChoice(choice) {
            if (currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: choice }));
            }
        }

        function sendResponse() {
            console.log('sendResponse');
            var response = responseInput.value;
            if (response && currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: response }));
                responseInput.value = '';
            }
        }

        
    </script>
</body>
</html>
