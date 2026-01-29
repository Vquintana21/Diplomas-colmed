<?php
/**
 * Configuración de Base de Datos
 * Sistema de Carga Masiva de Diplomas
 * 
 * INSTRUCCIONES:
 * 1. Modifica los valores según tu configuración de cPanel
 * 2. El host generalmente es 'localhost' en cPanel
 * 3. El usuario y base de datos tienen el prefijo de tu cuenta cPanel
 *    Ejemplo: si tu cuenta es "usuario1", la BD sería "usuario1_diplomas"
 */

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS - MODIFICAR AQUÍ
// =====================================================

define('DB_HOST', 'localhost');           // Servidor (normalmente localhost en cPanel)
define('DB_USER', 'localhost');       // Usuario de la base de datos
define('DB_PASS', 'localhost');      // Contraseña de la base de datos  
define('DB_NAME', 'localhost');        // Nombre de la base de datos

// =====================================================
// NO MODIFICAR DEBAJO DE ESTA LÍNEA
// =====================================================

// Conexión a la base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(array(
        'success' => false,
        'mensaje' => 'Error de conexión a la base de datos: ' . $conn->connect_error
    )));
}

// Establecer charset UTF-8
$conn->set_charset('utf8');

// Zona horaria (ajustar según necesidad)
date_default_timezone_set('America/Santiago');

define('LOG_RECHAZADOS_HABILITADO', true);
?>
