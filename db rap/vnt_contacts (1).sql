-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 30-10-2025 a las 19:15:04
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
-- Estructura de tabla para la tabla `vnt_contacts`
--

CREATE TABLE `vnt_contacts` (
  `id` int NOT NULL,
  `firstName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondLastName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `integrationDataId` int DEFAULT NULL,
  `warehouseId` int DEFAULT NULL,
  `positionId` int DEFAULT NULL COMMENT 'cargo del contacto',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vnt_contacts`
--

INSERT INTO `vnt_contacts` (`id`, `firstName`, `secondName`, `lastName`, `secondLastName`, `email`, `phone_contact`, `contact`, `status`, `integrationDataId`, `warehouseId`, `positionId`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'edwin', NULL, 'prieto', NULL, 'admin@gmail.com', '3123842021', NULL, 1, NULL, NULL, NULL, '2025-10-30 13:30:33', '2025-10-30 13:30:33', NULL),
(2, 'maria', NULL, 'suarez', NULL, 'car@gmail.com', '3154541618', NULL, 1, NULL, NULL, NULL, '2025-10-30 17:49:10', '2025-10-30 17:49:10', NULL),
(3, 'pruebas', NULL, 'pruebas', NULL, 'pruebas@gmail.com', '3154844918', NULL, 1, NULL, NULL, NULL, '2025-10-30 17:52:36', '2025-10-30 17:52:36', NULL),
(4, 'dfdf', NULL, 'dgfdfgdf', NULL, 'asd@gmail.com', '33333333', NULL, 1, NULL, NULL, NULL, '2025-10-30 18:00:24', '2025-10-30 18:00:24', NULL),
(5, 'fgfgfg', NULL, 'fghfgh', NULL, 'asda@gmail.com', '12312312312', NULL, 1, NULL, NULL, NULL, '2025-10-30 18:02:51', '2025-10-30 18:02:51', NULL),
(6, 'fghfgh', NULL, 'fhfhfg', NULL, 'sdfsdfs@gmail.com', '33333333333', NULL, 1, NULL, NULL, NULL, '2025-10-30 18:10:14', '2025-10-30 18:10:14', NULL),
(7, 'jerman', NULL, 'caicedo', NULL, 'jaime@gmail.com', '3154541310', NULL, 1, NULL, NULL, NULL, '2025-10-30 19:00:05', '2025-10-30 19:00:05', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouseid` (`warehouseId`),
  ADD KEY `positionid` (`positionId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  ADD CONSTRAINT `vnt_contacts_ibfk_1` FOREIGN KEY (`warehouseId`) REFERENCES `vnt_warehouses` (`id`),
  ADD CONSTRAINT `vnt_contacts_ibfk_2` FOREIGN KEY (`positionId`) REFERENCES `cfg_positions` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
