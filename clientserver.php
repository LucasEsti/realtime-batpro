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
        }
        #messageContainer {
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <textarea id="chatBox" readonly></textarea>
    <div id="messageContainer"></div>
    <input type="text" id="message" placeholder="Type your message..."/>
    <button onclick="sendMessage()">Send</button>

    <script>
        var ws = new WebSocket('ws://localhost:8080?type=client');
        var clientId = null;
        
        ws.onmessage = function(event) {
            var data = JSON.parse(event.data);

            if (data.type === 'id') {
                clientId = data.id;
                var messageDiv = document.createElement('div');
                messageDiv.textContent = event.data;
            } else if (data.type === 'message') {
                var messageContainer = document.getElementById('messageContainer');
                var messageDiv = document.createElement('div');
                messageDiv.textContent = event.data;
                messageContainer.appendChild(messageDiv);
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };

        
        function sendMessage() {
            var message = document.getElementById('message').value;
            if (clientId) {
                ws.send(JSON.stringify({ type: 'client', message: message, clientId: clientId }));
                document.getElementById('message').value = '';
            }
        }
        
    </script>
    
</body>
</html>
