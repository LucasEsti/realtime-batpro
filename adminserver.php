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
            overflow-y: scroll;
            border: 1px solid #ccc;
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
         #messageContainer2 {
            width: 300px;
            height: 200px;
            border: 1px solid #ccc;
            overflow-y: auto;
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
                  clientDiv.classList.add('clientSection', 'chat');
                  
                  contentDiv = document.createElement('div');
                  contentDiv.id = 'content-' + from;
                  contentDiv.classList.add('scrollable-div');
                  clientDiv.appendChild(contentDiv);
                  
                  messageContainer.appendChild(clientDiv);
                  
              }
        }
        
        function createInput(from) {
            if (!document.getElementById('input-' + from)) {
                let clientDiv = document.getElementById('messages-' + from);
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
            
            
        }
        
        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);
            console.log(data);
            
            if (data.type === 'listMessages') {
                messages = data.message;
                for (const key in messages) {
                    
                    if (messages.hasOwnProperty(key)) {
                        var statusMessage = 0;
                        var messageContainer = document.getElementById('messageContainer');
                            messages[key].forEach(message => {
                                
                                createMessageSection(message.idClient, message.nom);
                                let textAdmin = 'bubble me';
                                if (message.isAdmin == true) {
                                     textAdmin = 'bubble you';
                                }
                                var messageDisplay = document.getElementById('content-' + message.idClient);
                                if (message.filePath) {
                                      if (imageTypes.includes(message.fileType)) {
                                            messageDiv = document.createElement('img');
                                            messageDiv.src = uploadsUrl + message.filePath;
                                            messageDiv.classList.add('img-fluid', textAdmin);
                                          
                                            messageDisplay.appendChild(messageDiv);
                                            messageContainer.scrollTop = messageContainer.scrollHeight;
                                        } else {
                                            messageDiv = document.createElement('a');
                                            messageDiv.href = uploadsUrl + message.filePath;
                                            messageDiv.textContent = message.filePath;
                                            messageDiv.classList.add(textAdmin);

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
                                  statusMessage = message.isReadAdmin;
                                 
                            });
                            
                            
                            
                            if (statusMessage == 0) {
                                $("#client-" + key).addClass('non-lu');
                            }
                            
                            createInput(key);
                        }
                        
                  }
                    document.getElementById('listPeople').firstElementChild.classList.add('active');
                    document.getElementById('messageContainer').children[1].classList.add('active-chat');
                    
                    var container = $('#messageContainer');
                    var target = $('#input-' + document.getElementById('messageContainer').children[1].getAttribute("data-chat"));
                    console.log(target);
                    container.animate({
                        scrollTop: target.offset().top - container.offset().top + container.scrollTop()
                    }, 'slow');
                    
            }
            
            
            
            if (data.type === 'message') {
                var messageContainer = document.getElementById('messageContainer');
                
                createMessageSection(data.from, data.from);
                createInput(data.from);
                var messageDisplay = document.getElementById('content-' + data.from);
                var messageDiv = document.createElement('div');
                
                if (data.questionOld) {
                    if (data.questionOld.id == 102 || data.questionOld.id == 202) {
                        $("#client-" + data.from + " span" ).text(data.reponseQuestion);
                    }
                    messageDiv = document.createElement('div');
                    messageDiv.classList.add('bubble', 'you');
                    messageDiv.textContent = data.questionOld.question;
                    messageDisplay.appendChild(messageDiv);
                    
                    if (Object.keys(data.choicesOld).length > 0) {
                        for (var choice in data.choicesOld) {
                            messageDiv = document.createElement('div');
                            messageDiv.textContent = data.choicesOld[choice];
                            messageDiv.classList.add('bubble', 'you');
                            messageDisplay.appendChild(messageDiv);
                        }
                    } 
                    messageDiv = document.createElement('div');
                    messageDiv.classList.add('bubble', 'me');
                    messageDiv.textContent = data.reponseQuestion;
                    messageDisplay.appendChild(messageDiv);
                } else if (data.message) {
                    console.log("data.message");
                    console.log(data.message);
                    if (isObject(data.message)) {
                        console.log(data.message["type"]);
                        if (imageTypes.includes(data.message["type"])) {
                            messageDiv = document.createElement('img');
                            messageDiv.src = uploadsUrl + data.message["file-name"];
                            messageDiv.classList.add('bubble', 'you', "img-fluid");
                            messageDisplay.appendChild(messageDiv);
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        } else {
                            messageDiv = document.createElement('a');
                            messageDiv.href = uploadsUrl + data.message["file-name"];
                            messageDiv.textContent = data.message["file-name"];
                            messageDiv.classList.add('bubble', 'you');
                            messageDisplay.appendChild(messageDiv);
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        }
                    } else {
                        console.log("message simple");
                        messageDiv = document.createElement('div');
                        messageDiv.textContent = data.message;
                        messageDiv.classList.add('bubble', 'me');
                        messageDisplay.appendChild(messageDiv);
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                    

                } 
                
                //                    mettre en top dernier message non lu 
                    $("#client-" + data.from).prependTo('#listPeople');
                    $("#client-" + data.from).addClass('non-lu');
                
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

                var messageDisplay = document.getElementById('content-' + clientId);
                var adminMessageDiv = document.createElement('div');
                adminMessageDiv.classList.add('bubble', 'you', 'adminMessage');
                adminMessageDiv.textContent = message;
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
