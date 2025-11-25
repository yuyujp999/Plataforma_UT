-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-11-2025 a las 01:02:02
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ut_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id_admin` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `correo` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id_admin`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo`, `password`, `fecha_registro`) VALUES
(1, 'Maríaa S', 'García', 'Lozano', 'admin@example.edu', '$2y$10$4hTBw1Uhqc1V3ahfDoTGxeTByKTcY3w/JzlYqpb02rqp9x0qn3Q2O', '2025-10-17 19:16:26'),
(2, 'Yuleisy', 'Ocañas', 'González', 'ejemplo@ut.edu', '$2y$10$dyce7u3Ii4QqyExw.xdoZe1MyUY5qqLkbhGsR7aYx0mkG16Ib6tqu', '2025-10-17 19:36:55'),
(3, 'Juan', 'Cantú', 'Reyna', 'juan@gmail.com', '$2y$10$T8yPFyYa3OAn8KtA21utzec7tVDZ2yKKL1Y7MMMGoq54C9pY75SZO', '2025-10-17 19:42:06'),
(4, 'Ángel', 'Loza', 'Loza', 'angel@gmail.com', '$2y$10$qY6Tqc8m/6m7sWcyOUPPDeDfAeSMxBYELqCOX8lrjv7Lsjas8Tyga', '2025-10-17 19:44:05'),
(5, 'Devany', 'Zapata', 'Chavez', 'deva@gmail.com', '$2y$10$r1jFseMkgaa//u7WavandeXFRHr9lkc4fnpU1cfGPVg7zhXKjT2Wq', '2025-10-17 19:45:18'),
(6, 'Veronica', 'González', 'Jimenez', 'vero@gmail.com', '$2y$10$qK/nAznNUuJp/z5he8lwVu2JzjZfJlST/8XXELYCsOkH6GNOBHJLG', '2025-10-17 20:07:42'),
(7, 'Eduardo', 'Ocañas', 'González', 'eduardo@gmail.com', '$2y$10$c2iDDVrLMP17irI2lI2se.MvCWNmBn4hx45ty9kak4bEoO.EROQL6', '2025-10-17 20:09:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id_alumno` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `curp` varchar(18) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sexo` enum('Masculino','Femenino','Otro') NOT NULL,
  `telefono` bigint(20) UNSIGNED DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `correo_personal` varchar(255) DEFAULT NULL,
  `matricula` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estatus` enum('activo','baja','suspendido') NOT NULL DEFAULT 'activo',
  `fecha_baja` date DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `id_nombre_semestre` int(11) DEFAULT NULL,
  `contacto_emergencia` varchar(200) NOT NULL,
  `parentesco_emergencia` varchar(50) DEFAULT NULL,
  `telefono_emergencia` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id_alumno`, `nombre`, `apellido_paterno`, `apellido_materno`, `curp`, `fecha_nacimiento`, `sexo`, `telefono`, `direccion`, `correo_personal`, `matricula`, `password`, `estatus`, `fecha_baja`, `deleted_at`, `id_nombre_semestre`, `contacto_emergencia`, `parentesco_emergencia`, `telefono_emergencia`, `fecha_registro`) VALUES
(1, 'María Fernanda  EH', 'López', 'García', 'LOGM010101MDFRRA09', '2001-01-01', 'Femenino', 5512345678, 'Av. Central #45, CDMX', 'maria.lopez@example.com', 'A001', '12345hash1', 'activo', '2025-10-29', NULL, 1, 'Rosa García', 'Madre', 5519876543, '2025-10-18 22:35:35'),
(2, 'José Antonio BG', 'Martínez', 'Pérez', 'MAPJ000202HDFRRS03', '2000-02-02', 'Masculino', 5523456789, 'Calle Hidalgo #12, Puebla', 'jose.martinez@example.com', 'A002', '12345hash2', 'activo', NULL, NULL, 2, 'Luis Martínez', 'Padre', 5523456780, '2025-10-18 22:35:35'),
(3, 'Ana Sofía j', 'Ramírez', 'Torres', 'RATA000303MDFRRL06', '2000-03-03', 'Femenino', 5534567890, 'Av. Juárez #99, Querétaro', 'ana.ramirez@example.com', 'A003', '12345hash3', 'baja', '2025-11-01', NULL, 3, 'María Torres', 'Madre', 5545678901, '2025-10-18 22:35:35'),
(4, 'Carlos Alberto', 'Gómez', 'Luna', 'GOLC000404HDFRLL05', '2000-04-04', 'Masculino', 5545678902, 'Col. Centro #23, Monterrey', 'carlos.gomez@example.com', 'A004', '12345hash4', 'suspendido', NULL, NULL, 4, 'Laura Luna', 'Hermana', 5556789012, '2025-10-18 22:35:35'),
(7, 'Andrea j', 'Santos', 'Vega', 'SAVA000707MDFRGA09', '2000-07-07', 'Femenino', 5578901235, 'Calle Sur #78, Veracruz', 'andrea.santos@example.com', 'A007', '12345hash7', 'suspendido', NULL, NULL, 3, 'Héctor Vega', 'Padre', 5589012345, '2025-10-18 22:35:35'),
(8, 'Miguel', 'Flores', 'Morales', 'FLOM000808HDFRRL01', '2000-08-08', 'Masculino', 5589012346, 'Av. Independencia #34, Oaxaca', 'miguel.flores@example.com', 'A008', '12345hash8', 'activo', NULL, NULL, 4, 'Rosa Morales', 'Madre', 5590123456, '2025-10-18 22:35:35'),
(9, 'Valeria', 'Cruz', 'Núñez', 'CRNV000909MDFRRV02', '2000-09-09', 'Femenino', 5590123457, 'Calle Norte #22, Toluca', 'valeria.cruz@example.com', 'A009', '12345hash9', 'activo', NULL, NULL, 1, 'Elena Núñez', 'Madre', 5501234567, '2025-10-18 22:35:35'),
(10, 'Jorge Luis', 'Reyes', 'Ortiz', 'REOJ001010HDFRRJ03', '2000-10-10', 'Masculino', 5501234568, 'Av. Morelos #77, Mérida', 'jorge.reyes@example.com', 'A010', '12345hash10', 'activo', NULL, NULL, 2, 'Fernando Ortiz', 'Padre', 5512345670, '2025-10-18 22:35:35'),
(71, 'Isabella', 'Gómez', 'Ramírez', 'GROI011121MDFRRL21', '2001-11-21', 'Femenino', 5512340021, 'Av. Central #21, León', 'isabella21@example.com', 'A021', '12345hash21', 'activo', NULL, NULL, 3, 'Rosa Ramírez', 'Madre', 5511110021, '2025-10-19 00:46:00'),
(72, 'Santiago', 'Martínez', 'Pérez', 'MAPS011222HDFRRL22', '2001-12-22', 'Masculino', 5512340022, 'Calle Hidalgo #22, Puebla', 'santiago22@example.com', 'A022', '12345hash22', 'activo', NULL, NULL, 3, 'Luis Martínez', 'Padre', 5511110022, '2025-10-19 00:46:00'),
(73, 'Lucía', 'Ramírez', 'Torres', 'RATL020123MDFRRL23', '2002-01-23', 'Femenino', 5512340023, 'Av. Juárez #23, Querétaro', 'lucia23@example.com', 'A023', '12345hash23', 'activo', NULL, NULL, 3, 'María Torres', 'Madre', 5511110023, '2025-10-19 00:46:00'),
(74, 'Emilio', 'Gómez', 'Luna', 'GOLE020224HDFRRL24', '2002-02-24', 'Masculino', 5512340024, 'Col. Centro #24, Monterrey', 'emilio24@example.com', 'A024', '12345hash24', 'activo', NULL, NULL, 3, 'Laura Luna', 'Hermana', 5511110024, '2025-10-19 00:46:00'),
(75, 'Renata', 'Hernández', 'Ruiz', 'HERR020325MDFRRL25', '2002-03-25', 'Femenino', 5512340025, 'Av. Reforma #25, Guadalajara', 'renata25@example.com', 'A025', '12345hash25', 'activo', NULL, NULL, 3, 'Pedro Ruiz', 'Padre', 5511110025, '2025-10-19 00:46:00'),
(76, 'Mateo', 'Mendoza', 'Castro', 'MEMM020426HDFRRL26', '2002-04-26', 'Masculino', 5512340026, 'Col. Roma #26, CDMX', 'mateo26@example.com', 'A026', '12345hash26', 'activo', NULL, NULL, 3, 'Carmen Castro', 'Madre', 5511110026, '2025-10-19 00:46:00'),
(77, 'Valentina', 'Santos', 'Vega', 'SAVV020527MDFRRL27', '2002-05-27', 'Femenino', 5512340027, 'Calle Sur #27, Veracruz', 'valentina27@example.com', 'A027', '12345hash27', 'activo', NULL, NULL, 3, 'Héctor Vega', 'Padre', 5511110027, '2025-10-19 00:46:00'),
(78, 'Sebastián', 'Flores', 'Morales', 'FLOS020628HDFRRL28', '2002-06-28', 'Masculino', 5512340028, 'Av. Independencia #28, Oaxaca', 'sebastian28@example.com', 'A028', '12345hash28', 'activo', NULL, NULL, 3, 'Rosa Morales', 'Madre', 5511110028, '2025-10-19 00:46:00'),
(79, 'Natalia', 'Cruz', 'Núñez', 'CRNN020729MDFRRL29', '2002-07-29', 'Femenino', 5512340029, 'Calle Norte #29, Toluca', 'natalia29@example.com', 'A029', '12345hash29', 'activo', NULL, NULL, 3, 'Elena Núñez', 'Madre', 5511110029, '2025-10-19 00:46:00'),
(80, 'David', 'Reyes', 'Ortiz', 'REOD020830HDFRRL30', '2002-08-30', 'Masculino', 5512340030, 'Av. Morelos #30, Mérida', 'david30@example.com', 'A030', '12345hash30', 'activo', NULL, NULL, 3, 'Fernando Ortiz', 'Padre', 5511110030, '2025-10-19 00:46:00'),
(81, 'Ximena', 'Gómez', 'Ramírez', 'GROX020931MDFRRL31', '2002-09-30', 'Femenino', 5512340031, 'Av. Central #31, León', 'ximena31@example.com', 'A031', '12345hash31', 'activo', NULL, NULL, 4, 'Rosa Ramírez', 'Madre', 5511110031, '2025-10-19 00:46:00'),
(82, 'Rodrigo', 'Martínez', 'Pérez', 'MAPR021032HDFRRL32', '2002-10-01', 'Masculino', 5512340032, 'Calle Hidalgo #32, Puebla', 'rodrigo32@example.com', 'A032', '12345hash32', 'activo', NULL, NULL, 4, 'Luis Martínez', 'Padre', 5511110032, '2025-10-19 00:46:00'),
(83, 'Elena', 'Ramírez', 'Torres', 'RATE021133MDFRRL33', '2002-11-02', 'Femenino', 5512340033, 'Av. Juárez #33, Querétaro', 'elena33@example.com', 'A033', '12345hash33', 'activo', NULL, NULL, 4, 'María Torres', 'Madre', 5511110033, '2025-10-19 00:46:00'),
(84, 'Tomás', 'Gómez', 'Luna', 'GOLT021234HDFRRL34', '2002-12-03', 'Masculino', 5512340034, 'Col. Centro #34, Monterrey', 'tomas34@example.com', 'A034', '12345hash34', 'activo', NULL, NULL, 4, 'Laura Luna', 'Hermana', 5511110034, '2025-10-19 00:46:00'),
(85, 'Samantha', 'Hernández', 'Ruiz', 'HERS030135MDFRRL35', '2003-01-04', 'Femenino', 5512340035, 'Av. Reforma #35, Guadalajara', 'samantha35@example.com', 'A035', '12345hash35', 'activo', NULL, NULL, 4, 'Pedro Ruiz', 'Padre', 5511110035, '2025-10-19 00:46:00'),
(86, 'Andrés', 'Mendoza', 'Castro', 'MECA030236HDFRRL36', '2003-02-05', 'Masculino', 5512340036, 'Col. Roma #36, CDMX', 'andres36@example.com', 'A036', '12345hash36', 'activo', NULL, NULL, 4, 'Carmen Castro', 'Madre', 5511110036, '2025-10-19 00:46:00'),
(87, 'Jimena', 'Santos', 'Vega', 'SAVJ030337MDFRRL37', '2003-03-06', 'Femenino', 5512340037, 'Calle Sur #37, Veracruz', 'jimena37@example.com', 'A037', '12345hash37', 'activo', NULL, NULL, 4, 'Héctor Vega', 'Padre', 5511110037, '2025-10-19 00:46:00'),
(88, 'Leonardo', 'Flores', 'Morales', 'FLOL030438HDFRRL38', '2003-04-07', 'Masculino', 5512340038, 'Av. Independencia #38, Oaxaca', 'leonardo38@example.com', 'A038', '12345hash38', 'activo', NULL, NULL, 4, 'Rosa Morales', 'Madre', 5511110038, '2025-10-19 00:46:00'),
(89, 'Paula', 'Cruz', 'Núñez', 'CRNP030539MDFRRL39', '2003-05-08', 'Femenino', 5512340039, 'Calle Norte #39, Toluca', 'paula39@example.com', 'A039', '12345hash39', 'activo', NULL, NULL, 4, 'Elena Núñez', 'Madre', 5511110039, '2025-10-19 00:46:00'),
(90, 'Felipe', 'Reyes', 'Ortiz', 'REOF030640HDFRRL40', '2003-06-09', 'Masculino', 5512340040, 'Av. Morelos #40, Mérida', 'felipe40@example.com', 'A040', '12345hash40', 'activo', NULL, NULL, 4, 'Fernando Ortiz', 'Padre', 5511110040, '2025-10-19 00:46:00'),
(91, 'Victoria', 'Gómez', 'Ramírez', 'GROV030741MDFRRL41', '2003-07-10', 'Femenino', 5512340041, 'Av. Central #41, León', 'victoria41@example.com', 'A041', '12345hash41', 'activo', NULL, NULL, 5, 'Rosa Ramírez', 'Madre', 5511110041, '2025-10-19 00:46:00'),
(92, 'Martín', 'Martínez', 'Pérez', 'MAPM030842HDFRRL42', '2003-08-11', 'Masculino', 5512340042, 'Calle Hidalgo #42, Puebla', 'martin42@example.com', 'A042', '12345hash42', 'activo', NULL, NULL, 5, 'Luis Martínez', 'Padre', 5511110042, '2025-10-19 00:46:00'),
(93, 'Abigail', 'Ramírez', 'Torres', 'RATA030943MDFRRL43', '2003-09-12', 'Femenino', 5512340043, 'Av. Juárez #43, Querétaro', 'abigail43@example.com', 'A043', '12345hash43', 'activo', NULL, NULL, 5, 'María Torres', 'Madre', 5511110043, '2025-10-19 00:46:00'),
(94, 'Emiliano', 'Gómez', 'Luna', 'GOLE031044HDFRRL44', '2003-10-13', 'Masculino', 5512340044, 'Col. Centro #44, Monterrey', 'emiliano44@example.com', 'A044', '12345hash44', 'activo', NULL, NULL, 5, 'Laura Luna', 'Hermana', 5511110044, '2025-10-19 00:46:00'),
(95, 'Julieta', 'Hernández', 'Ruiz', 'HERJ031145MDFRRL45', '2003-11-14', 'Femenino', 5512340045, 'Av. Reforma #45, Guadalajara', 'julieta45@example.com', 'A045', '12345hash45', 'activo', NULL, NULL, 5, 'Pedro Ruiz', 'Padre', 5511110045, '2025-10-19 00:46:00'),
(96, 'Gabriel', 'Mendoza', 'Castro', 'MECA031246HDFRRL46', '2003-12-15', 'Masculino', 5512340046, 'Col. Roma #46, CDMX', 'gabriel46@example.com', 'A046', '12345hash46', 'activo', NULL, NULL, 5, 'Carmen Castro', 'Madre', 5511110046, '2025-10-19 00:46:00'),
(97, 'Regina', 'Santos', 'Vega', 'SAVR040147MDFRRL47', '2004-01-16', 'Femenino', 5512340047, 'Calle Sur #47, Veracruz', 'regina47@example.com', 'A047', '12345hash47', 'activo', NULL, NULL, 5, 'Héctor Vega', 'Padre', 5511110047, '2025-10-19 00:46:00'),
(98, 'Iván', 'Flores', 'Morales', 'FLOI040248HDFRRL48', '2004-02-17', 'Masculino', 5512340048, 'Av. Independencia #48, Oaxaca', 'ivan48@example.com', 'A048', '12345hash48', 'activo', NULL, NULL, 5, 'Rosa Morales', 'Madre', 5511110048, '2025-10-19 00:46:00'),
(99, 'Carolina', 'Cruz', 'Núñez', 'CRNC040349MDFRRL49', '2004-03-18', 'Femenino', 5512340049, 'Calle Norte #49, Toluca', 'carolina49@example.com', 'A049', '12345hash49', 'activo', NULL, NULL, 5, 'Elena Núñez', 'Madre', 5511110049, '2025-10-19 00:46:00'),
(100, 'Tomás', 'Reyes', 'Ortiz', 'REOT040450HDFRRL50', '2004-04-19', 'Masculino', 5512340050, 'Av. Morelos #50, Mérida', 'tomas50@example.com', 'A050', '12345hash50', 'activo', NULL, NULL, 5, 'Fernando Ortiz', 'Padre', 5511110050, '2025-10-19 00:46:00'),
(101, 'Luciano', 'Vargas', 'Serrano', 'VASL040551HDFRRL51', '2004-05-20', 'Masculino', 5512340051, 'Av. Hidalgo #51, Tijuana', 'luciano51@example.com', 'A051', '12345hash51', 'activo', NULL, NULL, 5, 'Ana Serrano', 'Madre', 5511110051, '2025-10-19 00:46:00'),
(102, 'Camila', 'Ortega', 'Romero', 'ORRC040652MDFRRL52', '2004-06-21', 'Femenino', 5512340052, 'Calle Morelos #52, Mérida', 'camila52@example.com', 'A052', '12345hash52', 'activo', NULL, NULL, 5, 'Mario Ortega', 'Padre', 5511110052, '2025-10-19 00:46:00'),
(103, 'Marco', 'Jiménez', 'Suárez', 'JISM040753HDFRRL53', '2004-07-22', 'Masculino', 5512340053, 'Col. Centro #53, León', 'marco53@example.com', 'A053', '12345hash53', 'activo', NULL, NULL, 5, 'Beatriz Suárez', 'Madre', 5511110053, '2025-10-19 00:46:00'),
(104, 'Natalia', 'Navarro', 'Cortés', 'NACN040854MDFRRL54', '2004-08-23', 'Femenino', 5512340054, 'Av. Juárez #54, Puebla', 'natalia54@example.com', 'A054', '12345hash54', 'activo', NULL, NULL, 5, 'Carmen Cortés', 'Madre', 5511110054, '2025-10-19 00:46:00'),
(105, 'Ángel', 'Salazar', 'Díaz', 'SADA040955HDFRRL55', '2004-09-24', 'Masculino', 5512340055, 'Av. Hidalgo #55, CDMX', 'angel55@example.com', 'A055', '12345hash55', 'activo', NULL, NULL, 5, 'Lucía Díaz', 'Madre', 5511110055, '2025-10-19 00:46:00'),
(106, 'Brenda', 'Moreno', 'Flores', 'MOFB041056MDFRRL56', '2004-10-25', 'Femenino', 5512340056, 'Av. Central #56, Guadalajara', 'brenda56@example.com', 'A056', '12345hash56', 'activo', NULL, NULL, 5, 'Jorge Moreno', 'Padre', 5511110056, '2025-10-19 00:46:00'),
(107, 'Erick', 'Vega', 'Santos', 'VESR041157HDFRRL57', '2004-11-26', 'Masculino', 5512340057, 'Calle Sur #57, Veracruz', 'erick57@example.com', 'A057', '12345hash57', 'activo', NULL, NULL, 5, 'Diana Santos', 'Madre', 5511110057, '2025-10-19 00:46:00'),
(108, 'Melissa', 'Cortés', 'Ríos', 'CORR041258MDFRRL58', '2004-12-27', 'Femenino', 5512340058, 'Av. Reforma #58, Monterrey', 'melissa58@example.com', 'A058', '12345hash58', 'activo', NULL, NULL, 5, 'Alberto Ríos', 'Padre', 5511110058, '2025-10-19 00:46:00'),
(109, 'Samuel', 'Domínguez', 'Lara', 'DOLS050159HDFRRL59', '2005-01-28', 'Masculino', 5512340059, 'Calle Norte #59, Toluca', 'samuel59@example.com', 'A059', '12345hash59', 'activo', NULL, NULL, 5, 'Carmen Lara', 'Madre', 5511110059, '2025-10-19 00:46:00'),
(110, 'María', 'Lara', 'Torres', 'LATM050260MDFRRL60', '2005-02-01', 'Femenino', 5512340060, 'Av. Morelos #60, Mérida', 'maria60@example.com', 'A060', '12345hash60', 'activo', NULL, NULL, 5, 'José Torres', 'Padre', 5511110060, '2025-10-19 00:46:00'),
(111, 'María Fernanda', 'López', 'García', 'LOGM010101MDFRRA01', '2001-01-01', 'Femenino', 5512340001, 'Av. Central #1, CDMX', 'maria1@example.com', 'B001', '12345hash1', 'activo', NULL, NULL, 1, 'Rosa García', 'Madre', 5511110001, '2025-10-19 00:51:07'),
(112, 'José Antonio', 'Martínez', 'Pérez', 'MAPJ000202HDFRRS02', '2000-02-02', 'Masculino', 5512340002, 'Calle Hidalgo #2, Puebla', 'jose2@example.com', 'B002', '12345hash2', 'activo', NULL, NULL, 1, 'Luis Martínez', 'Padre', 5511110002, '2025-10-19 00:51:07'),
(113, 'Ana Sofía', 'Ramírez', 'Torres', 'RATA000303MDFRRL03', '2000-03-03', 'Femenino', 5512340003, 'Av. Juárez #3, Querétaro', 'ana3@example.com', 'B003', '12345hash3', 'activo', NULL, NULL, 1, 'María Torres', 'Madre', 5511110003, '2025-10-19 00:51:07'),
(114, 'Carlos Alberto', 'Gómez', 'Luna', 'GOLC000404HDFRLL04', '2000-04-04', 'Masculino', 5512340004, 'Col. Centro #4, Monterrey', 'carlos4@example.com', 'B004', '12345hash4', 'activo', NULL, NULL, 1, 'Laura Luna', 'Hermana', 5511110004, '2025-10-19 00:51:07'),
(115, 'Daniela', 'Hernández', 'Ruiz', 'HERD000505MDFRRN05', '2000-05-05', 'Femenino', 5512340005, 'Av. Reforma #5, Guadalajara', 'daniela5@example.com', 'B005', '12345hash5', 'activo', NULL, NULL, 1, 'Pedro Ruiz', 'Padre', 5511110005, '2025-10-19 00:51:07'),
(116, 'Luis Ángel', 'Mendoza', 'Castro', 'MECL000606HDFRRS06', '2000-06-06', 'Masculino', 5512340006, 'Col. Roma #6, CDMX', 'luis6@example.com', 'B006', '12345hash6', 'activo', NULL, NULL, 1, 'Carmen Castro', 'Madre', 5511110006, '2025-10-19 00:51:07'),
(117, 'Andrea', 'Santos', 'Vega', 'SAVA000707MDFRGA07', '2000-07-07', 'Femenino', 5512340007, 'Calle Sur #7, Veracruz', 'andrea7@example.com', 'B007', '12345hash7', 'activo', NULL, NULL, 1, 'Héctor Vega', 'Padre', 5511110007, '2025-10-19 00:51:07'),
(118, 'Miguel', 'Flores', 'Morales', 'FLOM000808HDFRRL08', '2000-08-08', 'Masculino', 5512340008, 'Av. Independencia #8, Oaxaca', 'miguel8@example.com', 'B008', '12345hash8', 'activo', NULL, NULL, 1, 'Rosa Morales', 'Madre', 5511110008, '2025-10-19 00:51:07'),
(119, 'Valeria', 'Cruz', 'Núñez', 'CRNV000909MDFRRV09', '2000-09-09', 'Femenino', 5512340009, 'Calle Norte #9, Toluca', 'valeria9@example.com', 'B009', '12345hash9', 'activo', NULL, NULL, 1, 'Elena Núñez', 'Madre', 5511110009, '2025-10-19 00:51:07'),
(120, 'Jorge Luis', 'Reyes', 'Ortiz', 'REOJ001010HDFRRJ10', '2000-10-10', 'Masculino', 5512340010, 'Av. Morelos #10, Mérida', 'jorge10@example.com', 'B010', '12345hash10', 'activo', NULL, NULL, 1, 'Fernando Ortiz', 'Padre', 5511110010, '2025-10-19 00:51:07'),
(121, 'Camila', 'González', 'Pérez', 'GOPC010111MDFRRL11', '2001-01-11', 'Femenino', 5512340011, 'Av. Central #11, León', 'camila11@example.com', 'C011', '12345hash11', 'activo', NULL, NULL, 2, 'Laura Pérez', 'Madre', 5511110011, '2025-10-19 00:51:07'),
(122, 'Diego', 'Ramírez', 'Luna', 'RALD010212HDFRRS12', '2001-02-12', 'Masculino', 5512340012, 'Calle Hidalgo #12, Puebla', 'diego12@example.com', 'C012', '12345hash12', 'activo', NULL, NULL, 2, 'Luis Ramírez', 'Padre', 5511110012, '2025-10-19 00:51:07'),
(123, 'Fernanda', 'Torres', 'Santos', 'TOCF010313MDFRRL13', '2001-03-13', 'Femenino', 5512340013, 'Av. Juárez #13, Querétaro', 'fernanda13@example.com', 'C013', '12345hash13', 'activo', NULL, NULL, 2, 'María Santos', 'Madre', 5511110013, '2025-10-19 00:51:07'),
(124, 'Pablo', 'Hernández', 'Ríos', 'HEPR010414HDFRRL14', '2001-04-14', 'Masculino', 5512340014, 'Col. Centro #14, Monterrey', 'pablo14@example.com', 'C014', '12345hash14', 'activo', NULL, NULL, 2, 'Carmen Ríos', 'Madre', 5511110014, '2025-10-19 00:51:07'),
(125, 'Diana', 'Mendoza', 'Lopez', 'MELD010515MDFRRL15', '2001-05-15', 'Femenino', 5512340015, 'Av. Reforma #15, Guadalajara', 'diana15@example.com', 'C015', '12345hash15', 'activo', NULL, NULL, 2, 'Pedro Mendoza', 'Padre', 5511110015, '2025-10-19 00:51:07'),
(126, 'Ricardo', 'López', 'Ruiz', 'LORR010616HDFRRL16', '2001-06-16', 'Masculino', 5512340016, 'Col. Roma #16, CDMX', 'ricardo16@example.com', 'C016', '12345hash16', 'activo', NULL, NULL, 2, 'Laura Ruiz', 'Madre', 5511110016, '2025-10-19 00:51:07'),
(127, 'Sofía', 'Santos', 'Vega', 'SASV010717MDFRRL17', '2001-07-17', 'Femenino', 5512340017, 'Calle Sur #17, Veracruz', 'sofia17@example.com', 'C017', '12345hash17', 'activo', NULL, NULL, 2, 'Héctor Vega', 'Padre', 5511110017, '2025-10-19 00:51:07'),
(128, 'Manuel', 'Flores', 'Morales', 'FLMM010818HDFRRL18', '2001-08-18', 'Masculino', 5512340018, 'Av. Independencia #18, Oaxaca', 'manuel18@example.com', 'C018', '12345hash18', 'activo', NULL, NULL, 2, 'Rosa Morales', 'Madre', 5511110018, '2025-10-19 00:51:07'),
(129, 'Alejandra', 'Cruz', 'Núñez', 'CRNA010919MDFRRL19', '2001-09-19', 'Femenino', 5512340019, 'Calle Norte #19, Toluca', 'alejandra19@example.com', 'C019', '12345hash19', 'activo', NULL, NULL, 2, 'Elena Núñez', 'Madre', 5511110019, '2025-10-19 00:51:07'),
(130, 'Eduardo', 'Reyes', 'Ortiz', 'REOE011020HDFRRL20', '2001-10-20', 'Masculino', 5512340020, 'Av. Morelos #20, Mérida', 'eduardo20@example.com', 'C020', '12345hash20', 'activo', NULL, NULL, 2, 'Fernando Ortiz', 'Padre', 5511110020, '2025-10-19 00:51:07'),
(131, 'María Fernanda sdbh', 'López', 'García', 'HSDBSHDSJDJA2', '2025-11-05', 'Femenino', 5512345678, 'Av. Central #45, CDMX', 'hdbajs@gmail.com', 'A061', '$2y$10$lrc9qPDPBowKDFnieKw8T.tro9axlq8ke/YWpJf8oEmQ/3R6Gw2R.', 'activo', NULL, NULL, 3, 'sbdshad', 'sdjjauswq', 232831913, '2025-11-01 03:44:18'),
(132, 'Angel Antonio', 'Loza', 'Flores', '12412D32 22D2C', '2005-06-20', 'Masculino', 211412412414, 'edx211de12', 'angelantonio3loza@gmail.com', 'A062', '$2y$10$BaBIQb/kDxr9nbDAgPF8s.KSgmT8mXY08kRlC19LloZmkG/Yd1fYC', 'activo', NULL, NULL, 8, 'descrcsaewdw', 'asdcadawdad', 3242423424, '2025-11-03 01:11:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_ciclo`
--

CREATE TABLE `alumno_ciclo` (
  `id_alumno_ciclo` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_ciclo` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `estatus` enum('inscrito','baja','egresado','suspendido') DEFAULT 'inscrito',
  `fecha_inscripcion` datetime DEFAULT current_timestamp(),
  `fecha_baja` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumno_ciclo`
--

INSERT INTO `alumno_ciclo` (`id_alumno_ciclo`, `id_alumno`, `id_ciclo`, `id_grupo`, `estatus`, `fecha_inscripcion`, `fecha_baja`) VALUES
(1, 71, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(2, 72, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(3, 73, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(4, 74, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(5, 75, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(6, 76, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(7, 77, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(8, 78, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(9, 79, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(10, 80, 3, 6, '', '2025-10-31 18:22:37', '2025-11-02 01:39:05'),
(16, 3, 3, 5, 'inscrito', '2025-10-31 18:23:14', NULL),
(17, 7, 3, 5, 'inscrito', '2025-10-31 18:23:14', NULL),
(19, 71, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(20, 72, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(21, 73, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(22, 74, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(23, 75, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(24, 76, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(25, 77, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(26, 78, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(27, 79, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(28, 80, 2, 6, '', '2025-10-31 18:32:32', '2025-11-02 01:39:05'),
(34, 4, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(35, 8, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(36, 81, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(37, 82, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(38, 83, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(39, 84, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(40, 85, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(41, 86, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(42, 87, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(43, 88, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(44, 89, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(45, 90, 3, 7, '', '2025-10-31 18:34:03', '2025-11-02 01:38:30'),
(49, 91, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(50, 92, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(51, 93, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(52, 94, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(53, 95, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(54, 96, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(55, 97, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(56, 98, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(57, 99, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(58, 100, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(59, 101, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(60, 102, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(61, 103, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(62, 104, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(63, 105, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(64, 106, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(65, 107, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(66, 108, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(67, 109, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(68, 110, 3, 10, 'inscrito', '2025-10-31 18:48:51', NULL),
(80, 2, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(81, 10, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(82, 121, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(83, 122, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(84, 123, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(85, 124, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(86, 125, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(87, 126, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(88, 127, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(89, 128, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(90, 129, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(91, 130, 3, 12, 'inscrito', '2025-10-31 19:22:18', NULL),
(99, 131, 3, 5, 'inscrito', '2025-11-02 01:33:15', NULL),
(100, 4, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(101, 8, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(102, 81, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(103, 82, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(104, 83, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(105, 84, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(106, 85, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(107, 86, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(108, 87, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(109, 88, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(110, 89, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(111, 90, 4, 7, 'inscrito', '2025-11-02 01:38:30', NULL),
(115, 71, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(116, 72, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(117, 73, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(118, 74, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(119, 75, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(120, 76, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(121, 77, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(122, 78, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(123, 79, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL),
(124, 80, 4, 6, 'inscrito', '2025-11-02 01:39:05', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_alumnos`
--

CREATE TABLE `asignaciones_alumnos` (
  `id_asignacion_alumno` int(11) NOT NULL,
  `id_nombre_semestre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_docentes`
--

CREATE TABLE `asignaciones_docentes` (
  `id_asignacion_docente` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `id_nombre_materia` int(11) NOT NULL,
  `id_nombre_profesor_materia_grupo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignaciones_docentes`
--

INSERT INTO `asignaciones_docentes` (`id_asignacion_docente`, `id_docente`, `id_nombre_materia`, `id_nombre_profesor_materia_grupo`) VALUES
(1, 8, 2, 1),
(2, 3, 9, 3),
(3, 6, 4, 4),
(4, 7, 3, 5),
(5, 4, 3, 6),
(10, 14, 8, 11),
(13, 16, 18, 14),
(14, 16, 11, 15),
(15, 16, 17, 16),
(16, 16, 15, 17),
(17, 16, 7, 18),
(18, 16, 16, 19),
(19, 16, 19, 20),
(20, 16, 20, 21);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_grupo_alumno`
--

CREATE TABLE `asignaciones_grupo_alumno` (
  `id_asignacion_grupo_alumno` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignaciones_grupo_alumno`
--

INSERT INTO `asignaciones_grupo_alumno` (`id_asignacion_grupo_alumno`, `id_grupo`, `id_alumno`, `fecha_asignacion`) VALUES
(7, 5, 3, '2025-10-19 00:09:16'),
(8, 5, 7, '2025-10-19 00:09:16'),
(9, 6, 79, '2025-10-19 00:51:42'),
(10, 6, 78, '2025-10-19 00:51:42'),
(11, 6, 74, '2025-10-19 00:51:42'),
(12, 6, 71, '2025-10-19 00:51:42'),
(13, 6, 75, '2025-10-19 00:51:42'),
(14, 6, 72, '2025-10-19 00:51:42'),
(15, 6, 76, '2025-10-19 00:51:42'),
(16, 6, 73, '2025-10-19 00:51:42'),
(17, 6, 80, '2025-10-19 00:51:42'),
(18, 6, 77, '2025-10-19 00:51:42'),
(19, 7, 89, '2025-10-30 01:53:07'),
(20, 7, 88, '2025-10-30 01:53:07'),
(21, 7, 8, '2025-10-30 01:53:07'),
(22, 7, 4, '2025-10-30 01:53:07'),
(23, 7, 84, '2025-10-30 01:53:07'),
(24, 7, 81, '2025-10-30 01:53:07'),
(25, 7, 85, '2025-10-30 01:53:07'),
(26, 7, 82, '2025-10-30 01:53:07'),
(27, 7, 86, '2025-10-30 01:53:07'),
(28, 7, 83, '2025-10-30 01:53:07'),
(29, 7, 90, '2025-10-30 01:53:07'),
(30, 7, 87, '2025-10-30 01:53:07'),
(31, 10, 108, '2025-11-01 00:42:11'),
(32, 10, 99, '2025-11-01 00:42:11'),
(33, 10, 109, '2025-11-01 00:42:11'),
(34, 10, 98, '2025-11-01 00:42:11'),
(35, 10, 94, '2025-11-01 00:42:11'),
(36, 10, 91, '2025-11-01 00:42:11'),
(37, 10, 95, '2025-11-01 00:42:11'),
(38, 10, 103, '2025-11-01 00:42:11'),
(39, 10, 110, '2025-11-01 00:42:11'),
(40, 10, 92, '2025-11-01 00:42:11'),
(41, 10, 96, '2025-11-01 00:42:11'),
(42, 10, 106, '2025-11-01 00:42:11'),
(43, 10, 104, '2025-11-01 00:42:11'),
(44, 10, 102, '2025-11-01 00:42:11'),
(45, 10, 93, '2025-11-01 00:42:12'),
(46, 10, 100, '2025-11-01 00:42:12'),
(47, 10, 105, '2025-11-01 00:42:12'),
(48, 10, 97, '2025-11-01 00:42:12'),
(49, 10, 101, '2025-11-01 00:42:12'),
(50, 10, 107, '2025-11-01 00:42:12'),
(51, 12, 129, '2025-11-01 00:59:44'),
(52, 12, 128, '2025-11-01 00:59:44'),
(53, 12, 121, '2025-11-01 00:59:44'),
(54, 12, 124, '2025-11-01 00:59:44'),
(55, 12, 126, '2025-11-01 00:59:44'),
(56, 12, 2, '2025-11-01 00:59:44'),
(57, 12, 125, '2025-11-01 00:59:44'),
(58, 12, 122, '2025-11-01 00:59:44'),
(59, 12, 130, '2025-11-01 00:59:44'),
(60, 12, 10, '2025-11-01 00:59:44'),
(61, 12, 127, '2025-11-01 00:59:44'),
(62, 12, 123, '2025-11-01 00:59:44'),
(63, 5, 131, '2025-11-01 04:07:58'),
(64, 15, 132, '2025-11-03 01:14:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignar_materias`
--

CREATE TABLE `asignar_materias` (
  `id_asignacion` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_nombre_grupo_int` int(10) UNSIGNED DEFAULT NULL,
  `id_nombre_materia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignar_materias`
--

INSERT INTO `asignar_materias` (`id_asignacion`, `id_materia`, `id_nombre_grupo_int`, `id_nombre_materia`) VALUES
(7, 2, 11, 7),
(8, 10, 6, 8),
(9, 4, 8, 11),
(10, 1, 8, 12),
(11, 1, 5, 13),
(12, 7, 6, 14),
(13, 10, 13, 15),
(14, 4, 13, 16),
(15, 6, 13, 17),
(16, 1, 13, 18),
(17, 3, 7, 19),
(18, 9, 13, 20),
(19, 8, 13, 21);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias_alumnos`
--

CREATE TABLE `asistencias_alumnos` (
  `id_asistencia` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_asignacion_docente` int(11) NOT NULL,
  `id_grupo` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('P','A','J','R') NOT NULL DEFAULT 'P',
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `asistencias_alumnos`
--

INSERT INTO `asistencias_alumnos` (`id_asistencia`, `id_alumno`, `id_asignacion_docente`, `id_grupo`, `fecha`, `estado`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 89, 19, 7, '2025-11-23', 'A', NULL, '2025-11-23 19:53:06', NULL),
(2, 88, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(3, 8, 19, 7, '2025-11-23', 'A', NULL, '2025-11-23 19:53:06', NULL),
(4, 4, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(5, 84, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(6, 81, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(7, 85, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(8, 82, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(9, 86, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(10, 83, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(11, 90, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(12, 87, 19, 7, '2025-11-23', 'P', NULL, '2025-11-23 19:53:06', NULL),
(13, 89, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(14, 88, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(15, 8, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(16, 4, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(17, 84, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(18, 81, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(19, 85, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(20, 82, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(21, 86, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(22, 83, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(23, 90, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL),
(24, 87, 19, 7, '2025-11-24', 'P', NULL, '2025-11-24 02:23:02', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id_aula` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id_aula`, `nombre`, `capacidad`) VALUES
(1, 'aula 1', 40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_alumnos`
--

CREATE TABLE `calificaciones_alumnos` (
  `id` int(11) NOT NULL,
  `id_asignacion_docente` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `tipo` enum('tarea','proyecto','examen','asistencia') NOT NULL,
  `id_actividad` int(11) NOT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_asistencia`
--

CREATE TABLE `calificaciones_asistencia` (
  `id_cal_asistencia` int(11) NOT NULL,
  `id_asignacion_docente` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `puntos_asistencia` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `calificaciones_asistencia`
--

INSERT INTO `calificaciones_asistencia` (`id_cal_asistencia`, `id_asignacion_docente`, `id_alumno`, `puntos_asistencia`) VALUES
(1, 16, 132, 10.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calif_config`
--

CREATE TABLE `calif_config` (
  `id_asignacion_docente` int(11) NOT NULL,
  `pct_tareas` decimal(5,2) NOT NULL DEFAULT 34.00,
  `pct_proyectos` decimal(5,2) NOT NULL DEFAULT 33.00,
  `pct_examenes` decimal(5,2) NOT NULL DEFAULT 33.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calif_config`
--

INSERT INTO `calif_config` (`id_asignacion_docente`, `pct_tareas`, `pct_proyectos`, `pct_examenes`, `updated_at`) VALUES
(16, 34.00, 33.00, 33.00, '2025-11-21 18:00:51'),
(17, 34.00, 33.00, 33.00, '2025-11-21 20:07:34'),
(19, 34.00, 33.00, 33.00, '2025-11-21 20:34:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id_carrera` int(11) NOT NULL,
  `nombre_carrera` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_anios` int(11) DEFAULT 3,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`id_carrera`, `nombre_carrera`, `descripcion`, `duracion_anios`, `fecha_creacion`) VALUES
(1, 'Ingeniería en Sistemas', 'Carrera enfocada en el diseño, desarrollo, implementación y mantenimiento de sistemas.', 1, '2025-10-17 22:47:55'),
(2, 'Ingeneria en IndustrialDS', 'Carrera enfocada en optimizar procesos, recursos y sistemas dentro de empresas e industrias.', 3, '2025-10-18 00:51:04'),
(3, 'Ingeniería Mecatrónica', 'Integra mecánica, electrónica y programación para crear sistemas automatizados, robots y maquinaria.', 4, '2025-10-18 21:55:43'),
(4, 'Licenciatura en Lengua Inglesa', 'Forma profesionales con dominio del idioma inglés, capacitados para la enseñanza, traducción e interpretación.', 3, '2025-10-18 21:56:42'),
(5, 'Licenciatura en Mercadotecnia', 'Carrera enfocada en el análisis del mercado y el comportamiento del consumidor para crear estrategias efectivas de promoción.', 4, '2025-10-18 21:57:51'),
(6, 'Ingeniería en softwere', 'softwere', 3, '2025-10-21 03:37:16'),
(7, 'PRUEBA SECRETARIA', 'SSNNDKJS', 1, '2025-11-02 03:27:40'),
(8, 'TECNOLOGÍAS DE LA INFORMACIÓN E INNOVACIÓN DIGITAL', 'softwere', 5, '2025-11-03 01:10:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_nombres_grupo`
--

CREATE TABLE `cat_nombres_grupo` (
  `id_nombre_grupo` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_nombres_grupo`
--

INSERT INTO `cat_nombres_grupo` (`id_nombre_grupo`, `nombre`) VALUES
(10, 'II1G1'),
(5, 'IM1G1'),
(6, 'IM1G2'),
(7, 'LLI1G1'),
(11, 'LLI1G2'),
(8, 'LLI1G3'),
(12, 'LLI1G4'),
(9, 'LM1G1'),
(13, 'TIID1G1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_nombres_materias`
--

CREATE TABLE `cat_nombres_materias` (
  `id_nombre_materia` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cat_nombres_materias`
--

INSERT INTO `cat_nombres_materias` (`id_nombre_materia`, `nombre`) VALUES
(2, 'ADMI-II1G2'),
(14, 'ADMI-IM1G2'),
(9, 'ADMI-IS1G2'),
(8, 'COMU-IM1G2'),
(15, 'COMU-TIID1G1'),
(3, 'CONT-II1G1'),
(4, 'CONT-II1G2'),
(21, 'CONT-TIID1G1'),
(5, 'DISE-II1G1'),
(6, 'DISE-II1G2'),
(20, 'DISE-TIID1G1'),
(7, 'FISI-LLI1G2'),
(17, 'INGL-TIID1G1'),
(13, 'MATE-IM1G1'),
(12, 'MATE-IS1G1'),
(18, 'MATE-TIID1G1'),
(11, 'PROG-IS1G1'),
(16, 'PROG-TIID1G1'),
(19, 'QUIM-LLI1G1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_nombres_semestre`
--

CREATE TABLE `cat_nombres_semestre` (
  `id_nombre_semestre` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_nombres_semestre`
--

INSERT INTO `cat_nombres_semestre` (`id_nombre_semestre`, `nombre`) VALUES
(2, 'Ingeneria en Industrial 1'),
(1, 'Ingeniería en Sistemas 9'),
(6, 'Ingeniería en softwere 1'),
(3, 'Ingeniería Mecatrónica 1'),
(7, 'Ingeniería Mecatrónica 7'),
(4, 'Licenciatura en Lengua Inglesa 1'),
(5, 'Licenciatura en Mercadotecnia 3'),
(8, 'TECNOLOGÍAS DE LA INFORMACIÓN E INNOVACIÓN DIGITAL 1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_nombre_profesor_materia_grupo`
--

CREATE TABLE `cat_nombre_profesor_materia_grupo` (
  `id_nombre_profesor_materia_grupo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_nombre_profesor_materia_grupo`
--

INSERT INTO `cat_nombre_profesor_materia_grupo` (`id_nombre_profesor_materia_grupo`, `nombre`) VALUES
(1, 'Profesor Miguel Flores Morales - ADMI-II1G2'),
(3, 'Profesor Ana Patricia Rodríguez Santos - ADMI-IS1G2'),
(4, 'Profesor Luis Ángel Pérez Castillo - CONT-II1G2'),
(5, 'Profesor Alejandra Vega Flores - CONT-II1G1'),
(6, 'Profesor Ricardo Hernández Torres - CONT-II1G1'),
(11, 'Profesor Brayan David Casas Morales - COMU-IM1G2'),
(14, 'Profesor Angel Loza Flores - MATE-TIID1G1'),
(15, 'Profesor Angel Loza Flores - PROG-IS1G1'),
(16, 'Profesor Angel Loza Flores - INGL-TIID1G1'),
(17, 'Profesor Angel Loza Flores - COMU-TIID1G1'),
(18, 'Profesor Angel Loza Flores - FISI-LLI1G2'),
(19, 'Profesor Angel Loza Flores - PROG-TIID1G1'),
(20, 'Profesor Angel Loza Flores - QUIM-LLI1G1'),
(21, 'Profesor Angel Loza Flores - DISE-TIID1G1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chats`
--

CREATE TABLE `chats` (
  `id_chat` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `chats`
--

INSERT INTO `chats` (`id_chat`, `id_docente`, `id_alumno`, `id_grupo`, `fecha_creacion`) VALUES
(1, 16, 132, 15, '2025-11-12 01:26:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciclos_escolares`
--

CREATE TABLE `ciclos_escolares` (
  `id_ciclo` int(11) NOT NULL,
  `clave` varchar(12) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciclos_escolares`
--

INSERT INTO `ciclos_escolares` (`id_ciclo`, `clave`, `fecha_inicio`, `fecha_fin`, `activo`) VALUES
(2, 'ABRIL - MA', '2020-06-30', '2025-10-31', 1),
(3, 'sbdah', '2017-07-06', '2025-10-31', 1),
(4, 'ASJHA', '2012-01-31', '2025-10-31', 1),
(5, 'SNDJSdsd', '2025-11-05', '2025-11-26', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `id_docente` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `curp` varchar(18) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sexo` enum('Masculino','Femenino','Otro') NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `correo_personal` varchar(120) DEFAULT NULL,
  `matricula` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estatus` enum('activo','baja','suspendido') NOT NULL DEFAULT 'activo',
  `fecha_baja` date DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `nivel_estudios` enum('Licenciatura','Maestría','Doctorado','Otro') NOT NULL,
  `area_especialidad` varchar(150) DEFAULT NULL,
  `universidad_egreso` varchar(150) DEFAULT NULL,
  `cedula_profesional` varchar(20) DEFAULT NULL,
  `idiomas` varchar(150) DEFAULT NULL,
  `puesto` varchar(100) NOT NULL,
  `tipo_contrato` enum('Tiempo Completo','Medio Tiempo','Asignatura','Honorarios') NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `contacto_emergencia` varchar(100) DEFAULT NULL,
  `parentesco_emergencia` varchar(50) DEFAULT NULL,
  `telefono_emergencia` varchar(15) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docentes`
--

INSERT INTO `docentes` (`id_docente`, `nombre`, `apellido_paterno`, `apellido_materno`, `curp`, `rfc`, `fecha_nacimiento`, `sexo`, `telefono`, `direccion`, `correo_personal`, `matricula`, `password`, `estatus`, `fecha_baja`, `deleted_at`, `nivel_estudios`, `area_especialidad`, `universidad_egreso`, `cedula_profesional`, `idiomas`, `puesto`, `tipo_contrato`, `fecha_ingreso`, `contacto_emergencia`, `parentesco_emergencia`, `telefono_emergencia`, `fecha_registro`) VALUES
(1, 'María Elena SEC', 'Gómez', 'Ramírez', 'GORM800101MDFRRL0', 'GORM800101ABC', '1980-01-01', 'Femenino', '5512345678', 'Av. Insurgentes 123, CDMX', 'maria.gomez@ut.edu.mx', 'D001', 'hash12345a', 'baja', '2025-11-01', NULL, 'Doctorado', 'Educación', 'UNAM', '1234567', 'Español, Inglés', 'Profesora', 'Tiempo Completo', '2010-08-15', 'Rosa Ramírez', 'Madre', '5519876543', '2025-10-18 22:53:44'),
(2, 'Juan Carlos', 'Martínez', 'Luna', 'MALJ790202HDFRRL02', 'MALJ790202DEF', '1979-02-02', 'Masculino', '5523456789', 'Calle Hidalgo 45, Puebla', 'juan.martinez@ut.edu.mx', 'D002', 'hash12345b', 'activo', NULL, NULL, 'Licenciatura', 'Electrónica', 'IPN', '7654321', 'Español, Inglés', 'Docente de Ingeniería', 'Asignatura', '2015-02-20', 'Laura Luna', 'Esposa', '5523456780', '2025-10-18 22:53:44'),
(3, 'Ana PatriciaDWJDS', 'Rodríguez', 'Santos', 'ROSA850303MDFRRL03', 'ROSA850303GHI', '1985-03-03', 'Femenino', '5534567890', 'Col. Centro 33, Querétaro', 'ana.rodriguez@ut.edu.mx', 'D003', 'hash12345c', 'suspendido', NULL, NULL, 'Maestría', 'Administración', 'UANL', '8765432', 'Español, Inglés', 'Coordinadora Académica', 'Tiempo Completo', '2012-07-10', 'Pedro Santos', 'Padre', '5545678901', '2025-10-18 22:53:44'),
(4, 'Ricardo', 'Hernández', 'Torres', 'HETR820404HDFRRL04', 'HETR820404JKL', '1982-04-04', 'Masculino', '5545678902', 'Av. Reforma 220, Monterrey', 'ricardo.hernandez@ut.edu.mx', 'D004', 'hash12345d', 'activo', NULL, NULL, 'Doctorado', 'Robótica', 'ITESM', '2345678', 'Español, Inglés', 'Profesor Investigador', 'Tiempo Completo', '2009-09-05', 'Laura Torres', 'Esposa', '5556789012', '2025-10-18 22:53:44'),
(6, 'Luis Ángel', 'Pérez', 'Castillo', 'PECL870606HDFRRL06', 'PECL870606PQR', '1987-06-06', 'Masculino', '5567890124', 'Col. Roma Norte 56, CDMX', 'luis.perez@ut.edu.mx', 'D006', 'hash12345f', 'baja', '2025-11-01', NULL, 'Maestría', 'Informática', 'UNAM', '4567890', 'Español, Inglés', 'Docente de Sistemas', 'Tiempo Completo', '2014-05-30', 'Carla Castillo', 'Esposa', '5578901234', '2025-10-18 22:53:44'),
(7, 'Alejandra', 'Vega', 'Flores', 'VEFA910707MDFRRL07', 'VEFA910707STU', '1991-07-07', 'Femenino', '5578901235', 'Calle Sur 78, Veracruz', 'alejandra.vega@ut.edu.mx', 'D007', 'hash12345g', 'activo', NULL, NULL, 'Licenciatura', 'Mercadotecnia', 'UV', '5678901', 'Español, Inglés', 'Docente de Negocios', 'Medio Tiempo', '2020-09-01', 'Héctor Flores', 'Padre', '5589012345', '2025-10-18 22:53:44'),
(8, 'Miguel', 'Flores', 'Morales', 'FLOM880808HDFRRL08', 'FLOM880808VWX', '1988-08-08', 'Masculino', '5589012346', 'Av. Independencia 34, Oaxaca', 'miguel.flores@ut.edu.mx', 'D008', 'hash12345h', 'activo', NULL, NULL, 'Maestría', 'Mecatrónica', 'UABJO', '6789012', 'Español, Inglés', 'Docente de Mecatrónica', 'Tiempo Completo', '2011-03-25', 'Rosa Morales', 'Madre', '5590123456', '2025-10-18 22:53:44'),
(9, 'Valeria', 'Cruz', 'Núñez', 'CRNV890909MDFRRL09', 'CRNV890909YZA', '1989-09-09', 'Femenino', '5590123457', 'Calle Norte 22, Toluca', 'valeria.cruz@ut.edu.mx', 'D009', 'hash12345i', 'activo', NULL, NULL, 'Doctorado', 'Educación', 'UAEMEX', '7890123', 'Español, Inglés', 'Directora de Carrera', 'Tiempo Completo', '2008-06-15', 'Elena Núñez', 'Madre', '5501234567', '2025-10-18 22:53:44'),
(10, 'Jorge Luis', 'Reyes', 'Ortiz', 'REOJ900101HDFRRL10', 'REOJ900101BCD', '1990-01-01', 'Masculino', '5501234568', 'Av. Morelos 77, Mérida', 'jorge.reyes@ut.edu.mx', 'D010', 'hash12345j', 'activo', NULL, NULL, 'Maestría', 'Energías Renovables', 'UADY', '8901234', 'Español, Inglés', 'Profesor de Energías', 'Asignatura', '2019-04-10', 'Fernando Ortiz', 'Padre', '5512345670', '2025-10-18 22:53:44'),
(14, 'Brayan David', 'Casas', 'Morales', '2312dw3adaaw', '2DWQDQDWD', '2004-04-20', 'Masculino', '86136213718', 'KJABABDOJABCKUN UOFBIU', 'angelantonio33loza@gmail.com', 'DOC0002', '$2y$10$x.JLrbRz30E9jj2DWnlcduwBMo7q9O4dlAl5cV54e4hb/kHFov3la', 'activo', NULL, NULL, 'Licenciatura', '', 'ewewewewe', '', 'adsadsada', 'matematicas', 'Tiempo Completo', '2025-10-21', 'hjfuyj', '32323', '21313132', '2025-10-21 20:17:25'),
(15, 'EJEMPLO SEC', 'JDJ', 'JSDJSD', 'SSKDWDKS', 'WJJEWEJWJE', '2025-10-15', 'Femenino', '23273712838', 'SDNMankjqjwe', 'ejempSNNSDlo2@ut.edu', 'DOC0003', '$2y$10$Ly7ooW/0smfB5v5YLqogAebCrd/c5mGiLx4NgUz0yL3ZqdmAp5c4O', 'activo', NULL, NULL, 'Licenciatura', 'WDBS', 'NWSN NAS', 'Entregada', 'Español, Inglés', 'UWEHDJS', 'Asignatura', '2025-10-14', 'SDNSD', '22DSNSD', '2930123021', '2025-10-28 06:50:19'),
(16, 'Angel', 'Loza', 'Flores', 'LOFA050620HNLZLNA0', 'SCFWEFWCFCAFA', '2005-06-20', 'Masculino', '323242341', 'fwesacae', 'angelantonio3loza@gmail.com', 'DOC0004', '$2y$10$W4evAAII23NcktuvsKkJvOr7wgz7fFAb9/p4eHnNJc0E4F5Telwc.', 'activo', NULL, NULL, 'Doctorado', '', '12414142qwdqwqwd', '', 'adsadsada', 'matematicas', 'Tiempo Completo', '2025-11-02', 'Aleskis', 'Amigo', '342342425252352', '2025-11-03 01:07:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas_alumnos`
--

CREATE TABLE `entregas_alumnos` (
  `id_entrega` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_entrega` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_calificacion` datetime DEFAULT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL,
  `estado` varchar(30) DEFAULT 'Entregada',
  `retroalimentacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entregas_alumnos`
--

INSERT INTO `entregas_alumnos` (`id_entrega`, `id_tarea`, `id_alumno`, `archivo`, `fecha_entrega`, `fecha_calificacion`, `calificacion`, `estado`, `retroalimentacion`) VALUES
(1, 4, 132, 'uploads/entregas/entrega_6908f40694833_Angel_Loza.pdf', '2025-11-03 18:27:18', '2025-11-02 23:28:50', 5.00, 'Entregada', 'le falta '),
(2, 7, 132, 'uploads/entregas/entrega_69091cfc64793_Evi2-ADT-AALF (3).docx', '2025-11-03 21:22:04', '2025-11-03 17:41:16', 10.00, 'Entregada', 'excelente trabajo'),
(3, 8, 132, 'uploads/entregas/entrega_690b795be3619_Seguridad informática - Glosario - Angel Loza 22624.pdf', '2025-11-05 16:20:43', NULL, NULL, 'Devuelta', 'regreso'),
(4, 10, 132, 'uploads/entregas/entrega_690b8fc3211f5_Evi2-ADT-AALF (2).docx', '2025-11-05 17:56:19', NULL, 5.00, 'Calificada', '5'),
(5, 9, 132, 'uploads/entregas/entrega_690bfb5d3516d_entrega_69091cfc64793_Evi2-ADT-AALF (3).docx', '2025-11-06 01:35:25', NULL, NULL, 'Entregada', NULL),
(6, 21, 132, 'uploads/entregas/entrega_6913e31d65f00_Act1-ADT-AALF.pdf', '2025-11-12 01:30:05', '2025-11-24 00:55:16', 10.00, 'Calificada', 'aun le falta'),
(7, 23, 89, NULL, '2025-11-22 04:02:09', '2025-11-21 22:02:31', 5.00, 'Calificada', NULL),
(8, 24, 89, NULL, '2025-11-22 04:02:09', '2025-11-21 22:02:31', 5.00, 'Calificada', NULL),
(9, 23, 88, NULL, '2025-11-22 04:02:31', '2025-11-21 22:02:31', 10.00, 'Calificada', NULL),
(10, 24, 88, NULL, '2025-11-22 04:02:31', '2025-11-21 22:02:31', 10.00, 'Calificada', NULL),
(11, 25, 132, NULL, '2025-11-22 04:11:32', '2025-11-21 22:11:32', 10.00, 'Calificada', NULL),
(12, 26, 132, NULL, '2025-11-22 04:11:32', '2025-11-21 22:11:32', 8.00, 'Calificada', NULL),
(13, 16, 132, NULL, '2025-11-24 05:57:38', '2025-11-24 00:55:16', 8.00, 'Calificada', NULL),
(14, 17, 132, NULL, '2025-11-24 05:57:38', '2025-11-24 00:55:16', 9.00, 'Calificada', NULL),
(15, 22, 132, NULL, '2025-11-24 05:57:38', '2025-11-24 00:55:16', 10.00, 'Calificada', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas_evaluaciones_alumnos`
--

CREATE TABLE `entregas_evaluaciones_alumnos` (
  `id_entrega` int(11) NOT NULL,
  `id_evaluacion` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `fecha_entrega` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Entregada','Devuelta','Reentregada','Calificada') DEFAULT 'Entregada',
  `calificacion` decimal(5,2) DEFAULT NULL,
  `retroalimentacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entregas_evaluaciones_alumnos`
--

INSERT INTO `entregas_evaluaciones_alumnos` (`id_entrega`, `id_evaluacion`, `id_alumno`, `archivo`, `fecha_entrega`, `estado`, `calificacion`, `retroalimentacion`) VALUES
(1, 2, 89, '', '2025-11-21 22:02:09', 'Calificada', 5.00, NULL),
(2, 2, 88, '', '2025-11-21 22:02:31', 'Calificada', 10.00, NULL),
(3, 3, 132, '', '2025-11-21 22:11:32', 'Calificada', 9.00, NULL),
(4, 1, 132, '', '2025-11-23 23:57:38', 'Calificada', 7.00, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_docente`
--

CREATE TABLE `evaluaciones_docente` (
  `id_evaluacion` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `id_asignacion_docente` int(11) DEFAULT NULL,
  `titulo` varchar(200) NOT NULL,
  `tipo` enum('Examen','Proyecto Final','Otro') NOT NULL DEFAULT 'Proyecto Final',
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluaciones_docente`
--

INSERT INTO `evaluaciones_docente` (`id_evaluacion`, `id_docente`, `id_asignacion_docente`, `titulo`, `tipo`, `descripcion`, `archivo`, `fecha_publicacion`, `fecha_cierre`) VALUES
(1, 16, 16, 'Proyecto prueba 1', 'Proyecto Final', 'Proyecto', 'uploads/evaluaciones/eval_69167c16066ae_actividad 1  AALF.pdf', '2025-11-13 18:47:18', '2025-11-20 23:59:59'),
(2, 16, 19, 'Proyecto 1', 'Proyecto Final', 'aja', NULL, '2025-11-21 20:35:51', '2025-11-30 23:59:59'),
(3, 16, 20, 'Proyecto 1', 'Proyecto Final', 'asdadsa', NULL, '2025-11-21 22:10:44', '2025-11-30 23:59:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes`
--

CREATE TABLE `examenes` (
  `id_examen` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `id_asignacion_docente` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` date NOT NULL,
  `estado` enum('Activo','Cerrado') DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examenes`
--

INSERT INTO `examenes` (`id_examen`, `id_docente`, `id_asignacion_docente`, `titulo`, `descripcion`, `fecha_publicacion`, `fecha_cierre`, `estado`) VALUES
(1, 16, 18, 'Parcial 1', 'Responde las preguntas', '2025-11-19 18:02:49', '2025-11-20', 'Activo'),
(2, 16, 13, 'prueba', 'jhggffjh,hkjl', '2025-11-19 20:05:50', '2025-11-20', 'Activo'),
(3, 16, 19, 'Parcial 1', 'adadsa', '2025-11-21 20:36:36', '2025-11-30', 'Activo'),
(4, 16, 20, 'Parcial 1', '-', '2025-11-21 22:11:11', '2025-11-30', 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_calificaciones`
--

CREATE TABLE `examen_calificaciones` (
  `id_examen_calificacion` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `calificacion` decimal(5,2) NOT NULL,
  `fecha_calificacion` datetime DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen_calificaciones`
--

INSERT INTO `examen_calificaciones` (`id_examen_calificacion`, `id_examen`, `id_alumno`, `calificacion`, `fecha_calificacion`, `observaciones`) VALUES
(1, 3, 89, 10.00, '2025-11-21 20:37:50', NULL),
(11, 3, 88, 9.00, '2025-11-21 22:02:31', NULL),
(12, 4, 132, 10.00, '2025-11-21 22:11:32', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_preguntas`
--

CREATE TABLE `examen_preguntas` (
  `id_pregunta` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `tipo` enum('abierta','opcion') NOT NULL,
  `pregunta` text NOT NULL,
  `puntos` decimal(5,2) NOT NULL DEFAULT 1.00,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen_preguntas`
--

INSERT INTO `examen_preguntas` (`id_pregunta`, `id_examen`, `tipo`, `pregunta`, `puntos`, `orden`) VALUES
(1, 1, 'opcion', 'Que es php', 1.00, 1),
(2, 1, 'abierta', 'di algo', 1.00, 2),
(3, 2, 'opcion', 'pregunta', 1.00, 1),
(4, 3, 'abierta', 'sadadsada', 1.00, 1),
(5, 4, 'abierta', 'adsadadad', 1.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_pregunta_opciones`
--

CREATE TABLE `examen_pregunta_opciones` (
  `id_opcion` int(11) NOT NULL,
  `id_pregunta` int(11) NOT NULL,
  `texto_opcion` varchar(255) NOT NULL,
  `es_correcta` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen_pregunta_opciones`
--

INSERT INTO `examen_pregunta_opciones` (`id_opcion`, `id_pregunta`, `texto_opcion`, `es_correcta`) VALUES
(1, 1, 'Un lenguaje de programacion', 1),
(2, 1, 'Una plataforma de estudio', 0),
(3, 1, 'si', 0),
(4, 3, 'jcjgdhgdjy', 0),
(5, 3, 'klhkio', 0),
(6, 3, 'efaesff', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_respuestas`
--

CREATE TABLE `examen_respuestas` (
  `id_respuesta` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_pregunta` int(11) NOT NULL,
  `respuesta_texto` text DEFAULT NULL,
  `id_opcion` int(11) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen_respuestas`
--

INSERT INTO `examen_respuestas` (`id_respuesta`, `id_examen`, `id_alumno`, `id_pregunta`, `respuesta_texto`, `id_opcion`, `fecha_envio`) VALUES
(1, 1, 132, 2, 'ssisiss', NULL, '2025-11-19 18:56:13'),
(2, 1, 132, 1, NULL, 1, '2025-11-19 18:56:13'),
(3, 2, 132, 3, NULL, 4, '2025-11-19 20:06:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `id_nombre_semestre` int(11) NOT NULL,
  `id_nombre_grupo` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `id_nombre_semestre`, `id_nombre_grupo`) VALUES
(5, 3, 5),
(6, 3, 6),
(7, 4, 7),
(9, 4, 8),
(10, 5, 9),
(12, 2, 10),
(13, 4, 11),
(14, 4, 12),
(15, 8, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id_horario` int(11) NOT NULL,
  `id_nombre_profesor_materia_grupo` int(11) NOT NULL,
  `id_aula` int(11) NOT NULL,
  `dia` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  `bloque` tinyint(2) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id_horario`, `id_nombre_profesor_materia_grupo`, `id_aula`, `dia`, `bloque`, `hora_inicio`, `hora_fin`) VALUES
(1, 17, 1, 'Lunes', 3, '08:40:00', '09:30:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre_materia` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `nombre_materia`) VALUES
(1, 'Matemáticas Aplicadas'),
(2, 'Física General'),
(3, 'Química Básica'),
(4, 'Programación I'),
(5, 'Programación II'),
(6, 'Inglés Técnico'),
(7, 'Administración de Proyecto'),
(8, 'Contabilidad Financiera'),
(9, 'Diseño y Manufactura Asistida por Computadora'),
(10, 'Comunicación Oral y Escrita'),
(11, 'PRUEBA  EDITAR ');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id_mensaje` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `cuerpo` text NOT NULL,
  `prioridad` enum('normal','alta') NOT NULL DEFAULT 'normal',
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id_mensaje`, `id_admin`, `titulo`, `cuerpo`, `prioridad`, `fecha_envio`) VALUES
(2, 2, 'PRUEBA 2 ejemplo editar', 'PRUEBA 2', 'normal', '2025-11-01 15:02:01'),
(3, 2, 'PRUEBA 3', 'DSJDHS', 'alta', '2025-11-01 17:36:47'),
(4, 2, 'PRUEBA 4', 'SDSJD', 'normal', '2025-11-01 18:08:22'),
(5, 2, 'Mensaje de prueba', 'Semana 8 - Prueba', 'alta', '2025-11-02 12:19:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajesdocente`
--

CREATE TABLE `mensajesdocente` (
  `id_mensaje` int(11) NOT NULL,
  `id_chat` int(11) NOT NULL,
  `remitente` enum('docente','alumno') NOT NULL,
  `contenido` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajesdocente`
--

INSERT INTO `mensajesdocente` (`id_mensaje`, `id_chat`, `remitente`, `contenido`, `leido`, `fecha_envio`) VALUES
(1, 1, 'alumno', 'hola', 0, '2025-11-12 01:31:58'),
(2, 1, 'alumno', 'hola', 0, '2025-11-12 01:35:45'),
(3, 1, 'docente', 'hljg-ilhk_LKJh', 0, '2025-11-12 01:36:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_secretarias`
--

CREATE TABLE `mensajes_secretarias` (
  `id_ms` int(11) NOT NULL,
  `id_mensaje` int(11) NOT NULL,
  `id_secretaria` int(11) NOT NULL,
  `leido_en` datetime DEFAULT NULL,
  `archivado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajes_secretarias`
--

INSERT INTO `mensajes_secretarias` (`id_ms`, `id_mensaje`, `id_secretaria`, `leido_en`, `archivado`) VALUES
(13, 3, 9, NULL, 0),
(24, 2, 9, NULL, 0),
(25, 2, 11, NULL, 0),
(26, 2, 2, NULL, 0),
(27, 2, 6, NULL, 0),
(28, 2, 1, NULL, 0),
(29, 2, 3, NULL, 0),
(30, 2, 7, NULL, 0),
(31, 2, 4, NULL, 0),
(32, 2, 8, '2025-11-01 18:37:09', 0),
(33, 4, 1, NULL, 0),
(34, 5, 8, '2025-11-02 12:20:29', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `tipo` enum('movimiento','mensaje') NOT NULL DEFAULT 'movimiento',
  `titulo` varchar(120) NOT NULL,
  `detalle` text DEFAULT NULL,
  `para_rol` enum('admin','secretaria') NOT NULL DEFAULT 'admin',
  `actor_id` int(11) DEFAULT NULL,
  `recurso` varchar(50) DEFAULT NULL,
  `accion` varchar(30) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `tipo`, `titulo`, `detalle`, `para_rol`, `actor_id`, `recurso`, `accion`, `meta`, `leido`, `created_at`) VALUES
(14, 'movimiento', 'Suspensión retirada', 'Matrícula D006 - ID 6', 'admin', NULL, 'docentes', 'quitar_suspension', '{\"id_docente\":6,\"matricula\":\"D006\"}', 1, '2025-11-02 02:23:14'),
(15, 'movimiento', 'Docente reactivado', 'Matrícula D002 - ID 2', 'admin', NULL, 'docentes', 'reactivar', '{\"id_docente\":2,\"matricula\":\"D002\"}', 1, '2025-11-02 02:23:27'),
(16, 'movimiento', 'Quitar suspensión de alumno', 'La secretaría quitó la suspensión a María Fernanda  EH López (A001).', 'admin', NULL, 'alumno', 'quitar_suspension', '{\"id_alumno\":1}', 1, '2025-11-02 02:24:09'),
(17, 'movimiento', 'Edición de alumno', 'La secretaría editó al alumno Andrea j Santos (A007).', 'admin', NULL, 'alumno', 'edicion', '{\"id_alumno\":7}', 1, '2025-11-02 03:00:30'),
(18, 'movimiento', 'Reactivación de alumno', 'La secretaría reactivó a Ana Sofía jnjjn Ramírez (A003).', 'admin', NULL, 'alumno', 'reactivar', '{\"id_alumno\":3}', 1, '2025-11-02 03:08:20'),
(19, 'movimiento', 'Docente dado de baja', 'Matrícula D002 - ID 2', 'admin', NULL, 'docentes', 'baja', '{\"id_docente\":2,\"matricula\":\"D002\"}', 1, '2025-11-02 03:08:31'),
(20, 'movimiento', 'Docente suspendido', 'Matrícula D004 - ID 4', 'admin', NULL, 'docentes', 'suspender', '{\"id_docente\":4,\"matricula\":\"D004\"}', 1, '2025-11-02 03:08:36'),
(21, 'movimiento', 'Docente dado de baja', 'Matrícula D006 - ID 6', 'admin', NULL, 'docentes', 'baja', '{\"id_docente\":6,\"matricula\":\"D006\"}', 1, '2025-11-02 03:09:13'),
(22, 'movimiento', 'Docente suspendido', 'Matrícula D003 - ID 3', 'admin', NULL, 'docentes', 'suspender', '{\"id_docente\":3,\"matricula\":\"D003\"}', 1, '2025-11-02 03:09:27'),
(23, 'movimiento', 'Baja de alumno', 'La secretaría dio de baja a Ana Sofía jnjjn Ramírez (A003).', 'admin', NULL, 'alumno', 'baja', '{\"id_alumno\":3}', 1, '2025-11-02 03:09:38'),
(24, 'movimiento', 'Suspensión de alumno', 'La secretaría suspendió a Carlos Alberto Gómez (A004).', 'admin', NULL, 'alumno', 'suspension', '{\"id_alumno\":4}', 1, '2025-11-02 03:12:09'),
(25, 'movimiento', 'Suspensión de alumno', 'La secretaría suspendió a Andrea j Santos (A007).', 'admin', NULL, 'alumno', 'suspension', '{\"id_alumno\":7}', 1, '2025-11-02 03:12:18'),
(26, 'movimiento', 'Edición de carrera', 'Se editó la carrera Ingeneria en IndustrialDS.', 'admin', 8, 'carrera', 'edicion', '{\"id_carrera\":2,\"nombre_carrera\":\"Ingeneria en IndustrialDS\"}', 1, '2025-11-02 05:30:26'),
(27, 'movimiento', 'Reactivación de alumno', 'La secretaría reactivó a José Antonio BG Martínez (A002).', 'admin', NULL, 'alumno', 'reactivar', '{\"id_alumno\":2,\"matricula\":\"A002\"}', 1, '2025-11-02 05:40:00'),
(28, 'movimiento', 'Baja de alumno', 'La secretaría dio de baja a José Antonio BG Martínez (A002).', 'admin', NULL, 'alumno', 'baja', '{\"id_alumno\":2,\"matricula\":\"A002\"}', 1, '2025-11-02 05:40:11'),
(29, 'movimiento', 'Reactivación de alumno', 'La secretaría reactivó a José Antonio BG Martínez (A002).', 'admin', 8, 'alumno', 'reactivar', '{\"id_alumno\":2,\"matricula\":\"A002\"}', 1, '2025-11-02 05:44:58'),
(30, 'movimiento', 'Edición de alumno', 'La secretaría editó al alumno Ana Sofía j Ramírez (A003).', 'admin', 8, 'alumno', 'edicion', '{\"id_alumno\":3,\"matricula\":\"A003\",\"id_semestre\":3,\"semestre\":\"Ingeniería Mecatrónica 1\"}', 1, '2025-11-02 05:45:05'),
(31, 'movimiento', 'Docente dado de baja', 'Matrícula D001 - ID 1', 'admin', NULL, 'docentes', 'baja', '{\"id_docente\":1,\"matricula\":\"D001\"}', 1, '2025-11-02 05:46:30'),
(32, 'movimiento', 'Edición de ciclo escolar', 'Se editó el ciclo SNDJSdsd (2025-11-05 a 2025-11-26).', 'admin', 8, 'ciclo', 'edicion', '{\"id_ciclo\":5,\"clave\":\"SNDJSdsd\",\"fecha_inicio\":\"2025-11-05\",\"fecha_fin\":\"2025-11-26\",\"activo\":1}', 1, '2025-11-02 05:49:45'),
(33, 'movimiento', 'Docente actualizado', 'Matrícula D001 - ID 1', 'admin', 8, 'docente', 'editar', '{\"id_docente\":1,\"matricula\":\"D001\"}', 1, '2025-11-02 05:54:57'),
(34, 'movimiento', 'Docente reactivado', 'Matrícula D002 - ID 2', 'admin', 8, 'docente', 'reactivar', '{\"id_docente\":2,\"matricula\":\"D002\"}', 1, '2025-11-02 05:55:02'),
(35, 'movimiento', 'Alta de grupo', 'Se creó el grupo LLI1G4 en Licenciatura en Lengua Inglesa 1.', 'admin', 8, 'grupo', 'alta', '{\"id_grupo\":14,\"id_nombre_semestre\":4,\"id_nombre_grupo\":12,\"nombre_grupo\":\"LLI1G4\",\"nombre_semestre\":\"Licenciatura en Lengua Inglesa 1\"}', 1, '2025-11-02 05:58:17'),
(36, 'movimiento', 'Edición de semestre', 'Se actualizó el semestre a Ingeniería en Sistemas 9.', 'admin', 8, 'semestre', 'edicion', '{\"id_semestre\":1,\"id_carrera\":1,\"nombre_carrera\":\"Ingeniería en Sistemas\",\"semestre\":9,\"id_nombre_semestre\":1,\"nombre_semestre\":\"Ingeniería en Sistemas 9\"}', 1, '2025-11-02 06:02:43'),
(37, 'movimiento', 'Edición de materia', 'Se actualizó la materia \"Administración de Proyectos\" a \"Administración de Proyecto\".', 'admin', 8, 'materia', 'edicion', '{\"id_materia\":7,\"nombre_anterior\":\"Administración de Proyectos\",\"nombre_nuevo\":\"Administración de Proyecto\"}', 1, '2025-11-02 06:22:33'),
(38, 'movimiento', 'Edición de asignación de materia', 'Se actualizó la asignación: \"Comunicación Oral y Escrita\" → \"Física General\", IM1G1 → LLI1G2 (clave FISI-LLI1G2).', 'admin', 8, 'asignacion_materia', 'edicion', '{\"id_asignacion\":7,\"old\":{\"id_materia\":10,\"nombre_materia\":\"Comunicación Oral y Escrita\",\"id_nombre_grupo_int\":5,\"nombre_grupo\":\"IM1G1\",\"id_nombre_materia\":7,\"clave\":\"COMU-IM1G1\"},\"new\":{\"id_materia\":2,\"nombre_materia\":\"Física General\",\"id_nombre_grupo_int\":11,\"nombre_grupo\":\"LLI1G2\",\"id_nombre_materia\":7,\"clave\":\"FISI-LLI1G2\"}}', 1, '2025-11-02 06:49:39'),
(39, 'movimiento', 'Edición de asignación de docente', 'Se actualizó: Alejandra Vega Flores / ADMI-II1G2 → Miguel Flores Morales / ADMI-II1G2.', 'admin', 8, 'asignacion_docente', 'edicion', '{\"id_asignacion_docente\":1,\"old\":{\"id_docente\":7,\"docente\":\"Alejandra Vega Flores\",\"id_nombre_materia\":2,\"clave_materia\":\"ADMI-II1G2\",\"id_cpmg\":1},\"new\":{\"id_docente\":8,\"docente\":\"Miguel Flores Morales\",\"id_nombre_materia\":2,\"clave_materia\":\"ADMI-II1G2\",\"id_cpmg\":1,\"nombre_pmg\":\"Profesor Miguel Flores Morales - ADMI-II1G2\"}}', 1, '2025-11-02 07:20:40'),
(40, 'movimiento', 'Docente dado de baja', 'Matrícula D002 - ID 2', 'admin', 8, 'docente', 'baja', '{\"id_docente\":2,\"matricula\":\"D002\"}', 1, '2025-11-02 18:16:51'),
(41, 'movimiento', 'Suspensión retirada', 'Matrícula D003 - ID 3', 'admin', 8, 'docente', 'quitar_suspension', '{\"id_docente\":3,\"matricula\":\"D003\"}', 1, '2025-11-02 18:30:03'),
(42, 'movimiento', 'Suspensión retirada', 'Matrícula D004 - ID 4', 'admin', 8, 'docente', 'quitar_suspension', '{\"id_docente\":4,\"matricula\":\"D004\"}', 1, '2025-11-02 18:30:08'),
(43, 'movimiento', 'Docente reactivado', 'Matrícula D002 - ID 2', 'admin', 8, 'docente', 'reactivar', '{\"id_docente\":2,\"matricula\":\"D002\"}', 1, '2025-11-02 18:30:14'),
(44, 'movimiento', 'Docente suspendido', 'Matrícula D003 - ID 3', 'admin', 8, 'docente', 'suspender', '{\"id_docente\":3,\"matricula\":\"D003\"}', 1, '2025-11-02 18:30:44'),
(45, 'movimiento', 'Alta de aula', 'Se agregó el aula aula 1.', 'admin', 8, 'aula', 'alta', '{\"id_aula\":1,\"nombre\":\"aula 1\",\"capacidad\":40}', 0, '2025-11-24 06:59:08'),
(46, 'movimiento', 'Alta de pago/cargo', 'Se registró un movimiento para el alumno Angel Antonio Loza Flores (A062).', 'admin', 8, 'pago', 'alta', '{\"id_pago\":2,\"matricula\":\"A062\",\"alumno\":\"Angel Antonio Loza Flores\",\"periodo\":\"Si\",\"concepto\":\"si\",\"monto\":500,\"adeudo\":3000,\"pago\":500,\"condonacion\":1}', 0, '2025-11-24 07:08:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `periodo` varchar(100) NOT NULL,
  `concepto` varchar(200) NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adeudo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `condonacion` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `matricula`, `periodo`, `concepto`, `monto`, `adeudo`, `pago`, `condonacion`, `fecha_registro`) VALUES
(2, 'A062', 'Si', 'si', 500.00, 3000.00, 500.00, 1.00, '2025-11-24 01:08:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recursos_materias`
--

CREATE TABLE `recursos_materias` (
  `id_recurso` int(11) NOT NULL,
  `id_asignacion_docente` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secretarias`
--

CREATE TABLE `secretarias` (
  `id_secretaria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `curp` varchar(18) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sexo` enum('Masculino','Femenino','Otro') NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `correo_institucional` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `contacto_emergencia` varchar(100) DEFAULT NULL,
  `parentesco_emergencia` varchar(50) DEFAULT NULL,
  `telefono_emergencia` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `secretarias`
--

INSERT INTO `secretarias` (`id_secretaria`, `nombre`, `apellido_paterno`, `apellido_materno`, `curp`, `rfc`, `fecha_nacimiento`, `sexo`, `telefono`, `direccion`, `correo_institucional`, `password`, `departamento`, `fecha_ingreso`, `contacto_emergencia`, `parentesco_emergencia`, `telefono_emergencia`) VALUES
(1, 'Ana', 'López', 'Martínez', 'LOMA900202MDFPRN08', 'LOMA900202XYZ', '1990-02-02', 'Femenino', '8123456789', 'Calle Centro 456', 'ana.lopez@institucion.edu', '$2y$10$HashSecretariaDemo1234567890ABCDEFGHijklmnOPQRST', 'Escolar', '2021-01-15', 'Carlos López', 'Hermano', '8122223333'),
(2, 'María', 'González', 'López', 'GOLM900412MDFNPR06', 'GOLM900412AB1', '1990-04-12', 'Masculino', '5512345678', 'Av. Universidad 120, CDMX', 'maria.gonzalez@ut.edu.mx', '$2y$10$OqJ8w0H9PzO8.vzWlqdsKe5sd7e1j3eU1E2c7bW3K5V7T8H4YJ5uS', 'Recursos Humanos', '2022-03-10', 'Carlos González', 'Hermano', '5523456789'),
(3, 'Ana', 'Martínez', 'Reyes', 'MARA920515MDFRYS08', 'MARA920515CD2', '1992-05-15', 'Otro', '5534567890', 'Calle Reforma 45, CDMX', 'ana.martinez@ut.edu.mx', '$2y$10$H3kFJ7wD5lL2yT3pA1qB7mQ4fH8vW9uJ3oR6tE5pY9lZ0nU1aT2fG', 'Dirección Académica', '2021-11-22', 'Lucía Martínez', 'Madre', '5546789012'),
(4, 'Beatriz', 'Ramírez', 'Ortiz', 'RAOB890827MDFRZT03', 'RAOB890827EF3', '1989-08-27', 'Masculino', '5523456789', 'Av. Central 321, CDMX', 'beatriz.ramirez@ut.edu.mx', '$2y$10$B9lW7fG4tR6hP2qK8zM3oJ5sD1eV9xA0cR4bE6nY3uJ2iK1pL8tNq', 'Vinculación', '2023-01-14', 'dsjsjd', 'dshd', '3438249'),
(6, 'Patricia', 'Hernández', 'Vega', 'HEVP940224MDFRGT02', 'HEVP940224JK5', '1994-02-24', 'Femenino', '5567890123', 'Av. Juárez 87, CDMX', 'patricia.hernandez@ut.edu.mx', '$2y$10$T2vB9kH4mL1jS6oQ5wC3pD8xR2eN7fZ0aU4yV5tE8nO9rI6gP1bM', 'Contabilidad', '2021-06-18', 'María Vega', 'Madre', '5543210987'),
(7, 'Elena', 'Navarro', 'Cruz', 'NACE970705MDFRZN09', 'NACE970705PD6', '1997-07-05', '', '5510987654', 'Calle Hidalgo 210, CDMX', 'elena.navarro@ut.edu.mx', '$2y$10$M5nC3lH9tY1rB2eK8zW6sA4pJ0uQ7vE3dN5fT9oG2mL8xC1vR4iS', 'Coordinación General', '2023-07-25', 'Laura Cruz', 'Hermana', '5512340987'),
(8, 'Yuleisy', 'Ocañas', 'Gonzalez', 'OAGY76207B8302', 'YOGY6232390', '2005-08-16', 'Femenino', '8261666033', 'Calle Encino #305 Los Nogales', 'yuleisy.ocañas@institucional.com', '123', 'Escolares', '2025-10-17', '7239782929', 'Hermano', '237293023238'),
(9, 'Yule', 'Gzz', 'Gzz', 'U3Y2U3Y2U37374734', '73678887', '2022-02-10', 'Femenino', '826176789', 'Calle Encino #305 Los Nogales', 'yule.gzz@institucional.com', '$2y$10$TJ2ZXjjBl0iE/hi164G1X.0a7tDfRXlEsQy2GkJmtP04uCm.kOVui', 'Dirección Académica', '2025-10-17', 'Veronica Gonzalez', 'Madre', '826357290'),
(11, 'Ejemplo', 'Ejemplo', 'Ejemplo', 'sjdue83201302', 'EHY23EWUH3278', '2025-10-18', 'Masculino', '28378293821', 'sdkawqe', 'ejemplo.ejemplo@institucional.com', '$2y$10$JpxWS.0EROPBMuU8SfcEBe7QArAzWOQ.LpAHd7yPiRCQ58HqdLLc6', 'Dirección Académica', '2025-10-17', 'Juan Cantú', 'Novio', '3728381932');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `semestres`
--

CREATE TABLE `semestres` (
  `id_semestre` int(11) NOT NULL,
  `semestre` int(10) NOT NULL,
  `id_carrera` int(11) DEFAULT NULL,
  `id_nombre_semestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `semestres`
--

INSERT INTO `semestres` (`id_semestre`, `semestre`, `id_carrera`, `id_nombre_semestre`) VALUES
(1, 9, 1, 1),
(2, 1, 2, 2),
(3, 1, 3, 3),
(4, 1, 4, 4),
(5, 3, 5, 5),
(6, 1, 6, 6),
(7, 7, 3, 7),
(8, 1, 8, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_materias`
--

CREATE TABLE `tareas_materias` (
  `id_tarea` int(11) NOT NULL,
  `id_asignacion_docente` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tareas_materias`
--

INSERT INTO `tareas_materias` (`id_tarea`, `id_asignacion_docente`, `titulo`, `descripcion`, `archivo`, `fecha_entrega`, `fecha_creacion`) VALUES
(4, 18, 'Tarea prueba ', 'Prueba', 'uploads/tareas/1762140089_actividad 1  AALF.pdf', '2025-11-20', '2025-11-03 03:21:29'),
(5, 18, 'Proyecto prueba 2', '', NULL, '2025-10-01', '2025-11-03 03:21:45'),
(6, 18, 'Prueba 3', 'prueba 3', NULL, '2025-11-02', '2025-11-03 03:22:37'),
(7, 15, 'Tarea calificación', 'calif', NULL, '2025-12-03', '2025-11-03 21:21:47'),
(8, 13, 'Tarea Regreso Prueba', 'Prueba', NULL, '2025-11-27', '2025-11-03 23:51:40'),
(9, 17, 'pruebaaaa', '', NULL, '2025-11-20', '2025-11-04 01:11:08'),
(10, 13, 'ejemplo 2 de devuelto', '', NULL, '2025-12-10', '2025-11-05 16:25:12'),
(16, 16, 'Proyecto prueba 2', '', NULL, '2025-11-07', '2025-11-07 00:51:52'),
(17, 16, 'adasd', '', NULL, '2025-11-10', '2025-11-07 00:52:08'),
(18, 15, 'ejemplo a tiempo', '', NULL, '2025-11-20', '2025-11-11 15:18:22'),
(19, 15, 'ejemplo fuera de tiempo ', '', NULL, '2025-11-10', '2025-11-11 15:19:11'),
(20, 15, 'ejemplo de entrega cerrada', '', NULL, '2025-10-01', '2025-11-11 15:20:29'),
(21, 16, 'Ejemplo de devolución', '', NULL, '2025-11-25', '2025-11-12 01:08:26'),
(22, 16, 'Prueba chacon', 'sss', NULL, '2025-11-20', '2025-11-14 02:19:17'),
(23, 19, 'Act 1', 'act 1', NULL, '2025-11-30', '2025-11-22 02:35:14'),
(24, 19, 'act 2', 'act 2', NULL, '2025-11-30', '2025-11-22 02:35:23'),
(25, 20, 'Act 1', 'act 1', NULL, '2025-11-30', '2025-11-22 04:09:51'),
(26, 20, 'act 2', 'act2', NULL, '2025-11-30', '2025-11-22 04:10:04');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `uq_admin_correo` (`correo`);

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id_alumno`),
  ADD UNIQUE KEY `uq_alumnos_curp` (`curp`),
  ADD UNIQUE KEY `uq_alumnos_matricula` (`matricula`),
  ADD KEY `idx_alumnos_id_nombre_semestre` (`id_nombre_semestre`),
  ADD KEY `idx_alumnos_estatus` (`estatus`),
  ADD KEY `idx_alumnos_deleted_at` (`deleted_at`);

--
-- Indices de la tabla `alumno_ciclo`
--
ALTER TABLE `alumno_ciclo`
  ADD PRIMARY KEY (`id_alumno_ciclo`),
  ADD UNIQUE KEY `uk_alumno_ciclo` (`id_alumno`,`id_ciclo`),
  ADD UNIQUE KEY `uq_alumno_ciclo` (`id_alumno`,`id_ciclo`),
  ADD KEY `id_ciclo` (`id_ciclo`),
  ADD KEY `id_grupo` (`id_grupo`);

--
-- Indices de la tabla `asignaciones_alumnos`
--
ALTER TABLE `asignaciones_alumnos`
  ADD PRIMARY KEY (`id_asignacion_alumno`),
  ADD KEY `idx_asign_alum_id_nombre_semestre` (`id_nombre_semestre`);

--
-- Indices de la tabla `asignaciones_docentes`
--
ALTER TABLE `asignaciones_docentes`
  ADD PRIMARY KEY (`id_asignacion_docente`),
  ADD KEY `idx_asig_doc_id_docente` (`id_docente`),
  ADD KEY `id_nombre_materia` (`id_nombre_materia`),
  ADD KEY `id_nombre_profesor_materia_grupo` (`id_nombre_profesor_materia_grupo`);

--
-- Indices de la tabla `asignaciones_grupo_alumno`
--
ALTER TABLE `asignaciones_grupo_alumno`
  ADD PRIMARY KEY (`id_asignacion_grupo_alumno`),
  ADD UNIQUE KEY `uq_aga_grupo_alumno` (`id_grupo`,`id_alumno`),
  ADD KEY `idx_aga_id_grupo` (`id_grupo`),
  ADD KEY `idx_aga_id_alumno` (`id_alumno`);

--
-- Indices de la tabla `asignar_materias`
--
ALTER TABLE `asignar_materias`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `idx_asignar_id_materia` (`id_materia`),
  ADD KEY `id_nombre_grupo_int` (`id_nombre_grupo_int`,`id_nombre_materia`),
  ADD KEY `id_nombre_materia` (`id_nombre_materia`);

--
-- Indices de la tabla `asistencias_alumnos`
--
ALTER TABLE `asistencias_alumnos`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD UNIQUE KEY `ux_asistencia` (`id_alumno`,`id_asignacion_docente`,`fecha`),
  ADD KEY `fk_asist_asig` (`id_asignacion_docente`),
  ADD KEY `fk_asist_grupo` (`id_grupo`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id_aula`),
  ADD UNIQUE KEY `ux_aulas_nombre` (`nombre`);

--
-- Indices de la tabla `calificaciones_alumnos`
--
ALTER TABLE `calificaciones_alumnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_asignacion_docente` (`id_asignacion_docente`),
  ADD KEY `id_alumno` (`id_alumno`);

--
-- Indices de la tabla `calificaciones_asistencia`
--
ALTER TABLE `calificaciones_asistencia`
  ADD PRIMARY KEY (`id_cal_asistencia`),
  ADD UNIQUE KEY `ux_asig_alumno` (`id_asignacion_docente`,`id_alumno`),
  ADD KEY `fk_calasis_alum` (`id_alumno`);

--
-- Indices de la tabla `calif_config`
--
ALTER TABLE `calif_config`
  ADD PRIMARY KEY (`id_asignacion_docente`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id_carrera`);

--
-- Indices de la tabla `cat_nombres_grupo`
--
ALTER TABLE `cat_nombres_grupo`
  ADD PRIMARY KEY (`id_nombre_grupo`),
  ADD UNIQUE KEY `uq_cat_nombres_grupo` (`nombre`);

--
-- Indices de la tabla `cat_nombres_materias`
--
ALTER TABLE `cat_nombres_materias`
  ADD PRIMARY KEY (`id_nombre_materia`),
  ADD UNIQUE KEY `uq_cnm_nombre` (`nombre`),
  ADD UNIQUE KEY `uq_cat_nombres_materias_nombre` (`nombre`);

--
-- Indices de la tabla `cat_nombres_semestre`
--
ALTER TABLE `cat_nombres_semestre`
  ADD PRIMARY KEY (`id_nombre_semestre`),
  ADD UNIQUE KEY `u_nombre` (`nombre`),
  ADD UNIQUE KEY `uq_cat_nombres_nombre` (`nombre`);

--
-- Indices de la tabla `cat_nombre_profesor_materia_grupo`
--
ALTER TABLE `cat_nombre_profesor_materia_grupo`
  ADD PRIMARY KEY (`id_nombre_profesor_materia_grupo`);

--
-- Indices de la tabla `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id_chat`),
  ADD UNIQUE KEY `unique_chat` (`id_docente`,`id_alumno`);

--
-- Indices de la tabla `ciclos_escolares`
--
ALTER TABLE `ciclos_escolares`
  ADD PRIMARY KEY (`id_ciclo`),
  ADD UNIQUE KEY `uk_ciclos_clave` (`clave`);

--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`id_docente`),
  ADD UNIQUE KEY `uq_docentes_curp` (`curp`),
  ADD UNIQUE KEY `uq_docentes_rfc` (`rfc`),
  ADD UNIQUE KEY `uq_docentes_matricula` (`matricula`),
  ADD KEY `idx_docentes_estatus` (`estatus`),
  ADD KEY `idx_docentes_deleted_at` (`deleted_at`);

--
-- Indices de la tabla `entregas_alumnos`
--
ALTER TABLE `entregas_alumnos`
  ADD PRIMARY KEY (`id_entrega`),
  ADD KEY `id_tarea` (`id_tarea`),
  ADD KEY `id_alumno` (`id_alumno`);

--
-- Indices de la tabla `entregas_evaluaciones_alumnos`
--
ALTER TABLE `entregas_evaluaciones_alumnos`
  ADD PRIMARY KEY (`id_entrega`),
  ADD UNIQUE KEY `uniq_eval_alumno` (`id_evaluacion`,`id_alumno`),
  ADD KEY `idx_eval` (`id_evaluacion`),
  ADD KEY `idx_alumno` (`id_alumno`);

--
-- Indices de la tabla `evaluaciones_docente`
--
ALTER TABLE `evaluaciones_docente`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD KEY `idx_docente` (`id_docente`),
  ADD KEY `idx_asig` (`id_asignacion_docente`);

--
-- Indices de la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`id_examen`),
  ADD KEY `id_docente` (`id_docente`),
  ADD KEY `id_asignacion_docente` (`id_asignacion_docente`);

--
-- Indices de la tabla `examen_calificaciones`
--
ALTER TABLE `examen_calificaciones`
  ADD PRIMARY KEY (`id_examen_calificacion`),
  ADD UNIQUE KEY `ux_examen_alumno` (`id_examen`,`id_alumno`),
  ADD KEY `idx_examen` (`id_examen`),
  ADD KEY `idx_alumno` (`id_alumno`);

--
-- Indices de la tabla `examen_preguntas`
--
ALTER TABLE `examen_preguntas`
  ADD PRIMARY KEY (`id_pregunta`),
  ADD KEY `id_examen` (`id_examen`);

--
-- Indices de la tabla `examen_pregunta_opciones`
--
ALTER TABLE `examen_pregunta_opciones`
  ADD PRIMARY KEY (`id_opcion`),
  ADD KEY `id_pregunta` (`id_pregunta`);

--
-- Indices de la tabla `examen_respuestas`
--
ALTER TABLE `examen_respuestas`
  ADD PRIMARY KEY (`id_respuesta`),
  ADD KEY `id_examen` (`id_examen`),
  ADD KEY `id_alumno` (`id_alumno`),
  ADD KEY `id_pregunta` (`id_pregunta`),
  ADD KEY `id_opcion` (`id_opcion`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`),
  ADD UNIQUE KEY `uq_grupos_id_nombre_grupo` (`id_nombre_grupo`),
  ADD KEY `idx_grupos_id_nombre_semestre` (`id_nombre_semestre`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `idx_horarios_prof_mat_grupo` (`id_nombre_profesor_materia_grupo`),
  ADD KEY `idx_horarios_aula` (`id_aula`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `idx_admin` (`id_admin`);

--
-- Indices de la tabla `mensajesdocente`
--
ALTER TABLE `mensajesdocente`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_chat` (`id_chat`);

--
-- Indices de la tabla `mensajes_secretarias`
--
ALTER TABLE `mensajes_secretarias`
  ADD PRIMARY KEY (`id_ms`),
  ADD UNIQUE KEY `uq_mensaje_secretaria` (`id_mensaje`,`id_secretaria`),
  ADD KEY `fk_ms_secretaria` (`id_secretaria`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_matricula` (`matricula`);

--
-- Indices de la tabla `recursos_materias`
--
ALTER TABLE `recursos_materias`
  ADD PRIMARY KEY (`id_recurso`),
  ADD KEY `id_asignacion_docente` (`id_asignacion_docente`);

--
-- Indices de la tabla `secretarias`
--
ALTER TABLE `secretarias`
  ADD PRIMARY KEY (`id_secretaria`),
  ADD UNIQUE KEY `uq_secretarias_curp` (`curp`),
  ADD UNIQUE KEY `uq_secretarias_rfc` (`rfc`),
  ADD UNIQUE KEY `uq_secretarias_correo` (`correo_institucional`);

--
-- Indices de la tabla `semestres`
--
ALTER TABLE `semestres`
  ADD PRIMARY KEY (`id_semestre`),
  ADD UNIQUE KEY `uq_semestres_id_nombre_semestre` (`id_nombre_semestre`),
  ADD UNIQUE KEY `uq_semestres_carrera_semestre` (`id_carrera`,`semestre`),
  ADD KEY `idx_semestres_id_carrera` (`id_carrera`),
  ADD KEY `idx_semestres_id_nombre_semestre` (`id_nombre_semestre`);

--
-- Indices de la tabla `tareas_materias`
--
ALTER TABLE `tareas_materias`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `idx_tareas_materia_asignacion` (`id_asignacion_docente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT de la tabla `alumno_ciclo`
--
ALTER TABLE `alumno_ciclo`
  MODIFY `id_alumno_ciclo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de la tabla `asignaciones_alumnos`
--
ALTER TABLE `asignaciones_alumnos`
  MODIFY `id_asignacion_alumno` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones_docentes`
--
ALTER TABLE `asignaciones_docentes`
  MODIFY `id_asignacion_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `asignaciones_grupo_alumno`
--
ALTER TABLE `asignaciones_grupo_alumno`
  MODIFY `id_asignacion_grupo_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `asignar_materias`
--
ALTER TABLE `asignar_materias`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `asistencias_alumnos`
--
ALTER TABLE `asistencias_alumnos`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id_aula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `calificaciones_alumnos`
--
ALTER TABLE `calificaciones_alumnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calificaciones_asistencia`
--
ALTER TABLE `calificaciones_asistencia`
  MODIFY `id_cal_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id_carrera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cat_nombres_grupo`
--
ALTER TABLE `cat_nombres_grupo`
  MODIFY `id_nombre_grupo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `cat_nombres_materias`
--
ALTER TABLE `cat_nombres_materias`
  MODIFY `id_nombre_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `cat_nombres_semestre`
--
ALTER TABLE `cat_nombres_semestre`
  MODIFY `id_nombre_semestre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cat_nombre_profesor_materia_grupo`
--
ALTER TABLE `cat_nombre_profesor_materia_grupo`
  MODIFY `id_nombre_profesor_materia_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `chats`
--
ALTER TABLE `chats`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ciclos_escolares`
--
ALTER TABLE `ciclos_escolares`
  MODIFY `id_ciclo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `docentes`
--
ALTER TABLE `docentes`
  MODIFY `id_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `entregas_alumnos`
--
ALTER TABLE `entregas_alumnos`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `entregas_evaluaciones_alumnos`
--
ALTER TABLE `entregas_evaluaciones_alumnos`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_docente`
--
ALTER TABLE `evaluaciones_docente`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `examenes`
--
ALTER TABLE `examenes`
  MODIFY `id_examen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `examen_calificaciones`
--
ALTER TABLE `examen_calificaciones`
  MODIFY `id_examen_calificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `examen_preguntas`
--
ALTER TABLE `examen_preguntas`
  MODIFY `id_pregunta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `examen_pregunta_opciones`
--
ALTER TABLE `examen_pregunta_opciones`
  MODIFY `id_opcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `examen_respuestas`
--
ALTER TABLE `examen_respuestas`
  MODIFY `id_respuesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `mensajesdocente`
--
ALTER TABLE `mensajesdocente`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `mensajes_secretarias`
--
ALTER TABLE `mensajes_secretarias`
  MODIFY `id_ms` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `recursos_materias`
--
ALTER TABLE `recursos_materias`
  MODIFY `id_recurso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `secretarias`
--
ALTER TABLE `secretarias`
  MODIFY `id_secretaria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `semestres`
--
ALTER TABLE `semestres`
  MODIFY `id_semestre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tareas_materias`
--
ALTER TABLE `tareas_materias`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `fk_alumnos_semestres_id_nombre` FOREIGN KEY (`id_nombre_semestre`) REFERENCES `semestres` (`id_nombre_semestre`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `alumno_ciclo`
--
ALTER TABLE `alumno_ciclo`
  ADD CONSTRAINT `alumno_ciclo_ibfk_1` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `alumno_ciclo_ibfk_2` FOREIGN KEY (`id_ciclo`) REFERENCES `ciclos_escolares` (`id_ciclo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `alumno_ciclo_ibfk_3` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignaciones_alumnos`
--
ALTER TABLE `asignaciones_alumnos`
  ADD CONSTRAINT `fk_asign_alum_alumnos_id_nombre` FOREIGN KEY (`id_nombre_semestre`) REFERENCES `alumnos` (`id_nombre_semestre`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignaciones_docentes`
--
ALTER TABLE `asignaciones_docentes`
  ADD CONSTRAINT `asignaciones_docentes_ibfk_1` FOREIGN KEY (`id_nombre_materia`) REFERENCES `cat_nombres_materias` (`id_nombre_materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignaciones_docentes_ibfk_2` FOREIGN KEY (`id_nombre_profesor_materia_grupo`) REFERENCES `cat_nombre_profesor_materia_grupo` (`id_nombre_profesor_materia_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asig_doc_docentes` FOREIGN KEY (`id_docente`) REFERENCES `docentes` (`id_docente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignaciones_grupo_alumno`
--
ALTER TABLE `asignaciones_grupo_alumno`
  ADD CONSTRAINT `fk_aga_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aga_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignar_materias`
--
ALTER TABLE `asignar_materias`
  ADD CONSTRAINT `asignar_materias_ibfk_1` FOREIGN KEY (`id_nombre_materia`) REFERENCES `cat_nombres_materias` (`id_nombre_materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignar_materias_ibfk_2` FOREIGN KEY (`id_nombre_grupo_int`) REFERENCES `cat_nombres_grupo` (`id_nombre_grupo`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asignar_materias_materias` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencias_alumnos`
--
ALTER TABLE `asistencias_alumnos`
  ADD CONSTRAINT `fk_asist_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asist_asig` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asist_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones_alumnos`
--
ALTER TABLE `calificaciones_alumnos`
  ADD CONSTRAINT `calificaciones_alumnos_ibfk_1` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_alumnos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones_asistencia`
--
ALTER TABLE `calificaciones_asistencia`
  ADD CONSTRAINT `fk_calasis_alum` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_calasis_asig` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entregas_alumnos`
--
ALTER TABLE `entregas_alumnos`
  ADD CONSTRAINT `entregas_alumnos_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `tareas_materias` (`id_tarea`) ON DELETE CASCADE,
  ADD CONSTRAINT `entregas_alumnos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entregas_evaluaciones_alumnos`
--
ALTER TABLE `entregas_evaluaciones_alumnos`
  ADD CONSTRAINT `fk_eval_alumno_eval` FOREIGN KEY (`id_evaluacion`) REFERENCES `evaluaciones_docente` (`id_evaluacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evaluaciones_docente`
--
ALTER TABLE `evaluaciones_docente`
  ADD CONSTRAINT `fk_eval_asig` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_eval_docente` FOREIGN KEY (`id_docente`) REFERENCES `docentes` (`id_docente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD CONSTRAINT `examenes_ibfk_1` FOREIGN KEY (`id_docente`) REFERENCES `docentes` (`id_docente`),
  ADD CONSTRAINT `examenes_ibfk_2` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`);

--
-- Filtros para la tabla `examen_calificaciones`
--
ALTER TABLE `examen_calificaciones`
  ADD CONSTRAINT `fk_ec_alumno` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_examen` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id_examen`) ON DELETE CASCADE;

--
-- Filtros para la tabla `examen_preguntas`
--
ALTER TABLE `examen_preguntas`
  ADD CONSTRAINT `examen_preguntas_ibfk_1` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id_examen`);

--
-- Filtros para la tabla `examen_pregunta_opciones`
--
ALTER TABLE `examen_pregunta_opciones`
  ADD CONSTRAINT `examen_pregunta_opciones_ibfk_1` FOREIGN KEY (`id_pregunta`) REFERENCES `examen_preguntas` (`id_pregunta`);

--
-- Filtros para la tabla `examen_respuestas`
--
ALTER TABLE `examen_respuestas`
  ADD CONSTRAINT `examen_respuestas_ibfk_1` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`id_examen`),
  ADD CONSTRAINT `examen_respuestas_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id_alumno`),
  ADD CONSTRAINT `examen_respuestas_ibfk_3` FOREIGN KEY (`id_pregunta`) REFERENCES `examen_preguntas` (`id_pregunta`),
  ADD CONSTRAINT `examen_respuestas_ibfk_4` FOREIGN KEY (`id_opcion`) REFERENCES `examen_pregunta_opciones` (`id_opcion`);

--
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `fk_grupos_cat_nombres_sem` FOREIGN KEY (`id_nombre_semestre`) REFERENCES `cat_nombres_semestre` (`id_nombre_semestre`) ON UPDATE CASCADE,
  ADD CONSTRAINT `grupos_ibfk_1` FOREIGN KEY (`id_nombre_grupo`) REFERENCES `cat_nombres_grupo` (`id_nombre_grupo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `fk_mensajes_admin` FOREIGN KEY (`id_admin`) REFERENCES `administradores` (`id_admin`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajesdocente`
--
ALTER TABLE `mensajesdocente`
  ADD CONSTRAINT `mensajesdocente_ibfk_1` FOREIGN KEY (`id_chat`) REFERENCES `chats` (`id_chat`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes_secretarias`
--
ALTER TABLE `mensajes_secretarias`
  ADD CONSTRAINT `fk_ms_mensaje` FOREIGN KEY (`id_mensaje`) REFERENCES `mensajes` (`id_mensaje`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ms_secretaria` FOREIGN KEY (`id_secretaria`) REFERENCES `secretarias` (`id_secretaria`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_matricula` FOREIGN KEY (`matricula`) REFERENCES `alumnos` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recursos_materias`
--
ALTER TABLE `recursos_materias`
  ADD CONSTRAINT `recursos_materias_ibfk_1` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `semestres`
--
ALTER TABLE `semestres`
  ADD CONSTRAINT `fk_semestres__id_nombre_semestre` FOREIGN KEY (`id_nombre_semestre`) REFERENCES `cat_nombres_semestre` (`id_nombre_semestre`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_semestres_carreras` FOREIGN KEY (`id_carrera`) REFERENCES `carreras` (`id_carrera`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tareas_materias`
--
ALTER TABLE `tareas_materias`
  ADD CONSTRAINT `fk_tareas_asignacion_docente` FOREIGN KEY (`id_asignacion_docente`) REFERENCES `asignaciones_docentes` (`id_asignacion_docente`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
