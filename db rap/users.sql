-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 28-10-2025 a las 22:10:09
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
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `two_factor_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_failed_attempts` int NOT NULL DEFAULT '0',
  `two_factor_locked_until` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_enabled`, `two_factor_type`, `two_factor_secret`, `phone`, `two_factor_failed_attempts`, `two_factor_locked_until`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'edwin prieto', 'prieto@gmail.com', NULL, '$2y$12$newKi0eYLre9W1GwNK0UE.Cl4yZVbhyDF0ZM1LGj4MkCFrkvEg8aG', 1, 'totp', 'A5RFC2MGX4EV6XCS', NULL, 1, NULL, NULL, '2025-10-15 18:16:28', '2025-10-16 19:32:32'),
(2, 'carlos', 'carlos@gmail.com', NULL, '$2y$12$WA/SKDnOklIWA3sCBCLdWeeNydUKEIVRBnRn1wotlQGqGu7SzmdYO', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-15 18:26:07', '2025-10-15 18:26:07'),
(3, 'pruebas', 'pruebas@gmail.com', NULL, '$2y$12$zUPMdnHYqO0WJDg0B4aG4ezAaZ1/rA0V5UocmJkd6JfqlaAgbSMB6', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-16 02:36:18', '2025-10-16 02:36:18'),
(4, 'gregorio', 'gregorio@gmail.com', NULL, '$2y$12$KfwH.uNUIRdu6QRkr0SHT.h3QGuiyihjOsDVmHOsCMWCTWYIy083m', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-27 19:29:01', '2025-10-27 19:32:03'),
(5, 'sebastian', 'sebastian@gmail.com', NULL, '$2y$12$4yXXUBnQZNuwEi1YuK5G4uAnrWLs1.uRLrmUWZGwG3AeixVYNyCde', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-28 01:16:44', '2025-10-28 01:16:44'),
(6, 'sebastian', 'javier@gmail.com', NULL, '$2y$12$05beEPWqrk3ALBACOg6Q4.RmklFOz7sUxtQSu/.koMg3kqI7pv98m', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-28 01:26:19', '2025-10-28 01:26:19'),
(7, 'maria', 'maria@gmail.com', NULL, '$2y$12$5Z/FZN80orJTl0hh7vnxkuwqag49hdmoabB/x7j..mmGoHAcmQ9gK', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-28 01:28:04', '2025-10-28 01:28:04'),
(8, 'admin', 'admin@gmail.com', NULL, '$2y$12$TikIPEI4ebSeEsuee2qao.T0kd7r1Oxtk9wiSjzA/j2QbUGt3g7Ey', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-27 20:50:33', '2025-10-27 20:50:33'),
(9, 'pruebas', 'preubas@gmail.com', NULL, '$2y$12$EgSgqMlg8oLTjaCTdDKXb.bLzLJJo38gnOc.KYHXobLwat/gOetF.', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-27 20:53:17', '2025-10-27 20:53:17'),
(10, 'juan ', 'juan@gmail.com', NULL, '$2y$12$VmlvZUBMoRirQns7KoahJOEZKRKwTKtG4C4hl50Ka1zLZ7ic9Ofx6', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-27 21:03:52', '2025-10-27 21:03:52'),
(11, 'suarez', 'suarez@gmail.com', NULL, '$2y$12$xUhn1wUru9CmcFO.1BBtt.5OC/pf0VHnUhNnJVJ2lvDrkzZLBn7v6', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-27 21:07:00', '2025-10-27 21:07:00'),
(12, 'pruebas1', 'preubas1@gmail.com', NULL, '$2y$12$gcn3nThzVnwOYkELxTmSAun7R5H/vcH03LizpeNsrEuTHlMpsifsa', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-28 21:09:38', '2025-10-28 21:09:38'),
(13, 'preubas2', 'pruebas2@gmail.com', NULL, '$2y$12$2Eml2Tgue6PCZbBMRbrTzewfDTMaGYIVUXPrYJ6P448l60voqDOzG', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-28 21:17:51', '2025-10-28 21:17:51'),
(14, 'pruebas3', 'pruebas3@gmail.com', NULL, '$2y$12$4jNVi74Buk4gA1hWMjx6YuCFV4WBhhqbB3WpHJK2VzaijIgMqDTb2', 0, 'email', NULL, NULL, 0, NULL, NULL, '2025-10-28 21:37:24', '2025-10-28 21:37:24');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
