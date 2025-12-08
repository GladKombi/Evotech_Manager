-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 12:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evotech_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `attribition`
--

CREATE TABLE `attribition` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `role` int(11) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attribition`
--

INSERT INTO `attribition` (`id`, `user`, `role`, `statut`) VALUES
(1, 1, 1, 0);

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
  `nom` varchar(50) NOT NULL,
  `pseudo` varchar(50) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departement`
--

INSERT INTO `departement` (`id`, `nom`, `pseudo`, `statut`) VALUES
(1, 'developement web   ', 'Dev.', 0),
(2, 'Architucture', 'Ir', 0);

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
(1, 'EVO/D1/HDD', 0);

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
(5, '2025-01-21', 'Entree', 'Réport à nouveau', 500, 'Dollard', 0, 1),
(6, '2025-01-21', 'Entree', 'Réport à nouveau', 18000, 'Franc', 0, 1),
(7, '2025-03-19', 'Entree', 'ssdsdsdas', 24, 'Dollard', 0, 1),
(8, '2025-03-20', 'Entree', 'Réport à nouveau', 524, 'Dollard', 0, 0),
(9, '2025-03-20', 'Entree', 'Réport à nouveau', 18000, 'Franc', 0, 0),
(10, '2025-04-29', 'Sortie', 'achat imprimente', 234, 'Dollard', 0, 0),
(11, '2025-04-29', 'Sortie', 'Achat fournitures', 5000, 'Franc', 0, 0),
(12, '2025-04-29', 'Entree', 'don', 12000, 'Franc', 0, 0),
(13, '2025-05-09', 'Entree', 'pourcentage africaXtv', 100, 'Dollard', 0, 0),
(14, '2025-05-09', 'Sortie', 'dgsdfgsdfgsdfg', 120, 'Franc', 0, 0),
(15, '2025-05-16', 'Entree', 'dhdfhdfhdf', 6, 'Dollard', 0, 0);

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
(1, 'Rylah', '2025-04-24', 'Procure', '0231456479', 0),
(2, 'fgfsddsfgsd', '2025-04-25', 'fsdfdfdfdqs', '54156454', 0);

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `id` int(11) NOT NULL,
  `agent` int(11) NOT NULL,
  `projet` int(11) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participation`
--

INSERT INTO `participation` (`id`, `agent`, `projet`, `statut`) VALUES
(1, 1, 1, 1),
(2, 1, 1, 0),
(3, 2, 1, 0);

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
(1, 'APK', 2, 1, 'Evotech/glad/Mobil', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `projet`
--

CREATE TABLE `projet` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` text NOT NULL,
  `partenaire` int(11) NOT NULL,
  `type_projet` int(11) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projet`
--

INSERT INTO `projet` (`id`, `date`, `description`, `partenaire`, `type_projet`, `statut`) VALUES
(1, '2025-04-26', 'logiciel de gestion Zora_Business', 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `fonction` varchar(50) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `fonction`, `statut`) VALUES
(1, 'Gérant', 0);

-- --------------------------------------------------------

--
-- Table structure for table `type_projetc`
--

CREATE TABLE `type_projetc` (
  `id` int(11) NOT NULL,
  `descriprion` varchar(100) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `type_projetc`
--

INSERT INTO `type_projetc` (`id`, `descriprion`, `statut`) VALUES
(1, 'Informatique', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `departement` int(11) NOT NULL,
  `mail` varchar(50) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `pwd` text NOT NULL,
  `profil` varchar(100) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `genre`, `departement`, `mail`, `telephone`, `pwd`, `profil`, `statut`) VALUES
(1, 'Kombi', 'Glad', 'Masculin', 1, 'glad@gmail.com', '0997019883', '$2y$10$HpzZqJOOorr9/mYoJ.FUgeV0.9.RQL8NOVO2WLSvLHdT2adkF3V4W', 'Evotech_680564535d262.jpg', 0),
(2, 'albert', 'jdfjfs', 'Masculin', 1, 'mqil@gmail.com', '098765', '$2y$10$f9M4kCaWy51ad0yH3umNRuzXBKmRQa6K6cofJ0rZPn9uaMx1Oqd/K', 'Evotech_680ce91701c76.jpg', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attribition`
--
ALTER TABLE `attribition`
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
-- Indexes for table `projet`
--
ALTER TABLE `projet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `type_projetc`
--
ALTER TABLE `type_projetc`
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
-- AUTO_INCREMENT for table `attribition`
--
ALTER TABLE `attribition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `materiel`
--
ALTER TABLE `materiel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mouvementcaisse`
--
ALTER TABLE `mouvementcaisse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `partenaire`
--
ALTER TABLE `partenaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_production`
--
ALTER TABLE `post_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projet`
--
ALTER TABLE `projet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `type_projetc`
--
ALTER TABLE `type_projetc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
