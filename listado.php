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
    <title>Listado de Diplomas</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <!-- Estilos Institucionales -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        /* Personalizaciones específicas para listado */
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.15);
        }
        table.dataTable thead th {
            background: var(--color-secundario);
            color: white;
            font-weight: 600;
        }
        table.dataTable tbody tr:hover {
            background-color: rgba(26, 82, 118, 0.1) !important;
        }
        .stats-card {
            border-radius: var(--radio-medio);
            padding: 20px;
            color: white;
            text-align: center;
        }
        .stats-card.total { background: var(--gradiente-primario); }
        .stats-card .numero { font-size: 2.5rem; font-weight: 700; }
        .stats-card .texto { opacity: 0.9; font-size: 0.9rem; }
    </style>
</head>
<body data-pagina="listado">

    <!-- Navbar -->
    <nav class="navbar navbar-institucional">
        <div class="container-fluid">
            <a class="navbar-brand" href="listado.php">
                <i class="bi bi-list-ul me-2"></i>
                Listado de Diplomas
            </a>
            <div class="d-flex gap-2 align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-cloud-upload me-1"></i>
                    Carga Masiva
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

    <div class="container-fluid py-4">
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4 col-lg-3">
                <div class="stats-card total">
                    <div class="numero" id="totalRegistros">-</div>
                    <div class="texto">Total de Diplomas</div>
                </div>
            </div>
        </div>
        
        <!-- Card con DataTable -->
        <div class="card">
            <div class="card-header card-header-institucional py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>
                        Registros Oficiales
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm" onclick="recargarTabla()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaDiplomas" class="table table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Autores</th>
                                <th>Tema</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Datos cargados via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-4 text-muted">
            <small>Sistema de Gestión de Diplomas &copy; 2026</small>
        </div>
        
    </div>
    
    <!-- Modal Ver Detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-institucional">
                    <h5 class="modal-title">
                        <i class="bi bi-eye me-2"></i>
                        Detalle del Diploma
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">CÓDIGO</label>
                        <div class="fs-5"><code id="detalleCodigo"></code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">AUTOR(ES)</label>
                        <div id="detalleAutores"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">TEMA / TRABAJO</label>
                        <div id="detalleTema" style="text-align: justify;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar el siguiente diploma?</p>
                    <div class="alert alert-warning">
                        <strong>Código:</strong> <code id="eliminarCodigo"></code>
                    </div>
                    <p class="text-danger mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">
                        <i class="bi bi-trash me-1"></i>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <script>
    var tabla;
    var idEliminar = null;
    
    $(document).ready(function() {
        // Inicializar DataTable
        tabla = $('#tablaDiplomas').DataTable({
            ajax: {
                url: 'api-diplomas.php?action=listar',
                dataSrc: 'data'
            },
            columns: [
                { 
                    data: 'id',
                    width: '60px'
                },
                { 
                    data: 'codigo',
                    render: function(data) {
                        return '<span class="badge bg-primary badge-codigo">' + escapeHtml(data) + '</span>';
                    }
                },
                { 
                    data: 'autores',
                    render: function(data) {
                        var texto = escapeHtml(data);
                        if (texto.length > 60) {
                            return '<span class="text-truncate-table d-inline-block" title="' + texto + '">' + texto.substring(0, 60) + '...</span>';
                        }
                        return texto;
                    }
                },
                { 
                    data: 'tema',
                    render: function(data) {
                        var texto = escapeHtml(data);
                        if (texto.length > 80) {
                            return '<span class="text-truncate-table d-inline-block" title="' + texto + '">' + texto.substring(0, 80) + '...</span>';
                        }
                        return texto;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: '120px',
                    render: function(data, type, row) {
                        return '<div class="btn-group btn-group-sm">' +
                            '<button type="button" class="btn btn-info btn-action" onclick="verDetalle(' + row.id + ', \'' + escapeHtml(row.codigo) + '\', \'' + escapeJs(row.autores) + '\', \'' + escapeJs(row.tema) + '\')" title="Ver detalle">' +
                                '<i class="bi bi-eye"></i>' +
                            '</button>' +
                            '<button type="button" class="btn btn-danger btn-action" onclick="confirmarEliminar(' + row.id + ', \'' + escapeHtml(row.codigo) + '\')" title="Eliminar">' +
                                '<i class="bi bi-trash"></i>' +
                            '</button>' +
                        '</div>';
                    }
                }
            ],
            order: [[0, 'desc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>i',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Diplomas',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv me-1"></i> CSV',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Diplomas',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Listado de Diplomas',
                    exportOptions: {
                        columns: [0, 1, 2, 3]
                    }
                }
            ],
            responsive: true,
            processing: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            initComplete: function(settings, json) {
                if (json && json.total !== undefined) {
                    $('#totalRegistros').text(json.total);
                }
            }
        });
        
        // Evento confirmar eliminación
        $('#btnConfirmarEliminar').on('click', function() {
            if (idEliminar) {
                eliminarDiploma(idEliminar);
            }
        });
    });
    
    // Recargar tabla
    function recargarTabla() {
        tabla.ajax.reload(function(json) {
            if (json && json.total !== undefined) {
                $('#totalRegistros').text(json.total);
            }
        });
    }
    
    // Ver detalle
    function verDetalle(id, codigo, autores, tema) {
        $('#detalleCodigo').text(codigo);
        $('#detalleAutores').text(autores);
        $('#detalleTema').text(tema);
        
        var modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
        modal.show();
    }
    
    // Confirmar eliminación
    function confirmarEliminar(id, codigo) {
        idEliminar = id;
        $('#eliminarCodigo').text(codigo);
        
        var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modal.show();
    }
    
    // Eliminar diploma
    function eliminarDiploma(id) {
        var btn = $('#btnConfirmarEliminar');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Eliminando...');
        
        $.ajax({
            url: 'api-diplomas.php',
            method: 'POST',
            data: {
                action: 'eliminar',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Eliminar');
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('modalEliminar')).hide();
                
                if (response.success) {
                    // Recargar tabla
                    recargarTabla();
                    
                    // Mostrar mensaje
                    mostrarToast('success', 'Diploma eliminado correctamente');
                } else {
                    mostrarToast('danger', response.mensaje || 'Error al eliminar');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i>Eliminar');
                mostrarToast('danger', 'Error de conexión');
            }
        });
        
        idEliminar = null;
    }
    
    // Mostrar toast
    function mostrarToast(tipo, mensaje) {
        var alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
        var icon = tipo === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle';
        
        var toast = $('<div class="alert ' + alertClass + ' alert-dismissible fade show position-fixed" style="top: 80px; right: 20px; z-index: 9999; min-width: 300px;">' +
            '<i class="bi ' + icon + ' me-2"></i>' + mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
        
        $('body').append(toast);
        
        setTimeout(function() {
            toast.alert('close');
        }, 3000);
    }
    
    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
    
    // Escape para JavaScript strings
    function escapeJs(text) {
        if (!text) return '';
        return text.replace(/\\/g, '\\\\')
                   .replace(/'/g, "\\'")
                   .replace(/"/g, '\\"')
                   .replace(/\n/g, '\\n')
                   .replace(/\r/g, '\\r');
    }
    </script>
    
</body>
</html>
