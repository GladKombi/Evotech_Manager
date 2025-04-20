-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2025 at 06:07 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eka_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `postnom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `adresse` varchar(100) NOT NULL,
  `fonction` int(11) NOT NULL,
  `telephoneReferant` varchar(50) NOT NULL,
  `pwd` varchar(50) NOT NULL,
  `mail` text NOT NULL,
  `profil` text NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `nom`, `postnom`, `prenom`, `genre`, `telephone`, `adresse`, `fonction`, `telephoneReferant`, `pwd`, `mail`, `profil`, `statut`) VALUES
(1, 'Glad', 'Muvunga', 'Rylah', 'Masculin', '09876', 'Kambali', 1, '087654', '1234', '', 'G_Shop6760a4d4bb80c.jpg', 0),
(2, 'Eloge', 'Muyisa', 'Mumbere', 'Masculin', '0988766544', 'Kambali', 2, '009887', '1234', '', 'Eka_6761c4e239015.jpg', 0),
(3, 'Bob', 'Comando', 'Zic_Stars', 'Masculin', '0886764', 'kitulu', 2, '0878564', '1234', '', 'Eka_6761c79827016.jpg', 0),
(4, 'Thanks', 'malikewa', 'Natasha', 'Feminin', '09876554', 'Kitulu', 1, '09876', '1234', '', 'Eka_6761cc7394890.jpg', 0),
(5, 'anelka', 'vutsumbire', 'Amina', 'Masculin', '09876543', 'vubange', 1, '09877665', '1234', 'anelka@gmail.com', 'Eka_6799600c04b73.jpg', 0),
(6, 'FXFFG', 'FCXGF', 'CCFC', '', '0990179128', 'CFCFGCC', 0, '', '1234', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `categorie`
--

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categorie`
--

INSERT INTO `categorie` (`id`, `nom`, `statut`) VALUES
(1, 'camera', 0),
(2, 'Seep light', 0);

-- --------------------------------------------------------

--
-- Table structure for table `departement`
--

CREATE TABLE `departement` (
  `id` int(11) NOT NULL,
  `nom_Departement` varchar(100) NOT NULL,
  `denomination` varchar(100) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departement`
--

INSERT INTO `departement` (`id`, `nom_Departement`, `denomination`, `statut`) VALUES
(1, 'Development Web', 'Web Developer', 0),
(2, 'Videographie', 'Videaste', 0);

-- --------------------------------------------------------

--
-- Table structure for table `disk`
--

CREATE TABLE `disk` (
  `id` int(11) NOT NULL,
  `matricule` text NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disk`
--

INSERT INTO `disk` (`id`, `matricule`, `statut`) VALUES
(1, 'EKA/D1/HDD', 0),
(2, 'EKA/D2/HDD', 0),
(3, 'EKA/D3/HDD', 0);

-- --------------------------------------------------------

--
-- Table structure for table `materiel`
--

CREATE TABLE `materiel` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `marque` varchar(100) NOT NULL,
  `specialite` varchar(100) NOT NULL,
  `categorie` int(11) NOT NULL,
  `photo` text NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materiel`
--

INSERT INTO `materiel` (`id`, `nom`, `marque`, `specialite`, `categorie`, `photo`, `statut`) VALUES
(1, ' D810', 'Nikon', 'Photo', 1, 'Eka_6774407592d9d.jpg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `mouvementcaisse`
--

CREATE TABLE `mouvementcaisse` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(50) NOT NULL,
  `libelle` text NOT NULL,
  `montant` double NOT NULL,
  `devise` varchar(50) NOT NULL,
  `statut` int(11) NOT NULL,
  `cloture` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mouvementcaisse`
--

INSERT INTO `mouvementcaisse` (`id`, `date`, `type`, `libelle`, `montant`, `devise`, `statut`, `cloture`) VALUES
(1, '2025-01-21', 'Entree', 'paement facture fepsi', 1000, 'Dollard', 0, 1),
(2, '2025-01-21', 'Entree', 'prime fepsi', 20000, 'Franc', 0, 1),
(3, '2025-01-21', 'Sortie', 'Achat carb', 2000, 'Franc', 0, 1),
(4, '2025-01-21', 'Sortie', 'achat camera', 500, 'Dollard', 0, 1),
(5, '2025-01-21', 'Entree', 'Réport à nouveau', 500, 'Dollard', 0, 0),
(6, '2025-01-21', 'Entree', 'Réport à nouveau', 18000, 'Franc', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `partenaire`
--

CREATE TABLE `partenaire` (
  `id` int(11) NOT NULL,
  `Denomination` varchar(50) NOT NULL,
  `dateSignature` date NOT NULL,
  `adresse` text NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partenaire`
--

INSERT INTO `partenaire` (`id`, `Denomination`, `dateSignature`, `adresse`, `telephone`, `statut`) VALUES
(1, 'Sydip', '2024-12-01', 'Avenu Lubero', '098766', 0),
(2, 'ACPDI', '2024-12-17', 'Mirador', '097852415212', 0),
(3, 'SOPROCOPIV', '2024-12-17', 'Vungi', '09876545', 0),
(4, 'Fepsi', '2024-12-17', 'Du cente', '0987654', 0);

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `id` int(11) NOT NULL,
  `agent` int(11) NOT NULL,
  `terrain` int(11) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participation`
--

INSERT INTO `participation` (`id`, `agent`, `terrain`, `statut`) VALUES
(1, 1, 1, 0),
(2, 3, 1, 0),
(3, 3, 2, 0),
(4, 4, 2, 0),
(5, 1, 2, 0),
(6, 3, 3, 0),
(7, 4, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_production`
--

CREATE TABLE `post_production` (
  `id` int(11) NOT NULL,
  `Typeproduction` varchar(100) NOT NULL,
  `participation` int(11) NOT NULL,
  `disk` int(11) NOT NULL,
  `emplacement` varchar(50) NOT NULL,
  `etat` int(11) NOT NULL,
  `livraison` int(11) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_production`
--

INSERT INTO `post_production` (`id`, `Typeproduction`, `participation`, `disk`, `emplacement`, `etat`, `livraison`, `statut`) VALUES
(1, 'Video', 2, 1, 'Eka/Fepsi', 0, 0, 0),
(2, 'Photo', 1, 1, 'Eka/Fepsi', 0, 0, 0),
(3, 'photos', 7, 1, 'ekaConsuting/Sopro/photoCafee', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `terrain`
--

CREATE TABLE `terrain` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` text NOT NULL,
  `lieu` varchar(50) NOT NULL,
  `partenaire` int(11) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terrain`
--

INSERT INTO `terrain` (`id`, `date`, `description`, `lieu`, `partenaire`, `statut`) VALUES
(1, '2024-12-22', 'Remise Kits soin', 'Vuhovi', 1, 0),
(2, '2025-01-03', 'Remise de machine à coudre', 'Lubero', 2, 0),
(3, '2025-01-06', 'vente cafée', 'lubero', 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `postnom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `foction` varchar(50) NOT NULL,
  `profil` varchar(50) NOT NULL,
  `pwd` varchar(50) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `postnom`, `prenom`, `telephone`, `foction`, `profil`, `pwd`, `mail`, `statut`) VALUES
(1, 'Kombi', 'Ryllah', 'Glad', '098765', 'ceo', 'Eka_67ad0ac35d444.jpg', '1234', 'CEOGlad@Eka.com', 0),
(2, 'Kamala', 'albert', 'alblack', '0987654', 'Admin', 'Eka_67ad2a29a3004.jpg', '1234', 'Adminalblack@Eka.com', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departement`
--
ALTER TABLE `departement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disk`
--
ALTER TABLE `disk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materiel`
--
ALTER TABLE `materiel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mouvementcaisse`
--
ALTER TABLE `mouvementcaisse`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partenaire`
--
ALTER TABLE `partenaire`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_production`
--
ALTER TABLE `post_production`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `terrain`
--
ALTER TABLE `terrain`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departement`
--
ALTER TABLE `departement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `disk`
--
ALTER TABLE `disk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `materiel`
--
ALTER TABLE `materiel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mouvementcaisse`
--
ALTER TABLE `mouvementcaisse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `partenaire`
--
ALTER TABLE `partenaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `post_production`
--
ALTER TABLE `post_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `terrain`
--
ALTER TABLE `terrain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
