<?php
/**
 * confirmar-carga.php
 * Confirma la carga y transfiere datos de tabla temporal a tabla oficial
 * SOLO carga parcial (registros válidos)
 * Incluye log de rechazados (modular)
 * 
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
require_once 'log-rechazados.php';

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

$session_id = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';

if (empty($session_id)) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Sesión de carga no válida. Por favor, recargue la página e intente nuevamente.'
    ));
}

$session_id = $conn->real_escape_string($session_id);
$usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

// Verificar que existen registros en temporal
$queryVerificar = "SELECT 
                    COUNT(*) as total, 
                    SUM(CASE WHEN estado = 'valido' THEN 1 ELSE 0 END) as validos,
                    SUM(CASE WHEN estado = 'codigo_invalido' THEN 1 ELSE 0 END) as codigos_invalidos,
                    SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) as errores,
                    MAX(convocatoria_id) as convocatoria_id
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

// Solo carga parcial: únicamente registros válidos
$totalAInsertar = $resumen['validos'];
$convocatoria_id = $resumen['convocatoria_id'];

// Verificar que hay registros para insertar
if ($totalAInsertar == 0) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'No hay registros válidos para cargar.'
    ));
}

// ============================================
// OBTENER REGISTROS NO CARGADOS (para log y reporte)
// ============================================
$registrosNoCargados = array();
$totalRechazados = $resumen['codigos_invalidos'] + $resumen['errores'];

if ($totalRechazados > 0) {
    $queryNoCargados = "SELECT codigo, autores, tema, mensaje_error 
                        FROM z_diplomas_temporal 
                        WHERE session_id = '$session_id' AND estado != 'valido'";
    
    $resultNoCargados = $conn->query($queryNoCargados);
    
    if ($resultNoCargados) {
        while ($row = $resultNoCargados->fetch_assoc()) {
            $registrosNoCargados[] = array(
                'codigo' => $row['codigo'],
                'autores' => $row['autores'],
                'tema' => $row['tema'],
                'mensaje_error' => $row['mensaje_error'],
                'datos' => $row
            );
        }
    }
    
    // ============================================
    // GUARDAR LOG DE RECHAZADOS EN BD (MODULAR)
    // ============================================
    if (!empty($registrosNoCargados)) {
        guardarLogRechazadosMultiple(
            $conn,
            'diplomas',
            $session_id,
            $registrosNoCargados,
            $usuario_id,
            $convocatoria_id
        );
    }
}

// Verificar una vez más que no existan códigos duplicados
$queryDuplicados = "SELECT dt.codigo 
                    FROM z_diplomas_temporal dt
                    INNER JOIN z_diplomas d ON UPPER(dt.codigo) = UPPER(d.codigo)
                    WHERE dt.session_id = '$session_id' AND dt.estado = 'valido'";

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
// PROCESO DE INSERCIÓN CON TRANSACCIÓN
// ============================================

$conn->autocommit(false);

try {
    // Insertar solo registros válidos
    $queryInsert = "INSERT INTO z_diplomas (codigo, autores, tema)
                    SELECT codigo, autores, tema 
                    FROM z_diplomas_temporal 
                    WHERE session_id = '$session_id' AND estado = 'valido'";
    
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
    
    // Registrar evento de seguridad
    registrarEventoSeguridad(
        'carga_masiva_diplomas', 
        "Cargados $insertados diplomas. Rechazados: " . count($registrosNoCargados) . ". Convocatoria ID: $convocatoria_id", 
        $usuario_id
    );
    
    // Preparar respuesta
    $respuesta = array(
        'success' => true,
        'mensaje' => 'Carga completada exitosamente',
        'insertados' => $insertados,
        'tipo_carga' => 'parcial'
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
