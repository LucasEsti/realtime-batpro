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
                var messageDiv = document.createElement('div');
                messageDiv.textContent = 'Client (' + data.from + '): ' + data.message;
                messageContainer.appendChild(messageDiv);
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };

        function sendMessage() {
            var message = document.getElementById('message').value;
            var clientId = document.getElementById('clientId').value;
            if (message && clientId) {
                ws.send(JSON.stringify({ type: 'admin', message: message, clientId: clientId }));
                document.getElementById('message').value = '';
            }
        }
    </script>
</body>
</html>
