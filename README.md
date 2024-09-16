# Installer composer
# Installer Ratchet
# Demarrer le server :
  . server->server.php : bash("Terminal : php server.php")


Steps a suiver pour l'installation de websocket php

preparation de l'environement

Steps 1: installer composer sur le PC
pour verifier que composer est installer, aller dans "cmd" et taper composer -v

Steps 2: Créer un dossier pour le projet et installer Ratchet (Na dia efa misy ratchet ary ilay projet any dia aleo mi install vaovao sao dia efa nisy maj hafa)
installation de ratchet : composer require cboden/ratchet

tsara raha ilay fichier Il est bien de faire copier les fichier dans le .zip sans le "vendor" et le "composer.lock" et composer.json

Steps 3: Lancer le serveur dans le dossier du projet/server/server.php

Steps 4: WordPress
Activer le plugin : ChatLive  , Author : Stagiaire

Steps 5: dans WordPress créer les pages pour l'administrateur et le client

Page de l'administrateur, appeler les shortcode
[start_websocket]
[admin]
[websocket_admin_script]
[admin_css_link]

Page du client, appeler les shortcode
[start_websocket]
[chat]
[websocket_client_script] 


nano /etc/nginx/conf.d/users/xnrafbmy/somalaval-ai.xnr.afb.mybluehost.me/somalaval-ai.custom.conf
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart nginx

location /home/xnrafbmy/public_html/batpro/wp-content/themes/theme-batpro/realtime-batpro/server {
        proxy_pass http://127.0.0.1:8080; # Le port où votre serveur Ratchet écoute
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

         proxy_read_timeout 3600s; # Ajustez le temps selon vos besoins
    proxy_send_timeout 3600s; # Ajustez le temps selon vos besoins
    proxy_connect_timeout 3600s; # Ajustez le temps selon vos besoins
    }

cd /home/xnrafbmy/public_html/somalaval-ai/wp-content/themes/realtime-batpro/server/
sudo lsof -i :8080

rm -rf vendor/




wscat -c wss://batpro-madagascar.com/wp-content/themes/theme-batpro/realtime-batpro/server


launch with supervisord
the file configuration is in /etc/supervisord.conf
kill supervisord before sudo pkill supervisord
and then sudo supervisord -c supervisor.conf

sudo supervisorctl status
sudo supervisorctl stop ratchet:Ratchet
sudo supervisorctl start ratchet:Ratchet
sudo supervisorctl restart ratchet:Ratchet

[program:ratchet]
command = bash -c "ulimit -n 10000; exec /usr/local/bin/php /home/xnrafbmy/public_html/batpro/wp-content/themes/theme-batpro/realtime-batpro/server/server.php"
process_name = Ratchet
numprocs = 1
autostart = true
autorestart = true
user = root
stderr_logfile=/var/log/supervisor/ratchet.err.log
stdout_logfile=/var/log/supervisor/ratchet.out.log
stdout_logfile_maxbytes = 1MB
stderr_logfile_maxbytes = 1MB

admin_9wmjaorl
xfSe44e%VB@JF824

CREATE TABLE IF NOT EXISTS `Contenu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text,
  `filePath` varchar(255) DEFAULT NULL,
  `fileType` varchar(255) DEFAULT NULL,
  `idMessage` int NOT NULL,
  `isAdmin` tinyint(1) NOT NULL,
  `lastQuestion` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `Message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idClient` varchar(255) NOT NULL,
  `nom` varchar(255) NULL,
  `mail` varchar(255) NULL,
  `isReadClient` tinyint(1) NOT NULL,
  `isReadAdmin` tinyint(1) NOT NULL,
  `dateEnvoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4;


