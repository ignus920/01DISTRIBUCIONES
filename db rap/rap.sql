-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 24-10-2025 a las 20:43:57
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
-- Estructura de tabla para la tabla `cfg_positions`
--

CREATE TABLE `cfg_positions` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cfg_positions`
--

INSERT INTO `cfg_positions` (`id`, `name`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Propietario', 1, '2025-10-23 19:35:00', NULL, NULL),
(2, 'Administrador POS', 1, '2025-10-23 19:35:01', NULL, NULL),
(3, 'Vendedor', 1, '2025-10-24 00:36:59', NULL, NULL),
(4, 'Cajero', 1, '2025-10-24 00:36:59', NULL, NULL),
(5, 'Almacen', 1, '2025-10-24 00:37:26', NULL, NULL),
(6, 'Bodega', 1, '2025-10-24 00:37:26', NULL, NULL),
(7, 'Vendedor Institucional', 1, '2025-10-24 00:37:49', NULL, NULL),
(8, 'Compras', 1, '2025-10-24 00:37:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cnf_audit_status_log`
--

CREATE TABLE `cnf_audit_status_log` (
  `id` int NOT NULL,
  `warehouseId` int NOT NULL DEFAULT '1',
  `docId` int NOT NULL DEFAULT '0',
  `event` text NOT NULL,
  `campo1` text,
  `campo2` text,
  `campo3` text,
  `fecha_cambio` datetime DEFAULT (now()),
  `user` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cnf_cities`
--

CREATE TABLE `cnf_cities` (
  `id` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `cons` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `cod_ciudad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `cod_departamento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cnf_fiscal_responsabilities`
--

CREATE TABLE `cnf_fiscal_responsabilities` (
  `id` int NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `integrationDataId` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cnf_regime`
--

CREATE TABLE `cnf_regime` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint DEFAULT '1',
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cnf_type_identifications`
--

CREATE TABLE `cnf_type_identifications` (
  `id` int NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `acronym` varchar(255) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usr_permissions`
--

CREATE TABLE `usr_permissions` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usr_permissions`
--

INSERT INTO `usr_permissions` (`id`, `name`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usr_permissions_profiles`
--

CREATE TABLE `usr_permissions_profiles` (
  `id` int NOT NULL,
  `creater` tinyint NOT NULL,
  `deleter` tinyint NOT NULL,
  `editer` tinyint NOT NULL,
  `show` tinyint NOT NULL,
  `profileId` int DEFAULT NULL,
  `permissionId` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usr_profiles`
--

CREATE TABLE `usr_profiles` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(100) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usr_profiles`
--

INSERT INTO `usr_profiles` (`id`, `name`, `alias`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usr_users`
--

CREATE TABLE `usr_users` (
  `id` int NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  *`status` tinyint NOT NULL DEFAULT '1', user_tenants
  `registrationStatus` tinyint NOT NULL DEFAULT '0',
  `dateLastLogin` datetime DEFAULT NULL,user_tenants
  `tenantId` int DEFAULT NULL,user_tenants
  `profileId` int DEFAULT NULL,user_tenants
  *`loginWarehouseId` int DEFAULT NULL,user_tenants
  *`tokenReset` varchar(255) DEFAULT NULL,password_reset_tokens
  *`int_dataId` int DEFAULT NULL,users
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_companies` tenants
--

CREATE TABLE `vnt_companies` (
  `id` int NOT NULL,
  `businessName` varchar(255) DEFAULT NULL,
  `billingEmail` varchar(255) DEFAULT NULL,
  `firstName` varchar(255) DEFAULT NULL,
  `integrationDataId` int DEFAULT NULL,
  `identification` varchar(15) DEFAULT NULL,
  `checkDigit` int DEFAULT NULL COMMENT 'digito de verificacion',
  `lastName` varchar(255) DEFAULT NULL,
  `secondLastName` varchar(255) DEFAULT NULL,
  `secondName` varchar(255) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `typePerson` varchar(255) DEFAULT NULL,
  `typeIdentificationId` int DEFAULT NULL,
  `regimeId` int DEFAULT NULL,
  `code_ciiu` varchar(255) DEFAULT NULL,
  `fiscalResponsabilityId` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_contacts`
--

CREATE TABLE `vnt_contacts` (
  `id` int NOT NULL,
  `firstName` varchar(255) DEFAULT NULL,
  `secondName` varchar(255) DEFAULT NULL,
  `lastName` varchar(255) DEFAULT NULL,
  `secondLastName` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_contact` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `integrationDataId` int DEFAULT NULL,
  `warehouseId` int DEFAULT NULL,
  `positionId` int DEFAULT NULL COMMENT 'cargo del contacto',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_merchant_moduls`
--

CREATE TABLE `vnt_merchant_moduls` (
  `id` int NOT NULL,
  `merchantId` int DEFAULT NULL,
  `modulId` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_merchant_types`
--

CREATE TABLE `vnt_merchant_types` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  `version` varchar(100) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `vnt_merchant_types`
--

INSERT INTO `vnt_merchant_types` (`id`, `name`, `description`, `version`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'POS', 'Comercio B2C', '1', 1, '2025-10-24 00:22:56', NULL, NULL),
(2, 'INSTITUCIONAL', 'Comercio B2B', '1', 1, '2025-10-24 00:22:56', NULL, NULL);

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
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `vnt_moduls`
--

INSERT INTO `vnt_moduls` (`id`, `name`, `description`, `version`, `migration`, `dev_hours`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'VENTAS', 'Cotizador, clientes, cotizaciones, informes de ventas, cartera y clientes', '1', NULL, 1, 1, '2025-10-24 00:25:07', NULL, NULL),
(2, 'CAJA', 'Apertura, ingresos, egresos, arqueo, cierre, reporte movimiento, compronbante de  arqueo y cierre', '1', NULL, 1, 1, '2025-10-24 00:25:07', NULL, NULL),
(3, 'INVENTARIO', 'Items, ajustes de inventario, motivos de movimiento, traslados de bodega, bodegas, informes de movimiento de inventario', '1', NULL, 1, 1, '2025-10-24 00:28:10', NULL, NULL),
(4, 'PARAMETROS', 'seriales, comandas, saldos negativos, precios diferentes por sucursal, aplicaciones para los items, control de acceso por ubicacion y horario', '1', NULL, 1, 1, '2025-10-24 00:28:10', NULL, NULL);

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
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `vnt_plains`
--

INSERT INTO `vnt_plains` (`id`, `name`, `description`, `status`, `type`, `merchantTypeId`, `warehoseQty`, `usersQty`, `storesQty`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'A', 'Basico', 1, 'Saas', 1, 1, 2, 1, '2025-10-24 00:44:31', NULL, NULL),
(2, 'B', 'Avanzado', 1, 'Saas', 1, 2, 4, 4, '2025-10-24 00:45:10', NULL, NULL),
(3, 'C', 'Superior', 1, 'Saas', 1, 3, 8, 6, '2025-10-24 00:48:35', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_price_lists`
--

CREATE TABLE `vnt_price_lists` (
  `id` int NOT NULL,
  `title` varchar(10) NOT NULL,
  `value` float NOT NULL,
  `status` int NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_price_profiles`
--

CREATE TABLE `vnt_price_profiles` (
  `id` int NOT NULL,
  `price` int NOT NULL,
  `profile` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_tenants`
--

CREATE TABLE `vnt_tenants` (
  `id` int NOT NULL,
  `companyId` int NOT NULL DEFAULT '0',
  `merchantTypeId` int DEFAULT NULL,
  `plainId` int DEFAULT NULL,
  `afiliationDate` datetime DEFAULT NULL,
  `renovationDate` datetime DEFAULT NULL,
  `endTest` date DEFAULT NULL,
  `tenantCode` text,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vnt_terms`
--

CREATE TABLE `vnt_terms` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `days` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT (now()),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cfg_positions`
--
ALTER TABLE `cfg_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cnf_audit_status_log`
--
ALTER TABLE `cnf_audit_status_log`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cnf_cities`
--
ALTER TABLE `cnf_cities`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cnf_fiscal_responsabilities`
--
ALTER TABLE `cnf_fiscal_responsabilities`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cnf_regime`
--
ALTER TABLE `cnf_regime`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cnf_type_identifications`
--
ALTER TABLE `cnf_type_identifications`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usr_permissions`
--
ALTER TABLE `usr_permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usr_permissions_profiles`
--
ALTER TABLE `usr_permissions_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profileId` (`profileId`),
  ADD KEY `permissionId` (`permissionId`);

--
-- Indices de la tabla `usr_profiles`
--
ALTER TABLE `usr_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usr_users`
--
ALTER TABLE `usr_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profileId` (`profileId`),
  ADD KEY `tenantId` (`tenantId`),
  ADD KEY `loginWarehouseId` (`loginWarehouseId`);

--
-- Indices de la tabla `vnt_companies`
--
ALTER TABLE `vnt_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identification` (`identification`),
  ADD KEY `typeIdentificationId` (`typeIdentificationId`),
  ADD KEY `regimeId` (`regimeId`),
  ADD KEY `fiscalResponsabilityId` (`fiscalResponsabilityId`);

--
-- Indices de la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouseId` (`warehouseId`),
  ADD KEY `positionId` (`positionId`);

--
-- Indices de la tabla `vnt_merchant_moduls`
--
ALTER TABLE `vnt_merchant_moduls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchantId` (`merchantId`),
  ADD KEY `modulId` (`modulId`);

--
-- Indices de la tabla `vnt_merchant_types`
--
ALTER TABLE `vnt_merchant_types`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vnt_moduls`
--
ALTER TABLE `vnt_moduls`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vnt_plains`
--
ALTER TABLE `vnt_plains`
  ADD PRIMARY KEY (`id`),
  ADD KEY `merchantTypeId` (`merchantTypeId`) USING BTREE;

--
-- Indices de la tabla `vnt_price_lists`
--
ALTER TABLE `vnt_price_lists`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vnt_price_profiles`
--
ALTER TABLE `vnt_price_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `vnt_tenants`
--
ALTER TABLE `vnt_tenants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `companyId` (`companyId`),
  ADD KEY `merchantTypeId` (`merchantTypeId`),
  ADD KEY `plainId` (`plainId`);

--
-- Indices de la tabla `vnt_terms`
--
ALTER TABLE `vnt_terms`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT de la tabla `cfg_positions`
--
ALTER TABLE `cfg_positions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cnf_audit_status_log`
--
ALTER TABLE `cnf_audit_status_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cnf_cities`
--
ALTER TABLE `cnf_cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cnf_fiscal_responsabilities`
--
ALTER TABLE `cnf_fiscal_responsabilities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cnf_regime`
--
ALTER TABLE `cnf_regime`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cnf_type_identifications`
--
ALTER TABLE `cnf_type_identifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usr_permissions`
--
ALTER TABLE `usr_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `usr_permissions_profiles`
--
ALTER TABLE `usr_permissions_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usr_profiles`
--
ALTER TABLE `usr_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usr_users`
--
ALTER TABLE `usr_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_companies`
--
ALTER TABLE `vnt_companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_merchant_moduls`
--
ALTER TABLE `vnt_merchant_moduls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_merchant_types`
--
ALTER TABLE `vnt_merchant_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `vnt_moduls`
--
ALTER TABLE `vnt_moduls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `vnt_plains`
--
ALTER TABLE `vnt_plains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `vnt_price_lists`
--
ALTER TABLE `vnt_price_lists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_price_profiles`
--
ALTER TABLE `vnt_price_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_tenants`
--
ALTER TABLE `vnt_tenants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_terms`
--
ALTER TABLE `vnt_terms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vnt_warehouses`
--
ALTER TABLE `vnt_warehouses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `usr_permissions_profiles`
--
ALTER TABLE `usr_permissions_profiles`
  ADD CONSTRAINT `usr_permissions_profiles_ibfk_1` FOREIGN KEY (`profileId`) REFERENCES `usr_profiles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `usr_permissions_profiles_ibfk_2` FOREIGN KEY (`permissionId`) REFERENCES `usr_permissions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `vnt_companies`
--
ALTER TABLE `vnt_companies`
  ADD CONSTRAINT `vnt_companies_ibfk_1` FOREIGN KEY (`typeIdentificationId`) REFERENCES `cnf_type_identifications` (`id`),
  ADD CONSTRAINT `vnt_companies_ibfk_2` FOREIGN KEY (`regimeId`) REFERENCES `cnf_regime` (`id`),
  ADD CONSTRAINT `vnt_companies_ibfk_3` FOREIGN KEY (`fiscalResponsabilityId`) REFERENCES `cnf_fiscal_responsabilities` (`id`);

--
-- Filtros para la tabla `vnt_contacts`
--
ALTER TABLE `vnt_contacts`
  ADD CONSTRAINT `vnt_contacts_ibfk_1` FOREIGN KEY (`warehouseId`) REFERENCES `vnt_warehouses` (`id`),
  ADD CONSTRAINT `vnt_contacts_ibfk_2` FOREIGN KEY (`positionId`) REFERENCES `cfg_positions` (`id`);

--
-- Filtros para la tabla `vnt_merchant_moduls`
--
ALTER TABLE `vnt_merchant_moduls`
  ADD CONSTRAINT `vnt_merchant_moduls_ibfk_1` FOREIGN KEY (`merchantId`) REFERENCES `vnt_merchant_types` (`id`),
  ADD CONSTRAINT `vnt_merchant_moduls_ibfk_2` FOREIGN KEY (`modulId`) REFERENCES `vnt_moduls` (`id`);

--
-- Filtros para la tabla `vnt_plains`
--
ALTER TABLE `vnt_plains`
  ADD CONSTRAINT `vnt_plains_ibfk_1` FOREIGN KEY (`merchantTypeId`) REFERENCES `vnt_merchant_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `vnt_tenants`
--
ALTER TABLE `vnt_tenants`
  ADD CONSTRAINT `vnt_tenants_ibfk_1` FOREIGN KEY (`companyId`) REFERENCES `vnt_companies` (`id`),
  ADD CONSTRAINT `vnt_tenants_ibfk_2` FOREIGN KEY (`merchantTypeId`) REFERENCES `vnt_merchant_types` (`id`),
  ADD CONSTRAINT `vnt_tenants_ibfk_3` FOREIGN KEY (`plainId`) REFERENCES `vnt_plains` (`id`);

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
