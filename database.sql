-- Création de la Base de Données
CREATE DATABASE IF NOT EXISTS `agence_immo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agence_immo`;

-- --------------------------------------------------------
-- Table `utilisateurs`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(50) NOT NULL,
  `prenom` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `telephone` VARCHAR(20) NOT NULL,
  `role` ENUM('client', 'commercial') NOT NULL,
  `statut` ENUM('actif', 'inactif') DEFAULT 'actif',
  `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table `biens`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `biens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titre` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `type` ENUM('appartement', 'villa') NOT NULL,
  `prix_mensuel` DECIMAL(10,2) NOT NULL,
  `adresse` VARCHAR(150) NOT NULL,
  `ville` VARCHAR(50) NOT NULL,
  `chambres` INT NOT NULL,
  `salons` INT NOT NULL,
  `salles_de_bain` INT NOT NULL,
  `superficie` INT NOT NULL,
  `statut` ENUM('disponible', 'reserve', 'occupe') DEFAULT 'disponible',
  `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table `images`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bien_id` INT NOT NULL,
  `chemin` VARCHAR(255) NOT NULL,
  `est_principale` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (`bien_id`) REFERENCES `biens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table `reservations`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT NOT NULL,
  `bien_id` INT NOT NULL,
  `date_debut` DATE NOT NULL,
  `date_fin` DATE NOT NULL,
  `statut` ENUM('en_attente', 'validee', 'annulee', 'terminee') DEFAULT 'en_attente',
  `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bien_id`) REFERENCES `biens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table `favoris`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favoris` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT NOT NULL,
  `bien_id` INT NOT NULL,
  UNIQUE KEY `client_bien_unique` (`client_id`, `bien_id`),
  FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`bien_id`) REFERENCES `biens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Insertion de données de test
-- Mots de passe pour tous les comptes de test : password123
-- Le hash correspond à password_hash('password123', PASSWORD_BCRYPT)
-- --------------------------------------------------------

INSERT IGNORE INTO `utilisateurs` (`nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `role`, `statut`) VALUES
('Diop', 'Abdoulaye', 'commercial@immo.com', '$2y$10$BMbfhPga3OwEJSFChgJi8eUHMQ4d9Ur0YLy1eMHc4OZfgI9EBSrmC', '771234567', 'commercial', 'actif'),
('Sow', 'Fatou', 'client@immo.com', '$2y$10$BMbfhPga3OwEJSFChgJi8eUHMQ4d9Ur0YLy1eMHc4OZfgI9EBSrmC', '777654321', 'client', 'actif'),
('Ndiaye', 'Modou', 'client2@immo.com', '$2y$10$BMbfhPga3OwEJSFChgJi8eUHMQ4d9Ur0YLy1eMHc4OZfgI9EBSrmC', '789876543', 'client', 'actif');

INSERT IGNORE INTO `biens` (`id`, `titre`, `description`, `type`, `prix_mensuel`, `adresse`, `ville`, `chambres`, `salons`, `salles_de_bain`, `superficie`, `statut`) VALUES
(1, 'Superbe Villa F6 avec Piscine', 'Magnifique villa contemporaine située dans un quartier calme et sécurisé. Elle dispose d\'un grand salon lumineux, d\'une cuisine moderne équipée, de 5 chambres spacieuses avec salles de bain privatives, d\'un grand jardin avec piscine et d\'un garage pour 2 voitures.', 'villa', 1500000.00, 'Almadies, Zone 10', 'Dakar', 5, 2, 5, 450, 'disponible'),
(2, 'Appartement Haut Standing F3', 'Appartement de luxe meublé offrant une vue imprenable sur l\'océan. Composé d\'un salon avec balcon, de 2 chambres climatisées, d\'une cuisine équipée, d\'une buanderie et d\'un parking sécurisé. Groupe électrogène et ascenseur disponibles.', 'appartement', 700000.00, 'Fann Résidence', 'Dakar', 2, 1, 2, 120, 'disponible'),
(3, 'Villa d\'architecte avec vue Panoramique', 'Exceptionnelle villa moderne construite sur les hauteurs. Finitions de très haut standing, menuiserie en aluminium haut de gamme, système de domotique, salon double hauteur, terrasse sur le toit avec jacuzzi, cuisine américaine et quartier très résidentiel.', 'villa', 2500000.00, 'Mamelles', 'Dakar', 4, 2, 4, 380, 'disponible'),
(4, 'Appartement Cozy F4 Proche Centre-Ville', 'Spacieux appartement idéal pour une famille. Situé dans un immeuble récent et sécurisé, il offre un grand salon, 3 chambres dont la parentale avec dressing et salle d\'eau, une cuisine moderne et un balcon filant.', 'appartement', 450000.00, 'Mermoz', 'Dakar', 3, 1, 2, 150, 'disponible');

INSERT IGNORE INTO `images` (`bien_id`, `chemin`, `est_principale`) VALUES
(1, 'assets/images/villa1_1.jpg', 1),
(1, 'assets/images/villa1_2.jpg', 0),
(1, 'assets/images/villa1_3.jpg', 0),
(2, 'assets/images/app1_1.jpg', 1),
(2, 'assets/images/app1_2.jpg', 0),
(3, 'assets/images/villa2_1.jpg', 1),
(3, 'assets/images/villa2_2.jpg', 0),
(4, 'assets/images/app2_1.jpg', 1);
