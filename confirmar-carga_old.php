<?php
/**
 * confirmar-carga.php
 * Confirma la carga y transfiere datos de tabla temporal a tabla oficial
 * Soporta carga parcial (solo válidos) o total (incluye códigos inválidos)
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

require_once 'config.php';

/**
 * Responder en formato JSON y terminar
 */
function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// VALIDACIONES PREVIAS
// ============================================

// Verificar session_id
$session_id = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';

if (empty($session_id)) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Sesión de carga no válida. Por favor, recargue la página e intente nuevamente.'
    ));
}

$session_id = $conn->real_escape_string($session_id);

// Tipo de carga: 'total' = todos, 'parcial' = solo válidos (default)
$tipo_carga = isset($_POST['tipo_carga']) ? trim($_POST['tipo_carga']) : 'parcial';

// Verificar que existen registros en temporal
$queryVerificar = "SELECT 
                    COUNT(*) as total, 
                    SUM(CASE WHEN estado = 'valido' THEN 1 ELSE 0 END) as validos,
                    SUM(CASE WHEN estado = 'codigo_invalido' THEN 1 ELSE 0 END) as codigos_invalidos,
                    SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) as errores
                   FROM z_diplomas_temporal 
                   WHERE session_id = '$session_id'";

$result = $conn->query($queryVerificar);

if (!$result) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Error al verificar los datos: ' . $conn->error
    ));
}

$resumen = $result->fetch_assoc();

// Sin datos para procesar
if ($resumen['total'] == 0) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'No se encontraron datos para procesar. La sesión puede haber expirado. Por favor, vuelva a cargar el archivo.'
    ));
}

// Determinar estados a insertar según tipo de carga
$estadosAInsertar = array("'valido'");

if ($tipo_carga === 'total') {
    // Carga total: incluir también códigos inválidos
    $estadosAInsertar[] = "'codigo_invalido'";
    $totalAInsertar = $resumen['validos'] + $resumen['codigos_invalidos'];
} else {
    // Carga parcial: solo válidos
    $totalAInsertar = $resumen['validos'];
}

// Verificar que hay registros para insertar
if ($totalAInsertar == 0) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'No hay registros válidos para cargar.'
    ));
}

// Verificar errores críticos (no códigos inválidos, sino errores reales)
if ($resumen['errores'] > 0 && $tipo_carga === 'total') {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Existen ' . $resumen['errores'] . ' registro(s) con errores críticos (código vacío, duplicado, etc.). Corrija el archivo y vuelva a cargar.'
    ));
}

$estadosSQL = implode(',', $estadosAInsertar);

// Verificar una vez más que no existan códigos duplicados
$queryDuplicados = "SELECT dt.codigo 
                    FROM z_diplomas_temporal dt
                    INNER JOIN z_diplomas d ON UPPER(dt.codigo) = UPPER(d.codigo)
                    WHERE dt.session_id = '$session_id' AND dt.estado IN ($estadosSQL)";

$resultDup = $conn->query($queryDuplicados);

if ($resultDup && $resultDup->num_rows > 0) {
    $codigosDuplicados = array();
    while ($row = $resultDup->fetch_assoc()) {
        $codigosDuplicados[] = $row['codigo'];
    }
    
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Se detectaron códigos que ya existen en el sistema: ' . implode(', ', array_slice($codigosDuplicados, 0, 5)) . 
                     (count($codigosDuplicados) > 5 ? ' y ' . (count($codigosDuplicados) - 5) . ' más...' : '') .
                     ' Alguien más pudo haberlos insertado. Por favor, recargue y vuelva a procesar el archivo.'
    ));
}

// ============================================
// OBTENER REGISTROS NO CARGADOS (para reporte)
// ============================================
$registrosNoCargados = array();

if ($tipo_carga === 'parcial' && ($resumen['codigos_invalidos'] > 0 || $resumen['errores'] > 0)) {
    $queryNoCargados = "SELECT codigo, autores, tema, mensaje_error 
                        FROM z_diplomas_temporal 
                        WHERE session_id = '$session_id' AND estado NOT IN ($estadosSQL)";
    
    $resultNoCargados = $conn->query($queryNoCargados);
    
    if ($resultNoCargados) {
        while ($row = $resultNoCargados->fetch_assoc()) {
            $registrosNoCargados[] = $row;
        }
    }
}

// ============================================
// PROCESO DE INSERCIÓN CON TRANSACCIÓN
// ============================================

$conn->autocommit(false);

try {
    // Insertar registros según tipo de carga
    $queryInsert = "INSERT INTO z_diplomas (codigo, autores, tema)
                    SELECT codigo, autores, tema 
                    FROM z_diplomas_temporal 
                    WHERE session_id = '$session_id' AND estado IN ($estadosSQL)";
    
    if (!$conn->query($queryInsert)) {
        throw new Exception('Error al insertar en tabla diplomas: ' . $conn->error);
    }
    
    $insertados = $conn->affected_rows;
    
    if ($insertados == 0) {
        throw new Exception('No se insertaron registros. Verifique los datos e intente nuevamente.');
    }
    
    // Limpiar tabla temporal para esta sesión
    $queryLimpiar = "DELETE FROM z_diplomas_temporal WHERE session_id = '$session_id'";
    
    if (!$conn->query($queryLimpiar)) {
        throw new Exception('Error al limpiar tabla temporal: ' . $conn->error);
    }
    
    // Confirmar transacción
    $conn->commit();
    $conn->autocommit(true);
    
    // Preparar respuesta
    $respuesta = array(
        'success' => true,
        'mensaje' => 'Carga completada exitosamente',
        'insertados' => $insertados,
        'tipo_carga' => $tipo_carga
    );
    
    // Si hay registros no cargados, incluir info
    if (count($registrosNoCargados) > 0) {
        $respuesta['no_cargados'] = count($registrosNoCargados);
        $respuesta['registros_no_cargados'] = $registrosNoCargados;
    }
    
    responderJSON($respuesta);
    
} catch (Exception $e) {
    $conn->rollback();
    $conn->autocommit(true);
    
    responderJSON(array(
        'success' => false,
        'mensaje' => $e->getMessage()
    ));
}
?>
