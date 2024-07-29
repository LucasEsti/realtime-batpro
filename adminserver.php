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
    <title>Admin Chat</title>
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
    </style>
</head>
<body>
    <div id="messageContainer"></div>
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
        
        function createMessageSection(idClient, nom) {
            var from = idClient;
            var messageContainer = document.getElementById('messageContainer');
            var clientDiv = document.getElementById('client-' + from);
            if (!clientDiv) {
                  clientDiv = document.createElement('div');
                  clientDiv.id = 'client-' + from;
                  clientDiv.className = 'clientSection';
                  
                  let name = from;
                  if (nom != "") {
                      name = nom;
                  }
                  var clientTitle = document.createElement('h3');
                  clientTitle.textContent = 'Client ' + name;
                  clientDiv.appendChild(clientTitle);

                  var messageDisplay = document.createElement('div');
                  messageDisplay.id = 'messages-' + from;
                  clientDiv.appendChild(messageDisplay);

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

                  messageContainer.appendChild(clientDiv);
              }
        }
        
        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);
            console.log(data);
            
            if (data.type === 'listMessages') {
                messages = data.message;
                for (const key in messages) {
                    if (messages.hasOwnProperty(key)) { 
                        messages[key].forEach(message => {
//                              console.log(`ID: ${message.idClient}, Content: ${message.message}`);
                                var messageContainer = document.getElementById('messageContainer');
                                createMessageSection(message.idClient, message.nom);
                                let textAdmin = 'Client: ';
                                if (message.isAdmin == true) {
                                     textAdmin = 'Admin: ';
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
                                      messageDiv.textContent = textAdmin + 'Client: ' + message.message;

                                      messageDisplay.appendChild(messageDiv);
                                      messageContainer.scrollTop = messageContainer.scrollHeight;
                                  }
                        });
                    }
                  }
            }
            
            if (data.type === 'message') {
                var messageContainer = document.getElementById('messageContainer');
                
                createMessageSection(data.from, data.from);
                
                var messageDisplay = document.getElementById('messages-' + data.from);
                var messageDiv = document.createElement('div');
                
                if (data.questionOld) {
                    if (data.questionOld.id == 2) {
                        $("#client-" + data.from + " h3" ).text("Client " + data.reponseQuestion);
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
</body>
</html>
