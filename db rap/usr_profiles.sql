-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:10:55
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
-- Estructura de tabla para la tabla `usr_profiles`
--

CREATE TABLE `usr_profiles` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(100) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usr_profiles`
--

INSERT INTO `usr_profiles` (`id`, `name`, `alias`, `status`, `createdAt`, `updatedAt`, `deletedAt`) VALUES
(1, 'SuperUsuario', 'SuperUsuario', 1, '2025-10-24 20:05:30', NULL, NULL),
(2, 'Administrador', 'Administrador', 1, '2025-10-24 20:05:30', NULL, NULL),
(3, 'Administrador POS', 'Administrador POS', 1, '2025-10-24 20:06:04', NULL, NULL),
(4, 'Vendedor POS', 'Vendedor POS', 1, '2025-10-24 20:06:04', NULL, NULL),
(5, 'Cajero', 'Cajero', 1, '2025-10-24 20:06:44', NULL, NULL),
(6, 'Almacen', 'Almacen', 1, '2025-10-24 20:06:44', NULL, NULL),
(7, 'Vendedor Institucional', 'Vendedor Institucional', 1, '2025-10-24 20:07:29', NULL, NULL),
(8, 'Compras', 'Compras', 1, '2025-10-24 20:08:28', NULL, NULL),
(9, 'Mercadeo', 'Mercadeo', 1, '2025-10-24 20:08:28', NULL, NULL),
(10, 'Cartera', 'Cartera', 1, '2025-10-24 20:08:53', NULL, NULL),
(11, 'Facturacion', 'Facturacion', 1, '2025-10-24 20:08:53', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `usr_profiles`
--
ALTER TABLE `usr_profiles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `usr_profiles`
--
ALTER TABLE `usr_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
