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
    <nav class="navbar navbar-expand-lg navbar-dark navbar-institucional">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/logo.svg" alt="Logo" height="40" class="me-2">
                <span>Sistema de Diplomas</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-cloud-upload me-1"></i> Cargar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="listado.php">
                            <i class="bi bi-list-ul me-1"></i> Listado
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="convocatorias.php">
                            <i class="bi bi-folder me-1"></i> Convocatorias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-usuarios.php">
                            <i class="bi bi-people me-1"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="validador.php" target="_blank">
                            <i class="bi bi-patch-check me-1"></i> Validador
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($usuario['nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="cambiar-password.php"><i class="bi bi-key me-2"></i>Cambiar Contraseña</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                                                   onclick="return confirm('¿Está seguro de eliminar este usuario permitido?')" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
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
    </script>
    
</body>
</html>
