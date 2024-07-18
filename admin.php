<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./wp-content/plugins/Message/style/style_admin.css">
    <style>
        h2 {
            color: white;
            font-size: 35px;
            font-weight: bold;
            /* margin: 0 0; */
            align-items: center;
            justify-content: center;
        }
        .titre {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #066eb7;
            border-radius: 7px;
            padding: 2px;
            margin: 10px 20px;
            box-shadow: 6px 8px 10px rgba(0.3, 0.3, 0.3, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }   
        .titre:hover {
            transform: sclae(1.05);
            box-shadow: 6px 8px 15px rgba(0.3, 0.3, 0.3, 0.5);
        }
        .titre img {
            margin-right: 50px;
        }
        .titre::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150%;
            height: 150%;
            background-color: rgba(255, 255, 255, 0.2);
            transition: all 0.5s ease;
            border-radius: 50%;
            z-index: 0;
        }
        h3 {
            text-align: center;
        }
        .titre:hover::before {
            transform: translate(-50%, -50%) scale(0);
        }
        .titre:hover h2 {
            color: #f0ae13;
        }
        .clientList {
            margin-left: 20px;
            background-color: #274456;
            box-shadow: 6px 8px 10px rgba(0.3, 0.3, 0.3, 0.3);
        }
        .clientList h3 {
            color: white;
            font-size: 25px;
            font-weight: bold;
        }
        .discussion {
            margin-right: 20px;
            background-color: #F9F9F9;
        }
        .discussion h3 {
            font-size: 25px;
            font-weight: bold;
        }
        #clientList {
            list-style-type: none;
            padding: 0; 
            color: black;
        }
        #clientList li.unread {
            font-weight: bold;
            background-color: #8E8E8E; 
            color: white;
        }
        #clientList li {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #ccc;
            border-radius: 7px;
            border: none;
            background-color: #fff;
            margin: 5px;
            color: #000000;
        }
        .liste {
            overflow-y:auto;
            height: 29rem;
        }
        #messageLog {
            border: 1px solid #ccc;
            height: 460px;
            border-radius: 7px;
            overflow-y: auto;
            margin: 0 20px;
            padding: 15px;
        }
        .file-icon img, .image-icon img {
            display: inline-block;
            cursor: pointer;
            width: 35px;
            height: 35px;
            margin-left: 5px;
            margin-right: 5px;
            margin-top: 10px;
        }
        #adminFileInput, #adminImageInput {
            display: none;
        }
        .Bouton {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px;
        }
        .search-container {
            position: relative;
        }
        #searchInput {
            width: 250px;
            padding: 10px;
            border-radius: 20px;
            border: none;
            outline: none;
        }
        #searchButton {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
        #searchButton img {
            width: 25px;
            height: 25px;
            margin-top: 20px;
        }
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
            color: #066eb7;
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
            border-radius: 5px; 
        }
        #messageLog div[data-source="client"] img,
        #messageLog div[data-source="admin"] img {
            max-width: 100%; 
            height: auto; 
        }
        input[type="text"] {
            width: 350px;
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
        input[type="text"]:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .25);
        }
        button.sendAdminMessageButton {
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #fff;
            background-color: #E61F4A;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        button.sendAdminMessageButton:hover {
            background-color: #26b6b4;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        #searchInput {
            width: 250px;
            outline: none;
            font-size: 1rem;
            margin: 0 20px;
            margin-left: 40px;
            margin-top: 20px;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 20px;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
        #logo {
            width: 80px;
            height: 40px;
        }
        
    </style>
    <title>LiveChat</title>
</head>
<body>
    <div class="titre">
    <img id="logo" src="http://localhost/chat/wp-content/plugins/Message/image/logo-BATPRO_200x200pxl-removebg-preview.png" alt="Rechercher">
        <h2>Discussion avec les clients</h2>
    </div>
    
    <div class="clientList">
        <h3>Clients connectés</h3>
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Rechercher un client...">
            <button type="submit" id="searchButton">
                <img src="./wp-content/plugins/Message/image/recherche.png" alt="Rechercher">
            </button>
        </div>
        <div class="liste">
            <ul id="clientList"></ul>
        </div>
    </div>

    <div id="discussion" class="discussion">
        <h3><span id="selectedClientId"></span></h3>
        <div id="messageLog" class="messageLog"></div>

        <div class="Bouton">
            <input type="text" id="adminMessageInput" placeholder="Envoyer un message">
            <label for="adminFileInput" class="file-icon">
                <img src="./wp-content/plugins/Message/image/fichier.png" alt="Fichier">
            </label>
            <input type="file" id="adminFileInput">
            <label for="adminImageInput" class="image-icon">
                <img src="./wp-content/plugins/Message/image/image.png" alt="Image">
            </label>
            <input type="file" id="adminImageInput" accept="image/*">
            <button class="sendAdminMessageButton" id="sendAdminMessageButton">Envoyer</button>

            <form id="fileUploadForm" enctype="multipart/form-data">
                <input type="file" id="adminFileInput" name="file">
                <button type="submit"></button>
            </form>
        </div>
    </div>

<!--<script src="/script/admin.js"></script>-->
<!--pour l'upload-->
<script>
        document.getElementById('fileUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            const fileInput = document.getElementById('adminFileInput');
            if (fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]);

                fetch('upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Success:', data);

                    // Ajoutez le lien de téléchargement dans le messageLog
                    const fileLink = document.createElement('a');
                    fileLink.href = data.filePath;
                    fileLink.textContent = `Download ${data.fileName}`;
                    fileLink.download = data.fileName;
                    fileLink.style.display = 'block'; 

                    // Créer une div pour le message
                    const messageDiv = document.createElement('div');
                    messageDiv.setAttribute('data-source', 'admin'); 
                    messageDiv.classList.add('message'); // Assurez-vous d'utiliser les styles définis dans CSS
                    messageDiv.appendChild(fileLink);

                    // Ajouter le message à la zone de discussion
                    const messageLog = document.getElementById('messageLog');
                    messageLog.appendChild(messageDiv);
                    messageLog.scrollTop = messageLog.scrollHeight; // Pour faire défiler jusqu'au bas
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
            }
        });
    </script>
</body>
</html>