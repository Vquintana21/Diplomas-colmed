<?php
/**
 * log-rechazados.php
 * Módulo para guardar histórico de registros rechazados en cargas
 * 
 * MODULAR: Para desactivar, cambiar en config.php:
 * define('LOG_RECHAZADOS_HABILITADO', false);
 * 
 * Compatible con PHP 5.6+
 */

// Definir constante si no existe (por si config.php no la tiene)
if (!defined('LOG_RECHAZADOS_HABILITADO')) {
    define('LOG_RECHAZADOS_HABILITADO', true);
}

/**
 * Verificar si el log de rechazados está habilitado
 * @return bool
 */
function logRechazadosHabilitado() {
    return defined('LOG_RECHAZADOS_HABILITADO') && LOG_RECHAZADOS_HABILITADO === true;
}

/**
 * Guardar un registro rechazado en el log
 * 
 * @param mysqli $conn Conexión a BD
 * @param string $tipo_carga 'diplomas' o 'convocatorias'
 * @param string $session_id ID de sesión de la carga
 * @param string $codigo Código del registro rechazado
 * @param array $datos_registro Array con todos los campos del registro
 * @param string $mensaje_error Motivo del rechazo
 * @param int|null $usuario_id ID del usuario que realizó la carga
 * @param int|null $convocatoria_id ID de convocatoria (solo para diplomas)
 * @return bool True si se guardó, False si no (o si está deshabilitado)
 */
function guardarLogRechazado($conn, $tipo_carga, $session_id, $codigo, $datos_registro, $mensaje_error, $usuario_id = null, $convocatoria_id = null) {
    // Si está deshabilitado, retornar sin hacer nada
    if (!logRechazadosHabilitado()) {
        return false;
    }
    
    // Validar tipo de carga
    $tipos_validos = array('diplomas', 'convocatorias');
    if (!in_array($tipo_carga, $tipos_validos)) {
        return false;
    }
    
    // Preparar datos
    $tipo_carga_esc = $conn->real_escape_string($tipo_carga);
    $session_id_esc = $conn->real_escape_string($session_id);
    $codigo_esc = $conn->real_escape_string($codigo);
    $datos_json = $conn->real_escape_string(json_encode($datos_registro, JSON_UNESCAPED_UNICODE));
    $mensaje_esc = $conn->real_escape_string($mensaje_error);
    $usuario_sql = $usuario_id ? intval($usuario_id) : 'NULL';
    $convocatoria_sql = $convocatoria_id ? intval($convocatoria_id) : 'NULL';
    $fecha = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO z_log_rechazados 
              (tipo_carga, session_id, convocatoria_id, codigo, datos_registro, mensaje_error, usuario_id, fecha_registro) 
              VALUES 
              ('$tipo_carga_esc', '$session_id_esc', $convocatoria_sql, '$codigo_esc', '$datos_json', '$mensaje_esc', $usuario_sql, '$fecha')";
    
    return $conn->query($query) ? true : false;
}

/**
 * Guardar múltiples registros rechazados de una vez
 * Más eficiente que llamar guardarLogRechazado() múltiples veces
 * 
 * @param mysqli $conn Conexión a BD
 * @param string $tipo_carga 'diplomas' o 'convocatorias'
 * @param string $session_id ID de sesión de la carga
 * @param array $registros_rechazados Array de registros rechazados
 *              Cada elemento debe tener: codigo, datos (array), mensaje_error
 * @param int|null $usuario_id ID del usuario que realizó la carga
 * @param int|null $convocatoria_id ID de convocatoria (solo para diplomas)
 * @return int Cantidad de registros guardados (0 si está deshabilitado)
 */
function guardarLogRechazadosMultiple($conn, $tipo_carga, $session_id, $registros_rechazados, $usuario_id = null, $convocatoria_id = null) {
    // Si está deshabilitado, retornar sin hacer nada
    if (!logRechazadosHabilitado()) {
        return 0;
    }
    
    if (empty($registros_rechazados)) {
        return 0;
    }
    
    // Validar tipo de carga
    $tipos_validos = array('diplomas', 'convocatorias');
    if (!in_array($tipo_carga, $tipos_validos)) {
        return 0;
    }
    
    $tipo_carga_esc = $conn->real_escape_string($tipo_carga);
    $session_id_esc = $conn->real_escape_string($session_id);
    $usuario_sql = $usuario_id ? intval($usuario_id) : 'NULL';
    $convocatoria_sql = $convocatoria_id ? intval($convocatoria_id) : 'NULL';
    $fecha = date('Y-m-d H:i:s');
    
    $valores = array();
    
    foreach ($registros_rechazados as $registro) {
        $codigo_esc = $conn->real_escape_string(isset($registro['codigo']) ? $registro['codigo'] : '');
        $datos_json = $conn->real_escape_string(json_encode(isset($registro['datos']) ? $registro['datos'] : $registro, JSON_UNESCAPED_UNICODE));
        $mensaje_esc = $conn->real_escape_string(isset($registro['mensaje_error']) ? $registro['mensaje_error'] : '');
        
        $valores[] = "('$tipo_carga_esc', '$session_id_esc', $convocatoria_sql, '$codigo_esc', '$datos_json', '$mensaje_esc', $usuario_sql, '$fecha')";
    }
    
    if (empty($valores)) {
        return 0;
    }
    
    $query = "INSERT INTO z_log_rechazados 
              (tipo_carga, session_id, convocatoria_id, codigo, datos_registro, mensaje_error, usuario_id, fecha_registro) 
              VALUES " . implode(', ', $valores);
    
    if ($conn->query($query)) {
        return count($valores);
    }
    
    return 0;
}

/**
 * Obtener estadísticas de rechazos por tipo y período
 * Útil si el cliente quiere reportes
 * 
 * @param mysqli $conn Conexión a BD
 * @param string $tipo_carga 'diplomas', 'convocatorias' o 'todos'
 * @param string $fecha_desde Fecha inicio (Y-m-d)
 * @param string $fecha_hasta Fecha fin (Y-m-d)
 * @return array Estadísticas
 */
function obtenerEstadisticasRechazos($conn, $tipo_carga = 'todos', $fecha_desde = null, $fecha_hasta = null) {
    if (!logRechazadosHabilitado()) {
        return array('habilitado' => false, 'mensaje' => 'Log de rechazados deshabilitado');
    }
    
    $where = array();
    
    if ($tipo_carga !== 'todos') {
        $tipo_esc = $conn->real_escape_string($tipo_carga);
        $where[] = "tipo_carga = '$tipo_esc'";
    }
    
    if ($fecha_desde) {
        $fecha_desde_esc = $conn->real_escape_string($fecha_desde);
        $where[] = "DATE(fecha_registro) >= '$fecha_desde_esc'";
    }
    
    if ($fecha_hasta) {
        $fecha_hasta_esc = $conn->real_escape_string($fecha_hasta);
        $where[] = "DATE(fecha_registro) <= '$fecha_hasta_esc'";
    }
    
    $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Total de rechazos
    $query = "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT session_id) as sesiones,
                tipo_carga
              FROM z_log_rechazados 
              $where_sql
              GROUP BY tipo_carga";
    
    $result = $conn->query($query);
    $stats = array(
        'habilitado' => true,
        'por_tipo' => array(),
        'total' => 0,
        'sesiones' => 0
    );
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats['por_tipo'][$row['tipo_carga']] = array(
                'total' => (int)$row['total'],
                'sesiones' => (int)$row['sesiones']
            );
            $stats['total'] += (int)$row['total'];
            $stats['sesiones'] += (int)$row['sesiones'];
        }
    }
    
    return $stats;
}

/**
 * Limpiar logs antiguos (mantenimiento)
 * 
 * @param mysqli $conn Conexión a BD
 * @param int $dias_antiguedad Eliminar logs más antiguos que X días
 * @return int Cantidad de registros eliminados
 */
function limpiarLogsAntiguos($conn, $dias_antiguedad = 90) {
    if (!logRechazadosHabilitado()) {
        return 0;
    }
    
    $dias = intval($dias_antiguedad);
    $fecha_limite = date('Y-m-d H:i:s', strtotime("-$dias days"));
    
    $query = "DELETE FROM z_log_rechazados WHERE fecha_registro < '$fecha_limite'";
    
    if ($conn->query($query)) {
        return $conn->affected_rows;
    }
    
    return 0;
}
?>
