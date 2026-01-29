<?php
/**
 * registro.php
 * Página de registro de usuarios
 * Solo pueden registrarse personas autorizadas (en tabla usuarios_permitidos)
 * 
 * Cumple con ISO 27001:
 * - A.8.26: Política de contraseñas seguras
 * - A.8.28: Protección CSRF
 */

require_once 'security.php';
iniciarSesionSegura();

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$paso = 1; // Paso 1: Verificar RUT, Paso 2: Completar registro
$error = '';
$exito = '';
$rut_verificado = '';
$datos_permitido = null;
$errores_password = array();

/**
 * Formatear RUT (quitar puntos, mantener guión)
 */
function formatearRut($rut) {
    // Eliminar espacios y puntos
    $rut = str_replace(array(' ', '.'), '', trim($rut));
    // Convertir a mayúsculas (para la K)
    $rut = strtoupper($rut);
    return $rut;
}

/**
 * Validar formato de RUT chileno
 */
function validarFormatoRut($rut) {
    // Formato esperado: 12345678-9 o 12345678-K
    return preg_match('/^[0-9]{7,8}-[0-9Kk]$/', $rut);
}

/**
 * Validar dígito verificador del RUT
 */
function validarRutChileno($rut) {
    $rut = formatearRut($rut);
    
    if (!validarFormatoRut($rut)) {
        return false;
    }
    
    $partes = explode('-', $rut);
    $numero = $partes[0];
    $dv = strtoupper($partes[1]);
    
    // Calcular dígito verificador
    $suma = 0;
    $multiplo = 2;
    
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $suma += intval($numero[$i]) * $multiplo;
        $multiplo = $multiplo == 7 ? 2 : $multiplo + 1;
    }
    
    $resto = $suma % 11;
    $dvCalculado = 11 - $resto;
    
    if ($dvCalculado == 11) {
        $dvCalculado = '0';
    } elseif ($dvCalculado == 10) {
        $dvCalculado = 'K';
    } else {
        $dvCalculado = strval($dvCalculado);
    }
    
    return $dv === $dvCalculado;
}

// ============================================
// PASO 1: Verificar RUT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificar_rut'])) {
    $rut = isset($_POST['rut']) ? formatearRut($_POST['rut']) : '';
    
    if (empty($rut)) {
        $error = 'Por favor, ingrese su RUT.';
    } elseif (!validarFormatoRut($rut)) {
        $error = 'El formato del RUT no es válido. Use el formato: 12345678-9';
    } elseif (!validarRutChileno($rut)) {
        $error = 'El RUT ingresado no es válido. Verifique el dígito verificador.';
    } else {
        // Verificar si ya está registrado en usuarios
        $rut_escaped = $conn->real_escape_string($rut);
        $query = "SELECT id FROM z_usuarios WHERE rut = '$rut_escaped'";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $error = 'Este RUT ya tiene una cuenta registrada. Si olvidó su contraseña, contacte al administrador.';
        } else {
            // Verificar si está en usuarios_permitidos
            $query = "SELECT id, rut, nombre, email, activo FROM z_usuarios_permitidos WHERE rut = '$rut_escaped'";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $datos_permitido = $result->fetch_assoc();
                
                if (!$datos_permitido['activo']) {
                    $error = 'Su autorización para registrarse ha sido desactivada. Contacte al administrador.';
                } else {
                    // RUT válido y autorizado, pasar al paso 2
                    $paso = 2;
                    $rut_verificado = $rut;
                }
            } else {
                $error = 'Su RUT no está autorizado para registrarse en el sistema. Contacte al administrador.';
            }
        }
    }
}

// ============================================
// PASO 2: Completar registro
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completar_registro'])) {
    $rut = isset($_POST['rut_verificado']) ? formatearRut($_POST['rut_verificado']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';
    
    // Re-verificar el RUT (por seguridad)
    $rut_escaped = $conn->real_escape_string($rut);
    $query = "SELECT id, rut, nombre, email, rol, activo FROM z_usuarios_permitidos WHERE rut = '$rut_escaped' AND activo = 1";
    $result = $conn->query($query);
    
    if (!$result || $result->num_rows === 0) {
        $error = 'Error de verificación. Por favor, inicie el proceso nuevamente.';
        $paso = 1;
    } else {
        $datos_permitido = $result->fetch_assoc();
        
        // Verificar que no se haya registrado mientras completaba el formulario
        $query = "SELECT id FROM z_usuarios WHERE rut = '$rut_escaped'";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $error = 'Este RUT acaba de ser registrado. Si no fue usted, contacte al administrador.';
            $paso = 1;
        } else {
            // Validaciones del formulario
            if (empty($username)) {
                $error = 'Por favor, ingrese un nombre de usuario.';
                $paso = 2;
                $rut_verificado = $rut;
            } elseif (strlen($username) < 4) {
                $error = 'El nombre de usuario debe tener al menos 4 caracteres.';
                $paso = 2;
                $rut_verificado = $rut;
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $error = 'El nombre de usuario solo puede contener letras, números y guión bajo.';
                $paso = 2;
                $rut_verificado = $rut;
            } elseif (empty($password)) {
                $error = 'Por favor, ingrese una contraseña.';
                $paso = 2;
                $rut_verificado = $rut;
            } else {
                // Validar fortaleza de contraseña (ISO 27001)
                $validacionPassword = validarFortalezaPassword($password);
                if (!$validacionPassword['valido']) {
                    $error = 'La contraseña no cumple los requisitos de seguridad.';
                    $errores_password = $validacionPassword['errores'];
                    $paso = 2;
                    $rut_verificado = $rut;
                } elseif ($password !== $password_confirmar) {
                    $error = 'Las contraseñas no coinciden.';
                    $paso = 2;
                    $rut_verificado = $rut;
                } else {
                // Verificar que el username no exista
                $username_escaped = $conn->real_escape_string($username);
                $query = "SELECT id FROM z_usuarios WHERE username = '$username_escaped'";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $error = 'El nombre de usuario ya está en uso. Elija otro.';
                    $paso = 2;
                    $rut_verificado = $rut;
                } else {
                    // Todo válido, crear usuario
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $password_hash_escaped = $conn->real_escape_string($password_hash);
                    $nombre_escaped = $conn->real_escape_string($datos_permitido['nombre']);
                    $email_escaped = $conn->real_escape_string($datos_permitido['email'] ? $datos_permitido['email'] : '');
                    $rol_usuario = isset($datos_permitido['rol']) && $datos_permitido['rol'] === 'admin' ? 'admin' : 'usuario';
                    
                    $query = "INSERT INTO z_usuarios (rut, username, password, nombre, email, rol, activo, fecha_creacion) 
                              VALUES ('$rut_escaped', '$username_escaped', '$password_hash_escaped', '$nombre_escaped', '$email_escaped', '$rol_usuario', 1, NOW())";
                    
                    if ($conn->query($query)) {
                        $exito = '¡Registro exitoso! Ahora puede iniciar sesión con su usuario y contraseña.';
                        $paso = 3; // Paso de éxito
                    } else {
                        $error = 'Error al crear la cuenta: ' . $conn->error;
                        $paso = 2;
                        $rut_verificado = $rut;
                    }
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Diplomas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .registro-container {
            width: 100%;
            max-width: 480px;
        }
        
        .registro-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .registro-header {
            background: rgba(0, 0, 0, 0.05);
            padding: 30px;
            text-align: center;
        }
        
        .registro-header .icon {
            width: 70px;
            height: 70px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            color: white;
        }
        
        .registro-header.success .icon {
            background: var(--success-gradient);
        }
        
        .registro-header h4 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .registro-header p {
            margin: 10px 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .registro-body {
            padding: 30px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        
        .btn-primary-custom {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success-custom {
            background: var(--success-gradient);
        }
        
        .btn-success-custom:hover {
            box-shadow: 0 10px 20px rgba(17, 153, 142, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .paso-indicador {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .paso-indicador .paso {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .paso-indicador .paso.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .paso-indicador .paso.completed {
            background: var(--success-gradient);
            color: white;
        }
        
        .paso-indicador .linea {
            width: 40px;
            height: 3px;
            background: #e0e0e0;
            align-self: center;
        }
        
        .paso-indicador .linea.active {
            background: var(--success-gradient);
        }
        
        .info-usuario {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-usuario .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 3px;
        }
        
        .info-usuario .valor {
            font-weight: 600;
            color: #333;
        }
        
        .rut-input {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    
    <div class="registro-container">
        <div class="registro-card">
            
            <?php if ($paso === 3): ?>
            <!-- PASO 3: Registro exitoso -->
            <div class="registro-header success">
                <div class="icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h4>¡Registro Exitoso!</h4>
                <p>Su cuenta ha sido creada correctamente</p>
            </div>
            <div class="registro-body text-center">
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($exito); ?>
                </div>
                <a href="login.php" class="btn btn-primary btn-primary-custom btn-success-custom">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Ir a Iniciar Sesión
                </a>
            </div>
            
            <?php else: ?>
            <!-- PASOS 1 y 2 -->
            <div class="registro-header">
                <div class="icon">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h4>Crear Cuenta</h4>
                <p><?php echo $paso === 1 ? 'Ingrese su RUT para verificar autorización' : 'Complete sus datos de acceso'; ?></p>
            </div>
            
            <div class="registro-body">
                <!-- Indicador de pasos -->
                <div class="paso-indicador">
                    <div class="paso <?php echo $paso >= 1 ? 'active' : ''; ?> <?php echo $paso > 1 ? 'completed' : ''; ?>">1</div>
                    <div class="linea <?php echo $paso > 1 ? 'active' : ''; ?>"></div>
                    <div class="paso <?php echo $paso >= 2 ? 'active' : ''; ?>">2</div>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($paso === 1): ?>
                <!-- PASO 1: Verificar RUT -->
                <form method="POST" action="registro.php">
                    <?php echo campoCSRF(); ?>
                    <div class="mb-4">
                        <label for="rut" class="form-label fw-semibold">
                            <i class="bi bi-person-vcard me-1"></i>
                            RUT
                        </label>
                        <input type="text" class="form-control rut-input" id="rut" name="rut" 
                               placeholder="12345678-9" required
                               maxlength="12"
                               value="<?php echo isset($_POST['rut']) ? htmlspecialchars($_POST['rut']) : ''; ?>">
                        <div class="form-text">Ingrese su RUT sin puntos, con guión</div>
                    </div>
                    
                    <button type="submit" name="verificar_rut" class="btn btn-primary btn-primary-custom">
                        <i class="bi bi-search me-2"></i>
                        Verificar RUT
                    </button>
                </form>
                
                <?php elseif ($paso === 2): ?>
                <!-- PASO 2: Completar registro -->
                <div class="info-usuario">
                    <div class="row">
                        <div class="col-6">
                            <div class="label">RUT Verificado</div>
                            <div class="valor"><?php echo htmlspecialchars($rut_verificado); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="label">Nombre</div>
                            <div class="valor"><?php echo htmlspecialchars($datos_permitido['nombre']); ?></div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="registro.php">
                    <?php echo campoCSRF(); ?>
                    <input type="hidden" name="rut_verificado" value="<?php echo htmlspecialchars($rut_verificado); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">
                            <i class="bi bi-person me-1"></i>
                            Nombre de Usuario
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="usuario123" required
                               minlength="4" maxlength="50"
                               pattern="[a-zA-Z0-9_]+"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <div class="form-text">Mínimo 4 caracteres. Solo letras, números y guión bajo.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            <i class="bi bi-key me-1"></i>
                            Contraseña
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="••••••••" required minlength="8">
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
                    
                    <div class="mb-4">
                        <label for="password_confirmar" class="form-label fw-semibold">
                            <i class="bi bi-key-fill me-1"></i>
                            Confirmar Contraseña
                        </label>
                        <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" 
                               placeholder="••••••••" required minlength="8">
                    </div>
                    
                    <button type="submit" name="completar_registro" class="btn btn-primary btn-primary-custom btn-success-custom">
                        <i class="bi bi-check-lg me-2"></i>
                        Crear Cuenta
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="registro.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>
                            Volver a verificar RUT
                        </a>
                    </div>
                </form>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <span class="text-muted">¿Ya tiene una cuenta?</span>
                    <a href="login.php" class="ms-2 fw-semibold text-decoration-none">Iniciar Sesión</a>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <div class="text-center mt-4 text-white-50">
            <small>&copy; 2026 Sistema de Gestión de Diplomas</small>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Formatear RUT mientras se escribe
    document.getElementById('rut')?.addEventListener('input', function(e) {
        var valor = e.target.value.toUpperCase();
        // Eliminar todo excepto números y K
        valor = valor.replace(/[^0-9K]/g, '');
        
        // Agregar guión antes del último carácter si tiene más de 1
        if (valor.length > 1) {
            valor = valor.slice(0, -1) + '-' + valor.slice(-1);
        }
        
        e.target.value = valor;
    });
    </script>
    
</body>
</html>
