<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chatbot WebSocket</title>
</head>
<body>
    <div id="chat"></div>
    <input type="text" id="response" placeholder="Entrez votre choix" />
    <button onclick="sendResponse()">Envoyer</button>

    <script>
        var conn = new WebSocket('ws://localhost:8080');
        var chat = document.getElementById('chat');
        var responseInput = document.getElementById('response');
        var currentQuestionId = null;

        conn.onmessage = function(e) {
            var data = JSON.parse(e.data);
            if (data.question) {
                currentQuestionId = data.question_id;  // Mise à jour de currentQuestionId
                chat.innerHTML += '<p>' + data.question + '</p>';
                if (data.choices) {
                    for (var choice in data.choices) {
                        chat.innerHTML += '<p>' + choice + ': ' + data.choices[choice] + '</p>';
                    }
                }
            } else if (data.message) {
                chat.innerHTML += '<p>' + data.message + '</p>';
            }
        };

        function sendResponse() {
            var response = responseInput.value;
            if (currentQuestionId !== null) {
                conn.send(JSON.stringify({ question_id: currentQuestionId, response: response }));
                responseInput.value = '';
            } else {
                alert("Aucune question actuelle. Veuillez attendre la prochaine question.");
            }
        }
    </script>
</body>
</html>

