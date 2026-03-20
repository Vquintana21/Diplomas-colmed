# Reporte de Cumplimiento ISO 27001
## Sistema de Gestión de Diplomas COLMED

**Fecha de Evaluación:** Enero 2026
**Versión del Sistema:** 2.0
**Preparado por:** Equipo de Desarrollo

---

## 1. Resumen Ejecutivo

El Sistema de Gestión de Diplomas COLMED ha sido desarrollado siguiendo los lineamientos de la norma **ISO/IEC 27001:2022** para Sistemas de Gestión de Seguridad de la Información (SGSI). Este documento detalla los controles implementados según el Anexo A de la norma.

### Stack Tecnológico
| Componente | Tecnología |
|------------|------------|
| Backend | PHP 5.6+ / 7.x / 8.x |
| Base de Datos | MySQL 5.7+ / MariaDB |
| Frontend | Bootstrap 5.3, JavaScript ES5+ |
| Servidor Web | Apache / Nginx |

---

## 2. Controles de Seguridad Implementados

### 2.1. Control de Acceso (A.5.15 - A.5.18)

#### A.5.15 - Control de Acceso
| Control | Implementación | Archivo |
|---------|----------------|---------|
| Autenticación obligatoria | Todas las páginas protegidas requieren login | `auth.php` |
| Roles de usuario | Sistema de roles (admin/usuario) | `z_usuarios.rol` |
| Lista blanca de usuarios | Solo RUTs pre-autorizados pueden registrarse | `z_usuarios_permitidos` |

#### A.5.16 - Gestión de Identidades
```php
// Verificación de identidad por RUT chileno
// Solo usuarios en z_usuarios_permitidos pueden registrarse
$query = "SELECT * FROM z_usuarios_permitidos WHERE rut = ? AND activo = 1";
```

#### A.5.17 - Información de Autenticación
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- No se almacenan contraseñas en texto plano
- Hash verificado con `password_verify()`

---

### 2.2. Seguridad de Sesiones (A.8.25 - A.8.28)

#### A.8.25 - Ciclo de Vida de Desarrollo Seguro

**Archivo:** `security.php`

| Medida | Descripción |
|--------|-------------|
| Headers de Seguridad | X-Frame-Options, X-Content-Type-Options, X-XSS-Protection |
| Content Security Policy | Restricción de fuentes de scripts, estilos e imágenes |
| Session Cookies | HttpOnly, Secure, SameSite=Strict |

```php
// Configuración de cookies seguras
$cookieParams = array(
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,      // Solo HTTPS
    'httponly' => true,    // No accesible por JS
    'samesite' => 'Strict' // Previene CSRF
);
```

#### A.8.26 - Requisitos de Seguridad de Aplicaciones

**Política de Contraseñas Robusta:**
```php
function validarFortalezaPassword($password) {
    // Mínimo 8 caracteres
    // Al menos una mayúscula
    // Al menos una minúscula
    // Al menos un número
    // Al menos un carácter especial (!@#$%^&*...)
}
```

**Rate Limiting (Protección contra Fuerza Bruta):**
- Máximo 5 intentos fallidos
- Bloqueo temporal de 15 minutos
- Identificación por IP del cliente

```php
verificarRateLimit('login_' . $ip, 5, 900); // 5 intentos, 900 segundos
```

#### A.8.28 - Codificación Segura

| Vulnerabilidad | Mitigación |
|----------------|------------|
| SQL Injection | Escape de caracteres con `real_escape_string()` |
| XSS | `htmlspecialchars()` en salidas, CSP headers |
| CSRF | Tokens únicos por sesión, renovados cada 30 min |
| Session Fixation | Regeneración de session ID post-login |
| Clickjacking | Header X-Frame-Options: DENY |

**Protección CSRF:**
```php
// Generación de token (64 caracteres hexadecimales)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Verificación con comparación segura
hash_equals($_SESSION['csrf_token'], $token_recibido);
```

---

### 2.3. Registro y Monitoreo (A.8.15 - A.8.16)

#### A.8.15 - Logging
#### A.8.16 - Actividades de Monitoreo

**Tabla de Auditoría:** `z_log_seguridad`

| Campo | Descripción |
|-------|-------------|
| `tipo` | Categoría del evento |
| `descripcion` | Detalle del evento |
| `usuario_id` | Usuario involucrado |
| `ip` | Dirección IP del cliente |
| `user_agent` | Navegador/dispositivo |
| `datos_extra` | JSON con información adicional |
| `fecha` | Timestamp del evento |

**Eventos Registrados:**
| Evento | Cuándo se registra |
|--------|-------------------|
| `login_exitoso` | Autenticación correcta |
| `login_fallido` | Contraseña incorrecta |
| `login_bloqueado` | Cuenta desactivada |
| `logout` | Cierre de sesión |
| `cambio_password` | Modificación de contraseña |
| `cambio_password_fallido` | Intento fallido de cambio |
| `registro_usuario` | Nuevo usuario registrado |

---

### 2.4. Timeout de Sesión (A.8.28)

```php
define('SESSION_TIMEOUT', 1800); // 30 minutos de inactividad

// Verificación automática
if (time() - $_SESSION['ultimo_acceso'] > SESSION_TIMEOUT) {
    cerrarSesion();
    // Redirigir a login
}
```

---

## 3. Arquitectura de Seguridad

### 3.1. Flujo de Autenticación

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Usuario   │────▶│   login.php  │────▶│   auth.php  │
└─────────────┘     └──────────────┘     └─────────────┘
                           │                     │
                           ▼                     ▼
                    ┌──────────────┐     ┌─────────────┐
                    │ security.php │     │  z_usuarios │
                    │  - CSRF      │     │  (MySQL)    │
                    │  - Rate Limit│     └─────────────┘
                    │  - Headers   │
                    └──────────────┘
```

### 3.2. Capas de Seguridad

```
┌────────────────────────────────────────────────────┐
│                   CAPA DE TRANSPORTE               │
│              HTTPS (TLS 1.2+) [Servidor Web]       │
├────────────────────────────────────────────────────┤
│                   CAPA DE APLICACIÓN               │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────┐  │
│  │   Headers   │  │    CSRF     │  │   Rate     │  │
│  │  Seguridad  │  │   Tokens    │  │  Limiting  │  │
│  └─────────────┘  └─────────────┘  └────────────┘  │
├────────────────────────────────────────────────────┤
│                   CAPA DE SESIÓN                   │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────┐  │
│  │  HttpOnly   │  │   Secure    │  │  SameSite  │  │
│  │   Cookies   │  │   Cookies   │  │   Strict   │  │
│  └─────────────┘  └─────────────┘  └────────────┘  │
├────────────────────────────────────────────────────┤
│                   CAPA DE DATOS                    │
│  ┌─────────────┐  ┌─────────────┐  ┌────────────┐  │
│  │   Escape    │  │   Bcrypt    │  │  Prepared  │  │
│  │    SQL      │  │   Hashing   │  │ Statements │  │
│  └─────────────┘  └─────────────┘  └────────────┘  │
└────────────────────────────────────────────────────┘
```

---

## 4. Archivos de Seguridad

| Archivo | Función | Controles ISO 27001 |
|---------|---------|---------------------|
| `security.php` | Funciones centralizadas de seguridad | A.8.25, A.8.26, A.8.28 |
| `auth.php` | Gestión de autenticación y sesiones | A.5.15, A.5.16, A.5.17 |
| `login.php` | Punto de entrada con rate limiting | A.8.26 |
| `registro.php` | Registro con validación de RUT | A.5.16 |
| `cambiar-password.php` | Cambio de contraseña seguro | A.5.17 |

---

## 5. Base de Datos - Tablas de Seguridad

### 5.1. z_log_seguridad
Registro de auditoría de eventos de seguridad.

```sql
CREATE TABLE z_log_seguridad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    usuario_id INT,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    datos_extra TEXT,
    fecha DATETIME NOT NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha),
    INDEX idx_ip (ip)
);
```

### 5.2. z_usuarios_permitidos
Lista blanca de usuarios autorizados (control de acceso previo al registro).

```sql
CREATE TABLE z_usuarios_permitidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin','usuario') DEFAULT 'usuario',
    activo TINYINT(1) DEFAULT 1
);
```

### 5.3. z_usuarios
Usuarios registrados con contraseñas hasheadas.

```sql
CREATE TABLE z_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- bcrypt hash
    rol ENUM('admin','usuario'),
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME
);
```

---

## 6. Headers HTTP de Seguridad

Los siguientes headers se aplican automáticamente en cada respuesta:

```http
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net ...
```

---

## 7. Recomendaciones de Despliegue

### 7.1. Configuración del Servidor Web

```apache
# Apache - .htaccess
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>

# Forzar HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 7.2. Configuración de PHP

```ini
; php.ini recomendado
expose_php = Off
display_errors = Off
log_errors = On
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### 7.3. Checklist de Despliegue

- [ ] Certificado SSL/TLS válido instalado
- [ ] PHP configurado para producción (errores off)
- [ ] Cambiar contraseña del usuario admin por defecto
- [ ] Configurar respaldos automáticos de base de datos
- [ ] Revisar permisos de archivos (644 para PHP, 755 para directorios)
- [ ] Configurar firewall para permitir solo puertos 80/443
- [ ] Habilitar logs de acceso del servidor web

---

## 8. Matriz de Cumplimiento ISO 27001

| Control | Descripción | Estado | Evidencia |
|---------|-------------|--------|-----------|
| A.5.15 | Control de acceso | ✅ Implementado | `auth.php`, roles de usuario |
| A.5.16 | Gestión de identidades | ✅ Implementado | Validación RUT, lista blanca |
| A.5.17 | Información de autenticación | ✅ Implementado | bcrypt, política contraseñas |
| A.8.15 | Logging | ✅ Implementado | `z_log_seguridad` |
| A.8.16 | Actividades de monitoreo | ✅ Implementado | Registro de eventos |
| A.8.25 | Ciclo de vida seguro | ✅ Implementado | `security.php` |
| A.8.26 | Requisitos de seguridad | ✅ Implementado | Rate limiting, CSP |
| A.8.28 | Codificación segura | ✅ Implementado | CSRF, XSS, SQLi protegidos |

---

## 9. Contacto y Soporte

Para consultas técnicas sobre la implementación de seguridad:

- **Repositorio:** Sistema de Gestión de Diplomas COLMED
- **Documentación:** `/plantillas/Instructivo.pdf`

---

*Documento generado para evaluación de cumplimiento ISO 27001*
*Sistema de Gestión de Diplomas COLMED v2.0*
