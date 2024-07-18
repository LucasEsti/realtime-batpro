const socket = new WebSocket('ws://localhost:8084');

socket.addEventListener('open', (event) => {
    console.log('WebSocket connection ouverte:', event);
});
// Verification si le client existe
let existingClients = [];

// Pour les clients connectés
socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    const clientId = data.clientId;
    // Vérification du client si il existe déjà dans la liste
    let existingItem = document.querySelector(`#clientList li[data-client-id="${clientId}"]`);

    if (!existingItem) {
        const clientList = document.getElementById('clientList');
        // Afficher le client avec son ID et son nom
        const listItem = document.createElement('li');
        listItem.textContent = `${clientId}`; // Affichage du nom du client
        listItem.setAttribute('data-client-id', clientId);
        // Marquer les nouveaux clients comme non lus
        listItem.classList.add('unread'); 
        listItem.addEventListener('click', () => {
            createClientChatWindow(clientId);
            const activeItems = document.querySelectorAll('.clientList li.active');
            activeItems.forEach(item => item.classList.remove('active'));
            listItem.classList.add('active');

            // Marquer les messages comme lus lorsque le client est sélectionné
            markMessagesAsRead(clientId);
        });
        // Insérer le nouvel élément au début de la liste
        clientList.insertBefore(listItem, clientList.firstChild);
    } else {
        // Déplacer l'élément existant vers le début de la liste
        existingItem.parentNode.insertBefore(existingItem, existingItem.parentNode.firstChild);
    }
    // Marquer les messages comme non lus lorsque de nouveaux messages arrivent
    markMessagesAsUnread(clientId);
    // Toujours marquer le dernier message comme non lu
    existingItem.classList.add('unread');
});
// Sélection de l'input de recherche
const searchInput = document.getElementById('searchInput');

// Ajout d'un écouteur d'événements sur l'input de recherche
searchInput.addEventListener('input', function() {
    const searchText = this.value.toLowerCase().trim();
    // Sélection de la liste des clients connectés
    const clientList = document.getElementById('clientList'); 
    const clientItems = clientList.querySelectorAll('li');
    
    clientItems.forEach(function(item) {
        // Récupération du texte du client actuel dans la liste
        const clientText = item.textContent.toLowerCase();
        // Vérification si le texte du client contient le texte saisi dans la zone de recherche
        if (clientText.includes(searchText)) {
            // Affichage du client si le texte correspond
            item.style.display = 'block';
        } else {
            // Masquage du client si le texte ne correspond pas
            item.style.display = 'none';
        }
    });
});
// Pour la creation de Zone de discussion de chaque client
socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    const clientId = data.clientId;
    const messageContent = data.content;
    const messageType = data.type;

    // Stocker temporairement le message dans la structure de données
    if (!clientMessages[clientId]) {
        clientMessages[clientId] = [];
    }
    clientMessages[clientId].push({ type: messageType, content: messageContent });
});
// Fonction pour marquer les messages comme lus
function markMessagesAsRead(clientId) {
    const clientListItem = document.querySelector(`#clientList li[data-client-id="${clientId}"]`);
    clientListItem.classList.remove('unread');
}
// Fonction pour marquer les messages comme non lus
function markMessagesAsUnread(clientId) {
    const clientListItem = document.querySelector(`#clientList li[data-client-id="${clientId}"]`);
    clientListItem.classList.add('unread');
}
// Pour la création de Zone de discussion de chaque client
socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    const adminMessage = data.adminMessage;
    const messageContent = data.content;
    const clientId = data.clientId;
    const clientName = data.clientName;
    const messageType = data.type;

    let clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
    if (!clientMessageDiv) {
        clientMessageDiv = document.createElement('div');
        clientMessageDiv.id = `messageLog-${clientId}`;
        document.getElementById('messageLog').appendChild(clientMessageDiv);
    }
    // Vérification du type de message
    if (messageType === 'image') {
        if (clientId !== 1) {
            const imgElement = document.createElement('img');
            imgElement.src = 'data:image/jpg;base64,' + messageContent;
            clientMessageDiv.appendChild(imgElement);
        }
    } else if (messageType === 'text/plain') { // Condition pour les fichiers texte
        //Appel de la fonction pour afficher le contenu du fichier texte
        displaySelectedFileContent(clientId, messageContent);
    }
    //Affichage des messages selon la source
    if (adminMessage) {
        displayAdminMessage(messageContent, clientId);
    } else {
        displayClientMessage(clientId, clientName, messageContent);
    }
});

socket.addEventListener('close', (event) => {
    console.log('WebSocket connection fermé', event);
});

socket.addEventListener('error', (event) => {
    console.error('WebSocket error', event);
});
//Cacher la div de discussion au chargement de la page
window.addEventListener('load', () => {
    document.getElementById('discussion').style.display = 'none';
});
//Ajout d'une gestionnaire d'événements pour le clic sur un client
document.getElementById('clientList').addEventListener('click', (event) => {
    if (event.target.tagName === 'LI') {
        const clientId = event.target.getAttribute('data-client-id');
        //Affichage de la div de discussion
        document.getElementById('discussion').style.display = 'block';
        //Cacher toutes les autres zones de discussion
        const allMessageLogs = document.querySelectorAll('[id^="messageLog-"]');
        allMessageLogs.forEach(messageLog => {
            if (messageLog.id === `messageLog-${clientId}`) {
                messageLog.style.display = 'block';
            } else {
                messageLog.style.display = 'none';
            }
        });
        //Marquer les messages du client comme lus
        markMessagesAsRead(clientId);
        //Afficher la section de la conversation client sélectionnée
        document.getElementById(`messageLog-${clientId}`).style.display = 'block';
    }
});
//Structure de données pour stocker temporairement les messages par client
let clientMessages = {};

//Pour la réception des messages WebSocket
socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    const clientId = data.clientId;
    const messageContent = data.content;
    const messageType = data.type;

    //initialisation du SD
    if (!clientMessages[clientId]) {
        clientMessages[clientId] = [];
    }
    //Stocker temporairement le message dans la structure de données
    clientMessages[clientId].push({ type: messageType, content: messageContent });

    //Vérifiez si le client sélectionné correspond à l'ID du client actuel
    if (isSelectedClient(clientId)) {
        // Afficher le message dans la zone de discussion du client
        displayMessage(clientId, messageContent);
    }
});

//Vérifie si le client est actuellement sélectionné
function isSelectedClient(clientId) {
    const activeItems = document.querySelectorAll('.clientList li.active');
    return Array.from(activeItems).some(item => item.getAttribute('data-client-id') === clientId);
}

//Afficher les messages du client sélectionné
function displayMessage(clientId, message) {
    const clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
    if (clientMessageDiv && clientId !== 1) { 
        const newMessageDiv = document.createElement('div');
        clientMessageDiv.appendChild(newMessageDiv);
    }
}

//Ajouter des événements sur l'envoie de message...
function createClientChatWindow(clientId) {
    const discussion = document.getElementById('discussion');
    const selectedClientIdSpan = document.getElementById('selectedClientId');
    selectedClientIdSpan.textContent = clientId;

    let clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
    if (!clientMessageDiv) {
        clientMessageDiv = document.createElement('div');
        clientMessageDiv.id = `messageLog-${clientId}`;
        //Vérifier si le client n'est pas le client 1 avant d'ajouter le titre
        if (clientId !== 1) {
            clientMessageDiv.innerHTML = `<h3>Messages du client ${clientId}</h3>`;
        }
        document.body.appendChild(clientMessageDiv);
    }
    //Evénement pour les deux boutons
    document.getElementById('sendAdminMessageButton').addEventListener('click', () => {
        const clientId = document.getElementById('selectedClientId').textContent; // Récupérer l'ID du client sélectionné
        const message = document.getElementById('adminMessageInput').value.trim(); // Récupérer le message
        if (message) {
            sendMessageToClient(clientId, message); // Envoyer le message au client sélectionné
            document.getElementById('adminMessageInput').value = ''; 
        }
    });
    //Evénements pour la touche Entrée sur l'input de message
    document.getElementById('adminMessageInput').addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault(); //Empêcher le comportement par défaut du formulaire
            const clientId = document.getElementById('selectedClientId').textContent; //Récupérer l'ID du client sélectionné
            const message = document.getElementById('adminMessageInput').value.trim(); //Récupérer le message
            if (message) {
                sendMessageToClient(clientId, message); // Envoyer le message au client sélectionné
                document.getElementById('adminMessageInput').value = ''; 
            }
        }
    }); 
    //Evénements pour le changement de fichier pour l'input d'image
    document.getElementById('adminImageInput').addEventListener('change', (event) => {
        const fileInputId = `adminImageInput_${clientId}`; //Obtenez l'ID spécifique de l'input de fichier
        const file = event.target.files[0];
        if (file && clientId !== '1') { //Vérifiez si le client n'est pas l'administrateur lui-même           
        }
    });
}
//Événements pour le changement de fichier pour l'input de fichier
document.getElementById('adminFileInput').addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (file) {
        displayFileContentInMessageInput(file);
    }
});
// Événements pour le changement de fichier pour l'input d'image
document.getElementById('adminImageInput').addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (file) {
        displayFileContentInMessageInput(file);
    }
});
// Affichage du contenu du fichier sélectionné dans l'input de message
function displayFileContentInMessageInput(file) {
    const adminMessageInput = document.getElementById('adminMessageInput');
    const reader = new FileReader(); 
    // Vérifier le type de fichier
    if (file.type && file.type.includes('image')) {
        reader.onload = function (e) {
            adminMessageInput.value = `<img src="${e.target.result}" alt="Image sélectionnée">`;
        };
        reader.readAsDataURL(file);
    } else {
        reader.onload = function (e) {
            adminMessageInput.value = e.target.result;
        };
        reader.readAsText(file);
    }
}
// Événements pour le clic sur le bouton d'envoi
document.getElementById('sendAdminMessageButton').addEventListener('click', () => {
    sendMessageOrFile(clientId);
    resetMessageInput();
});
// Réinitialisation de l'input de message après l'envoi du message
function resetMessageInput() {
    const adminMessageInput = document.getElementById('adminMessageInput');
    adminMessageInput.value = '';
}
// Afficher les messages des clients
function displayClientMessage(clientId, clientName, message) {
    const clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
    if (clientMessageDiv && clientId !== 1) {
        const newMessageDiv = document.createElement('div');
        // Ajout de l'attribut data-source pour indiquer que c'est un message de client
        newMessageDiv.setAttribute('data-source', 'client');
        // Vérifier si le message est une image base64
        if (isBase64Image(message)) {
            const imgElement = document.createElement('img');
            imgElement.src = message;
            newMessageDiv.appendChild(imgElement); // Ajouter l'image directement au message div
        } else {
            // Afficher le message avec le nom du client
            newMessageDiv.innerHTML = message;
        }
        // Ajouter le nouveau message div au messageLog
        clientMessageDiv.appendChild(newMessageDiv);
    }
}
// Fonction utilitaire pour vérifier si le message est une image base64
function isBase64Image(message) {
    return message.startsWith('data:image/') || /\.(jpg|jpeg|png|gif)$/i.test(message);
}

function insertMessageBeforeInput(messageDiv, inputElement) {
    inputElement.parentNode.insertBefore(messageDiv, inputElement);
}
// Fonction pour envoyer les messages aux clients
function sendMessageToClient(clientId, message) {
    const data = {
        toClient: true,
        clientId: clientId,
        content: message
    };
    socket.send(JSON.stringify(data));
    // Afficher le message sur la page de l'administrateur
    displayAdminMessage(message, clientId);
}
// Afficher le message de l'admin
function displayAdminMessage(message, clientId) {
    const clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
    if (clientMessageDiv && clientId !== 1) {
        const newMessageDiv = document.createElement('div');
        newMessageDiv.setAttribute('data-source', 'admin');
        newMessageDiv.innerHTML = `${message}`;
        clientMessageDiv.appendChild(newMessageDiv);
    }
}
// Fonction pour envoyer un fichier à un client spécifique
function sendFileToClient(clientId, file) {
    // console.log(`Sending file to client: ${clientId}, ${file.name}`); 
    const reader = new FileReader();
    reader.onload = function(event) {
        const message = {
            fileToClient: true,
            clientId: clientId,
            fileName: file.name,
            type: file.type,
            fileContent: event.target.result
        };
        // console.log('Message to send:', message); 
        socket.send(JSON.stringify(message));
    };
    reader.readAsDataURL(file);
}

document.getElementById('fileUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData();
    const fileInput = document.getElementById('adminFileInput');
    const clientId = document.getElementById('selectedClientId').textContent; // L'ID du client sélectionné

    if (fileInput.files.length > 0 && clientId) {
        const file = fileInput.files[0];
        sendFileToClient(clientId, file);

        // Créer une div pour le message côté administrateur
        const messageDiv = document.createElement('div');
        messageDiv.setAttribute('data-source', 'admin'); // Marquer comme venant de l'administrateur
        messageDiv.classList.add('messageLog');

        //lien de téléchargement pour le fichier
        const fileLink = document.createElement('a');
        fileLink.href = URL.createObjectURL(file); // Utiliser URL.createObjectURL pour créer un lien local
        fileLink.textContent = `${file.name}`;
        fileLink.download = file.name;
        fileLink.style.display = 'block'; //Pour s'assurer que le lien prend une ligne entière

        messageDiv.appendChild(fileLink);

        const clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
        if (clientMessageDiv) {
            // Ajout du message à la fin de la liste des messages pour ce client
            clientMessageDiv.appendChild(messageDiv);
        }
        // Faire défiler jusqu'en bas pour afficher le nouveau message
        document.getElementById('messageLog').scrollTop = document.getElementById('messageLog').scrollHeight;
        // Réinitialisation de l'input de fichier
        resetMessageInput();
    }
});
// Ajouter des événements sur l'envoie de message spécifique à chaque fenêtre de discussion client
document.addEventListener('click', (event) => {
    if (event.target.classList.contains('sendAdminMessageButton')) {
        const clientId = event.target.getAttribute('data-client-id');
        const adminMessageInput = document.getElementById(`adminMessageInput_${clientId}`);
        const adminMessage = adminMessageInput.value.trim();
        if (adminMessage) {
            sendMessageToClient(clientId, adminMessage);
            adminMessageInput.value = '';
        }   
    } else if (event.target.classList.contains('sendAdminFileButton')) {
        const clientId = event.target.getAttribute('data-client-id');
        const adminFileInput = document.getElementById(`adminFileInput_${clientId}`);
        const file = adminFileInput.files[0];
        if (file) {
            sendFileToClient(clientId, file);
            adminFileInput.value = '';
        }
    }
});
// Fonction qui modifie la fonction pour afficher le contenu du fichier sélectionné
function displaySelectedFileContent(clientId, file) {
    const reader = new FileReader();
    reader.onload = function(event) {
        const fileContent = event.target.result;
        const clientMessageDiv = document.getElementById(`messageLog-${clientId}`);
        if (clientMessageDiv) {
            const newMessageDiv = document.createElement('div');
            newMessageDiv.innerHTML = `<p>${fileContent}</p>`;
            clientMessageDiv.appendChild(newMessageDiv);
        }
    };
    reader.readAsText(file);
}  
//Fonction pour envoyer une image vers le client
function sendImageToClient(clientId, file) {
    const reader = new FileReader();
    reader.onload = function(event) {
        const imageBase64 = event.target.result.split(',')[1];
        const message = {
            type: 'image',
            content: imageBase64,
            filename: file.name,
            clientId: clientId // ID du client spécifique
        };
        // Envoyer l'image au client spécifique
        socket.send(JSON.stringify(message));
    };
    reader.readAsDataURL(file);
}
// Écoute de l'événement avant le rechargement de la page
window.addEventListener('beforeunload', function(event) {
    // Récupérer l'ID de l'administrateur
    const adminId = document.getElementById('selectedClientId').textContent;
    // Envoyer une requête au serveur pour indiquer que l'administrateur se recharge
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `notifyAdminReload.php?admin_id=${adminId}`, true); // Passer l'ID de l'administrateur dans la requête GET
    xhr.send();
});