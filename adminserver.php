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
                    clientDiv.className = 'clientSection';
                    
                    var clientTitle = document.createElement('h3');
                    clientTitle.textContent = 'Client ' + data.from;
                    clientDiv.appendChild(clientTitle);

                    var messageDisplay = document.createElement('div');
                    messageDisplay.id = 'messages-' + data.from;
                    clientDiv.appendChild(messageDisplay);

                    var messageInput = document.createElement('input');
                    messageInput.type = 'text';
                    messageInput.placeholder = 'Type your message...';
                    messageInput.id = 'input-' + data.from;
                    clientDiv.appendChild(messageInput);

                    var sendButton = document.createElement('button');
                    sendButton.textContent = 'Send';
                    sendButton.onclick = (function(clientId) {
                        return function() {
                            sendMessage(clientId);
                        };
                    })(data.from);
                    clientDiv.appendChild(sendButton);

                    messageContainer.appendChild(clientDiv);
                }

                var messageDisplay = document.getElementById('messages-' + data.from);
                var messageDiv = document.createElement('div');
                messageDiv.textContent = 'Client: ' + data.message;
                messageDisplay.appendChild(messageDiv);
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };

        function sendMessage(clientId) {
            var messageInput = document.getElementById('input-' + clientId);
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
        }
    </script>
</body>
</html>
