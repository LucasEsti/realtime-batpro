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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="<?php echo $source; ?>style/chatbox.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        input[type="file"] {
            display: none;
          }

          .custom-file-upload {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
          }
    </style>
</head>
<body>
        <h1 class="my-element">An animated element</h1>

        <div class=" floating-chat hidden ">
            <div class="new-message hidden">
                <i class="fa-solid fa-1"></i>
            </div>
            
            <i class="fa fa-comments" aria-hidden="true"></i>
            <div class="chat container-fluid">
                <div class="header">
                    <span class="title" style="align-self: flex-end;">
                        ChatLive
                    </span>
                    <button type="button" class="btn btn-link" aria-label="Close"><i class="fa-regular fa-window-minimize text-white"></i></button>
                </div>
                <ul id="chat" class="messages">
                </ul>
                <div class=" footer">
                    <div class="container">
                        <div class="row ">
                            <div id="response" class="col-8 hidden">
                                <input type="text" id="responseInput" placeholder="Entrez votre réponse" class="form-control text-box " />
                            </div>
                            
                            <div id="simpleMessage" class="col-8 hidden">
                                <input type="text" id="simpleMessageInput" placeholder="Entrez un message" class=" form-control text-box " />
                            </div>
                            
                            
                            <div id="fileInput" class="col-2 hidden ">
                                <label for="fileInputValue" class="custom-file-upload">
                                        <i class="fa-regular fa-file"></i>
                                </label>
                                <input type="file" id="fileInputValue" class="form-control" title=" "/>
                            </div>
                            
                            
                            <div id="sendButton" class="col-2 hidden ">
                                <button type="button" onclick="sendResponse()" class=" btn btn-primary "><i class="fa-regular fa-paper-plane"></i></button>
                            </div>
                            <div id="sendSimpleMessageButton" class="col-2 hidden">
                                <button type="button" onclick="sendMessage()" class=" btn btn-primary "><i class="fa-regular fa-paper-plane"></i></button>
                            </div>
                            
                        </div>
                    </div>
                    
                    
                    
                </div>
            </div>
        </div>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    

        <!--<button id="sendFileButton" onclick="sendFile()" class="hidden">Envoyer Fichier</button>-->
    </div>
    <script>
        
        function playNotificationSound() {
//            audio.play(); // Joue le son
        }

        // Appelez la fonction lorsque la notification est déclenchée
        const audio = new Audio('https://batpro-madagascar.com/wp-content/uploads/2024/09/livechat-129007.mp3'); 
        audio.load();
        var clientId = $.cookie('clientId');
        var newMessage = $(".new-message");
        
        let connex;
        let reconnection = 0;
        let linkServer = 'ws://localhost:8080';
        if (clientId !== undefined) {
            connex = linkServer + '?type=client&userId=' + clientId;
        } else {
            connex = linkServer + '?type=client';
        }
        var conn = new WebSocket(connex);
        
        // Définir l'URL des uploads depuis PHP
        const uploadsUrl = '<?php echo $uploadsUrl; ?>';
        function isObject(value) {
            return value !== null && typeof value === 'object' && value.constructor === Object;
        }
        
        
        var chat = document.getElementById('chat');
        var responseInput = document.getElementById('response');
        var sendButton = document.getElementById('sendButton');
        var simpleMessageInput = document.getElementById('simpleMessage');
        var sendSimpleMessageButton = document.getElementById('sendSimpleMessageButton');
        var fileInput = document.getElementById('fileInput');
//        var sendFileButton = document.getElementById('sendFileButton');
        var currentQuestionId = null;
        const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        
        
         function onMessageWebscocket(e) {
            var data = JSON.parse(e.data);
            if (data.type === 'pong') {
                console.log('Pong reçu du serveur');
            }
//            var chatBox = document.getElementById('chatBox');
            if (data.type === 'id') {
                $.cookie('clientId', data.id, { expires: 7, path: '/' });
            } 
            console.log('not pong');
            if (data.type === 'listMessages' && reconnection == 0) {
                console.log("listMessages");
                if (data.messageClient) {
                    console.log(data);
                    var statusMessage = 0;
                    
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
                            li.innerHTML = message.message;
                            chat.appendChild(li);
                        }
                        statusMessage = message.isReadClient;
                    });
                    
                    if (statusMessage == false) {
                        newMessage.removeClass("hidden");
                    }
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
                    fileInput.classList.remove('hidden');
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
                console.log(data);
                currentQuestionId = data.question_id;  // Mise à jour de currentQuestionId
                
                var li = document.createElement('li');
                li.className = "other";
                console.log(data.question);
                li.textContent = data.question;
                chat.appendChild(li);
                
                $(".choices").remove();
                if (Object.keys(data.choices).length > 0) {
                    console.log('choice object');
                    var li = document.createElement('li');
                    li.classList.add('other', 'choices', 'class3');
                    
                    for (var choice in data.choices) {
                        var button = document.createElement('button');
                        button.innerHTML = data.choices[choice];
                        button.setAttribute('type', 'button');
                        button.classList.add('btn', 'btn-outline-primary', 'ml-1', 'mr-1', 'mb-1', 'mt-1');
                        button.onclick = (function(choice) {
                            return function() {
                                sendChoice(choice);
                            };
                        })(choice);
                        
                        li.appendChild(button);
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
                    
                    if (!data.lien) {
                        fileInput.classList.add('hidden');
                    } else {
                        fileInput.classList.remove('hidden');
                    }
                    
//                    sendFileButton.classList.add('hidden');
                }
                playNotificationSound();
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
                        $(".choices").remove();
                        responseInput.classList.add('hidden');
                        sendButton.classList.add('hidden');
                    }
                    
                }
                if (self == "self") {
                    newMessage.addClass("hidden");
                } else {
                    newMessage.removeClass("hidden");
                }
                playNotificationSound();
                
            }
            
            var container = $('#chat');
            
            var target = $('#chat li:last');
            container.animate({
                scrollTop: target.offset().top - container.offset().top + container.scrollTop()
            }, 'slow');
        }

        
        function reconnectionOnClose() {
            if (conn && (conn.readyState === WebSocket.OPEN || conn.readyState === WebSocket.CONNECTING)) {
                console.log("Déjà connecté ou en cours de connexion, reconnexion non nécessaire.");
                return;
            }
            if (clientId !== undefined) {
                conn = linkServer + '?type=client&userId=' + clientId;

            } else {
                conn = linkServer + '?type=client';
            }
            conn = new WebSocket(connex);
            reconnection = 1;
            conn.onmessage = function(e) {
                onMessageWebscocket(e);
            };

            conn.onerror = function(error) {
                console.log('WebSocket error: ' + error.message);
            };

            conn.onclose = function() {
                reconnectionOnClose();
            };
        }
        
        conn.onopen = function() {
            console.log('WebSocket connection opened');
            $(".floating-chat").removeClass("hidden");
            const elementBounce = document.querySelector('.fa-comments');
            elementBounce.classList.add('animate__animated', 'animate__tada', "animate__delay-3s", "animate__infinite");
            setInterval(function() {
                console.log('Envoi du ping au serveur');
                conn.send(JSON.stringify({ type: 'ping' }));
            }, 120000);
        };
        
        conn.onclose = function() {
            console.log('WebSocket is closed now.');
            reconnectionOnClose();
        };
        conn.onerror = function(error) {
            console.log('WebSocket error: ' + error.message);
        };
        
        conn.onmessage = function(e) {
            onMessageWebscocket(e);
        };
        
       
        setInterval(function() {
            reconnectionOnClose(); // Réessaie de te reconnecter
        }, 5000);
        

        
        function sendResponse() {
            console.log('sendResponse');
            var response = $('#responseInput').val();
            var file = document.getElementById('fileInputValue').files[0];
            if (currentQuestionId !== null) {
                var dataResp = { type: 'client', question_id: currentQuestionId};
                dataResp.response = "";
                
                if (file) {
                    var reader = new FileReader();
                    var readFile = null;
                    reader.onload = function(e) {
                        var base64File = e.target.result.split(',')[1];
                        dataResp.file = { name: file.name, data: base64File };
                        
                        conn.send(JSON.stringify(dataResp));
                        $('#responseInput').val('');
                    };
                    reader.readAsDataURL(file);
                    $('#fileInputValue').val(''); // Clear the file input
                } else {
                    if (response) {
                        dataResp.response = response;
                        conn.send(JSON.stringify(dataResp));
                        $('#responseInput').val('');
                    }
                    
                }
                conn.send(JSON.stringify({type: 'client', isReadClient: 1 }));
                newMessage.addClass("hidden");
                
            }
        }
        
        function sendMessage() {
            console.log('sendMessage');
            var message2 = $('#simpleMessageInput').val();
            var file = document.getElementById('fileInputValue').files[0];
            if (message2) {
                conn.send(JSON.stringify({type: 'client', simple_message: message2 }));
                $('#simpleMessageInput').val('');
            }
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var base64File = e.target.result.split(',')[1];
                    conn.send(JSON.stringify({type: 'client', file: { name: file.name, data: base64File } }));
                };
                reader.readAsDataURL(file);
                $('#fileInputValue').val(''); // Clear the file input
            }
            conn.send(JSON.stringify({type: 'client', isReadClient: 1 }));
            newMessage.addClass("hidden");
        }
        
        
        
        function sendChoice(choice) {
            if (currentQuestionId !== null) {
                conn.send(JSON.stringify({type: 'client', question_id: currentQuestionId, response: choice }));
                conn.send(JSON.stringify({type: 'client', isReadClient: 1 }));
                newMessage.addClass("hidden");
            }
        }

        

        var element = $('.floating-chat');
        var myStorage = localStorage;

        if (!myStorage.getItem('chatID')) {
            myStorage.setItem('chatID', createUUID());
        }

        setTimeout(function() {
            element.addClass('enter');
        }, 1000);

        element.click(openElement);

        function openElement() {
            var messages = element.find('.messages');
            var textInput = element.find('.text-box');
            element.find('>i').hide();
            element.addClass('expand');
            element.find('.chat').addClass('enter');
            var strLength = textInput.val().length * 2;
            textInput.keydown(onMetaAndEnter).prop("disabled", false).focus();
            element.off('click', openElement);
            element.find('.header button').click(closeElement);
            element.find('#sendMessage').click(sendNewMessage);
            messages.scrollTop(messages.prop("scrollHeight"));
            newMessage.addClass("hidden");

            if (conn.readyState === conn.OPEN) {
                //vue sur message
                conn.send(JSON.stringify({type: 'client', isReadClient: 1 }));
            }

        }

        function closeElement() {
            element.find('.chat').removeClass('enter').hide();
            element.find('>i').show();
            element.removeClass('expand');
            element.find('.header button').off('click', closeElement);
            element.find('#sendMessage').off('click', sendNewMessage);
            element.find('.text-box').off('keydown', onMetaAndEnter).prop("disabled", true).blur();
            setTimeout(function() {
                element.find('.chat').removeClass('enter').show()
                element.click(openElement);
            }, 500);
        }

        function createUUID() {
            // http://www.ietf.org/rfc/rfc4122.txt
            var s = [];
            var hexDigits = "0123456789abcdef";
            for (var i = 0; i < 36; i++) {
                s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
            }
            s[14] = "4"; // bits 12-15 of the time_hi_and_version field to 0010
            s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1); // bits 6-7 of the clock_seq_hi_and_reserved to 01
            s[8] = s[13] = s[18] = s[23] = "-";

            var uuid = s.join("");
            return uuid;
        }

        function sendNewMessage() {
            var userInput = $('.text-box');
            var newMessage = userInput.html().replace(/\<div\>|\<br.*?\>/ig, '\n').replace(/\<\/div\>/g, '').trim().replace(/\n/g, '<br>');

            if (!newMessage) return;

            var messagesContainer = $('.messages');

            messagesContainer.append([
                '<li class="self">',
                newMessage,
                '</li>'
            ].join(''));

            // clean out old message
            userInput.html('');
            // focus on input
            userInput.focus();

            messagesContainer.finish().animate({
                scrollTop: messagesContainer.prop("scrollHeight")
            }, 250);
        }

        function onMetaAndEnter(event) {
            if ((event.metaKey || event.ctrlKey) && event.keyCode == 13) {
                sendNewMessage();
            }
        }
        
    </script>
</body>
</html>
