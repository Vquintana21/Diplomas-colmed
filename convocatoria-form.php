<?php
/**
 * Formulario para crear/editar convocatorias
 * Solo accesible para administradores
 */
require_once 'auth.php';
requerirAutenticacion();

if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$convocatoria = null;
$es_edicion = false;
$error = '';

// Si viene un ID, es edición
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM z_convocatorias WHERE id = $id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $convocatoria = $result->fetch_assoc();
        $es_edicion = true;
    } else {
        header('Location: convocatorias.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_base = isset($_POST['codigo_base']) ? strtoupper(trim($_POST['codigo_base'])) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $tipo_documento = isset($_POST['tipo_documento']) ? trim($_POST['tipo_documento']) : '';
    $info_institucional = isset($_POST['info_institucional']) ? trim($_POST['info_institucional']) : '';
    $etiqueta_persona = isset($_POST['etiqueta_persona']) ? trim($_POST['etiqueta_persona']) : 'Autor(es)';
    $etiqueta_tema = isset($_POST['etiqueta_tema']) ? trim($_POST['etiqueta_tema']) : '';
    
    // Validaciones
    if (empty($codigo_base)) {
        $error = 'El código base es obligatorio.';
    } elseif (strlen($codigo_base) !== 10) {
        $error = 'El código base debe tener exactamente 10 caracteres.';
    } elseif (!preg_match('/^[A-Z][0-9]{4}[0-9]{4}[A-Z]$/', $codigo_base)) {
        $error = 'El código base debe seguir el formato: T(1 letra) + AAAA(4 dígitos año) + EEEE(4 dígitos evento) + C(1 letra categoría). Ejemplo: D20251134M';
    } elseif (empty($nombre)) {
        $error = 'El nombre de la convocatoria es obligatorio.';
    } elseif (empty($tipo_documento)) {
        $error = 'El tipo de documento es obligatorio.';
    } else {
        // Verificar que no exista otro código base igual (excepto en edición)
        $check_query = "SELECT id FROM z_convocatorias WHERE codigo_base = '" . $conn->real_escape_string($codigo_base) . "'";
        if ($es_edicion) {
            $check_query .= " AND id != " . (int)$_POST['id'];
        }
        $check_result = $conn->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = 'Ya existe una convocatoria con ese código base.';
        } else {
            // Preparar datos
            $codigo_base_esc = $conn->real_escape_string($codigo_base);
            $nombre_esc = $conn->real_escape_string($nombre);
            $tipo_documento_esc = $conn->real_escape_string($tipo_documento);
            $info_institucional_esc = $conn->real_escape_string($info_institucional);
            $etiqueta_persona_esc = $conn->real_escape_string($etiqueta_persona);
            $etiqueta_tema_esc = $etiqueta_tema ? "'" . $conn->real_escape_string($etiqueta_tema) . "'" : "NULL";
            
            if ($es_edicion) {
                $query = "UPDATE z_convocatorias SET 
                            codigo_base = '$codigo_base_esc',
                            nombre = '$nombre_esc',
                            tipo_documento = '$tipo_documento_esc',
                            info_institucional = '$info_institucional_esc',
                            etiqueta_persona = '$etiqueta_persona_esc',
                            etiqueta_tema = $etiqueta_tema_esc
                          WHERE id = " . (int)$_POST['id'];
            } else {
                $query = "INSERT INTO z_convocatorias (codigo_base, nombre, tipo_documento, info_institucional, etiqueta_persona, etiqueta_tema)
                          VALUES ('$codigo_base_esc', '$nombre_esc', '$tipo_documento_esc', '$info_institucional_esc', '$etiqueta_persona_esc', $etiqueta_tema_esc)";
            }
            
            if ($conn->query($query)) {
                header('Location: convocatorias.php?msg=' . ($es_edicion ? 'actualizado' : 'creado'));
                exit;
            } else {
                $error = 'Error al guardar: ' . $conn->error;
            }
        }
    }
    
    // Si hay error, mantener los datos ingresados
    $convocatoria = array(
        'id' => isset($_POST['id']) ? $_POST['id'] : '',
        'codigo_base' => $codigo_base,
        'nombre' => $nombre,
        'tipo_documento' => $tipo_documento,
        'info_institucional' => $info_institucional,
        'etiqueta_persona' => $etiqueta_persona,
        'etiqueta_tema' => $etiqueta_tema
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_edicion ? 'Editar' : 'Nueva'; ?> Convocatoria - Sistema de Diplomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <style>
        .codigo-preview {
            font-family: 'Courier New', monospace;
            font-size: 1.5em;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #1a5276, #2874a6);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        .codigo-preview .correlativo {
            color: #ffd700;
        }
        .formato-help {
            font-size: 0.85em;
            color: #6c757d;
        }
        .formato-help code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .char-count {
            font-size: 0.8em;
            color: #6c757d;
        }
        .char-count.invalid {
            color: #dc3545;
        }
        .char-count.valid {
            color: #28a745;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                    <li class="nav-item">
                        <a class="nav-link" href="admin-usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
                    </li>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="bi bi-<?php echo $es_edicion ? 'pencil' : 'plus-circle'; ?> text-primary"></i>
                                <?php echo $es_edicion ? 'Editar' : 'Nueva'; ?> Convocatoria
                            </h4>
                            <a href="convocatorias.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Vista previa del código -->
                        <div class="codigo-preview">
                            <span id="previewCodigo"><?php echo $convocatoria ? htmlspecialchars($convocatoria['codigo_base']) : 'D20251134M'; ?></span><span class="correlativo">001</span>
                        </div>

                        <form method="POST" id="formConvocatoria">
                            <?php if ($es_edicion): ?>
                            <input type="hidden" name="id" value="<?php echo $convocatoria['id']; ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="codigo_base" class="form-label">Código Base <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control font-monospace" id="codigo_base" name="codigo_base" 
                                           value="<?php echo $convocatoria ? htmlspecialchars($convocatoria['codigo_base']) : ''; ?>"
                                           maxlength="10" required placeholder="D20251134M"
                                           style="text-transform: uppercase; letter-spacing: 1px;">
                                    <div class="char-count mt-1">
                                        <span id="charCount">0</span>/10 caracteres
                                    </div>
                                    <div class="formato-help mt-2">
                                        <strong>Formato:</strong> <code>T</code> + <code>AAAA</code> + <code>EEEE</code> + <code>C</code><br>
                                        <small>
                                            T = Tipo (D=Diploma, C=Certificado)<br>
                                            AAAA = Año (2025)<br>
                                            EEEE = Código evento (1134)<br>
                                            C = Categoría (M=Metropolitano, R=Regional, N=Nacional)
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_documento" class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tipo_documento" name="tipo_documento"
                                           value="<?php echo $convocatoria ? htmlspecialchars($convocatoria['tipo_documento']) : ''; ?>"
                                           required placeholder="Ej: Diploma, Certificado, Participación" list="tipos_sugeridos">
                                    <datalist id="tipos_sugeridos">
                                        <option value="Diploma">
                                        <option value="Certificado">
                                        <option value="Certificado de Participación">
                                        <option value="Reconocimiento">
                                        <option value="Constancia">
                                    </datalist>
                                    <small class="text-muted">Este texto aparece en "Diploma Válido" o "Certificado Válido"</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Convocatoria <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?php echo $convocatoria ? htmlspecialchars($convocatoria['nombre']) : ''; ?>"
                                       required placeholder="Ej: Congreso Metropolitano de Médicos Generales de Zona - Julio 2025">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="etiqueta_persona" class="form-label">Etiqueta para Personas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="etiqueta_persona" name="etiqueta_persona"
                                           value="<?php echo $convocatoria ? htmlspecialchars($convocatoria['etiqueta_persona']) : 'Autor(es)'; ?>"
                                           required placeholder="Ej: Autor(es), Participante, Asistente" list="etiquetas_persona">
                                    <datalist id="etiquetas_persona">
                                        <option value="Autor(es)">
                                        <option value="Participante">
                                        <option value="Asistente">
                                        <option value="Expositor">
                                        <option value="Ponente">
                                    </datalist>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="etiqueta_tema" class="form-label">Etiqueta para Tema <span class="text-muted">(opcional)</span></label>
                                    <input type="text" class="form-control" id="etiqueta_tema" name="etiqueta_tema"
                                           value="<?php echo $convocatoria ? htmlspecialchars($convocatoria['etiqueta_tema']) : ''; ?>"
                                           placeholder="Ej: Trabajo presentado, Tema de ponencia" list="etiquetas_tema">
                                    <datalist id="etiquetas_tema">
                                        <option value="Trabajo - Artículo - Tema presentado">
                                        <option value="Tema de ponencia">
                                        <option value="Trabajo de investigación">
                                    </datalist>
                                    <small class="text-muted">Dejar vacío si no aplica (ej: certificados de participación)</small>
                                </div>
                            </div>
							
							<!-- Alerta informativa sobre la lógica del tema -->
                            <div class="alert alert-info small mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>¿Cómo funciona el campo "Tema"?</strong>
                                <ul class="mb-0 mt-2">
                                    <li>El tema se muestra en el validador <strong>solo si</strong> la convocatoria tiene etiqueta Y el diploma tiene tema.</li>
                                    <li><strong>Con etiqueta:</strong> Complete este campo si los diplomas incluirán un tema específico (ej: título de ponencia).</li>
                                    <li><strong>Sin etiqueta:</strong> Déjelo vacío para certificados de participación sin tema individual.</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <label for="info_institucional" class="form-label">Información Institucional</label>
                                <textarea class="form-control" id="info_institucional" name="info_institucional" rows="4"
                                          placeholder="Texto descriptivo que aparecerá en la validación del diploma..."><?php echo $convocatoria ? htmlspecialchars($convocatoria['info_institucional']) : ''; ?></textarea>
                                <small class="text-muted">Este texto aparece al validar un diploma en el sitio público.</small>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <a href="convocatorias.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-<?php echo $es_edicion ? 'check-lg' : 'plus-lg'; ?>"></i>
                                    <?php echo $es_edicion ? 'Guardar Cambios' : 'Crear Convocatoria'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const codigoInput = document.getElementById('codigo_base');
        const charCount = document.getElementById('charCount');
        const previewCodigo = document.getElementById('previewCodigo');
        
        function updatePreview() {
            const valor = codigoInput.value.toUpperCase();
            const len = valor.length;
            
            // Actualizar contador
            charCount.textContent = len;
            charCount.parentElement.className = 'char-count mt-1';
            if (len === 10) {
                charCount.parentElement.classList.add('valid');
            } else if (len > 10) {
                charCount.parentElement.classList.add('invalid');
            }
            
            // Actualizar preview
            previewCodigo.textContent = valor || 'D20251134M';
        }
        
        codigoInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            updatePreview();
        });
        
        // Inicializar
        updatePreview();
    </script>
</body>
</html>
<?php $conn->close(); ?>
