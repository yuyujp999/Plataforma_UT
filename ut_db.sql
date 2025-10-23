-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-10-2025 a las 18:15:52
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
(1, 'Maríaa', 'García', 'Lozano', 'admin@example.edu', '$2y$10$4hTBw1Uhqc1V3ahfDoTGxeTByKTcY3w/JzlYqpb02rqp9x0qn3Q2O', '2025-10-17 19:16:26'),
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
  `id_nombre_semestre` int(11) DEFAULT NULL,
  `contacto_emergencia` varchar(200) NOT NULL,
  `parentesco_emergencia` varchar(50) DEFAULT NULL,
  `telefono_emergencia` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id_alumno`, `nombre`, `apellido_paterno`, `apellido_materno`, `curp`, `fecha_nacimiento`, `sexo`, `telefono`, `direccion`, `correo_personal`, `matricula`, `password`, `id_nombre_semestre`, `contacto_emergencia`, `parentesco_emergencia`, `telefono_emergencia`, `fecha_registro`) VALUES
(1, 'María Fernanda', 'López', 'García', 'LOGM010101MDFRRA09', '2001-01-01', 'Femenino', 5512345678, 'Av. Central #45, CDMX', 'maria.lopez@example.com', 'A001', '12345hash1', 1, 'Rosa García', 'Madre', 5519876543, '2025-10-18 22:35:35'),
(2, 'José Antonio', 'Martínez', 'Pérez', 'MAPJ000202HDFRRS03', '2000-02-02', 'Masculino', 5523456789, 'Calle Hidalgo #12, Puebla', 'jose.martinez@example.com', 'A002', '12345hash2', 2, 'Luis Martínez', 'Padre', 5523456780, '2025-10-18 22:35:35'),
(3, 'Ana Sofía', 'Ramírez', 'Torres', 'RATA000303MDFRRL06', '2000-03-03', 'Femenino', 5534567890, 'Av. Juárez #99, Querétaro', 'ana.ramirez@example.com', 'A003', '12345hash3', 3, 'María Torres', 'Madre', 5545678901, '2025-10-18 22:35:35'),
(4, 'Carlos Alberto', 'Gómez', 'Luna', 'GOLC000404HDFRLL05', '2000-04-04', 'Masculino', 5545678902, 'Col. Centro #23, Monterrey', 'carlos.gomez@example.com', 'A004', '12345hash4', 4, 'Laura Luna', 'Hermana', 5556789012, '2025-10-18 22:35:35'),
(5, 'Daniela', 'Hernández', 'Ruiz', 'HERD000505MDFRRN07', '2000-05-05', 'Femenino', 5556789013, 'Av. Reforma #10, Guadalajara', 'daniela.hernandez@example.com', 'A005', '12345hash5', 2, 'Pedro Ruiz', 'Padre', 5567890123, '2025-10-18 22:35:35'),
(6, 'Luis Ángel', 'Mendoza', 'Castro', 'MECL000606HDFRRS08', '2000-06-06', 'Masculino', 5567890124, 'Col. Roma #56, CDMX', 'luis.mendoza@example.com', 'A006', '12345hash6', 1, 'Carmen Castro', 'Madre', 5578901234, '2025-10-18 22:35:35'),
(7, 'Andrea', 'Santos', 'Vega', 'SAVA000707MDFRGA09', '2000-07-07', 'Femenino', 5578901235, 'Calle Sur #78, Veracruz', 'andrea.santos@example.com', 'A007', '12345hash7', 3, 'Héctor Vega', 'Padre', 5589012345, '2025-10-18 22:35:35'),
(8, 'Miguel', 'Flores', 'Morales', 'FLOM000808HDFRRL01', '2000-08-08', 'Masculino', 5589012346, 'Av. Independencia #34, Oaxaca', 'miguel.flores@example.com', 'A008', '12345hash8', 4, 'Rosa Morales', 'Madre', 5590123456, '2025-10-18 22:35:35'),
(9, 'Valeria', 'Cruz', 'Núñez', 'CRNV000909MDFRRV02', '2000-09-09', 'Femenino', 5590123457, 'Calle Norte #22, Toluca', 'valeria.cruz@example.com', 'A009', '12345hash9', 1, 'Elena Núñez', 'Madre', 5501234567, '2025-10-18 22:35:35'),
(10, 'Jorge Luis', 'Reyes', 'Ortiz', 'REOJ001010HDFRRJ03', '2000-10-10', 'Masculino', 5501234568, 'Av. Morelos #77, Mérida', 'jorge.reyes@example.com', 'A010', '12345hash10', 2, 'Fernando Ortiz', 'Padre', 5512345670, '2025-10-18 22:35:35'),
(71, 'Isabella', 'Gómez', 'Ramírez', 'GROI011121MDFRRL21', '2001-11-21', 'Femenino', 5512340021, 'Av. Central #21, León', 'isabella21@example.com', 'A021', '12345hash21', 3, 'Rosa Ramírez', 'Madre', 5511110021, '2025-10-19 00:46:00'),
(72, 'Santiago', 'Martínez', 'Pérez', 'MAPS011222HDFRRL22', '2001-12-22', 'Masculino', 5512340022, 'Calle Hidalgo #22, Puebla', 'santiago22@example.com', 'A022', '12345hash22', 3, 'Luis Martínez', 'Padre', 5511110022, '2025-10-19 00:46:00'),
(73, 'Lucía', 'Ramírez', 'Torres', 'RATL020123MDFRRL23', '2002-01-23', 'Femenino', 5512340023, 'Av. Juárez #23, Querétaro', 'lucia23@example.com', 'A023', '12345hash23', 3, 'María Torres', 'Madre', 5511110023, '2025-10-19 00:46:00'),
(74, 'Emilio', 'Gómez', 'Luna', 'GOLE020224HDFRRL24', '2002-02-24', 'Masculino', 5512340024, 'Col. Centro #24, Monterrey', 'emilio24@example.com', 'A024', '12345hash24', 3, 'Laura Luna', 'Hermana', 5511110024, '2025-10-19 00:46:00'),
(75, 'Renata', 'Hernández', 'Ruiz', 'HERR020325MDFRRL25', '2002-03-25', 'Femenino', 5512340025, 'Av. Reforma #25, Guadalajara', 'renata25@example.com', 'A025', '12345hash25', 3, 'Pedro Ruiz', 'Padre', 5511110025, '2025-10-19 00:46:00'),
(76, 'Mateo', 'Mendoza', 'Castro', 'MEMM020426HDFRRL26', '2002-04-26', 'Masculino', 5512340026, 'Col. Roma #26, CDMX', 'mateo26@example.com', 'A026', '12345hash26', 3, 'Carmen Castro', 'Madre', 5511110026, '2025-10-19 00:46:00'),
(77, 'Valentina', 'Santos', 'Vega', 'SAVV020527MDFRRL27', '2002-05-27', 'Femenino', 5512340027, 'Calle Sur #27, Veracruz', 'valentina27@example.com', 'A027', '12345hash27', 3, 'Héctor Vega', 'Padre', 5511110027, '2025-10-19 00:46:00'),
(78, 'Sebastián', 'Flores', 'Morales', 'FLOS020628HDFRRL28', '2002-06-28', 'Masculino', 5512340028, 'Av. Independencia #28, Oaxaca', 'sebastian28@example.com', 'A028', '12345hash28', 3, 'Rosa Morales', 'Madre', 5511110028, '2025-10-19 00:46:00'),
(79, 'Natalia', 'Cruz', 'Núñez', 'CRNN020729MDFRRL29', '2002-07-29', 'Femenino', 5512340029, 'Calle Norte #29, Toluca', 'natalia29@example.com', 'A029', '12345hash29', 3, 'Elena Núñez', 'Madre', 5511110029, '2025-10-19 00:46:00'),
(80, 'David', 'Reyes', 'Ortiz', 'REOD020830HDFRRL30', '2002-08-30', 'Masculino', 5512340030, 'Av. Morelos #30, Mérida', 'david30@example.com', 'A030', '12345hash30', 3, 'Fernando Ortiz', 'Padre', 5511110030, '2025-10-19 00:46:00'),
(81, 'Ximena', 'Gómez', 'Ramírez', 'GROX020931MDFRRL31', '2002-09-30', 'Femenino', 5512340031, 'Av. Central #31, León', 'ximena31@example.com', 'A031', '12345hash31', 4, 'Rosa Ramírez', 'Madre', 5511110031, '2025-10-19 00:46:00'),
(82, 'Rodrigo', 'Martínez', 'Pérez', 'MAPR021032HDFRRL32', '2002-10-01', 'Masculino', 5512340032, 'Calle Hidalgo #32, Puebla', 'rodrigo32@example.com', 'A032', '12345hash32', 4, 'Luis Martínez', 'Padre', 5511110032, '2025-10-19 00:46:00'),
(83, 'Elena', 'Ramírez', 'Torres', 'RATE021133MDFRRL33', '2002-11-02', 'Femenino', 5512340033, 'Av. Juárez #33, Querétaro', 'elena33@example.com', 'A033', '12345hash33', 4, 'María Torres', 'Madre', 5511110033, '2025-10-19 00:46:00'),
(84, 'Tomás', 'Gómez', 'Luna', 'GOLT021234HDFRRL34', '2002-12-03', 'Masculino', 5512340034, 'Col. Centro #34, Monterrey', 'tomas34@example.com', 'A034', '12345hash34', 4, 'Laura Luna', 'Hermana', 5511110034, '2025-10-19 00:46:00'),
(85, 'Samantha', 'Hernández', 'Ruiz', 'HERS030135MDFRRL35', '2003-01-04', 'Femenino', 5512340035, 'Av. Reforma #35, Guadalajara', 'samantha35@example.com', 'A035', '12345hash35', 4, 'Pedro Ruiz', 'Padre', 5511110035, '2025-10-19 00:46:00'),
(86, 'Andrés', 'Mendoza', 'Castro', 'MECA030236HDFRRL36', '2003-02-05', 'Masculino', 5512340036, 'Col. Roma #36, CDMX', 'andres36@example.com', 'A036', '12345hash36', 4, 'Carmen Castro', 'Madre', 5511110036, '2025-10-19 00:46:00'),
(87, 'Jimena', 'Santos', 'Vega', 'SAVJ030337MDFRRL37', '2003-03-06', 'Femenino', 5512340037, 'Calle Sur #37, Veracruz', 'jimena37@example.com', 'A037', '12345hash37', 4, 'Héctor Vega', 'Padre', 5511110037, '2025-10-19 00:46:00'),
(88, 'Leonardo', 'Flores', 'Morales', 'FLOL030438HDFRRL38', '2003-04-07', 'Masculino', 5512340038, 'Av. Independencia #38, Oaxaca', 'leonardo38@example.com', 'A038', '12345hash38', 4, 'Rosa Morales', 'Madre', 5511110038, '2025-10-19 00:46:00'),
(89, 'Paula', 'Cruz', 'Núñez', 'CRNP030539MDFRRL39', '2003-05-08', 'Femenino', 5512340039, 'Calle Norte #39, Toluca', 'paula39@example.com', 'A039', '12345hash39', 4, 'Elena Núñez', 'Madre', 5511110039, '2025-10-19 00:46:00'),
(90, 'Felipe', 'Reyes', 'Ortiz', 'REOF030640HDFRRL40', '2003-06-09', 'Masculino', 5512340040, 'Av. Morelos #40, Mérida', 'felipe40@example.com', 'A040', '12345hash40', 4, 'Fernando Ortiz', 'Padre', 5511110040, '2025-10-19 00:46:00'),
(91, 'Victoria', 'Gómez', 'Ramírez', 'GROV030741MDFRRL41', '2003-07-10', 'Femenino', 5512340041, 'Av. Central #41, León', 'victoria41@example.com', 'A041', '12345hash41', 5, 'Rosa Ramírez', 'Madre', 5511110041, '2025-10-19 00:46:00'),
(92, 'Martín', 'Martínez', 'Pérez', 'MAPM030842HDFRRL42', '2003-08-11', 'Masculino', 5512340042, 'Calle Hidalgo #42, Puebla', 'martin42@example.com', 'A042', '12345hash42', 5, 'Luis Martínez', 'Padre', 5511110042, '2025-10-19 00:46:00'),
(93, 'Abigail', 'Ramírez', 'Torres', 'RATA030943MDFRRL43', '2003-09-12', 'Femenino', 5512340043, 'Av. Juárez #43, Querétaro', 'abigail43@example.com', 'A043', '12345hash43', 5, 'María Torres', 'Madre', 5511110043, '2025-10-19 00:46:00'),
(94, 'Emiliano', 'Gómez', 'Luna', 'GOLE031044HDFRRL44', '2003-10-13', 'Masculino', 5512340044, 'Col. Centro #44, Monterrey', 'emiliano44@example.com', 'A044', '12345hash44', 5, 'Laura Luna', 'Hermana', 5511110044, '2025-10-19 00:46:00'),
(95, 'Julieta', 'Hernández', 'Ruiz', 'HERJ031145MDFRRL45', '2003-11-14', 'Femenino', 5512340045, 'Av. Reforma #45, Guadalajara', 'julieta45@example.com', 'A045', '12345hash45', 5, 'Pedro Ruiz', 'Padre', 5511110045, '2025-10-19 00:46:00'),
(96, 'Gabriel', 'Mendoza', 'Castro', 'MECA031246HDFRRL46', '2003-12-15', 'Masculino', 5512340046, 'Col. Roma #46, CDMX', 'gabriel46@example.com', 'A046', '12345hash46', 5, 'Carmen Castro', 'Madre', 5511110046, '2025-10-19 00:46:00'),
(97, 'Regina', 'Santos', 'Vega', 'SAVR040147MDFRRL47', '2004-01-16', 'Femenino', 5512340047, 'Calle Sur #47, Veracruz', 'regina47@example.com', 'A047', '12345hash47', 5, 'Héctor Vega', 'Padre', 5511110047, '2025-10-19 00:46:00'),
(98, 'Iván', 'Flores', 'Morales', 'FLOI040248HDFRRL48', '2004-02-17', 'Masculino', 5512340048, 'Av. Independencia #48, Oaxaca', 'ivan48@example.com', 'A048', '12345hash48', 5, 'Rosa Morales', 'Madre', 5511110048, '2025-10-19 00:46:00'),
(99, 'Carolina', 'Cruz', 'Núñez', 'CRNC040349MDFRRL49', '2004-03-18', 'Femenino', 5512340049, 'Calle Norte #49, Toluca', 'carolina49@example.com', 'A049', '12345hash49', 5, 'Elena Núñez', 'Madre', 5511110049, '2025-10-19 00:46:00'),
(100, 'Tomás', 'Reyes', 'Ortiz', 'REOT040450HDFRRL50', '2004-04-19', 'Masculino', 5512340050, 'Av. Morelos #50, Mérida', 'tomas50@example.com', 'A050', '12345hash50', 5, 'Fernando Ortiz', 'Padre', 5511110050, '2025-10-19 00:46:00'),
(101, 'Luciano', 'Vargas', 'Serrano', 'VASL040551HDFRRL51', '2004-05-20', 'Masculino', 5512340051, 'Av. Hidalgo #51, Tijuana', 'luciano51@example.com', 'A051', '12345hash51', 5, 'Ana Serrano', 'Madre', 5511110051, '2025-10-19 00:46:00'),
(102, 'Camila', 'Ortega', 'Romero', 'ORRC040652MDFRRL52', '2004-06-21', 'Femenino', 5512340052, 'Calle Morelos #52, Mérida', 'camila52@example.com', 'A052', '12345hash52', 5, 'Mario Ortega', 'Padre', 5511110052, '2025-10-19 00:46:00'),
(103, 'Marco', 'Jiménez', 'Suárez', 'JISM040753HDFRRL53', '2004-07-22', 'Masculino', 5512340053, 'Col. Centro #53, León', 'marco53@example.com', 'A053', '12345hash53', 5, 'Beatriz Suárez', 'Madre', 5511110053, '2025-10-19 00:46:00'),
(104, 'Natalia', 'Navarro', 'Cortés', 'NACN040854MDFRRL54', '2004-08-23', 'Femenino', 5512340054, 'Av. Juárez #54, Puebla', 'natalia54@example.com', 'A054', '12345hash54', 5, 'Carmen Cortés', 'Madre', 5511110054, '2025-10-19 00:46:00'),
(105, 'Ángel', 'Salazar', 'Díaz', 'SADA040955HDFRRL55', '2004-09-24', 'Masculino', 5512340055, 'Av. Hidalgo #55, CDMX', 'angel55@example.com', 'A055', '12345hash55', 5, 'Lucía Díaz', 'Madre', 5511110055, '2025-10-19 00:46:00'),
(106, 'Brenda', 'Moreno', 'Flores', 'MOFB041056MDFRRL56', '2004-10-25', 'Femenino', 5512340056, 'Av. Central #56, Guadalajara', 'brenda56@example.com', 'A056', '12345hash56', 5, 'Jorge Moreno', 'Padre', 5511110056, '2025-10-19 00:46:00'),
(107, 'Erick', 'Vega', 'Santos', 'VESR041157HDFRRL57', '2004-11-26', 'Masculino', 5512340057, 'Calle Sur #57, Veracruz', 'erick57@example.com', 'A057', '12345hash57', 5, 'Diana Santos', 'Madre', 5511110057, '2025-10-19 00:46:00'),
(108, 'Melissa', 'Cortés', 'Ríos', 'CORR041258MDFRRL58', '2004-12-27', 'Femenino', 5512340058, 'Av. Reforma #58, Monterrey', 'melissa58@example.com', 'A058', '12345hash58', 5, 'Alberto Ríos', 'Padre', 5511110058, '2025-10-19 00:46:00'),
(109, 'Samuel', 'Domínguez', 'Lara', 'DOLS050159HDFRRL59', '2005-01-28', 'Masculino', 5512340059, 'Calle Norte #59, Toluca', 'samuel59@example.com', 'A059', '12345hash59', 5, 'Carmen Lara', 'Madre', 5511110059, '2025-10-19 00:46:00'),
(110, 'María', 'Lara', 'Torres', 'LATM050260MDFRRL60', '2005-02-01', 'Femenino', 5512340060, 'Av. Morelos #60, Mérida', 'maria60@example.com', 'A060', '12345hash60', 5, 'José Torres', 'Padre', 5511110060, '2025-10-19 00:46:00'),
(111, 'María Fernanda', 'López', 'García', 'LOGM010101MDFRRA01', '2001-01-01', 'Femenino', 5512340001, 'Av. Central #1, CDMX', 'maria1@example.com', 'B001', '12345hash1', 1, 'Rosa García', 'Madre', 5511110001, '2025-10-19 00:51:07'),
(112, 'José Antonio', 'Martínez', 'Pérez', 'MAPJ000202HDFRRS02', '2000-02-02', 'Masculino', 5512340002, 'Calle Hidalgo #2, Puebla', 'jose2@example.com', 'B002', '12345hash2', 1, 'Luis Martínez', 'Padre', 5511110002, '2025-10-19 00:51:07'),
(113, 'Ana Sofía', 'Ramírez', 'Torres', 'RATA000303MDFRRL03', '2000-03-03', 'Femenino', 5512340003, 'Av. Juárez #3, Querétaro', 'ana3@example.com', 'B003', '12345hash3', 1, 'María Torres', 'Madre', 5511110003, '2025-10-19 00:51:07'),
(114, 'Carlos Alberto', 'Gómez', 'Luna', 'GOLC000404HDFRLL04', '2000-04-04', 'Masculino', 5512340004, 'Col. Centro #4, Monterrey', 'carlos4@example.com', 'B004', '12345hash4', 1, 'Laura Luna', 'Hermana', 5511110004, '2025-10-19 00:51:07'),
(115, 'Daniela', 'Hernández', 'Ruiz', 'HERD000505MDFRRN05', '2000-05-05', 'Femenino', 5512340005, 'Av. Reforma #5, Guadalajara', 'daniela5@example.com', 'B005', '12345hash5', 1, 'Pedro Ruiz', 'Padre', 5511110005, '2025-10-19 00:51:07'),
(116, 'Luis Ángel', 'Mendoza', 'Castro', 'MECL000606HDFRRS06', '2000-06-06', 'Masculino', 5512340006, 'Col. Roma #6, CDMX', 'luis6@example.com', 'B006', '12345hash6', 1, 'Carmen Castro', 'Madre', 5511110006, '2025-10-19 00:51:07'),
(117, 'Andrea', 'Santos', 'Vega', 'SAVA000707MDFRGA07', '2000-07-07', 'Femenino', 5512340007, 'Calle Sur #7, Veracruz', 'andrea7@example.com', 'B007', '12345hash7', 1, 'Héctor Vega', 'Padre', 5511110007, '2025-10-19 00:51:07'),
(118, 'Miguel', 'Flores', 'Morales', 'FLOM000808HDFRRL08', '2000-08-08', 'Masculino', 5512340008, 'Av. Independencia #8, Oaxaca', 'miguel8@example.com', 'B008', '12345hash8', 1, 'Rosa Morales', 'Madre', 5511110008, '2025-10-19 00:51:07'),
(119, 'Valeria', 'Cruz', 'Núñez', 'CRNV000909MDFRRV09', '2000-09-09', 'Femenino', 5512340009, 'Calle Norte #9, Toluca', 'valeria9@example.com', 'B009', '12345hash9', 1, 'Elena Núñez', 'Madre', 5511110009, '2025-10-19 00:51:07'),
(120, 'Jorge Luis', 'Reyes', 'Ortiz', 'REOJ001010HDFRRJ10', '2000-10-10', 'Masculino', 5512340010, 'Av. Morelos #10, Mérida', 'jorge10@example.com', 'B010', '12345hash10', 1, 'Fernando Ortiz', 'Padre', 5511110010, '2025-10-19 00:51:07'),
(121, 'Camila', 'González', 'Pérez', 'GOPC010111MDFRRL11', '2001-01-11', 'Femenino', 5512340011, 'Av. Central #11, León', 'camila11@example.com', 'C011', '12345hash11', 2, 'Laura Pérez', 'Madre', 5511110011, '2025-10-19 00:51:07'),
(122, 'Diego', 'Ramírez', 'Luna', 'RALD010212HDFRRS12', '2001-02-12', 'Masculino', 5512340012, 'Calle Hidalgo #12, Puebla', 'diego12@example.com', 'C012', '12345hash12', 2, 'Luis Ramírez', 'Padre', 5511110012, '2025-10-19 00:51:07'),
(123, 'Fernanda', 'Torres', 'Santos', 'TOCF010313MDFRRL13', '2001-03-13', 'Femenino', 5512340013, 'Av. Juárez #13, Querétaro', 'fernanda13@example.com', 'C013', '12345hash13', 2, 'María Santos', 'Madre', 5511110013, '2025-10-19 00:51:07'),
(124, 'Pablo', 'Hernández', 'Ríos', 'HEPR010414HDFRRL14', '2001-04-14', 'Masculino', 5512340014, 'Col. Centro #14, Monterrey', 'pablo14@example.com', 'C014', '12345hash14', 2, 'Carmen Ríos', 'Madre', 5511110014, '2025-10-19 00:51:07'),
(125, 'Diana', 'Mendoza', 'Lopez', 'MELD010515MDFRRL15', '2001-05-15', 'Femenino', 5512340015, 'Av. Reforma #15, Guadalajara', 'diana15@example.com', 'C015', '12345hash15', 2, 'Pedro Mendoza', 'Padre', 5511110015, '2025-10-19 00:51:07'),
(126, 'Ricardo', 'López', 'Ruiz', 'LORR010616HDFRRL16', '2001-06-16', 'Masculino', 5512340016, 'Col. Roma #16, CDMX', 'ricardo16@example.com', 'C016', '12345hash16', 2, 'Laura Ruiz', 'Madre', 5511110016, '2025-10-19 00:51:07'),
(127, 'Sofía', 'Santos', 'Vega', 'SASV010717MDFRRL17', '2001-07-17', 'Femenino', 5512340017, 'Calle Sur #17, Veracruz', 'sofia17@example.com', 'C017', '12345hash17', 2, 'Héctor Vega', 'Padre', 5511110017, '2025-10-19 00:51:07'),
(128, 'Manuel', 'Flores', 'Morales', 'FLMM010818HDFRRL18', '2001-08-18', 'Masculino', 5512340018, 'Av. Independencia #18, Oaxaca', 'manuel18@example.com', 'C018', '12345hash18', 2, 'Rosa Morales', 'Madre', 5511110018, '2025-10-19 00:51:07'),
(129, 'Alejandra', 'Cruz', 'Núñez', 'CRNA010919MDFRRL19', '2001-09-19', 'Femenino', 5512340019, 'Calle Norte #19, Toluca', 'alejandra19@example.com', 'C019', '12345hash19', 2, 'Elena Núñez', 'Madre', 5511110019, '2025-10-19 00:51:07'),
(130, 'Eduardo', 'Reyes', 'Ortiz', 'REOE011020HDFRRL20', '2001-10-20', 'Masculino', 5512340020, 'Av. Morelos #20, Mérida', 'eduardo20@example.com', 'C020', '12345hash20', 2, 'Fernando Ortiz', 'Padre', 5511110020, '2025-10-19 00:51:07');

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
(1, 7, 2, 1),
(2, 3, 9, 3),
(3, 6, 4, 4),
(4, 7, 3, 5),
(5, 4, 3, 6),
(6, 11, 11, 7),
(8, 11, 12, 9),
(9, 11, 8, 10),
(10, 14, 8, 11),
(11, 11, 14, 12),
(12, 11, 7, 13);

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
(18, 6, 77, '2025-10-19 00:51:42');

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
(7, 10, 5, 7),
(8, 10, 6, 8),
(9, 4, 8, 11),
(10, 1, 8, 12),
(11, 1, 5, 13),
(12, 7, 6, 14);

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
(2, 'Ingeneria en Industrial', 'Carrera enfocada en optimizar procesos, recursos y sistemas dentro de empresas e industrias.', 3, '2025-10-18 00:51:04'),
(3, 'Ingeniería Mecatrónica', 'Integra mecánica, electrónica y programación para crear sistemas automatizados, robots y maquinaria.', 4, '2025-10-18 21:55:43'),
(4, 'Licenciatura en Lengua Inglesa', 'Forma profesionales con dominio del idioma inglés, capacitados para la enseñanza, traducción e interpretación.', 3, '2025-10-18 21:56:42'),
(5, 'Licenciatura en Mercadotecnia', 'Carrera enfocada en el análisis del mercado y el comportamiento del consumidor para crear estrategias efectivas de promoción.', 4, '2025-10-18 21:57:51'),
(6, 'Ingeniería en softwere', 'softwere', 3, '2025-10-21 03:37:16');

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
(5, 'IM1G1'),
(6, 'IM1G2'),
(8, 'IS1G1'),
(7, 'LLI1G1');

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
(7, 'COMU-IM1G1'),
(8, 'COMU-IM1G2'),
(3, 'CONT-II1G1'),
(4, 'CONT-II1G2'),
(5, 'DISE-II1G1'),
(6, 'DISE-II1G2'),
(13, 'MATE-IM1G1'),
(12, 'MATE-IS1G1'),
(11, 'PROG-IS1G1');

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
(1, 'Ingeniería en Sistemass 1'),
(6, 'Ingeniería en softwere 1'),
(3, 'Ingeniería Mecatrónica 1'),
(4, 'Licenciatura en Lengua Inglesa 1'),
(5, 'Licenciatura en Mercadotecnia 1');

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
(1, 'Profesor Alejandra Vega Flores - ADMI-II1G2'),
(3, 'Profesor Ana Patricia Rodríguez Santos - ADMI-IS1G2'),
(4, 'Profesor Luis Ángel Pérez Castillo - CONT-II1G2'),
(5, 'Profesor Alejandra Vega Flores - CONT-II1G1'),
(6, 'Profesor Ricardo Hernández Torres - CONT-II1G1'),
(7, 'Profesor Angel Antonio Loza Flores - PROG-IS1G1'),
(9, 'Profesor Angel Antonio Loza Flores - MATE-IS1G1'),
(10, 'Profesor Angel Antonio Loza Flores - COMU-IM1G2'),
(11, 'Profesor Brayan David Casas Morales - COMU-IM1G2'),
(12, 'Profesor Angel Antonio Loza Flores - ADMI-IM1G2'),
(13, 'Profesor Angel Antonio Loza Flores - COMU-IM1G1');

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

INSERT INTO `docentes` (`id_docente`, `nombre`, `apellido_paterno`, `apellido_materno`, `curp`, `rfc`, `fecha_nacimiento`, `sexo`, `telefono`, `direccion`, `correo_personal`, `matricula`, `password`, `nivel_estudios`, `area_especialidad`, `universidad_egreso`, `cedula_profesional`, `idiomas`, `puesto`, `tipo_contrato`, `fecha_ingreso`, `contacto_emergencia`, `parentesco_emergencia`, `telefono_emergencia`, `fecha_registro`) VALUES
(1, 'María Elena', 'Gómez', 'Ramírez', 'GORM800101MDFRRL01', 'GORM800101ABC', '1980-01-01', 'Femenino', '5512345678', 'Av. Insurgentes 123, CDMX', 'maria.gomez@ut.edu.mx', 'D001', 'hash12345a', 'Maestría', 'Educación', 'UNAM', '1234567', 'Español, Inglés', 'Profesora', 'Tiempo Completo', '2010-08-15', 'Rosa Ramírez', 'Madre', '5519876543', '2025-10-18 22:53:44'),
(2, 'Juan Carlos', 'Martínez', 'Luna', 'MALJ790202HDFRRL02', 'MALJ790202DEF', '1979-02-02', 'Masculino', '5523456789', 'Calle Hidalgo 45, Puebla', 'juan.martinez@ut.edu.mx', 'D002', 'hash12345b', 'Licenciatura', 'Electrónica', 'IPN', '7654321', 'Español, Inglés', 'Docente de Ingeniería', 'Asignatura', '2015-02-20', 'Laura Luna', 'Esposa', '5523456780', '2025-10-18 22:53:44'),
(3, 'Ana Patricia', 'Rodríguez', 'Santos', 'ROSA850303MDFRRL03', 'ROSA850303GHI', '1985-03-03', 'Femenino', '5534567890', 'Col. Centro 33, Querétaro', 'ana.rodriguez@ut.edu.mx', 'D003', 'hash12345c', 'Maestría', 'Administración', 'UANL', '8765432', 'Español, Inglés', 'Coordinadora Académica', 'Tiempo Completo', '2012-07-10', 'Pedro Santos', 'Padre', '5545678901', '2025-10-18 22:53:44'),
(4, 'Ricardo', 'Hernández', 'Torres', 'HETR820404HDFRRL04', 'HETR820404JKL', '1982-04-04', 'Masculino', '5545678902', 'Av. Reforma 220, Monterrey', 'ricardo.hernandez@ut.edu.mx', 'D004', 'hash12345d', 'Doctorado', 'Robótica', 'ITESM', '2345678', 'Español, Inglés', 'Profesor Investigador', 'Tiempo Completo', '2009-09-05', 'Laura Torres', 'Esposa', '5556789012', '2025-10-18 22:53:44'),
(5, 'Daniela', 'Sánchez', 'Morales', 'SAMD900505MDFRRL05', 'SAMD900505MNO', '1990-05-05', 'Femenino', '5556789013', 'Calle Juárez 120, Guadalajara', 'daniela.sanchez@ut.edu.mx', 'D005', 'hash12345e', 'Licenciatura', 'Idiomas', 'UdeG', '3456789', 'Español, Inglés, Francés', 'Docente de Inglés', 'Asignatura', '2018-01-12', 'Pedro Morales', 'Padre', '5567890123', '2025-10-18 22:53:44'),
(6, 'Luis Ángel', 'Pérez', 'Castillo', 'PECL870606HDFRRL06', 'PECL870606PQR', '1987-06-06', 'Masculino', '5567890124', 'Col. Roma Norte 56, CDMX', 'luis.perez@ut.edu.mx', 'D006', 'hash12345f', 'Maestría', 'Informática', 'UNAM', '4567890', 'Español, Inglés', 'Docente de Sistemas', 'Tiempo Completo', '2014-05-30', 'Carla Castillo', 'Esposa', '5578901234', '2025-10-18 22:53:44'),
(7, 'Alejandra', 'Vega', 'Flores', 'VEFA910707MDFRRL07', 'VEFA910707STU', '1991-07-07', 'Femenino', '5578901235', 'Calle Sur 78, Veracruz', 'alejandra.vega@ut.edu.mx', 'D007', 'hash12345g', 'Licenciatura', 'Mercadotecnia', 'UV', '5678901', 'Español, Inglés', 'Docente de Negocios', 'Medio Tiempo', '2020-09-01', 'Héctor Flores', 'Padre', '5589012345', '2025-10-18 22:53:44'),
(8, 'Miguel', 'Flores', 'Morales', 'FLOM880808HDFRRL08', 'FLOM880808VWX', '1988-08-08', 'Masculino', '5589012346', 'Av. Independencia 34, Oaxaca', 'miguel.flores@ut.edu.mx', 'D008', 'hash12345h', 'Maestría', 'Mecatrónica', 'UABJO', '6789012', 'Español, Inglés', 'Docente de Mecatrónica', 'Tiempo Completo', '2011-03-25', 'Rosa Morales', 'Madre', '5590123456', '2025-10-18 22:53:44'),
(9, 'Valeria', 'Cruz', 'Núñez', 'CRNV890909MDFRRL09', 'CRNV890909YZA', '1989-09-09', 'Femenino', '5590123457', 'Calle Norte 22, Toluca', 'valeria.cruz@ut.edu.mx', 'D009', 'hash12345i', 'Doctorado', 'Educación', 'UAEMEX', '7890123', 'Español, Inglés', 'Directora de Carrera', 'Tiempo Completo', '2008-06-15', 'Elena Núñez', 'Madre', '5501234567', '2025-10-18 22:53:44'),
(10, 'Jorge Luis', 'Reyes', 'Ortiz', 'REOJ900101HDFRRL10', 'REOJ900101BCD', '1990-01-01', 'Masculino', '5501234568', 'Av. Morelos 77, Mérida', 'jorge.reyes@ut.edu.mx', 'D010', 'hash12345j', 'Maestría', 'Energías Renovables', 'UADY', '8901234', 'Español, Inglés', 'Profesor de Energías', 'Asignatura', '2019-04-10', 'Fernando Ortiz', 'Padre', '5512345670', '2025-10-18 22:53:44'),
(11, 'Angel Antonio', 'Loza', 'Flores', 'LOFA050620HNLZLNA0', 'SCFWEFWCFCAFA', '2005-06-20', 'Masculino', '86136213718', 'KJABABDOJABCKUN UOFBIU', 'angelantonio3loza@gmail.com', 'DOC0001', '$2y$10$oFGBBnwpBeHk6G2eK42w3.6z3P/RApFkYIIGBSwWmji81PdTv9iHe', 'Licenciatura', '', 'ewewewewe', '', '', 'matematicas', 'Tiempo Completo', '2025-10-20', 'Aleskis', 'Amigo', '3123123213', '2025-10-21 03:36:41'),
(14, 'Brayan David', 'Casas', 'Morales', '2312dw3adaaw', '2DWQDQDWD', '2004-04-20', 'Masculino', '86136213718', 'KJABABDOJABCKUN UOFBIU', 'angelantonio33loza@gmail.com', 'DOC0002', '$2y$10$x.JLrbRz30E9jj2DWnlcduwBMo7q9O4dlAl5cV54e4hb/kHFov3la', 'Licenciatura', '', 'ewewewewe', '', 'adsadsada', 'matematicas', 'Tiempo Completo', '2025-10-21', 'hjfuyj', '32323', '21313132', '2025-10-21 20:17:25');

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
(9, 6, 8);

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
(7, 'Administración de Proyectos'),
(8, 'Contabilidad Financiera'),
(9, 'Diseño y Manufactura Asistida por Computadora'),
(10, 'Comunicación Oral y Escrita');

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

--
-- Volcado de datos para la tabla `recursos_materias`
--

INSERT INTO `recursos_materias` (`id_recurso`, `id_asignacion_docente`, `titulo`, `descripcion`, `archivo`, `fecha_creacion`) VALUES
(1, 9, 'Clase 1', 'actividad para clase', 'uploads/recursos/1761165567_Ecosabor_Snacks_Ventas_2025.xlsx', '2025-10-22 20:39:27');

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
(8, 'Yuleisy', 'Ocañas', 'Gonzalez', 'OAGY76207B8302', 'YOGY6232390', '2005-08-16', 'Femenino', '8261666033', 'Calle Encino #305 Los Nogales', 'yuleisy.ocañas@institucional.com', 'Twscihjg', 'Escolares', '2025-10-17', '7239782929', 'Hermano', '237293023238'),
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
(1, 1, 1, 1),
(2, 1, 2, 2),
(3, 1, 3, 3),
(4, 1, 4, 4),
(5, 1, 5, 5),
(6, 1, 6, 6);

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
(1, 9, 'Glosario', 'Hacer un Glosario', 'uploads/tareas/1761076300_Act2-ADT-XX.docx', '2025-10-22', '2025-10-21 19:51:40'),
(3, 9, 'caca', 'adasdad', NULL, NULL, '2025-10-23 06:26:21');

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
  ADD KEY `idx_alumnos_id_nombre_semestre` (`id_nombre_semestre`);

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
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`id_docente`),
  ADD UNIQUE KEY `uq_docentes_curp` (`curp`),
  ADD UNIQUE KEY `uq_docentes_rfc` (`rfc`),
  ADD UNIQUE KEY `uq_docentes_matricula` (`matricula`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`),
  ADD UNIQUE KEY `uq_grupos_id_nombre_grupo` (`id_nombre_grupo`),
  ADD KEY `idx_grupos_id_nombre_semestre` (`id_nombre_semestre`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`);

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
  MODIFY `id_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT de la tabla `asignaciones_alumnos`
--
ALTER TABLE `asignaciones_alumnos`
  MODIFY `id_asignacion_alumno` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones_docentes`
--
ALTER TABLE `asignaciones_docentes`
  MODIFY `id_asignacion_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `asignaciones_grupo_alumno`
--
ALTER TABLE `asignaciones_grupo_alumno`
  MODIFY `id_asignacion_grupo_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `asignar_materias`
--
ALTER TABLE `asignar_materias`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id_carrera` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cat_nombres_grupo`
--
ALTER TABLE `cat_nombres_grupo`
  MODIFY `id_nombre_grupo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cat_nombres_materias`
--
ALTER TABLE `cat_nombres_materias`
  MODIFY `id_nombre_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `cat_nombres_semestre`
--
ALTER TABLE `cat_nombres_semestre`
  MODIFY `id_nombre_semestre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cat_nombre_profesor_materia_grupo`
--
ALTER TABLE `cat_nombre_profesor_materia_grupo`
  MODIFY `id_nombre_profesor_materia_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `docentes`
--
ALTER TABLE `docentes`
  MODIFY `id_docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id_semestre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tareas_materias`
--
ALTER TABLE `tareas_materias`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `fk_alumnos_semestres_id_nombre` FOREIGN KEY (`id_nombre_semestre`) REFERENCES `semestres` (`id_nombre_semestre`) ON DELETE SET NULL ON UPDATE CASCADE;

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
-- Filtros para la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD CONSTRAINT `fk_grupos_cat_nombres_sem` FOREIGN KEY (`id_nombre_semestre`) REFERENCES `cat_nombres_semestre` (`id_nombre_semestre`) ON UPDATE CASCADE,
  ADD CONSTRAINT `grupos_ibfk_1` FOREIGN KEY (`id_nombre_grupo`) REFERENCES `cat_nombres_grupo` (`id_nombre_grupo`) ON DELETE CASCADE ON UPDATE CASCADE;

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
