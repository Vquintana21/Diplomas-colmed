/**
 * app.js
 * JavaScript para el sistema de carga masiva de diplomas
 * Solo carga parcial (registros válidos)
 */

(function() {
    'use strict';
    
    // ============================================
    // VARIABLES GLOBALES
    // ============================================
    var sessionId = '';
    var datosRegistros = [];
    var registrosNoCargados = [];
    var modalCargando = null;
    
    // ============================================
    // INICIALIZACIÓN
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Generar session ID único
        sessionId = 'CARGA_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        var sessionIdInput = document.getElementById('sessionId');
        if (sessionIdInput) {
            sessionIdInput.value = sessionId;
        }
        
        // Badge de sesión
        var sessionBadge = document.getElementById('sessionBadge');
        if (sessionBadge && sessionBadge.querySelector('span')) {
            // No modificar si tiene otro contenido
        }
        
        // Inicializar modal
        var modalEl = document.getElementById('modalCargando');
        if (modalEl) {
            modalCargando = new bootstrap.Modal(modalEl);
        }
        
        // Inicializar eventos
        inicializarEventos();
    });
    
    // ============================================
    // EVENTOS
    // ============================================
    function inicializarEventos() {
        var uploadZone = document.getElementById('uploadZone');
        var archivoInput = document.getElementById('archivoInput');
        var formCarga = document.getElementById('formCarga');
        var btnCambiar = document.getElementById('btnCambiar');
        var btnCancelar = document.getElementById('btnCancelar');
        var btnConfirmar = document.getElementById('btnConfirmar');
        var btnNuevaCarga = document.getElementById('btnNuevaCarga');
        var btnDescargarReporte = document.getElementById('btnDescargarReporte');
        
        // Upload zone click
        if (uploadZone) {
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
                    manejarArchivoSeleccionado(e.dataTransfer.files[0]);
                }
            });
        }
        
        // Archivo seleccionado
        if (archivoInput) {
            archivoInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    manejarArchivoSeleccionado(this.files[0]);
                }
            });
        }
        
        // Cambiar archivo
        if (btnCambiar) {
            btnCambiar.addEventListener('click', function() {
                archivoInput.value = '';
                document.getElementById('uploadZone').classList.remove('d-none');
                document.getElementById('archivoInfo').classList.add('d-none');
                document.getElementById('btnProcesar').disabled = true;
            });
        }
        
        // Procesar archivo
        if (formCarga) {
            formCarga.addEventListener('submit', function(e) {
                e.preventDefault();
                procesarArchivo();
            });
        }
        
        // Cancelar
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                if (confirm('¿Está seguro de cancelar? Se perderán los datos procesados.')) {
                    limpiarYReiniciar();
                }
            });
        }
        
        // Confirmar carga
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function() {
                confirmarCarga();
            });
        }
        
        // Nueva carga
        if (btnNuevaCarga) {
            btnNuevaCarga.addEventListener('click', function() {
                location.reload();
            });
        }
        
        // Descargar reporte
        if (btnDescargarReporte) {
            btnDescargarReporte.addEventListener('click', function(e) {
                e.preventDefault();
                descargarReporteCSV();
            });
        }
        
        // Filtros de tabla
        var btnFiltroTodos = document.getElementById('btnFiltroTodos');
        var btnFiltroValidos = document.getElementById('btnFiltroValidos');
        var btnFiltroErrores = document.getElementById('btnFiltroErrores');
        
        if (btnFiltroTodos) {
            btnFiltroTodos.addEventListener('click', function() {
                activarFiltro(this, 'todos');
            });
        }
        
        if (btnFiltroValidos) {
            btnFiltroValidos.addEventListener('click', function() {
                activarFiltro(this, 'valido');
            });
        }
        
        if (btnFiltroErrores) {
            btnFiltroErrores.addEventListener('click', function() {
                activarFiltro(this, 'error');
            });
        }
    }
    
    // ============================================
    // MANEJO DE ARCHIVO
    // ============================================
    function manejarArchivoSeleccionado(file) {
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
        
        document.getElementById('uploadZone').classList.add('d-none');
        document.getElementById('archivoInfo').classList.remove('d-none');
        document.getElementById('btnProcesar').disabled = false;
    }
    
    // ============================================
    // PROCESAR ARCHIVO
    // ============================================
    function procesarArchivo() {
        mostrarModal('Procesando archivo...');
        
        var formCarga = document.getElementById('formCarga');
        var formData = new FormData(formCarga);
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'procesar-carga.php', true);
        
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
            mostrarAlerta('danger', 'Error de conexión. Intente nuevamente.');
        };
        
        xhr.send(formData);
    }
    
    // ============================================
    // MOSTRAR PREVIEW
    // ============================================
    function mostrarPreview(data) {
        // Actualizar pasos
        actualizarPasos(2);
        
        // Ocultar sección upload
        document.getElementById('seccionUpload').classList.add('d-none');
        
        // Mostrar sección preview
        document.getElementById('seccionPreview').classList.remove('d-none');
        
        // Info de convocatoria
        if (data.convocatoria) {
            document.getElementById('codigoBasePreview').textContent = data.convocatoria.codigo_base;
            document.getElementById('nombreConvPreview').textContent = data.convocatoria.nombre;
        }
        
        // Generar resumen
        var resumenHtml = 
            '<div class="row">' +
                '<div class="col-md-4 mb-2">' +
                    '<div class="stats-box total">' +
                        '<div class="numero">' + data.total + '</div>' +
                        '<div>Total Registros</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4 mb-2">' +
                    '<div class="stats-box validos">' +
                        '<div class="numero">' + data.validos + '</div>' +
                        '<div>Válidos</div>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4 mb-2">' +
                    '<div class="stats-box errores">' +
                        '<div class="numero">' + data.errores + '</div>' +
                        '<div>Con Errores</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        
        document.getElementById('resumenCarga').innerHTML = resumenHtml;
        
        // Mostrar alerta si hay errores
        var alertaErrores = document.getElementById('alertaErrores');
        if (alertaErrores) {
            if (data.errores > 0 || data.codigos_invalidos > 0) {
                alertaErrores.classList.remove('d-none');
            } else {
                alertaErrores.classList.add('d-none');
            }
        }
        
        // Llenar tabla
        renderizarTablaPreview(data.registros, 'todos');
        
        // Deshabilitar confirmación si no hay válidos
        if (data.validos === 0) {
            document.getElementById('btnConfirmar').disabled = true;
        }
    }
    
    // ============================================
    // RENDERIZAR TABLA
    // ============================================
    function renderizarTablaPreview(registros, filtro) {
        var tbody = document.getElementById('tbodyPreview');
        tbody.innerHTML = '';
        
        registros.forEach(function(reg, idx) {
            // Aplicar filtro
            if (filtro !== 'todos') {
                if (filtro === 'valido' && reg.estado !== 'valido') return;
                if (filtro === 'error' && reg.estado === 'valido') return;
            }
            
            var esValido = reg.estado === 'valido';
            var badgeClass = esValido ? 'bg-success' : 'bg-danger';
            var badgeText = esValido ? 'Válido' : 'Error';
            var badgeIcon = esValido ? 'check-circle' : 'x-circle';
            
            var tr = document.createElement('tr');
            tr.innerHTML = 
                '<td>' + (idx + 1) + '</td>' +
                '<td><span class="badge ' + badgeClass + '"><i class="bi bi-' + badgeIcon + ' me-1"></i>' + badgeText + '</span></td>' +
                '<td><code>' + escapeHtml(reg.codigo) + '</code></td>' +
                '<td>' + escapeHtml(reg.autores) + '</td>' +
                '<td>' + escapeHtml(reg.tema || '-') + '</td>' +
                '<td><small class="text-' + (esValido ? 'muted' : 'danger') + '">' + escapeHtml(reg.mensaje_error || '-') + '</small></td>';
            
            tbody.appendChild(tr);
        });
    }
    
    function activarFiltro(btn, filtro) {
        // Quitar active de todos
        document.getElementById('btnFiltroTodos').classList.remove('active');
        document.getElementById('btnFiltroValidos').classList.remove('active');
        document.getElementById('btnFiltroErrores').classList.remove('active');
        
        // Activar el seleccionado
        btn.classList.add('active');
        
        // Renderizar con filtro
        renderizarTablaPreview(datosRegistros, filtro);
    }
    
    // ============================================
    // CONFIRMAR CARGA
    // ============================================
    function confirmarCarga() {
        // Contar válidos
        var validos = datosRegistros.filter(function(r) { return r.estado === 'valido'; }).length;
        var errores = datosRegistros.filter(function(r) { return r.estado !== 'valido'; }).length;
        
        var mensaje = '¿Confirma la carga de ' + validos + ' registro(s) válido(s)?';
        if (errores > 0) {
            mensaje += '\n\n' + errores + ' registro(s) con errores NO serán cargados y se generará un reporte.';
        }
        
        if (!confirm(mensaje)) {
            return;
        }
        
        mostrarModal('Guardando diplomas...');
        
        var formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('tipo_carga', 'parcial'); // Siempre parcial
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'confirmar-carga.php', true);
        
        xhr.onload = function() {
            ocultarModal();
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    mostrarResultado(response);
                } catch (e) {
                    mostrarAlerta('danger', 'Error al procesar la respuesta');
                    console.error(e);
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
    // MOSTRAR RESULTADO
    // ============================================
    function mostrarResultado(data) {
        // Actualizar pasos
        actualizarPasos(3);
        
        // Ocultar preview
        document.getElementById('seccionPreview').classList.add('d-none');
        
        // Mostrar resultado
        document.getElementById('seccionResultado').classList.remove('d-none');
        
        var resultadoDiv = document.getElementById('resultadoFinal');
        
        if (data.success) {
            resultadoDiv.innerHTML = 
                '<div class="text-center py-4">' +
                    '<i class="bi bi-check-circle-fill text-success display-1"></i>' +
                    '<h3 class="mt-3 text-success">¡Carga Completada!</h3>' +
                    '<p class="text-muted">Se cargaron <strong>' + data.insertados + '</strong> diploma(s) correctamente.</p>' +
                '</div>';
            
            // Mostrar reporte de no cargados si hay
            if (data.registros_no_cargados && data.registros_no_cargados.length > 0) {
                registrosNoCargados = data.registros_no_cargados;
                document.getElementById('reporteNoCargados').classList.remove('d-none');
            }
        } else {
            resultadoDiv.innerHTML = 
                '<div class="text-center py-4">' +
                    '<i class="bi bi-x-circle-fill text-danger display-1"></i>' +
                    '<h3 class="mt-3 text-danger">Error en la Carga</h3>' +
                    '<p class="text-muted">' + escapeHtml(data.mensaje) + '</p>' +
                '</div>';
        }
    }
    
    // ============================================
    // DESCARGAR REPORTE CSV
    // ============================================
    function descargarReporteCSV() {
        if (registrosNoCargados.length === 0) {
            mostrarAlerta('warning', 'No hay registros para descargar');
            return;
        }
        
        // Generar CSV
        var csv = 'codigo;autores;tema;error\n';
        
        registrosNoCargados.forEach(function(reg) {
            csv += '"' + (reg.codigo || '') + '";';
            csv += '"' + (reg.autores || '').replace(/"/g, '""') + '";';
            csv += '"' + (reg.tema || '').replace(/"/g, '""') + '";';
            csv += '"' + (reg.mensaje_error || '').replace(/"/g, '""') + '"\n';
        });
        
        // Descargar
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'diplomas_rechazados_' + new Date().toISOString().slice(0, 10) + '.csv';
        link.click();
    }
    
    // ============================================
    // LIMPIAR Y REINICIAR
    // ============================================
    function limpiarYReiniciar() {
        // Limpiar temporal via AJAX
        var formData = new FormData();
        formData.append('session_id', sessionId);
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'limpiar-temporal.php', true);
        xhr.send(formData);
        
        // Recargar página
        location.reload();
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    function actualizarPasos(pasoActivo) {
        var step1 = document.getElementById('step1');
        var step2 = document.getElementById('step2');
        var step3 = document.getElementById('step3');
        
        if (!step1 || !step2 || !step3) return;
        
        // Resetear
        step1.classList.remove('active', 'completed');
        step2.classList.remove('active', 'completed');
        step3.classList.remove('active', 'completed');
        
        if (pasoActivo >= 1) step1.classList.add(pasoActivo > 1 ? 'completed' : 'active');
        if (pasoActivo >= 2) step2.classList.add(pasoActivo > 2 ? 'completed' : 'active');
        if (pasoActivo >= 3) step3.classList.add('active');
    }
    
    function mostrarModal(texto) {
        if (modalCargando) {
            document.getElementById('textoCargando').textContent = texto;
            modalCargando.show();
        }
    }
    
    function ocultarModal() {
        if (modalCargando) {
            modalCargando.hide();
        }
    }
    
    function mostrarAlerta(tipo, mensaje) {
        var alertaGlobal = document.getElementById('alertaGlobal');
        if (!alertaGlobal) return;
        
        var icono = tipo === 'success' ? 'check-circle' : (tipo === 'warning' ? 'exclamation-triangle' : 'exclamation-circle');
        
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
        alertDiv.innerHTML = '<i class="bi bi-' + icono + ' me-2"></i>' + mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        
        alertaGlobal.appendChild(alertDiv);
        
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
