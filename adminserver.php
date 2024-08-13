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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
<link rel="stylesheet" href="<?php echo $source; ?>direct-messaging/dist/style.css">
    <style>
        
        
    </style>
</head>
<body>
    <div></div>
    
    <div class="wrapper">
        <div class="container">
            <div class="left">
                <div class="top">
    <!--                <input type="text" placeholder="Search" />
                    <a href="javascript:;" class="search"></a>-->
                </div>
                <ul id="listPeople" class="people">
                </ul>
            </div>


            <div id="messageContainer" class="right">
                <div class="top"><span>To: <span class="name"></span></span></div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
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
        
        ws.onclose = function() {
                console.log('WebSocket is closed now.');
            };
        ws.onerror = function(error) {
            console.log('WebSocket error: ' + error.message);
        };
        
        var listMessage = document.getElementById('listMessage');
        
        function createMessageSection(idClient, nom) {
            var from = idClient;
            var messageContainer = document.getElementById('messageContainer');
            var people = document.getElementById('listPeople');
            
            var clientDiv = document.getElementById('client-' + from);
            if (!clientDiv) {
                let name = from;
                if (nom != "" || nom != null) {
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
                  clientDiv.classList.add('clientSection', 'chat', 'row');
                  
                  contentDiv = document.createElement('div');
                  contentDiv.id = 'content-' + from;
                  contentDiv.classList.add('scrollable-div');
                  clientDiv.appendChild(contentDiv);
                  
                  messageContainer.appendChild(clientDiv);
                  
              }
        }
        
        function createInput(from, firstLoad = false) {
            
            if (!document.getElementById('input-' + from)) {
                console.log("createInput");
                let clientDiv = document.getElementById('messages-' + from);
                var messageInput = document.createElement('input');
                messageInput.type = 'text';
                messageInput.classList.add('form-control', 'mt-5');
                
                messageInput.placeholder = 'Type your message...';
                
                messageInput.id = 'input-' + from;
                clientDiv.appendChild(messageInput);

                var fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.classList.add('mt-2', 'form-control');
                fileInput.id = 'file-' + from;
                clientDiv.appendChild(fileInput);


                var sendButton = document.createElement('button');
                sendButton.textContent = 'Send';
                sendButton.classList.add('mt-2');
                sendButton.classList.add('btn', 'btn-primary');
                sendButton.setAttribute('type', 'button');
                sendButton.onclick = (function(clientId) {
                    return function() {
                        sendMessage(clientId);
                    };
                })(from);
                clientDiv.appendChild(sendButton);
                
                if (firstLoad == true) {
                    const personElements = document.querySelectorAll('.person');
                    let hasActivePerson = false;

                    personElements.forEach(person => {
                        if (person.classList.contains('active')) {
                            hasActivePerson = true;
                        }
                    });

                    if (!hasActivePerson) {
                        document.getElementById('client-' + from).classList.add('active');
                        document.getElementById('messages-' + from).classList.add('active-chat');
                    }
                    
                }
                
            }
            
            
        }
        
        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);
            console.log(data);
            
            if (data.type === 'listMessages') {
                messages = data.message;
                if (data.message.length != 0) {
                    var idFirstElement = "";
                    for (const key in messages) {

                        if (messages.hasOwnProperty(key)) {
                            var statusMessage = 0;
                            var messageContainer = document.getElementById('messageContainer');
                                messages[key].forEach(message => {

                                    createMessageSection(message.idClient, message.nom);
                                    var textAdmin = 'me';
                                    if (message.isAdmin == true) {
                                         textAdmin = 'you';
                                    }
                                    var messageDisplay = document.getElementById('content-' + message.idClient);
                                    if (message.filePath) {
                                          if (imageTypes.includes(message.fileType)) {
                                                messageDiv = document.createElement('div');
                                                messageDiv.classList.add('bubble', textAdmin);
                                                
                                                let img = document.createElement('img');
                                                img.src = uploadsUrl + message.filePath;
                                                img.classList.add('img-fluid');
                                                messageDiv.appendChild(img);

                                                messageDisplay.appendChild(messageDiv);
                                                messageContainer.scrollTop = messageContainer.scrollHeight;
                                            } else {
                                                messageDiv = document.createElement('div');
                                                messageDiv.classList.add('bubble', textAdmin);
                                                
                                                let divA = document.createElement('a');
                                                divA.href = uploadsUrl + message.filePath;
                                                divA.textContent = message.filePath;
                                                messageDiv.appendChild(divA);

                                                messageDisplay.appendChild(messageDiv);
                                                messageContainer.scrollTop = messageContainer.scrollHeight;
                                          }
                                      } 
                                      if (message.message) {
                                          console.log("message simple");
                                          messageDiv = document.createElement('div');
                                          messageDiv.innerHTML = message.message;
                                          messageDiv.classList.add('bubble', textAdmin);

                                          messageDisplay.appendChild(messageDiv);
                                          messageContainer.scrollTop = messageContainer.scrollHeight;
                                      }
                                      statusMessage = message.isReadAdmin;

                                });
                                
                                if (idFirstElement == "") {
                                    idFirstElement = key;
                                }

                                
                                if (statusMessage == 0) {
                                    $("#client-" + key).addClass('non-lu');
                                }

                                createInput(key);
                            }

                      }
                  
                        document.getElementById('client-' + idFirstElement).classList.add('active');
                        document.getElementById('messages-' + idFirstElement).classList.add('active-chat');

                        var container = $('#messageContainer');
                        var target = $('#input-' + document.getElementById('messageContainer').children[1].getAttribute("data-chat"));
                        container.animate({
                            scrollTop: target.offset().top - container.offset().top + container.scrollTop()
                        }, 'slow');
                  }
                    
                    
            }
            
            
            
            if (data.type === 'message') {
                var messageContainer = document.getElementById('messageContainer');
                
                createMessageSection(data.from, data.from);
                createInput(data.from, true);
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
                        let self = "me";
                        if (data.self) {
                            self = "you";
                        }
                        if (imageTypes.includes(data.message["type"])) {
                            messageDiv = document.createElement('div');
                            messageDiv.classList.add('bubble', self);

                            let img = document.createElement('img');
                            img.src = uploadsUrl + data.message["file-name"];
                            img.classList.add("img-fluid");
                            messageDiv.appendChild(img);
                            
                            messageDisplay.appendChild(messageDiv);
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        } else {
                            messageDiv = document.createElement('div');
                            messageDiv.classList.add('bubble', self);

                            let divA = document.createElement('a');
                            divA.href = uploadsUrl + data.message["file-name"];
                            divA.textContent = data.message["file-name"];
                            messageDiv.appendChild(divA);
                            
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
        
        let friends = {
  list: document.querySelector('ul.people'),
  all: document.querySelectorAll('.left .person'),
  name: '' },

chat = {
  container: document.querySelector('.container .right'),
  current: null,
  person: null,
  name: document.querySelector('.container .right .top .name') };

function updateFriends() {
    friends.all = document.querySelectorAll('.left .person');
    friends.list = document.querySelector('ul.people');
    friends.all.forEach(f => {
        f.addEventListener('mousedown', () => {
          f.classList.contains('active') || setAciveChat(f);
        });
      });
}

friends.all.forEach(f => {
  f.addEventListener('mousedown', () => {
    f.classList.contains('active') || setAciveChat(f);
  });
});

function setAciveChat(f) {
    if (friends.list != null && friends.list.querySelector('.active') != null) {
        friends.list.querySelector('.active').classList.remove('active');
        f.classList.add('active');
        f.classList.remove('non-lu');
        chat.current = chat.container.querySelector('.active-chat');

        chat.person = f.getAttribute('data-chat');
        ws.send(JSON.stringify({ type: 'admin', isReadAdmin: 1, clientId: chat.person }));
        console.log(chat.person);
        chat.current.classList.remove('active-chat');
        chat.container.querySelector('[data-chat="' + chat.person + '"]').classList.add('active-chat');
        friends.name = f.querySelector('.name').innerText;
        chat.name.innerHTML = friends.name;
        
        var container = $('#messageContainer');
        var target = $('#input-' + chat.person);
        container.animate({
            scrollTop: target.offset().top - container.offset().top + container.scrollTop()
        }, 'slow');
    }
  
}
    </script>
    
    <script  src="<?php echo $source; ?>direct-messaging/dist/script.js"></script>
</body>
</html>
