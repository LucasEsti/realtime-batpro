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
    </style>
</head>
<body>
    <div id="chatBox" readonly></div>
    <div id="messageContainer"></div>
    <input type="text" id="message" placeholder="Type your message..."/>
    <button onclick="sendMessage()">Send</button>

    <script>
        var ws = new WebSocket('ws://localhost:8080?type=client');
        var clientId = null;
        
        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);
            var chatBox = document.getElementById('chatBox');

            if (data.type === 'id') {
                clientId = data.id;
                var messageDiv = document.createElement('div');
                messageDiv.textContent = "Your client ID is " + clientId;
                chatBox.appendChild(messageDiv);
            } else if (data.type === 'message') {
                var chatMessage = document.createElement('div');
                chatMessage.textContent = data.message;
                chatBox.appendChild(chatMessage);
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        };

        function sendMessage() {
            var message = document.getElementById('message').value;
            var chatBox = document.getElementById('chatBox');
            if (clientId) {
                ws.send(JSON.stringify({ type: 'client', message: message, clientId: clientId }));
                
                var sentMessage = document.createElement('div');
                sentMessage.textContent = "You: " + message;
                chatBox.appendChild(sentMessage);
                chatBox.scrollTop = chatBox.scrollHeight;
                
                document.getElementById('message').value = '';
            }
        }
        
    </script>
</body>
</html>
