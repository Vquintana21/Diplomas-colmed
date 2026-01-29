<?php
/**
 * cambiar-password.php
 * Página para cambiar contraseña del usuario actual
 * 
 * Cumple con ISO 27001:
 * - A.8.26: Política de contraseñas seguras
 * - A.8.28: Protección CSRF
 */

require_once 'auth.php';
requerirAutenticacion();
$usuario = obtenerUsuarioActual();

$mensaje = '';
$tipo_mensaje = '';
$errores_password = array();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verificar CSRF
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verificarTokenCSRF($csrf_token)) {
        $mensaje = 'Token de seguridad inválido. Recargue la página.';
        $tipo_mensaje = 'danger';
    } else {
        require_once 'config.php';
        
        $password_actual = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
        $password_nueva = isset($_POST['password_nueva']) ? $_POST['password_nueva'] : '';
        $password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';
        
        // Validaciones
        if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
            $mensaje = 'Por favor, complete todos los campos.';
            $tipo_mensaje = 'danger';
        } else {
            // Validar fortaleza de contraseña (ISO 27001)
            $validacionPassword = validarFortalezaPassword($password_nueva);
            if (!$validacionPassword['valido']) {
                $mensaje = 'La contraseña no cumple los requisitos de seguridad.';
                $tipo_mensaje = 'danger';
                $errores_password = $validacionPassword['errores'];
            } elseif ($password_nueva !== $password_confirmar) {
                $mensaje = 'Las contraseñas nuevas no coinciden.';
                $tipo_mensaje = 'danger';
            } else {
                // Verificar contraseña actual
                $id = intval($usuario['id']);
                $query = "SELECT password FROM z_usuarios WHERE id = $id";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    
                    if (password_verify($password_actual, $row['password'])) {
                        // Contraseña actual correcta, actualizar
                        $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                        $nuevo_hash_escaped = $conn->real_escape_string($nuevo_hash);
                        
                        $queryUpdate = "UPDATE z_usuarios SET password = '$nuevo_hash_escaped' WHERE id = $id";
                        
                        if ($conn->query($queryUpdate)) {
                            $mensaje = '¡Contraseña actualizada correctamente!';
                            $tipo_mensaje = 'success';
                            
                            // Registrar evento de seguridad
                            registrarEventoSeguridad('cambio_password', 'Contraseña actualizada exitosamente', $usuario['id']);
                        } else {
                            $mensaje = 'Error al actualizar la contraseña.';
                            $tipo_mensaje = 'danger';
                        }
                    } else {
                        $mensaje = 'La contraseña actual es incorrecta.';
                        $tipo_mensaje = 'danger';
                        
                        // Registrar intento fallido
                        registrarEventoSeguridad('cambio_password_fallido', 'Contraseña actual incorrecta', $usuario['id']);
                    }
                } else {
                    $mensaje = 'Error al verificar el usuario.';
                    $tipo_mensaje = 'danger';
                }
                
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Sistema de Diplomas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .btn-primary-custom {
            background: var(--gradiente-primario);
            border: none;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #154360 0%, #1a5276 100%);
        }
        .password-requirements { font-size: 0.85rem; color: #666; }
        .password-requirements li { margin-bottom: 5px; }
    </style>
</head>
<body data-pagina="cambiar-password">

    <!-- Navbar -->
    <?php include 'nav.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header card-header-institucional py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-shield-lock me-2"></i>
                            Cambiar Contraseña
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                            <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($mensaje); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4 p-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle fs-3 me-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($usuario['username']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="cambiar-password.php">
                            <?php echo campoCSRF(); ?>
                            <div class="mb-3">
                                <label for="password_actual" class="form-label">Contraseña Actual</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password_actual" 
                                           name="password_actual" required>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="mb-3">
                                <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password_nueva" 
                                           name="password_nueva" required minlength="8">
                                </div>
                                <div class="form-text">
                                    <small>Requisitos: mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</small>
                                </div>
                                <?php if (!empty($errores_password)): ?>
                                <div class="text-danger small mt-1">
                                    <?php foreach ($errores_password as $err): ?>
                                    <div><i class="bi bi-x-circle me-1"></i><?php echo htmlspecialchars($err); ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" class="form-control" id="password_confirmar" 
                                           name="password_confirmar" required minlength="6">
                                </div>
                            </div>
                            
                            <div class="password-requirements mb-4">
                                <strong>Requisitos de la contraseña:</strong>
                                <ul class="mt-2 mb-0">
                                    <li>Mínimo 6 caracteres</li>
                                    <li>Se recomienda usar letras, números y símbolos</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-primary-custom btn-lg">
                                    <i class="bi bi-check-lg me-2"></i>
                                    Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>
