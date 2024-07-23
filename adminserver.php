<!DOCTYPE html>
<html>
<head>
    <title>Admin Chat</title>
    <style>
        #messageContainer {
            height: 200px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .clientMessages {
            margin-bottom: 20px;
        }
        .adminMessage {
            color: blue;
        }
    </style>
</head>
<body>
    <div id="messageContainer"></div>
    <input type="text" id="message" placeholder="Type your message..."/>
    <input type="text" id="clientId" placeholder="Client ID"/>
    <button onclick="sendMessage()">Send</button>

    <script>
        var ws = new WebSocket('ws://localhost:8080?type=admin');

        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);
            console.log(data);
            if (data.type === 'message') {
                var messageContainer = document.getElementById('messageContainer');
                var clientDiv = document.getElementById('client-' + data.from);

                if (!clientDiv) {
                    clientDiv = document.createElement('div');
                    clientDiv.id = 'client-' + data.from;
                    clientDiv.className = 'clientMessages';
                    var clientTitle = document.createElement('h3');
                    clientTitle.textContent = 'Client ' + data.from;
                    clientDiv.appendChild(clientTitle);
                    messageContainer.appendChild(clientDiv);
                }

                var messageDiv = document.createElement('div');
                messageDiv.textContent = 'Client: ' + data.message;
                clientDiv.appendChild(messageDiv);
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };

        function sendMessage() {
            var message = document.getElementById('message').value;
            var clientId = document.getElementById('clientId').value;
            if (message && clientId) {
                ws.send(JSON.stringify({ type: 'admin', message: message, clientId: clientId }));

                var messageContainer = document.getElementById('messageContainer');
                var clientDiv = document.getElementById('client-' + clientId);

                if (!clientDiv) {
                    clientDiv = document.createElement('div');
                    clientDiv.id = 'client-' + clientId;
                    clientDiv.className = 'clientMessages';
                    var clientTitle = document.createElement('h3');
                    clientTitle.textContent = 'Client ' + clientId;
                    clientDiv.appendChild(clientTitle);
                    messageContainer.appendChild(clientDiv);
                }

                var adminMessageDiv = document.createElement('div');
                adminMessageDiv.textContent = 'Admin: ' + message;
                adminMessageDiv.className = 'adminMessage';
                clientDiv.appendChild(adminMessageDiv);
                messageContainer.scrollTop = messageContainer.scrollHeight;

                document.getElementById('message').value = '';
            }
        }
    </script>
</body>
</html>
