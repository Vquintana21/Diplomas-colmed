<?php
/**
 * limpiar-temporal.php
 * Limpia datos de la tabla temporal
 * - Si recibe session_id: limpia solo esa sesión
 * - Sin session_id: limpia sesiones antiguas (+24 horas)
 * Compatible con PHP 5.6+
 */

// Verificar autenticación
require_once 'auth.php';
if (!estaAutenticado()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'mensaje' => 'Sesión expirada. Por favor, inicie sesión nuevamente.'));
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Incluir configuración
require_once 'config.php';

/**
 * Responder en formato JSON y terminar
 */
function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// PROCESO DE LIMPIEZA
// ============================================

$session_id = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';

if (!empty($session_id)) {
    // Limpiar sesión específica
    $session_id = $conn->real_escape_string($session_id);
    
    $query = "DELETE FROM z_diplomas_temporal WHERE session_id = '$session_id'";
    
    if ($conn->query($query)) {
        $eliminados = $conn->affected_rows;
        
        responderJSON(array(
            'success' => true,
            'mensaje' => 'Datos temporales eliminados correctamente',
            'eliminados' => $eliminados
        ));
    } else {
        responderJSON(array(
            'success' => false,
            'mensaje' => 'Error al eliminar datos temporales: ' . $conn->error
        ));
    }
    
} else {
    // Limpieza automática de sesiones antiguas (más de 24 horas)
    // Útil para ejecutar como tarea programada (cron)
    
    $fechaLimite = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    $query = "DELETE FROM z_diplomas_temporal WHERE fecha_carga < '$fechaLimite'";
    
    if ($conn->query($query)) {
        $eliminados = $conn->affected_rows;
        
        responderJSON(array(
            'success' => true,
            'mensaje' => 'Limpieza de sesiones antiguas completada',
            'eliminados' => $eliminados,
            'fecha_limite' => $fechaLimite
        ));
    } else {
        responderJSON(array(
            'success' => false,
            'mensaje' => 'Error en limpieza automática: ' . $conn->error
        ));
    }
}
?>
