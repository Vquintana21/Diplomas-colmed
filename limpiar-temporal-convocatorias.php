<?php
/**
 * limpiar-temporal-convocatorias.php
 * Limpia datos de la tabla temporal de convocatorias
 * Compatible con PHP 5.6+
 */

// Verificar autenticación
require_once 'auth.php';
if (!estaAutenticado()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'mensaje' => 'Sesión expirada.'));
    exit;
}

// Solo admin
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'mensaje' => 'No autorizado.'));
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

/**
 * Responder JSON
 */
function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$session_id = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';

if (!empty($session_id)) {
    // Limpiar sesión específica
    $session_id = $conn->real_escape_string($session_id);
    
    $query = "DELETE FROM z_convocatorias_temporal WHERE session_id = '$session_id'";
    
    if ($conn->query($query)) {
        $eliminados = $conn->affected_rows;
        
        responderJSON(array(
            'success' => true,
            'mensaje' => 'Datos temporales eliminados',
            'eliminados' => $eliminados
        ));
    } else {
        responderJSON(array(
            'success' => false,
            'mensaje' => 'Error: ' . $conn->error
        ));
    }
} else {
    // Limpieza automática de sesiones antiguas (+24 horas)
    $fechaLimite = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    $query = "DELETE FROM z_convocatorias_temporal WHERE fecha_carga < '$fechaLimite'";
    
    if ($conn->query($query)) {
        $eliminados = $conn->affected_rows;
        
        responderJSON(array(
            'success' => true,
            'mensaje' => 'Limpieza automática completada',
            'eliminados' => $eliminados
        ));
    } else {
        responderJSON(array(
            'success' => false,
            'mensaje' => 'Error: ' . $conn->error
        ));
    }
}
?>
