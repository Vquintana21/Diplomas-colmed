<?php
/**
 * admin-usuarios.php
 * Administración de usuarios permitidos (solo admin)
 */

require_once 'auth.php';
requerirAutenticacion();
$usuario = obtenerUsuarioActual();

// Solo admin puede acceder
if ($usuario['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$mensaje = '';
$tipo_mensaje = '';

/**
 * Formatear RUT
 */
function formatearRut($rut) {
    $rut = str_replace(array(' ', '.'), '', trim($rut));
    return strtoupper($rut);
}

/**
 * Validar formato de RUT chileno
 */
function validarFormatoRut($rut) {
    return preg_match('/^[0-9]{7,8}-[0-9Kk]$/', $rut);
}

// ============================================
// PROCESAR ACCIONES
// ============================================

// Agregar usuario permitido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    
    // Verificar CSRF
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verificarTokenCSRF($csrf_token)) {
        $mensaje = 'Token de seguridad inválido. Recargue la página.';
        $tipo_mensaje = 'danger';
    } else {
        $rut = formatearRut($_POST['rut']);
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $rol = isset($_POST['rol']) && $_POST['rol'] === 'admin' ? 'admin' : 'usuario';
        
        if (empty($rut) || empty($nombre)) {
            $mensaje = 'RUT y Nombre son obligatorios.';
            $tipo_mensaje = 'danger';
        } elseif (!validarFormatoRut($rut)) {
            $mensaje = 'El formato del RUT no es válido.';
            $tipo_mensaje = 'danger';
        } else {
            $rut_escaped = $conn->real_escape_string($rut);
            
            // Verificar si ya existe
            $query = "SELECT id FROM z_usuarios_permitidos WHERE rut = '$rut_escaped'";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $mensaje = 'Este RUT ya está en la lista de usuarios permitidos.';
                $tipo_mensaje = 'warning';
            } else {
                $nombre_escaped = $conn->real_escape_string($nombre);
                $email_escaped = $conn->real_escape_string($email);
                $rol_escaped = $conn->real_escape_string($rol);
                
                $query = "INSERT INTO z_usuarios_permitidos (rut, nombre, email, rol, activo, fecha_agregado) 
                          VALUES ('$rut_escaped', '$nombre_escaped', '$email_escaped', '$rol_escaped', 1, NOW())";
                
                if ($conn->query($query)) {
                    $mensaje = 'Usuario permitido agregado correctamente.';
                    $tipo_mensaje = 'success';
                    
                    // Registrar evento
                    registrarEventoSeguridad('usuario_permitido_agregado', "RUT: $rut, Rol: $rol", $usuario['id']);
                } else {
                    $mensaje = 'Error al agregar: ' . $conn->error;
                    $tipo_mensaje = 'danger';
                }
            }
        }
    }
}

// Eliminar usuario permitido
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    // Verificar que no esté registrado
    $query = "SELECT up.rut, u.id as usuario_id 
              FROM z_usuarios_permitidos up 
              LEFT JOIN z_usuarios u ON up.rut = u.rut 
              WHERE up.id = $id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['usuario_id']) {
            $mensaje = 'No se puede eliminar porque este RUT ya tiene una cuenta registrada.';
            $tipo_mensaje = 'warning';
        } else {
            if ($conn->query("DELETE FROM z_usuarios_permitidos WHERE id = $id")) {
                $mensaje = 'Usuario permitido eliminado.';
                $tipo_mensaje = 'success';
            }
        }
    }
}

// Cambiar estado activo/inactivo
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE z_usuarios_permitidos SET activo = NOT activo WHERE id = $id");
    $mensaje = 'Estado actualizado.';
    $tipo_mensaje = 'info';
}

// Eliminar usuario registrado (de z_usuarios)
if (isset($_GET['eliminar_registrado'])) {
    $id = intval($_GET['eliminar_registrado']);

    // Obtener datos del usuario permitido
    $query = "SELECT up.id, up.rut, up.nombre, u.id as usuario_id
              FROM z_usuarios_permitidos up
              LEFT JOIN z_usuarios u ON up.rut = u.rut
              WHERE up.id = $id";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // No permitir eliminarse a sí mismo
        if ($row['usuario_id'] == $usuario['id']) {
            $mensaje = 'No puede eliminarse a sí mismo.';
            $tipo_mensaje = 'danger';
        } else {
            $rut_usuario = $row['rut'];
            $nombre_usuario = $row['nombre'];

            // Eliminar de z_usuarios si está registrado
            if ($row['usuario_id']) {
                $conn->query("DELETE FROM z_usuarios WHERE id = " . intval($row['usuario_id']));
            }

            // Eliminar de z_usuarios_permitidos
            if ($conn->query("DELETE FROM z_usuarios_permitidos WHERE id = $id")) {
                $mensaje = "Usuario \"$nombre_usuario\" ($rut_usuario) eliminado completamente del sistema.";
                $tipo_mensaje = 'success';

                // Registrar evento de seguridad
                registrarEventoSeguridad('usuario_eliminado', "RUT: $rut_usuario, Nombre: $nombre_usuario", $usuario['id']);
            } else {
                $mensaje = 'Error al eliminar el usuario.';
                $tipo_mensaje = 'danger';
            }
        }
    }
}

// Obtener lista de usuarios permitidos
$query = "SELECT up.*, 
          (SELECT COUNT(*) FROM z_usuarios u WHERE u.rut = up.rut) as registrado
          FROM z_usuarios_permitidos up 
          ORDER BY up.fecha_agregado DESC";
$result = $conn->query($query);
$usuarios_permitidos = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios_permitidos[] = $row;
    }
}

// Estadísticas
$total_permitidos = count($usuarios_permitidos);
$total_registrados = 0;
foreach ($usuarios_permitidos as $up) {
    if ($up['registrado'] > 0) $total_registrados++;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios Permitidos</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .stats-card {
            border-radius: var(--radio-medio);
            padding: 20px;
            color: white;
            text-align: center;
        }
        .stats-card.total { background: var(--gradiente-primario); }
        .stats-card.registrados { background: var(--gradiente-exito); }
        .stats-card.pendientes { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stats-card .numero { font-size: 2rem; font-weight: 700; }
    </style>
</head>
<body data-pagina="admin-usuarios">

    <!-- Navbar -->
    <?php include 'nav.php'; ?>

    <div class="container-fluid py-4">
        
        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
            <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'danger' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card total">
                    <div class="numero"><?php echo $total_permitidos; ?></div>
                    <div>Total Autorizados</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card registrados">
                    <div class="numero"><?php echo $total_registrados; ?></div>
                    <div>Ya Registrados</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card pendientes">
                    <div class="numero"><?php echo $total_permitidos - $total_registrados; ?></div>
                    <div>Pendientes de Registro</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Formulario agregar -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header card-header-institucional py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-person-plus me-2"></i>
                            Agregar Usuario Permitido
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="admin-usuarios.php">
                            <?php echo campoCSRF(); ?>
                            <div class="mb-3">
                                <label for="rut" class="form-label">RUT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="rut" name="rut" 
                                       placeholder="12345678-9" required maxlength="12">
                                <div class="form-text">Sin puntos, con guión</div>
                            </div>
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       placeholder="Juan Pérez González" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (opcional)</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="correo@ejemplo.com">
                            </div>
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="usuario" selected>Usuario</option>
                                    <option value="admin">Administrador</option>
                                </select>
                                <div class="form-text">El administrador puede gestionar usuarios permitidos</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="agregar" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>
                                    Agregar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Lista de usuarios permitidos -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header card-header-institucional py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            Lista de Usuarios Permitidos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaPermitidos" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>RUT</th>
                                        <th>Nombre</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Registrado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios_permitidos as $up): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary badge-rut"><?php echo htmlspecialchars($up['rut']); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($up['nombre']); ?>
                                            <?php if ($up['email']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($up['email']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($up['rol']) && $up['rol'] === 'admin'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-shield-check"></i> Admin</span>
                                            <?php else: ?>
                                            <span class="badge bg-info"><i class="bi bi-person"></i> Usuario</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($up['activo']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i> Activo</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x"></i> Bloqueado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($up['registrado'] > 0): ?>
                                            <span class="badge bg-primary"><i class="bi bi-person-check"></i> Sí</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass"></i> Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?toggle=<?php echo $up['id']; ?>" class="btn btn-<?php echo $up['activo'] ? 'primary' : 'danger'; ?> btn-action" title="<?php echo $up['activo'] ? 'Bloquear' : 'Activar'; ?>">
                                                    <i class="bi bi-toggle-<?php echo $up['activo'] ? 'on' : 'off'; ?>"></i>
                                                </a>
                                                <?php if ($up['registrado'] == 0): ?>
                                                <a href="?eliminar=<?php echo $up['id']; ?>" class="btn btn-outline-danger btn-action"
                                                   onclick="return confirm('¿Está seguro de eliminar este usuario permitido?')" title="Eliminar permiso">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php else: ?>
                                                <button type="button" class="btn btn-danger btn-action"
                                                        onclick="confirmarEliminarRegistrado(<?php echo $up['id']; ?>, '<?php echo htmlspecialchars($up['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($up['rut'], ENT_QUOTES); ?>')"
                                                        title="Eliminar usuario registrado">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Modal de confirmación para eliminar usuario registrado -->
    <div class="modal fade" id="modalEliminarRegistrado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Eliminar Usuario Registrado
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <strong>¡Atención!</strong> Esta acción eliminará completamente al usuario del sistema.
                    </div>
                    <p>¿Está seguro de eliminar al usuario:</p>
                    <ul class="list-unstyled ms-3">
                        <li><strong>Nombre:</strong> <span id="elimNombre"></span></li>
                        <li><strong>RUT:</strong> <span id="elimRut"></span></li>
                    </ul>
                    <p class="text-danger mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Se eliminará tanto su cuenta como su permiso de acceso. Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancelar
                    </button>
                    <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Eliminar Usuario
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#tablaPermitidos').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 10
        });
        
        // Formatear RUT mientras se escribe
        document.getElementById('rut').addEventListener('input', function(e) {
            var valor = e.target.value.toUpperCase();
            valor = valor.replace(/[^0-9K]/g, '');
            if (valor.length > 1) {
                valor = valor.slice(0, -1) + '-' + valor.slice(-1);
            }
            e.target.value = valor;
        });
    });

    // Modal para confirmar eliminación de usuario registrado
    var modalEliminar = null;

    function confirmarEliminarRegistrado(id, nombre, rut) {
        document.getElementById('elimNombre').textContent = nombre;
        document.getElementById('elimRut').textContent = rut;
        document.getElementById('btnConfirmarEliminar').href = '?eliminar_registrado=' + id;

        if (!modalEliminar) {
            modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarRegistrado'));
        }
        modalEliminar.show();
    }
    </script>
    
</body>
</html>
