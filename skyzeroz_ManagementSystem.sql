-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 14-07-2026 a las 23:11:42
-- Versión del servidor: 10.6.18-MariaDB-cll-lve
-- Versión de PHP: 8.1.34

-- USE gestor_bd;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `skyzeroz_ManagementSystem`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `average`
--

CREATE TABLE `average` (
  `idAvarage` int(11) NOT NULL,
  `average` decimal(5,2) DEFAULT NULL,
  `idStudent` int(11) NOT NULL,
  `idSchoolYear` int(11) NOT NULL,
  `idSchoolQuarter` int(11) NOT NULL,
  `idSubject` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `average`
--

INSERT INTO `average` (`idAvarage`, `average`, `idStudent`, `idSchoolYear`, `idSchoolQuarter`, `idSubject`) VALUES
(21, 8.90, 21, 1, 1, 13),
(22, 7.45, 23, 1, 1, 13),
(31, 8.30, 21, 1, 2, 13),
(33, 10.00, 24, 1, 1, 18),
(36, 8.50, 24, 1, 1, 11),
(39, 8.90, 24, 1, 1, 17),
(40, 8.10, 27, 1, 1, 17),
(45, 10.00, 24, 1, 1, 15),
(46, 10.00, 26, 1, 1, 15),
(55, 0.00, 24, 1, 3, 15),
(56, 0.00, 26, 1, 3, 15),
(58, 5.50, 25, 1, 1, 11),
(97, 9.00, 31, 1, 1, 18),
(98, 7.40, 32, 1, 1, 18),
(144, 8.10, 24, 1, 1, 20),
(145, 7.90, 31, 1, 1, 20),
(146, 8.50, 32, 1, 1, 20),
(150, 8.70, 24, 1, 1, 16),
(151, 7.40, 31, 1, 1, 16),
(152, 9.10, 32, 1, 1, 16),
(157, 8.70, 31, 1, 1, 11),
(158, 9.00, 32, 1, 1, 11),
(163, 7.60, 31, 1, 1, 17),
(164, 7.50, 32, 1, 1, 17),
(165, 8.80, 24, 1, 1, 14),
(166, 5.50, 31, 1, 1, 14),
(167, 8.00, 32, 1, 1, 14),
(195, 9.00, 24, 1, 3, 13),
(196, 0.00, 31, 1, 3, 13),
(197, 0.00, 32, 1, 3, 13),
(204, 8.60, 35, 2, 4, 17),
(205, 8.00, 24, 2, 4, 17),
(206, 6.70, 25, 2, 4, 17),
(207, 5.60, 31, 2, 4, 17),
(208, 7.50, 32, 2, 4, 17),
(209, 7.80, 35, 2, 4, 19),
(210, 7.50, 24, 2, 4, 19),
(211, 6.70, 25, 2, 4, 19),
(212, 7.10, 31, 2, 4, 19),
(213, 8.00, 32, 2, 4, 19),
(214, 10.00, 35, 2, 5, 11),
(215, 0.00, 24, 2, 5, 11),
(216, 0.00, 25, 2, 5, 11),
(217, 0.00, 31, 2, 5, 11),
(218, 0.00, 32, 2, 5, 11),
(219, 8.80, 35, 2, 5, 19),
(220, 0.00, 24, 2, 5, 19),
(221, 0.00, 25, 2, 5, 19),
(222, 0.00, 31, 2, 5, 19),
(223, 0.00, 32, 2, 5, 19),
(619, 1.50, 35, 2, 5, 16),
(620, 0.00, 24, 2, 5, 16),
(621, 0.00, 25, 2, 5, 16),
(622, 0.00, 31, 2, 5, 16),
(623, 0.00, 32, 2, 5, 16);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conductReports`
--

CREATE TABLE `conductReports` (
  `idConductReport` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `date_` date DEFAULT NULL,
  `actionTaken` varchar(100) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `idStudent` int(11) DEFAULT NULL,
  `idTeacher` int(11) DEFAULT NULL,
  `idDirector` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `conductReports`
--

INSERT INTO `conductReports` (`idConductReport`, `description`, `date_`, `actionTaken`, `feedback`, `idStudent`, `idTeacher`, `idDirector`) VALUES
(1, 'MAL', '2026-02-14', 'Disciplinario', 'MAL', 24, 28, NULL),
(2, 'MAL ALUMNO', '2026-02-14', 'Conductual', 'EXPLUSARLO', 24, 28, NULL),
(3, 'Mal conducta', '2026-02-16', 'Bitácora', 'Malo', 24, 44, NULL),
(4, 'Mala conducta, y destrucción de equipo', '2026-02-16', 'Bitácora', 'Le pegó a un compañero', 35, 44, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `director`
--

CREATE TABLE `director` (
  `idDirector` int(11) NOT NULL,
  `certificate` varchar(100) DEFAULT NULL,
  `coverLetter` varchar(100) DEFAULT NULL,
  `positionStartDate` date DEFAULT NULL,
  `idUser` int(11) DEFAULT NULL,
  `idUserInfo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluationCriteria`
--

CREATE TABLE `evaluationCriteria` (
  `idEvalCriteria` int(11) NOT NULL,
  `criteria` varchar(50) DEFAULT NULL,
  `porcentage` decimal(2,0) DEFAULT NULL,
  `idSubject` int(11) DEFAULT NULL,
  `idSchoolYear` int(11) DEFAULT NULL,
  `idSchoolQuarter` int(11) DEFAULT NULL,
  `idGroup` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `evaluationCriteria`
--

INSERT INTO `evaluationCriteria` (`idEvalCriteria`, `criteria`, `porcentage`, `idSubject`, `idSchoolYear`, `idSchoolQuarter`, `idGroup`) VALUES
(416, 'C1', 25, 13, 1, 1, NULL),
(417, 'C2', 20, 13, 1, 1, NULL),
(418, 'C3', 55, 13, 1, 1, NULL),
(422, 'C1', 30, 13, 1, 2, NULL),
(423, 'C2', 40, 13, 1, 2, NULL),
(424, 'C3', 30, 13, 1, 2, NULL),
(469, 'C1', 5, 15, 1, 3, NULL),
(470, 'C2', 95, 15, 1, 3, NULL),
(715, 'C1', 15, 20, 1, 1, NULL),
(716, 'C2', 40, 20, 1, 1, NULL),
(717, 'C3', 45, 20, 1, 1, NULL),
(739, 'C1', 30, 16, 1, 1, NULL),
(740, 'C2', 30, 16, 1, 1, NULL),
(741, 'C3', 40, 16, 1, 1, NULL),
(742, 'C1', 10, 17, 1, 1, NULL),
(743, 'C2', 50, 17, 1, 1, NULL),
(744, 'C3', 40, 17, 1, 1, NULL),
(745, 'C1', 25, 14, 1, 1, NULL),
(746, 'C2', 50, 14, 1, 1, NULL),
(747, 'C3', 25, 14, 1, 1, NULL),
(793, 'C1', 10, 18, 1, 1, NULL),
(794, 'C2', 25, 18, 1, 1, NULL),
(795, 'C3', 10, 18, 1, 1, NULL),
(796, 'C4', 15, 18, 1, 1, NULL),
(803, 'C1', 50, 13, 1, 3, NULL),
(804, 'C2', 20, 13, 1, 3, NULL),
(805, 'C3', 30, 13, 1, 3, NULL),
(806, 'C1', 45, 11, 1, 1, NULL),
(807, 'C2', 10, 11, 1, 1, NULL),
(808, 'C3', 45, 11, 1, 1, NULL),
(809, 'C1', 45, 17, 2, 4, NULL),
(810, 'C2', 50, 17, 2, 4, NULL),
(811, 'C3', 5, 17, 2, 4, NULL),
(812, 'C1', 25, 19, 2, 4, NULL),
(813, 'C2', 20, 19, 2, 4, NULL),
(814, 'C3', 30, 19, 2, 4, NULL),
(815, 'C4', 25, 19, 2, 4, NULL),
(1161, 'tareas', 20, 19, 2, 5, NULL),
(1162, 'tca', 35, 19, 2, 5, NULL),
(1163, 'TRABAJOS', 0, 19, 2, 5, NULL),
(1182, 'C1', 30, 16, 2, 5, NULL),
(1183, 'C2', 40, 16, 2, 5, NULL),
(1184, 'C3', 30, 16, 2, 5, NULL),
(1225, 'Examen', 20, 11, 2, 5, NULL),
(1226, 'Tareas', 20, 11, 2, 5, NULL),
(1227, 'participacion', 20, 11, 2, 5, NULL),
(1228, 'Practicas', 20, 11, 2, 5, NULL),
(1229, 'Asistencia', 20, 11, 2, 5, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluation_criteria`
--

CREATE TABLE `evaluation_criteria` (
  `id_eval_criteria` int(11) NOT NULL,
  `id_school_year` int(11) NOT NULL,
  `id_school_quarter` int(11) NOT NULL,
  `id_subject` int(11) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `criteria_index` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `evaluation_criteria`
--

INSERT INTO `evaluation_criteria` (`id_eval_criteria`, `id_school_year`, `id_school_quarter`, `id_subject`, `percentage`, `criteria_index`) VALUES
(13, 1, 1, 15, 65.00, 0),
(14, 1, 1, 15, 15.00, 1),
(15, 1, 1, 15, 10.00, 2),
(16, 1, 1, 15, 10.00, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gradesSubject`
--

CREATE TABLE `gradesSubject` (
  `idGradeSubject` int(11) NOT NULL,
  `grade` decimal(10,0) DEFAULT NULL,
  `evalDate` date DEFAULT NULL,
  `quarter` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `idStudent` int(11) DEFAULT NULL,
  `idSubject` int(11) DEFAULT NULL,
  `idEvalCriteria` int(11) DEFAULT NULL,
  `idSchoolYear` int(11) DEFAULT NULL,
  `idSchoolQuarter` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `gradesSubject`
--

INSERT INTO `gradesSubject` (`idGradeSubject`, `grade`, `evalDate`, `quarter`, `status`, `description`, `idStudent`, `idSubject`, `idEvalCriteria`, `idSchoolYear`, `idSchoolQuarter`) VALUES
(341, 8, '2025-04-28', 'Primer Trimestre', 1, NULL, 21, 13, 416, 1, 1),
(342, 7, '2025-04-28', 'Primer Trimestre', 1, NULL, 21, 13, 417, 1, 1),
(343, 10, '2025-04-28', 'Primer Trimestre', 1, NULL, 21, 13, 418, 1, 1),
(344, 9, '2025-04-28', 'Primer Trimestre', 1, NULL, 23, 13, 416, 1, 1),
(345, 4, '2025-04-28', 'Primer Trimestre', 1, NULL, 23, 13, 417, 1, 1),
(346, 8, '2025-04-28', 'Primer Trimestre', 1, NULL, 23, 13, 418, 1, 1),
(350, 7, '2025-05-12', 'Segundo Trimestre', 1, NULL, 21, 13, 422, 1, 2),
(351, 8, '2025-05-12', 'Segundo Trimestre', 1, NULL, 21, 13, 423, 1, 2),
(352, 10, '2025-05-12', 'Segundo Trimestre', 1, NULL, 21, 13, 424, 1, 2),
(413, NULL, '2025-05-22', 'Tercer Trimestre', 1, NULL, 24, 15, 469, 1, 3),
(414, NULL, '2025-05-22', 'Tercer Trimestre', 1, NULL, 24, 15, 470, 1, 3),
(415, NULL, '2025-05-22', 'Tercer Trimestre', 1, NULL, 26, 15, 469, 1, 3),
(416, NULL, '2025-05-22', 'Tercer Trimestre', 1, NULL, 26, 15, 470, 1, 3),
(850, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 20, 715, 1, 1),
(851, 7, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 20, 716, 1, 1),
(852, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 20, 717, 1, 1),
(853, 7, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 20, 715, 1, 1),
(854, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 20, 716, 1, 1),
(855, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 20, 717, 1, 1),
(856, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 20, 715, 1, 1),
(857, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 20, 716, 1, 1),
(858, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 20, 717, 1, 1),
(913, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 16, 739, 1, 1),
(914, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 16, 740, 1, 1),
(915, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 16, 741, 1, 1),
(916, 10, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 16, 739, 1, 1),
(917, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 16, 740, 1, 1),
(918, 5, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 16, 741, 1, 1),
(919, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 16, 739, 1, 1),
(920, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 16, 740, 1, 1),
(921, 10, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 16, 741, 1, 1),
(922, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 17, 742, 1, 1),
(923, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 17, 743, 1, 1),
(924, 10, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 17, 744, 1, 1),
(925, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 17, 742, 1, 1),
(926, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 17, 743, 1, 1),
(927, 7, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 17, 744, 1, 1),
(928, 10, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 17, 742, 1, 1),
(929, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 17, 743, 1, 1),
(930, 5, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 17, 744, 1, 1),
(931, 10, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 14, 745, 1, 1),
(932, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 14, 746, 1, 1),
(933, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 24, 14, 747, 1, 1),
(934, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 14, 745, 1, 1),
(935, 2, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 14, 746, 1, 1),
(936, 10, '2025-09-20', 'Primer Trimestre', 1, NULL, 31, 14, 747, 1, 1),
(937, 9, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 14, 745, 1, 1),
(938, 8, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 14, 746, 1, 1),
(939, 7, '2025-09-20', 'Primer Trimestre', 1, NULL, 32, 14, 747, 1, 1),
(1075, 10, '2025-10-07', 'Primer Trimestre', 1, NULL, 24, 18, 793, 1, 1),
(1076, 10, '2025-10-07', 'Primer Trimestre', 1, NULL, 24, 18, 794, 1, 1),
(1077, 10, '2025-10-07', 'Primer Trimestre', 1, NULL, 24, 18, 795, 1, 1),
(1078, 10, '2025-10-07', 'Primer Trimestre', 1, NULL, 24, 18, 796, 1, 1),
(1079, 9, '2025-10-07', 'Primer Trimestre', 1, NULL, 31, 18, 793, 1, 1),
(1080, 9, '2025-10-07', 'Primer Trimestre', 1, NULL, 31, 18, 794, 1, 1),
(1081, 9, '2025-10-07', 'Primer Trimestre', 1, NULL, 31, 18, 795, 1, 1),
(1082, 9, '2025-10-07', 'Primer Trimestre', 1, NULL, 31, 18, 796, 1, 1),
(1083, 6, '2025-10-07', 'Primer Trimestre', 1, NULL, 32, 18, 793, 1, 1),
(1084, 6, '2025-10-07', 'Primer Trimestre', 1, NULL, 32, 18, 794, 1, 1),
(1085, 8, '2025-10-07', 'Primer Trimestre', 1, NULL, 32, 18, 795, 1, 1),
(1086, 10, '2025-10-07', 'Primer Trimestre', 1, NULL, 32, 18, 796, 1, 1),
(1105, 10, '2025-11-21', 'Tercer Trimestre', 1, NULL, 24, 13, 803, 1, 3),
(1106, 8, '2025-11-21', 'Tercer Trimestre', 1, NULL, 24, 13, 804, 1, 3),
(1107, 8, '2025-11-21', 'Tercer Trimestre', 1, NULL, 24, 13, 805, 1, 3),
(1108, NULL, '2025-11-21', 'Tercer Trimestre', 1, NULL, 31, 13, 803, 1, 3),
(1109, NULL, '2025-11-21', 'Tercer Trimestre', 1, NULL, 31, 13, 804, 1, 3),
(1110, NULL, '2025-11-21', 'Tercer Trimestre', 1, NULL, 31, 13, 805, 1, 3),
(1111, NULL, '2025-11-21', 'Tercer Trimestre', 1, NULL, 32, 13, 803, 1, 3),
(1112, NULL, '2025-11-21', 'Tercer Trimestre', 1, NULL, 32, 13, 804, 1, 3),
(1113, NULL, '2025-11-21', 'Tercer Trimestre', 1, NULL, 32, 13, 805, 1, 3),
(1114, 9, '2025-11-21', 'Primer Trimestre', 1, NULL, 24, 11, 806, 1, 1),
(1115, 8, '2025-11-21', 'Primer Trimestre', 1, NULL, 24, 11, 807, 1, 1),
(1116, 8, '2025-11-21', 'Primer Trimestre', 1, NULL, 24, 11, 808, 1, 1),
(1117, 8, '2025-11-21', 'Primer Trimestre', 1, NULL, 31, 11, 806, 1, 1),
(1118, 10, '2025-11-21', 'Primer Trimestre', 1, NULL, 31, 11, 807, 1, 1),
(1119, 9, '2025-11-21', 'Primer Trimestre', 1, NULL, 31, 11, 808, 1, 1),
(1120, 9, '2025-11-21', 'Primer Trimestre', 1, NULL, 32, 11, 806, 1, 1),
(1121, 9, '2025-11-21', 'Primer Trimestre', 1, NULL, 32, 11, 807, 1, 1),
(1122, 9, '2025-11-21', 'Primer Trimestre', 1, NULL, 32, 11, 808, 1, 1),
(1123, 9, '2026-02-16', 'Primer Trimestre', 1, NULL, 35, 17, 809, 2, 4),
(1124, 8, '2026-02-16', 'Primer Trimestre', 1, NULL, 35, 17, 810, 2, 4),
(1125, 10, '2026-02-16', 'Primer Trimestre', 1, NULL, 35, 17, 811, 2, 4),
(1126, 9, '2026-02-16', 'Primer Trimestre', 1, NULL, 24, 17, 809, 2, 4),
(1127, 7, '2026-02-16', 'Primer Trimestre', 1, NULL, 24, 17, 810, 2, 4),
(1128, 8, '2026-02-16', 'Primer Trimestre', 1, NULL, 24, 17, 811, 2, 4),
(1129, 5, '2026-02-16', 'Primer Trimestre', 1, NULL, 25, 17, 809, 2, 4),
(1130, 8, '2026-02-16', 'Primer Trimestre', 1, NULL, 25, 17, 810, 2, 4),
(1131, 9, '2026-02-16', 'Primer Trimestre', 1, NULL, 25, 17, 811, 2, 4),
(1132, 5, '2026-02-16', 'Primer Trimestre', 1, NULL, 31, 17, 809, 2, 4),
(1133, 6, '2026-02-16', 'Primer Trimestre', 1, NULL, 31, 17, 810, 2, 4),
(1134, 7, '2026-02-16', 'Primer Trimestre', 1, NULL, 31, 17, 811, 2, 4),
(1135, 7, '2026-02-16', 'Primer Trimestre', 1, NULL, 32, 17, 809, 2, 4),
(1136, 8, '2026-02-16', 'Primer Trimestre', 1, NULL, 32, 17, 810, 2, 4),
(1137, 7, '2026-02-16', 'Primer Trimestre', 1, NULL, 32, 17, 811, 2, 4),
(1138, 4, '2026-02-17', 'Primer Trimestre', 1, NULL, 35, 19, 812, 2, 4),
(1139, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 35, 19, 813, 2, 4),
(1140, 9, '2026-02-17', 'Primer Trimestre', 1, NULL, 35, 19, 814, 2, 4),
(1141, 10, '2026-02-17', 'Primer Trimestre', 1, NULL, 35, 19, 815, 2, 4),
(1142, 7, '2026-02-17', 'Primer Trimestre', 1, NULL, 24, 19, 812, 2, 4),
(1143, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 24, 19, 813, 2, 4),
(1144, 7, '2026-02-17', 'Primer Trimestre', 1, NULL, 24, 19, 814, 2, 4),
(1145, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 24, 19, 815, 2, 4),
(1146, 5, '2026-02-17', 'Primer Trimestre', 1, NULL, 25, 19, 812, 2, 4),
(1147, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 25, 19, 813, 2, 4),
(1148, 6, '2026-02-17', 'Primer Trimestre', 1, NULL, 25, 19, 814, 2, 4),
(1149, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 25, 19, 815, 2, 4),
(1150, 7, '2026-02-17', 'Primer Trimestre', 1, NULL, 31, 19, 812, 2, 4),
(1151, 6, '2026-02-17', 'Primer Trimestre', 1, NULL, 31, 19, 813, 2, 4),
(1152, 7, '2026-02-17', 'Primer Trimestre', 1, NULL, 31, 19, 814, 2, 4),
(1153, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 31, 19, 815, 2, 4),
(1154, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 32, 19, 812, 2, 4),
(1155, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 32, 19, 813, 2, 4),
(1156, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 32, 19, 814, 2, 4),
(1157, 8, '2026-02-17', 'Primer Trimestre', 1, NULL, 32, 19, 815, 2, 4),
(2803, 10, '2026-04-15', '0', 1, NULL, 35, 19, 1161, 2, 5),
(2804, 8, '2026-04-15', '0', 1, NULL, 35, 19, 1162, 2, 5),
(2805, 7, '2026-04-15', '0', 1, NULL, 35, 19, 1163, 2, 5),
(2806, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 19, 1161, 2, 5),
(2807, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 19, 1162, 2, 5),
(2808, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 19, 1163, 2, 5),
(2809, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 19, 1161, 2, 5),
(2810, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 19, 1162, 2, 5),
(2811, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 19, 1163, 2, 5),
(2812, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 19, 1161, 2, 5),
(2813, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 19, 1162, 2, 5),
(2814, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 19, 1163, 2, 5),
(2815, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 19, 1161, 2, 5),
(2816, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 19, 1162, 2, 5),
(2817, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 19, 1163, 2, 5),
(2908, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 35, 16, 1182, 2, 5),
(2909, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 35, 16, 1183, 2, 5),
(2910, 5, '2026-04-15', 'Segundo Trimestre', 1, NULL, 35, 16, 1184, 2, 5),
(2911, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 16, 1182, 2, 5),
(2912, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 16, 1183, 2, 5),
(2913, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 16, 1184, 2, 5),
(2914, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 16, 1182, 2, 5),
(2915, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 16, 1183, 2, 5),
(2916, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 16, 1184, 2, 5),
(2917, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 16, 1182, 2, 5),
(2918, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 16, 1183, 2, 5),
(2919, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 16, 1184, 2, 5),
(2920, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 16, 1182, 2, 5),
(2921, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 16, 1183, 2, 5),
(2922, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 16, 1184, 2, 5),
(3123, 10, '2026-04-16', '0', 1, NULL, 35, 11, 1225, 2, 5),
(3124, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 35, 11, 1226, 2, 5),
(3125, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 35, 11, 1227, 2, 5),
(3126, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 35, 11, 1228, 2, 5),
(3127, 10, '2026-04-16', '0', 1, NULL, 35, 11, 1229, 2, 5),
(3128, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 11, 1225, 2, 5),
(3129, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 11, 1226, 2, 5),
(3130, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 11, 1227, 2, 5),
(3131, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 11, 1228, 2, 5),
(3132, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 24, 11, 1229, 2, 5),
(3133, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 11, 1225, 2, 5),
(3134, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 11, 1226, 2, 5),
(3135, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 11, 1227, 2, 5),
(3136, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 11, 1228, 2, 5),
(3137, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 25, 11, 1229, 2, 5),
(3138, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 11, 1225, 2, 5),
(3139, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 11, 1226, 2, 5),
(3140, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 11, 1227, 2, 5),
(3141, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 11, 1228, 2, 5),
(3142, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 31, 11, 1229, 2, 5),
(3143, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 11, 1225, 2, 5),
(3144, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 11, 1226, 2, 5),
(3145, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 11, 1227, 2, 5),
(3146, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 11, 1228, 2, 5),
(3147, NULL, '2026-04-15', 'Segundo Trimestre', 1, NULL, 32, 11, 1229, 2, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `groups`
--

CREATE TABLE `groups` (
  `idGroup` int(11) NOT NULL,
  `group_` varchar(2) DEFAULT NULL,
  `grade` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `groups`
--

INSERT INTO `groups` (`idGroup`, `group_`, `grade`) VALUES
(1, 'A', '3'),
(2, 'B', '3'),
(3, 'B', '4'),
(4, 'A', '4'),
(5, 'A', '1'),
(6, 'B', '1'),
(7, 'B', '2'),
(8, 'A', '2'),
(9, 'C', '4'),
(10, 'A', '5'),
(11, 'B', '5'),
(12, 'A', '6'),
(13, 'B', '6');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `kardex`
--

CREATE TABLE `kardex` (
  `idKardex` int(11) NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `idStudent` int(11) DEFAULT NULL,
  `idSchoolYear` int(11) DEFAULT NULL,
  `idLearningArea` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `learningArea`
--

CREATE TABLE `learningArea` (
  `idLearningArea` int(11) NOT NULL,
  `name` char(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `learningArea`
--

INSERT INTO `learningArea` (`idLearningArea`, `name`) VALUES
(1, 'Lenguaje'),
(2, 'Saberes y Pensamiento Científico'),
(3, 'Ética, Naturaleza y Sociedad'),
(4, 'De lo Humano a lo Comunitario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `limitDate`
--

CREATE TABLE `limitDate` (
  `idLimitDate` int(11) NOT NULL,
  `limitDate` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `limitDate`
--

INSERT INTO `limitDate` (`idLimitDate`, `limitDate`) VALUES
(1, '2026-02-12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportCard`
--

CREATE TABLE `reportCard` (
  `idReportCard` int(11) NOT NULL,
  `idGradeSubject` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `idRole` int(11) NOT NULL,
  `level_` varchar(2) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`idRole`, `level_`, `description`) VALUES
(1, 'ME', 'Maestro Especial'),
(2, 'MS', 'Maestro de Escolarizado'),
(3, 'AD', 'Director Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `schoolInfo`
--

CREATE TABLE `schoolInfo` (
  `schoolNum` int(11) NOT NULL,
  `schoolName` char(100) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `number_` varchar(5) DEFAULT NULL,
  `neighborhood` char(30) DEFAULT NULL,
  `cp` varchar(5) DEFAULT NULL,
  `schoolCodeCCT` varchar(10) DEFAULT NULL,
  `socialMedia` varchar(100) DEFAULT NULL,
  `phoneNumber` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `schoolInfo`
--

INSERT INTO `schoolInfo` (`schoolNum`, `schoolName`, `street`, `number_`, `neighborhood`, `cp`, `schoolCodeCCT`, `socialMedia`, `phoneNumber`, `email`, `shift`) VALUES
(1, 'Gregorio Torres Quintero 2308', 'Francisco I. Madero', '506', 'Centro', '31700', '08EPR0071I', 'https://www.facebook.com/Esc.Chiquita.2308/?locale=es_LA', '6366941426', 'gtq2308cte@gmail.com', 'Matutino');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `schoolQuarter`
--

CREATE TABLE `schoolQuarter` (
  `idSchoolQuarter` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `idSchoolYear` int(11) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `schoolQuarter`
--

INSERT INTO `schoolQuarter` (`idSchoolQuarter`, `name`, `description`, `idSchoolYear`, `startDate`, `endDate`) VALUES
(1, 'Primer Trimestre', 'Primer trimestre del período académico', 1, NULL, NULL),
(2, 'Segundo Trimestre', 'Segundo trimestre del período académico', 1, NULL, NULL),
(3, 'Tercer Trimestre', 'Tercer trimestre del período académico', 1, NULL, NULL),
(4, 'Primer Trimestre', 'Primer trimestre del período académico', 2, '2026-02-03', '2026-05-01'),
(5, 'Segundo Trimestre', 'Segundo trimestre del período académico', 2, '2026-02-28', '2026-08-21'),
(6, 'Tercer Trimestre', 'Tercer trimestre del período académico', 2, '2026-09-19', '2026-12-10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `schoolYear`
--

CREATE TABLE `schoolYear` (
  `idSchoolYear` int(11) NOT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `schoolYear`
--

INSERT INTO `schoolYear` (`idSchoolYear`, `startDate`, `endDate`) VALUES
(1, '2025-03-01', '2025-12-15'),
(2, '2026-02-02', '2026-12-26'),
(4, '2027-02-15', '2027-12-08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `students`
--

CREATE TABLE `students` (
  `idStudent` int(11) NOT NULL,
  `idUserInfo` int(11) DEFAULT NULL,
  `idTutor` int(11) DEFAULT NULL,
  `schoolNum` int(11) DEFAULT NULL,
  `idGroup` int(11) DEFAULT NULL,
  `idSchoolYear` int(11) DEFAULT NULL,
  `idStudentStatus` int(11) DEFAULT NULL,
  `curp` char(18) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `students`
--

INSERT INTO `students` (`idStudent`, `idUserInfo`, `idTutor`, `schoolNum`, `idGroup`, `idSchoolYear`, `idStudentStatus`, `curp`) VALUES
(24, 71, 35, NULL, 4, 2, 1, 'GADSBDNW6472Mr8'),
(25, 74, 36, NULL, 4, 2, 1, 'MATS040505HCHTRG05'),
(26, 75, 37, NULL, 5, 1, 1, 'LORA110809MCHLZN07'),
(27, 77, 39, NULL, 6, 1, 1, 'HEDM120912HCHBRM01'),
(28, 78, 40, NULL, 11, 1, 1, 'CUSA100304MCHNRL06'),
(29, 79, 41, NULL, 9, 1, 1, 'GOPM081020HCHNND08'),
(31, 88, 43, NULL, 4, 2, 1, 'asdadas'),
(32, 89, 44, NULL, 4, 2, 1, 'asdadas'),
(33, 93, 45, NULL, 5, 2, 1, 'AAAAAAAA'),
(34, 94, 46, NULL, 8, 1, 1, 'AAAAAAAA'),
(35, 95, 47, NULL, 4, 2, 1, 'AAAAAAAA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `studentStatus`
--

CREATE TABLE `studentStatus` (
  `idStudentStatus` int(11) NOT NULL,
  `nomenclature` char(2) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `studentStatus`
--

INSERT INTO `studentStatus` (`idStudentStatus`, `nomenclature`, `description`) VALUES
(1, 'AC', 'Activo'),
(2, 'BA', 'Baja Definitiva'),
(4, 'EG', 'Egresado'),
(5, 'IN', 'Inscrito'),
(6, 'TR', 'En Trámite');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subjects`
--

CREATE TABLE `subjects` (
  `idSubject` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `specialSubject` tinyint(1) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `idLearningArea` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `subjects`
--

INSERT INTO `subjects` (`idSubject`, `name`, `specialSubject`, `description`, `idLearningArea`) VALUES
(11, 'Español', 0, 'Materia de comprensión y producción de textos', 1),
(12, 'Inglés', 1, 'Lengua extranjera', 1),
(13, 'Artes', 1, 'Expresión artística y apreciación estética', 1),
(14, 'Matemáticas', 0, 'Razonamiento lógico-matemático', 2),
(15, 'Computación', 1, 'Uso de herramientas tecnológicas', 2),
(16, 'Geografía', 0, 'Estudio del espacio y el territorio', 3),
(17, 'Historia', 0, 'Estudio de los procesos históricos', 3),
(18, 'Ciencias Naturales', 0, 'Estudio del entorno natural y fenómenos científicos', 3),
(19, 'Formación Cívica y Ética', 0, 'Desarrollo de valores y ciudadanía', 4),
(20, 'Educación Física', 1, 'Actividad física y cuidado corporal', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `teacherGroupsSubjects`
--

CREATE TABLE `teacherGroupsSubjects` (
  `idDFM` int(11) NOT NULL,
  `idGroup` int(11) DEFAULT NULL,
  `idTeacher` int(11) DEFAULT NULL,
  `idSubject` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `teacherGroupsSubjects`
--

INSERT INTO `teacherGroupsSubjects` (`idDFM`, `idGroup`, `idTeacher`, `idSubject`) VALUES
(60, 4, 28, 11),
(75, 4, 28, 16),
(80, 1, 42, 12),
(85, 5, 42, 12),
(94, 4, 28, 19),
(99, 4, 44, 15),
(100, 4, 28, 17),
(102, 3, 44, 15),
(103, 9, 44, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `teachers`
--

CREATE TABLE `teachers` (
  `idTeacher` int(11) NOT NULL,
  `profesionalID` varchar(20) DEFAULT NULL,
  `ine` varchar(20) DEFAULT NULL,
  `ineDocument` text DEFAULT NULL,
  `typeTeacher` varchar(20) DEFAULT NULL,
  `idTeacherStatus` int(11) DEFAULT NULL,
  `idUserInfo` int(11) DEFAULT NULL,
  `idUser` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `teachers`
--

INSERT INTO `teachers` (`idTeacher`, `profesionalID`, `ine`, `ineDocument`, `typeTeacher`, `idTeacherStatus`, `idUserInfo`, `idUser`) VALUES
(28, '4567890', '123456789012', NULL, 'MS', 1, 67, 33),
(29, '64721773719', '098765432123', NULL, 'ME', 1, 68, 34),
(34, 'cusacsancnasl', 'Flores', NULL, 'MS', 1, 80, 39),
(38, 'PROFGITHUB2023', 'DEMO123456789', NULL, '1', 1, 84, 43),
(41, 'wewrfw3', '32432432', NULL, 'ME', 1, 90, 46),
(42, 'ddtdtrdtr', 'hgyugguguyg', NULL, 'ME', 1, 91, 47),
(44, '64721773719', '098765432123', NULL, 'ME', 1, 96, 49);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `teacherStatus`
--

CREATE TABLE `teacherStatus` (
  `idTeacherStatus` int(11) NOT NULL,
  `nomenclature` char(4) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `teacherStatus`
--

INSERT INTO `teacherStatus` (`idTeacherStatus`, `nomenclature`, `description`) VALUES
(1, 'ACT', 'Activo'),
(2, 'INAC', 'Inactivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `teacherSubject`
--

CREATE TABLE `teacherSubject` (
  `idTeacherSubject` int(11) NOT NULL,
  `idTeacher` int(11) DEFAULT NULL,
  `idSubject` int(11) DEFAULT NULL,
  `idSchoolYear` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `teacherSubject`
--

INSERT INTO `teacherSubject` (`idTeacherSubject`, `idTeacher`, `idSubject`, `idSchoolYear`) VALUES
(44, 23, 13, 2),
(45, 23, 15, 2),
(48, 23, 13, 2),
(50, 27, 19, 2),
(60, 28, 11, 2),
(72, 28, 16, 2),
(74, 28, 17, 2),
(79, 28, 17, 2),
(80, 28, 16, 2),
(90, 28, 17, 2),
(91, 28, 19, 2),
(97, 44, 15, 2),
(98, 28, 17, 2),
(100, 44, 15, 2),
(101, 44, 15, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tutors`
--

CREATE TABLE `tutors` (
  `idTutor` int(11) NOT NULL,
  `relative_` char(20) DEFAULT NULL,
  `ine` varchar(20) DEFAULT NULL,
  `ineDocument` text DEFAULT NULL,
  `tutorLastnamePa` varchar(30) DEFAULT NULL,
  `tutorLastnameMa` varchar(30) DEFAULT NULL,
  `tutorName` varchar(100) DEFAULT NULL,
  `tutorPhone` varchar(15) DEFAULT NULL,
  `tutorAddress` varchar(100) DEFAULT NULL,
  `tutorNeighborhood` varchar(30) DEFAULT NULL,
  `tutorEmail` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tutors`
--

INSERT INTO `tutors` (`idTutor`, `relative_`, `ine`, `ineDocument`, `tutorLastnamePa`, `tutorLastnameMa`, `tutorName`, `tutorPhone`, `tutorAddress`, `tutorNeighborhood`, `tutorEmail`) VALUES
(35, 'Madre', '7478539729', '', 'Garcia', 'Tarelo', 'Pamela Itzel', '6361243810', 'Calle Peru 1202', '', 'pamelatarelo18@gmail.com'),
(36, 'Madre', 'Ruiz', '', 'Caycho', 'Caycho', 'Renzo', '6361106001', 'Avenida José Granda 2222', '', 'ana.lopez@outlook.com'),
(37, 'Abuelo Paterno', 'Torres', '', 'López', '', 'Jorge Luis', '6363456789', 'Calle Francisco I. Madero 45', '', 'jlopez@gmail.com'),
(39, 'Madre', 'González', '', 'Díaz', '', 'Mariana ', '6365678901', 'Av. Benito Juárez 300', '', 'marianadiaz12@gmail.com'),
(40, 'Padre', 'Herrera', '', 'Cruz', '', 'Ricardo', '6367890123', 'Calle Constitución 852', '', 'ricardoRuiz@yahoo.com'),
(41, 'Madre', 'Flores', '', 'Pineda', 'adsd', 'Silvia', '6369012345', 'Calle 2 de Abril 210', '', 'silviapineda57@gmail.com'),
(43, 'Padre', 'DASDadasdadasdsad', '', 'my', 'last name', 'my full name', '(123) 456-7890', 'full street address', '', 'me@mydomain.com'),
(44, 'Padre', 'DASDadasdadasdsad', '', 'my', 'last name', 'my full name', '(123) 456-7890', 'full street address', '', 'me@mydomain.com'),
(45, 'Padre', 'DASDadasdadasdsad', '', 'Caycho', '', 'Renzo', '(123) 456-7890', 'Avenida José Granda 2222', '', 'me@mydomain.com'),
(46, 'Padre', 'DASDadasdadasdsad', '', 'Caycho', 'Caycho', 'Renzo', '6361106001', 'Avenida José Granda 2222', '', 'me@mydomain.com'),
(47, 'Padre', '7478539729', '', 'Caycho', 'Caycho', 'Renzo', '6361106001', 'Avenida José Granda 2222', '', 'renzocaycho03@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `idUser` int(11) NOT NULL,
  `username` char(30) NOT NULL,
  `password` varchar(100) DEFAULT NULL,
  `idRole` int(11) DEFAULT NULL,
  `idUserInfo` int(11) DEFAULT NULL,
  `raw_password` varchar(100) DEFAULT NULL,
  `password_changed` tinyint(1) DEFAULT 0 COMMENT 'Indica si el usuario ya cambió su contraseña temporal',
  `password_change_date` datetime DEFAULT NULL COMMENT 'Fecha del último cambio de contraseña'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`idUser`, `username`, `password`, `idRole`, `idUserInfo`, `raw_password`, `password_changed`, `password_change_date`) VALUES
(32, 'admin25', '$2y$10$TcJtEm1YB5WiWHvk7dLxx.1Pfeb3ExTyanNMWhsdDapAxg6B2ybgG', 3, 66, NULL, 1, '2025-09-21 11:07:51'),
(33, 'pamela10', '$2y$10$lqEYM6s/zsRktX3outQ6UubPDW225117gbKT76ooRNxztTf05ddA2', 1, 67, NULL, 1, '2025-09-21 10:57:05'),
(34, 'renzo48', '$2y$10$mrCenzpIDovrrrYpB//K2OAPCPvyjG5KYBb/HObNFwwrfGXdN2uCC', 2, 68, NULL, 1, '2025-09-21 10:58:08'),
(39, 'usuario119', '$2y$10$cWyfZ.evaCnzjQ8eF9e5T.6Z7O0f9Oc5nU0YOuHzdbY3SwfRXt8Iy', 2, 80, NULL, 1, '2025-09-21 11:24:31'),
(43, 'profesordemo', '$2y$10$8KZtNAMTI.mFfiDcaEdzZeQmI3rYy/YJbK.TAhUmYRQ5829xH/nW6', 2, 84, 'github123', 0, NULL),
(46, 'mateo49', '$2y$10$KyuXBlksM9GCRHmq5TZsgeWaZoeWAaFzQwV6XtILFeAd4h.jGw1l2', 1, 90, NULL, 1, '2025-09-21 11:58:53'),
(47, 'mateo64', '$2y$10$CzB2htU8e6ImsMcMnT1Bce5ADpJS7KPMCGGGfiUe0SGfh26MTO1ze', 1, 91, NULL, 1, '2025-09-22 17:58:13'),
(49, 'francisco85', '$2y$10$qZABlD7DLjuflN5XQsabZOV6881jenhU.CfDPXhLCR0GJzOTvuVj6', 1, 96, NULL, 1, '2026-02-16 21:24:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usersInfo`
--

CREATE TABLE `usersInfo` (
  `idUserInfo` int(11) NOT NULL,
  `lastnamePa` varchar(30) DEFAULT NULL,
  `lastnameMa` varchar(30) DEFAULT NULL,
  `names` char(100) DEFAULT NULL,
  `gender` char(10) DEFAULT NULL,
  `birthDate` date DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `number_` varchar(5) DEFAULT NULL,
  `neighborhood` char(30) DEFAULT NULL,
  `cp` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usersInfo`
--

INSERT INTO `usersInfo` (`idUserInfo`, `lastnamePa`, `lastnameMa`, `names`, `gender`, `birthDate`, `phone`, `email`, `street`, `number_`, `neighborhood`, `cp`) VALUES
(66, 'Pérez', 'García', 'Juan Manuel', 'Masculino', '1975-06-15', '5551234567', 'juan.director@escuela.edu.mx', 'Calle Reforma', '123', 'Centro', '06000'),
(67, 'Garcia', 'Tarelo', 'Pamela Itzel', 'Femenino', NULL, '6361243810', 'pamelatarelo18@gmail.com', 'Calle Peruana 564', NULL, NULL, NULL),
(68, 'Caycho ', 'Pomachagua', 'Renzo Gabriel', 'Masculino', NULL, '636', 'renzocaycho@gmail.com', 'Calle Perú 1254', NULL, NULL, NULL),
(71, 'Garcia', 'Caballero', 'Victor Manuel', 'M', NULL, '6361024727', 'victorgarcia@gmail.com', 'Calle Gonzalez', '', '', ''),
(74, 'Martínez', 'Torres', 'Diego Alejandro', 'M', NULL, '6367358905', 'diego.mtz@outlook.com', 'Calle 5 de Febrero 121', '', '', ''),
(75, 'López', 'Ramírez', 'Ana Sofía', 'F', NULL, '6363456789', 'analopez@gmail.com', 'Calle Francisco I. Madero 45', '', '', ''),
(77, 'Herrera', 'Díaz', 'Emiliano', 'M', NULL, '6365678901', 'marianadiaz12@gmail.com', 'Av. Benito Juárez 300', '', '', ''),
(78, 'Cruz', 'Sánchez', 'Valentina', 'F', NULL, '6367890123', 'valcruz@outlook.com', 'Calle Constitución 852', '', '', ''),
(79, 'González ', 'Pineda', 'Mateo', 'M', NULL, '6369012345', 'mateoglez@outlook.com', 'Calle 2 de Abril 210', '', '', ''),
(80, 'si', 'si', 'usuario1', 'Masculino', NULL, '6369012345', 'mateoglez@outlook.com', 'Calle 2 de Abril 210', NULL, NULL, NULL),
(84, 'Demo', 'GitHub', 'Profesor', 'M', NULL, '9876543210', 'demo@github.com', 'Demo Street 123', NULL, NULL, NULL),
(88, 'my last name', 'my last name', 'my full name', 'M', NULL, '(123) 456-7890', 'me@mydomain.com', 'full street address', '', '', ''),
(89, 'my last name', 'my last name', 'my full name2', 'F', NULL, '(123) 456-7890', 'me@mydomain.com', 'full street address', '', '', ''),
(90, 'Garrido', 'Lecca', 'Mateo', 'Masculino', NULL, '6361024727', 'victorgarcia@gmail.com', 'Calle Gonzalez', NULL, NULL, NULL),
(91, 'Garcia', 'Caballero', 'Mateo', 'Masculino', NULL, '6361024727', 'victorgarcia@gmail.com', 'Calle Gonzalez', NULL, NULL, NULL),
(93, 'my last name', 'Caycho', 'RAFA', 'M', NULL, 'EFE', 'renzocaycho@yahoo.es', 'Avenida José Granda 2222', '', '', ''),
(94, 'my last name', 'Caycho', 'RenzoAAAAAAAAAAAA', 'F', NULL, '6361243810', 'EGRERG@AAAAA', 'Avenida José Granda 2222', '', '', ''),
(95, 'Caycho', 'Caycho', 'Renzo', 'M', NULL, '6361106001', 'renzocaycho03@gmail.com', 'Avenida José Granda 2222', '', '', ''),
(96, 'Loya', 'Peña', 'Francisco', 'Masculino', NULL, '636', 'renzocaycho@gmail.com', 'Calle Si', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_remember_tokens`
--

CREATE TABLE `user_remember_tokens` (
  `idUser` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `average`
--
ALTER TABLE `average`
  ADD PRIMARY KEY (`idAvarage`),
  ADD UNIQUE KEY `uq_unique_average_entry` (`idStudent`,`idSchoolYear`,`idSchoolQuarter`,`idSubject`),
  ADD KEY `idSchoolYear` (`idSchoolYear`),
  ADD KEY `idSchoolQuarter` (`idSchoolQuarter`),
  ADD KEY `fk_average_subject` (`idSubject`);

--
-- Indices de la tabla `conductReports`
--
ALTER TABLE `conductReports`
  ADD PRIMARY KEY (`idConductReport`),
  ADD KEY `idStudent` (`idStudent`),
  ADD KEY `idTeacher` (`idTeacher`),
  ADD KEY `idDirector` (`idDirector`);

--
-- Indices de la tabla `director`
--
ALTER TABLE `director`
  ADD PRIMARY KEY (`idDirector`),
  ADD KEY `idUserInfo` (`idUserInfo`),
  ADD KEY `idUser` (`idUser`);

--
-- Indices de la tabla `evaluationCriteria`
--
ALTER TABLE `evaluationCriteria`
  ADD PRIMARY KEY (`idEvalCriteria`),
  ADD KEY `idSubject` (`idSubject`),
  ADD KEY `FK_evalCriteria_schoolYear` (`idSchoolYear`),
  ADD KEY `FK_evalCriteria_schoolQuarter` (`idSchoolQuarter`),
  ADD KEY `idGroup` (`idGroup`);

--
-- Indices de la tabla `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  ADD PRIMARY KEY (`id_eval_criteria`),
  ADD UNIQUE KEY `unique_criteria` (`id_school_year`,`id_school_quarter`,`id_subject`,`criteria_index`);

--
-- Indices de la tabla `gradesSubject`
--
ALTER TABLE `gradesSubject`
  ADD PRIMARY KEY (`idGradeSubject`),
  ADD UNIQUE KEY `uq_unique_grade_entry` (`idStudent`,`idSubject`,`idEvalCriteria`,`idSchoolYear`,`idSchoolQuarter`),
  ADD KEY `idSubject` (`idSubject`),
  ADD KEY `idEvalCriteria` (`idEvalCriteria`),
  ADD KEY `fk_school_year` (`idSchoolYear`),
  ADD KEY `fk_school_quarter` (`idSchoolQuarter`);

--
-- Indices de la tabla `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`idGroup`);

--
-- Indices de la tabla `kardex`
--
ALTER TABLE `kardex`
  ADD PRIMARY KEY (`idKardex`),
  ADD KEY `idStudent` (`idStudent`),
  ADD KEY `idSchoolYear` (`idSchoolYear`),
  ADD KEY `idLearningArea` (`idLearningArea`);

--
-- Indices de la tabla `learningArea`
--
ALTER TABLE `learningArea`
  ADD PRIMARY KEY (`idLearningArea`);

--
-- Indices de la tabla `limitDate`
--
ALTER TABLE `limitDate`
  ADD PRIMARY KEY (`idLimitDate`);

--
-- Indices de la tabla `reportCard`
--
ALTER TABLE `reportCard`
  ADD PRIMARY KEY (`idReportCard`),
  ADD KEY `idGradeSubject` (`idGradeSubject`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`idRole`);

--
-- Indices de la tabla `schoolInfo`
--
ALTER TABLE `schoolInfo`
  ADD PRIMARY KEY (`schoolNum`);

--
-- Indices de la tabla `schoolQuarter`
--
ALTER TABLE `schoolQuarter`
  ADD PRIMARY KEY (`idSchoolQuarter`),
  ADD KEY `idSchoolYear` (`idSchoolYear`);

--
-- Indices de la tabla `schoolYear`
--
ALTER TABLE `schoolYear`
  ADD PRIMARY KEY (`idSchoolYear`);

--
-- Indices de la tabla `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`idStudent`),
  ADD KEY `idUserInfo` (`idUserInfo`),
  ADD KEY `idTutor` (`idTutor`),
  ADD KEY `schoolNum` (`schoolNum`),
  ADD KEY `idGroup` (`idGroup`),
  ADD KEY `idSchoolYear` (`idSchoolYear`),
  ADD KEY `idStudentStatus` (`idStudentStatus`);

--
-- Indices de la tabla `studentStatus`
--
ALTER TABLE `studentStatus`
  ADD PRIMARY KEY (`idStudentStatus`);

--
-- Indices de la tabla `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`idSubject`),
  ADD KEY `idLearningArea` (`idLearningArea`);

--
-- Indices de la tabla `teacherGroupsSubjects`
--
ALTER TABLE `teacherGroupsSubjects`
  ADD PRIMARY KEY (`idDFM`),
  ADD KEY `idGroup` (`idGroup`),
  ADD KEY `idTeacher` (`idTeacher`),
  ADD KEY `idSubject` (`idSubject`);

--
-- Indices de la tabla `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`idTeacher`),
  ADD KEY `idTeacherStatus` (`idTeacherStatus`),
  ADD KEY `idUserInfo` (`idUserInfo`),
  ADD KEY `idUser` (`idUser`);

--
-- Indices de la tabla `teacherStatus`
--
ALTER TABLE `teacherStatus`
  ADD PRIMARY KEY (`idTeacherStatus`);

--
-- Indices de la tabla `teacherSubject`
--
ALTER TABLE `teacherSubject`
  ADD PRIMARY KEY (`idTeacherSubject`),
  ADD KEY `idTeacher` (`idTeacher`),
  ADD KEY `idSubject` (`idSubject`),
  ADD KEY `idSchoolYear` (`idSchoolYear`);

--
-- Indices de la tabla `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`idTutor`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`idUser`),
  ADD KEY `idRole` (`idRole`),
  ADD KEY `idUserInfo` (`idUserInfo`);

--
-- Indices de la tabla `usersInfo`
--
ALTER TABLE `usersInfo`
  ADD PRIMARY KEY (`idUserInfo`);

--
-- Indices de la tabla `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD PRIMARY KEY (`idUser`),
  ADD UNIQUE KEY `token_unique` (`token`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `average`
--
ALTER TABLE `average`
  MODIFY `idAvarage` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=694;

--
-- AUTO_INCREMENT de la tabla `conductReports`
--
ALTER TABLE `conductReports`
  MODIFY `idConductReport` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `director`
--
ALTER TABLE `director`
  MODIFY `idDirector` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evaluationCriteria`
--
ALTER TABLE `evaluationCriteria`
  MODIFY `idEvalCriteria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1230;

--
-- AUTO_INCREMENT de la tabla `evaluation_criteria`
--
ALTER TABLE `evaluation_criteria`
  MODIFY `id_eval_criteria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `gradesSubject`
--
ALTER TABLE `gradesSubject`
  MODIFY `idGradeSubject` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3157;

--
-- AUTO_INCREMENT de la tabla `groups`
--
ALTER TABLE `groups`
  MODIFY `idGroup` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `kardex`
--
ALTER TABLE `kardex`
  MODIFY `idKardex` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `learningArea`
--
ALTER TABLE `learningArea`
  MODIFY `idLearningArea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `limitDate`
--
ALTER TABLE `limitDate`
  MODIFY `idLimitDate` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `reportCard`
--
ALTER TABLE `reportCard`
  MODIFY `idReportCard` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `idRole` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `schoolInfo`
--
ALTER TABLE `schoolInfo`
  MODIFY `schoolNum` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `schoolQuarter`
--
ALTER TABLE `schoolQuarter`
  MODIFY `idSchoolQuarter` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `schoolYear`
--
ALTER TABLE `schoolYear`
  MODIFY `idSchoolYear` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `students`
--
ALTER TABLE `students`
  MODIFY `idStudent` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `studentStatus`
--
ALTER TABLE `studentStatus`
  MODIFY `idStudentStatus` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `subjects`
--
ALTER TABLE `subjects`
  MODIFY `idSubject` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `teacherGroupsSubjects`
--
ALTER TABLE `teacherGroupsSubjects`
  MODIFY `idDFM` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT de la tabla `teachers`
--
ALTER TABLE `teachers`
  MODIFY `idTeacher` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `teacherStatus`
--
ALTER TABLE `teacherStatus`
  MODIFY `idTeacherStatus` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `teacherSubject`
--
ALTER TABLE `teacherSubject`
  MODIFY `idTeacherSubject` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT de la tabla `tutors`
--
ALTER TABLE `tutors`
  MODIFY `idTutor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `idUser` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `usersInfo`
--
ALTER TABLE `usersInfo`
  MODIFY `idUserInfo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `average`
--
ALTER TABLE `average`
  ADD CONSTRAINT `average_ibfk_2` FOREIGN KEY (`idStudent`) REFERENCES `students` (`idStudent`),
  ADD CONSTRAINT `average_ibfk_3` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`),
  ADD CONSTRAINT `fk_average_subject` FOREIGN KEY (`idSubject`) REFERENCES `subjects` (`idSubject`),
  ADD CONSTRAINT `idSchoolQuarter` FOREIGN KEY (`idSchoolQuarter`) REFERENCES `schoolQuarter` (`idSchoolQuarter`);

--
-- Filtros para la tabla `conductReports`
--
ALTER TABLE `conductReports`
  ADD CONSTRAINT `conductReports_ibfk_1` FOREIGN KEY (`idStudent`) REFERENCES `students` (`idStudent`),
  ADD CONSTRAINT `conductReports_ibfk_2` FOREIGN KEY (`idTeacher`) REFERENCES `teachers` (`idTeacher`),
  ADD CONSTRAINT `conductReports_ibfk_3` FOREIGN KEY (`idDirector`) REFERENCES `director` (`idDirector`);

--
-- Filtros para la tabla `director`
--
ALTER TABLE `director`
  ADD CONSTRAINT `director_ibfk_1` FOREIGN KEY (`idUserInfo`) REFERENCES `usersInfo` (`idUserInfo`),
  ADD CONSTRAINT `director_ibfk_2` FOREIGN KEY (`idUser`) REFERENCES `users` (`idUser`);

--
-- Filtros para la tabla `evaluationCriteria`
--
ALTER TABLE `evaluationCriteria`
  ADD CONSTRAINT `FK_evalCriteria_schoolQuarter` FOREIGN KEY (`idSchoolQuarter`) REFERENCES `schoolQuarter` (`idSchoolQuarter`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_evalCriteria_schoolYear` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `evaluationCriteria_ibfk_1` FOREIGN KEY (`idSubject`) REFERENCES `subjects` (`idSubject`),
  ADD CONSTRAINT `evaluationCriteria_ibfk_2` FOREIGN KEY (`idGroup`) REFERENCES `groups` (`idGroup`);

--
-- Filtros para la tabla `gradesSubject`
--
ALTER TABLE `gradesSubject`
  ADD CONSTRAINT `fk_school_quarter` FOREIGN KEY (`idSchoolQuarter`) REFERENCES `schoolQuarter` (`idSchoolQuarter`),
  ADD CONSTRAINT `fk_school_year` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`),
  ADD CONSTRAINT `gradesSubject_ibfk_1` FOREIGN KEY (`idStudent`) REFERENCES `students` (`idStudent`),
  ADD CONSTRAINT `gradesSubject_ibfk_2` FOREIGN KEY (`idSubject`) REFERENCES `subjects` (`idSubject`),
  ADD CONSTRAINT `gradesSubject_ibfk_3` FOREIGN KEY (`idEvalCriteria`) REFERENCES `evaluationCriteria` (`idEvalCriteria`);

--
-- Filtros para la tabla `kardex`
--
ALTER TABLE `kardex`
  ADD CONSTRAINT `kardex_ibfk_1` FOREIGN KEY (`idStudent`) REFERENCES `students` (`idStudent`),
  ADD CONSTRAINT `kardex_ibfk_2` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`),
  ADD CONSTRAINT `kardex_ibfk_3` FOREIGN KEY (`idLearningArea`) REFERENCES `learningArea` (`idLearningArea`);

--
-- Filtros para la tabla `reportCard`
--
ALTER TABLE `reportCard`
  ADD CONSTRAINT `reportCard_ibfk_1` FOREIGN KEY (`idGradeSubject`) REFERENCES `gradesSubject` (`idGradeSubject`);

--
-- Filtros para la tabla `schoolQuarter`
--
ALTER TABLE `schoolQuarter`
  ADD CONSTRAINT `schoolQuarter_ibfk_1` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`);

--
-- Filtros para la tabla `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`idUserInfo`) REFERENCES `usersInfo` (`idUserInfo`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`idTutor`) REFERENCES `tutors` (`idTutor`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`schoolNum`) REFERENCES `schoolInfo` (`schoolNum`),
  ADD CONSTRAINT `students_ibfk_4` FOREIGN KEY (`idGroup`) REFERENCES `groups` (`idGroup`),
  ADD CONSTRAINT `students_ibfk_5` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`),
  ADD CONSTRAINT `students_ibfk_6` FOREIGN KEY (`idStudentStatus`) REFERENCES `studentStatus` (`idStudentStatus`);

--
-- Filtros para la tabla `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`idLearningArea`) REFERENCES `learningArea` (`idLearningArea`);

--
-- Filtros para la tabla `teacherGroupsSubjects`
--
ALTER TABLE `teacherGroupsSubjects`
  ADD CONSTRAINT `teacherGroupsSubjects_ibfk_1` FOREIGN KEY (`idGroup`) REFERENCES `groups` (`idGroup`),
  ADD CONSTRAINT `teacherGroupsSubjects_ibfk_2` FOREIGN KEY (`idTeacher`) REFERENCES `teachers` (`idTeacher`),
  ADD CONSTRAINT `teacherGroupsSubjects_ibfk_3` FOREIGN KEY (`idSubject`) REFERENCES `subjects` (`idSubject`);

--
-- Filtros para la tabla `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`idTeacherStatus`) REFERENCES `teacherStatus` (`idTeacherStatus`),
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`idUserInfo`) REFERENCES `usersInfo` (`idUserInfo`),
  ADD CONSTRAINT `teachers_ibfk_3` FOREIGN KEY (`idUser`) REFERENCES `users` (`idUser`);

--
-- Filtros para la tabla `teacherSubject`
--
ALTER TABLE `teacherSubject`
  ADD CONSTRAINT `teacherSubject_ibfk_1` FOREIGN KEY (`idTeacher`) REFERENCES `teachers` (`idTeacher`),
  ADD CONSTRAINT `teacherSubject_ibfk_2` FOREIGN KEY (`idSubject`) REFERENCES `subjects` (`idSubject`),
  ADD CONSTRAINT `teacherSubject_ibfk_3` FOREIGN KEY (`idSchoolYear`) REFERENCES `schoolYear` (`idSchoolYear`);

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`idRole`) REFERENCES `roles` (`idRole`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`idUserInfo`) REFERENCES `usersInfo` (`idUserInfo`);

--
-- Filtros para la tabla `user_remember_tokens`
--
ALTER TABLE `user_remember_tokens`
  ADD CONSTRAINT `user_remember_tokens_ibfk_1` FOREIGN KEY (`idUser`) REFERENCES `users` (`idUser`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
