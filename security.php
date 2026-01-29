<?php
/**
 * security.php
 * Funciones de seguridad centralizadas según ISO 27001
 * Incluir este archivo antes de cualquier otro
 */

// =====================================================
// CONFIGURACIÓN DE SEGURIDAD
// =====================================================

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// =====================================================
// HEADERS DE SEGURIDAD (A.8.26)
// =====================================================
function aplicarHeadersSeguridad() {
    // Prevenir clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevenir MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Habilitar protección XSS del navegador
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy básica
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net https://code.jquery.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net; font-src 'self' https://cdn.jsdelivr.net;");
    
    // Eliminar header que expone versión de PHP
    header_remove('X-Powered-By');
}

// =====================================================
// CONFIGURACIÓN SEGURA DE SESIONES (A.8.28)
// =====================================================
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar cookies de sesión seguras
        $cookieParams = array(
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        );
        
        // PHP 7.3+
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($cookieParams);
        } else {
            // PHP 5.6 - 7.2
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'] . '; SameSite=Strict',
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
        
        // Usar solo cookies (no URL)
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);
        
        session_start();
    }
}

// =====================================================
// REGENERAR SESSION ID (después de login)
// =====================================================
function regenerarSesion() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// =====================================================
// PROTECCIÓN CSRF (A.8.28)
// =====================================================
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Renovar token cada 30 minutos
    if (time() - $_SESSION['csrf_token_time'] > 1800) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function campoCSRF() {
    return '<input type="hidden" name="csrf_token" value="' . generarTokenCSRF() . '">';
}

// =====================================================
// RATE LIMITING - Protección contra fuerza bruta (A.8.26)
// =====================================================
function verificarRateLimit($identificador, $maxIntentos = 5, $tiempoBloqueo = 900) {
    $clave = 'rate_limit_' . md5($identificador);
    
    if (!isset($_SESSION[$clave])) {
        $_SESSION[$clave] = array(
            'intentos' => 0,
            'primer_intento' => time(),
            'bloqueado_hasta' => 0
        );
    }
    
    $datos = &$_SESSION[$clave];
    
    // Verificar si está bloqueado
    if ($datos['bloqueado_hasta'] > time()) {
        $tiempoRestante = $datos['bloqueado_hasta'] - time();
        return array(
            'permitido' => false,
            'mensaje' => "Demasiados intentos. Espere " . ceil($tiempoRestante / 60) . " minutos.",
            'tiempo_restante' => $tiempoRestante
        );
    }
    
    // Resetear contador si pasó el tiempo de ventana
    if (time() - $datos['primer_intento'] > $tiempoBloqueo) {
        $datos['intentos'] = 0;
        $datos['primer_intento'] = time();
    }
    
    return array(
        'permitido' => true,
        'intentos_restantes' => $maxIntentos - $datos['intentos']
    );
}

function registrarIntentoFallido($identificador, $maxIntentos = 5, $tiempoBloqueo = 900) {
    $clave = 'rate_limit_' . md5($identificador);
    
    if (!isset($_SESSION[$clave])) {
        $_SESSION[$clave] = array(
            'intentos' => 0,
            'primer_intento' => time(),
            'bloqueado_hasta' => 0
        );
    }
    
    $_SESSION[$clave]['intentos']++;
    
    // Bloquear si excede el máximo
    if ($_SESSION[$clave]['intentos'] >= $maxIntentos) {
        $_SESSION[$clave]['bloqueado_hasta'] = time() + $tiempoBloqueo;
    }
}

function resetearRateLimit($identificador) {
    $clave = 'rate_limit_' . md5($identificador);
    if (isset($_SESSION[$clave])) {
        unset($_SESSION[$clave]);
    }
}

// =====================================================
// VALIDACIÓN DE CONTRASEÑAS SEGURAS (A.8.26)
// =====================================================
function validarFortalezaPassword($password) {
    $errores = array();
    
    // Mínimo 8 caracteres
    if (strlen($password) < 8) {
        $errores[] = 'Debe tener al menos 8 caracteres';
    }
    
    // Al menos una mayúscula
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = 'Debe incluir al menos una letra mayúscula';
    }
    
    // Al menos una minúscula
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = 'Debe incluir al menos una letra minúscula';
    }
    
    // Al menos un número
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = 'Debe incluir al menos un número';
    }
    
    // Al menos un carácter especial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-]/', $password)) {
        $errores[] = 'Debe incluir al menos un carácter especial (!@#$%^&*...)';
    }
    
    return array(
        'valido' => empty($errores),
        'errores' => $errores
    );
}

// =====================================================
// LOGGING DE SEGURIDAD (A.8.15, A.8.16)
// =====================================================
function registrarEventoSeguridad($tipo, $descripcion, $usuario_id = null, $datos_extra = array()) {
    global $conn;
    
    // Si no hay conexión, intentar crear una
    if (!isset($conn) || $conn->connect_error) {
        return false;
    }
    
    $ip = obtenerIPCliente();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    
    $tipo_escaped = $conn->real_escape_string($tipo);
    $descripcion_escaped = $conn->real_escape_string($descripcion);
    $ip_escaped = $conn->real_escape_string($ip);
    $user_agent_escaped = $conn->real_escape_string($user_agent);
    $datos_json = $conn->real_escape_string(json_encode($datos_extra));
    $usuario_id_sql = $usuario_id ? intval($usuario_id) : 'NULL';
    
    $query = "INSERT INTO z_log_seguridad (tipo, descripcion, usuario_id, ip, user_agent, datos_extra, fecha) 
              VALUES ('$tipo_escaped', '$descripcion_escaped', $usuario_id_sql, '$ip_escaped', '$user_agent_escaped', '$datos_json', NOW())";
    
    return $conn->query($query);
}

function obtenerIPCliente() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validar que sea una IP válida
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return '0.0.0.0';
}

// =====================================================
// SANITIZACIÓN DE ENTRADA (A.8.28)
// =====================================================
function sanitizarEntrada($valor, $tipo = 'string') {
    if ($valor === null) {
        return '';
    }
    
    switch ($tipo) {
        case 'int':
            return intval($valor);
            
        case 'float':
            return floatval($valor);
            
        case 'email':
            $valor = filter_var(trim($valor), FILTER_SANITIZE_EMAIL);
            return filter_var($valor, FILTER_VALIDATE_EMAIL) ? $valor : '';
            
        case 'url':
            return filter_var(trim($valor), FILTER_SANITIZE_URL);
            
        case 'html':
            return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            
        case 'string':
        default:
            // Eliminar caracteres nulos y trim
            $valor = str_replace(chr(0), '', $valor);
            return trim($valor);
    }
}

// =====================================================
// MANEJO SEGURO DE ERRORES (A.8.28)
// =====================================================
function manejarErrorSeguro($mensaje_interno, $mensaje_usuario = 'Ha ocurrido un error. Intente nuevamente.') {
    // Registrar error interno (no mostrar al usuario)
    error_log('[SEGURIDAD] ' . date('Y-m-d H:i:s') . ' - ' . $mensaje_interno);
    
    // Devolver mensaje genérico al usuario
    return $mensaje_usuario;
}

// =====================================================
// INICIALIZACIÓN AUTOMÁTICA
// =====================================================

// Aplicar headers de seguridad si no es una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    aplicarHeadersSeguridad();
}
?>
