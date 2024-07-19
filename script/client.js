// G�n�rer un identifiant unique pour le client
const clientId = localStorage.getItem('clientId') || uuid.v4();
localStorage.setItem('clientId', clientId); // Stocker l'identifiant dans le stockage local

// Connecter au serveur WebSocket en envoyant l'identifiant unique
const socket = new WebSocket('wss://somalaval-ai.xnr.afb.mybluehost.me/wp-content/plugins/Message/server?clientId=' + clientId);

socket.addEventListener('open', (event) => {
    console.log('WebSocket connection opened:', event);
});


// �couter les messages du serveur WebSocket
socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);
    const adminMessage = data.adminMessage;
    const messageType = data.type;
    const messageContent = data.content;
    const filePath = data.filePath; // Ajout� pour les fichiers
    const fileName = data.fileName; // Ajout� pour les fichiers

    const messageLog = document.getElementById('messageLog');

    // V�rifier si le message est un fichier et provient de l'administrateur
    if (messageType === 'file' && adminMessage) {
        const fileLink = document.createElement('a');
        fileLink.href = filePath;
        fileLink.textContent = `${fileName}`;
        fileLink.download = fileName;
        fileLink.target = '_blank'; // Ouvre le lien dans un nouvel onglet

        // Cr�er un div pour contenir le lien de t�l�chargement
        const fileDiv = document.createElement('div');
        fileDiv.classList.add('message');
        fileDiv.setAttribute('data-source', 'client');
        fileDiv.appendChild(fileLink);

        // Ajouter le div du message au messageLog
        messageLog.appendChild(fileDiv);
    }

    // V�rifier le type du message pour les images
    else if (messageType === 'image') {
        console.log("Received image content:", messageContent); // V�rifiez le contenu de l'image dans la console
        // Cr�er un nouvel �l�ment img
        const imgElement = document.createElement('img');
        // D�finir l'attribut src avec les donn�es de l'image base64
        imgElement.src = 'data:image/jpg;base64,' + messageContent;
        // Ajouter l'�l�ment img au messageLog
        messageLog.appendChild(imgElement);
    }

    // V�rifier et afficher les messages de l'administrateur ou du client
    if (adminMessage) {
        if (messageContent !== undefined) { // V�rifie si messageContent n'est pas undefined
            messageLog.innerHTML += `<div data-source="client">${messageContent}</div>`;
        }
    } else {
        if (messageContent !== undefined) { // V�rifie si messageContent n'est pas undefined
            messageLog.innerHTML += `<div data-source="admin">${messageContent}</div>`;
        }
    }
});


socket.addEventListener('close', (event) => {
    console.log('WebSocket connection ferm�', event);
});

socket.addEventListener('error', (event) => {
    console.error('WebSocket error', event);
});

// Fonction pour r�cup�rer les messages sauvegard�s dans le stockage local
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
    messageLog.innerHTML = savedMessages.join(''); // Ajouter les messages sauvegard�s au messageLog

    // Restaurer le nom du client s'il est sauvegard�
    userName = localStorage.getItem('userName');

    // V�rifier si la langue choisie par l'utilisateur est d�j� sauvegard�e
    const savedLanguage = localStorage.getItem('language');
    if (savedLanguage) {
        // Si une langue est d�j� sauvegard�e, passer directement � l'�tape suivante du chatbot
        chatbotState = ChatbotStates.AWAITING_ANSWER;
    } else {
        // Si aucune langue n'est sauvegard�e, demander au client de choisir une langue
        initialResponse();
    }
});

// �tats possibles du chatbot
const ChatbotStates = {
    INITIAL: 'INITIAL',
    AWAITING_LANGUAGE: 'AWAITING_LANGUAGE',
    // AWAITING_EMAIL: 'AWAITING_EMAIL', 
    AWAITING_PRO_OR_PART_NAME: 'AWAITING_PRO_OR_PART_NAME', 
    AWAITING_PROORPART_SUBMISSION : 'AWAITING_PROORPART_SUBMISSION',
    AWAITING_ANSWER: 'AWAITING_ANSWER',
    ADMIN_INTERACTION: 'ADMIN_INTERACTION'
};

// Variable pour suivre l'�tat actuel du chatbot
let chatbotState = ChatbotStates.INITIAL;

// Fonction pour g�rer la r�ponse du chatbot en fonction de l'�tat actuel
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
        // Ajoutez d'autres �tats au besoin
        // default:
        //     response = "Je suis d�sol�, je ne comprends pas. Pouvez-vous reformuler ?";
    }
    return response;
}

// Fonction pour g�rer la r�ponse initiale du chatbot
function initialResponse(userMessage) {
    chatbotState = ChatbotStates.AWAITING_LANGUAGE; // Mettre � jour l'�tat
    return "Veuillez choisir une langue : <br>" +
        "<button id='malagasyButton' onclick='sendMessage(\"Malagasy\", this)'>Malagasy</button>" +
        "<button id='francaisButton' onclick='sendMessage(\"Fran�ais\", this)'>Fran�ais</button>";
}

// Fonction pour g�rer la r�ponse lors du choix de la langue
function awaitingLanguageResponse(userMessage) {
    const lowerCaseMessage = userMessage.toLowerCase();
    if (lowerCaseMessage === "fran�ais" ) {
        // Si l'utilisateur a choisi une langue, passer � l'�tape de saisie de l'adresse e-mail
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
        // Si l'utilisateur n'a pas choisi une langue valide, demander � nouveau
        return "Veuillez choisir une language: <br>" +
            "<button id='malagasyButton' onclick='sendMessage(\"Malagasy\", this)'>Malagasy</button>" +
            "<button id='francaisButton' onclick='sendMessage(\"Fran�ais\", this)'>Fran�ais</button>";
    }
}

// Fonction pour v�rifier l'adresse e-mail ou le num�ro de t�l�phone
function isValidEmailOrPhone(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^\+?[0-9\s\-]{10,}$/;
    return emailRegex.test(input) || phoneRegex.test(input);
}

//Fonction pour valider le nom (soci�t� || !soci�t�)
function validateName(name) {
    // V�rifie si le nom est vide
    if (!name || name.trim() === '') {
        return false;
    }
    // Si toutes les validations sont pass�es, retourne true
    return true;
}

// Fonction pour g�rer la r�ponse lors de la saisie du nom ou nom de l'entreprise
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
        // Si l'utilisateur n'a pas choisi une option valide, demander � nouveau
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

// Fonction pour g�rer la r�ponse apr�s la saisie du nom ou nom de l'entreprise
function handleProOrPartNameSubmission() {
    const language = localStorage.getItem('language');
    if (language === 'fr') {
        chatbotState = ChatbotStates.AWAITING_ANSWER;
        return "Vos coordonn�es sont bien re�ues. <br><br>" +
            "Veuillez choisir ce qui vous convient : <br>" +
            "<button id='niveau1' onclick='sendMessage(\"Je cherche un produit\", this)'>Je cherche un produit</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Demander des renseignements\", this)'>Demander des renseignements</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Demander un devis\", this)'>Demander un devis</button>" +
            "<button id='niveau1' onclick='sendMessage(\"D�tails sur un produit\", this)'>D�tails sur un produit</button>" +
            "<button id='niveau1' onclick='sendMessage(\"Service apr�s vente\", this)'>Service apr�s vente</button>" +
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

// Messages pr�d�finis pour les demandes de lien ou de photo
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
    // Convertir le message en minuscules pour une correspondance insensible � la casse
    const lowerCaseMessage = userMessage.toLowerCase();
    // Pour r�cup�rer la langue choisie par le client
    const language = localStorage.getItem('language');
    let response = '';
    let responseSent = false;

    // V�rifier si une r�ponse n'a pas d�j� �t� envoy�e
    if (!responseSent) {
        // Si le chatbot attend un devis
        if (awaitingDevis) {
            response = (language === 'fr') ? "En combien de quantit� ?" : "Firy isa ?";
            awaitingDevis = false;
            responseSent = true;
        } else if (awaitingLinkOrPhoto) {
            response = (language === 'fr') ? "Afin de vous offrir un suivi personnalis�, veuillez fournir votre contact (WhatsApp, email ou t�l�phone). Un responsable vous contactera dans les 15 minutes" : 
                "Mba ahafahanay manara-maso anao manokana dia ataovy eto ny laharana finday na adresy mailaka anao. Hisy tompon'andraikitra hiantso anao afaka 15 minitra";
            awaitingLinkOrPhoto = false;
            responseSent = true;
        } else if (awaitingPayement) {
            response = (language === 'fr') ? "Quel est votre mode de paiement? <br><br>" +
                "<button id='niveau1' onclick='sendMessage(\"Esp�ce\", this)'>Esp�ce</button>" +
                "<button id='niveau1' onclick='sendMessage(\"Ch�que\", this)'>Ch�que</button>" +
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
            response = (language === 'fr') ? "Veuillez indiquer la date et le lieu de livraison souhait�s" :
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
                response = (language === 'fr') ? "Notre service commercial vous enverra un devis par e-mail sous peu. Merci de nous avoir contact�. A bient�t !"
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
                "<button id='m�tallurgieBouton' onclick='sendMessage(\"M�tallurgie\", this)'>M�tallurgie</button>" +
                "<button id='peintureBouton' onclick='sendMessage(\"Peinture & �tanch�it�\", this)'>Peinture & �tanch�it�</button>" +
                "<button id='s�curit�Bouton' onclick='sendMessage(\"S�curit� incendie\", this)'>S�curit� incendie</button>" +
                "<button id='travauxBouton' onclick='sendMessage(\"Travaux publics & g�nie civil\", this)'>Travaux publics & g�nie civil</button>" +
                "<button id='�quipementBouton' onclick='sendMessage(\"�quipement �lectrique & soudage\", this)'>�quipement �lectrique & soudage </button>"
                : "Ireto avy ny entana afaka hanampiana anao :<br>" +
                "<button id='outillageBouton' onclick='sendMessage(\"Fitaovana\", this)'>Fitaovana</button>" +
                "<button id='m�tallurgieBouton' onclick='sendMessage(\"Metaly\", this)'>Metaly</button>" +
                "<button id='peintureBouton' onclick='sendMessage(\"Peinture & �tanch�it�\", this)'>Loko sy �tanch�it�</button>" +
                "<button id='s�curit�Bouton' onclick='sendMessage(\"Fiarovana amin'ny afo\", this)'>Fiarovana amin'ny afo</button>" +
                "<button id='travauxBouton' onclick='sendMessage(\"Asa vaventy & injeniera sivily\", this)'>Asa vaventy & injeniera sivily</button>" +
                "<button id='�quipementBouton' onclick='sendMessage(\"Fitaovana elektrika sy soudage\", this)'>Fitaovana elektrika sy soudage </button>";
            responseSent = true;
        } else if (lowerCaseMessage === "outillage" || lowerCaseMessage === "fitaovana") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "m�tallurgie" || lowerCaseMessage === "metaly") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "peinture & �tanch�it�" || lowerCaseMessage === "loko sy �tanch�it�") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "s�curit� incendie" || lowerCaseMessage === "fiarovana amin'ny afo") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "travaux publics & g�nie civil" || lowerCaseMessage === "asa vaventy & injeniera sivily") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true;
        } else if (lowerCaseMessage === "�quipement �lectrique & soudage" || lowerCaseMessage === "fitaovana elektrika sy soudage") {
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
            responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        }  else if (lowerCaseMessage === "partenariat" || lowerCaseMessage === "fiaraha-miasa") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        } else if (lowerCaseMessage === "fournisseurs" || lowerCaseMessage === "mpamatsy") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        } else if (lowerCaseMessage === "recrutement" || lowerCaseMessage === "hitady asa") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        } else if (lowerCaseMessage === "contacter magasins" || lowerCaseMessage === "fifandraisana") {
            response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
            awaitingLinkOrPhoto = true;
            responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        } else if (lowerCaseMessage === "autres" || lowerCaseMessage === "hafa") {
            response = requestAutreAide;
            responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        }
    }

    //3
    if (lowerCaseMessage === "demander un devis" || lowerCaseMessage === "hanontany vinavina") {
        response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
        awaitingDevis = true;
        awaitingPayement = true;
        responseSent = true; // Marquer que la r�ponse a �t� envoy�e
        lieuLivraison = true;
        autreInfo = true;
        awaitingYesNoResponse = true;
    }

    //4
    if (lowerCaseMessage === "d�tails sur un produit" || lowerCaseMessage === "antsipirian'ny entana") {
        response = (language === 'fr') ? requestLinkOrPhotoMessageFr : requestLinkOrPhotoMessageMg;
        detailsProd = true;
        detailYesNo= true;
        responseSent = true; // Marquer que la r�ponse a �t� envoy�e
    }

    //5 
    if (lowerCaseMessage === "service apr�s vente" || lowerCaseMessage === "tolotra vita varotra") {
        response = (language === 'fr') ? "" +
            "<button id='savBouton' onclick='sendMessage(\"R�clamation\", this)'>R�clamation</button>" +
            "<button id='partenariatBouton' onclick='sendMessage(\"R�parer un produit\", this)'>R�parer un produit</button>" +
            "<button id='fournisseursBouton' onclick='sendMessage(\"Chercher des pi�ces d�tach�es\", this)'>Chercher des pi�ces d�tach�es</button>" +
            "<button id='recrutementBouton' onclick='sendMessage(\"Garantie\", this)'>Garantie</button>" +
            "<button id='contacterBouton' onclick='sendMessage(\"R�cup�rer un produit\", this)'>R�cup�rer un produit</button>" +
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
    // Afficher la r�ponse
    return response;
}


// Fonction pour envoyer le message � l'admin
function sendMessage(message, button) {
    const userName = localStorage.getItem('userName');
    sendMessageToAdmin(message, userName);
    button.disabled = true; // D�sactiver le bouton apr�s avoir envoy� le message
}

// Fonction pour afficher �galement le message du client dans la zone de discussion
function sendMessageToAdmin(userMessage, name) {
    const data = {
        toAdmin: true,
        clientName: name,
        content: userMessage // Envoyer le message de l'utilisateur � l'administrateur
    };
    // Envoyer le message du client � l'admin
    socket.send(JSON.stringify(data));
    // Obtenir la r�ponse du chatbot en fonction du message de l'utilisateur
    const response = chatbotResponse(userMessage);
    // Afficher le message de l'utilisateur dans la zone de discussion
    const messageLog = document.getElementById('messageLog');
    messageLog.innerHTML += `<div data-source="admin">${userMessage}</div>`;
    // Mettre � jour le nom du client sur le titre h1
    document.getElementById('clientName').innerText = name;
    // Si une r�ponse du chatbot est disponible, l'ajouter au journal des messages
    if (response) {
        // Cr�er un objet de donn�es pour la r�ponse du chatbot
        const responseData = {
            adminMessage: true,
            clientId: clientId,
            content: response // Envoyer la r�ponse du chatbot � l'administrateur
        };
        // Envoyer la r�ponse du chatbot au serveur
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
// R�cup�re l'identifiant du client � partir du DOM.
function getClientId() {
    // Vous pouvez r�cup�rer cette valeur � partir du DOM
    const clientIdElement = document.getElementById('clientID');
    if (clientIdElement) {
        return clientIdElement.textContent; // Renvoie le texte contenu dans l'�l�ment
    } else {
        return ''; // Renvoie une cha�ne vide si l'�l�ment n'est pas trouv�
    }
}

// �v�nements pour le changement de fichier pour l'input d'image
document.getElementById('ImageInput').addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (file) {
        displayContent(file);
    }
});

// Affichage du contenu du fichier s�lectionn� dans l'input de message
function displayContent(file) {
    const messageInput = document.getElementById('messageInput');
    const reader = new FileReader();
    if (file.type.includes('image')) {
        reader.onload = function (e) {
            messageInput.value = `<img src="${e.target.result}" alt="Image s�lectionn�e">`;
        };
        reader.readAsDataURL(file);
    }
}

// R�initialisation de l'input de message apr�s l'envoi du message
function resetMessageInput() {
    const messageInput = document.getElementById('messageInput');
    messageInput.value = '';
}

// D�finition d'une fonction pour envoyer le message � partir de l'entr�e de message
function sendMessageFromInput() {
    const userMessage = messageInput.value.trim();
    if (userMessage) {
        sendMessageToAdmin(userMessage, userName);
        messageInput.value = '';
    }
}

const messageInput = document.getElementById('messageInput');
const sendMessageButton = document.getElementById('sendMessageButton');

// Ajoutez un �couteur d'�v�nements pour le clic sur le bouton d'envoi de messages
sendMessageButton.addEventListener('click', () => {
    sendMessageFromInput();
});

// Ajoutez un �couteur d'�v�nements pour la pression de la touche Entr�e dans l'entr�e de message
messageInput.addEventListener('keypress', (event) => {
    // V�rifiez si la touche press�e est la touche Entr�e (keyCode 13)
    if (event.keyCode === 13) {
        sendMessageFromInput();
    }
});