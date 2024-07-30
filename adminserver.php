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
    <title>Admin Chat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
<link rel="stylesheet" href="<?php echo $source; ?>direct-messaging/dist/style.css">
    <style>
        #messageContainer {
            height: 400px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .clientSection {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .adminMessage {
            color: blue;
        }
        
        .scrollable-div {
            overflow-y: scroll; /* Active le défilement vertical si nécessaire */
        }

        
    </style>
</head>
<body>
    <div ></div>
    
    <div class="wrapper">
    <div class="container">
        <div class="left">
            <div class="top">
                <input type="text" placeholder="Search" />
                <a href="javascript:;" class="search"></a>
            </div>
            <ul id="listPeople" class="people">
            </ul>
        </div>
        <div id="messageContainer" class="right">
            <div class="top"><span>To: <span class="name"></span></span></div>
            
            
<!--            <div class="write">
                <a href="javascript:;" class="write-link attach"></a>
                <input type="text" />
                <a href="javascript:;" class="write-link smiley"></a>
                <a href="javascript:;" class="write-link send"></a>
            </div>-->
        </div>
    </div>
</div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script>
        var ws = new WebSocket('ws://localhost:8080?type=admin');
        function isObject(value) {
            return value !== null && typeof value === 'object' && value.constructor === Object;
        }
        const uploadsUrl = '<?php echo $uploadsUrl; ?>';
        const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        ws.onopen = function() {
            console.log('WebSocket connection opened');
        };
        
        var listMessage = document.getElementById('listMessage');
        
        function createMessageSection(idClient, nom) {
            var from = idClient;
            var messageContainer = document.getElementById('messageContainer');
            var people = document.getElementById('listPeople');
            
            var clientDiv = document.getElementById('client-' + from);
            if (!clientDiv) {
                let name = from;
                if (nom != "") {
                    name = nom;
                }

                <!--list chat-->
                var liPersonne = document.createElement('li');
                liPersonne.setAttribute('data-chat', from);
                liPersonne.id = 'client-' + from;
                liPersonne.classList.add('person', 'chat');

                var spanPerson = document.createElement('span');
                spanPerson.className = 'name';
                spanPerson.textContent = name;

                liPersonne.appendChild(spanPerson);

                people.appendChild(liPersonne);
                updateFriends();
                    <!--list client-->
                
                
                  clientDiv = document.createElement('div');
                  clientDiv.id = 'messages-' + from;
                  clientDiv.setAttribute('data-chat', from);
                  clientDiv.classList.add('clientSection', 'chat', 'scrollable-div');

                  messageContainer.appendChild(clientDiv);
              }
        }
        
        function createInput(from, clientDiv) {
            var messageInput = document.createElement('input');
            messageInput.type = 'text';
            messageInput.placeholder = 'Type your message...';
            messageInput.id = 'input-' + from;
            clientDiv.appendChild(messageInput);

            var fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.id = 'file-' + from;
            clientDiv.appendChild(fileInput);


            var sendButton = document.createElement('button');
            sendButton.textContent = 'Send';
            sendButton.onclick = (function(clientId) {
                return function() {
                    sendMessage(clientId);
                };
            })(from);
            clientDiv.appendChild(sendButton);
            
            
        }
        
        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);
            console.log(data);
            
            if (data.type === 'listMessages') {
                messages = data.message;
                for (const key in messages) {
                    
                    if (messages.hasOwnProperty(key)) {
                        messages[key].forEach(message => {
                                var messageContainer = document.getElementById('messageContainer');
                                createMessageSection(message.idClient, message.nom);
                                let textAdmin = 'bubble me';
                                if (message.isAdmin == true) {
                                     textAdmin = 'bubble you';
                                }
                                var messageDisplay = document.getElementById('messages-' + message.idClient);
                                if (message.filePath) {
                                      if (imageTypes.includes(message.fileType)) {
                                          messageDiv = document.createElement('img');
                                          messageDiv.src = uploadsUrl + message.filePath;
                                          messageDiv.className = "img-fluid";
                                          
                                          let messageDiv2 = document.createElement('div');
                                            messageDiv2.textContent = textAdmin;
                                            messageDisplay.appendChild(messageDiv2);
                                          
                                          messageDisplay.appendChild(messageDiv);
                                          messageContainer.scrollTop = messageContainer.scrollHeight;
                                      } else {
                                          messageDiv = document.createElement('a');
                                          messageDiv.href = uploadsUrl + message.filePath;
                                          messageDiv.textContent = message.filePath;
                                          
                                          let messageDiv2 = document.createElement('div');
                                            messageDiv2.textContent = textAdmin;
                                            messageDisplay.appendChild(messageDiv2);

                                          messageDisplay.appendChild(messageDiv);
                                          messageContainer.scrollTop = messageContainer.scrollHeight;
                                      }
                                  } 
                                  if (message.message) {
                                      console.log("message simple");
                                      messageDiv = document.createElement('div');
                                      messageDiv.textContent = message.message;
                                      messageDiv.className = textAdmin;

                                      messageDisplay.appendChild(messageDiv);
                                      messageContainer.scrollTop = messageContainer.scrollHeight;
                                  }
                                  
                                 
                            });
                            createInput(key, document.getElementById('messages-' + key));
                    }
                  }
                  document.getElementById('listPeople').firstElementChild.classList.add('active');
                  document.getElementById('messageContainer').children[1].classList.add('active-chat');
                
                  
            }
            
            if (data.type === 'message') {
                var messageContainer = document.getElementById('messageContainer');
                
                createMessageSection(data.from, data.from);
                
                var messageDisplay = document.getElementById('messages-' + data.from);
                var messageDiv = document.createElement('div');
                
                if (data.questionOld) {
                    if (data.questionOld.id == 2) {
                        $("#client-" + data.from + " span" ).text(data.reponseQuestion);
                    }
                    messageDiv = document.createElement('div');
                    messageDiv.textContent = data.questionOld.question;
                    messageDisplay.appendChild(messageDiv);
                    if (Object.keys(data.choicesOld).length > 0) {
                        for (var choice in data.choicesOld) {
                            messageDiv = document.createElement('div');
                            messageDiv.textContent = data.choicesOld[choice];
                            messageDisplay.appendChild(messageDiv);
                        }
                    } 
                    messageDiv = document.createElement('div');
                    messageDiv.textContent = "reponse :" + data.reponseQuestion;
                    messageDisplay.appendChild(messageDiv);
                } else if (data.message) {
                    console.log("data.message");
                    console.log(data.message);
                    if (isObject(data.message)) {
                        console.log(data.message["type"]);
                        if (imageTypes.includes(data.message["type"])) {
                            messageDiv = document.createElement('img');
                            messageDiv.src = uploadsUrl + data.message["file-name"];
                            messageDiv.className = "img-fluid";
                            messageDisplay.appendChild(messageDiv);
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        } else {
                            messageDiv = document.createElement('a');
                            messageDiv.href = uploadsUrl + data.message["file-name"];
                            messageDiv.textContent = data.message["file-name"];
                            
                            messageDisplay.appendChild(messageDiv);
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        }
                    } else {
                        console.log("message simple");
                        messageDiv = document.createElement('div');
                        messageDiv.textContent = 'Client: ' + data.message;
                        
                        messageDisplay.appendChild(messageDiv);
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                } 
                console.log("-------------");

            } 
        };

        function sendMessage(clientId) {
            var messageInput = document.getElementById('input-' + clientId);
            var fileInput = document.getElementById('file-' + clientId);
            var file = fileInput.files[0];
            var message = messageInput.value;
            if (message && clientId) {
                ws.send(JSON.stringify({ type: 'admin', message: message, clientId: clientId }));

                var messageDisplay = document.getElementById('messages-' + clientId);
                var adminMessageDiv = document.createElement('div');
                adminMessageDiv.textContent = 'Admin: ' + message;
                adminMessageDiv.className = 'adminMessage';
                messageDisplay.appendChild(adminMessageDiv);

                messageInput.value = '';
            } 
            if (file && clientId) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var base64File = e.target.result.split(',')[1];
                    ws.send(JSON.stringify({ file: { name: file.name, data: base64File }, clientId: clientId }));
                };
                reader.readAsDataURL(file);
                fileInput.value = ''; // Clear the file input
            }
        }
    </script>
    
    <script  src="<?php echo $source; ?>direct-messaging/dist/script.js"></script>
</body>
</html>
