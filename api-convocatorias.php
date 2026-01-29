<?php
/**
 * API para operaciones CRUD de convocatorias
 */
header('Content-Type: application/json; charset=utf-8');

require_once 'auth.php';
requerirAutenticacion();

// Solo admin
if ($_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(array('success' => false, 'mensaje' => 'No autorizado'));
    exit;
}

require_once 'config.php';

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

switch ($accion) {
    case 'toggle':
        // Activar/desactivar convocatoria
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 0;
        
        if ($id <= 0) {
            echo json_encode(array('success' => false, 'mensaje' => 'ID inválido'));
            exit;
        }
        
        $query = "UPDATE z_convocatorias SET activo = $activo WHERE id = $id";
        if ($conn->query($query)) {
            echo json_encode(array('success' => true, 'mensaje' => 'Estado actualizado'));
        } else {
            echo json_encode(array('success' => false, 'mensaje' => 'Error: ' . $conn->error));
        }
        break;
        
    case 'eliminar':
        // Eliminar convocatoria (solo si no tiene diplomas)
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            echo json_encode(array('success' => false, 'mensaje' => 'ID inválido'));
            exit;
        }
        
        // Obtener codigo_base
        $query = "SELECT codigo_base FROM z_convocatorias WHERE id = $id";
        $result = $conn->query($query);
        
        if (!$result || $result->num_rows == 0) {
            echo json_encode(array('success' => false, 'mensaje' => 'Convocatoria no encontrada'));
            exit;
        }
        
        $conv = $result->fetch_assoc();
        $codigo_base = $conn->real_escape_string($conv['codigo_base']);
        
        // Verificar que no tenga diplomas asociados
        $check_query = "SELECT COUNT(*) as total FROM z_diplomas WHERE codigo LIKE '$codigo_base%'";
        $check_result = $conn->query($check_query);
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['total'] > 0) {
            echo json_encode(array(
                'success' => false, 
                'mensaje' => 'No se puede eliminar: tiene ' . $check_row['total'] . ' diplomas asociados'
            ));
            exit;
        }
        
        // Eliminar
        $delete_query = "DELETE FROM z_convocatorias WHERE id = $id";
        if ($conn->query($delete_query)) {
            echo json_encode(array('success' => true, 'mensaje' => 'Convocatoria eliminada'));
        } else {
            echo json_encode(array('success' => false, 'mensaje' => 'Error: ' . $conn->error));
        }
        break;
        
    case 'listar':
        // Listar convocatorias activas (para dropdown)
        $solo_activas = isset($_POST['solo_activas']) ? (int)$_POST['solo_activas'] : 1;
        
        $query = "SELECT id, codigo_base, nombre, tipo_documento FROM z_convocatorias";
        if ($solo_activas) {
            $query .= " WHERE activo = 1";
        }
        $query .= " ORDER BY fecha_creacion DESC";
        
        $result = $conn->query($query);
        $convocatorias = array();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $convocatorias[] = $row;
            }
        }
        
        echo json_encode(array('success' => true, 'convocatorias' => $convocatorias));
        break;
        
    case 'obtener':
        // Obtener una convocatoria por ID
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            echo json_encode(array('success' => false, 'mensaje' => 'ID inválido'));
            exit;
        }
        
        $query = "SELECT * FROM z_convocatorias WHERE id = $id";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            echo json_encode(array('success' => true, 'convocatoria' => $result->fetch_assoc()));
        } else {
            echo json_encode(array('success' => false, 'mensaje' => 'Convocatoria no encontrada'));
        }
        break;
        
    default:
        echo json_encode(array('success' => false, 'mensaje' => 'Acción no válida'));
}

$conn->close();
?>
