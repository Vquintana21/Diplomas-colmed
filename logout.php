<?php
/**
 * logout.php
 * Cerrar sesión del usuario
 */

require_once 'auth.php';

// Cerrar sesión
cerrarSesion();

// Redirigir al login
header('Location: login.php');
exit;
?>
