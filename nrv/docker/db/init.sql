CREATE DATABASE IF NOT EXISTS `nrv_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nrv_db`;

CREATE TABLE IF NOT EXISTS `artiste` (
    `id_artiste` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `information` TEXT,
    `genre` VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `soiree` (
    `id_soiree` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `thematique` VARCHAR(100),
    `date` DATE NOT NULL,
    `horaire_debut` TIME NOT NULL,
    `lieu` VARCHAR(100) NOT NULL,
    `places_disponibles` INT NOT NULL,
    `prix` DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `spectacle` (
    `id_spectacle` INT AUTO_INCREMENT PRIMARY KEY,
    `id_soiree` INT NOT NULL,
    `titre` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `genre` VARCHAR(50) NOT NULL,
    `image` VARCHAR(255),
    `video` VARCHAR(255),
    `creneau` TIME NOT NULL,
    FOREIGN KEY (`id_soiree`) REFERENCES `soiree`(`id_soiree`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `spectacle_artiste` (
    `id_spectacle` INT NOT NULL,
    `id_artiste` INT NOT NULL,
    PRIMARY KEY (`id_spectacle`, `id_artiste`),
    FOREIGN KEY (`id_spectacle`) REFERENCES `spectacle`(`id_spectacle`) ON DELETE CASCADE,
    FOREIGN KEY (`id_artiste`) REFERENCES `artiste`(`id_artiste`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `utilisateur` (
    `id_utilisateur` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(50) NOT NULL,
    `prenom` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `commande` (
    `id_commande` INT AUTO_INCREMENT PRIMARY KEY,
    `id_utilisateur` INT NOT NULL,
    `montant_total` DECIMAL(10,2) DEFAULT 0.00,
    `date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `statut` ENUM('en_cours', 'payee', 'annulee') DEFAULT 'en_cours',
    FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `panier` (
    `id_panier` INT AUTO_INCREMENT PRIMARY KEY,
    `id_commande` INT NOT NULL,
    `id_soiree` INT NOT NULL,
    `type_tarif` ENUM('normal', 'reduit', 'pass_3_jours') NOT NULL,
    `quantite` INT NOT NULL DEFAULT 1,
    FOREIGN KEY (`id_commande`) REFERENCES `commande`(`id_commande`) ON DELETE CASCADE,
    FOREIGN KEY (`id_soiree`) REFERENCES `soiree`(`id_soiree`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `billet` (
    `id_billet` INT AUTO_INCREMENT PRIMARY KEY,
    `id_commande` INT NOT NULL,
    `id_soiree` INT NOT NULL,
    `reference` VARCHAR(50) UNIQUE NOT NULL,
    `token` VARCHAR(64) UNIQUE NOT NULL,
    `categorie_tarif` ENUM('normal', 'reduit', 'pass_3_jours') NOT NULL,
    FOREIGN KEY (`id_commande`) REFERENCES `commande`(`id_commande`) ON DELETE CASCADE,
    FOREIGN KEY (`id_soiree`) REFERENCES `soiree`(`id_soiree`) ON DELETE CASCADE
) ENGINE=InnoDB;
