CREATE DATABASE IF NOT EXISTS evotech_africa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evotech_africa;
-- Importer ici votre SQL complet corrigé.
-- Seed administrateur minimal :
CREATE TABLE IF NOT EXISTS UTILISATEURS (
  utilisateur_id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(255) UNIQUE,
  mot_de_passe_hash VARCHAR(255) NOT NULL,
  profil VARCHAR(500),
  actif BOOLEAN DEFAULT true,
  statut INT DEFAULT 0,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO UTILISATEURS(nom,prenom,email,mot_de_passe_hash,actif) VALUES('Admin','Evotech','admin@evo.africa','$2y$10$B8fZbi.EEPLM09q2e6nYGuQyR9ZiqZ0IHGg3W0JHZkEP2YoB3eARW',1);

-- Réalisations pour la page d'accueil
-- categories 
CREATE TABLE IF NOT EXISTS CATEGORIES (
  categorie_id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL, -- technologie, service, etc.
  description TEXT,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- realisations 
CREATE TABLE IF NOT EXISTS REALISATIONS (
  realisation_id INT PRIMARY KEY AUTO_INCREMENT,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  categorie int,
  statut INT DEFAULT 0, -- 0: brouillon, 1: publié
  suprime int DEFAULT 0, -- 0: non supprimé, 1: supprimé
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- images
CREATE TABLE IF NOT EXISTS REALISATIONS_IMAGES (
  image_id INT PRIMARY KEY AUTO_INCREMENT,
  realisation_id INT,
  chemin_image VARCHAR(255) NOT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;