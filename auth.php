<?php
/**
 * auth.php
 * Sistema de autenticación y protección de sesiones
 * Incluir este archivo al inicio de cada página protegida
 * 
 * Cumple con ISO 27001: A.8.25, A.8.26, A.8.28
 */

// Incluir funciones de seguridad
require_once __DIR__ . '/security.php';

// Iniciar sesión segura
iniciarSesionSegura();

// Tiempo de expiración de sesión (30 minutos)
define('SESSION_TIMEOUT', 1800);

/**
 * Verificar si el usuario está autenticado
 */
function estaAutenticado() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_logueado'])) {
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactivo = time() - $_SESSION['ultimo_acceso'];
        if ($inactivo > SESSION_TIMEOUT) {
            cerrarSesion();
            return false;
        }
    }
    
    // Actualizar tiempo de último acceso
    $_SESSION['ultimo_acceso'] = time();
    
    return true;
}

/**
 * Requerir autenticación - redirige al login si no está autenticado
 */
function requerirAutenticacion() {
    if (!estaAutenticado()) {
        // Guardar URL actual para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        header('Location: login.php');
        exit;
    }
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Obtener datos del usuario actual
 */
function obtenerUsuarioActual() {
    if (!estaAutenticado()) {
        return null;
    }
    
    return array(
        'id' => $_SESSION['usuario_id'],
        'username' => $_SESSION['usuario_username'],
        'nombre' => $_SESSION['usuario_nombre'],
        'rol' => $_SESSION['usuario_rol']
    );
}

/**
 * Verificar si es administrador
 */
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

// Las funciones CSRF están en security.php:
// - generarTokenCSRF()
// - verificarTokenCSRF()
// - campoCSRF()

// Si este archivo se incluye en una página protegida, verificar autenticación
// Las páginas que no requieren auth (login.php) no deben llamar a requerirAutenticacion()
?>
