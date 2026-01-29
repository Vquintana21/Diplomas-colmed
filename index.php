<?php
require_once 'auth.php';
requerirAutenticacion();
$usuario = obtenerUsuarioActual();

// Cargar convocatorias activas
require_once 'config.php';
$query = "SELECT id, codigo_base, nombre, tipo_documento FROM z_convocatorias WHERE activo = 1 ORDER BY fecha_creacion DESC";
$result = $conn->query($query);
$convocatorias = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $convocatorias[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Carga Masiva de Diplomas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light" data-pagina="carga-diplomas">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-institucional">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                Carga Masiva de Diplomas
            </a>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($usuario['rol'] === 'admin'): ?>
                <a href="convocatorias.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-folder me-1"></i>
                    Convocatorias
                </a>
                <?php endif; ?>
                <a href="listado.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-list-ul me-1"></i>
                    Listado
                </a>
                <a href="validador.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-patch-check me-1"></i>
                    Validador
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
        
        <div id="alertaGlobal"></div>
        
        <div class="card shadow-lg border-0 rounded-4">
            
            <div class="card-header card-header-institucional py-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="mb-1">
                            <i class="bi bi-cloud-upload me-2"></i>
                            Importar Diplomas
                        </h4>
                        <p class="mb-0 opacity-75">Cargue diplomas desde un archivo Excel o CSV</p>
                    </div>
					
                    <div class="col-auto">
                        <span class="badge text-primary fs-6" id="sessionBadge">
                           <a href="plantillas/Instructivo.pdf" class="btn btn-sm btn-warning" target="_blank">
                                            <i class="bi bi-download me-1"></i> Instrucciones
                                        </a>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                
                <!-- Indicador de Pasos -->
                <div class="steps-container mb-5">
                    <div class="d-flex justify-content-center">
                        <div class="step active" id="step1">
                            <div class="step-number">1</div>
                            <div class="step-label">Seleccionar y Subir</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" id="step2">
                            <div class="step-number">2</div>
                            <div class="step-label">Validar Datos</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" id="step3">
                            <div class="step-number">3</div>
                            <div class="step-label">Confirmar</div>
                        </div>
                    </div>
                </div>
                
                <!-- ========================================= -->
                <!-- SECCIÓN 1: SELECCIONAR CONVOCATORIA Y SUBIR -->
                <!-- ========================================= -->
                <div id="seccionUpload">
                    
                    <?php if (empty($convocatorias)): ?>
                    <!-- Sin convocatorias -->
                    <div class="text-center py-5">
                        <i class="bi bi-folder-x display-1 text-muted"></i>
                        <h4 class="mt-3">No hay convocatorias activas</h4>
                        <p class="text-muted">Debe crear una convocatoria antes de cargar diplomas.</p>
                        <?php if ($usuario['rol'] === 'admin'): ?>
                        <a href="convocatoria-form.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>
                            Crear Convocatoria
                        </a>
                        <?php else: ?>
                        <p class="text-muted">Contacte al administrador para crear una convocatoria.</p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    
                    <!-- Paso 1a: Seleccionar Convocatoria -->
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="bi bi-1-circle-fill text-primary me-2"></i>
                            Seleccione la Convocatoria
                        </h5>
                        
                        <div class="row g-3" id="listaConvocatorias">
                            <?php foreach ($convocatorias as $conv): ?>
                            <div class="col-md-6">
                                <div class="card card-seleccion h-100" 
                                     data-id="<?php echo $conv['id']; ?>"
                                     data-codigo="<?php echo htmlspecialchars($conv['codigo_base']); ?>"
                                     data-nombre="<?php echo htmlspecialchars($conv['nombre']); ?>"
                                     onclick="seleccionarConvocatoria(this)">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span class="codigo-base"><?php echo htmlspecialchars($conv['codigo_base']); ?></span>
                                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($conv['tipo_documento']); ?></span>
                                            </div>
                                            <i class="bi bi-check-circle-fill text-primary d-none check-icon"></i>
                                        </div>
                                        <p class="mb-0 mt-2 small"><?php echo htmlspecialchars($conv['nombre']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Info de convocatoria seleccionada -->
                        <div id="convocatoriaSeleccionada" class="d-none mt-4">
                            <div class="codigo-info-box">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <small class="opacity-75">Convocatoria seleccionada:</small>
                                        <div class="codigo">
                                            <span id="codigoBaseDisplay"></span><span class="correlativo">XXX</span>
                                        </div>
                                        <small id="nombreConvDisplay" class="opacity-75"></small>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-outline-light btn-sm" onclick="cambiarConvocatoria()">
                                            <i class="bi bi-arrow-repeat"></i> Cambiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning border-0 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Importante:</strong> Los códigos del archivo deben iniciar con <code id="codigoBaseAlert"></code> seguido de 3 dígitos (001-999).
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Paso 1b: Subir Archivo -->
                    <div id="seccionSubirArchivo" class="d-none">
                        <h5 class="mb-3">
                            <i class="bi bi-2-circle-fill text-primary me-2"></i>
                            Suba el Archivo
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
                                            <li>Columnas requeridas: <code>codigo</code>, <code>autores</code>, <code>tema</code> <small class="text-muted">(tema opcional)</small></li>
											<li>CSV: usar <strong>punto y coma (;)</strong> como separador</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                           <div class="col-lg-6">
                            <div class="card bg-success bg-opacity-10 border-success h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-success">
                                        <i class="bi bi-download me-2"></i>
                                        Plantilla de Ejemplo
                                    </h6>
                                    <p class="small mb-3">Descargue la plantilla con el formato correcto:</p>
                                    <div class="btn-group">
                                        <a href="descargar.php?tipo=csv" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-filetype-csv me-1"></i> CSV
                                        </a>
                                        <a href="descargar.php?tipo=xlsx" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-file-earmark-excel me-1"></i> Excel
                                        </a>
										 <a href="plantillas/Instructivo.pdf" class="btn btn-sm btn-outline-info" target="_blank">
                                            <i class="bi bi-download me-1"></i> Instrucciones
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        
                        <!-- Zona de Upload -->
                        <form id="formCarga" enctype="multipart/form-data">
                            <input type="hidden" name="session_id" id="sessionId">
                            <input type="hidden" name="convocatoria_id" id="convocatoriaId">
                            
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
                    
                    <?php endif; ?>
                </div>
                
                <!-- ========================================= -->
                <!-- SECCIÓN 2: VISTA PREVIA Y VALIDACIÓN -->
                <!-- ========================================= -->
                <div id="seccionPreview" class="d-none">
                    
                    <!-- Info de convocatoria -->
                    <div id="infoConvocatoriaPreview" class="codigo-preview mb-4">
                        <small class="opacity-75">Convocatoria:</small>
                        <div class="codigo">
                            <span id="codigoBasePreview"></span>
                        </div>
                        <small id="nombreConvPreview" class="opacity-75"></small>
                    </div>
                    
                    <!-- Resumen -->
                    <div id="resumenCarga" class="mb-4"></div>
                    
                    <!-- Opciones de carga (si hay errores de código) 
                    <div id="opcionesCarga" class="d-none mb-4">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Hay registros con códigos inválidos</strong>
                            </div>
                            <div class="card-body">
                                <p>Seleccione cómo desea proceder:</p>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="tipoCarga" id="cargaTotal" value="total">
                                    <label class="form-check-label" for="cargaTotal">
                                        <strong>Cargar TODOS los registros</strong>
                                        <small class="text-muted d-block">Incluye registros con códigos que no coinciden con la convocatoria</small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoCarga" id="cargaParcial" value="parcial" checked>
                                    <label class="form-check-label" for="cargaParcial">
                                        <strong>Cargar solo registros VÁLIDOS</strong>
                                        <small class="text-muted d-block">Se generará un reporte con los registros no cargados</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    
                    <!-- Tabla de Registros -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-table me-2"></i>
                                    Vista Previa de Datos
                                </h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary active" id="btnFiltroTodos">
                                        Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-success" id="btnFiltroValidos">
                                        <i class="bi bi-check-circle"></i> Válidos
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" id="btnFiltroErrores">
                                        <i class="bi bi-x-circle"></i> Errores
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="tablaPreview">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="50">#</th>
                                            <th width="90">Estado</th>
                                            <th width="150">Código</th>
                                            <th>Autores</th>
                                            <th>Tema</th>
                                            <th width="200">Observación</th>
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
                    <div id="resultadoFinal"></div>
                    
                    <!-- Reporte de no cargados -->
                    <div id="reporteNoCargados" class="d-none mt-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-text display-4 text-warning"></i>
                                <h5 class="mt-3">Registros No Cargados</h5>
                                <p class="text-muted">Descargue el reporte con los registros que no fueron cargados</p>
                                <a href="#" id="btnDescargarReporte" class="btn btn-warning">
                                    <i class="bi bi-download me-2"></i>
                                    Descargar Reporte CSV
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary btn-lg" id="btnNuevaCarga">
                            <i class="bi bi-plus-lg me-2"></i>
                            Nueva Carga
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted">
            <small>
                Sistema de Carga Masiva de Diplomas &copy; 2026
            </small>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Variables globales para convocatoria
        let convocatoriaSeleccionadaId = null;
        let convocatoriaSeleccionadaCodigo = null;
        let convocatoriaSeleccionadaNombre = null;
        
        function seleccionarConvocatoria(elemento) {
            // Quitar selección anterior
            document.querySelectorAll('.convocatoria-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('.check-icon').classList.add('d-none');
            });
            
            // Marcar seleccionada
            elemento.classList.add('selected');
            elemento.querySelector('.check-icon').classList.remove('d-none');
            
            // Guardar datos
            convocatoriaSeleccionadaId = elemento.dataset.id;
            convocatoriaSeleccionadaCodigo = elemento.dataset.codigo;
            convocatoriaSeleccionadaNombre = elemento.dataset.nombre;
            
            // Actualizar hidden input
            document.getElementById('convocatoriaId').value = convocatoriaSeleccionadaId;
            
            // Mostrar info y sección de subir archivo
            document.getElementById('convocatoriaSeleccionada').classList.remove('d-none');
            document.getElementById('codigoBaseDisplay').textContent = convocatoriaSeleccionadaCodigo;
            document.getElementById('nombreConvDisplay').textContent = convocatoriaSeleccionadaNombre;
            document.getElementById('codigoBaseAlert').textContent = convocatoriaSeleccionadaCodigo;
            
            document.getElementById('seccionSubirArchivo').classList.remove('d-none');
            
            // Ocultar lista de convocatorias
            document.getElementById('listaConvocatorias').classList.add('d-none');
        }
        
        function cambiarConvocatoria() {
            // Limpiar selección
            convocatoriaSeleccionadaId = null;
            convocatoriaSeleccionadaCodigo = null;
            convocatoriaSeleccionadaNombre = null;
            document.getElementById('convocatoriaId').value = '';
            
            // Quitar selección visual
            document.querySelectorAll('.convocatoria-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('.check-icon').classList.add('d-none');
            });
            
            // Ocultar secciones
            document.getElementById('convocatoriaSeleccionada').classList.add('d-none');
            document.getElementById('seccionSubirArchivo').classList.add('d-none');
            
            // Mostrar lista
            document.getElementById('listaConvocatorias').classList.remove('d-none');
            
            // Limpiar archivo si había uno seleccionado
            document.getElementById('archivoInput').value = '';
            document.getElementById('archivoInfo').classList.add('d-none');
            document.getElementById('uploadZone').classList.remove('d-none');
            document.getElementById('btnProcesar').disabled = true;
        }
    </script>
    
</body>
</html>
