<?php
/*
Plugin Name: ChatLive
Description: Messagerie instantanée
Version: 1.0
Author: Stagiaire
*/

// Fonction qui initialise le plugin
function initialize_plugin() {
    // Vérification de la disponibilité de la fonction add_shortcode
    if (function_exists('add_shortcode')) {
        // Ajout du shortcode pour afficher le plugin WebSocket
        add_shortcode('afficher_plugin_websocket', 'afficher_plugin_websocket');
    } else {
        // En cas de problème, affichage d'un message d'erreur
        error_log('La fonction add_shortcode n\'est pas disponible.');
    }
}
// Appel de la fonction d'initialisation lorsque WordPress est prêt
add_action('init', 'initialize_plugin');

// Fonction pour appeller le server
function afficher_plugin_websocket() {
    // Début du tampon de sortie
    ob_start(); 

    // Inclure le fichier principal du plugin
    include_once dirname(__FILE__) . '/server/server.php';

    // Fin du tampon de sortie et retourne son contenu
    return ob_get_clean(); 
}
//shortcode pour le server.php WebSocket
add_shortcode('afficher_plugin_websocket', 'afficher_plugin_websocket');

//Fonction pour le client shortcode d'affichage
function chat_shortcode() {
    ob_start();
    include dirname(__FILE__) . '/client.php';
    return ob_get_clean();
}
//shortcode pour le client
add_shortcode('chat', 'chat_shortcode');

// Fonction pour générer le code JavaScript du client WebSocket
function generate_websocket_client_script() {
    ob_start(); ?>
    <script>      
    // Inclure le fichier client.js
    <?php include_once dirname(__FILE__) . '/script/client.js'; ?>
    </script>
    <?php
    return ob_get_clean();
}
// Ajout d' un shortcode pour inclure le code JavaScript du client WebSocket
add_shortcode('websocket_client_script', 'generate_websocket_client_script');

//Fonction pour appeller et afficher Chat.php(Generateur de message admin et client)
function start_websocket_server() {
    ob_start();
    require __DIR__ . '/vendor/autoload.php';
    require_once dirname(__FILE__) . '/src/Chat.php'; //Chemin correct 

    $server = new \MyApp\MessageServer();

    ob_end_clean(); // Nettoyer le tampon de sortie
}
//shortcode du Chat.php
add_shortcode('start_websocket', 'start_websocket_server');

//Fonction pour admin
function admin_shortcode() {
    ob_start();
    include dirname(__FILE__) . '/admin.php';
    return ob_get_clean();
}
//shortcode pour admin.php
add_shortcode('admin', 'admin_shortcode');

//fonction pour l'admin.js
function generate_websocket_admin_script() {
    ob_start(); ?>
    <script>
        //inclure admin.js
        <?php include_once dirname(__FILE__) . '/script/admin.js'?>
    </script>
    <?php
    return ob_get_clean();
}
//shortcode pour admin.js
add_shortcode('websocket_admin_script', 'generate_websocket_admin_script');

// Fonction pour générer le code HTML du lien vers le fichier CSS d'administration
function generate_admin_css_link() {
    // Chemin vers votre fichier CSS
    $css_file = plugin_dir_url(__FILE__) . './style/style_admin.css';
    
    // Balise HTML pour inclure le fichier CSS
    return '<link rel="stylesheet" type="text/css" href="' . $css_file . '">';
}
// Ajout d'un shortcode pour inclure le CSS d'administration
add_shortcode('admin_css_link', 'generate_admin_css_link');

// Fonction pour générer le code HTML du lien vers le fichier CSS du client
function generate_client_css_link() {
    // Chemin vers votre fichier CSS du client
    $css_file = plugin_dir_url(__FILE__) . 'http://localhost/BatP/wp-content/plugins/Message/style/style_client.css';
    
    // Balise HTML pour inclure le fichier CSS
    return '<link rel="stylesheet" type="text/css" href="' . $css_file . '">';
}
// Ajout d'un shortcode pour inclure le CSS du client
add_shortcode('client_css_link', 'generate_client_css_link');


?>