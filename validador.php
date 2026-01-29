<?php
require_once 'auth.php';
requerirAutenticacion();
$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validador de Diplomas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body { background: var(--color-fondo); min-height: 100vh; }
        .validador-card {
            max-width: 600px;
            margin: 0 auto;
            border-radius: var(--radio-grande);
            box-shadow: var(--sombra-elevada);
            overflow: hidden;
            background: white;
        }
        .validador-content { padding: 40px 30px; background: white; }
        .codigo-input {
            font-family: var(--fuente-monospace);
            letter-spacing: 2px;
            font-size: 1.1rem;
            padding: 15px;
            border: 2px solid var(--color-borde);
            border-radius: var(--radio-medio);
        }
        .codigo-input:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.15);
        }
        .btn-validar {
            background: var(--gradiente-primario);
            border: none;
            padding: 15px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: var(--radio-medio);
        }
        .btn-validar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 82, 118, 0.3);
        }
        .resultado-card { border-radius: var(--radio-grande); margin-top: 25px; }
        .resultado-header { display: flex; align-items: center; gap: 15px; padding: 20px; border-bottom: 1px solid rgba(0,0,0,0.1); }
        .resultado-header .icono { font-size: 2rem; }
        .resultado-body { padding: 20px; }
        .info-item { margin-bottom: 15px; }
        .info-item:last-child { margin-bottom: 0; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-texto-secundario); font-weight: 600; margin-bottom: 5px; }
        .info-value { color: var(--color-texto); line-height: 1.6; }
        .info-value code { background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 4px; }
        .institucional-box { background: rgba(0,0,0,0.05); border-radius: var(--radio-medio); padding: 15px; font-size: 0.9rem; text-align: justify; }
        .nav-link-custom { color: rgba(255,255,255,0.8); text-decoration: none; }
        .nav-link-custom:hover { color: white; }
    </style>
</head>
<body data-pagina="validador">

    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-institucional">
        <div class="container">
            <a class="navbar-brand" href="validador.php">
                <i class="bi bi-patch-check me-2"></i>
                Validador de Diplomas
            </a>
            <div class="d-flex gap-2 align-items-center">
                <a href="index.php" class="nav-link-custom">
                    <i class="bi bi-cloud-upload me-1"></i>
                    Carga Masiva
                </a>
                <a href="listado.php" class="nav-link-custom">
                    <i class="bi bi-list-ul me-1"></i>
                    Listado
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">@<?php echo htmlspecialchars($usuario['username']); ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($usuario['rol'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="admin-usuarios.php"><i class="bi bi-person-gear me-2"></i>Usuarios Permitidos</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="cambiar-password.php"><i class="bi bi-key me-2"></i>Cambiar Contraseña</a></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <div class="validador-card">
            <div class="validador-header">
                <h2><i class="bi bi-award me-2"></i>Validación de Certificados</h2>
                <p>Verifique la autenticidad de un diploma institucional</p>
            </div>
            
            <div class="validador-content">
                <form id="formValidar">
                    <div class="mb-4">
                        <label for="codigo" class="form-label fw-semibold">
                            <i class="bi bi-upc-scan me-1"></i>
                            Código de Verificación
                        </label>
                        <input 
                            type="text" 
                            class="form-control codigo-input" 
                            id="codigo" 
                            name="codigo"
                            placeholder="Ej: D20251134M001" 
                            required
                            autocomplete="off">
                        <div class="form-text">
                            Ingrese el código que aparece en el diploma
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-validar" id="btnValidar">
                            <i class="bi bi-search me-2"></i>
                            Validar Diploma
                        </button>
                    </div>
                </form>
                
                <!-- Resultado -->
                <div id="resultado"></div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-4 text-muted">
            <small>
                Sistema de Validación de Diplomas &copy; 2026
            </small>
        </div>
        
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    (function() {
        var form = document.getElementById('formValidar');
        var btnValidar = document.getElementById('btnValidar');
        var resultado = document.getElementById('resultado');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var codigo = document.getElementById('codigo').value.trim();
            
            if (codigo === '') {
                mostrarResultado(false, 'Por favor, ingrese un código de diploma.');
                return;
            }
            
            // Deshabilitar botón y mostrar spinner
            btnValidar.disabled = true;
            btnValidar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validando...';
            resultado.innerHTML = '';
            
            // Enviar petición AJAX
            var formData = new FormData();
            formData.append('codigo', codigo);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'validar.php', true);
            
            xhr.onload = function() {
                btnValidar.disabled = false;
                btnValidar.innerHTML = '<i class="bi bi-search me-2"></i>Validar Diploma';
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            mostrarResultado(true, response.mensaje, response.datos);
                        } else {
                            mostrarResultado(false, response.mensaje);
                        }
                    } catch (e) {
                        mostrarResultado(false, 'Error al procesar la respuesta del servidor.');
                    }
                } else {
                    mostrarResultado(false, 'Error de conexión con el servidor.');
                }
            };
            
            xhr.onerror = function() {
                btnValidar.disabled = false;
                btnValidar.innerHTML = '<i class="bi bi-search me-2"></i>Validar Diploma';
                mostrarResultado(false, 'Error de conexión. Intente nuevamente.');
            };
            
            xhr.send(formData);
        });
        
        function mostrarResultado(valido, mensaje, datos) {
            var html = '';
            
            if (valido && datos) {
                // Usar etiquetas dinámicas de la convocatoria
                var etiquetaPersona = datos.etiqueta_persona || 'Autor(es)';
                var etiquetaTema = datos.etiqueta_tema || '';
                var tipoDocumento = datos.tipo_documento || 'Diploma';
                var infoInstitucional = datos.info_institucional || '';
				var temaDiploma = datos.tema || '';
                
                html = '<div class="resultado-card resultado-valido">' +
                    '<div class="resultado-header">' +
                        '<span class="icono"><i class="bi bi-check-circle-fill"></i></span>' +
                        '<div>' +
                            '<h5 class="mb-0 text-success">' + escapeHtml(tipoDocumento) + ' Válido</h5>' +
                            '<small class="text-muted">Certificado verificado correctamente</small>' +
                        '</div>' +
                    '</div>' +
                    '<div class="resultado-body">' +
                        '<div class="info-item">' +
                            '<div class="info-label">Código</div>' +
                            '<div class="info-value"><code>' + escapeHtml(datos.codigo) + '</code></div>' +
                        '</div>' +
                        '<div class="info-item">' +
                            '<div class="info-label">' + escapeHtml(etiquetaPersona) + '</div>' +
                            '<div class="info-value">' + escapeHtml(datos.autores) + '</div>' +
                        '</div>';
                
                // Mostrar tema solo si hay etiqueta y contenido
                // Mostrar tema SOLO si ambos existen: etiqueta en convocatoria Y tema en diploma
                if (etiquetaTema && etiquetaTema.trim() !== '' && temaDiploma && temaDiploma.trim() !== '') {
                    html += '<div class="info-item">' +
                        '<div class="info-label">' + escapeHtml(etiquetaTema) + '</div>' +
                        '<div class="info-value">' + escapeHtml(temaDiploma) + '</div>' +
                    '</div>';
                }
                
                // Mostrar info institucional solo si existe
                if (infoInstitucional && infoInstitucional.trim() !== '') {
                    html += '<div class="info-item">' +
                        '<div class="info-label">Información Institucional</div>' +
                        '<div class="institucional-box">' + escapeHtml(infoInstitucional) + '</div>' +
                    '</div>';
                }
                
                html += '</div></div>';
            } else {
                html = '<div class="resultado-card resultado-invalido">' +
                    '<div class="resultado-header">' +
                        '<span class="icono"><i class="bi bi-x-circle-fill"></i></span>' +
                        '<div>' +
                            '<h5 class="mb-0 text-danger">Código No Válido</h5>' +
                            '<small class="text-muted">No se encontró en el sistema</small>' +
                        '</div>' +
                    '</div>' +
                    '<div class="resultado-body">' +
                        '<p class="mb-0">' + escapeHtml(mensaje) + '</p>' +
                    '</div>' +
                '</div>';
            }
            
            resultado.innerHTML = html;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    })();
    </script>
    
</body>
</html>
