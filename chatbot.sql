-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 26 juil. 2024 à 13:57
-- Version du serveur : 8.0.31
-- Version de PHP : 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `chatbot`
--

-- --------------------------------------------------------

--
-- Structure de la table `contenu`
--

DROP TABLE IF EXISTS `contenu`;
CREATE TABLE IF NOT EXISTS `contenu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text,
  `filePath` varchar(255) DEFAULT NULL,
  `fileType` varchar(255) DEFAULT NULL,
  `idMessage` int NOT NULL,
  `isAdmin` tinyint(1) NOT NULL,
  `lastQuestion` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `contenu`
--

INSERT INTO `contenu` (`id`, `message`, `filePath`, `fileType`, `idMessage`, `isAdmin`, `lastQuestion`) VALUES
(1, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 1, 1, 1),
(2, 'Malagasy', NULL, NULL, 1, 0, 1),
(3, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 2, 1, 1),
(4, 'Malagasy', NULL, NULL, 2, 0, 1),
(5, 'Quel est votre nom?<br>', NULL, NULL, 2, 1, 2),
(6, 'czaa', NULL, NULL, 2, 0, 2),
(7, 'gfzr', NULL, NULL, 2, 1, NULL),
(8, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 3, 1, 1),
(9, 'Malagasy', NULL, NULL, 3, 0, 1),
(10, 'Quel est votre nom?<br>', NULL, NULL, 3, 1, 2),
(11, 'lucas', NULL, NULL, 3, 0, 2),
(12, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 4, 1, 1),
(13, 'Malagasy', NULL, NULL, 4, 0, 1),
(14, 'Quel est votre nom?<br>', NULL, NULL, 4, 1, 2),
(15, 'test', NULL, NULL, 4, 0, 2),
(16, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 5, 1, 1),
(17, 'Malagasy', NULL, NULL, 5, 0, 1),
(18, 'Quel est votre nom?<br>', NULL, NULL, 5, 1, 2),
(19, 'lucasx', NULL, NULL, 5, 0, 2),
(20, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 6, 1, 1),
(21, 'Malagasy', NULL, NULL, 6, 0, 1),
(22, 'Quel est votre nom?<br>', NULL, NULL, 6, 1, 2),
(23, 'lucas', NULL, NULL, 6, 0, 2),
(24, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 7, 1, 1),
(25, 'Malagasy', NULL, NULL, 7, 0, 1),
(26, 'Quel est votre nom?<br>', NULL, NULL, 7, 1, 2),
(27, 'lucas', NULL, NULL, 7, 0, 2),
(28, 'Quel est votre email?<br>', NULL, NULL, 7, 1, 3),
(29, 'test', NULL, NULL, 7, 0, 3),
(30, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 8, 1, 1),
(31, 'Malagasy', NULL, NULL, 8, 0, 1),
(32, 'Quel est votre nom?<br>', NULL, NULL, 8, 1, 2),
(33, 'eric', NULL, NULL, 8, 0, 2),
(34, 'Quel est votre email?<br>', NULL, NULL, 8, 1, 3),
(35, 'ert', NULL, NULL, 8, 0, 3),
(36, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 9, 1, 1),
(37, 'Malagasy', NULL, NULL, 9, 0, 1),
(38, 'Quel est votre nom?<br>', NULL, NULL, 9, 1, 2),
(39, 'lucas', NULL, NULL, 9, 0, 2),
(40, 'Quel est votre email?<br>', NULL, NULL, 9, 1, 3),
(41, 'test', NULL, NULL, 9, 0, 3),
(42, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 10, 1, 1),
(43, 'Malagasy', NULL, NULL, 10, 0, 1),
(44, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 11, 1, 1),
(45, 'Malagasy', NULL, NULL, 11, 0, 1),
(46, 'Bienvenu, Quelle est votre langue préférée?<br>Français<br>Malagasy<br>', NULL, NULL, 12, 1, 1),
(47, 'Malagasy', NULL, NULL, 12, 0, 1),
(48, 'Quel est votre nom?<br>', NULL, NULL, 12, 1, 2),
(49, 'lucas', NULL, NULL, 12, 0, 2),
(50, 'Quel est votre email?<br>', NULL, NULL, 12, 1, 3),
(51, 'test', NULL, NULL, 12, 0, 3);

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idClient` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `isReadClient` tinyint(1) NOT NULL,
  `isReadAdmin` tinyint(1) NOT NULL,
  `dateEnvoi` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `message`
--

INSERT INTO `message` (`id`, `idClient`, `nom`, `mail`, `isReadClient`, `isReadAdmin`, `dateEnvoi`) VALUES
(1, '66a3a18200b82', '', '0', 1, 0, '2024-07-26 13:15:46'),
(2, '66a3a49130d2e', '', '0', 0, 1, '2024-07-26 13:29:02'),
(3, '66a3a4b7a7c87', '', '0', 1, 0, '2024-07-26 13:29:31'),
(4, '66a3a544ef86d', '', '0', 1, 0, '2024-07-26 13:31:54'),
(5, '66a3a5696ffe7', '', '0', 1, 0, '2024-07-26 13:32:34'),
(6, '66a3a584a1f25', '', '0', 1, 0, '2024-07-26 13:32:56'),
(7, '66a3a6341a284', '', '0', 1, 0, '2024-07-26 13:35:58'),
(8, '66a3a6471179f', '', '0', 1, 0, '2024-07-26 13:36:49'),
(9, '66a3a6cdd8bce', '', '0', 1, 0, '2024-07-26 13:38:28'),
(10, '66a3a7fca03b1', '', '0', 1, 0, '2024-07-26 13:43:25'),
(11, '66a3a83c35ccc', '', '', 1, 0, '2024-07-26 13:44:29'),
(12, '66a3a85335d9f', 'lucas', 'test', 1, 0, '2024-07-26 13:45:07');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
