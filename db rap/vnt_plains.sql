-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:08:14
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
-- Estructura de tabla para la tabla `vnt_plains`
--

CREATE TABLE `vnt_plains` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `status` tinyint DEFAULT '1',
  `type` enum('Vendido','Saas') DEFAULT NULL,
  `merchantTypeId` int DEFAULT NULL,
  `warehoseQty` int NOT NULL DEFAULT '1',
  `usersQty` int NOT NULL DEFAULT '2',
  `storesQty` int NOT NULL DEFAULT '1',
  `createAt` datetime NOT NULL,
  `updateAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `vnt_plains`
--

INSERT INTO `vnt_plains` (`id`, `name`, `description`, `status`, `type`, `merchantTypeId`, `warehoseQty`, `usersQty`, `storesQty`, `createAt`, `updateAt`, `deletedAt`) VALUES
(1, 'A', 'Basico', 1, 'Saas', 1, 1, 2, 1, '2025-10-24 00:44:31', NULL, NULL),
(2, 'B', 'Avanzado', 1, 'Saas', 1, 2, 4, 4, '2025-10-24 00:45:10', NULL, NULL),
(3, 'C', 'Superior', 1, 'Saas', 1, 3, 8, 6, '2025-10-24 00:48:35', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_plains`
--
ALTER TABLE `vnt_plains`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchantTypeId` (`merchantTypeId`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_plains`
--
ALTER TABLE `vnt_plains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vnt_plains`
--
ALTER TABLE `vnt_plains`
  ADD CONSTRAINT `vnt_plains_ibfk_1` FOREIGN KEY (`merchantTypeId`) REFERENCES `vnt_merchant_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
