-- =====================================================
-- SISTEMA DE CARGA MASIVA DE DIPLOMAS
-- Script de instalación de base de datos
-- =====================================================

-- Crear base de datos (opcional, si no existe)
-- CREATE DATABASE IF NOT EXISTS `nombre_base_datos` CHARACTER SET utf8 COLLATE utf8_spanish_ci;
-- USE `nombre_base_datos`;

-- =====================================================
-- TABLA DE LOG DE SEGURIDAD (ISO 27001 - A.8.15, A.8.16)
-- Registro de eventos de seguridad para auditoría
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_log_seguridad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Tipo de evento: login_exitoso, login_fallido, logout, cambio_password, etc.',
  `descripcion` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_spanish_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `datos_extra` text COLLATE utf8_spanish_ci COMMENT 'JSON con datos adicionales',
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- =====================================================
-- TABLA DE USUARIOS PERMITIDOS (RUTs autorizados)
-- Solo las personas en esta tabla pueden registrarse
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_usuarios_permitidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut` varchar(12) COLLATE utf8_spanish_ci NOT NULL COMMENT 'RUT sin puntos, con guión (ej: 12345678-9)',
  `nombre` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Nombre completo de la persona',
  `email` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `rol` enum('admin','usuario') COLLATE utf8_spanish_ci DEFAULT 'usuario' COMMENT 'Rol que tendrá al registrarse',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=puede registrarse, 0=bloqueado',
  `fecha_agregado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Ejemplo de usuarios permitidos (descomentar y modificar según necesidad)
-- INSERT INTO `z_usuarios_permitidos` (`rut`, `nombre`, `email`) VALUES
-- ('12345678-9', 'Juan Pérez González', 'juan.perez@ejemplo.com'),
-- ('98765432-1', 'María López Silva', 'maria.lopez@ejemplo.com');

-- =====================================================
-- TABLA DE USUARIOS (usuarios registrados)
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rut` varchar(12) COLLATE utf8_spanish_ci NOT NULL COMMENT 'RUT del usuario',
  `username` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `rol` enum('admin','usuario') COLLATE utf8_spanish_ci DEFAULT 'usuario',
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rut` (`rut`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Usuario administrador por defecto (no requiere estar en z_usuarios_permitidos)
-- Usuario: admin / Contraseña: admin123 (CAMBIAR DESPUÉS DE INSTALAR)
INSERT INTO `z_usuarios` (`rut`, `username`, `password`, `nombre`, `email`, `rol`) VALUES
('00000000-0', 'admin', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQbLgtnOoNFnsVgKdKS7LGxGsGDAYq', 'Administrador', 'admin@ejemplo.com', 'admin');

-- =====================================================
-- TABLA DE CONVOCATORIAS (tipos de diploma/certificado)
-- Define los códigos base y textos institucionales
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_convocatorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_base` varchar(20) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Prefijo del código (10 caracteres). Ej: D20251134M',
  `nombre` varchar(200) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Nombre descriptivo de la convocatoria',
  `tipo_documento` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Tipo: Diploma, Certificado, Participación, etc.',
  `info_institucional` text COLLATE utf8_spanish_ci COMMENT 'Texto institucional que aparece en la validación',
  `etiqueta_persona` varchar(100) COLLATE utf8_spanish_ci DEFAULT 'Autor(es)' COMMENT 'Etiqueta para el campo de personas',
  `etiqueta_tema` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL COMMENT 'Etiqueta para el tema (opcional, puede ser NULL)',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=activo, 0=inactivo',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_base` (`codigo_base`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Ejemplo de convocatoria (descomentar si desea)
-- INSERT INTO `z_convocatorias` (`codigo_base`, `nombre`, `tipo_documento`, `info_institucional`, `etiqueta_persona`, `etiqueta_tema`) VALUES
-- ('D20251134M', 'Congreso Metropolitano MGZ Julio 2025', 'Diploma', '"Autor/a" del trabajo de investigación presentado en el Congreso Metropolitano de Médicos Generales de Zona, patrocinado por el capítulo Metropolitano de Médicos Generales de Zona y Colegio Médico Regional Santiago, entre el 24 y 25 de Julio en Santiago de Chile.', 'Autor(es)', 'Trabajo - Artículo - Tema presentado');

-- =====================================================
-- TABLA PRINCIPAL: z_diplomas
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_diplomas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `autores` text COLLATE utf8_spanish_ci NOT NULL,
  `tema` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- =====================================================
-- TABLA TEMPORAL: z_diplomas_temporal
-- Para precarga y validación antes de inserción final
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_diplomas_temporal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `autores` text COLLATE utf8_spanish_ci NOT NULL,
  `tema` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `session_id` varchar(100) COLLATE utf8_spanish_ci NOT NULL COMMENT 'Identificador único de sesión de carga',
  `convocatoria_id` int(11) DEFAULT NULL COMMENT 'ID de la convocatoria seleccionada',
  `estado` enum('pendiente','valido','error','codigo_invalido') COLLATE utf8_spanish_ci DEFAULT 'pendiente',
  `mensaje_error` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
  `fecha_carga` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_fecha_carga` (`fecha_carga`),
  KEY `idx_convocatoria_id` (`convocatoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- =====================================================
-- TABLA DE REGISTRO: z_registro (para auditoría de validaciones)
-- =====================================================
CREATE TABLE IF NOT EXISTS `z_registro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- =====================================================
-- DATOS DE EJEMPLO (opcional - descomentar si desea)
-- =====================================================
/*
INSERT INTO `z_diplomas` (`codigo`, `autores`, `tema`) VALUES
('D20251134M001', 'CAROLINA JIMENEZ ZENTENO, CRISTINA ALEJANDRA JIMENEZ ZENTENO', 'MIASTENIA GRAVIS: ENFOQUE INTEGRAL EN EL TRATAMIENTO DE UN CASO COMPLEJO.'),
('D20251134M002', 'SEBASTIAN ALFREDO ASTROZA SUAREZ, LORETO CORREA MUNOZ', 'UN ENFOQUE PREVENTIVO: RECONOCIMIENTO DE SIGNOS DE ALARMA EN TUMORES ÓSEOS.');
*/
