# Manual de Gestión de Usuarios

## Sistema de Diplomas COLMED

Este documento describe el flujo completo de gestión de usuarios del sistema, incluyendo el proceso de autorización, registro y administración.

---

## 1. Flujo General de Acceso

El sistema implementa un modelo de **autorización previa** donde los usuarios deben ser autorizados por un administrador antes de poder registrarse.

```
┌─────────────────────────────────────────────────────────────────────┐
│                      FLUJO DE ACCESO AL SISTEMA                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   1. ADMIN AUTORIZA          2. USUARIO SE REGISTRA                 │
│   ┌─────────────┐            ┌─────────────┐                        │
│   │  Agrega RUT │  ───────►  │  Ingresa    │                        │
│   │  y datos    │            │  sus datos  │                        │
│   └─────────────┘            └─────────────┘                        │
│         │                           │                               │
│         ▼                           ▼                               │
│   ┌─────────────┐            ┌─────────────┐                        │
│   │  Usuario    │            │  Sistema    │                        │
│   │  PENDIENTE  │            │  valida RUT │                        │
│   └─────────────┘            └─────────────┘                        │
│                                     │                               │
│                                     ▼                               │
│                              ┌─────────────┐                        │
│                              │  Usuario    │                        │
│                              │  REGISTRADO │                        │
│                              └─────────────┘                        │
│                                     │                               │
│                                     ▼                               │
│                              ┌─────────────┐                        │
│                              │   ACCESO    │                        │
│                              │  AL SISTEMA │                        │
│                              └─────────────┘                        │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Configuración Inicial del Sistema

### Primera Instalación

Al instalar el sistema por primera vez, el desarrollador debe insertar manualmente un registro de administrador en la base de datos:

```sql
-- Insertar usuario administrador inicial en z_usuarios_permitidos
INSERT INTO z_usuarios_permitidos (rut, nombre, email, rol, activo, fecha_agregado)
VALUES ('12345678-9', 'Administrador Principal', 'admin@colmed.cl', 'admin', 1, NOW());
```

> **Importante:** Este RUT debe corresponder a una persona real que será el primer administrador del sistema. Una vez registrado, este usuario podrá agregar a otros usuarios y administradores.

### Pasos del Primer Administrador

1. Acceder a la página de registro (`registro.php`)
2. Ingresar el RUT previamente cargado por el desarrollador
3. Completar el formulario de registro (contraseña, etc.)
4. Iniciar sesión con las credenciales creadas
5. Acceder al panel de administración de usuarios

---

## 3. Panel de Administración de Usuarios

### Acceso

- **URL:** `admin-usuarios.php`
- **Requisito:** Rol de administrador
- **Ubicación:** Menú superior → "Admin Usuarios"

### Vista General

El panel muestra tres indicadores:

| Indicador | Descripción |
|-----------|-------------|
| **Total Autorizados** | Cantidad total de usuarios en la lista de permitidos |
| **Ya Registrados** | Usuarios que completaron su registro |
| **Pendientes de Registro** | Usuarios autorizados que aún no se registran |

---

## 4. Agregar Usuario Permitido (Autorizar)

### Proceso

1. En el panel de administración, completar el formulario "Agregar Usuario Permitido"
2. Campos requeridos:
   - **RUT:** Formato sin puntos, con guión (ej: `12345678-9`)
   - **Nombre Completo:** Nombre del usuario
   - **Email:** Opcional, para referencia
   - **Rol:** Usuario o Administrador

3. Hacer clic en "Agregar"

### Resultado

El usuario queda en estado **PENDIENTE** hasta que complete su registro.

```
Estado: PENDIENTE
┌──────────────────────────────────────┐
│  RUT: 12345678-9                     │
│  Nombre: Juan Pérez                  │
│  Rol: Usuario                        │
│  Estado: Activo                      │
│  Registrado: Pendiente               │
└──────────────────────────────────────┘
```

---

## 5. Registro de Usuario

### Proceso (desde la perspectiva del usuario)

1. El usuario accede a `registro.php`
2. Ingresa su RUT (debe estar previamente autorizado)
3. Si el RUT está autorizado y activo:
   - Completa el formulario de registro
   - Crea su contraseña
   - El sistema lo registra en `z_usuarios`
4. Si el RUT **no está** autorizado:
   - El sistema rechaza el registro
   - Muestra mensaje indicando que debe contactar al administrador

### Validaciones del Sistema

| Validación | Resultado |
|------------|-----------|
| RUT no autorizado | Registro rechazado |
| RUT autorizado pero bloqueado | Registro rechazado |
| RUT ya registrado | Mensaje de error (ya existe cuenta) |
| RUT autorizado y activo | Registro permitido |

---

## 6. Estados de Usuario

### Matriz de Estados

| Estado | Autorizado | Registrado | Activo | Puede Acceder |
|--------|------------|------------|--------|---------------|
| Pendiente | Sí | No | Sí | No (sin cuenta) |
| Registrado Activo | Sí | Sí | Sí | **Sí** |
| Registrado Bloqueado | Sí | Sí | No | No |
| Pendiente Bloqueado | Sí | No | No | No |

---

## 7. Acciones de Administración

### 7.1 Activar / Bloquear Usuario

**Icono:** Toggle (interruptor)

| Estado Actual | Acción | Resultado |
|---------------|--------|-----------|
| Activo | Bloquear | Usuario no puede acceder al sistema |
| Bloqueado | Activar | Usuario recupera acceso |

**Uso:** Click en el botón de toggle en la columna "Acciones"

> **Nota:** Bloquear un usuario no elimina sus datos. Es útil para suspensiones temporales.

---

### 7.2 Eliminar Usuario Pendiente

**Icono:** Papelera (outline)

**Aplica a:** Usuarios que **NO** han completado el registro

**Resultado:**
- Elimina el registro de `z_usuarios_permitidos`
- El RUT ya no está autorizado para registrarse

**Confirmación:** Diálogo simple de confirmación

---

### 7.3 Eliminar Usuario Registrado

**Icono:** Persona con X (rojo sólido)

**Aplica a:** Usuarios que **SÍ** han completado el registro

**Resultado:**
- Elimina la cuenta del usuario de `z_usuarios`
- Elimina el permiso de `z_usuarios_permitidos`
- El usuario pierde todo acceso al sistema
- Se registra en el log de seguridad

**Confirmación:** Modal de advertencia con detalles del usuario

```
┌─────────────────────────────────────────────┐
│  ⚠️  Eliminar Usuario Registrado            │
├─────────────────────────────────────────────┤
│                                             │
│  ¡Atención! Esta acción eliminará           │
│  completamente al usuario del sistema.      │
│                                             │
│  Nombre: Juan Pérez González                │
│  RUT: 12345678-9                            │
│                                             │
│  Se eliminará tanto su cuenta como su       │
│  permiso de acceso. Esta acción no se       │
│  puede deshacer.                            │
│                                             │
│         [Cancelar]  [Eliminar Usuario]      │
└─────────────────────────────────────────────┘
```

> **Restricción:** Un administrador no puede eliminarse a sí mismo.

---

## 8. Tabla Resumen de Acciones

| Acción | Usuario Pendiente | Usuario Registrado |
|--------|-------------------|-------------------|
| Activar/Bloquear | ✅ Disponible | ✅ Disponible |
| Eliminar permiso | ✅ Disponible | ❌ No disponible |
| Eliminar cuenta completa | ❌ No aplica | ✅ Disponible |
| Ver en lista | ✅ Badge "Pendiente" | ✅ Badge "Sí" |

---

## 9. Roles del Sistema

### Usuario

- Acceso a funcionalidades básicas del sistema
- Carga de diplomas
- Gestión de convocatorias (según permisos)
- **No puede** administrar usuarios

### Administrador

- Todas las funcionalidades de Usuario
- Acceso al panel de administración de usuarios
- Agregar/eliminar usuarios permitidos
- Activar/bloquear usuarios
- Asignar roles a nuevos usuarios

---

## 10. Seguridad y Auditoría

### Registro de Eventos

El sistema registra automáticamente en `z_log_seguridad`:

| Evento | Descripción |
|--------|-------------|
| `usuario_permitido_agregado` | Cuando se autoriza un nuevo RUT |
| `usuario_eliminado` | Cuando se elimina un usuario registrado |
| `login_exitoso` | Inicios de sesión |
| `login_fallido` | Intentos fallidos de acceso |

### Protecciones Implementadas

- **Token CSRF** en formularios
- **Validación de RUT** formato chileno
- **Prevención de auto-eliminación** (admin no puede eliminarse)
- **Escape de datos** para prevenir inyección SQL y XSS

---

## 11. Preguntas Frecuentes

### ¿Qué pasa si un usuario olvida su contraseña?

El usuario debe contactar al administrador. El administrador puede:
1. Bloquear y eliminar la cuenta actual
2. Volver a agregar el RUT como permitido
3. El usuario se registra nuevamente

### ¿Puedo tener múltiples administradores?

Sí. Al agregar un usuario permitido, seleccione el rol "Administrador". Cada administrador tendrá acceso completo al panel de gestión de usuarios.

### ¿Qué sucede si bloqueo a un usuario que está logueado?

El usuario podrá continuar su sesión actual, pero no podrá iniciar sesión nuevamente una vez que la sesión expire o cierre sesión.

### ¿Puedo reactivar un usuario eliminado?

Si se eliminó completamente (usuario registrado), debe:
1. Agregarlo nuevamente como usuario permitido
2. El usuario debe registrarse de nuevo

Si solo estaba bloqueado, simplemente actívelo con el toggle.

---

## 12. Estructura de Base de Datos

### Tabla: z_usuarios_permitidos

```sql
| Campo          | Tipo         | Descripción                    |
|----------------|--------------|--------------------------------|
| id             | INT          | Identificador único            |
| rut            | VARCHAR(12)  | RUT del usuario                |
| nombre         | VARCHAR(255) | Nombre completo                |
| email          | VARCHAR(255) | Email (opcional)               |
| rol            | VARCHAR(20)  | 'usuario' o 'admin'            |
| activo         | TINYINT(1)   | 1 = activo, 0 = bloqueado      |
| fecha_agregado | DATETIME     | Fecha de autorización          |
```

### Tabla: z_usuarios

```sql
| Campo            | Tipo         | Descripción                    |
|------------------|--------------|--------------------------------|
| id               | INT          | Identificador único            |
| rut              | VARCHAR(12)  | RUT (FK a z_usuarios_permitidos)|
| nombre           | VARCHAR(255) | Nombre del usuario             |
| email            | VARCHAR(255) | Email del usuario              |
| password_hash    | VARCHAR(255) | Contraseña encriptada          |
| rol              | VARCHAR(20)  | Rol asignado                   |
| fecha_registro   | DATETIME     | Fecha de registro              |
```

---

## Contacto y Soporte

Para soporte técnico o consultas sobre la gestión de usuarios, contactar al área de sistemas.

---

*Documento actualizado: Enero 2026*
*Sistema de Diplomas COLMED - Manual de Gestión de Usuarios v1.0*
