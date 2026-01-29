<?php
/**
 * carga-convocatorias.php
 * Interfaz para carga masiva de convocatorias desde archivo Excel/CSV
 * Solo accesible para administradores
 * 
 * Compatible con PHP 5.6+
 */

require_once 'auth.php';
requerirAutenticacion();
$usuario = obtenerUsuarioActual();

// Solo admin puede acceder
if ($usuario['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

// Generar session_id única para esta carga
$session_id = 'CONV_' . time() . '_' . substr(md5(uniqid()), 0, 8);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Masiva de Convocatorias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .table-preview { font-size: 0.9rem; }
        .table-preview th {
            background: var(--color-secundario);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .badge-estado-valido { background-color: var(--color-exito); }
        .badge-estado-error { background-color: #dc3545; }
        .stats-box {
            padding: 15px;
            border-radius: var(--radio-medio);
            text-align: center;
            color: white;
        }
        .stats-box.total { background: var(--gradiente-primario); }
        .stats-box.validos { background: var(--gradiente-exito); }
        .stats-box.errores { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .stats-box .numero { font-size: 2rem; font-weight: 700; }
        .codigo-base-preview {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: var(--color-primario);
        }
    </style>
</head>
<body class="bg-light" data-pagina="carga-convocatorias">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-institucional">
        <div class="container">
            <a class="navbar-brand" href="carga-convocatorias.php">
                <i class="bi bi-folder-plus me-2"></i>
                Carga Masiva de Convocatorias
            </a>
            <div class="d-flex gap-2 align-items-center">
                <a href="convocatorias.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver a Convocatorias
                </a>
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-house me-1"></i>
                    Inicio
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($usuario['nombre']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        
        <div id="alertaGlobal"></div>
        
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header card-header-institucional py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-1">
                            <i class="bi bi-cloud-upload me-2"></i>
                            Importar Convocatorias
                        </h4>
                        <p class="mb-0 opacity-75">Cargue múltiples convocatorias desde un archivo Excel</p>
                    </div>
                    <div class="col-auto">
                        <a href="plantillas/plantilla-convocatorias.xlsx" class="btn btn-warning btn-sm">
                            <i class="bi bi-download me-1"></i> Descargar Plantilla
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                
                <!-- Indicador de Pasos -->
                <div class="step-indicator">
                    <div class="step active" id="stepIndicator1">1</div>
                    <div class="line" id="lineIndicator1"></div>
                    <div class="step" id="stepIndicator2">2</div>
                    <div class="line" id="lineIndicator2"></div>
                    <div class="step" id="stepIndicator3">3</div>
                </div>
                
                <!-- ========================================= -->
                <!-- SECCIÓN 1: SUBIR ARCHIVO -->
                <!-- ========================================= -->
                <div id="seccionUpload">
                    
                    <h5 class="mb-4">
                        <i class="bi bi-1-circle-fill text-primary me-2"></i>
                        Suba el Archivo de Convocatorias
                    </h5>
                    
                    <!-- Información y Plantilla -->
                    <div class="row mb-4">
                        <div class="col-lg-6 mb-3 mb-lg-0">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Instrucciones
                                    </h6>
                                    <ul class="small mb-0">
                                        <li>Formatos aceptados: <strong>.xlsx</strong> y <strong>.csv</strong></li>
                                        <li>Tamaño máximo: <strong>5 MB</strong></li>
                                        <li>La primera fila debe contener los encabezados</li>
                                        <li>Columnas requeridas: <code>codigo_base</code>, <code>nombre</code>, <code>tipo_documento</code></li>
                                        <li>Columnas opcionales: <code>info_institucional</code>, <code>etiqueta_persona</code>, <code>etiqueta_tema</code></li>
                                        <li>CSV: usar <strong>punto y coma (;)</strong> como separador</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card bg-success bg-opacity-10 border-success h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-success">
                                        <i class="bi bi-file-earmark-ruled me-2"></i>
                                        Formato del Código Base
                                    </h6>
                                    <p class="small mb-2">El código base debe tener <strong>10 caracteres</strong>:</p>
                                    <code class="d-block mb-2">T + AAAA + EEEE + C</code>
                                    <ul class="small mb-0">
                                        <li><strong>T</strong> = Tipo (D=Diploma, C=Certificado)</li>
                                        <li><strong>AAAA</strong> = Año (ej: 2025)</li>
                                        <li><strong>EEEE</strong> = Código evento (ej: 1134)</li>
                                        <li><strong>C</strong> = Categoría (M, R, N, etc.)</li>
                                    </ul>
                                    <div class="mt-2">
                                        <small class="text-muted">Ejemplo: <code>D20251134M</code></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Zona de Upload -->
                    <form id="formCarga" enctype="multipart/form-data">
                        <input type="hidden" name="session_id" id="sessionId" value="<?php echo $session_id; ?>">
                        
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <h5>Arrastre su archivo aquí</h5>
                            <p class="text-muted mb-3">o haga clic para seleccionar</p>
                            <span class="badge bg-secondary">
                                .xlsx, .csv (máx. 5MB)
                            </span>
                            <input type="file" name="archivo" id="archivoInput" 
                                   accept=".xlsx,.csv" class="d-none">
                        </div>
                        
                        <!-- Archivo seleccionado -->
                        <div id="archivoInfo" class="d-none">
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="bi bi-file-earmark-check fs-3 me-3"></i>
                                <div class="flex-grow-1">
                                    <strong id="nombreArchivo"></strong>
                                    <br>
                                    <small class="text-muted" id="infoArchivo"></small>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnCambiar">
                                    <i class="bi bi-x-lg"></i> Cambiar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Botón Procesar -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5" id="btnProcesar" disabled>
                                <i class="bi bi-gear me-2"></i>
                                Procesar Archivo
                            </button>
                        </div>
                    </form>
                    
                </div>
                
                <!-- ========================================= -->
                <!-- SECCIÓN 2: VISTA PREVIA Y VALIDACIÓN -->
                <!-- ========================================= -->
                <div id="seccionPreview" class="d-none">
                    
                    <!-- Resumen -->
                    <div class="row mb-4" id="resumenStats">
                        <div class="col-md-4 mb-2">
                            <div class="stats-box total">
                                <div class="numero" id="statTotal">0</div>
                                <div>Total Registros</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="stats-box validos">
                                <div class="numero" id="statValidos">0</div>
                                <div>Válidos</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="stats-box errores">
                                <div class="numero" id="statErrores">0</div>
                                <div>Con Errores</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alerta si hay errores -->
                    <div id="alertaErrores" class="alert alert-warning d-none">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Atención:</strong> Hay registros con errores que no serán cargados. 
                        Al confirmar, solo se cargarán los registros válidos y se generará un reporte CSV con los rechazados.
                    </div>
                    
                    <!-- Tabla de Registros -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-table me-2"></i>
                                    Vista Previa de Datos
                                </h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary active" data-filtro="todos">
                                        Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-success" data-filtro="valido">
                                        <i class="bi bi-check-circle"></i> Válidos
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" data-filtro="error">
                                        <i class="bi bi-x-circle"></i> Errores
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-preview mb-0">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="80">Estado</th>
                                            <th width="120">Código Base</th>
                                            <th>Nombre</th>
                                            <th width="120">Tipo Doc.</th>
                                            <th width="180">Observación</th>
                                            <th width="70" class="text-center">Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyPreview"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="btnCancelar">
                            <i class="bi bi-x-lg me-2"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="btnConfirmar">
                            <i class="bi bi-check-lg me-2"></i>
                            Confirmar Carga
                        </button>
                    </div>
                </div>
                
                <!-- ========================================= -->
                <!-- SECCIÓN 3: RESULTADO FINAL -->
                <!-- ========================================= -->
                <div id="seccionResultado" class="d-none">
                    <div id="resultadoContenido"></div>
                    
                    <!-- Reporte de rechazados -->
                    <div id="reporteRechazados" class="d-none mt-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-text display-4 text-warning"></i>
                                <h5 class="mt-3">Registros No Cargados</h5>
                                <p class="text-muted">Descargue el reporte con los registros que no fueron cargados</p>
                                <button type="button" class="btn btn-warning" id="btnDescargarReporte">
                                    <i class="bi bi-download me-2"></i>
                                    Descargar Reporte CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary btn-lg" id="btnNuevaCarga">
                            <i class="bi bi-plus-lg me-2"></i>
                            Nueva Carga
                        </button>
                        <a href="convocatorias.php" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="bi bi-list me-2"></i>
                            Ver Convocatorias
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
    
    <!-- Modal de Carga -->
    <div class="modal fade" id="modalCargando" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mb-0" id="textoCargando">Procesando...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-institucional">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>
                        Detalle del Registro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Código Base:</dt>
                        <dd class="col-sm-8">
                            <span class="codigo-base-preview" id="detalleCodigoBase"></span>
                        </dd>
                        
                        <dt class="col-sm-4">Nombre:</dt>
                        <dd class="col-sm-8" id="detalleNombre"></dd>
                        
                        <dt class="col-sm-4">Tipo de Documento:</dt>
                        <dd class="col-sm-8" id="detalleTipoDoc"></dd>
                        
                        <dt class="col-sm-4">Info. Institucional:</dt>
                        <dd class="col-sm-8">
                            <div id="detalleInfoInst" class="text-muted fst-italic" style="white-space: pre-wrap; max-height: 150px; overflow-y: auto;"></div>
                        </dd>
                        
                        <dt class="col-sm-4">Etiqueta Persona:</dt>
                        <dd class="col-sm-8" id="detalleEtiqPersona"></dd>
                        
                        <dt class="col-sm-4">Etiqueta Tema:</dt>
                        <dd class="col-sm-8" id="detalleEtiqTema"></dd>
                        
                        <dt class="col-sm-4">Estado:</dt>
                        <dd class="col-sm-8" id="detalleEstado"></dd>
                        
                        <dt class="col-sm-4 d-none" id="detalleObservacionLabel">Observación:</dt>
                        <dd class="col-sm-8 d-none" id="detalleObservacionTexto"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        // Variables globales
        var sessionId = document.getElementById('sessionId').value;
        var datosRegistros = [];
        var registrosRechazados = [];
        var modalCargando = new bootstrap.Modal(document.getElementById('modalCargando'));
        
        // Elementos DOM
        var uploadZone = document.getElementById('uploadZone');
        var archivoInput = document.getElementById('archivoInput');
        var archivoInfo = document.getElementById('archivoInfo');
        var btnProcesar = document.getElementById('btnProcesar');
        var formCarga = document.getElementById('formCarga');
        
        // ============================================
        // SECCIÓN 1: SUBIR ARCHIVO
        // ============================================
        
        // Click en zona de upload
        uploadZone.addEventListener('click', function() {
            archivoInput.click();
        });
        
        // Drag & Drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', function() {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                archivoInput.files = e.dataTransfer.files;
                mostrarArchivoSeleccionado(e.dataTransfer.files[0]);
            }
        });
        
        // Archivo seleccionado
        archivoInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                mostrarArchivoSeleccionado(this.files[0]);
            }
        });
        
        function mostrarArchivoSeleccionado(file) {
            var extensionesValidas = ['xlsx', 'csv'];
            var extension = file.name.split('.').pop().toLowerCase();
            
            if (extensionesValidas.indexOf(extension) === -1) {
                mostrarAlerta('danger', 'Formato no válido. Solo se permiten archivos .xlsx o .csv');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                mostrarAlerta('danger', 'El archivo excede el tamaño máximo de 5MB');
                return;
            }
            
            document.getElementById('nombreArchivo').textContent = file.name;
            document.getElementById('infoArchivo').textContent = formatearTamano(file.size);
            
            uploadZone.classList.add('d-none');
            archivoInfo.classList.remove('d-none');
            btnProcesar.disabled = false;
        }
        
        // Botón cambiar archivo
        document.getElementById('btnCambiar').addEventListener('click', function() {
            archivoInput.value = '';
            uploadZone.classList.remove('d-none');
            archivoInfo.classList.add('d-none');
            btnProcesar.disabled = true;
        });
        
        // Procesar archivo
        formCarga.addEventListener('submit', function(e) {
            e.preventDefault();
            
            mostrarModal('Procesando archivo...');
            
            var formData = new FormData(formCarga);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'procesar-carga-convocatorias.php', true);
            
            xhr.onload = function() {
                ocultarModal();
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            datosRegistros = response.registros;
                            mostrarPreview(response);
                        } else {
                            mostrarAlerta('danger', response.mensaje);
                        }
                    } catch (e) {
                        mostrarAlerta('danger', 'Error al procesar la respuesta del servidor');
                        console.error(e);
                    }
                } else {
                    mostrarAlerta('danger', 'Error de conexión con el servidor');
                }
            };
            
            xhr.onerror = function() {
                ocultarModal();
                mostrarAlerta('danger', 'Error de conexión');
            };
            
            xhr.send(formData);
        });
        
        // ============================================
        // SECCIÓN 2: PREVIEW
        // ============================================
        
        function mostrarPreview(data) {
            // Actualizar indicadores de paso
            document.getElementById('stepIndicator1').classList.remove('active');
            document.getElementById('stepIndicator1').classList.add('completed');
            document.getElementById('lineIndicator1').classList.add('active');
            document.getElementById('stepIndicator2').classList.add('active');
            
            // Ocultar sección upload, mostrar preview
            document.getElementById('seccionUpload').classList.add('d-none');
            document.getElementById('seccionPreview').classList.remove('d-none');
            
            // Stats
            document.getElementById('statTotal').textContent = data.total;
            document.getElementById('statValidos').textContent = data.validos;
            document.getElementById('statErrores').textContent = data.errores;
            
            // Mostrar alerta si hay errores
            if (data.errores > 0) {
                document.getElementById('alertaErrores').classList.remove('d-none');
            }
            
            // Llenar tabla
            renderizarTabla(data.registros, 'todos');
            
            // Deshabilitar confirmación si no hay válidos
            if (data.validos === 0) {
                document.getElementById('btnConfirmar').disabled = true;
            }
        }
        
        function renderizarTabla(registros, filtro) {
            var tbody = document.getElementById('tbodyPreview');
            tbody.innerHTML = '';
            
            registros.forEach(function(reg, idx) {
                if (filtro !== 'todos' && reg.estado !== filtro) {
                    return;
                }
                
                var badgeClass = reg.estado === 'valido' ? 'badge-estado-valido' : 'badge-estado-error';
                var badgeText = reg.estado === 'valido' ? 'Válido' : 'Error';
                var badgeIcon = reg.estado === 'valido' ? 'check-circle' : 'x-circle';
                
                var tr = document.createElement('tr');
                tr.innerHTML = 
                    '<td>' + (idx + 1) + '</td>' +
                    '<td><span class="badge ' + badgeClass + '"><i class="bi bi-' + badgeIcon + ' me-1"></i>' + badgeText + '</span></td>' +
                    '<td><span class="codigo-base-preview">' + escapeHtml(reg.codigo_base) + '</span></td>' +
                    '<td>' + escapeHtml(reg.nombre) + '</td>' +
                    '<td>' + escapeHtml(reg.tipo_documento) + '</td>' +
                    '<td><small class="text-' + (reg.estado === 'error' ? 'danger' : 'muted') + '">' + escapeHtml(reg.mensaje_error || '-') + '</small></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary" onclick="mostrarDetalle(' + idx + ')" title="Ver detalle"><i class="bi bi-eye"></i></button></td>';
                
                tbody.appendChild(tr);
            });
        }
        
        // Función para mostrar modal de detalle
        function mostrarDetalle(idx) {
            var reg = datosRegistros[idx];
            
            document.getElementById('detalleCodigoBase').textContent = reg.codigo_base || '';
            document.getElementById('detalleNombre').textContent = reg.nombre || '';
            document.getElementById('detalleTipoDoc').textContent = reg.tipo_documento || '';
            document.getElementById('detalleInfoInst').textContent = reg.info_institucional || '(No especificada)';
            document.getElementById('detalleEtiqPersona').textContent = reg.etiqueta_persona || 'Autor(es)';
            document.getElementById('detalleEtiqTema').textContent = reg.etiqueta_tema || '(No especificada)';
            
            // Estado con badge
            var badgeClass = reg.estado === 'valido' ? 'bg-success' : 'bg-danger';
            var badgeText = reg.estado === 'valido' ? 'Válido' : 'Error';
            document.getElementById('detalleEstado').innerHTML = '<span class="badge ' + badgeClass + '">' + badgeText + '</span>';
            
            // Mostrar observación solo si hay error
            var labelObs = document.getElementById('detalleObservacionLabel');
            var textoObs = document.getElementById('detalleObservacionTexto');
            
            if (reg.estado === 'error' && reg.mensaje_error) {
                labelObs.classList.remove('d-none');
                textoObs.classList.remove('d-none');
                textoObs.innerHTML = '<span class="text-danger">' + escapeHtml(reg.mensaje_error) + '</span>';
            } else {
                labelObs.classList.add('d-none');
                textoObs.classList.add('d-none');
            }
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
        }
        
        // Exponer función globalmente para el onclick
        window.mostrarDetalle = mostrarDetalle;
        
        // Filtros de tabla
        document.querySelectorAll('[data-filtro]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('[data-filtro]').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                renderizarTabla(datosRegistros, this.dataset.filtro);
            });
        });
        
        // Botón cancelar
        document.getElementById('btnCancelar').addEventListener('click', function() {
            if (confirm('¿Está seguro de cancelar? Se perderán los datos procesados.')) {
                limpiarYReiniciar();
            }
        });
        
        // Botón confirmar
        document.getElementById('btnConfirmar').addEventListener('click', function() {
            var totalValidos = parseInt(document.getElementById('statValidos').textContent);
            var totalErrores = parseInt(document.getElementById('statErrores').textContent);
            
            var mensaje = '¿Confirma la carga de ' + totalValidos + ' convocatoria(s)?';
            if (totalErrores > 0) {
                mensaje += '\n\n' + totalErrores + ' registro(s) con errores NO serán cargados.';
            }
            
            if (confirm(mensaje)) {
                confirmarCarga();
            }
        });
        
        function confirmarCarga() {
            mostrarModal('Guardando convocatorias...');
            
            var formData = new FormData();
            formData.append('session_id', sessionId);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'confirmar-carga-convocatorias.php', true);
            
            xhr.onload = function() {
                ocultarModal();
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        mostrarResultado(response);
                    } catch (e) {
                        mostrarAlerta('danger', 'Error al procesar la respuesta');
                    }
                } else {
                    mostrarAlerta('danger', 'Error de conexión');
                }
            };
            
            xhr.onerror = function() {
                ocultarModal();
                mostrarAlerta('danger', 'Error de conexión');
            };
            
            xhr.send(formData);
        }
        
        // ============================================
        // SECCIÓN 3: RESULTADO
        // ============================================
        
        function mostrarResultado(data) {
            // Actualizar indicadores
            document.getElementById('stepIndicator2').classList.remove('active');
            document.getElementById('stepIndicator2').classList.add('completed');
            document.getElementById('lineIndicator2').classList.add('active');
            document.getElementById('stepIndicator3').classList.add('active');
            
            // Ocultar preview, mostrar resultado
            document.getElementById('seccionPreview').classList.add('d-none');
            document.getElementById('seccionResultado').classList.remove('d-none');
            
            var contenido = document.getElementById('resultadoContenido');
            
            if (data.success) {
                contenido.innerHTML = 
                    '<div class="text-center py-4">' +
                        '<i class="bi bi-check-circle-fill text-success display-1"></i>' +
                        '<h3 class="mt-3 text-success">¡Carga Completada!</h3>' +
                        '<p class="text-muted">Se cargaron <strong>' + data.insertados + '</strong> convocatoria(s) correctamente.</p>' +
                    '</div>';
                
                // Mostrar reporte de rechazados si hay
                if (data.rechazados && data.rechazados.length > 0) {
                    registrosRechazados = data.rechazados;
                    document.getElementById('reporteRechazados').classList.remove('d-none');
                }
            } else {
                contenido.innerHTML = 
                    '<div class="text-center py-4">' +
                        '<i class="bi bi-x-circle-fill text-danger display-1"></i>' +
                        '<h3 class="mt-3 text-danger">Error en la Carga</h3>' +
                        '<p class="text-muted">' + escapeHtml(data.mensaje) + '</p>' +
                    '</div>';
            }
        }
        
        // Descargar reporte de rechazados
        document.getElementById('btnDescargarReporte').addEventListener('click', function() {
            if (registrosRechazados.length === 0) return;
            
            // Generar CSV
            var csv = 'codigo_base;nombre;tipo_documento;info_institucional;etiqueta_persona;etiqueta_tema;error\n';
            
            registrosRechazados.forEach(function(reg) {
                csv += '"' + (reg.codigo_base || '') + '";';
                csv += '"' + (reg.nombre || '') + '";';
                csv += '"' + (reg.tipo_documento || '') + '";';
                csv += '"' + (reg.info_institucional || '') + '";';
                csv += '"' + (reg.etiqueta_persona || '') + '";';
                csv += '"' + (reg.etiqueta_tema || '') + '";';
                csv += '"' + (reg.mensaje_error || '') + '"\n';
            });
            
            // Descargar
            var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'convocatorias_rechazadas_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        });
        
        // Nueva carga
        document.getElementById('btnNuevaCarga').addEventListener('click', function() {
            location.reload();
        });
        
        // ============================================
        // UTILIDADES
        // ============================================
        
        function limpiarYReiniciar() {
            // Limpiar temporal via AJAX
            var formData = new FormData();
            formData.append('session_id', sessionId);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'limpiar-temporal-convocatorias.php', true);
            xhr.send(formData);
            
            location.reload();
        }
        
        function mostrarModal(texto) {
            document.getElementById('textoCargando').textContent = texto;
            modalCargando.show();
        }
        
        function ocultarModal() {
            modalCargando.hide();
        }
        
        function mostrarAlerta(tipo, mensaje) {
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
            alertDiv.innerHTML = '<i class="bi bi-' + (tipo === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' +
                mensaje + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            
            document.getElementById('alertaGlobal').appendChild(alertDiv);
            
            setTimeout(function() {
                alertDiv.remove();
            }, 5000);
        }
        
        function formatearTamano(bytes) {
            if (bytes < 1024) return bytes + ' bytes';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
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
