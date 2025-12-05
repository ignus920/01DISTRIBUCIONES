-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:09:04
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
  `firstName` varchar(255) DEFAULT NULL,
  `secondName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `secondLastName` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_contact` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `integrationDataId` int DEFAULT NULL,
  `warehouseId` int DEFAULT NULL,
  `positionId` int DEFAULT NULL COMMENT 'cargo del contacto',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouseId` (`warehouseId`),
  ADD KEY `positionId` (`positionId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
