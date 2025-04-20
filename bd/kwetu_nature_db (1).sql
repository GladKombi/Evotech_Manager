-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2025 at 05:55 AM
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
-- Database: `kwetu_nature_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activite`
--

CREATE TABLE `activite` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `Theme` text NOT NULL,
  `lieu` varchar(100) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activite`
--

INSERT INTO `activite` (`id`, `date`, `Theme`, `lieu`, `statut`) VALUES
(1, '2025-02-06', 'Picnick Ecologique', 'Reseve Itav', 0);

-- --------------------------------------------------------

--
-- Table structure for table `article`
--

CREATE TABLE `article` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `titre` text NOT NULL,
  `contenus` text NOT NULL,
  `image` text NOT NULL,
  `statut` int(11) NOT NULL,
  `publication` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article`
--

INSERT INTO `article` (`id`, `date`, `titre`, `contenus`, `image`, `statut`, `publication`) VALUES
(1, '2025-02-06', 'Glad aime le code', 'patati patata', 'Nature67a4858a1e873.jpg', 0, 0),
(2, '2025-02-06', 'Glad', 'Bla bla bla bla bla bla bla', 'Nature67a4f93998b51.jpg', 0, 0),
(3, '2025-02-06', 'Glad aime le code  grave', 'bla bla bla bla bla bla bla bla bla bla bla  Charline Baraka, cultivatrice à Beni, au Nord-Kivu, a vu sa vie changer de manière significative depuis qu&#039;elle a décidé de se lancer dans la culture du cacao. Cela ne fait qu&#039;un an qu&#039;elle s&#039;est lancée, mais ce choix a déjà eu un impact considérable sur son quotidien.                                                                                           ', 'Nature67a51ae7c40e0.jpg', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `membre`
--

CREATE TABLE `membre` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `postnom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `dateNaissance` date NOT NULL,
  `age` int(11) NOT NULL,
  `adress` text NOT NULL,
  `ville` int(11) NOT NULL,
  `etatCivil` varchar(50) NOT NULL,
  `profession` text NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `dateIntegration` date NOT NULL,
  `photo` text NOT NULL,
  `statut` int(11) NOT NULL,
  `aprobation` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membre`
--

INSERT INTO `membre` (`id`, `nom`, `postnom`, `prenom`, `genre`, `dateNaissance`, `age`, `adress`, `ville`, `etatCivil`, `profession`, `telephone`, `dateIntegration`, `photo`, `statut`, `aprobation`) VALUES
(1, 'glad', 'kombi', 'Rylla', 'Feminin', '2002-06-04', 22, 'kambali', 1, 'Celibataire', 'Dev', '097852415212', '2025-01-19', 'profile_glad.jpg', 0, 1),
(2, 'Lad_Ryl', 'Muvunga', 'Ryllay', 'Feminin', '2000-02-19', 24, 'Londo', 2, 'Fiance', 'Pharmacienne', '0987654', '2025-01-19', 'Nature678cc50be7574.jpg', 0, 1),
(3, 'akilah', 'milonde', 'popo', 'Masculin', '2000-02-12', 24, 'Kambali', 1, 'Fiance', 'Farceur', '09887654', '2025-02-05', 'Nature67a2a4fb01a1e.jpg', 0, 0),
(4, 'akilah', 'milonde', 'popo', 'Masculin', '2000-02-27', 24, 'Kambali', 2, 'Fiance', 'Farceur', '09887654', '2025-02-05', 'Nature67a2d3eccd496.jpg', 0, 0),
(5, 'akilah', 'milonde', 'popo', 'Masculin', '2000-02-05', 25, 'Kambali', 1, 'Fiance', 'Farceur', '09887654', '2025-02-05', 'Nature67a2d4abb445b.jpg', 0, 0),
(6, 'akilah', 'milonde', 'popo', 'Masculin', '2000-02-04', 25, 'Kambali', 1, 'celibataire', 'Farceur', '09887654', '2025-02-05', 'Nature67a305a6c7502.jpg', 0, 0),
(7, 'albert', 'milonde', 'laur', 'Masculin', '2000-02-12', 24, 'Malera', 1, 'celibataire', 'Pharmacienne', '0876543234', '2025-02-06', 'Nature67a488933bcd0.jpg', 0, 0),
(8, 'Mumbere', 'Vutsumbire', 'Anelka', 'Masculin', '2000-10-30', 24, 'Vubange', 1, 'Fiance', 'Artiste', '089786754', '2025-02-06', 'Nature67a4f785cac7a.jpg', 0, 1),
(9, 'albert', 'Muvunga', 'Ryllay', 'Feminin', '1978-09-17', 46, 'Londo', 1, 'Celibataire', 'Pharmacienne', '71213213', '2025-02-07', 'Nature67a5bde48d18e.jpg', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `fonction` varchar(50) NOT NULL,
  `pwd` text NOT NULL,
  `profil` text NOT NULL,
  `statut` int(11) NOT NULL,
  `postnom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `telephone` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `fonction`, `pwd`, `profil`, `statut`, `postnom`, `prenom`, `user_name`, `telephone`) VALUES
(1, 'Glad', 'Admin', '1234', 'Nature67a598366ca56.jpg', 0, 'Kombi', 'Ryllah', 'Glad2Kombi@nature.com', '0997019883'),
(2, 'Anelka', 'coordonateur', '1234', 'Nature67a59d9bba748.jpg', 0, 'Vutsumbire', 'Amina', 'Anelka2Vutsumbire@nature.com', '0987456321');

-- --------------------------------------------------------

--
-- Table structure for table `ville`
--

CREATE TABLE `ville` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `statut` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ville`
--

INSERT INTO `ville` (`id`, `nom`, `statut`) VALUES
(1, 'Butembo', 0),
(2, 'Beni', 0),
(3, '2go', 0),
(4, 'Glad aime le code', 1),
(5, 'Kisangani', 1),
(6, 'Kisangani', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activite`
--
ALTER TABLE `activite`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `membre`
--
ALTER TABLE `membre`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ville`
--
ALTER TABLE `ville`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activite`
--
ALTER TABLE `activite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `article`
--
ALTER TABLE `article`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `membre`
--
ALTER TABLE `membre`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ville`
--
ALTER TABLE `ville`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
