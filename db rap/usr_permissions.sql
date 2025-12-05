-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:10:31
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
-- Estructura de tabla para la tabla `usr_permissions`
--

CREATE TABLE `usr_permissions` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usr_permissions`
--

INSERT INTO `usr_permissions` (`id`, `name`, `status`, `createdAt`, `updatedAt`, `deletedAt`) VALUES
(1, 'Parametros', 1, '2025-10-24 20:13:34', NULL, NULL),
(2, 'Usuarios', 1, '2025-10-24 20:13:34', NULL, NULL),
(3, 'Ventas', 1, '2025-10-24 20:13:49', NULL, NULL),
(4, 'Inventario', 1, '2025-10-24 20:13:49', NULL, NULL),
(5, 'Facturacion', 1, '2025-10-24 20:14:25', NULL, NULL),
(6, 'Administracion de Items', 1, '2025-10-24 20:14:25', NULL, NULL),
(7, 'Caja', 1, '2025-10-24 20:15:00', NULL, NULL),
(8, 'Compras', 1, '2025-10-24 20:15:00', NULL, NULL),
(9, 'Mercadeo', 1, '2025-10-24 20:15:15', NULL, NULL),
(10, 'Cartera', 1, '2025-10-24 20:15:15', NULL, NULL),
(11, 'Informes de ventas', 1, '2025-10-24 20:15:40', NULL, NULL),
(12, 'Informes de inventario', 1, '2025-10-24 20:15:40', NULL, NULL),
(13, 'Informes de Caja', 1, '2025-10-24 20:16:06', NULL, NULL),
(14, 'Informes de Cartera', 1, '2025-10-24 20:16:06', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `usr_permissions`
--
ALTER TABLE `usr_permissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `usr_permissions`
--
ALTER TABLE `usr_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
