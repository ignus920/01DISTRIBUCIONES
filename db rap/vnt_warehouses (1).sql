-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 30-10-2025 a las 19:14:43
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
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cityId` int DEFAULT NULL,
  `billingFormat` int NOT NULL DEFAULT '16',
  `is_credit` int NOT NULL DEFAULT '0',
  `termId` int NOT NULL DEFAULT '1' COMMENT 'forma de pago',
  `creditLimit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT 'cupo de credito',
  `status` tinyint DEFAULT '1',
  `integrationDataId` int DEFAULT NULL,
  `main` tinyint DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vnt_warehouses`
--

INSERT INTO `vnt_warehouses` (`id`, `companyId`, `name`, `address`, `postcode`, `cityId`, `billingFormat`, `is_credit`, `termId`, `creditLimit`, `status`, `integrationDataId`, `main`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 13:30:33', '2025-10-30 13:30:33', NULL),
(2, 2, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 17:49:10', '2025-10-30 17:49:10', NULL),
(3, 3, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 17:52:36', '2025-10-30 17:52:36', NULL),
(4, 4, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 18:00:24', '2025-10-30 18:00:24', NULL),
(5, 5, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 18:02:51', '2025-10-30 18:02:51', NULL),
(6, 6, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 18:10:14', '2025-10-30 18:10:14', NULL),
(7, 7, 'Principal', 'Dirección principal', NULL, NULL, 16, 0, 1, '0', 1, NULL, 1, '2025-10-30 19:00:05', '2025-10-30 19:00:05', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_warehouses`
--
ALTER TABLE `vnt_warehouses`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_warehouses`
--
ALTER TABLE `vnt_warehouses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
