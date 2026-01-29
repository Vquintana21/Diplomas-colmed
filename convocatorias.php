<?php
/**
 * Gestión de Convocatorias
 * Solo accesible para administradores
 */
require_once 'auth.php';
requerirAutenticacion();

// Verificar que sea admin
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Obtener lista de convocatorias
$query = "SELECT * FROM z_convocatorias ORDER BY fecha_creacion DESC";
$result = $conn->query($query);
$convocatorias = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $convocatorias[] = $row;
    }
}

// Contar diplomas por convocatoria
$diplomas_count = array();
$query_count = "SELECT SUBSTRING(codigo, 1, 10) as prefijo, COUNT(*) as total FROM z_diplomas GROUP BY prefijo";
$result_count = $conn->query($query_count);
if ($result_count) {
    while ($row = $result_count->fetch_assoc()) {
        $diplomas_count[$row['prefijo']] = $row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Convocatorias - Sistema de Diplomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .codigo-base {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1em;
            color: var(--color-primario);
        }
        .badge-activo { background-color: var(--color-exito); }
        .badge-inactivo { background-color: #dc3545; }
        .info-truncate {
            max-height: 60px; overflow: hidden; text-overflow: ellipsis;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        }
        .btn-action { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
</head>
<body data-pagina="convocatorias">
    <nav class="navbar navbar-expand-lg navbar-institucional">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-award"></i> Sistema de Diplomas
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-cloud-upload"></i> Cargar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="convocatorias.php"><i class="bi bi-folder"></i> Convocatorias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="listado.php"><i class="bi bi-list-ul"></i> Listado</a>
                    </li>
                    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin-usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="cambiar-password.php"><i class="bi bi-key"></i> Cambiar Contraseña</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-folder text-primary"></i> Gestión de Convocatorias</h2>
                <p class="text-muted mb-0">Administre los tipos de diplomas y certificados</p>
            </div>
			<a href="convocatoria-form.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Nueva Convocatoria
            </a>
			<a href="carga-convocatorias.php" class="btn btn-success me-2">
				<i class="bi bi-cloud-upload"></i> Carga Masiva
			</a>
            <a href="plantillas/Instructivo_convocatoria.pdf" target="_blank" class="btn btn-warning">
                <i class="bi bi-plus-lg"></i> Instrucciones Generales
            </a>
        </div>

        <!-- Alertas -->
        <div id="alertContainer"></div>

        <?php if (empty($convocatorias)): ?>
        <!-- Estado vacío -->
        <div class="card">
            <div class="card-body empty-state">
                <i class="bi bi-folder-x"></i>
                <h4>No hay convocatorias registradas</h4>
                <p>Cree una nueva convocatoria para comenzar a cargar diplomas.</p>
                <a href="convocatoria-form.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Crear Primera Convocatoria
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Tabla de convocatorias -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código Base</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th class="text-center">Diplomas</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($convocatorias as $conv): ?>
                            <tr id="row-<?php echo $conv['id']; ?>">
                                <td>
                                    <span class="codigo-base"><?php echo htmlspecialchars($conv['codigo_base']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($conv['nombre']); ?></strong>
                                    <?php if ($conv['info_institucional']): ?>
                                    <br><small class="text-muted info-truncate"><?php echo htmlspecialchars(substr($conv['info_institucional'], 0, 100)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($conv['tipo_documento']); ?></td>
                                <td class="text-center">
                                    <?php 
                                    $count = isset($diplomas_count[$conv['codigo_base']]) ? $diplomas_count[$conv['codigo_base']] : 0;
                                    echo '<span class="badge bg-secondary">' . $count . '</span>';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($conv['activo']): ?>
                                    <span class="badge badge-activo">Activo</span>
                                    <?php else: ?>
                                    <span class="badge badge-inactivo">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="convocatoria-form.php?id=<?php echo $conv['id']; ?>" 
                                           class="btn btn-outline-primary btn-action" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-<?php echo $conv['activo'] ? 'warning' : 'success'; ?> btn-action"
                                                onclick="toggleEstado(<?php echo $conv['id']; ?>, <?php echo $conv['activo']; ?>)"
                                                title="<?php echo $conv['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                            <i class="bi bi-<?php echo $conv['activo'] ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                        <?php if ($count == 0): ?>
                                        <button type="button" class="btn btn-outline-danger btn-action"
                                                onclick="eliminar(<?php echo $conv['id']; ?>, '<?php echo htmlspecialchars($conv['nombre'], ENT_QUOTES); ?>')"
                                                title="Eliminar">
                                            <i class="bi bi-trash"></i>
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
        <?php endif; ?>

        <!-- Leyenda -->
        <div class="mt-4">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> 
                Las convocatorias con diplomas asociados no pueden eliminarse. 
                Desactívelas si ya no desea usarlas para nuevas cargas.
            </small>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Confirmar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    ¿Está seguro?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmModalBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.getElementById('alertContainer').appendChild(alertDiv);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }
        
        function toggleEstado(id, estadoActual) {
            const nuevoEstado = estadoActual ? 0 : 1;
            const accion = estadoActual ? 'desactivar' : 'activar';
            
            document.getElementById('confirmModalTitle').textContent = 'Confirmar cambio de estado';
            document.getElementById('confirmModalBody').textContent = `¿Está seguro de ${accion} esta convocatoria?`;
            document.getElementById('confirmModalBtn').className = 'btn btn-warning';
            document.getElementById('confirmModalBtn').textContent = estadoActual ? 'Desactivar' : 'Activar';
            
            document.getElementById('confirmModalBtn').onclick = function() {
                fetch('api-convocatorias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `accion=toggle&id=${id}&activo=${nuevoEstado}`
                })
                .then(r => r.json())
                .then(data => {
                    confirmModal.hide();
                    if (data.success) {
                        location.reload();
                    } else {
                        showAlert(data.mensaje, 'danger');
                    }
                })
                .catch(() => {
                    confirmModal.hide();
                    showAlert('Error de conexión', 'danger');
                });
            };
            
            confirmModal.show();
        }
        
        function eliminar(id, nombre) {
            document.getElementById('confirmModalTitle').textContent = 'Confirmar eliminación';
            document.getElementById('confirmModalBody').innerHTML = `
                ¿Está seguro de eliminar la convocatoria <strong>"${nombre}"</strong>?
                <br><br>
                <small class="text-danger">Esta acción no se puede deshacer.</small>
            `;
            document.getElementById('confirmModalBtn').className = 'btn btn-danger';
            document.getElementById('confirmModalBtn').textContent = 'Eliminar';
            
            document.getElementById('confirmModalBtn').onclick = function() {
                fetch('api-convocatorias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `accion=eliminar&id=${id}`
                })
                .then(r => r.json())
                .then(data => {
                    confirmModal.hide();
                    if (data.success) {
                        document.getElementById('row-' + id).remove();
                        showAlert('Convocatoria eliminada correctamente', 'success');
                    } else {
                        showAlert(data.mensaje, 'danger');
                    }
                })
                .catch(() => {
                    confirmModal.hide();
                    showAlert('Error de conexión', 'danger');
                });
            };
            
            confirmModal.show();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
