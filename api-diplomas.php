<?php
/**
 * api-diplomas.php
 * API para operaciones CRUD de diplomas
 * Compatible con DataTables y AJAX
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
 * Responder en formato JSON
 */
function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener acción
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    
    // ============================================
    // LISTAR DIPLOMAS (para DataTables)
    // ============================================
    case 'listar':
        $query = "SELECT id, codigo, autores, tema FROM z_diplomas ORDER BY id DESC";
        $result = $conn->query($query);
        
        if (!$result) {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'Error en la consulta: ' . $conn->error,
                'data' => array(),
                'total' => 0
            ));
        }
        
        $diplomas = array();
        while ($row = $result->fetch_assoc()) {
            $diplomas[] = $row;
        }
        
        // Obtener total
        $queryTotal = "SELECT COUNT(*) as total FROM z_diplomas";
        $resultTotal = $conn->query($queryTotal);
        $total = $resultTotal ? $resultTotal->fetch_assoc()['total'] : count($diplomas);
        
        responderJSON(array(
            'success' => true,
            'data' => $diplomas,
            'total' => (int)$total
        ));
        break;
    
    // ============================================
    // OBTENER UN DIPLOMA POR ID
    // ============================================
    case 'obtener':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id <= 0) {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'ID no válido'
            ));
        }
        
        $query = "SELECT id, codigo, autores, tema FROM z_diplomas WHERE id = $id";
        $result = $conn->query($query);
        
        if (!$result || $result->num_rows === 0) {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'Diploma no encontrado'
            ));
        }
        
        $diploma = $result->fetch_assoc();
        
        responderJSON(array(
            'success' => true,
            'data' => $diploma
        ));
        break;
    
    // ============================================
    // ELIMINAR DIPLOMA
    // ============================================
    case 'eliminar':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'ID no válido'
            ));
        }
        
        // Verificar que existe
        $queryCheck = "SELECT codigo FROM z_diplomas WHERE id = $id";
        $resultCheck = $conn->query($queryCheck);
        
        if (!$resultCheck || $resultCheck->num_rows === 0) {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'El diploma no existe o ya fue eliminado'
            ));
        }
        
        $diploma = $resultCheck->fetch_assoc();
        
        // Eliminar
        $queryDelete = "DELETE FROM z_diplomas WHERE id = $id";
        
        if ($conn->query($queryDelete)) {
            responderJSON(array(
                'success' => true,
                'mensaje' => 'Diploma eliminado correctamente',
                'codigo' => $diploma['codigo']
            ));
        } else {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'Error al eliminar: ' . $conn->error
            ));
        }
        break;
    
    // ============================================
    // BUSCAR DIPLOMAS
    // ============================================
    case 'buscar':
        $termino = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (strlen($termino) < 2) {
            responderJSON(array(
                'success' => false,
                'mensaje' => 'El término de búsqueda debe tener al menos 2 caracteres',
                'data' => array()
            ));
        }
        
        $termino_escaped = $conn->real_escape_string($termino);
        
        $query = "SELECT id, codigo, autores, tema FROM z_diplomas 
                  WHERE codigo LIKE '%$termino_escaped%' 
                     OR autores LIKE '%$termino_escaped%' 
                     OR tema LIKE '%$termino_escaped%'
                  ORDER BY id DESC
                  LIMIT 100";
        
        $result = $conn->query($query);
        
        $diplomas = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $diplomas[] = $row;
            }
        }
        
        responderJSON(array(
            'success' => true,
            'data' => $diplomas,
            'total' => count($diplomas)
        ));
        break;
    
    // ============================================
    // ESTADÍSTICAS
    // ============================================
    case 'estadisticas':
        $stats = array();
        
        // Total de diplomas
        $query = "SELECT COUNT(*) as total FROM z_diplomas";
        $result = $conn->query($query);
        $stats['total_diplomas'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
        
        // Total de validaciones
        $query = "SELECT COUNT(*) as total FROM z_registro";
        $result = $conn->query($query);
        $stats['total_validaciones'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
        
        // Validaciones hoy
        $hoy = date('Y-m-d');
        $query = "SELECT COUNT(*) as total FROM z_registro WHERE DATE(fecha) = '$hoy'";
        $result = $conn->query($query);
        $stats['validaciones_hoy'] = $result ? (int)$result->fetch_assoc()['total'] : 0;
        
        // Diplomas más validados (top 5)
        $query = "SELECT r.codigo, COUNT(*) as cantidad, d.autores, d.tema
                  FROM z_registro r
                  LEFT JOIN diplomas d ON UPPER(r.codigo) = UPPER(d.codigo)
                  GROUP BY r.codigo
                  ORDER BY cantidad DESC
                  LIMIT 5";
        $result = $conn->query($query);
        $stats['mas_validados'] = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['mas_validados'][] = $row;
            }
        }
        
        responderJSON(array(
            'success' => true,
            'data' => $stats
        ));
        break;
    
    // ============================================
    // ACCIÓN NO VÁLIDA
    // ============================================
    default:
        responderJSON(array(
            'success' => false,
            'mensaje' => 'Acción no válida'
        ));
}

$conn->close();
?>
