-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 20-04-2026 a las 17:33:45
-- Versión del servidor: 5.7.44-48
-- Versión de PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `smartagr_db_dev`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producer_segment_prices`
--

CREATE TABLE `producer_segment_prices` (
  `id` int(11) NOT NULL,
  `id_plan` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `data` text,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `producer_segment_prices`
--

INSERT INTO `producer_segment_prices` (`id`, `id_plan`, `status_id`, `id_user`, `data`, `month`, `year`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11\",\"current_year_value\":\"10\",\"id_plan\":1,\"date\":\"01/10/2024\"},{\"classification_id\":16,\"last_year_value\":\"37\",\"current_year_value\":\"47\",\"id_plan\":1,\"date\":\"01/10/2024\"},{\"classification_id\":41,\"last_year_value\":\"35\",\"current_year_value\":\"31\",\"id_plan\":1,\"date\":\"01/10/2024\"},{\"classification_id\":43,\"last_year_value\":\"124\",\"current_year_value\":\"103\",\"id_plan\":1,\"date\":\"01/10/2024\"},{\"classification_id\":42,\"last_year_value\":\"18\",\"current_year_value\":\"18\",\"id_plan\":1,\"date\":\"01/10/2024\"}]}', 10, 2024, '2024-11-14 08:43:15', '2024-11-14 08:43:15', NULL),
(2, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"12\",\"current_year_value\":\"10\",\"id_plan\":1,\"date\":\"01/11/2024\"},{\"classification_id\":16,\"last_year_value\":\"48\",\"current_year_value\":\"45\",\"id_plan\":1,\"date\":\"01/11/2024\"},{\"classification_id\":41,\"last_year_value\":\"31\",\"current_year_value\":\"29\",\"id_plan\":1,\"date\":\"01/11/2024\"},{\"classification_id\":43,\"last_year_value\":\"71\",\"current_year_value\":\"56\",\"id_plan\":1,\"date\":\"01/11/2024\"},{\"classification_id\":42,\"last_year_value\":\"22\",\"current_year_value\":\"21\",\"id_plan\":1,\"date\":\"01/11/2024\"}]}', 11, 2024, '2024-12-10 07:44:55', '2024-12-10 07:44:55', NULL),
(3, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"10.5\",\"current_year_value\":\"8.3\",\"id_plan\":1,\"date\":\"01/12/2024\"},{\"classification_id\":16,\"last_year_value\":\"38.5\",\"current_year_value\":\"38.7\",\"id_plan\":1,\"date\":\"01/12/2024\"},{\"classification_id\":41,\"last_year_value\":\"30.5\",\"current_year_value\":\"27.6\",\"id_plan\":1,\"date\":\"01/12/2024\"},{\"classification_id\":43,\"last_year_value\":\"71\",\"current_year_value\":\"56\",\"id_plan\":1,\"date\":\"01/12/2024\"},{\"classification_id\":42,\"last_year_value\":\"19.9\",\"current_year_value\":\"16.7\",\"id_plan\":1,\"date\":\"01/12/2024\"}]}', 12, 2024, '2025-01-04 05:54:45', '2025-01-04 05:54:45', NULL),
(4, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11,7\",\"current_year_value\":\"9,2\",\"id_plan\":1,\"date\":\"01/01/2025\"},{\"classification_id\":16,\"last_year_value\":\"36,7\",\"current_year_value\":\"37,8\",\"id_plan\":1,\"date\":\"01/01/2025\"},{\"classification_id\":41,\"last_year_value\":\"35\",\"current_year_value\":\"30,2\",\"id_plan\":1,\"date\":\"01/01/2025\"},{\"classification_id\":43,\"last_year_value\":\"123,7\",\"current_year_value\":\"103\",\"id_plan\":1,\"date\":\"01/01/2025\"},{\"classification_id\":42,\"last_year_value\":\"3,5\",\"current_year_value\":\"3,5\",\"id_plan\":1,\"date\":\"01/01/2025\"}]}', 1, 2025, '2025-02-06 02:12:22', '2025-02-06 02:12:22', NULL),
(5, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.6\",\"current_year_value\":\"8.8\",\"id_plan\":1,\"date\":\"01/02/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"37.8\",\"id_plan\":1,\"date\":\"01/02/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"30.2\",\"id_plan\":1,\"date\":\"01/02/2025\"},{\"classification_id\":43,\"last_year_value\":\"123.7\",\"current_year_value\":\"100.2\",\"id_plan\":1,\"date\":\"01/02/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.5\",\"id_plan\":1,\"date\":\"01/02/2025\"}]}', 2, 2025, '2025-03-06 23:30:58', '2025-03-06 23:30:58', NULL),
(6, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.3\",\"current_year_value\":\"8.2\",\"id_plan\":1,\"date\":\"01/03/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"37.8\",\"id_plan\":1,\"date\":\"01/03/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"29.3\",\"id_plan\":1,\"date\":\"01/03/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.0\",\"current_year_value\":\"95.2\",\"id_plan\":1,\"date\":\"01/03/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.5\",\"id_plan\":1,\"date\":\"01/03/2025\"}]}', 3, 2025, '2025-04-08 21:43:32', '2025-04-08 21:43:32', NULL),
(7, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.2\",\"current_year_value\":\"8.1\",\"id_plan\":1,\"date\":\"01/04/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"37.8\",\"id_plan\":1,\"date\":\"01/04/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"29.2\",\"id_plan\":1,\"date\":\"01/04/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.0\",\"current_year_value\":\"95.2\",\"id_plan\":1,\"date\":\"01/04/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.5\",\"id_plan\":1,\"date\":\"01/04/2025\"}]}', 4, 2025, '2025-05-07 22:28:09', '2025-05-07 22:28:09', NULL),
(8, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.1\",\"current_year_value\":\"8.0\",\"id_plan\":1,\"date\":\"01/05/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"37.8\",\"id_plan\":1,\"date\":\"01/05/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"29.0\",\"id_plan\":1,\"date\":\"01/05/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.0\",\"current_year_value\":\"95.2\",\"id_plan\":1,\"date\":\"01/05/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.6\",\"id_plan\":1,\"date\":\"01/05/2025\"}]}', 5, 2025, '2025-06-06 02:05:34', '2025-06-06 02:05:34', NULL),
(9, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"23/24\",\"current_year\":\"24/25\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.0\",\"current_year_value\":\"8.0\",\"id_plan\":1,\"date\":\"01/06/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"36.8\",\"id_plan\":1,\"date\":\"01/06/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.1\",\"current_year_value\":\"28.8\",\"id_plan\":1,\"date\":\"01/06/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.0\",\"current_year_value\":\"95.2\",\"id_plan\":1,\"date\":\"01/06/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.6\",\"id_plan\":1,\"date\":\"01/06/2025\"}]}', 6, 2025, '2025-07-31 22:13:43', '2025-07-31 22:13:43', NULL),
(10, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.0\",\"current_year_value\":\"8.0\",\"id_plan\":1,\"date\":\"01/07/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"36.8\",\"id_plan\":1,\"date\":\"01/07/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"28.7\",\"id_plan\":1,\"date\":\"01/07/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.0\",\"current_year_value\":\"96.4\",\"id_plan\":1,\"date\":\"01/07/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.7\",\"id_plan\":1,\"date\":\"01/07/2025\"}]}', 7, 2025, '2025-08-08 20:24:22', '2025-08-08 20:24:22', NULL),
(11, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.0\",\"current_year_value\":\"8.0\",\"id_plan\":1,\"date\":\"01/08/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"37.6\",\"id_plan\":1,\"date\":\"01/08/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"28.6\",\"id_plan\":1,\"date\":\"01/08/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.1\",\"current_year_value\":\"96.1\",\"id_plan\":1,\"date\":\"01/08/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.7\",\"id_plan\":1,\"date\":\"01/08/2025\"}]}', 8, 2025, '2025-09-05 03:59:03', '2025-09-05 03:59:03', NULL),
(12, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"11.0\",\"current_year_value\":\"8.0\",\"id_plan\":1,\"date\":\"01/09/2025\"},{\"classification_id\":16,\"last_year_value\":\"36.7\",\"current_year_value\":\"37.6\",\"id_plan\":1,\"date\":\"01/09/2025\"},{\"classification_id\":41,\"last_year_value\":\"35.0\",\"current_year_value\":\"28.6\",\"id_plan\":1,\"date\":\"01/09/2025\"},{\"classification_id\":43,\"last_year_value\":\"122.1\",\"current_year_value\":\"96.1\",\"id_plan\":1,\"date\":\"01/09/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.0\",\"current_year_value\":\"17.7\",\"id_plan\":1,\"date\":\"01/09/2025\"}]}', 9, 2025, '2025-10-06 19:14:40', '2025-10-06 19:14:40', NULL),
(13, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"10.0\",\"current_year_value\":\"7.8\",\"id_plan\":1,\"date\":\"01/10/2025\"},{\"classification_id\":16,\"last_year_value\":\"37.1\",\"current_year_value\":\"37.0\",\"id_plan\":1,\"date\":\"01/10/2025\"},{\"classification_id\":41,\"last_year_value\":\"31.0\",\"current_year_value\":\"24.9\",\"id_plan\":1,\"date\":\"01/10/2025\"},{\"classification_id\":43,\"last_year_value\":\"102.6\",\"current_year_value\":\"87.4\",\"id_plan\":1,\"date\":\"01/10/2025\"},{\"classification_id\":42,\"last_year_value\":\"18.8\",\"current_year_value\":\"17.7\",\"id_plan\":1,\"date\":\"01/10/2025\"}]}', 10, 2025, '2025-11-08 03:31:53', '2025-11-08 03:31:53', NULL),
(14, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"10.6\",\"current_year_value\":\"9.0\",\"id_plan\":1,\"date\":\"01/11/2025\"},{\"classification_id\":16,\"last_year_value\":\"39.1\",\"current_year_value\":\"38.2\",\"id_plan\":1,\"date\":\"01/11/2025\"},{\"classification_id\":41,\"last_year_value\":\"29.0\",\"current_year_value\":\"26.7\",\"id_plan\":1,\"date\":\"01/11/2025\"},{\"classification_id\":43,\"last_year_value\":\"76.1\",\"current_year_value\":\"72.0\",\"id_plan\":1,\"date\":\"01/11/2025\"},{\"classification_id\":42,\"last_year_value\":\"17.3\",\"current_year_value\":\"15.3\",\"id_plan\":1,\"date\":\"01/11/2025\"}]}', 11, 2025, '2025-12-10 02:35:20', '2025-12-10 02:35:20', NULL),
(15, 1, 1, NULL, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"last_year_value\":\"10.7\",\"current_year_value\":\"9.6\",\"id_plan\":1,\"date\":\"01/12/2025\"},{\"classification_id\":16,\"last_year_value\":\"39.1\",\"current_year_value\":\"38.2\",\"id_plan\":1,\"date\":\"01/12/2025\"},{\"classification_id\":41,\"last_year_value\":\"30.1\",\"current_year_value\":\"27.1\",\"id_plan\":1,\"date\":\"01/12/2025\"},{\"classification_id\":43,\"last_year_value\":\"75.6\",\"current_year_value\":\"68.9\",\"id_plan\":1,\"date\":\"01/12/2025\"},{\"classification_id\":42,\"last_year_value\":\"17.3\",\"current_year_value\":\"15.1\",\"id_plan\":1,\"date\":\"01/12/2025\"}]}', 12, 2025, '2026-01-07 00:57:30', '2026-01-07 00:57:30', NULL),
(141, 1, 1, 25, '{\"series_name\":{\"last_year\":\"24/25\",\"current_year\":\"25/26\"},\"data\":[{\"classification_id\":15,\"current_year_value\":\"123\",\"last_year_value\":\"234\",\"id_plan\":1,\"date\":\"19/02/2026\"},{\"classification_id\":42,\"current_year_value\":\"111\",\"last_year_value\":\"333\",\"id_plan\":1,\"date\":\"19/02/2026\"},{\"classification_id\":16,\"current_year_value\":\"222\",\"last_year_value\":\"333\",\"id_plan\":1,\"date\":\"19/02/2026\"},{\"classification_id\":41,\"current_year_value\":\"234\",\"last_year_value\":\"111\",\"id_plan\":1,\"date\":\"19/02/2026\"},{\"classification_id\":43,\"current_year_value\":\"234\",\"last_year_value\":\"333\",\"id_plan\":1,\"date\":\"19/02/2026\"}]}', 1, 2026, '2026-02-20 10:27:00', '2026-02-20 10:27:00', NULL),
(142, 1, 1, 25, '{\"series_name\":{\"last_year\":\"24\\/25\",\"current_year\":\"25\\/26\"},\"data\":[{\"classification_id\":15,\"current_year_value\":\"111\",\"last_year_value\":\"222\",\"id_plan\":1,\"date\":\"25\\/02\\/2026\"},{\"classification_id\":16,\"current_year_value\":\"123\",\"last_year_value\":\"333\",\"id_plan\":1,\"date\":\"25\\/02\\/2026\"}]}', 2, 2026, '2026-02-25 22:07:53', '2026-02-25 22:08:22', '2026-02-25 22:08:22'),
(143, 1, 1, 24, '{\"series_name\":{\"last_year\":\"24\\/25\",\"current_year\":\"25\\/26\"},\"data\":[{\"classification_id\":42,\"current_year_value\":\"17,1\",\"last_year_value\":\"15\",\"id_plan\":1,\"date\":\"28\\/02\\/2026\"},{\"classification_id\":43,\"current_year_value\":\"39,5\",\"last_year_value\":\"36,8\",\"id_plan\":1,\"date\":\"28\\/02\\/2026\"},{\"classification_id\":16,\"current_year_value\":\"33,6\",\"last_year_value\":\"32,4\",\"id_plan\":1,\"date\":\"28\\/02\\/2026\"},{\"classification_id\":15,\"current_year_value\":\"12,4\",\"last_year_value\":\"10,9\",\"id_plan\":1,\"date\":\"28\\/02\\/2026\"},{\"classification_id\":41,\"current_year_value\":\"64,5\",\"last_year_value\":\"61,1\",\"id_plan\":1,\"date\":\"28\\/02\\/2026\"}]}', 2, 2026, '2026-03-05 22:59:33', '2026-03-05 23:24:07', NULL),
(144, 1, 1, 24, '{\"series_name\":{\"last_year\":\"24\\/25\",\"current_year\":\"25\\/26\"},\"data\":[{\"classification_id\":42,\"current_year_value\":\"16,3\",\"last_year_value\":\"14,9\",\"id_plan\":1,\"date\":\"08\\/04\\/2026\"},{\"classification_id\":43,\"current_year_value\":\"39,3\",\"last_year_value\":\"36,8\",\"id_plan\":1,\"date\":\"08\\/04\\/2026\"},{\"classification_id\":16,\"current_year_value\":\"33,2\",\"last_year_value\":\"32,7\",\"id_plan\":1,\"date\":\"08\\/04\\/2026\"},{\"classification_id\":15,\"current_year_value\":\"10,9\",\"last_year_value\":\"10,1\",\"id_plan\":1,\"date\":\"08\\/04\\/2026\"},{\"classification_id\":41,\"current_year_value\":\"58,5\",\"last_year_value\":\"57,1\",\"id_plan\":1,\"date\":\"08\\/04\\/2026\"}]}', 3, 2026, '2026-04-08 15:18:56', '2026-04-08 15:18:56', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `producer_segment_prices`
--
ALTER TABLE `producer_segment_prices`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD KEY `fk_producer_segment_prices_status` (`status_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `producer_segment_prices`
--
ALTER TABLE `producer_segment_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `producer_segment_prices`
--
ALTER TABLE `producer_segment_prices`
  ADD CONSTRAINT `fk_producer_segment_prices_status` FOREIGN KEY (`status_id`) REFERENCES `statuses_reports` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
