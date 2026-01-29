<?php
/**
 * confirmar-carga-convocatorias.php
 * Confirma la carga de convocatorias desde tabla temporal a tabla oficial
 * Incluye log de rechazados (modular)
 * 
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
require_once 'log-rechazados.php';

/**
 * Responder JSON
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
    responderJSON(array('success' => false, 'mensaje' => 'Sesión de carga no válida.'));
}

$session_id = $conn->real_escape_string($session_id);
$usuario_id = $_SESSION['usuario_id'];

// Verificar que existen registros
$queryVerificar = "SELECT 
                    COUNT(*) as total, 
                    SUM(CASE WHEN estado = 'valido' THEN 1 ELSE 0 END) as validos,
                    SUM(CASE WHEN estado = 'error' THEN 1 ELSE 0 END) as errores
                   FROM z_convocatorias_temporal 
                   WHERE session_id = '$session_id'";

$result = $conn->query($queryVerificar);

if (!$result) {
    responderJSON(array('success' => false, 'mensaje' => 'Error al verificar datos: ' . $conn->error));
}

$resumen = $result->fetch_assoc();

if ($resumen['total'] == 0) {
    responderJSON(array('success' => false, 'mensaje' => 'No se encontraron datos. La sesión puede haber expirado.'));
}

if ($resumen['validos'] == 0) {
    responderJSON(array('success' => false, 'mensaje' => 'No hay registros válidos para cargar.'));
}

// ============================================
// OBTENER REGISTROS RECHAZADOS (para log y reporte)
// ============================================
$registrosRechazados = array();

if ($resumen['errores'] > 0) {
    $queryRechazados = "SELECT codigo_base, nombre, tipo_documento, info_institucional, 
                               etiqueta_persona, etiqueta_tema, mensaje_error 
                        FROM z_convocatorias_temporal 
                        WHERE session_id = '$session_id' AND estado = 'error'";
    
    $resultRechazados = $conn->query($queryRechazados);
    
    if ($resultRechazados) {
        while ($row = $resultRechazados->fetch_assoc()) {
            $registrosRechazados[] = array(
                'codigo' => $row['codigo_base'],
                'codigo_base' => $row['codigo_base'],
                'nombre' => $row['nombre'],
                'tipo_documento' => $row['tipo_documento'],
                'info_institucional' => $row['info_institucional'],
                'etiqueta_persona' => $row['etiqueta_persona'],
                'etiqueta_tema' => $row['etiqueta_tema'],
                'mensaje_error' => $row['mensaje_error'],
                'datos' => $row
            );
        }
    }
    
    // ============================================
    // GUARDAR LOG DE RECHAZADOS EN BD (MODULAR)
    // ============================================
    if (!empty($registrosRechazados)) {
        guardarLogRechazadosMultiple(
            $conn,
            'convocatorias',
            $session_id,
            $registrosRechazados,
            $usuario_id,
            null // No aplica convocatoria_id para carga de convocatorias
        );
    }
}

// ============================================
// VERIFICAR DUPLICADOS DE ÚLTIMO MOMENTO
// ============================================
$queryDuplicados = "SELECT ct.codigo_base 
                    FROM z_convocatorias_temporal ct
                    INNER JOIN z_convocatorias c ON UPPER(ct.codigo_base) = UPPER(c.codigo_base)
                    WHERE ct.session_id = '$session_id' AND ct.estado = 'valido'";

$resultDup = $conn->query($queryDuplicados);

if ($resultDup && $resultDup->num_rows > 0) {
    $codigosDuplicados = array();
    while ($row = $resultDup->fetch_assoc()) {
        $codigosDuplicados[] = $row['codigo_base'];
    }
    
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Se detectaron códigos que ya existen: ' . implode(', ', array_slice($codigosDuplicados, 0, 5)) .
                     (count($codigosDuplicados) > 5 ? ' y más...' : '') . ' Alguien pudo haberlos insertado. Recargue y vuelva a intentar.'
    ));
}

// ============================================
// PROCESO DE INSERCIÓN CON TRANSACCIÓN
// ============================================

$conn->autocommit(false);

try {
    // Insertar solo registros válidos
    $queryInsert = "INSERT INTO z_convocatorias (codigo_base, nombre, tipo_documento, info_institucional, etiqueta_persona, etiqueta_tema, activo, fecha_creacion)
                    SELECT codigo_base, nombre, tipo_documento, info_institucional, etiqueta_persona, etiqueta_tema, 1, NOW()
                    FROM z_convocatorias_temporal 
                    WHERE session_id = '$session_id' AND estado = 'valido'";
    
    if (!$conn->query($queryInsert)) {
        throw new Exception('Error al insertar convocatorias: ' . $conn->error);
    }
    
    $insertados = $conn->affected_rows;
    
    if ($insertados == 0) {
        throw new Exception('No se insertaron registros.');
    }
    
    // Limpiar tabla temporal
    $queryLimpiar = "DELETE FROM z_convocatorias_temporal WHERE session_id = '$session_id'";
    
    if (!$conn->query($queryLimpiar)) {
        throw new Exception('Error al limpiar temporal: ' . $conn->error);
    }
    
    // Confirmar transacción
    $conn->commit();
    $conn->autocommit(true);
    
    // Registrar evento de seguridad
    registrarEventoSeguridad(
        'carga_masiva_convocatorias', 
        "Cargadas $insertados convocatorias. Rechazadas: " . count($registrosRechazados), 
        $usuario_id
    );
    
    // Preparar respuesta
    $respuesta = array(
        'success' => true,
        'mensaje' => 'Carga completada exitosamente',
        'insertados' => $insertados,
        'total_rechazados' => count($registrosRechazados)
    );
    
    // Incluir rechazados para descarga
    if (!empty($registrosRechazados)) {
        $respuesta['rechazados'] = $registrosRechazados;
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
