-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 29-01-2026 a las 16:36:18
-- Versión del servidor: 5.7.44-log
-- Versión de PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dpimeduc_soporte`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_convocatorias`
--

CREATE TABLE `z_convocatorias` (
  `id` int(11) NOT NULL,
  `codigo_base` varchar(20) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Prefijo del código (10 caracteres). Ej: D20251134M',
  `nombre` varchar(200) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Nombre descriptivo de la convocatoria',
  `tipo_documento` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Tipo: Diploma, Certificado, Participación, etc.',
  `info_institucional` text COLLATE utf8_spanish_ci COMMENT 'Texto institucional que aparece en la validación',
  `etiqueta_persona` varchar(100) COLLATE utf8_spanish_ci DEFAULT 'Autor(es)' COMMENT 'Etiqueta para el campo de personas',
  `etiqueta_tema` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL COMMENT 'Etiqueta para el tema (opcional, puede ser NULL)',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=activo, 0=inactivo',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_convocatorias`
--

INSERT INTO `z_convocatorias` (`id`, `codigo_base`, `nombre`, `tipo_documento`, `info_institucional`, `etiqueta_persona`, `etiqueta_tema`, `activo`, `fecha_creacion`) VALUES
(3, 'C20261102M', 'Participacion encuentro metropolitano de medicos', 'Participación', 'Participante del Encuentro Metropolitano de Médicos en Periodo Asistencial Obligatorio en APS 2026, el día 23 de enero 2026', 'Participante', NULL, 1, '2026-01-22 16:44:27'),
(4, 'D20251134M', 'Congreso Metropolitano de Médicos Generales de Zona', 'Diploma', 'Autor(a) del trabajo de investigación presentado en el Congreso Metropolitano de Médicos Generales de Zona, patrocinado por el capítulo Metropolitano de Médicos Generales de Zona y Colegio Médico Regional Santiago, entre el 24 y 25 de Julio en Santiago de Chile.', 'Autor(es)', 'Trabajo - Artículo - Tema presentado', 1, '2026-01-22 16:45:18'),
(5, 'D20261001M', 'Congreso Metropolitano de Ejemplo 2026', 'Diploma', 'Texto institucional que aparece en la validacion del diploma. Puede ser extenso.', 'Autor(es)', 'Trabajo - Articulo - Tema presentado', 1, '2026-01-29 14:44:32'),
(6, 'C20261002N', 'Certificado de Participacion Nacional', 'Certificado', 'Participante del evento nacional celebrado en Santiago.', 'Participante', NULL, 1, '2026-01-29 14:44:32'),
(7, 'D20261003R', 'Diploma Regional de Investigacion', 'Diploma', 'Descripcion del evento regional.', 'Investigador(es)', 'Tema de investigacion', 1, '2026-01-29 14:44:32'),
(8, 'D20251003R', 'Diploma Regional de Investigacion 2025', 'Diploma', 'Descripcion del evento regional.', 'Autor(es)', NULL, 1, '2026-01-29 14:46:53'),
(9, 'D20251002R', 'Diploma Regional de Investigacion 2025', 'Diploma', '', 'Autor(es)', NULL, 1, '2026-01-29 15:13:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_convocatorias_temporal`
--

CREATE TABLE `z_convocatorias_temporal` (
  `id` int(11) NOT NULL,
  `codigo_base` varchar(20) COLLATE utf8_spanish_ci NOT NULL,
  `nombre` varchar(200) COLLATE utf8_spanish_ci NOT NULL,
  `tipo_documento` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `info_institucional` text COLLATE utf8_spanish_ci,
  `etiqueta_persona` varchar(100) COLLATE utf8_spanish_ci DEFAULT 'Autor(es)',
  `etiqueta_tema` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Identificador único de sesión de carga',
  `estado` enum('pendiente','valido','error') COLLATE utf8_spanish_ci DEFAULT 'pendiente',
  `mensaje_error` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `fecha_carga` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_diplomas`
--

CREATE TABLE `z_diplomas` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `autores` text COLLATE utf8_spanish_ci NOT NULL,
  `tema` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_diplomas`
--

INSERT INTO `z_diplomas` (`id`, `codigo`, `autores`, `tema`) VALUES
(12, 'D20251134M999', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION'),
(13, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES'),
(14, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS'),
(15, 'C20261102M001', 'Adriana Alessandra Acerbi Godoy', ''),
(16, 'C20261102M002', 'Angela Maria Acosta Palacio', ''),
(17, 'C20261102M003', 'Jefferson Javier Alcivar Castillo', ''),
(18, 'C20261102M004', 'Patricio Alejandro Aravena Calvo', ''),
(19, 'C20261102M005', 'Gonzalo Omar Arce Palma', ''),
(20, 'C20261102M006', 'Paula Andrea Aubel Lazo', ''),
(21, 'C20261102M007', 'Rolando Fabio Bahamondes Rojas', ''),
(22, 'C20261102M008', 'Arleana Vanessa Balazs Ramos', ''),
(23, 'C20261102M009', 'Ismael Antonio Ballesteros Mendoza', ''),
(24, 'C20261102M010', 'Tomas Elias Barraza Gomez', ''),
(25, 'C20261102M011', 'Natalia Francisca Barrios Fernandez', ''),
(26, 'C20261102M012', 'Johana Del Valle Barrios Marcano', ''),
(27, 'C20261102M013', 'Eddymar Carolina Belandria Repillosa', ''),
(28, 'C20261102M014', 'Gabriela Alexandra Betancourt Medina', ''),
(29, 'C20261102M015', 'Valentina Soledad Bravo Soto', ''),
(30, 'C20261102M016', 'Valentina Maria Burgos Diaz', ''),
(31, 'C20261102M017', 'Kevin Andres Bustamante Alamos', ''),
(32, 'C20261102M018', 'Patricia Margarita Cadena Rivas', ''),
(33, 'C20261102M019', 'Felipe De Jesus Caldera Mendez', ''),
(34, 'C20261102M020', 'Jeniffer Alejandra Calderon Jeraldo', ''),
(35, 'C20261102M021', 'Juan Sebastian Calderon Segura', ''),
(36, 'C20261102M022', 'Jacqueline Calero Montealegre', ''),
(37, 'C20261102M023', 'Ana Maria Campos Romero', ''),
(38, 'C20261102M024', 'Alvaro Ignacio Raul Ivan Carcamo Lobos', ''),
(39, 'C20261102M025', 'Yadiane Carranca Sanchez', ''),
(40, 'C20261102M026', 'Matias Ignacio Joaquin Carreno Osorio', ''),
(41, 'C20261102M027', 'Efrata Belen Carvajal Siverio', ''),
(42, 'C20261102M028', 'Cindy Vanessa Carvallo Hernandez', ''),
(43, 'C20261102M029', 'Daisy Dayana Cedillo Rossi', ''),
(44, 'C20261102M030', 'Patricio Alejandro Cerda Gonzalez', ''),
(45, 'C20261102M031', 'Rebeca Chavez Zenteno', ''),
(46, 'C20261102M032', 'Oscar Guillermo Cisterna Cea', ''),
(47, 'C20261102M033', 'Luis Enrique Cordova Argudo', ''),
(48, 'C20261102M034', 'Barbara Catalina Couble Pascual', ''),
(49, 'C20261102M035', 'Macarena Francisca De La Maza Cordova', ''),
(50, 'C20261102M036', 'Romina Fernanda De La Rivera Maikowski', ''),
(51, 'C20261102M037', 'Alba Cecilia Del Toro Larios', ''),
(52, 'C20261102M038', 'Joely Yuslany Diaz Arteaga', ''),
(53, 'C20261102M039', 'Sofia Constanza Droppelmann Tavelli', ''),
(54, 'C20261102M040', 'Deyanira Fernanda Espinoza Morales', ''),
(55, 'C20261102M041', 'Esteban Nicolas Fernandez Flores', ''),
(56, 'C20261102M042', 'Mayra Alejandra Fernandez Reyes', ''),
(57, 'C20261102M043', 'Katherine Fonseca Ricaurte', ''),
(58, 'C20261102M044', 'Maximiliano Hector Furnes Valdes', ''),
(59, 'C20261102M045', 'Josefina Antonia Galimany Acosta', ''),
(60, 'C20261102M046', 'Beatriz Francesca Pía Gana Maggiolo', ''),
(61, 'C20261102M047', 'Alina Alejandra Garcia Cabrera', ''),
(62, 'C20261102M048', 'Luisana De Jesus Godoy Velasquez', ''),
(63, 'C20261102M049', 'Yudania Gomez Llopiz', ''),
(64, 'C20261102M050', 'Juliaska Julianny Gonzalez Maldonado', ''),
(65, 'C20261102M051', 'Adriana Jose Graterol Maldonado', ''),
(66, 'C20261102M052', 'Jhony Alexander Hernandez Grajales', ''),
(67, 'C20261102M053', 'Marcelo Andres Ibarra Pinto', ''),
(68, 'C20261102M054', 'Felipe Ignacio Ibarra Quezada', ''),
(69, 'C20261102M055', 'María Consuelo Jara Barrientos', ''),
(70, 'C20261102M056', 'Judith Eulalia Jerez Lema', ''),
(71, 'C20261102M057', 'Alan Vicente Koch Flandez', ''),
(72, 'C20261102M058', 'Alexandra Constanza Lagos Melgarejo', ''),
(73, 'C20261102M059', 'Alex Julio Lalleman Mena', ''),
(74, 'C20261102M060', 'Militza Mariela Lee-Chong Gonzalez', ''),
(75, 'C20261102M061', 'Ana Elena Leon Freitez', ''),
(76, 'C20261102M062', 'Claudia De Jesus Lopez Garcia', ''),
(77, 'C20261102M063', 'Gleidys Patricia Lopez Santana', ''),
(78, 'C20261102M064', 'Kenia Lopez Vallejos', ''),
(79, 'C20261102M065', 'Yolanda Cecilia Lozada El Khouri', ''),
(80, 'C20261102M066', 'Miguel Angel Maldonado Flores', ''),
(81, 'C20261102M067', 'Consuelo Francisca Martínez-Conde Sanhueza', ''),
(82, 'C20261102M068', 'Gisselli Cristina Martinez Hidalgo', ''),
(83, 'C20261102M069', 'Johancil Del Valle Martinez Ramos', ''),
(84, 'C20261102M070', 'Natalia Andrea Melendez Vallejos', ''),
(85, 'C20261102M071', 'Paquita Mariuxi Merino Luna', ''),
(86, 'C20261102M072', 'Carolina Andrea Meza Bertel', ''),
(87, 'C20261102M073', 'Helia Mariana Mirabal Romero', ''),
(88, 'C20261102M074', 'Rodrigo Rafael Montoya Salazar', ''),
(89, 'C20261102M075', 'Arturo Abad Morales Rivas', ''),
(90, 'C20261102M076', 'Matias Andres Moreno Venegas', ''),
(91, 'C20261102M077', 'Joaquin Marcial Enrique Muller Romero', ''),
(92, 'C20261102M078', 'Victoria Beatriz Munoz Acuna', ''),
(93, 'C20261102M079', 'Mario Andres Murcia Cuellar', ''),
(94, 'C20261102M080', 'Matias Eduardo Oliva Padilla', ''),
(95, 'C20261102M081', 'Paulina Alejandra Ortega Caballero', ''),
(96, 'C20261102M082', 'Willmary Beatriz Osorio Natera', ''),
(97, 'C20261102M083', 'Nelson Fabio Ovalle Araujo', ''),
(98, 'C20261102M084', 'Camila Andrea Paiva Brahm', ''),
(99, 'C20261102M085', 'Rodrigo Alfonso Jose Palacios Rius', ''),
(100, 'C20261102M086', 'Angela Patricia Pardo Correa', ''),
(101, 'C20261102M087', 'Paul Andres Pardo Encalada', ''),
(102, 'C20261102M088', 'Alexis Guillermo Pathe Meza', ''),
(103, 'C20261102M089', 'Pablo Ivan Perez Cabezas', ''),
(104, 'C20261102M090', 'Gena Quesada Fuentes', ''),
(105, 'C20261102M091', 'Eric Alfonso Ramirez Villalobos', ''),
(106, 'C20261102M092', 'Maria Jose Ramos Ramos', ''),
(107, 'C20261102M093', 'Jorge Luis Rangel Penaloza', ''),
(108, 'C20261102M094', 'Alexander Recabarren Baez', ''),
(109, 'C20261102M095', 'Jenniffer Gabriela Reinoso Mejia', ''),
(110, 'C20261102M096', 'Yeisson Norberto Rincon Ospina', ''),
(111, 'C20261102M097', 'Angel Daniel Rivera Robles', ''),
(112, 'C20261102M098', 'Natalia Isabel Rivera Alarcón', ''),
(113, 'C20261102M099', 'Dubrhaska Isabel Rodriguez Chavez', ''),
(114, 'C20261102M100', 'Katherine Andrea Rojas Gonzalez', ''),
(115, 'C20261102M101', 'Monica Carolina Roman Molina', ''),
(116, 'C20261102M102', 'Benjamin Jesus Saavedra Cantillana', ''),
(117, 'C20261102M103', 'Tomas Ignacio Saavedra Rojas', ''),
(118, 'C20261102M104', 'Oscar Camilo Saravia Zepeda', ''),
(119, 'C20261102M105', 'Militza Lucia Schwartenski Buneder', ''),
(120, 'C20261102M106', 'Igor Yamil Silva Marmol', ''),
(121, 'C20261102M107', 'Sofia Solano', ''),
(122, 'C20261102M108', 'Alejandra Isabel Soto Rivera', ''),
(123, 'C20261102M109', 'Faheem Tahir', ''),
(124, 'C20261102M110', 'Denisse Del Carmen Teran Guzman', ''),
(125, 'C20261102M111', 'Estefania Carol Teran Quezada', ''),
(126, 'C20261102M112', 'Jose Matias Torres Pineda', ''),
(127, 'C20261102M113', 'Maria De Los Angeles Tudare Miquilena', ''),
(128, 'C20261102M114', 'Katia Camila Tudela Rojas', ''),
(129, 'C20261102M115', 'Andres Felipe Ugalde Barraza', ''),
(130, 'C20261102M116', 'Carlos Andres Urrutia Paredes', ''),
(131, 'C20261102M117', 'Alexandra Rosalina Valenzuela Figueroa', ''),
(132, 'C20261102M118', 'Paulina Natasha Vander Molen Gonzalez', ''),
(133, 'C20261102M119', 'Pietro Hugo Paolo Vasquez Bracamonte', ''),
(134, 'C20261102M120', 'Cilena Andreina Velasquez Castaneda', ''),
(135, 'C20261102M121', 'Fernando Samuel Velazco Altuve', ''),
(136, 'C20261102M122', 'Gustavo Ernesto Vera Real', ''),
(137, 'C20261102M123', 'Maria Paz Vergara Betanzo', ''),
(138, 'C20261102M124', 'Claudia Estefania Yanez Gonzalez', ''),
(139, 'C20261102M125', 'Henry Bienvenido Zambrano Velasquez', ''),
(140, 'C20261102M126', 'Kathy Lizbeth Zambrano Lombeida', ''),
(141, 'C20261102M127', 'Henry Bienvenido Zambrano Velasquez', ''),
(142, 'C20261102M128', 'Jose Manuel Zamorano Munoz', ''),
(143, 'C20261102M129', 'Royra Nairovi Zerpa Brizuela', ''),
(144, 'C20261102M130', 'Paola Mercedes Zurita Hurtado', ''),
(270, 'D20251134M992', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS'),
(271, 'D20251134M994', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION'),
(272, 'D20251134M995', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES'),
(273, 'D20251134M996', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS'),
(274, 'D20251134M010', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES'),
(275, 'D20251134M011', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS'),
(277, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_diplomas_temporal`
--

CREATE TABLE `z_diplomas_temporal` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `autores` text COLLATE utf8_spanish_ci NOT NULL,
  `tema` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Identificador único de sesión de carga',
  `convocatoria_id` int(11) DEFAULT NULL COMMENT 'ID de la convocatoria seleccionada',
  `estado` enum('pendiente','valido','error','codigo_invalido') COLLATE utf8_spanish_ci DEFAULT 'pendiente',
  `mensaje_error` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `fecha_carga` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_diplomas_temporal`
--

INSERT INTO `z_diplomas_temporal` (`id`, `codigo`, `autores`, `tema`, `session_id`, `convocatoria_id`, `estado`, `mensaje_error`, `fecha_carga`) VALUES
(19, 'C20261102M001', 'Adriana Alessandra Acerbi Godoy', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(20, 'C20261102M002', 'Angela Maria Acosta Palacio', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(21, 'C20261102M003', 'Jefferson Javier Alcivar Castillo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(22, 'C20261102M004', 'Patricio Alejandro Aravena Calvo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(23, 'C20261102M005', 'Gonzalo Omar Arce Palma', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(24, 'C20261102M006', 'Paula Andrea Aubel Lazo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(25, 'C20261102M007', 'Rolando Fabio Bahamondes Rojas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(26, 'C20261102M008', 'Arleana Vanessa Balazs Ramos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(27, 'C20261102M009', 'Ismael Antonio Ballesteros Mendoza', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(28, 'C20261102M010', 'Tomas Elias Barraza Gomez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(29, 'C20261102M011', 'Natalia Francisca Barrios Fernandez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(30, 'C20261102M012', 'Johana Del Valle Barrios Marcano', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(31, 'C20261102M013', 'Eddymar Carolina Belandria Repillosa', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(32, 'C20261102M014', 'Gabriela Alexandra Betancourt Medina', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(33, 'C20261102M015', 'Valentina Soledad Bravo Soto', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(34, 'C20261102M016', 'Valentina Maria Burgos Diaz', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(35, 'C20261102M017', 'Kevin Andres Bustamante Alamos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(36, 'C20261102M018', 'Patricia Margarita Cadena Rivas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(37, 'C20261102M019', 'Felipe De Jesus Caldera Mendez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(38, 'C20261102M020', 'Jeniffer Alejandra Calderon Jeraldo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(39, 'C20261102M021', 'Juan Sebastian Calderon Segura', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(40, 'C20261102M022', 'Jacqueline Calero Montealegre', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(41, 'C20261102M023', 'Ana Maria Campos Romero', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(42, 'C20261102M024', 'Alvaro Ignacio Raul Ivan Carcamo Lobos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(43, 'C20261102M025', 'Yadiane Carranca Sanchez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(44, 'C20261102M026', 'Matias Ignacio Joaquin Carreno Osorio', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(45, 'C20261102M027', 'Efrata Belen Carvajal Siverio', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(46, 'C20261102M028', 'Cindy Vanessa Carvallo Hernandez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(47, 'C20261102M029', 'Daisy Dayana Cedillo Rossi', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(48, 'C20261102M030', 'Patricio Alejandro Cerda Gonzalez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(49, 'C20261102M031', 'Rebeca Chavez Zenteno', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(50, 'C20261102M032', 'Oscar Guillermo Cisterna Cea', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(51, 'C20261102M033', 'Luis Enrique Cordova Argudo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(52, 'C20261102M034', 'Barbara Catalina Couble Pascual', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(53, 'C20261102M035', 'Macarena Francisca De La Maza Cordova', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(54, 'C20261102M036', 'Romina Fernanda De La Rivera Maikowski', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(55, 'C20261102M037', 'Alba Cecilia Del Toro Larios', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(56, 'C20261102M038', 'Joely Yuslany Diaz Arteaga', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(57, 'C20261102M039', 'Sofia Constanza Droppelmann Tavelli', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(58, 'C20261102M040', 'Deyanira Fernanda Espinoza Morales', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(59, 'C20261102M041', 'Esteban Nicolas Fernandez Flores', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(60, 'C20261102M042', 'Mayra Alejandra Fernandez Reyes', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(61, 'C20261102M043', 'Katherine Fonseca Ricaurte', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(62, 'C20261102M044', 'Maximiliano Hector Furnes Valdes', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(63, 'C20261102M045', 'Josefina Antonia Galimany Acosta', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(64, 'C20261102M046', 'Beatriz Francesca Pía Gana Maggiolo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(65, 'C20261102M047', 'Alina Alejandra Garcia Cabrera', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(66, 'C20261102M048', 'Luisana De Jesus Godoy Velasquez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(67, 'C20261102M049', 'Yudania Gomez Llopiz', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(68, 'C20261102M050', 'Juliaska Julianny Gonzalez Maldonado', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(69, 'C20261102M051', 'Adriana Jose Graterol Maldonado', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(70, 'C20261102M052', 'Jhony Alexander Hernandez Grajales', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(71, 'C20261102M053', 'Marcelo Andres Ibarra Pinto', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(72, 'C20261102M054', 'Felipe Ignacio Ibarra Quezada', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(73, 'C20261102M055', 'María Consuelo Jara Barrientos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(74, 'C20261102M056', 'Judith Eulalia Jerez Lema', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(75, 'C20261102M057', 'Alan Vicente Koch Flandez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(76, 'C20261102M058', 'Alexandra Constanza Lagos Melgarejo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(77, 'C20261102M059', 'Alex Julio Lalleman Mena', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(78, 'C20261102M060', 'Militza Mariela Lee-Chong Gonzalez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(79, 'C20261102M061', 'Ana Elena Leon Freitez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(80, 'C20261102M062', 'Claudia De Jesus Lopez Garcia', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(81, 'C20261102M063', 'Gleidys Patricia Lopez Santana', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(82, 'C20261102M064', 'Kenia Lopez Vallejos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(83, 'C20261102M065', 'Yolanda Cecilia Lozada El Khouri', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(84, 'C20261102M066', 'Miguel Angel Maldonado Flores', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(85, 'C20261102M067', 'Consuelo Francisca Martínez-Conde Sanhueza', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(86, 'C20261102M068', 'Gisselli Cristina Martinez Hidalgo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(87, 'C20261102M069', 'Johancil Del Valle Martinez Ramos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(88, 'C20261102M070', 'Natalia Andrea Melendez Vallejos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(89, 'C20261102M071', 'Paquita Mariuxi Merino Luna', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(90, 'C20261102M072', 'Carolina Andrea Meza Bertel', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(91, 'C20261102M073', 'Helia Mariana Mirabal Romero', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(92, 'C20261102M074', 'Rodrigo Rafael Montoya Salazar', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(93, 'C20261102M075', 'Arturo Abad Morales Rivas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(94, 'C20261102M076', 'Matias Andres Moreno Venegas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(95, 'C20261102M077', 'Joaquin Marcial Enrique Muller Romero', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(96, 'C20261102M078', 'Victoria Beatriz Munoz Acuna', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(97, 'C20261102M079', 'Mario Andres Murcia Cuellar', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(98, 'C20261102M080', 'Matias Eduardo Oliva Padilla', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(99, 'C20261102M081', 'Paulina Alejandra Ortega Caballero', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(100, 'C20261102M082', 'Willmary Beatriz Osorio Natera', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(101, 'C20261102M083', 'Nelson Fabio Ovalle Araujo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(102, 'C20261102M084', 'Camila Andrea Paiva Brahm', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(103, 'C20261102M085', 'Rodrigo Alfonso Jose Palacios Rius', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(104, 'C20261102M086', 'Angela Patricia Pardo Correa', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(105, 'C20261102M087', 'Paul Andres Pardo Encalada', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(106, 'C20261102M088', 'Alexis Guillermo Pathe Meza', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(107, 'C20261102M089', 'Pablo Ivan Perez Cabezas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(108, 'C20261102M090', 'Gena Quesada Fuentes', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(109, 'C20261102M091', 'Eric Alfonso Ramirez Villalobos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(110, 'C20261102M092', 'Maria Jose Ramos Ramos', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(111, 'C20261102M093', 'Jorge Luis Rangel Penaloza', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(112, 'C20261102M094', 'Alexander Recabarren Baez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(113, 'C20261102M095', 'Jenniffer Gabriela Reinoso Mejia', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(114, 'C20261102M096', 'Yeisson Norberto Rincon Ospina', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(115, 'C20261102M097', 'Angel Daniel Rivera Robles', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(116, 'C20261102M098', 'Natalia Isabel Rivera Alarcón', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(117, 'C20261102M099', 'Dubrhaska Isabel Rodriguez Chavez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(118, 'C20261102M100', 'Katherine Andrea Rojas Gonzalez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(119, 'C20261102M101', 'Monica Carolina Roman Molina', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(120, 'C20261102M102', 'Benjamin Jesus Saavedra Cantillana', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(121, 'C20261102M103', 'Tomas Ignacio Saavedra Rojas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(122, 'C20261102M104', 'Oscar Camilo Saravia Zepeda', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(123, 'C20261102M105', 'Militza Lucia Schwartenski Buneder', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(124, 'C20261102M106', 'Igor Yamil Silva Marmol', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(125, 'C20261102M107', 'Sofia Solano', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(126, 'C20261102M108', 'Alejandra Isabel Soto Rivera', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(127, 'C20261102M109', 'Faheem Tahir', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(128, 'C20261102M110', 'Denisse Del Carmen Teran Guzman', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(129, 'C20261102M111', 'Estefania Carol Teran Quezada', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(130, 'C20261102M112', 'Jose Matias Torres Pineda', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(131, 'C20261102M113', 'Maria De Los Angeles Tudare Miquilena', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(132, 'C20261102M114', 'Katia Camila Tudela Rojas', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(133, 'C20261102M115', 'Andres Felipe Ugalde Barraza', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(134, 'C20261102M116', 'Carlos Andres Urrutia Paredes', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(135, 'C20261102M117', 'Alexandra Rosalina Valenzuela Figueroa', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(136, 'C20261102M118', 'Paulina Natasha Vander Molen Gonzalez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(137, 'C20261102M119', 'Pietro Hugo Paolo Vasquez Bracamonte', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(138, 'C20261102M120', 'Cilena Andreina Velasquez Castaneda', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(139, 'C20261102M121', 'Fernando Samuel Velazco Altuve', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(140, 'C20261102M122', 'Gustavo Ernesto Vera Real', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(141, 'C20261102M123', 'Maria Paz Vergara Betanzo', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(142, 'C20261102M124', 'Claudia Estefania Yanez Gonzalez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(143, 'C20261102M125', 'Henry Bienvenido Zambrano Velasquez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(144, 'C20261102M126', 'Kathy Lizbeth Zambrano Lombeida', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(145, 'C20261102M127', 'Henry Bienvenido Zambrano Velasquez', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(146, 'C20261102M128', 'Jose Manuel Zamorano Munoz', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(147, 'C20261102M129', 'Royra Nairovi Zerpa Brizuela', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(148, 'C20261102M130', 'Paola Mercedes Zurita Hurtado', '', 'CARGA_1769111175700_nw4fvyi2n', 3, 'error', 'Tema vacío', '2026-01-22 16:47:29'),
(291, 'D20251134M994;NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOM', '', '', 'CARGA_1769113369901_hn97ll7jl', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:22:54'),
(292, 'D20251134M995;JUAN PEREZ GARCIA, MARIA LOPEZ RODRI', '', '', 'CARGA_1769113369901_hn97ll7jl', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:22:54'),
(293, 'D20251134M996;CARLOS MARTINEZ SOTO;ANALISIS DE FAC', '', '', 'CARGA_1769113369901_hn97ll7jl', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:22:54'),
(294, 'D20251134M994;NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOM', '', '', 'CARGA_1769113516971_vfk8g33lw', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:25:23'),
(295, 'D20251134M995;JUAN PEREZ GARCIA, MARIA LOPEZ RODRI', '', '', 'CARGA_1769113516971_vfk8g33lw', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:25:23'),
(296, 'D20251134M996;CARLOS MARTINEZ SOTO;ANALISIS DE FAC', '', '', 'CARGA_1769113516971_vfk8g33lw', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:25:23'),
(297, 'D20251134M994;NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOM', '', '', 'CARGA_1769113535882_t7fp0hsjo', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:25:47'),
(298, 'D20251134M995;JUAN PEREZ GARCIA, MARIA LOPEZ RODRI', '', '', 'CARGA_1769113535882_t7fp0hsjo', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:25:47'),
(299, 'D20251134M996;CARLOS MARTINEZ SOTO;ANALISIS DE FAC', '', '', 'CARGA_1769113535882_t7fp0hsjo', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:25:47'),
(300, 'D20251134M994;NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOM', '', '', 'CARGA_1769113618073_s7wb2ou5w', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:27:06'),
(301, 'D20251134M995;JUAN PEREZ GARCIA, MARIA LOPEZ RODRI', '', '', 'CARGA_1769113618073_s7wb2ou5w', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:27:06'),
(302, 'D20251134M996;CARLOS MARTINEZ SOTO;ANALISIS DE FAC', '', '', 'CARGA_1769113618073_s7wb2ou5w', 4, 'codigo_invalido', 'Código excede 50 caracteres; Código debe tener 13 caracteres; Autores vacío', '2026-01-22 17:27:06'),
(309, 'D20251134M994', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769527659201_sqxjhzquq', 4, 'error', 'Código ya existe en el sistema', '2026-01-27 12:27:54'),
(310, 'D20251134M995', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769527659201_sqxjhzquq', 4, 'error', 'Código ya existe en el sistema', '2026-01-27 12:27:54'),
(311, 'D20251134M996', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769527659201_sqxjhzquq', 4, 'error', 'Código ya existe en el sistema', '2026-01-27 12:27:54'),
(312, 'D20251134M010', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769694731786_kam0hhtnh', 4, 'valido', '', '2026-01-29 10:52:17'),
(313, 'D20251134M011', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769694731786_kam0hhtnh', 4, 'valido', '', '2026-01-29 10:52:17'),
(314, 'D20251134M010', 'victor quintana', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769694731786_kam0hhtnh', 4, 'error', 'Código duplicado en el archivo', '2026-01-29 10:52:17'),
(315, 'D20251134012', 'andres aedo', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769694731786_kam0hhtnh', 4, 'codigo_invalido', 'Código debe tener 13 caracteres', '2026-01-29 10:52:17'),
(320, 'D20251134M013', '', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769696693091_qc6tkmy9w', 4, 'error', 'Autores vacío', '2026-01-29 11:24:59'),
(327, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769711061377_xetfdjpt8', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:24:34'),
(328, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769711061377_xetfdjpt8', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:24:34'),
(329, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769711061377_xetfdjpt8', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:24:34'),
(330, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769711648847_ygqo2crhl', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:34:14'),
(331, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769711648847_ygqo2crhl', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:34:14'),
(332, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769711648847_ygqo2crhl', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:34:14'),
(333, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769711799948_k0zvswmr8', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:37:06'),
(334, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769711799948_k0zvswmr8', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:37:06'),
(335, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769711799948_k0zvswmr8', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:37:06'),
(336, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712292860_scbevarqy', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:44:59'),
(337, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712292860_scbevarqy', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:44:59'),
(338, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712292860_scbevarqy', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:44:59'),
(339, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712307306_5w0mk24ga', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:45:23'),
(340, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712307306_5w0mk24ga', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:45:23'),
(341, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712307306_5w0mk24ga', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:45:23'),
(342, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712385771_wxr31y3uz', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:46:30'),
(343, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712385771_wxr31y3uz', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:46:30'),
(344, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712385771_wxr31y3uz', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:46:30'),
(345, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712696743_el8r7rse9', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:51:49'),
(346, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712696743_el8r7rse9', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:51:49'),
(347, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712696743_el8r7rse9', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:51:49'),
(348, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712745439_b88utbmby', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:53:45'),
(349, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712745439_b88utbmby', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:53:45'),
(350, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712745439_b88utbmby', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:53:45'),
(351, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712838170_u8rfkcph5', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:54:04'),
(352, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712838170_u8rfkcph5', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:54:04'),
(353, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712838170_u8rfkcph5', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:54:04'),
(354, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769712980314_uu3nkwoa5', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 15:56:26'),
(355, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769712980314_uu3nkwoa5', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:56:26'),
(356, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769712980314_uu3nkwoa5', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 15:56:26'),
(357, 'D20261003R001', 'NOMBRE AUTOR 1, NOMBRE AUTOR 2 Y NOMBRE AUTOR 3', 'TITULO DEL TEMA O TRABAJO DE INVESTIGACION', 'CARGA_1769713539299_zrp5fqsyf', 7, 'error', 'Código ya existe en el sistema', '2026-01-29 16:05:53'),
(358, 'D20251134M998', 'JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ', 'ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES', 'CARGA_1769713539299_zrp5fqsyf', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 16:05:53'),
(359, 'D20251134M997', 'CARLOS MARTINEZ SOTO', 'ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS', 'CARGA_1769713539299_zrp5fqsyf', 7, 'codigo_invalido', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', '2026-01-29 16:05:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_log_rechazados`
--

CREATE TABLE `z_log_rechazados` (
  `id` int(11) NOT NULL,
  `tipo_carga` enum('diplomas','convocatorias') COLLATE utf8_spanish_ci NOT NULL COMMENT 'Tipo de carga que generó el rechazo',
  `session_id` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Session ID de la carga',
  `convocatoria_id` int(11) DEFAULT NULL COMMENT 'ID de convocatoria (solo para diplomas)',
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Código del registro rechazado',
  `datos_registro` text COLLATE utf8_spanish_ci NOT NULL COMMENT 'JSON con todos los campos del registro',
  `mensaje_error` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL COMMENT 'Motivo del rechazo',
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuario que realizó la carga',
  `fecha_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_log_rechazados`
--

INSERT INTO `z_log_rechazados` (`id`, `tipo_carga`, `session_id`, `convocatoria_id`, `codigo`, `datos_registro`, `mensaje_error`, `usuario_id`, `fecha_registro`) VALUES
(1, 'convocatorias', 'CONV_1769708783_96309b03', NULL, 'D20261001M', '{\"codigo_base\":\"D20261001M\",\"nombre\":\"Congreso Metropolitano de Ejemplo 2026\",\"tipo_documento\":\"Diploma\",\"info_institucional\":\"Texto institucional que aparece en la validacion del diploma. Puede ser extenso.\",\"etiqueta_persona\":\"Autor(es)\",\"etiqueta_tema\":\"Trabajo - Articulo - Tema presentado\",\"mensaje_error\":\"Código base ya existe en el sistema\"}', 'Código base ya existe en el sistema', 2, '2026-01-29 14:46:53'),
(2, 'convocatorias', 'CONV_1769708783_96309b03', NULL, 'C2026100N', '{\"codigo_base\":\"C2026100N\",\"nombre\":\"Certificado de Participacion Nacional\",\"tipo_documento\":\"Certificado\",\"info_institucional\":\"Participante del evento nacional celebrado en Santiago.\",\"etiqueta_persona\":\"Participante\",\"etiqueta_tema\":null,\"mensaje_error\":\"Código base: Debe tener exactamente 10 caracteres\"}', 'Código base: Debe tener exactamente 10 caracteres', 2, '2026-01-29 14:46:53'),
(3, 'convocatorias', 'CONV_1769709659_2da1e71b', NULL, 'D20261001M', '{\"codigo_base\":\"D20261001M\",\"nombre\":\"Congreso Metropolitano de Ejemplo 2026\",\"tipo_documento\":\"Diploma\",\"info_institucional\":\"Texto institucional que aparece en la validacion del diploma. Puede ser extenso.\",\"etiqueta_persona\":\"Autor(es)\",\"etiqueta_tema\":\"Trabajo - Articulo - Tema presentado\",\"mensaje_error\":\"Código base ya existe en el sistema\"}', 'Código base ya existe en el sistema', 2, '2026-01-29 15:13:13'),
(4, 'convocatorias', 'CONV_1769709659_2da1e71b', NULL, 'C2026100N', '{\"codigo_base\":\"C2026100N\",\"nombre\":\"Certificado de Participacion Nacional\",\"tipo_documento\":\"Certificado\",\"info_institucional\":\"Participante del evento nacional celebrado en Santiago.\",\"etiqueta_persona\":\"Participante\",\"etiqueta_tema\":null,\"mensaje_error\":\"Código base: Debe tener exactamente 10 caracteres\"}', 'Código base: Debe tener exactamente 10 caracteres', 2, '2026-01-29 15:13:13'),
(5, 'convocatorias', 'CONV_1769709659_2da1e71b', NULL, 'D20251003R', '{\"codigo_base\":\"D20251003R\",\"nombre\":\"Diploma Regional de Investigacion 2025\",\"tipo_documento\":\"Diploma\",\"info_institucional\":\"Descripcion del evento regional.\",\"etiqueta_persona\":\"Autor(es)\",\"etiqueta_tema\":null,\"mensaje_error\":\"Código base ya existe en el sistema\"}', 'Código base ya existe en el sistema', 2, '2026-01-29 15:13:13'),
(6, 'convocatorias', 'CONV_1769709659_2da1e71b', NULL, 'D20251001R', '{\"codigo_base\":\"D20251001R\",\"nombre\":\"Diploma Regional de Investigacion 2025\",\"tipo_documento\":\"\",\"info_institucional\":\"\",\"etiqueta_persona\":\"Autor(es)\",\"etiqueta_tema\":null,\"mensaje_error\":\"Tipo de documento vacío\"}', 'Tipo de documento vacío', 2, '2026-01-29 15:13:13'),
(7, 'convocatorias', 'CONV_1769709659_2da1e71b', NULL, 'D20251000R', '{\"codigo_base\":\"D20251000R\",\"nombre\":\"\",\"tipo_documento\":\"\",\"info_institucional\":\"\",\"etiqueta_persona\":\"Autor(es)\",\"etiqueta_tema\":null,\"mensaje_error\":\"Nombre vacío; Tipo de documento vacío\"}', 'Nombre vacío; Tipo de documento vacío', 2, '2026-01-29 15:13:13'),
(8, 'diplomas', 'CARGA_1769710929002_4or3e1yi3', 7, 'D20251134M998', '{\"codigo\":\"D20251134M998\",\"autores\":\"JUAN PEREZ GARCIA, MARIA LOPEZ RODRIGUEZ\",\"tema\":\"ESTUDIO SOBRE EL IMPACTO DE LA TELEMEDICINA EN ZONAS RURALES\",\"mensaje_error\":\"Código debe iniciar con D20261003R; Código ya existe en el sistema\"}', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', 2, '2026-01-29 15:23:26'),
(9, 'diplomas', 'CARGA_1769710929002_4or3e1yi3', 7, 'D20251134M997', '{\"codigo\":\"D20251134M997\",\"autores\":\"CARLOS MARTINEZ SOTO\",\"tema\":\"ANALISIS DE FACTORES DE RIESGO CARDIOVASCULAR EN PACIENTES DIABETICOS\",\"mensaje_error\":\"Código debe iniciar con D20261003R; Código ya existe en el sistema\"}', 'Código debe iniciar con D20261003R; Código ya existe en el sistema', 2, '2026-01-29 15:23:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_log_seguridad`
--

CREATE TABLE `z_log_seguridad` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_spanish_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `datos_extra` text COLLATE utf8_spanish_ci,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_log_seguridad`
--

INSERT INTO `z_log_seguridad` (`id`, `tipo`, `descripcion`, `usuario_id`, `ip`, `user_agent`, `datos_extra`, `fecha`) VALUES
(19, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-22 16:54:39'),
(20, 'login_fallido', 'Contraseña incorrecta para: admin', NULL, '172.25.0.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '{\"username\":\"admin\"}', '2026-01-22 17:00:38'),
(21, 'login_exitoso', 'Login exitoso: admin', 3, '172.25.0.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '[]', '2026-01-22 17:00:42'),
(22, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-22 17:01:37'),
(23, 'login_exitoso', 'Login exitoso: admin', 3, '172.25.0.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-22 19:40:35'),
(24, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-27 12:00:16'),
(25, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-27 12:01:42'),
(26, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-27 12:17:05'),
(27, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 10:49:33'),
(28, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 11:24:52'),
(29, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 14:28:40'),
(30, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 14:38:50'),
(31, 'login_exitoso', 'Login exitoso: admin', 3, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '[]', '2026-01-29 14:39:18'),
(32, 'carga_masiva_convocatorias', 'Cargadas 3 convocatorias. Rechazadas: 0', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 14:44:32'),
(33, 'carga_masiva_convocatorias', 'Cargadas 1 convocatorias. Rechazadas: 2', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 14:46:53'),
(34, 'carga_masiva_convocatorias', 'Cargadas 1 convocatorias. Rechazadas: 5', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 15:13:13'),
(35, 'carga_masiva_diplomas', 'Cargados 1 diplomas. Rechazados: 2. Convocatoria ID: 7', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 15:23:26'),
(36, 'login_exitoso', 'Login exitoso: victorq', 2, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '[]', '2026-01-29 15:46:24'),
(37, 'login_exitoso', 'Login exitoso: admin', 3, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '[]', '2026-01-29 15:51:21'),
(38, 'login_exitoso', 'Login exitoso: admin', 3, '172.25.0.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '[]', '2026-01-29 15:51:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_registro`
--

CREATE TABLE `z_registro` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_registro`
--

INSERT INTO `z_registro` (`id`, `codigo`, `fecha`) VALUES
(21, 'D20251134M997', '2026-01-22 16:50:22'),
(22, 'C20261102M130', '2026-01-22 16:50:30'),
(23, 'D20251134M997', '2026-01-22 16:50:48'),
(24, 'D20251134M997', '2026-01-22 16:51:51'),
(25, 'D20251134M996', '2026-01-22 19:47:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_usuarios`
--

CREATE TABLE `z_usuarios` (
  `id` int(11) NOT NULL,
  `rut` varchar(12) COLLATE utf8_spanish_ci NOT NULL COMMENT 'RUT del usuario',
  `username` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `rol` enum('admin','usuario') COLLATE utf8_spanish_ci DEFAULT 'usuario',
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_usuarios`
--

INSERT INTO `z_usuarios` (`id`, `rut`, `username`, `password`, `nombre`, `email`, `rol`, `activo`, `ultimo_acceso`, `fecha_creacion`) VALUES
(2, '16784781-1', 'victorq', '$2y$10$3p6XpJiNZjXWpMDd0Uq0nuqJ0QfGdwQdy5rRLaEPO.qrl2DYmMQHy', 'victor quintana', 'felpilla@gmail.com', 'admin', 1, '2026-01-29 15:46:24', '2026-01-15 14:07:35'),
(3, '12345678-5', 'admin', '$2y$10$P/G.deZLoshVZr65EiezxOEI8YLhGZpWf7JlWT1w6U5hOTeOl/bkS', 'user_admin', 'user_admin@adimin.cl', 'admin', 1, '2026-01-29 15:51:35', '2026-01-15 14:27:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `z_usuarios_permitidos`
--

CREATE TABLE `z_usuarios_permitidos` (
  `id` int(11) NOT NULL,
  `rut` varchar(12) COLLATE utf8_spanish_ci NOT NULL COMMENT 'RUT sin puntos, con guión (ej: 12345678-9)',
  `nombre` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Nombre completo de la persona',
  `email` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `rol` enum('admin','usuario') COLLATE utf8_spanish_ci DEFAULT 'usuario',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=puede registrarse, 0=bloqueado',
  `fecha_agregado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `z_usuarios_permitidos`
--

INSERT INTO `z_usuarios_permitidos` (`id`, `rut`, `nombre`, `email`, `rol`, `activo`, `fecha_agregado`) VALUES
(1, '16784781-1', 'victor quintana', 'felpilla@gmail.com', 'admin', 1, '2026-01-15 14:06:58'),
(2, '12345678-5', 'user_admin', 'user_admin@adimin.cl', 'admin', 1, '2026-01-15 14:25:24');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `z_convocatorias`
--
ALTER TABLE `z_convocatorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_base` (`codigo_base`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `z_convocatorias_temporal`
--
ALTER TABLE `z_convocatorias_temporal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_codigo_base` (`codigo_base`),
  ADD KEY `idx_fecha_carga` (`fecha_carga`);

--
-- Indices de la tabla `z_diplomas`
--
ALTER TABLE `z_diplomas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`);

--
-- Indices de la tabla `z_diplomas_temporal`
--
ALTER TABLE `z_diplomas_temporal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_fecha_carga` (`fecha_carga`),
  ADD KEY `idx_convocatoria_id` (`convocatoria_id`);

--
-- Indices de la tabla `z_log_rechazados`
--
ALTER TABLE `z_log_rechazados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo_carga` (`tipo_carga`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_fecha` (`fecha_registro`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_convocatoria` (`convocatoria_id`);

--
-- Indices de la tabla `z_log_seguridad`
--
ALTER TABLE `z_log_seguridad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_ip` (`ip`);

--
-- Indices de la tabla `z_registro`
--
ALTER TABLE `z_registro`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `z_usuarios`
--
ALTER TABLE `z_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `z_usuarios_permitidos`
--
ALTER TABLE `z_usuarios_permitidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `z_convocatorias`
--
ALTER TABLE `z_convocatorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `z_convocatorias_temporal`
--
ALTER TABLE `z_convocatorias_temporal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `z_diplomas`
--
ALTER TABLE `z_diplomas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT de la tabla `z_diplomas_temporal`
--
ALTER TABLE `z_diplomas_temporal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=360;

--
-- AUTO_INCREMENT de la tabla `z_log_rechazados`
--
ALTER TABLE `z_log_rechazados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `z_log_seguridad`
--
ALTER TABLE `z_log_seguridad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `z_registro`
--
ALTER TABLE `z_registro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `z_usuarios`
--
ALTER TABLE `z_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `z_usuarios_permitidos`
--
ALTER TABLE `z_usuarios_permitidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
