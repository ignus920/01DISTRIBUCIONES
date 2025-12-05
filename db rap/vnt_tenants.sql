-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:08:03
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
-- Estructura de tabla para la tabla `vnt_tenants`
--

CREATE TABLE `vnt_tenants` (
  `id` int NOT NULL,
  `companyId` int NOT NULL DEFAULT '0',
  `merchantTypeId` int DEFAULT NULL,
  `plainId` int DEFAULT NULL,
  `afiliationDate` datetime DEFAULT NULL,
  `renovationDate` datetime DEFAULT NULL,
  `endTest` date DEFAULT NULL,
  `tenantCode` text,
  `status` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_tenants`
--
ALTER TABLE `vnt_tenants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `companyId` (`companyId`),
  ADD KEY `merchantTypeId` (`merchantTypeId`),
  ADD KEY `plainId` (`plainId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_tenants`
--
ALTER TABLE `vnt_tenants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vnt_tenants`
--
ALTER TABLE `vnt_tenants`
  ADD CONSTRAINT `vnt_tenants_ibfk_1` FOREIGN KEY (`companyId`) REFERENCES `vnt_companies` (`id`),
  ADD CONSTRAINT `vnt_tenants_ibfk_2` FOREIGN KEY (`merchantTypeId`) REFERENCES `vnt_merchant_types` (`id`),
  ADD CONSTRAINT `vnt_tenants_ibfk_3` FOREIGN KEY (`plainId`) REFERENCES `vnt_plains` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
