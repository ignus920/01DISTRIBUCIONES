-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:08:51
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
-- Estructura de tabla para la tabla `vnt_merchant_moduls`
--

CREATE TABLE `vnt_merchant_moduls` (
  `id` int NOT NULL,
  `merchantId` int DEFAULT NULL,
  `modulId` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `vnt_merchant_moduls`
--

INSERT INTO `vnt_merchant_moduls` (`id`, `merchantId`, `modulId`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_merchant_moduls`
--
ALTER TABLE `vnt_merchant_moduls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchantId` (`merchantId`),
  ADD KEY `modulId` (`modulId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_merchant_moduls`
--
ALTER TABLE `vnt_merchant_moduls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `vnt_merchant_moduls`
--
ALTER TABLE `vnt_merchant_moduls`
  ADD CONSTRAINT `vnt_merchant_moduls_ibfk_1` FOREIGN KEY (`merchantId`) REFERENCES `vnt_merchant_types` (`id`),
  ADD CONSTRAINT `vnt_merchant_moduls_ibfk_2` FOREIGN KEY (`modulId`) REFERENCES `vnt_moduls` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
