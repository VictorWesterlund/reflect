SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `reflect`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_acl`
--

CREATE TABLE `api_acl` (
  `id` varchar(32) NOT NULL,
  `api_key` varchar(255) COMMENT 'foreign_key:api_keys.id',
  `endpoint` varchar(255) NOT NULL COMMENT 'foreign_key:api_endpoints.id',
  `method` enum('GET','POST','PUT','PATCH','DELETE') NOT NULL,
  `created` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_endpoints`
--

CREATE TABLE `api_endpoints` (
  `endpoint` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `api_endpoints`
--

INSERT INTO `api_endpoints` (`endpoint`, `active`) VALUES
('reflect/acl', 1),
('reflect/endpoint', 1),
('reflect/key', 1),
('reflect/session/key', 1),
('reflect/session/user', 1),
('reflect/user', 1);

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `user` varchar(255) DEFAULT NULL COMMENT 'foreign_key:api_user.id',
  `expires` int(32) DEFAULT NULL,
  `created` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `active`, `user`, `expires`, `created`) VALUES
('PUBLIC_API_KEY', 1, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `api_users`
--

CREATE TABLE `api_users` (
  `id` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created` int(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_acl`
--
ALTER TABLE `api_acl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_key` (`api_key`),
  ADD KEY `endpoint` (`endpoint`);

--
-- Indexes for table `api_endpoints`
--
ALTER TABLE `api_endpoints`
  ADD PRIMARY KEY (`endpoint`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);

--
-- Indexes for table `api_users`
--
ALTER TABLE `api_users`
  ADD PRIMARY KEY (`id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_acl`
--
ALTER TABLE `api_acl`
  ADD CONSTRAINT `api_acl_ibfk_1` FOREIGN KEY (`api_key`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `api_acl_ibfk_2` FOREIGN KEY (`endpoint`) REFERENCES `api_endpoints` (`endpoint`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user`) REFERENCES `api_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
