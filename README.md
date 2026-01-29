# Sistema de Carga Masiva de Diplomas

Sistema independiente para importar diplomas desde archivos Excel (.xlsx) o CSV, con validación previa y confirmación antes de la inserción final.

![PHP](https://img.shields.io/badge/PHP-5.6+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)

---

## 📋 Características

- ✅ **Sistema de Login**: Autenticación con contraseña hasheada
- ✅ **Registro con RUT**: Solo usuarios autorizados pueden registrarse
- ✅ **Administración de Usuarios**: Gestión de RUTs permitidos (solo admin)
- ✅ **Carga Masiva**: Importar diplomas desde Excel/CSV
- ✅ **Validador**: Verificar autenticidad de diplomas
- ✅ **Listado**: Vista con DataTables (buscar, ordenar, exportar)
- ✅ Interfaz moderna con Bootstrap 5
- ✅ Drag & Drop para subir archivos
- ✅ Soporte para Excel (.xlsx) y CSV
- ✅ Validación completa antes de insertar
- ✅ Sistema "todo o nada" (sin cargas parciales)
- ✅ Detección de códigos duplicados
- ✅ Vista previa con filtros
- ✅ Transacciones MySQL para integridad
- ✅ API REST para operaciones CRUD
- ✅ Sesiones protegidas con timeout
- ✅ Validación de RUT chileno
- ✅ Compatible con PHP 5.6+

---

## 📁 Estructura de Archivos

```
carga-diplomas/
├── login.php              # Página de inicio de sesión
├── logout.php             # Cerrar sesión
├── registro.php           # Registro de nuevos usuarios (validación RUT)
├── auth.php               # Sistema de autenticación
├── cambiar-password.php   # Cambiar contraseña
├── admin-usuarios.php     # Administrar usuarios permitidos (solo admin)
├── index.php              # Interfaz de carga masiva
├── validador.php          # Interfaz de validación de diplomas
├── listado.php            # Listado de diplomas con DataTables
├── config.php             # Configuración de BD (EDITAR)
├── procesar-carga.php     # Procesa archivo subido
├── confirmar-carga.php    # Confirma e inserta datos
├── validar.php            # Procesa validación de diploma
├── api-diplomas.php       # API REST para operaciones CRUD
├── descargar.php          # Descarga de plantillas
├── limpiar-temporal.php   # Limpia datos temporales
├── assets/
│   ├── css/
│   │   └── custom.css     # Estilos personalizados
│   └── js/
│       └── app.js         # JavaScript/AJAX
├── plantillas/
│   ├── plantilla_diplomas.csv   # Plantilla CSV
│   └── plantilla_diplomas.xlsx  # Plantilla Excel
├── sql/
│   └── install.sql        # SQL de instalación
└── README.md              # Este archivo
```

---

## 🚀 Instalación en cPanel

### Paso 1: Crear la Base de Datos

1. Ingresa a **cPanel** → **Bases de datos MySQL**
2. Crea una nueva base de datos (ej: `usuario_diplomas`)
3. Crea un usuario y asígnalo a la base de datos con **TODOS LOS PRIVILEGIOS**

### Paso 2: Ejecutar el SQL

1. Ve a **phpMyAdmin** desde cPanel
2. Selecciona tu base de datos
3. Ve a la pestaña **SQL**
4. Copia y pega el contenido de `sql/install.sql`
5. Haz clic en **Ejecutar**

### Paso 3: Configurar la Conexión

Edita el archivo `config.php` con tus datos:

```php
define('DB_HOST', 'localhost');           // Generalmente localhost
define('DB_USER', 'usuario_bd');          // Tu usuario de BD
define('DB_PASS', 'tu_password');         // Tu contraseña
define('DB_NAME', 'usuario_diplomas');    // Nombre de tu BD
```

> **Nota:** En cPanel, el usuario y BD suelen tener el prefijo de tu cuenta.
> Ejemplo: si tu cuenta es `micuenta`, sería `micuenta_diplomas`

### Paso 4: Subir Archivos

1. Ve a **Administrador de Archivos** en cPanel
2. Navega a `public_html` (o la carpeta donde quieras instalarlo)
3. Crea una carpeta (ej: `carga-diplomas`)
4. Sube todos los archivos manteniendo la estructura
5. **Importante:** Asegúrate de que los archivos `.php` tengan permisos `644`

### Paso 5: Acceder al Sistema

Abre en tu navegador:
```
Carga Masiva: https://tudominio.com/carga-diplomas/
Validador:    https://tudominio.com/carga-diplomas/validador.php
Listado:      https://tudominio.com/carga-diplomas/listado.php
```

---

## 🔐 Sistema de Autenticación

### Credenciales por defecto
```
Usuario: admin
Contraseña: admin123
```
⚠️ **IMPORTANTE:** Cambiar la contraseña después del primer acceso.

### Sistema de Registro con RUT

Solo las personas autorizadas pueden registrarse. El flujo es:

1. **Admin agrega RUT** a la tabla `usuarios_permitidos`
2. **Usuario ingresa su RUT** en la página de registro
3. **Sistema verifica** que el RUT esté autorizado y no esté ya registrado
4. **Usuario crea** su nombre de usuario y contraseña
5. **Usuario puede loguearse** con sus credenciales

### Tabla `usuarios_permitidos`
| Campo | Descripción |
|-------|-------------|
| rut | RUT sin puntos, con guión (ej: 12345678-9) |
| nombre | Nombre completo de la persona |
| email | Email opcional |
| activo | 1=puede registrarse, 0=bloqueado |

### Validaciones de RUT
- Formato válido (12345678-9)
- Dígito verificador correcto
- RUT en lista de permitidos
- RUT no registrado previamente

### Administración de Usuarios Permitidos
Accesible solo para el rol `admin` desde el menú de usuario → "Usuarios Permitidos"

---

## 📋 Módulo Listado (DataTables)

Vista completa de todos los diplomas registrados con funcionalidades avanzadas.

### Características
- Búsqueda global en tiempo real
- Ordenamiento por columnas
- Paginación configurable
- Exportar a Excel, CSV e Imprimir
- Ver detalle completo de cada diploma
- Eliminar diplomas (con confirmación)
- Diseño responsive

---

## 🔍 Módulo Validador

El validador permite verificar la autenticidad de un diploma ingresando su código.

### Uso
1. Acceder a `validador.php`
2. Ingresar el código del diploma (ej: `D20251134M001`)
3. Si es válido, muestra: código, autores, tema e información institucional
4. Si no existe, muestra mensaje de error

### Registro de Consultas
Cada validación exitosa se registra en la tabla `registro` con fecha y hora.

---

## 📊 Formato del Archivo de Carga

### Columnas Requeridas

| Columna | Descripción | Límite |
|---------|-------------|--------|
| `codigo` | Código único del diploma | Máx. 50 caracteres |
| `autores` | Nombre(s) del/los autor(es) | Sin límite |
| `tema` | Título del trabajo | Máx. 255 caracteres |

### Ejemplo CSV

```csv
codigo,autores,tema
D20251134M999,"NOMBRE AUTOR 1, NOMBRE AUTOR 2",TITULO DEL TRABAJO
D20251134M998,OTRO AUTOR,OTRO TEMA DE INVESTIGACION
```

### Ejemplo Excel

| codigo | autores | tema |
|--------|---------|------|
| D20251134M999 | NOMBRE AUTOR 1, NOMBRE AUTOR 2 | TITULO DEL TRABAJO |

---

## ✅ Validaciones que se Realizan

1. **Código**
   - No puede estar vacío
   - Máximo 50 caracteres
   - No puede estar duplicado en el sistema
   - No puede estar duplicado en el mismo archivo

2. **Autores**
   - No puede estar vacío

3. **Tema**
   - No puede estar vacío
   - Máximo 255 caracteres

4. **General**
   - Si hay AL MENOS UN error, NO se carga nada
   - Todos los registros deben ser válidos para continuar

---

## 🔄 Flujo de Trabajo

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   1. SUBIR      │ --> │   2. VALIDAR    │ --> │  3. CONFIRMAR   │
│    ARCHIVO      │     │     DATOS       │     │     CARGA       │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │                       │                       │
   .xlsx o .csv         Vista previa con          Inserción en
   máx. 5MB             errores detallados        tabla oficial
```

---

## 🛠️ Solución de Problemas

### "Error de conexión a la base de datos"

- Verifica los datos en `config.php`
- Asegúrate de que el usuario tenga permisos sobre la BD
- En cPanel, verifica que el usuario esté asignado a la BD

### "No se encontraron datos en el archivo"

- Verifica que la primera fila tenga los encabezados: `codigo`, `autores`, `tema`
- Para CSV, el delimitador puede ser `,` o `;`
- Guarda el Excel en formato `.xlsx` (no `.xls`)

### "Código ya existe en el sistema"

- El código ya está registrado en la tabla `diplomas`
- Usa un código diferente o elimina el existente

### "La tabla diplomas_temporal no existe"

- Ejecuta el SQL de instalación (`sql/install.sql`)

### Archivos no se suben

- Verifica permisos de la carpeta (755 para carpetas, 644 para archivos)
- Revisa el límite de `upload_max_filesize` en PHP

---

## 🔧 Configuración Adicional (Opcional)

### Limpieza Automática de Sesiones Antiguas

Las sesiones temporales se limpian automáticamente después de 24 horas.
Para ejecutar manualmente o via cron:

```bash
# Cron job diario a las 3am
0 3 * * * curl -s "https://tudominio.com/carga-diplomas/limpiar-temporal.php" > /dev/null
```

### Aumentar Límite de Subida en PHP

Si necesitas subir archivos más grandes, edita `.htaccess`:

```apache
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
```

---

## 📝 Tablas de la Base de Datos

### `diplomas` (Tabla Principal)

```sql
CREATE TABLE diplomas (
  id int(11) AUTO_INCREMENT PRIMARY KEY,
  codigo varchar(50) UNIQUE NOT NULL,
  autores text NOT NULL,
  tema varchar(255) NOT NULL
);
```

### `diplomas_temporal` (Precarga)

```sql
CREATE TABLE diplomas_temporal (
  id int(11) AUTO_INCREMENT PRIMARY KEY,
  codigo varchar(50) NOT NULL,
  autores text NOT NULL,
  tema varchar(255) NOT NULL,
  session_id varchar(100) NOT NULL,
  estado enum('pendiente','valido','error') DEFAULT 'pendiente',
  mensaje_error varchar(255),
  fecha_carga datetime NOT NULL
);
```

---

## 📜 Licencia

Este proyecto es de uso libre para fines educativos y de gestión institucional.

---

## 👤 Autor

**Victor Quintana**  
Sistema de Carga Masiva de Diplomas v1.0 - Enero 2026
