-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 01-04-2026 a las 21:16:23
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
-- Base de datos: `smartagr_db_prod`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `harvest_prices`
--

CREATE TABLE `harvest_prices` (
  `id` int(11) NOT NULL,
  `id_plan` int(11) DEFAULT NULL,
  `year` varchar(4) DEFAULT NULL,
  `month` varchar(10) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `data` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `harvest_prices`
--

INSERT INTO `harvest_prices` (`id`, `id_plan`, `year`, `month`, `status_id`, `data`, `created_at`, `updated_at`) VALUES
(1, 2, '2024', '06', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"183"},"soybean":{"crop_id":1,"value":"307"},"sunflower":{"crop_id":4,"value":"295"},"wheat":{"crop_id":3,"value":"240"}}}]}', '2025-06-06 21:13:56', '2025-06-06 21:13:56'),
(2, 2, '2024', '07', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"176"},"soybean":{"crop_id":1,"value":"293"},"sunflower":{"crop_id":4,"value":"310"},"wheat":{"crop_id":3,"value":"210"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(3, 2, '2024', '08', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"168"},"soybean":{"crop_id":1,"value":"280"},"sunflower":{"crop_id":4,"value":"310"},"wheat":{"crop_id":3,"value":"209"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(4, 2, '2024', '09', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"174"},"soybean":{"crop_id":1,"value":"289"},"sunflower":{"crop_id":4,"value":"310"},"wheat":{"crop_id":3,"value":"206"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(5, 2, '2024', '10', 1, '{"regions":[{{"region_id":5,"data":{"corn":{"crop_id":2,"value":"185"},"soybean":{"crop_id":1,"value":"301"},"sunflower":{"crop_id":4,"value":"340"},"wheat":{"crop_id":3,"value":"210"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(6, 2, '2024', '11', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"183"},"soybean":{"crop_id":1,"value":"284"},"sunflower":{"crop_id":4,"value":"380"},"wheat":{"crop_id":3,"value":"190"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(7, 2, '2024', '12', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"177"},"soybean":{"crop_id":1,"value":"275"},"sunflower":{"crop_id":4,"value":"360"},"wheat":{"crop_id":3,"value":"185"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(8, 2, '2025', '01', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"179"},"soybean":{"crop_id":1,"value":"270"},"sunflower":{"crop_id":4,"value":"320"},"wheat":{"crop_id":3,"value":"192"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(9, 2, '2025', '02', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"197"},"soybean":{"crop_id":1,"value":"293"},"sunflower":{"crop_id":4,"value":"330"},"wheat":{"crop_id":3,"value":"210"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(10, 2, '2025', '03', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"193"},"soybean":{"crop_id":1,"value":"293"},"sunflower":{"crop_id":4,"value":"330"},"wheat":{"crop_id":3,"value":"196"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(11, 2, '2025', '04', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"198"},"soybean":{"crop_id":1,"value":"294"},"sunflower":{"crop_id":4,"value":"305"},"wheat":{"crop_id":3,"value":"230"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(12, 2, '2025', '05', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"180"},"soybean":{"crop_id":1,"value":"281"},"sunflower":{"crop_id":4,"value":"325"},"wheat":{"crop_id":3,"value":"230"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(13, 2, '2025', '06', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"177"},"soybean":{"crop_id":1,"value":"285"},"sunflower":{"crop_id":4,"value":"300"},"wheat":{"crop_id":3,"value":"195"}}}]}', '2025-07-03 21:36:04', '2025-07-03 21:36:04'),
(14, 2, '2024', '01', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"183"},"soybean":{"crop_id":1,"value":"303"},"sunflower":{"crop_id":4,"value":"280"},"wheat":{"crop_id":3,"value":"200"}}}]}', '2026-02-09 19:40:20', '2026-02-09 19:40:20'),
(15, 2, '2024', '02', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"174"},"soybean":{"crop_id":1,"value":"290"},"sunflower":{"crop_id":4,"value":"300"},"wheat":{"crop_id":3,"value":"210"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(16, 2, '2024', '03', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"165"},"soybean":{"crop_id":1,"value":"280"},"sunflower":{"crop_id":4,"value":"290"},"wheat":{"crop_id":3,"value":"195"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(17, 2, '2024', '04', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"169"},"soybean":{"crop_id":1,"value":"279"},"sunflower":{"crop_id":4,"value":"280"},"wheat":{"crop_id":3,"value":"198"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(18, 2, '2024', '05', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"181"},"soybean":{"crop_id":1,"value":"299"},"sunflower":{"crop_id":4,"value":"280"},"wheat":{"crop_id":3,"value":"210"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(19, 2, '2025', '07', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"174"},"soybean":{"crop_id":1,"value":"283"},"sunflower":{"crop_id":4,"value":"300"},"wheat":{"crop_id":3,"value":"190"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(20, 2, '2025', '08', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"176"},"soybean":{"crop_id":1,"value":"292"},"sunflower":{"crop_id":4,"value":"310"},"wheat":{"crop_id":3,"value":"197"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(21, 2, '2025', '09', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"175"},"soybean":{"crop_id":1,"value":"296"},"sunflower":{"crop_id":4,"value":"325"},"wheat":{"crop_id":3,"value":"183"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(22, 2, '2025', '10', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"174"},"soybean":{"crop_id":1,"value":"312"},"sunflower":{"crop_id":4,"value":"315"},"wheat":{"crop_id":3,"value":"188"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(23, 2, '2025', '11', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"178"},"soybean":{"crop_id":1,"value":"317"},"sunflower":{"crop_id":4,"value":"325"},"wheat":{"crop_id":3,"value":"187"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(24, 2, '2025', '12', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"182"},"soybean":{"crop_id":1,"value":"326"},"sunflower":{"crop_id":4,"value":"340"},"wheat":{"crop_id":3,"value":"175"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(25, 2, '2026', '01', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"181"},"soybean":{"crop_id":1,"value":"317"},"sunflower":{"crop_id":4,"value":"325"},"wheat":{"crop_id":3,"value":"180"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14'),
(26, 2, '2026', '02', 1, '{"regions":[{"region_id":5,"data":{"corn":{"crop_id":2,"value":"182"},"soybean":{"crop_id":1,"value":"313"},"sunflower":{"crop_id":4,"value":"360"},"wheat":{"crop_id":3,"value":"181"}}}]}', '2026-03-06 23:00:14', '2026-03-06 23:00:14');
(30, 2, '2026', '03', 1, '{\"regions\":[{\"region_id\":5,\"data\":{\"corn\":{\"crop_id\":2,\"value\":\"184\"},\"soybean\":{\"crop_id\":1,\"value\":\"326\"},\"sunflower\":{\"crop_id\":4,\"value\":\"365\"},\"wheat\":{\"crop_id\":3,\"value\":\"183\"}}}]}', '2026-04-10 03:03:47', '2026-04-11 01:28:29');
