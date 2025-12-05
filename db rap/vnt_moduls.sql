-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:08:25
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
-- Estructura de tabla para la tabla `vnt_moduls`
--

CREATE TABLE `vnt_moduls` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `version` varchar(100) DEFAULT NULL,
  `migration` text,
  `dev_hours` int NOT NULL DEFAULT '1',
  `status` tinyint NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  `deletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `vnt_moduls`
--

INSERT INTO `vnt_moduls` (`id`, `name`, `description`, `version`, `migration`, `dev_hours`, `status`, `createdAt`, `updatedAt`, `deletedAt`) VALUES
(1, 'VENTAS', 'Cotizador, clientes, cotizaciones, informes de ventas, cartera y clientes', '1', NULL, 1, 1, '2025-10-24 00:25:07', NULL, NULL),
(2, 'CAJA', 'Apertura, ingresos, egresos, arqueo, cierre, reporte movimiento, compronbante de  arqueo y cierre', '1', NULL, 1, 1, '2025-10-24 00:25:07', NULL, NULL),
(3, 'INVENTARIO', 'Items, ajustes de inventario, motivos de movimiento, traslados de bodega, bodegas, informes de movimiento de inventario', '1', NULL, 1, 1, '2025-10-24 00:28:10', NULL, NULL),
(4, 'PARAMETROS', 'seriales, comandas, saldos negativos, precios diferentes por sucursal, aplicaciones para los items, control de acceso por ubicacion y horario', '1', NULL, 1, 1, '2025-10-24 00:28:10', NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `vnt_moduls`
--
ALTER TABLE `vnt_moduls`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `vnt_moduls`
--
ALTER TABLE `vnt_moduls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
