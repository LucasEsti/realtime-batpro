// Générer un identifiant unique pour le client
const clientId = localStorage.getItem('clientId') || uuid.v4();
localStorage.setItem('clientId', clientId); // Stocker l'identifiant dans le stockage local

// Connecter au serveur WebSocket en envoyant l'identifiant unique
const socket = new WebSocket('ws://localhost:8084?clientId=' + clientId);

socket.addEventListener('open', (event) => {
    console.log('WebSocket connection opened:', event);
});


// Écouter les messages du serveur WebSocket
socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    const adminMessage = data.adminMessage;
    const messageType = data.type;
    const messageContent = data.content;
    const filePath = data.filePath; // Ajouté pour les fichiers
    const fileName = data.fileName; // Ajouté pour les fichiers

    const messageLog = document.getElementById('messageLog');

    // Vérifier si le message est un fichier et provient de l'administrateur
    if (messageType === 'file' && adminMessage) {
        const fileLink = document.createElement('a');
        fileLink.href = filePath;
        fileLink.textContent = `${fileName}`;
        fileLink.download = fileName;
        fileLink.target = '_blank'; // Ouvre le lien dans un nouvel onglet

        // Créer un div pour contenir le lien de téléchargement
        const fileDiv = document.createElement('div');
        fileDiv.classList.add('message');
        fileDiv.setAttribute('data-source', 'client');
        fileDiv.appendChild(fileLink);

        // Ajouter le div du message au messageLog
        messageLog.appendChild(fileDiv);
    }

    // Vérifier le type du message pour les images
    else if (messageType === 'image') {
        console.log("Received image content:", messageContent); // Vérifiez le contenu de l'image dans la console
        // Créer un nouvel élément img
        const imgElement = document.createElement('img');
        // Définir l'attribut src avec les données de l'image base64
        imgElement.src = 'data:image/jpg;base64,' + messageContent;
        // Ajouter l'élément img au messageLog
        messageLog.appendChild(imgElement);
    }

    // Vérifier et afficher les messages de l'administrateur ou du client
    if (adminMessage) {
        if (messageContent !== undefined) { // Vérifie si messageContent n'est pas undefined
            messageLog.innerHTML += `<div data-source="client">${messageContent}</div>`;
        }
    } else {
        if (messageContent !== undefined) { // Vérifie si messageContent n'est pas undefined
            messageLog.innerHTML += `<div data-source="admin">${messageContent}</div>`;
        }
    }
});


socket.addEventListener('close', (event) => {
    console.log('WebSocket connection fermé', event);
});

socket.addEventListener('error', (event) => {
    console.error('WebSocket error', event);
});

// Fonction pour récupérer les messages sauvegardés dans le stockage local
function getSavedMessages() {
    return JSON.parse(localStorage.getItem('savedMessages')) || [];
}

// Fonction pour sauvegarder les messages dans le stockage local
function saveMessages(messages) {
    localStorage.setItem('savedMessages', JSON.stringify(messages));
}

window.addEventListener('load', () => {
    const savedMessages = getSavedMessages();
    const messageLog = document.getElementById('messageLog');
    messageLog.innerHTML = savedMessages.join(''); // Ajouter les messages sauvegardés au messageLog

    // Restaurer le nom du client s'il est sauvegardé
    userName = localStorage.getItem('userName');

    // Vérifier si la langue choisie par l'utilisateur est déjà sauvegardée
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        // Si une langue est déjà sauvegardée, passer directement à l'étape suivante du chatbot
        chatbotState = ChatbotStates.AWAITING_ANSWER;
    } else {
        // Si aucune langue n'est sauvegardée, demander au client de choisir une langue
        initialResponse();
    }
});

// États possibles du chatbot
const ChatbotStates = {
    INITIAL: 'INITIAL',
    AWAITING_LANGUAGE: 'AWAITING_LANGUAGE',
    // AWAITING_EMAIL: 'AWAITING_EMAIL', 
    AWAITING_PRO_OR_PART_NAME: 'AWAITING_PRO_OR_PART_NAME', 
    AWAITING_PROORPART_SUBMISSION : 'AWAITING_PROORPART_SUBMISSION',
    AWAITING_ANSWER: 'AWAITING_ANSWER',
    ADMIN_INTERACTION: 'ADMIN_INTERACTION'
};

// Variable pour suivre l'état actuel du chatbot
let chatbotState = ChatbotStates.INITIAL;

// Fonction pour gérer la réponse du chatbot en fonction de l'état actuel
function chatbotResponse(userMessage) {
    console.log(chatbotState);
    let response;
    switch (chatbotState) {
        case ChatbotStates.INITIAL:
            response = initialResponse(userMessage);
            break;
        case ChatbotStates.AWAITING_LANGUAGE:
            response = awaitingLanguageResponse(userMessage);
            break;
        case ChatbotStates.AWAITING_PRO_OR_PART_NAME:
            response = awaitingProOrPartNameResponse(userMessage);
            break;
        case ChatbotStates.AWAITING_PROORPART_SUBMISSION:
            response = handleProOrPartNameSubmission ();
            break;
        case ChatbotStates.AWAITING_ANSWER:
            response = awaitingAnswerResponse(userMessage);
            break;
        // Ajoutez d'autres états au besoin
        // default:
        //     response = "Je suis désolé, je ne comprends pas. Pouvez-vous reformuler ?";
    }
    return response;
}

// Fonction pour gérer la réponse initiale du chatbot
function initialResponse(userMessage) {
    chatbotState = ChatbotStates.AWAITING_LANGUAGE; // Mettre à jour l'état
    return "Veuillez choisir une langue : <br>" +
        "<button id='malagasyButton' onclick='sendMessage(\"Malagasy\", this)'>Malagasy</button>" +
        "<button id='francaisButton' onclick='sendMessage(\"Français\", this)'>Français</button>";
}

// Fonction pour gérer la réponse lors du choix de la langue
function awaitingLanguageResponse(userMessage) {
    const lowerCaseMessage = userMessage.toLowerCase();
    if (lowerCaseMessage === "français" ) {
        // Si l'utilisateur a choisi une langue, passer à l'étape de saisie de l'adresse e-mail
        chatbotState = ChatbotStates.AWAITING_PRO_OR_PART_NAME;
        localStorage.setItem('language', 'fr'); //Sauvegarde de la langue choisi
        return "Veuillez choisir : <br>" +
            "<button id='niveau1' onclick='sendMessage(\"Professionnel\", this)'>Professionnel</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Particulier\", this)'>Particulier</button>";
    } else if (lowerCaseMessage ==="malagasy") {
        chatbotState = ChatbotStates.AWAITING_PRO_OR_PART_NAME;
        localStorage.setItem('language', 'mg');
        return "Misafidiana : <br>" +
            "<button id='niveau1' onclick='sendMessage(\"Mpiasa\", this)'>Mpiasa</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Olon-tsotra\", this)'>Olon-tsotra</button>";
    }
    else {
        // Si l'utilisateur n'a pas choisi une langue valide, demander à nouveau
        return "Veuillez choisir une language: <br>" +
            "<button id='malagasyButton' onclick='sendMessage(\"Malagasy\", this)'>Malagasy</button>" +
            "<button id='francaisButton' onclick='sendMessage(\"Français\", this)'>Français</button>";
    }
}

// Fonction pour vérifier l'adresse e-mail ou le numéro de téléphone
function isValidEmailOrPhone(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^\+?[0-9\s\-]{10,}$/;
    return emailRegex.test(input) || phoneRegex.test(input);
}

//Fonction pour valider le nom (société || !société)
function validateName(name) {
    // Vérifie si le nom est vide
    if (!name || name.trim() === '') {
        return false;
    }
    // Si toutes les validations sont passées, retourne true
    return true;
}

// Fonction pour gérer la réponse lors de la saisie du nom ou nom de l'entreprise
function awaitingProOrPartNameResponse(userMessage) {
    const lowerCaseMessage = userMessage.toLowerCase();
    const language = localStorage.getItem('language');

    if (lowerCaseMessage === "professionnel" || lowerCaseMessage === "mpiasa") {
        chatbotState = ChatbotStates.AWAITING_PROORPART_SUBMISSION;
        localStorage.setItem('userType', 'pro');
        if (language === 'fr') {
            return "Veuillez saisir le nom de votre entreprise : ";
        } else {
            return "Ampidiro ny anaran'ny orinasa :";
        }
    } else if (lowerCaseMessage === "particulier" || lowerCaseMessage === "olon-tsotra") {
        chatbotState = ChatbotStates.AWAITING_PROORPART_SUBMISSION;
        localStorage.setItem('userType', 'particulier');
        if (language === 'fr') {
            return "Veuillez saisir votre nom :";
        } else {
            return "Ampidiro ny anaranao :";
        }
    } else {
        // Si l'utilisateur n'a pas choisi une option valide, demander à nouveau
        if (language === 'fr') {
            return "Veuillez choisir une option valide : <br>" +
                "<button id='niveau1' onclick='sendMessage(\"Professionnel\", this)'>Professionnel</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Particulier\", this)'>Particulier</button>";
        } else {
            return "Misafidiana safidy azo antoka: <br>" +
                "<button id='niveau1' onclick='sendMessage(\"Mpiasa\", this)'>Mpiasa</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Olon-tsotra\", this)'>Olon-tsotra</button>";
        }
    }
}

// Fonction pour gérer la réponse après la saisie du nom ou nom de l'entreprise
function handleProOrPartNameSubmission() {
    const language = localStorage.getItem('language');
    if (language === 'fr') {
        chatbotState = ChatbotStates.AWAITING_ANSWER;
        return "Vos coordonnées sont bien reçues. <br><br>" +
            "Veuillez choisir ce qui vous convient : <br>" +
            "<button id='niveau1' onclick='sendMessage(\"Je cherche un produit\", this)'>Je cherche un produit</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Demander des renseignements\", this)'>Demander des renseignements</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Demander un devis\", this)'>Demander un devis</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Détails sur un produit\", this)'>Détails sur un produit</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Service après vente\", this)'>Service après vente</button>" +
            "<button id='boutonAutre' onclick='sendMessage(\"Autres\", this)'>Autres</button>";
    } else {
        chatbotState = ChatbotStates.AWAITING_ANSWER;
        return "Voaray tsara ny mombamomba anao. <br><br>" +
            "Misafidiana amin'izay tadiavinao eto : <br>" +
            "<button id='niveau1' onclick='sendMessage(\"Hitady entana\", this)'>Hitady entana</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Hanontany fanazavana\", this)'>Hanontany fanazavana</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Hanontany vinavina\", this)'>Hanontany vinavina</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Antsipirian'ny entana\", this)'>Antsipirian'ny entana</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Tolotra vita varotra\", this)'>Tolotra vita varotra</button>" +
            "<button id='boutonAutre' onclick='sendMessage(\"Hafa\", this)'>Hafa</button>";
    }
}

// Messages prédéfinis pour les demandes de lien ou de photo
const requestLinkOrPhotoMessageFr = "Veuillez envoyer le lien ou la photo du produit";
const requestLinkOrPhotoMessageMg = "Mba alefaso ny rohy na sarin'ilay entana azafady";
const requestAutreAideFr = "Bienvenue au service commercial de BATPRO. Comment pouvons-nous vous aider ?";
const requestAutreAideMg = "Tongasoa eto amin'ny sampana varotry ny BATPRO. Inona no azo hanampiana anao ?";

let awaitingDevis = false;
let awaitingLinkOrPhoto = false;
let awaitingPayement = false;
let lieuLivraison = false;
let autreInfo = false;
let awaitingYesNoResponse = false;
let detailsProd = false;
let detailYesNo = false;

function awaitingAnswerResponse(userMessage) {
    // Convertir le message en minuscules pour une correspondance insensible à la casse
    const lowerCaseMessage = userMessage.toLowerCase();
    // Pour récupérer la langue choisie par le client
    const language = localStorage.getItem('language');
    let response = '';
    let responseSent = false;

    // Vérifier si une réponse n'a pas déjà été envoyée
    if (!responseSent) {
        // Si le chatbot attend un devis
        if (awaitingDevis) {
            response = (language === 'fr') ? "En combien de quantité ?" : "Firy isa ?";
            awaitingDevis = false;
            responseSent = true;
        } else if (awaitingLinkOrPhoto) {
            response = (language === 'fr') ? "Afin de vous offrir un suivi personnalisé, veuillez fournir votre contact (WhatsApp, email ou téléphone). Un responsable vous contactera dans les 15 minutes" : 
                "Mba ahafahanay manara-maso anao manokana dia ataovy eto ny laharana finday na adresy mailaka anao. Hisy tompon'andraikitra hiantso anao afaka 15 minitra";
            awaitingLinkOrPhoto = false;
            responseSent = true;
        } else if (awaitingPayement) {
            response = (language === 'fr') ? "Quel est votre mode de paiement? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Espèce\", this)'>Espèce</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Chèque\", this)'>Chèque</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Virement\", this)'>Virement</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Autres\", this)'>Autres</button>"
                : "Inona no fomba fandoavanao vola? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Vola\", this)'>Vola</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Seka\", this)'>Taratasim-bola</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Famindrana\", this)'>Famindrana</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Hafa\", this)'>Hafa</button>";
            awaitingPayement = false;
            responseSent = true;
        } else if (lieuLivraison) {
            response = (language === 'fr') ? "Veuillez indiquer la date et le lieu de livraison souhaités" :
                "Daty sy toerana hanaterana azy azafady";
            lieuLivraison = false;
            responseSent = true;
        } else if (autreInfo) {
            response = (language === 'fr') ? "Souhaitez-vous rajouter d'autres informations ? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Oui\", this)'>Oui</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Non\", this)'>Non</button>"
                : "Mbola mila fanazavana hafa ve ianao? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Eny\", this)'>Eny</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Tsia\", this)'>Tsia</button>";
            autreInfo = false;
            responseSent = true;
        } else if (awaitingYesNoResponse) {
            if (lowerCaseMessage === "oui" || lowerCaseMessage === "eny") {
                response = (language === 'fr') ? "En quoi d'autre puis-je vous aider ?" : "Inona no mety mbola azo hanampiana anao?";
            } else if (lowerCaseMessage === "non" || lowerCaseMessage === "tsia") {
                response = (language === 'fr') ? "Notre service commercial vous enverra un devis par e-mail sous peu. Merci de nous avoir contacté. A bientôt !"
                    : "Alefan'ny sampana ara-barotra aminao amin'ny alalan'ny adresy mailaka ny vinavina. Misaotra anao nifandray taminay. Amin'ny manaraka indray !";
            }
            awaitingYesNoResponse = false;
            responseSent = true;
        } else if (detailsProd) {
            response = (language === 'fr') ? "Avez-vous eu la fiche technique correspondante ? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Oui\", this)'>Oui</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Non\", this)'>Non</button>"
                : "Anananao ve ny taratasy ara-teknika mifandray amin'io ? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Eny\", this)'>Eny</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Tsia\", this)'>Tsia</button>";
            detailsProd = false;
            responseSent = true;
        } else if (detailYesNo) {
            if (lowerCaseMessage === "oui" || lowerCaseMessage === "eny") {
                responseSent = true;
            } else if (lowerCaseMessage === "non" || lowerCaseMessage === "tsia") {
                response = (language === 'fr') ? "Quelle informations voudriez-vous avoir ?"
                    : "Inona ny fanampim-panazavana mbola tinao ho fantatra ?";
            }
            detailYesNo = false;
            responseSent = true;
        }

        //1
        else if (lowerCaseMessage === "je cherche un produit" || lowerCaseMessage === "hitady entana") {
            response = (language === 'fr') ? "Quels produits?<br>" +
                "<button id='outillageBouton' onclick='sendMessage(\"Outillage\", this)'>Outillage</button>" +
                "<button id='métallurgieBouton' onclick='sendMessage(\"Métallurgie\", this)'>Métallurgie</button>" +
                "<button id='peintureBouton' onclick='sendMessage(\"Peinture & étanchéité\", this)'>Peinture & étanchéité</button>" +
                "<button id='sécuritéBouton' onclick='sendMessage(\"Sécurité incendie\", this)'>Sécurité incendie</button>" +
                "<button id='travauxBouton' onclick='sendMessage(\"Travaux publics & génie civil\", this)'>Travaux publics & génie civil</button>" +
                "<button id='équipementBouton' onclick='sendMessage(\"Équipement électrique & soudage\", this)'>Équipement électrique & soudage </button>"
                : "Ireto avy ny entana afaka hanampiana anao :<br>" +
                "<button id='outillageBouton' onclick='sendMessage(\"Fitaovana\", this)'>Fitaovana</button>" +
                "<button id='métallurgieBouton' onclick='sendMessage(\"Metaly\", this)'>Metaly</button>" +
                "<button id='peintureBouton' onclick='sendMessage(\"Peinture & étanchéité\", this)'>Loko sy étanchéité</button>" +
                "<button id='sécuritéBouton' onclick='sendMessage(\"Fiarovana amin'ny afo\", this)'>Fiarovana amin'ny afo</button>" +
                "<button id='travauxBouton' onclick='sendMessage(\"Asa vaventy & injeniera sivily\", this)'>Asa vaventy & injeniera sivily</button>" +
                "<button id='équipementBouton' onclick='sendMessage(\"Fitaovana elektrika sy soudage\", this)'>Fitaovana elektrika sy soudage </button>";
            responseSent = true;
        } else if (lowerCaseMessage === "outillage" || lowerCaseMessage === "fitaovana") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "métallurgie" || lowerCaseMessage === "metaly") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "peinture & étanchéité" || lowerCaseMessage === "loko sy étanchéité") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "sécurité incendie" || lowerCaseMessage === "fiarovana amin'ny afo") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "travaux publics & génie civil" || lowerCaseMessage === "asa vaventy & injeniera sivily") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "équipement électrique & soudage" || lowerCaseMessage === "fitaovana elektrika sy soudage") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "autre" || lowerCaseMessage === "hafa") {
            response = (language === 'fr') ? requestAutreAideFr : requestAutreAideMg;
            awaitingYesNoResponse = true;
            responseSent = true;
        }

        //2 
        else if (lowerCaseMessage === "demander des renseignements" || lowerCaseMessage === "hanontany fanazavana") {
            response = (language === 'fr') ? "Quels renseignements?<br>"+
                "<button id='savBouton' onclick='sendMessage(\"SAV\", this)'>SAV</button>" +
                "<button id='partenariatBouton' onclick='sendMessage(\"Partenariat\", this)'>Partenariat</button>" +
                "<button id='fournisseursBouton' onclick='sendMessage(\"Fournisseurs\", this)'>Fournisseurs</button>" +
                "<button id='recrutementBouton' onclick='sendMessage(\"Recrutement\", this)'>Recrutement</button>" +
                "<button id='contacterBouton' onclick='sendMessage(\"Contacter magasins\", this)'>Contacter magasins</button>" +
                "<button id='boutonAutre' onclick='sendMessage(\"Autres\", this)'>Autres </button>" 
            : "Inona no hilanao fanazavana?<br>"+
                "<button id='savBouton' onclick='sendMessage(\"SAV\", this)'>SAV</button>" +
                "<button id='partenariatBouton' onclick='sendMessage(\"Fiaraha-miasa\", this)'>Fiaraha-miasa</button>" +
                "<button id='fournisseursBouton' onclick='sendMessage(\"Mpamatsy\", this)'>Mpamatsy</button>" +
                "<button id='recrutementBouton' onclick='sendMessage(\"Hitady asa\", this)'>Hitady asa</button>" +
                "<button id='contacterBouton' onclick='sendMessage(\"Fifandraisana\", this)'>Fifandraisana</button>" +
                "<button id='boutonAutre' onclick='sendMessage(\"Hafa\", this)'>Hafa </button>" 
        } else if (lowerCaseMessage === "sav" || lowerCaseMessage === "sav") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la réponse a été envoyée
        }  else if (lowerCaseMessage === "partenariat" || lowerCaseMessage === "fiaraha-miasa") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la réponse a été envoyée
        } else if (lowerCaseMessage === "fournisseurs" || lowerCaseMessage === "mpamatsy") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la réponse a été envoyée
        } else if (lowerCaseMessage === "recrutement" || lowerCaseMessage === "hitady asa") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la réponse a été envoyée
        } else if (lowerCaseMessage === "contacter magasins" || lowerCaseMessage === "fifandraisana") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la réponse a été envoyée
        } else if (lowerCaseMessage === "autres" || lowerCaseMessage === "hafa") {
            response = requestAutreAide;
            responseSent = true; // Marquer que la réponse a été envoyée
        }
    }

    //3
    if (lowerCaseMessage === "demander un devis" || lowerCaseMessage === "hanontany vinavina") {
        response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
        awaitingDevis = true;
        awaitingPayement = true;
        responseSent = true; // Marquer que la réponse a été envoyée
        lieuLivraison = true;
        autreInfo = true;
        awaitingYesNoResponse = true;
    }

    //4
    if (lowerCaseMessage === "détails sur un produit" || lowerCaseMessage === "antsipirian'ny entana") {
        response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
        detailsProd = true;
        detailYesNo= true;
        responseSent = true; // Marquer que la réponse a été envoyée
    }

    //5 
    if (lowerCaseMessage === "service après vente" || lowerCaseMessage === "tolotra vita varotra") {
        response = (language === 'fr') ? "" +
            "<button id='savBouton' onclick='sendMessage(\"Réclamation\", this)'>Réclamation</button>" +
            "<button id='partenariatBouton' onclick='sendMessage(\"Réparer un produit\", this)'>Réparer un produit</button>" +
            "<button id='fournisseursBouton' onclick='sendMessage(\"Chercher des pièces détachées\", this)'>Chercher des pièces détachées</button>" +
            "<button id='recrutementBouton' onclick='sendMessage(\"Garantie\", this)'>Garantie</button>" +
            "<button id='contacterBouton' onclick='sendMessage(\"Récupérer un produit\", this)'>Récupérer un produit</button>" +
            "<button id='boutonAutre' onclick='sendMessage(\"Autres\", this)'>Autres</button>" 
            : "" +
            "<button id='savBouton' onclick='sendMessage(\"Fanitsiana\", this)'>Fanitsiana</button><br>" +
            "<button id='partenariatBouton' onclick='sendMessage(\"Hanamboatra entana\", this)'>Hanamboatra entana</button>" +
            "<button id='fournisseursBouton' onclick='sendMessage(\"Hitady piesy antsinjarany\", this)'>Hitady piesy antsinjarany</button>" +
            "<button id='recrutementBouton' onclick='sendMessage(\"Antoka\", this)'>Antoka</button>" +
            "<button id='contacterBouton' onclick='sendMessage(\"Haka entana\", this)'>Haka entana</button>" +
            "<button id='boutonAutre' onclick='sendMessage(\"Hafa\", this)'>Hafa</button>" ;
        responseSent = true;
    }

    //6
    if (lowerCaseMessage === "autres" || lowerCaseMessage === "hafa") {
        response = (language === 'fr') ? requestAutreAideFr : requestAutreAideMg;
        responseSent = true;
    }
    // Afficher la réponse
    return response;
}


// Fonction pour envoyer le message à l'admin
function sendMessage(message, button) {
    const userName = localStorage.getItem('userName');
    sendMessageToAdmin(message, userName);
    button.disabled = true; // Désactiver le bouton après avoir envoyé le message
}

// Fonction pour afficher également le message du client dans la zone de discussion
function sendMessageToAdmin(userMessage, name) {
    const data = {
        toAdmin: true,
        clientName: name,
        content: userMessage // Envoyer le message de l'utilisateur à l'administrateur
    };
    // Envoyer le message du client à l'admin
    socket.send(JSON.stringify(data));
    // Obtenir la réponse du chatbot en fonction du message de l'utilisateur
    const response = chatbotResponse(userMessage);
    // Afficher le message de l'utilisateur dans la zone de discussion
    const messageLog = document.getElementById('messageLog');
    messageLog.innerHTML += `<div data-source="admin">${userMessage}</div>`;
    // Mettre à jour le nom du client sur le titre h1
    document.getElementById('clientName').innerText = name;
    // Si une réponse du chatbot est disponible, l'ajouter au journal des messages
    if (response) {
        // Créer un objet de données pour la réponse du chatbot
        const responseData = {
            adminMessage: true,
            clientId: clientId,
            content: response // Envoyer la réponse du chatbot à l'administrateur
        };
        // Envoyer la réponse du chatbot au serveur
        socket.send(JSON.stringify(responseData));
    }
}

// Modifier la fonction pour envoyer un message pour sauvegarder les messages
document.getElementById('sendMessageButton').addEventListener('click', () => {
    const userMessage = document.getElementById('messageInput').value.trim();
    if (userMessage) {
        sendMessageToAdmin(userMessage, userName);
        document.getElementById('messageInput').value = '';
    }
});

// Envoyer une image avec l'ID du client
function sendImage() {
    const file = document.getElementById('imageInput').files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const imageBase64 = event.target.result.split(',')[1];
            const message = {
                type: 'image',
                content: imageBase64,
                extension: file.name.split('.').pop(),
                filename: file.name,
                clientId: getClientId() // Ajouter l'ID du client
            };
            socket.send(JSON.stringify(message));
        };
        reader.readAsDataURL(file);
    }
}
// Récupère l'identifiant du client à partir du DOM.
function getClientId() {
    // Vous pouvez récupérer cette valeur à partir du DOM
    const clientIdElement = document.getElementById('clientID');
    if (clientIdElement) {
        return clientIdElement.textContent; // Renvoie le texte contenu dans l'élément
    } else {
        return ''; // Renvoie une chaîne vide si l'élément n'est pas trouvé
    }
}

// Événements pour le changement de fichier pour l'input d'image
document.getElementById('ImageInput').addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (file) {
        displayContent(file);
    }
});

// Affichage du contenu du fichier sélectionné dans l'input de message
function displayContent(file) {
    const messageInput = document.getElementById('messageInput');
    const reader = new FileReader();
    if (file.type.includes('image')) {
        reader.onload = function (e) {
            messageInput.value = `<img src="${e.target.result}" alt="Image sélectionnée">`;
        };
        reader.readAsDataURL(file);
    }
}

// Réinitialisation de l'input de message après l'envoi du message
function resetMessageInput() {
    const messageInput = document.getElementById('messageInput');
    messageInput.value = '';
}

// Définition d'une fonction pour envoyer le message à partir de l'entrée de message
function sendMessageFromInput() {
    const userMessage = messageInput.value.trim();
    if (userMessage) {
        sendMessageToAdmin(userMessage, userName);
        messageInput.value = '';
    }
}

const messageInput = document.getElementById('messageInput');
const sendMessageButton = document.getElementById('sendMessageButton');

// Ajoutez un écouteur d'événements pour le clic sur le bouton d'envoi de messages
sendMessageButton.addEventListener('click', () => {
    sendMessageFromInput();
});

// Ajoutez un écouteur d'événements pour la pression de la touche Entrée dans l'entrée de message
messageInput.addEventListener('keypress', (event) => {
    // Vérifiez si la touche pressée est la touche Entrée (keyCode 13)
    if (event.keyCode === 13) {
        sendMessageFromInput();
    }
});