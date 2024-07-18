<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="http://localhost/chat/wp-content/plugins/Message/style/style_client.css">
    <title>LiveChat</title>
    <style>
        @keyframes scroll {
            0% { transform: translateX(100%); }/* Commencer le défilement à droite de l'écran */
            100% {transform: translateX(-100%);}/* Déplacer le message complètement à gauche de l'écran */
        }
        .container {
            width: 400px;
            margin: 0 auto;
            padding: 0;
            border-radius: 10px;
            position: fixed;
            box-shadow: -6px 8px 10px rgba(0.3, 0.3, 0.1, 0.3), 6px 8px 10px rgba(0.3, 0.3, 0.3, 0.3);
            bottom: 90px; 
            right: 20px;
            z-index: 2; 
            display: none; 
        }
        .chat-header {
            background-color: #066eb7;
            color: #fff;
            padding: 10px 20px;
            text-align: center;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .chat-header h1 {
            font-size: 40px;
            color: white;
            font-weight: bold;
            margin: 0;
            font-size: 35px;
        }
        .input-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px;
        }
        .message-log {
            overflow-y: auto;
            height: 350px;
            width: 377.5px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f5f5f5;
            scroll-behavior: smooth;
        }
        .input-container input[type="text"] {
            width: 300px;
            padding: 10px;
            outline: none;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 5px;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
        .input-container input[type="text"]:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
        }
        .input-container button {
            height: 45px;
            width: 80px;
            font-size: 1rem;
            line-height: 1.5;
            color: #fff;
            background-color: #E61F4A;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .input-container button:hover {
            background-color: #26b6b4;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        #ImageInput {
            display: none;
        }
        .image-icon img {
            display: inline-block;
            cursor: pointer;
            width: 35px;
            margin-left: 5px;
            margin-right: 5px;
            margin-top: 10px;
        }
        /* Client mandefa message */
        #messageLog div[data-source="admin"] {
            text-align: start; 
            margin-bottom: 10px;
            padding: 5px;
            border-radius: 10px;
            background-color: #066eb7;
            max-width: 60%;
            clear: both;
            word-wrap: break-word;
            float: right;
            color: #fff;
        }
        /* Admin mandefa message */
        #messageLog div[data-source="client"] {
            text-align: start;
            margin-bottom: 10px;
            padding: 5px;
            border-radius: 10px;
            background-color: #f0ae13;
            max-width: 60%;
            clear: both;
            word-wrap: break-word;
            float: left;
            color: #fff;
        }
        #messageLog div[data-source="client"] img,
        #messageLog div[data-source="admin"] img {
            max-width: 100%; 
            height: auto; 
        }
        #malagasyButton:disabled, #francaisButton:disabled,
        #outillageBouton:disabled, #savBouton:disabled,
        #métallurgieBouton:disabled, #partenariatBouton:disabled,
        #peintureBouton:disabled, #fournisseursBouton:disabled,
        #sécuritéBouton:disabled, #recrutementBouton:disabled,
        #travauxBouton:disabled, #contacterBouton:disabled,
        #équipementBouton:disabled, #autresBouton:disabled, 
        #quincaillerieBouton:disabled, #jardinBouton:disabled
        #boutonAutre:disabled, #niveau1:disabled{
            opacity: 0.6; 
            cursor: not-allowed;
        }
        #malagasyButton, #francaisButton,
        #niveau1,
        #outillageBouton, #savBouton,
        #métallurgieBouton, #partenariatBouton,
        #peintureBouton, #fournisseursBouton,
        #sécuritéBouton, #recrutementBouton,
        #travauxBouton, #contacterBouton,
        #équipementBouton, #autresBouton, #quincaillerieBouton
        #jardinBouton, #boutonAutre {
            background-color: #fff;
            color: #f0ae13;
            border: none; 
            padding: 10px 20px;
            text-align: center; 
            text-decoration: none;
            display: inline-block;
            font-size: 16px; 
            font-weight: bold;
            margin-right: 10px;
            margin-top: 7px; 
            position: center;
            cursor: pointer; 
            border-radius: 5px; 
            transition: background-color 0.3s ease;
        }
        #malagasyButton:hover,
        #francaisButton:hover,
        #niveau1:hover,
        #outillageBouton:hover, #savBouton:hover,
        #métallurgieBouton:hover, #partenariatBouton:hover,
        #peintureBouton:hover, #fournisseursBouton:hover,
        #sécuritéBouton:hover, #recrutementBouton:hover,
        #travauxBouton:hover, #contacterBouton:hover,
        #équipementBouton:hover, #autresBouton:hover
        #quincaillerieBouton:hover, #jardinBouton:hover,
        #boutonAutre:hover {
            background-color: #E61F4A; 
            color: #fff;
        }

        .bubble {
            position: fixed;
            bottom: 20px;
            right: 20px;
            cursor: pointer;
            z-index: 1;
            transition: transform 0.3s ease;
        }
        .bubble:hover {
            transform: scale(1.1);
        }
        .bubble img {
            width: 50px; 
            height: auto;
        }
        #logo {
            width: 80px;
            height: 40px;
        }
    </style>
</head>
<body>
    <!-- Bulle de discussion -->
    <div class="bubble" id="bubble">
        <!-- Icône de la bulle de discussion -->
        <img src="http://localhost/chat/wp-content/plugins/Message/image/74-749108_download-png-ico-icns-facebook-messenger-round-icon-removebg-preview.png" alt="Chat Bubble Icon">
    </div>
    <!-- Conteneur de chat -->
    <div class="container" id="container" style="display: none;">
        <div class="chat-header">
        <img id="logo" src="http://localhost/chat/wp-content/plugins/Message/image/logo-BATPRO_200x200pxl-removebg-preview.png" alt="Rechercher">
            <h1 id="clientName"></h1>
        </div>
        <div class="message-log" id="messageLog">
            <!-- Les messages seront ajoutés ici -->
        </div>
        <div class="input-container">
            <input type="text" id="messageInput" placeholder="Entrez votre message...">
            <label for="ImageInput" class="image-icon">
                <img src="http://localhost/chat/wp-content/plugins/Message/image/image.png" alt="Image">
            </label>
            <input type="file" id="ImageInput" accept="image/*">
            <button id="sendMessageButton">Envoyer</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/uuid/8.3.2/uuid.min.js"></script>
    <!-- <script src="/script/client.js"></script> -->
    
    <script>
        // Au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer la bulle de discussion
            const bubble = document.getElementById('bubble');
            // Récupérer le conteneur de chat
            const container = document.getElementById('container');

            // Ajouter un écouteur d'événements au clic sur la bulle de discussion
            bubble.addEventListener('click', function() {
                // Basculer la visibilité du conteneur de chat
                container.style.display = container.style.display === 'none' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>