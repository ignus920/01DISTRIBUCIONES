-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:07:21
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
-- Estructura de tabla para la tabla `vnt_warehouses`
--

CREATE TABLE `vnt_warehouses` (
  `id` int NOT NULL,
  `companyId` int NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `cityId` int DEFAULT NULL,
  `billingFormat` int NOT NULL DEFAULT '16',
  `is_credit` int NOT NULL DEFAULT '0',
  `termId` int NOT NULL DEFAULT '1' COMMENT 'forma de pago',
  `creditLimit` varchar(20) NOT NULL DEFAULT '0' COMMENT 'cupo de credito',
  `priceList` int NOT NULL DEFAULT '1' COMMENT 'lista de precio asignada',
  `status` tinyint DEFAULT '1',
  `integrationDataId` int DEFAULT NULL,
  `main` tinyint DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_warehouses`
--
ALTER TABLE `vnt_warehouses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cityId` (`cityId`),
  ADD KEY `companyId` (`companyId`),
  ADD KEY `termId` (`termId`),
  ADD KEY `priceList` (`priceList`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_warehouses`
--
ALTER TABLE `vnt_warehouses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vnt_warehouses`
--
ALTER TABLE `vnt_warehouses`
  ADD CONSTRAINT `vnt_warehouses_ibfk_1` FOREIGN KEY (`cityId`) REFERENCES `cnf_cities` (`id`),
  ADD CONSTRAINT `vnt_warehouses_ibfk_2` FOREIGN KEY (`companyId`) REFERENCES `vnt_companies` (`id`),
  ADD CONSTRAINT `vnt_warehouses_ibfk_3` FOREIGN KEY (`termId`) REFERENCES `vnt_terms` (`id`),
  ADD CONSTRAINT `vnt_warehouses_ibfk_4` FOREIGN KEY (`priceList`) REFERENCES `vnt_price_lists` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
