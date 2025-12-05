-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: mysql:3306
-- Tiempo de generación: 29-10-2025 a las 14:14:25
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
-- Estructura de tabla para la tabla `countries`
--

CREATE TABLE `countries` (
  `id` bigint UNSIGNED NOT NULL,
  `iso2` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `phone_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso3` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subregion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `countries`
--

INSERT INTO `countries` (`id`, `iso2`, `name`, `status`, `phone_code`, `iso3`, `region`, `subregion`) VALUES
(1, 'AF', 'Afghanistan', 1, '93', 'AFG', 'Asia', 'Southern Asia'),
(2, 'AX', 'Aland Islands', 1, '358', 'ALA', 'Europe', 'Northern Europe'),
(3, 'AL', 'Albania', 1, '355', 'ALB', 'Europe', 'Southern Europe'),
(4, 'DZ', 'Algeria', 1, '213', 'DZA', 'Africa', 'Northern Africa'),
(5, 'AS', 'American Samoa', 1, '1684', 'ASM', 'Oceania', 'Polynesia'),
(6, 'AD', 'Andorra', 1, '376', 'AND', 'Europe', 'Southern Europe'),
(7, 'AO', 'Angola', 1, '244', 'AGO', 'Africa', 'Middle Africa'),
(8, 'AI', 'Anguilla', 1, '1', 'AIA', 'Americas', 'Caribbean'),
(9, 'AQ', 'Antarctica', 1, '672', 'ATA', 'Polar', ''),
(10, 'AG', 'Antigua And Barbuda', 1, '1', 'ATG', 'Americas', 'Caribbean'),
(11, 'AR', 'Argentina', 1, '54', 'ARG', 'Americas', 'South America'),
(12, 'AM', 'Armenia', 1, '374', 'ARM', 'Asia', 'Western Asia'),
(13, 'AW', 'Aruba', 1, '297', 'ABW', 'Americas', 'Caribbean'),
(14, 'AU', 'Australia', 1, '61', 'AUS', 'Oceania', 'Australia and New Zealand'),
(15, 'AT', 'Austria', 1, '43', 'AUT', 'Europe', 'Western Europe'),
(16, 'AZ', 'Azerbaijan', 1, '994', 'AZE', 'Asia', 'Western Asia'),
(17, 'BH', 'Bahrain', 1, '973', 'BHR', 'Asia', 'Western Asia'),
(18, 'BD', 'Bangladesh', 1, '880', 'BGD', 'Asia', 'Southern Asia'),
(19, 'BB', 'Barbados', 1, '1', 'BRB', 'Americas', 'Caribbean'),
(20, 'BY', 'Belarus', 1, '375', 'BLR', 'Europe', 'Eastern Europe'),
(21, 'BE', 'Belgium', 1, '32', 'BEL', 'Europe', 'Western Europe'),
(22, 'BZ', 'Belize', 1, '501', 'BLZ', 'Americas', 'Central America'),
(23, 'BJ', 'Benin', 1, '229', 'BEN', 'Africa', 'Western Africa'),
(24, 'BM', 'Bermuda', 1, '1', 'BMU', 'Americas', 'Northern America'),
(25, 'BT', 'Bhutan', 1, '975', 'BTN', 'Asia', 'Southern Asia'),
(26, 'BO', 'Bolivia', 1, '591', 'BOL', 'Americas', 'South America'),
(27, 'BQ', 'Bonaire, Sint Eustatius and Saba', 1, '599', 'BES', 'Americas', 'Caribbean'),
(28, 'BA', 'Bosnia and Herzegovina', 1, '387', 'BIH', 'Europe', 'Southern Europe'),
(29, 'BW', 'Botswana', 1, '267', 'BWA', 'Africa', 'Southern Africa'),
(30, 'BV', 'Bouvet Island', 1, '47', 'BVT', '', ''),
(31, 'BR', 'Brazil', 1, '55', 'BRA', 'Americas', 'South America'),
(32, 'IO', 'British Indian Ocean Territory', 1, '246', 'IOT', 'Africa', 'Eastern Africa'),
(33, 'BN', 'Brunei', 1, '673', 'BRN', 'Asia', 'South-Eastern Asia'),
(34, 'BG', 'Bulgaria', 1, '359', 'BGR', 'Europe', 'Eastern Europe'),
(35, 'BF', 'Burkina Faso', 1, '226', 'BFA', 'Africa', 'Western Africa'),
(36, 'BI', 'Burundi', 1, '257', 'BDI', 'Africa', 'Eastern Africa'),
(37, 'KH', 'Cambodia', 1, '855', 'KHM', 'Asia', 'South-Eastern Asia'),
(38, 'CM', 'Cameroon', 1, '237', 'CMR', 'Africa', 'Middle Africa'),
(39, 'CA', 'Canada', 1, '1', 'CAN', 'Americas', 'Northern America'),
(40, 'CV', 'Cape Verde', 1, '238', 'CPV', 'Africa', 'Western Africa'),
(41, 'KY', 'Cayman Islands', 1, '1', 'CYM', 'Americas', 'Caribbean'),
(42, 'CF', 'Central African Republic', 1, '236', 'CAF', 'Africa', 'Middle Africa'),
(43, 'TD', 'Chad', 1, '235', 'TCD', 'Africa', 'Middle Africa'),
(44, 'CL', 'Chile', 1, '56', 'CHL', 'Americas', 'South America'),
(45, 'CN', 'China', 1, '86', 'CHN', 'Asia', 'Eastern Asia'),
(46, 'CX', 'Christmas Island', 1, '61', 'CXR', 'Oceania', 'Australia and New Zealand'),
(47, 'CC', 'Cocos (Keeling) Islands', 1, '61', 'CCK', 'Oceania', 'Australia and New Zealand'),
(48, 'CO', 'Colombia', 1, '57', 'COL', 'Americas', 'South America'),
(49, 'KM', 'Comoros', 1, '269', 'COM', 'Africa', 'Eastern Africa'),
(50, 'CG', 'Congo', 1, '242', 'COG', 'Africa', 'Middle Africa'),
(51, 'CK', 'Cook Islands', 1, '682', 'COK', 'Oceania', 'Polynesia'),
(52, 'CR', 'Costa Rica', 1, '506', 'CRI', 'Americas', 'Central America'),
(53, 'CI', 'Cote D\'Ivoire (Ivory Coast)', 1, '225', 'CIV', 'Africa', 'Western Africa'),
(54, 'HR', 'Croatia', 1, '385', 'HRV', 'Europe', 'Southern Europe'),
(55, 'CU', 'Cuba', 1, '53', 'CUB', 'Americas', 'Caribbean'),
(56, 'CW', 'Curaçao', 1, '599', 'CUW', 'Americas', 'Caribbean'),
(57, 'CY', 'Cyprus', 1, '357', 'CYP', 'Europe', 'Southern Europe'),
(58, 'CZ', 'Czech Republic', 1, '420', 'CZE', 'Europe', 'Eastern Europe'),
(59, 'CD', 'Democratic Republic of the Congo', 1, '243', 'COD', 'Africa', 'Middle Africa'),
(60, 'DK', 'Denmark', 1, '45', 'DNK', 'Europe', 'Northern Europe'),
(61, 'DJ', 'Djibouti', 1, '253', 'DJI', 'Africa', 'Eastern Africa'),
(62, 'DM', 'Dominica', 1, '1', 'DMA', 'Americas', 'Caribbean'),
(63, 'DO', 'Dominican Republic', 1, '1', 'DOM', 'Americas', 'Caribbean'),
(64, 'TL', 'East Timor', 1, '670', 'TLS', 'Asia', 'South-Eastern Asia'),
(65, 'EC', 'Ecuador', 1, '593', 'ECU', 'Americas', 'South America'),
(66, 'EG', 'Egypt', 1, '20', 'EGY', 'Africa', 'Northern Africa'),
(67, 'SV', 'El Salvador', 1, '503', 'SLV', 'Americas', 'Central America'),
(68, 'GQ', 'Equatorial Guinea', 1, '240', 'GNQ', 'Africa', 'Middle Africa'),
(69, 'ER', 'Eritrea', 1, '291', 'ERI', 'Africa', 'Eastern Africa'),
(70, 'EE', 'Estonia', 1, '372', 'EST', 'Europe', 'Northern Europe'),
(71, 'ET', 'Ethiopia', 1, '251', 'ETH', 'Africa', 'Eastern Africa'),
(72, 'FK', 'Falkland Islands', 1, '500', 'FLK', 'Americas', 'South America'),
(73, 'FO', 'Faroe Islands', 1, '298', 'FRO', 'Europe', 'Northern Europe'),
(74, 'FJ', 'Fiji Islands', 1, '679', 'FJI', 'Oceania', 'Melanesia'),
(75, 'FI', 'Finland', 1, '358', 'FIN', 'Europe', 'Northern Europe'),
(76, 'FR', 'France', 1, '33', 'FRA', 'Europe', 'Western Europe'),
(77, 'GF', 'French Guiana', 1, '594', 'GUF', 'Americas', 'South America'),
(78, 'PF', 'French Polynesia', 1, '689', 'PYF', 'Oceania', 'Polynesia'),
(79, 'TF', 'French Southern Territories', 1, '262', 'ATF', 'Africa', 'Southern Africa'),
(80, 'GA', 'Gabon', 1, '241', 'GAB', 'Africa', 'Middle Africa'),
(81, 'GM', 'Gambia The', 1, '220', 'GMB', 'Africa', 'Western Africa'),
(82, 'GE', 'Georgia', 1, '995', 'GEO', 'Asia', 'Western Asia'),
(83, 'DE', 'Germany', 1, '49', 'DEU', 'Europe', 'Western Europe'),
(84, 'GH', 'Ghana', 1, '233', 'GHA', 'Africa', 'Western Africa'),
(85, 'GI', 'Gibraltar', 1, '350', 'GIB', 'Europe', 'Southern Europe'),
(86, 'GR', 'Greece', 1, '30', 'GRC', 'Europe', 'Southern Europe'),
(87, 'GL', 'Greenland', 1, '299', 'GRL', 'Americas', 'Northern America'),
(88, 'GD', 'Grenada', 1, '1', 'GRD', 'Americas', 'Caribbean'),
(89, 'GP', 'Guadeloupe', 1, '590', 'GLP', 'Americas', 'Caribbean'),
(90, 'GU', 'Guam', 1, '1', 'GUM', 'Oceania', 'Micronesia'),
(91, 'GT', 'Guatemala', 1, '502', 'GTM', 'Americas', 'Central America'),
(92, 'GG', 'Guernsey and Alderney', 1, '44', 'GGY', 'Europe', 'Northern Europe'),
(93, 'GN', 'Guinea', 1, '224', 'GIN', 'Africa', 'Western Africa'),
(94, 'GW', 'Guinea-Bissau', 1, '245', 'GNB', 'Africa', 'Western Africa'),
(95, 'GY', 'Guyana', 1, '592', 'GUY', 'Americas', 'South America'),
(96, 'HT', 'Haiti', 1, '509', 'HTI', 'Americas', 'Caribbean'),
(97, 'HM', 'Heard Island and McDonald Islands', 1, '672', 'HMD', '', ''),
(98, 'HN', 'Honduras', 1, '504', 'HND', 'Americas', 'Central America'),
(99, 'HK', 'Hong Kong S.A.R.', 1, '852', 'HKG', 'Asia', 'Eastern Asia'),
(100, 'HU', 'Hungary', 1, '36', 'HUN', 'Europe', 'Eastern Europe'),
(101, 'IS', 'Iceland', 1, '354', 'ISL', 'Europe', 'Northern Europe'),
(102, 'IN', 'India', 1, '91', 'IND', 'Asia', 'Southern Asia'),
(103, 'ID', 'Indonesia', 1, '62', 'IDN', 'Asia', 'South-Eastern Asia'),
(104, 'IR', 'Iran', 1, '98', 'IRN', 'Asia', 'Southern Asia'),
(105, 'IQ', 'Iraq', 1, '964', 'IRQ', 'Asia', 'Western Asia'),
(106, 'IE', 'Ireland', 1, '353', 'IRL', 'Europe', 'Northern Europe'),
(107, 'IL', 'Israel', 1, '972', 'ISR', 'Asia', 'Western Asia'),
(108, 'IT', 'Italy', 1, '39', 'ITA', 'Europe', 'Southern Europe'),
(109, 'JM', 'Jamaica', 1, '1', 'JAM', 'Americas', 'Caribbean'),
(110, 'JP', 'Japan', 1, '81', 'JPN', 'Asia', 'Eastern Asia'),
(111, 'JE', 'Jersey', 1, '44', 'JEY', 'Europe', 'Northern Europe'),
(112, 'JO', 'Jordan', 1, '962', 'JOR', 'Asia', 'Western Asia'),
(113, 'KZ', 'Kazakhstan', 1, '7', 'KAZ', 'Asia', 'Central Asia'),
(114, 'KE', 'Kenya', 1, '254', 'KEN', 'Africa', 'Eastern Africa'),
(115, 'KI', 'Kiribati', 1, '686', 'KIR', 'Oceania', 'Micronesia'),
(116, 'XK', 'Kosovo', 1, '383', 'XKX', 'Europe', 'Eastern Europe'),
(117, 'KW', 'Kuwait', 1, '965', 'KWT', 'Asia', 'Western Asia'),
(118, 'KG', 'Kyrgyzstan', 1, '996', 'KGZ', 'Asia', 'Central Asia'),
(119, 'LA', 'Laos', 1, '856', 'LAO', 'Asia', 'South-Eastern Asia'),
(120, 'LV', 'Latvia', 1, '371', 'LVA', 'Europe', 'Northern Europe'),
(121, 'LB', 'Lebanon', 1, '961', 'LBN', 'Asia', 'Western Asia'),
(122, 'LS', 'Lesotho', 1, '266', 'LSO', 'Africa', 'Southern Africa'),
(123, 'LR', 'Liberia', 1, '231', 'LBR', 'Africa', 'Western Africa'),
(124, 'LY', 'Libya', 1, '218', 'LBY', 'Africa', 'Northern Africa'),
(125, 'LI', 'Liechtenstein', 1, '423', 'LIE', 'Europe', 'Western Europe'),
(126, 'LT', 'Lithuania', 1, '370', 'LTU', 'Europe', 'Northern Europe'),
(127, 'LU', 'Luxembourg', 1, '352', 'LUX', 'Europe', 'Western Europe'),
(128, 'MO', 'Macau S.A.R.', 1, '853', 'MAC', 'Asia', 'Eastern Asia'),
(129, 'MK', 'Macedonia', 1, '389', 'MKD', 'Europe', 'Southern Europe'),
(130, 'MG', 'Madagascar', 1, '261', 'MDG', 'Africa', 'Eastern Africa'),
(131, 'MW', 'Malawi', 1, '265', 'MWI', 'Africa', 'Eastern Africa'),
(132, 'MY', 'Malaysia', 1, '60', 'MYS', 'Asia', 'South-Eastern Asia'),
(133, 'MV', 'Maldives', 1, '960', 'MDV', 'Asia', 'Southern Asia'),
(134, 'ML', 'Mali', 1, '223', 'MLI', 'Africa', 'Western Africa'),
(135, 'MT', 'Malta', 1, '356', 'MLT', 'Europe', 'Southern Europe'),
(136, 'IM', 'Man (Isle of)', 1, '44', 'IMN', 'Europe', 'Northern Europe'),
(137, 'MH', 'Marshall Islands', 1, '692', 'MHL', 'Oceania', 'Micronesia'),
(138, 'MQ', 'Martinique', 1, '596', 'MTQ', 'Americas', 'Caribbean'),
(139, 'MR', 'Mauritania', 1, '222', 'MRT', 'Africa', 'Western Africa'),
(140, 'MU', 'Mauritius', 1, '230', 'MUS', 'Africa', 'Eastern Africa'),
(141, 'YT', 'Mayotte', 1, '262', 'MYT', 'Africa', 'Eastern Africa'),
(142, 'MX', 'Mexico', 1, '52', 'MEX', 'Americas', 'Central America'),
(143, 'FM', 'Micronesia', 1, '691', 'FSM', 'Oceania', 'Micronesia'),
(144, 'MD', 'Moldova', 1, '373', 'MDA', 'Europe', 'Eastern Europe'),
(145, 'MC', 'Monaco', 1, '377', 'MCO', 'Europe', 'Western Europe'),
(146, 'MN', 'Mongolia', 1, '976', 'MNG', 'Asia', 'Eastern Asia'),
(147, 'ME', 'Montenegro', 1, '382', 'MNE', 'Europe', 'Southern Europe'),
(148, 'MS', 'Montserrat', 1, '1', 'MSR', 'Americas', 'Caribbean'),
(149, 'MA', 'Morocco', 1, '212', 'MAR', 'Africa', 'Northern Africa'),
(150, 'MZ', 'Mozambique', 1, '258', 'MOZ', 'Africa', 'Eastern Africa'),
(151, 'MM', 'Myanmar', 1, '95', 'MMR', 'Asia', 'South-Eastern Asia'),
(152, 'NA', 'Namibia', 1, '264', 'NAM', 'Africa', 'Southern Africa'),
(153, 'NR', 'Nauru', 1, '674', 'NRU', 'Oceania', 'Micronesia'),
(154, 'NP', 'Nepal', 1, '977', 'NPL', 'Asia', 'Southern Asia'),
(155, 'NL', 'Netherlands', 1, '31', 'NLD', 'Europe', 'Western Europe'),
(156, 'NC', 'New Caledonia', 1, '687', 'NCL', 'Oceania', 'Melanesia'),
(157, 'NZ', 'New Zealand', 1, '64', 'NZL', 'Oceania', 'Australia and New Zealand'),
(158, 'NI', 'Nicaragua', 1, '505', 'NIC', 'Americas', 'Central America'),
(159, 'NE', 'Niger', 1, '227', 'NER', 'Africa', 'Western Africa'),
(160, 'NG', 'Nigeria', 1, '234', 'NGA', 'Africa', 'Western Africa'),
(161, 'NU', 'Niue', 1, '683', 'NIU', 'Oceania', 'Polynesia'),
(162, 'NF', 'Norfolk Island', 1, '672', 'NFK', 'Oceania', 'Australia and New Zealand'),
(163, 'KP', 'North Korea', 1, '850', 'PRK', 'Asia', 'Eastern Asia'),
(164, 'MP', 'Northern Mariana Islands', 1, '1', 'MNP', 'Oceania', 'Micronesia'),
(165, 'NO', 'Norway', 1, '47', 'NOR', 'Europe', 'Northern Europe'),
(166, 'OM', 'Oman', 1, '968', 'OMN', 'Asia', 'Western Asia'),
(167, 'PK', 'Pakistan', 1, '92', 'PAK', 'Asia', 'Southern Asia'),
(168, 'PW', 'Palau', 1, '680', 'PLW', 'Oceania', 'Micronesia'),
(169, 'PS', 'Palestinian Territory Occupied', 1, '970', 'PSE', 'Asia', 'Western Asia'),
(170, 'PA', 'Panama', 1, '507', 'PAN', 'Americas', 'Central America'),
(171, 'PG', 'Papua new Guinea', 1, '675', 'PNG', 'Oceania', 'Melanesia'),
(172, 'PY', 'Paraguay', 1, '595', 'PRY', 'Americas', 'South America'),
(173, 'PE', 'Peru', 1, '51', 'PER', 'Americas', 'South America'),
(174, 'PH', 'Philippines', 1, '63', 'PHL', 'Asia', 'South-Eastern Asia'),
(175, 'PN', 'Pitcairn Island', 1, '870', 'PCN', 'Oceania', 'Polynesia'),
(176, 'PL', 'Poland', 1, '48', 'POL', 'Europe', 'Eastern Europe'),
(177, 'PT', 'Portugal', 1, '351', 'PRT', 'Europe', 'Southern Europe'),
(178, 'PR', 'Puerto Rico', 1, '1', 'PRI', 'Americas', 'Caribbean'),
(179, 'QA', 'Qatar', 1, '974', 'QAT', 'Asia', 'Western Asia'),
(180, 'RE', 'Reunion', 1, '262', 'REU', 'Africa', 'Eastern Africa'),
(181, 'RO', 'Romania', 1, '40', 'ROU', 'Europe', 'Eastern Europe'),
(182, 'RU', 'Russia', 1, '7', 'RUS', 'Europe', 'Eastern Europe'),
(183, 'RW', 'Rwanda', 1, '250', 'RWA', 'Africa', 'Eastern Africa'),
(184, 'SH', 'Saint Helena', 1, '290', 'SHN', 'Africa', 'Western Africa'),
(185, 'KN', 'Saint Kitts And Nevis', 1, '1', 'KNA', 'Americas', 'Caribbean'),
(186, 'LC', 'Saint Lucia', 1, '1', 'LCA', 'Americas', 'Caribbean'),
(187, 'PM', 'Saint Pierre and Miquelon', 1, '508', 'SPM', 'Americas', 'Northern America'),
(188, 'VC', 'Saint Vincent And The Grenadines', 1, '1', 'VCT', 'Americas', 'Caribbean'),
(189, 'BL', 'Saint-Barthelemy', 1, '590', 'BLM', 'Americas', 'Caribbean'),
(190, 'MF', 'Saint-Martin (French part)', 1, '590', 'MAF', 'Americas', 'Caribbean'),
(191, 'WS', 'Samoa', 1, '685', 'WSM', 'Oceania', 'Polynesia'),
(192, 'SM', 'San Marino', 1, '378', 'SMR', 'Europe', 'Southern Europe'),
(193, 'ST', 'Sao Tome and Principe', 1, '239', 'STP', 'Africa', 'Middle Africa'),
(194, 'SA', 'Saudi Arabia', 1, '966', 'SAU', 'Asia', 'Western Asia'),
(195, 'SN', 'Senegal', 1, '221', 'SEN', 'Africa', 'Western Africa'),
(196, 'RS', 'Serbia', 1, '381', 'SRB', 'Europe', 'Southern Europe'),
(197, 'SC', 'Seychelles', 1, '248', 'SYC', 'Africa', 'Eastern Africa'),
(198, 'SL', 'Sierra Leone', 1, '232', 'SLE', 'Africa', 'Western Africa'),
(199, 'SG', 'Singapore', 1, '65', 'SGP', 'Asia', 'South-Eastern Asia'),
(200, 'SX', 'Sint Maarten (Dutch part)', 1, '1721', 'SXM', 'Americas', 'Caribbean'),
(201, 'SK', 'Slovakia', 1, '421', 'SVK', 'Europe', 'Eastern Europe'),
(202, 'SI', 'Slovenia', 1, '386', 'SVN', 'Europe', 'Southern Europe'),
(203, 'SB', 'Solomon Islands', 1, '677', 'SLB', 'Oceania', 'Melanesia'),
(204, 'SO', 'Somalia', 1, '252', 'SOM', 'Africa', 'Eastern Africa'),
(205, 'ZA', 'South Africa', 1, '27', 'ZAF', 'Africa', 'Southern Africa'),
(206, 'GS', 'South Georgia', 1, '500', 'SGS', 'Americas', 'South America'),
(207, 'KR', 'South Korea', 1, '82', 'KOR', 'Asia', 'Eastern Asia'),
(208, 'SS', 'South Sudan', 1, '211', 'SSD', 'Africa', 'Middle Africa'),
(209, 'ES', 'Spain', 1, '34', 'ESP', 'Europe', 'Southern Europe'),
(210, 'LK', 'Sri Lanka', 1, '94', 'LKA', 'Asia', 'Southern Asia'),
(211, 'SD', 'Sudan', 1, '249', 'SDN', 'Africa', 'Northern Africa'),
(212, 'SR', 'Suriname', 1, '597', 'SUR', 'Americas', 'South America'),
(213, 'SJ', 'Svalbard And Jan Mayen Islands', 1, '47', 'SJM', 'Europe', 'Northern Europe'),
(214, 'SZ', 'Swaziland', 1, '268', 'SWZ', 'Africa', 'Southern Africa'),
(215, 'SE', 'Sweden', 1, '46', 'SWE', 'Europe', 'Northern Europe'),
(216, 'CH', 'Switzerland', 1, '41', 'CHE', 'Europe', 'Western Europe'),
(217, 'SY', 'Syria', 1, '963', 'SYR', 'Asia', 'Western Asia'),
(218, 'TW', 'Taiwan', 1, '886', 'TWN', 'Asia', 'Eastern Asia'),
(219, 'TJ', 'Tajikistan', 1, '992', 'TJK', 'Asia', 'Central Asia'),
(220, 'TZ', 'Tanzania', 1, '255', 'TZA', 'Africa', 'Eastern Africa'),
(221, 'TH', 'Thailand', 1, '66', 'THA', 'Asia', 'South-Eastern Asia'),
(222, 'BS', 'The Bahamas', 1, '1', 'BHS', 'Americas', 'Caribbean'),
(223, 'TG', 'Togo', 1, '228', 'TGO', 'Africa', 'Western Africa'),
(224, 'TK', 'Tokelau', 1, '690', 'TKL', 'Oceania', 'Polynesia'),
(225, 'TO', 'Tonga', 1, '676', 'TON', 'Oceania', 'Polynesia'),
(226, 'TT', 'Trinidad And Tobago', 1, '1', 'TTO', 'Americas', 'Caribbean'),
(227, 'TN', 'Tunisia', 1, '216', 'TUN', 'Africa', 'Northern Africa'),
(228, 'TR', 'Turkey', 1, '90', 'TUR', 'Asia', 'Western Asia'),
(229, 'TM', 'Turkmenistan', 1, '993', 'TKM', 'Asia', 'Central Asia'),
(230, 'TC', 'Turks And Caicos Islands', 1, '1', 'TCA', 'Americas', 'Caribbean'),
(231, 'TV', 'Tuvalu', 1, '688', 'TUV', 'Oceania', 'Polynesia'),
(232, 'UG', 'Uganda', 1, '256', 'UGA', 'Africa', 'Eastern Africa'),
(233, 'UA', 'Ukraine', 1, '380', 'UKR', 'Europe', 'Eastern Europe'),
(234, 'AE', 'United Arab Emirates', 1, '971', 'ARE', 'Asia', 'Western Asia'),
(235, 'GB', 'United Kingdom', 1, '44', 'GBR', 'Europe', 'Northern Europe'),
(236, 'US', 'United States', 1, '1', 'USA', 'Americas', 'Northern America'),
(237, 'UM', 'United States Minor Outlying Islands', 1, '1', 'UMI', 'Americas', 'Northern America'),
(238, 'UY', 'Uruguay', 1, '598', 'URY', 'Americas', 'South America'),
(239, 'UZ', 'Uzbekistan', 1, '998', 'UZB', 'Asia', 'Central Asia'),
(240, 'VU', 'Vanuatu', 1, '678', 'VUT', 'Oceania', 'Melanesia'),
(241, 'VA', 'Vatican City State (Holy See)', 1, '379', 'VAT', 'Europe', 'Southern Europe'),
(242, 'VE', 'Venezuela', 1, '58', 'VEN', 'Americas', 'South America'),
(243, 'VN', 'Vietnam', 1, '84', 'VNM', 'Asia', 'South-Eastern Asia'),
(244, 'VG', 'Virgin Islands (British)', 1, '1', 'VGB', 'Americas', 'Caribbean'),
(245, 'VI', 'Virgin Islands (US)', 1, '1', 'VIR', 'Americas', 'Caribbean'),
(246, 'WF', 'Wallis And Futuna Islands', 1, '681', 'WLF', 'Oceania', 'Polynesia'),
(247, 'EH', 'Western Sahara', 1, '212', 'ESH', 'Africa', 'Northern Africa'),
(248, 'YE', 'Yemen', 1, '967', 'YEM', 'Asia', 'Western Asia'),
(249, 'ZM', 'Zambia', 1, '260', 'ZMB', 'Africa', 'Eastern Africa'),
(250, 'ZW', 'Zimbabwe', 1, '263', 'ZWE', 'Africa', 'Eastern Africa');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
