-- ============================================================
-- Structure de base de Données Gestion Intégré Evotech_Africa
-- ============================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS evotech_africa;
USE evotech_africa;

-- ## 1. ENTITÉS FONDAMENTALES et RH gerer par le DG

-- Table des utilisateurs
CREATE TABLE UTILISATEURS (
  utilisateur_id INT PRIMARY KEY AUTO_INCREMENT,  
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(255) UNIQUE, -- généré automatiquement au Format: prenom_utilisateur-utilisateur_id@Evo.africa
  mot_de_passe_hash VARCHAR(255) NOT NULL,
  profil VARCHAR(500), -- pour la photo de profil 
  actif BOOLEAN DEFAULT true,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un utilisateur
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des Départements
CREATE TABLE DEPARTEMENTS (
  departement_id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(10) UNIQUE, -- généré automatiquement au format : 'Dev.', 'Arch.', 'Com'
  nom VARCHAR(50) NOT NULL, -- 'Informatique', 'Architecture', 'Communication'
  description TEXT,
  responsable_id INT, -- Référence UTILISATEURS
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un département
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des rôles pour gérer les rôles des administrateurs et cadres de l'entreprise
CREATE TABLE ROLES (
  role_id INT PRIMARY KEY AUTO_INCREMENT,
  fonction VARCHAR(50) NOT NULL UNIQUE, -- 'DG', 'ChefDepartement', 'actionnaire', 'comptable', 'Chef_projet'
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un rôle
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des Attributions des rôles aux utilisateurs
CREATE TABLE ATTRIBUTIONS (
  attribution_id INT PRIMARY KEY AUTO_INCREMENT,
  utilisateur_id INT NOT NULL,
  role_id INT NOT NULL,
  date_attribution DATE DEFAULT CURRENT_DATE,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime une attribution
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE DES AGENTS/EMPLOYÉS (Données RH)
CREATE TABLE AGENTS (
  agent_id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  date_naissance DATE,
  adresse TEXT,
  telephone_personnel VARCHAR(20),
  telephone_professionnel VARCHAR(20),
  email_personnel VARCHAR(255),
  email_professionnel VARCHAR(255) UNIQUE, -- généré automatiquement au Format: prenom_agent-agent_id@Evo.africa
  mot_de_passe_hash VARCHAR(255) NOT NULL,
  departement_id INT, -- Référence DÉPARTEMENTS
  situation_matrimoniale VARCHAR(30),
  nombre_enfants INT DEFAULT 0,
  urgence_nom VARCHAR(100),
  urgence_telephone VARCHAR(20),
  photo_profil VARCHAR(500),
  cv_path VARCHAR(500),
  etat ENUM('Actif', 'Inactif', 'Suspendu') DEFAULT 'Actif',
  date_embauche DATE DEFAULT CURRENT_DATE,
  date_depart DATE,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un agent
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des Partenaires (Clients)
CREATE TABLE PARTENAIRES (
  partenaire_id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(200) NOT NULL,
  type ENUM('Entreprise','Particulier') DEFAULT 'Particulier',
  telephone VARCHAR(20),
  adresse TEXT,
  email VARCHAR(255),
  secteur_activite VARCHAR(100),
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un partenaire
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ## 2. MODULE PROJETS gerer par le chef de projet
-- Table des projets
CREATE TABLE PROJETS (
  projet_id INT PRIMARY KEY AUTO_INCREMENT,
  reference VARCHAR(50) UNIQUE NOT NULL, -- Format: EVPRO-2026-001
  partenaire_id INT, -- Référence PARTENAIRES
  titre VARCHAR(200) NOT NULL,
  description_projet TEXT,
  chef_projet_id INT, -- Référence UTILISATEURS
  date_debut DATE,
  date_fin_prevue DATE,
  date_fin_reelle DATE,
  budget_estime DECIMAL(15,2) DEFAULT 0,
  etat ENUM('Suspendu', 'En cours', 'Terminé', 'Annulé') DEFAULT 'En cours',
  priorite ENUM('Basse', 'Normale', 'Haute', 'Urgent') DEFAULT 'Normale',
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un projet
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des participations d'agent au projet
CREATE TABLE PARTICIPATION (
  participation_id INT PRIMARY KEY AUTO_INCREMENT,
  agent_id INT NOT NULL, -- Référence AGENT
  projet_id INT NOT NULL, -- Référence Projet
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime une participation
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des assignations des tâches
CREATE TABLE TACHES (
  tache_id INT PRIMARY KEY AUTO_INCREMENT,
  projet_id INT NOT NULL, -- Référence PROJET
  description_tache VARCHAR(200) NOT NULL,
  assigne_a INT, -- Référence AGENT
  date_debut_prevue DATE DEFAULT CURRENT_DATE,
  date_fin_prevue DATE,
  priorite ENUM('Basse', 'Normale', 'Haute', 'Urgent') DEFAULT 'Normale',
  etat ENUM('À faire', 'En cours', 'En revue', 'Terminé', 'Bloqué') DEFAULT 'À faire',
  estimation_heures DECIMAL(5,2) DEFAULT 0,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime une tâche
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ## 3. MODULE TEMPS (TIMESHEET) gerer par l'Agent

-- Table des TimeSheet
CREATE TABLE TIMESHEET (
  timesheet_id INT PRIMARY KEY AUTO_INCREMENT,
  tache_id INT NOT NULL, -- Référence TACHE
  agent_id INT NOT NULL, -- Référence AGENT (ajouté pour faciliter les requêtes)
  date_travail DATE DEFAULT CURRENT_DATE,
  worked_heures DECIMAL(5,2) NOT NULL, -- Les heures passées à travailler
  description_travail TEXT,
  etat ENUM('Brouillon', 'Soumise', 'Validée', 'Refusée') DEFAULT 'Brouillon',
  date_soumission DATETIME,
  valide_par INT, -- Référence UTILISATEURS (chef de projet)
  date_validation DATETIME,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un timesheet
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ## 4. MODULE CONTRATS COMMUNICATION Gerer chef de departement et le DG

-- Table des contrats de communication
CREATE TABLE CONTRATS_COM (
  contrat_id INT PRIMARY KEY AUTO_INCREMENT,
  reference VARCHAR(50) UNIQUE NOT NULL, -- Format: CONT-COM-2026-001
  partenaire_id INT NOT NULL, -- Référence PARTENAIRE
  type_contrat VARCHAR(100) NOT NULL, -- 'Gestion Réseaux Sociaux', 'SEO', 'Relations Presse'
  description TEXT,
  date_debut DATE NOT NULL,
  date_fin DATE,
  montant DECIMAL(15,2) NOT NULL,
  periodicite ENUM('Mensuel', 'Trimestriel', 'Annuel','Forfait', 'Horaire') DEFAULT 'Mensuel',
  responsable_id INT, -- Référence AGENTS (responsable du contrat)
  etat ENUM('Actif', 'En attente', 'Terminé', 'Résilié') DEFAULT 'Actif',
  conditions_particulieres TEXT,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un contrat
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des activités du contrat de communication
CREATE TABLE ACTIVITES_CONTRAT (
  activite_id INT PRIMARY KEY AUTO_INCREMENT,
  contrat_id INT NOT NULL, -- Référence CONTRATS_COM
  date_activite DATE DEFAULT CURRENT_DATE,
  description_activite VARCHAR(200) NOT NULL, -- 'Publication Facebook', 'Article de blog', 'Newsletter'
  details TEXT,
  lien_url VARCHAR(500),
  cout DECIMAL(10,2) DEFAULT 0,
  statut_activite ENUM('Planifiée', 'Réalisée', 'Annulée') DEFAULT 'Planifiée',
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime une activité
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ## 5. MODULE FINANCE & CAISSE géré par le DG et Comptable

-- Table des comptes de l'Entreprise
CREATE TABLE COMPTES (
  compte_id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL, -- 'Caisse Principale', 'Compte Courant', 'Compte Épargne'
  type ENUM('Banque','Caisse') DEFAULT 'Caisse',
  numero_compte VARCHAR(50),
  solde_initial DECIMAL(15,2) DEFAULT 0,
  solde_actuel DECIMAL(15,2) DEFAULT 0,
  devise VARCHAR(3) DEFAULT '$',
  responsable_id INT, -- Référence UTILISATEURS
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  statut INT DEFAULT 0 -- il est 0 par défaut, Le statut change à 1 quand on supprime un compte
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des mouvements des comptes
CREATE TABLE MOUVEMENT_COMPTE (
  mouvement_id INT PRIMARY KEY AUTO_INCREMENT,
  compte_id INT NOT NULL, -- Référence COMPTES
  type_mouvement ENUM('entrée', 'sortie') NOT NULL,
  montant DECIMAL(15,2) NOT NULL,
  motif VARCHAR(200) NOT NULL,
  projet_id INT, -- Référence PROJETS (si lié à un projet)
  contrat_id INT, -- Référence CONTRATS_COM (si lié à un contrat)
  reference_piece VARCHAR(100), -- Numéro de facture, de reçu, etc.
  cree_par INT, -- ID utilisateur qui a créé le mouvement
  date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un mouvement
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des transferts entre comptes
CREATE TABLE TRANSFERTS_COMPTE (
  transfert_id INT PRIMARY KEY AUTO_INCREMENT,
  compte_source_id INT NOT NULL, -- Compte d'où sort l'argent
  compte_destination_id INT NOT NULL, -- Compte qui reçoit l'argent
  montant DECIMAL(15,2) NOT NULL,
  motif VARCHAR(200) NOT NULL,
  reference_transfert VARCHAR(100), -- Référence interne du transfert
  mouvement_sortie_id INT NOT NULL, -- Lien vers le mouvement de sortie
  mouvement_entree_id INT NOT NULL, -- Lien vers le mouvement d'entrée
  date_transfert DATETIME DEFAULT CURRENT_TIMESTAMP,
  valide_par INT, -- ID utilisateur qui a validé (optionnel)
  statut INT DEFAULT 0, -- 0 = actif, 1 = supprimé
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ## 6. MODULE ARCHIVAGE DES PRODUCTIONS géré par le chef de projet

-- Table de type production
CREATE TABLE TYPES_PRODUCTION (
  type_prod_id INT PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL, -- 'Plan Architecture', 'Code Source', 'Charte Graphique'
  description TEXT,
  departement_id INT, -- Référence DÉPARTEMENTS
  extension_autorisee VARCHAR(100), -- 'pdf,docx,zip', 'dwg,dxf', 'psd,ai'
  taille_max_mo INT DEFAULT 100,
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un type
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tables DISK de stockage
CREATE TABLE DISK_STOCKAGE (
  disk_id INT PRIMARY KEY AUTO_INCREMENT,
  matricule VARCHAR(100) UNIQUE NOT NULL, -- Généré automatiquement Format : EVO/D1/HDD
  capacite_go INT NOT NULL,
  emplacement_physique VARCHAR(200),
  date_acquisition DATE,
  etat ENUM('Fonctionnel', 'Maintenance', 'HS') DEFAULT 'Fonctionnel',
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime un Disk
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des productions
CREATE TABLE PRODUCTION (
  production_id INT PRIMARY KEY AUTO_INCREMENT,
  projet_id INT NOT NULL, -- Référence PROJETS
  type_prod_id INT NOT NULL, -- Référence TYPES_PRODUCTION
  disk_id INT NOT NULL, -- Référence Disk
  nom_fichier VARCHAR(255) NOT NULL,
  chemin_stockage VARCHAR(500) NOT NULL,
  cloud_url VARCHAR(500),
  date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME,
  confidentialite ENUM('Public', 'Interne', 'Confidentiel') DEFAULT 'Interne',
  statut INT DEFAULT 0, -- il est 0 par défaut, Le statut change à 1 quand on supprime une production
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE connexions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(utilisateur_id)
);

ALTER TABLE utilisateurs ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE `contrats_com` CHANGE `periodicite` `periodicite` ENUM('Mensuel','Trimestriel','Annuel','Forfait','Horaire','Hebdomadaire') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'Mensuel';