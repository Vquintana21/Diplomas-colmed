-- =====================================================
-- SCRIPT DE MIGRACIÓN - Agregar soporte de Convocatorias
-- Versión 2.0
-- Ejecutar en bases de datos que ya tengan las tablas base
-- =====================================================

-- 1. Crear tabla de convocatorias
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

-- 2. Agregar columna convocatoria_id a tabla temporal (si no existe)
-- Primero verificamos si la columna existe
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'z_diplomas_temporal' 
    AND COLUMN_NAME = 'convocatoria_id'
);

-- Si no existe, la agregamos (MySQL no soporta IF en ALTER TABLE directamente)
-- Ejecutar manualmente si da error:
ALTER TABLE `z_diplomas_temporal` 
ADD COLUMN IF NOT EXISTS `convocatoria_id` int(11) DEFAULT NULL COMMENT 'ID de la convocatoria seleccionada' AFTER `session_id`;

-- 3. Modificar enum de estado para incluir 'codigo_invalido'
ALTER TABLE `z_diplomas_temporal` 
MODIFY COLUMN `estado` enum('pendiente','valido','error','codigo_invalido') COLLATE utf8_spanish_ci DEFAULT 'pendiente';

-- 4. Agregar índice para convocatoria_id si no existe
CREATE INDEX IF NOT EXISTS `idx_convocatoria_id` ON `z_diplomas_temporal` (`convocatoria_id`);

-- =====================================================
-- DATOS INICIALES (Ejemplo - Ajustar según necesidad)
-- =====================================================

-- Insertar convocatoria de ejemplo basada en datos existentes
-- INSERT INTO `z_convocatorias` (`codigo_base`, `nombre`, `tipo_documento`, `info_institucional`, `etiqueta_persona`, `etiqueta_tema`) VALUES
-- ('D20251134M', 'Congreso Metropolitano MGZ Julio 2025', 'Diploma', '"Autor/a" del trabajo de investigación presentado en el Congreso Metropolitano de Médicos Generales de Zona, patrocinado por el capítulo Metropolitano de Médicos Generales de Zona y Colegio Médico Regional Santiago, entre el 24 y 25 de Julio en Santiago de Chile.', 'Autor(es)', 'Trabajo - Artículo - Tema presentado');

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
-- Verificar que la migración fue exitosa
SELECT 'Migración completada' AS status;
SELECT COUNT(*) AS total_convocatorias FROM z_convocatorias;
