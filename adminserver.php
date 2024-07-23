<!DOCTYPE html>
<html>
<head>
    <title>Admin Chat</title>
    <style>
        #clientContainer {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .client-section {
            border: 1px solid #ccc;
            padding: 10px;
        }
        .client-messages {
            height: 150px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div id="clientContainer"></div>
    <input type="text" id="message" placeholder="Type your message..."/>
    <input type="text" id="clientId" placeholder="Client ID"/>
    <button onclick="sendMessage()">Send</button>

    <script>
        var ws = new WebSocket('ws://localhost:8080?type=admin');
        var clients = {};

        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);

            if (data.type === 'message') {
                if (!clients[data.from]) {
                    createClientSection(data.from);
                }

                var messageContainer = document.getElementById('messages-' + data.from);
                var messageDiv = document.createElement('div');
                messageDiv.textContent = 'Client: ' + data.message;
                messageContainer.appendChild(messageDiv);
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };

        function createClientSection(clientId) {
            var clientContainer = document.getElementById('clientContainer');

            var clientSection = document.createElement('div');
            clientSection.className = 'client-section';
            clientSection.id = 'client-' + clientId;

            var header = document.createElement('h3');
            header.textContent = 'Client: ' + clientId;
            clientSection.appendChild(header);

            var messageContainer = document.createElement('div');
            messageContainer.className = 'client-messages';
            messageContainer.id = 'messages-' + clientId;
            clientSection.appendChild(messageContainer);

            clientContainer.appendChild(clientSection);
            clients[clientId] = true;
        }

        function sendMessage() {
            var message = document.getElementById('message').value;
            var clientId = document.getElementById('clientId').value;
            if (message && clientId) {
                ws.send(JSON.stringify({ type: 'message', message: message, clientId: clientId }));
                document.getElementById('message').value = '';
            }
        }
    </script>
</body>
</html>
