-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:09:58
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
-- Estructura de tabla para la tabla `user_tenants`
--

CREATE TABLE `user_tenants` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tenant_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `user_tenants`
--

INSERT INTO `user_tenants` (`id`, `user_id`, `tenant_id`, `role`, `is_active`, `last_accessed_at`, `created_at`, `updated_at`) VALUES
(1, 1, '9a056ad1-4e65-4499-99d6-a813d21de5dc', 'admin', 1, '2025-10-16 18:12:57', '2025-10-15 18:16:31', '2025-10-16 18:12:57'),
(2, 2, '9ce60348-3be7-4bcb-9249-5f608463d1a8', 'admin', 1, '2025-10-15 18:26:36', '2025-10-15 18:26:33', '2025-10-15 18:26:36'),
(3, 3, 'bac91087-b744-462f-9b20-21b2d6050e14', 'admin', 1, '2025-10-16 02:36:22', '2025-10-16 02:36:21', '2025-10-16 02:36:22'),
(4, 4, 'de5b0d26-8efb-4262-a60c-7c32db5938d3', 'admin', 1, '2025-10-28 00:16:40', '2025-10-27 19:29:10', '2025-10-28 00:16:40'),
(5, 7, '0b323f0d-f12a-4432-9df7-418313db432e', 'admin', 1, '2025-10-28 01:28:06', '2025-10-28 01:28:05', '2025-10-28 01:28:06'),
(6, 11, 'acf803ad-997c-4aaf-9192-81000c7ff7b2', 'admin', 1, '2025-10-28 19:34:12', '2025-10-27 21:07:59', '2025-10-28 19:34:12'),
(7, 13, '178824b3-de33-48d5-9f63-0f6ff98af25b', 'admin', 1, '2025-10-28 21:30:05', '2025-10-28 21:18:47', '2025-10-28 21:30:05'),
(8, 14, 'be179fc6-ce3c-4cbb-9f71-3ff49370fd0d', 'admin', 1, '2025-10-28 21:52:03', '2025-10-28 21:38:18', '2025-10-28 21:52:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `user_tenants`
--
ALTER TABLE `user_tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_tenants_user_id_tenant_id_unique` (`user_id`,`tenant_id`),
  ADD KEY `user_tenants_tenant_id_foreign` (`tenant_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `user_tenants`
--
ALTER TABLE `user_tenants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
