/**
 * Sistema de Carga Masiva de Diplomas
 * JavaScript Principal - Con soporte de Convocatorias
 * VERSION: 2026-01-29-FIX2 (codificación corregida)
 */
// console.log('app.js VERSION: 2026-01-29-FIX2 cargado');

(function() {
    'use strict';

    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    var sessionId = 'CARGA_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    var datosRegistros = [];
    var filtroActual = 'todos';
    var registrosNoCargados = [];
    var convocatoriaInfo = null;
    
    // Elementos DOM
    var uploadZone;
    var archivoInput;
    var formCarga;
    var btnProcesar;
    
    // ============================================
    // INICIALIZACION
    // ============================================
    function init() {
        uploadZone = document.getElementById('uploadZone');
        archivoInput = document.getElementById('archivoInput');
        formCarga = document.getElementById('formCarga');
        btnProcesar = document.getElementById('btnProcesar');
        
        if (!uploadZone || !formCarga) return; // No estamos en la página de carga
        
        document.getElementById('sessionId').value = sessionId;
        
        setupUploadZone();
        setupFormSubmit();
        setupButtons();
        setupFiltros();
    }
    
    // ============================================
    // ZONA DE UPLOAD - DRAG & DROP
    // ============================================
    function setupUploadZone() {
        uploadZone.addEventListener('click', function() {
            archivoInput.click();
        });
        
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                procesarArchivoSeleccionado(files[0]);
            }
        });
        
        archivoInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                procesarArchivoSeleccionado(this.files[0]);
            }
        });
        
        document.getElementById('btnCambiar').addEventListener('click', function(e) {
            e.stopPropagation();
            resetearUpload();
        });
    }
    
    function procesarArchivoSeleccionado(file) {
        var extension = file.name.split('.').pop().toLowerCase();
        var extensionesValidas = ['xlsx', 'csv'];
        
        if (extensionesValidas.indexOf(extension) === -1) {
            mostrarAlerta('danger', 'Formato no válido. Solo se aceptan archivos .xlsx o .csv');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            mostrarAlerta('danger', 'El archivo excede el tamaño máximo de 5MB');
            return;
        }
        
        document.getElementById('uploadZone').classList.add('d-none');
        document.getElementById('archivoInfo').classList.remove('d-none');
        document.getElementById('nombreArchivo').textContent = file.name;
        document.getElementById('infoArchivo').textContent = 
            'Tamaño: ' + formatBytes(file.size) + ' | Tipo: ' + extension.toUpperCase();
        
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        archivoInput.files = dataTransfer.files;
        
        btnProcesar.disabled = false;
        ocultarAlerta();
    }
    
    function resetearUpload() {
        document.getElementById('uploadZone').classList.remove('d-none');
        document.getElementById('archivoInfo').classList.add('d-none');
        archivoInput.value = '';
        btnProcesar.disabled = true;
    }
    
    // ============================================
    // ENVÃO DEL FORMULARIO
    // ============================================
    function setupFormSubmit() {
        formCarga.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verificar que hay convocatoria seleccionada
            var convocatoriaId = document.getElementById('convocatoriaId').value;
            if (!convocatoriaId) {
                mostrarAlerta('danger', 'Debe seleccionar una convocatoria antes de procesar el archivo.');
                return;
            }
            
            var formData = new FormData(this);
            
            mostrarModalCargando('Procesando archivo...');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'procesar-carga.php', true);
            
            xhr.onload = function() {
                ocultarModalCargando();
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            datosRegistros = response.registros;
                            convocatoriaInfo = response.convocatoria;
                            mostrarPreview(response);
                        } else {
                            mostrarAlerta('danger', response.mensaje);
                        }
                    } catch (e) {
                        console.error('Error en procesamiento:', e);
                        console.error('Stack:', e.stack);
                        // console.log('Respuesta del servidor:', xhr.responseText.substring(0, 500));
                        mostrarAlerta('danger', 'Error al procesar: ' + e.message + ' (ver consola para detalles)');
                    }
                } else {
                    mostrarAlerta('danger', 'Error del servidor (código: ' + xhr.status + ')');
                }
            };
            
            xhr.onerror = function() {
                ocultarModalCargando();
                mostrarAlerta('danger', 'Error de conexión. Verifique su conexión a internet.');
            };
            
            xhr.send(formData);
        });
    }
    
    // ============================================
    // VISTA PREVIA
    // ============================================
    function mostrarPreview(response) {
        // console.log('=== mostrarPreview iniciado ===');
        
        // Actualizar pasos (con verificación null)
        var step1 = document.getElementById('step1');
        var step2 = document.getElementById('step2');
        var stepLine = document.querySelector('.step-line');
        var seccionUpload = document.getElementById('seccionUpload');
        var seccionPreview = document.getElementById('seccionPreview');
        var codigoBasePreview = document.getElementById('codigoBasePreview');
        var nombreConvPreview = document.getElementById('nombreConvPreview');
        var opcionesCarga = document.getElementById('opcionesCarga');
        var resumenCarga = document.getElementById('resumenCarga');
        var btnConfirmar = document.getElementById('btnConfirmar');
        
        // Debug: mostrar qué elementos existen
        // console.log('Elementos encontrados:', {
            step1: !!step1,
            step2: !!step2,
            stepLine: !!stepLine,
            seccionUpload: !!seccionUpload,
            seccionPreview: !!seccionPreview,
            codigoBasePreview: !!codigoBasePreview,
            nombreConvPreview: !!nombreConvPreview,
            opcionesCarga: !!opcionesCarga,
            resumenCarga: !!resumenCarga,
            btnConfirmar: !!btnConfirmar
        });
        
        // Verificar elementos críticos
        if (!seccionPreview) {
            console.error('ERROR: seccionPreview no existe');
            mostrarAlerta('danger', 'Error: falta el elemento #seccionPreview en la página.');
            return;
        }
        if (!resumenCarga) {
            console.error('ERROR: resumenCarga no existe');
            mostrarAlerta('danger', 'Error: falta el elemento #resumenCarga en la página.');
            return;
        }
        
        // Actualizar pasos
        if (step1) {
            step1.classList.remove('active');
            step1.classList.add('completed');
        }
        if (step2) {
            step2.classList.add('active');
        }
        if (stepLine) {
            stepLine.classList.add('active');
        }
        
        if (seccionUpload) {
            seccionUpload.classList.add('d-none');
        }
        
        seccionPreview.classList.remove('d-none');
        seccionPreview.classList.add('fade-in');
        
        // console.log('Pasos actualizados OK');
        
        // Mostrar info de convocatoria
        if (convocatoriaInfo) {
            if (codigoBasePreview) {
                codigoBasePreview.textContent = convocatoriaInfo.codigo_base;
            }
            if (nombreConvPreview) {
                nombreConvPreview.textContent = convocatoriaInfo.nombre;
            }
        }
        
        // console.log('Info convocatoria OK');
        
        // Generar resumen
        var codigosInvalidos = response.codigos_invalidos || 0;
        var erroresOtros = response.errores - codigosInvalidos;
        
        var resumenHTML = '<div class="resumen-cards">' +
            '<div class="resumen-card total">' +
                '<div class="numero">' + response.total + '</div>' +
                '<div class="texto">Total registros</div>' +
            '</div>' +
            '<div class="resumen-card validos">' +
                '<div class="numero">' + response.validos + '</div>' +
                '<div class="texto">Válidos</div>' +
            '</div>';
        
        if (codigosInvalidos > 0) {
            resumenHTML += '<div class="resumen-card warning">' +
                '<div class="numero">' + codigosInvalidos + '</div>' +
                '<div class="texto">Código inválido</div>' +
            '</div>';
        }
        
        if (erroresOtros > 0) {
            resumenHTML += '<div class="resumen-card errores">' +
                '<div class="numero">' + erroresOtros + '</div>' +
                '<div class="texto">Otros errores</div>' +
            '</div>';
        }
        
        resumenHTML += '</div>';
        
        // Alertas según resultado
        if (codigosInvalidos > 0 && convocatoriaInfo) {
            resumenHTML += '<div class="alert alert-warning mt-3">' +
                '<i class="bi bi-exclamation-triangle me-2"></i>' +
                '<strong>' + codigosInvalidos + ' registro(s)</strong> tienen códigos que no coinciden con la convocatoria <code>' + convocatoriaInfo.codigo_base + '</code>.' +
            '</div>';
            
            // Mostrar opciones de carga
            if (opcionesCarga) {
                opcionesCarga.classList.remove('d-none');
            }
        } else {
            if (opcionesCarga) {
                opcionesCarga.classList.add('d-none');
            }
        }
        
        if (erroresOtros > 0) {
            resumenHTML += '<div class="alert alert-danger mt-3">' +
                '<i class="bi bi-x-circle me-2"></i>' +
                '<strong>' + erroresOtros + ' registro(s)</strong> tienen errores críticos (código vacío, duplicado, etc.) y no podrán ser cargados.' +
            '</div>';
        }
        
        if (response.validos > 0 && response.errores === 0) {
            resumenHTML += '<div class="alert alert-success mt-3">' +
                '<i class="bi bi-check-circle me-2"></i>' +
                'Todos los registros son válidos y están listos para ser cargados.' +
            '</div>';
        }
        
        resumenCarga.innerHTML = resumenHTML;
        
        // console.log('Resumen generado OK');
        
        // Poblar tabla
        poblarTablaPreview(response.registros);
        
        // console.log('Tabla poblada OK');
        
        // Habilitar/deshabilitar botón confirmar
        if (btnConfirmar) {
            btnConfirmar.disabled = (response.validos <= 0);
        }
        
        // console.log('=== mostrarPreview completado ===');
    }
    
    function poblarTablaPreview(registros) {
        var tbody = document.getElementById('tbodyPreview');
        var html = '';
        
        for (var i = 0; i < registros.length; i++) {
            var reg = registros[i];
            var rowClass = '';
            var estadoBadge = '';
            
            if (reg.estado === 'valido') {
                rowClass = 'table-success';
                estadoBadge = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Válido</span>';
            } else if (reg.estado === 'codigo_invalido') {
                rowClass = 'table-warning';
                estadoBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Código</span>';
            } else {
                rowClass = 'table-danger';
                estadoBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Error</span>';
            }
            
            html += '<tr class="' + rowClass + '" data-estado="' + reg.estado + '">' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + estadoBadge + '</td>' +
                '<td><code>' + escapeHtml(reg.codigo) + '</code></td>' +
                '<td>' + escapeHtml(truncar(reg.autores, 50)) + '</td>' +
                '<td>' + escapeHtml(truncar(reg.tema, 60)) + '</td>' +
                '<td><small class="text-muted">' + escapeHtml(reg.mensaje_error || '-') + '</small></td>' +
            '</tr>';
        }
        
        tbody.innerHTML = html || '<tr><td colspan="6" class="text-center text-muted">No hay registros</td></tr>';
    }
    
    // ============================================
    // FILTROS DE TABLA
    // ============================================
    function setupFiltros() {
        var btnTodos = document.getElementById('btnFiltroTodos');
        var btnValidos = document.getElementById('btnFiltroValidos');
        var btnErrores = document.getElementById('btnFiltroErrores');
        
        if (!btnTodos) return;
        
        btnTodos.addEventListener('click', function() {
            filtrarTabla('todos');
            actualizarBotonesFiltro(this);
        });
        
        btnValidos.addEventListener('click', function() {
            filtrarTabla('valido');
            actualizarBotonesFiltro(this);
        });
        
        btnErrores.addEventListener('click', function() {
            filtrarTabla('error');
            actualizarBotonesFiltro(this);
        });
    }
    
    function filtrarTabla(filtro) {
        var filas = document.querySelectorAll('#tbodyPreview tr');
        
        filas.forEach(function(fila) {
            var estado = fila.getAttribute('data-estado');
            
            if (filtro === 'todos') {
                fila.style.display = '';
            } else if (filtro === 'valido') {
                fila.style.display = estado === 'valido' ? '' : 'none';
            } else if (filtro === 'error') {
                fila.style.display = (estado === 'error' || estado === 'codigo_invalido') ? '' : 'none';
            }
        });
    }
    
    function actualizarBotonesFiltro(botonActivo) {
        document.querySelectorAll('.btn-group .btn').forEach(function(btn) {
            btn.classList.remove('active');
        });
        botonActivo.classList.add('active');
    }
    
    // ============================================
    // BOTONES DE ACCIÃ“N
    // ============================================
    function setupButtons() {
        document.getElementById('btnCancelar').addEventListener('click', function() {
            if (!confirm('¿Está seguro de cancelar? Se perderán los datos precargados.')) {
                return;
            }
            
            var formData = new FormData();
            formData.append('session_id', sessionId);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'limpiar-temporal.php', true);
            xhr.onload = function() {
                nuevaCarga();
            };
            xhr.send(formData);
        });
        
        document.getElementById('btnConfirmar').addEventListener('click', function() {
            confirmarCarga();
        });
        
        document.getElementById('btnNuevaCarga').addEventListener('click', function() {
            nuevaCarga();
        });
    }
    
    // ============================================
    // CONFIRMAR CARGA
    // ============================================
    function confirmarCarga() {
        mostrarModalCargando('Insertando registros...');
        
        var formData = new FormData();
        formData.append('session_id', sessionId);
        
        // Obtener tipo de carga si hay opciones visibles
        var opcionesCarga = document.getElementById('opcionesCarga');
        if (opcionesCarga && !opcionesCarga.classList.contains('d-none')) {
            var tipoCarga = document.querySelector('input[name="tipoCarga"]:checked');
            formData.append('tipo_carga', tipoCarga ? tipoCarga.value : 'parcial');
        } else {
            formData.append('tipo_carga', 'parcial');
        }
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'confirmar-carga.php', true);
        
        xhr.onload = function() {
            ocultarModalCargando();
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    mostrarResultadoFinal(response);
                } catch (e) {
                    mostrarAlerta('danger', 'Error al procesar la respuesta: ' + e.message);
                }
            } else {
                mostrarAlerta('danger', 'Error del servidor (código: ' + xhr.status + ')');
            }
        };
        
        xhr.onerror = function() {
            ocultarModalCargando();
            mostrarAlerta('danger', 'Error de conexión.');
        };
        
        xhr.send(formData);
    }
    
    // ============================================
    // RESULTADO FINAL
    // ============================================
    function mostrarResultadoFinal(response) {
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step2').classList.add('completed');
        document.getElementById('step3').classList.add('active');
        var stepLines = document.querySelectorAll('.step-line');
        if (stepLines[1]) stepLines[1].classList.add('active');
        
        document.getElementById('seccionPreview').classList.add('d-none');
        
        var seccionResultado = document.getElementById('seccionResultado');
        seccionResultado.classList.remove('d-none');
        seccionResultado.classList.add('fade-in');
        
        var html = '';
        
        if (response.success) {
            html = '<div class="resultado-exito">' +
                '<div class="icono"><i class="bi bi-check-circle-fill"></i></div>' +
                '<h3>¡Carga Completada Exitosamente!</h3>' +
                '<p class="lead">Se han insertado <strong>' + response.insertados + '</strong> diploma(s) en el sistema.</p>' +
                '<p class="text-muted">Los registros ya están disponibles para validación.</p>' +
            '</div>';
            
            // Si hay registros no cargados, mostrar opción de descarga
            if (response.no_cargados && response.no_cargados > 0) {
                registrosNoCargados = response.registros_no_cargados;
                document.getElementById('reporteNoCargados').classList.remove('d-none');
                document.getElementById('btnDescargarReporte').addEventListener('click', function(e) {
                    e.preventDefault();
                    descargarReporteNoCargados();
                });
            }
        } else {
            html = '<div class="resultado-error">' +
                '<div class="icono"><i class="bi bi-x-circle-fill"></i></div>' +
                '<h3>Error en la Carga</h3>' +
                '<p class="lead">' + escapeHtml(response.mensaje) + '</p>' +
            '</div>';
        }
        
        document.getElementById('resultadoFinal').innerHTML = html;
    }
    
    // ============================================
    // DESCARGAR REPORTE DE NO CARGADOS
    // ============================================
    function descargarReporteNoCargados() {
        if (!registrosNoCargados || registrosNoCargados.length === 0) {
            mostrarAlerta('warning', 'No hay registros para descargar.');
            return;
        }
        
        // Crear contenido CSV
        var csv = 'codigo;autores;tema;motivo_rechazo\n';
        
        for (var i = 0; i < registrosNoCargados.length; i++) {
            var reg = registrosNoCargados[i];
            csv += '"' + (reg.codigo || '').replace(/"/g, '""') + '";';
            csv += '"' + (reg.autores || '').replace(/"/g, '""') + '";';
            csv += '"' + (reg.tema || '').replace(/"/g, '""') + '";';
            csv += '"' + (reg.mensaje_error || '').replace(/"/g, '""') + '"\n';
        }
        
        // Crear blob y descargar
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', 'registros_no_cargados_' + new Date().toISOString().slice(0, 10) + '.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // ============================================
    // NUEVA CARGA
    // ============================================
    function nuevaCarga() {
        // Recargar página para resetear completamente
        window.location.reload();
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    function mostrarAlerta(tipo, mensaje) {
        var alertaDiv = document.getElementById('alertaGlobal');
        if (!alertaDiv) {
            // Fallback: crear el elemento si no existe
            alertaDiv = document.createElement('div');
            alertaDiv.id = 'alertaGlobal';
            var container = document.querySelector('.container');
            if (container) {
                container.insertBefore(alertaDiv, container.firstChild);
            } else {
                document.body.insertBefore(alertaDiv, document.body.firstChild);
            }
        }
        alertaDiv.innerHTML = '<div class="alert alert-' + tipo + ' alert-dismissible fade show">' +
            '<i class="bi bi-' + (tipo === 'danger' ? 'exclamation-triangle' : 'info-circle') + ' me-2"></i>' +
            mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>';
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    function ocultarAlerta() {
        var alertaDiv = document.getElementById('alertaGlobal');
        if (alertaDiv) {
            alertaDiv.innerHTML = '';
        }
    }
    
    function mostrarModalCargando(texto) {
        document.getElementById('textoCargando').textContent = texto || 'Procesando...';
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCargando'));
        modal.show();
    }
    
    function ocultarModalCargando() {
        var modalElement = document.getElementById('modalCargando');
        var modal = bootstrap.Modal.getInstance(modalElement);
        
        if (modal) {
            modal.hide();
        }
        
        setTimeout(function() {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.body.style.removeProperty('overflow');
            
            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(backdrop) {
                backdrop.remove();
            });
            
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.removeAttribute('aria-modal');
            modalElement.removeAttribute('role');
        }, 150);
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
    
    function truncar(texto, maxLength) {
        if (!texto) return '';
        if (texto.length <= maxLength) return texto;
        return texto.substring(0, maxLength) + '...';
    }
    
    // ============================================
    // INICIAR
    // ============================================
    document.addEventListener('DOMContentLoaded', init);
    
})();
