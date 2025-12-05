-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:09:40
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
-- Estructura de tabla para la tabla `tenants`
--

CREATE TABLE `tenants` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `db_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '127.0.0.1',
  `db_port` int NOT NULL DEFAULT '3306',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
) ;

--
-- Volcado de datos para la tabla `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `email`, `phone`, `address`, `db_name`, `db_user`, `db_password`, `db_host`, `db_port`, `is_active`, `settings`, `created_at`, `updated_at`, `data`) VALUES
('0b323f0d-f12a-4432-9df7-418313db432e', 'maria', 'maria@gmail.com', NULL, NULL, 'tenant_0b323f0d_f12a_4432_9df7_418313db432e', 'root', '', '127.0.0.1', 3306, 1, '[]', '2025-10-28 01:28:04', '2025-10-28 01:28:04', NULL),
('178824b3-de33-48d5-9f63-0f6ff98af25b', 'pruebas2', 'pruebas2@gmail.com', NULL, NULL, 'tenant_178824b3_de33_48d5_9f63_0f6ff98af25b', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-28 21:17:51', '2025-10-28 21:17:51', NULL),
('66afb34d-0ec2-4fcd-a332-d037129fee5c', 'pruebas', 'preubas@gmail.com', NULL, NULL, 'tenant_66afb34d_0ec2_4fcd_a332_d037129fee5c', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-27 20:53:17', '2025-10-27 20:53:17', NULL),
('9a056ad1-4e65-4499-99d6-a813d21de5dc', 'empresa1', 'prieto@gmail.com', NULL, NULL, 'tenant_9a056ad1_4e65_4499_99d6_a813d21de5dc', 'root', '', '127.0.0.1', 3306, 1, '[]', '2025-10-15 18:16:28', '2025-10-15 18:16:28', NULL),
('9ce60348-3be7-4bcb-9249-5f608463d1a8', 'carlos1', 'carlos@gmail.com', NULL, NULL, 'tenant_9ce60348_3be7_4bcb_9249_5f608463d1a8', 'root', '', '127.0.0.1', 3306, 1, '[]', '2025-10-15 18:26:11', '2025-10-15 18:26:11', NULL),
('a1b6ae9c-130d-4ac5-9bae-c7e761b20f49', 'pruebas1', 'preubas1@gmail.com', NULL, NULL, 'tenant_a1b6ae9c_130d_4ac5_9bae_c7e761b20f49', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-28 21:09:39', '2025-10-28 21:09:39', NULL),
('acf803ad-997c-4aaf-9192-81000c7ff7b2', 'suarez', 'suarez@gmail.com', NULL, NULL, 'tenant_acf803ad_997c_4aaf_9192_81000c7ff7b2', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-27 21:07:00', '2025-10-27 21:07:00', NULL),
('bac91087-b744-462f-9b20-21b2d6050e14', 'pruebas', 'pruebas@gmail.com', NULL, NULL, 'tenant_bac91087_b744_462f_9b20_21b2d6050e14', 'root', '', '127.0.0.1', 3306, 1, '[]', '2025-10-16 02:36:18', '2025-10-16 02:36:18', NULL),
('be179fc6-ce3c-4cbb-9f71-3ff49370fd0d', 'pruebas3', 'pruebas3@gmail.com', NULL, NULL, 'pruebas3_be179fc6_ce3c_4cbb_9f71_3ff49370fd0d', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-28 21:37:24', '2025-10-28 21:37:24', NULL),
('c1cca38e-1a80-44ed-ac35-40a937058538', 'juan', 'juan@gmail.com', NULL, NULL, 'tenant_c1cca38e_1a80_44ed_ac35_40a937058538', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-27 21:03:52', '2025-10-27 21:03:52', NULL),
('de5b0d26-8efb-4262-a60c-7c32db5938d3', 'gregorio', 'gregorio@gmail.com', NULL, NULL, 'tenant_de5b0d26_8efb_4262_a60c_7c32db5938d3', 'root', '', '127.0.0.1', 3306, 1, '[]', '2025-10-27 19:29:04', '2025-10-27 19:29:04', NULL),
('fd5495ef-23fd-4597-b2e1-75afca658dfd', 'admin', 'admin@gmail.com', NULL, NULL, 'tenant_fd5495ef_23fd_4597_b2e1_75afca658dfd', 'root', 'marsella', '100.91.238.113', 3306, 1, '[]', '2025-10-27 20:50:33', '2025-10-27 20:50:33', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_email_unique` (`email`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
