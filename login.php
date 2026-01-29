<?php
/**
 * login.php
 * Página de inicio de sesión
 * 
 * Cumple con ISO 27001:
 * - A.8.26: Rate limiting contra fuerza bruta
 * - A.8.28: Protección CSRF
 * - A.8.15/A.8.16: Logging de eventos de seguridad
 */

require_once 'security.php';
iniciarSesionSegura();

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$username_value = '';
$bloqueado = false;

// Verificar rate limiting por IP
$ip = obtenerIPCliente();
$rateCheck = verificarRateLimit('login_' . $ip, 5, 900); // 5 intentos, 15 min bloqueo

if (!$rateCheck['permitido']) {
    $error = $rateCheck['mensaje'];
    $bloqueado = true;
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    
    // Verificar CSRF
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verificarTokenCSRF($csrf_token)) {
        $error = 'Token de seguridad inválido. Recargue la página.';
    } else {
        require_once 'config.php';
        
        $username = isset($_POST['username']) ? sanitizarEntrada($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $username_value = htmlspecialchars($username);
        
        if (empty($username) || empty($password)) {
            $error = 'Por favor, complete todos los campos.';
        } else {
            // Buscar usuario en la base de datos
            $username_escaped = $conn->real_escape_string($username);
            $query = "SELECT id, username, password, nombre, rol, activo FROM z_usuarios WHERE username = '$username_escaped'";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                
                // Verificar si el usuario está activo
                if (!$usuario['activo']) {
                    $error = 'Esta cuenta está desactivada. Contacte al administrador.';
                    registrarEventoSeguridad('login_bloqueado', "Intento de login con cuenta desactivada: $username", null, array('username' => $username));
                }
                // Verificar contraseña
                elseif (password_verify($password, $usuario['password'])) {
                    // Login exitoso
                    
                    // Regenerar session ID para prevenir session fixation
                    regenerarSesion();
                    
                    // Crear sesión
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_username'] = $usuario['username'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_rol'] = $usuario['rol'];
                    $_SESSION['usuario_logueado'] = true;
                    $_SESSION['ultimo_acceso'] = time();
                    
                    // Resetear rate limit
                    resetearRateLimit('login_' . $ip);
                    
                    // Actualizar último acceso en BD
                    $ahora = date('Y-m-d H:i:s');
                    $conn->query("UPDATE z_usuarios SET ultimo_acceso = '$ahora' WHERE id = " . $usuario['id']);
                    
                    // Registrar evento de seguridad
                    registrarEventoSeguridad('login_exitoso', "Login exitoso: " . $usuario['username'], $usuario['id']);
                    
                    // Redirigir
                    $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
                    unset($_SESSION['redirect_url']);
                    
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    // Contraseña incorrecta
                    $error = 'Usuario o contraseña incorrectos.';
                    registrarIntentoFallido('login_' . $ip, 5, 900);
                    registrarEventoSeguridad('login_fallido', "Contraseña incorrecta para: $username", null, array('username' => $username));
                }
            } else {
                // Usuario no existe
                $error = 'Usuario o contraseña incorrectos.';
                registrarIntentoFallido('login_' . $ip, 5, 900);
                registrarEventoSeguridad('login_fallido', "Usuario no existe: $username", null, array('username' => $username));
            }
            
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Diplomas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body {
            background: var(--gradiente-primario);
            min-height: 100vh;
        }
        .login-body { padding: 30px; }
        .form-floating { margin-bottom: 20px; }
        .form-floating .form-control {
            border-radius: var(--radio-medio);
            border: 2px solid var(--color-borde);
            height: 58px;
        }
        .form-floating .form-control:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.15);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--gradiente-primario);
            border: none;
            border-radius: var(--radio-medio);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 82, 118, 0.4);
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            z-index: 10;
        }
        .password-toggle:hover { color: var(--color-primario); }
        .form-floating-password { position: relative; }
        .form-floating-password .form-control { padding-right: 50px; }
    </style>
</head>
<body data-pagina="login">

    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="bi bi-award"></i>
                </div>
                <h4>Sistema de Diplomas COLMED</h4>
                <p>Ingrese sus credenciales para continuar</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php" autocomplete="off">
                    <?php echo campoCSRF(); ?>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Usuario" required autofocus
                               value="<?php echo $username_value; ?>">
                        <label for="username">
                            <i class="bi bi-person me-2"></i>Usuario
                        </label>
                    </div>
                    
                    <div class="form-floating form-floating-password">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Contraseña" required>
                        <label for="password">
                            <i class="bi bi-key me-2"></i>Contraseña
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <span class="text-muted">¿No tiene una cuenta?</span>
                    <a href="registro.php" class="ms-2 fw-semibold text-decoration-none">Registrarse</a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4 text-white-50">
            <small>&copy; 2026 Sistema de Gestión de Diplomas</small>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function togglePassword() {
        var input = document.getElementById('password');
        var icon = document.getElementById('toggleIcon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
    </script>
    
</body>
</html>
