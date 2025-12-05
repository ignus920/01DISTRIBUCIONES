-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 30-10-2025 a las 19:14:21
-- Versión del servidor: 8.0.43
-- Versión de PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `rap`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_companies`
--

CREATE TABLE `vnt_companies` (
  `id` int NOT NULL,
  `businessName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billingEmail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integrationDataId` int DEFAULT NULL,
  `identification` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkDigit` int DEFAULT NULL COMMENT 'digito de verificacion',
  `lastName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondLastName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `typePerson` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `typeIdentificationId` int DEFAULT NULL,
  `regimeId` int DEFAULT NULL,
  `code_ciiu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fiscalResponsabilityId` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vnt_companies`
--

INSERT INTO `vnt_companies` (`id`, `businessName`, `billingEmail`, `firstName`, `integrationDataId`, `identification`, `checkDigit`, `lastName`, `secondLastName`, `secondName`, `status`, `typePerson`, `typeIdentificationId`, `regimeId`, `code_ciiu`, `fiscalResponsabilityId`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'carwash', 'admin@gmail.com', 'edwin', NULL, NULL, NULL, 'prieto', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 13:30:33', '2025-10-30 13:30:33', NULL),
(2, 'carwash1', 'car@gmail.com', 'maria', NULL, NULL, NULL, 'suarez', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 17:49:10', '2025-10-30 17:49:10', NULL),
(3, 'pruebas', 'pruebas@gmail.com', 'pruebas', NULL, NULL, NULL, 'pruebas', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 17:52:36', '2025-10-30 17:52:36', NULL),
(4, 'dad', 'asd@gmail.com', 'dfdf', NULL, NULL, NULL, 'dgfdfgdf', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 18:00:24', '2025-10-30 18:00:24', NULL),
(5, 'dvdfvdfv', 'asda@gmail.com', 'fgfgfg', NULL, NULL, NULL, 'fghfgh', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 18:02:50', '2025-10-30 18:02:50', NULL),
(6, 'gfhfghfgh', 'sdfsdfs@gmail.com', 'fghfgh', NULL, NULL, NULL, 'fhfhfg', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 18:10:14', '2025-10-30 18:10:14', NULL),
(7, 'jaime', 'jaime@gmail.com', 'jerman', NULL, NULL, NULL, 'caicedo', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2025-10-30 19:00:05', '2025-10-30 19:00:05', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_companies`
--
ALTER TABLE `vnt_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identification` (`identification`),
  ADD KEY `typeidentificationid` (`typeIdentificationId`),
  ADD KEY `regimeid` (`regimeId`),
  ADD KEY `fiscalresponsabilityid` (`fiscalResponsabilityId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_companies`
--
ALTER TABLE `vnt_companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vnt_companies`
--
ALTER TABLE `vnt_companies`
  ADD CONSTRAINT `vnt_companies_ibfk_1` FOREIGN KEY (`typeIdentificationId`) REFERENCES `cnf_type_identifications` (`id`),
  ADD CONSTRAINT `vnt_companies_ibfk_2` FOREIGN KEY (`regimeId`) REFERENCES `cnf_regime` (`id`),
  ADD CONSTRAINT `vnt_companies_ibfk_3` FOREIGN KEY (`fiscalResponsabilityId`) REFERENCES `cnf_fiscal_responsabilities` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
