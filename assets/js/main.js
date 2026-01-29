/**
 * Sistema de Gestion de Diplomas - COLMED
 * JavaScript Unificado v2.0
 * ISO 27001 Compliant
 */

(function() {
    'use strict';

    // ============================================
    // NAMESPACE GLOBAL
    // ============================================
    window.COLMED = window.COLMED || {};

    // ============================================
    // MODULO: UTILIDADES
    // ============================================
    COLMED.Utils = {
        /**
         * Formatear bytes a unidad legible
         */
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Escapar HTML para prevenir XSS
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        },

        /**
         * Truncar texto
         */
        truncar: function(texto, maxLength) {
            if (!texto) return '';
            maxLength = maxLength || 50;
            if (texto.length <= maxLength) return texto;
            return texto.substring(0, maxLength) + '...';
        },

        /**
         * Generar ID de sesion unico
         */
        generarSessionId: function(prefijo) {
            prefijo = prefijo || 'SESSION';
            return prefijo + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Validar extension de archivo
         */
        validarExtension: function(nombreArchivo, extensionesPermitidas) {
            var extension = nombreArchivo.split('.').pop().toLowerCase();
            return extensionesPermitidas.indexOf(extension) !== -1;
        },

        /**
         * Validar tamano de archivo (en bytes)
         */
        validarTamano: function(tamano, maxBytes) {
            return tamano <= maxBytes;
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };

    // ============================================
    // MODULO: ALERTAS Y NOTIFICACIONES
    // ============================================
    COLMED.Alertas = {
        container: null,

        init: function(containerId) {
            this.container = document.getElementById(containerId || 'alertaGlobal');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'alertaGlobal';
                var main = document.querySelector('.container') || document.body;
                main.insertBefore(this.container, main.firstChild);
            }
        },

        mostrar: function(tipo, mensaje, autoCerrar) {
            if (!this.container) this.init();

            var iconos = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-circle',
                'info': 'info-circle'
            };

            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + tipo + ' alert-dismissible fade show alert-institucional';
            alertDiv.innerHTML =
                '<i class="bi bi-' + (iconos[tipo] || 'info-circle') + ' me-2"></i>' +
                mensaje +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';

            this.container.appendChild(alertDiv);

            if (autoCerrar !== false) {
                setTimeout(function() {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        ocultar: function() {
            if (this.container) {
                this.container.innerHTML = '';
            }
        },

        exito: function(mensaje) {
            this.mostrar('success', mensaje);
        },

        error: function(mensaje) {
            this.mostrar('danger', mensaje);
        },

        advertencia: function(mensaje) {
            this.mostrar('warning', mensaje);
        },

        info: function(mensaje) {
            this.mostrar('info', mensaje);
        }
    };

    // ============================================
    // MODULO: MODAL DE CARGA
    // ============================================
    COLMED.Modal = {
        modalElement: null,
        modalInstance: null,

        init: function(modalId) {
            this.modalElement = document.getElementById(modalId || 'modalCargando');
        },

        mostrar: function(texto) {
            if (!this.modalElement) this.init();
            if (!this.modalElement) return;

            var textoEl = document.getElementById('textoCargando');
            if (textoEl) {
                textoEl.textContent = texto || 'Procesando...';
            }

            this.modalInstance = bootstrap.Modal.getOrCreateInstance(this.modalElement);
            this.modalInstance.show();
        },

        ocultar: function() {
            var self = this;
            if (this.modalInstance) {
                this.modalInstance.hide();
            }

            // Limpiar backdrop
            setTimeout(function() {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');

                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.remove();
                });

                if (self.modalElement) {
                    self.modalElement.classList.remove('show');
                    self.modalElement.style.display = 'none';
                    self.modalElement.setAttribute('aria-hidden', 'true');
                }
            }, 150);
        }
    };

    // ============================================
    // MODULO: ZONA DE UPLOAD
    // ============================================
    COLMED.Upload = {
        config: {
            maxSize: 5 * 1024 * 1024, // 5MB
            extensiones: ['xlsx', 'csv'],
            onFileSelected: null,
            onError: null
        },

        init: function(opciones) {
            var self = this;
            Object.assign(this.config, opciones || {});

            var uploadZone = document.getElementById('uploadZone');
            var archivoInput = document.getElementById('archivoInput');

            if (!uploadZone || !archivoInput) return;

            // Click en zona
            uploadZone.addEventListener('click', function() {
                archivoInput.click();
            });

            // Drag over
            uploadZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });

            // Drag leave
            uploadZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
            });

            // Drop
            uploadZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');

                var files = e.dataTransfer.files;
                if (files.length > 0) {
                    self.procesarArchivo(files[0]);
                }
            });

            // Input change
            archivoInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    self.procesarArchivo(this.files[0]);
                }
            });

            // Boton cambiar
            var btnCambiar = document.getElementById('btnCambiar');
            if (btnCambiar) {
                btnCambiar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    self.resetear();
                });
            }
        },

        procesarArchivo: function(file) {
            // Validar extension
            if (!COLMED.Utils.validarExtension(file.name, this.config.extensiones)) {
                var msg = 'Formato no valido. Solo se aceptan archivos: ' + this.config.extensiones.join(', ');
                if (this.config.onError) {
                    this.config.onError(msg);
                } else {
                    COLMED.Alertas.error(msg);
                }
                return false;
            }

            // Validar tamano
            if (!COLMED.Utils.validarTamano(file.size, this.config.maxSize)) {
                var msg = 'El archivo excede el tamano maximo de ' + COLMED.Utils.formatBytes(this.config.maxSize);
                if (this.config.onError) {
                    this.config.onError(msg);
                } else {
                    COLMED.Alertas.error(msg);
                }
                return false;
            }

            // Actualizar UI
            var uploadZone = document.getElementById('uploadZone');
            var archivoInfo = document.getElementById('archivoInfo');
            var nombreArchivo = document.getElementById('nombreArchivo');
            var infoArchivo = document.getElementById('infoArchivo');
            var btnProcesar = document.getElementById('btnProcesar');

            if (uploadZone) uploadZone.classList.add('d-none');
            if (archivoInfo) archivoInfo.classList.remove('d-none');
            if (nombreArchivo) nombreArchivo.textContent = file.name;
            if (infoArchivo) {
                var ext = file.name.split('.').pop().toUpperCase();
                infoArchivo.textContent = 'Tamano: ' + COLMED.Utils.formatBytes(file.size) + ' | Tipo: ' + ext;
            }
            if (btnProcesar) btnProcesar.disabled = false;

            // Asignar archivo al input
            var archivoInput = document.getElementById('archivoInput');
            if (archivoInput) {
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                archivoInput.files = dataTransfer.files;
            }

            // Callback
            if (this.config.onFileSelected) {
                this.config.onFileSelected(file);
            }

            COLMED.Alertas.ocultar();
            return true;
        },

        resetear: function() {
            var uploadZone = document.getElementById('uploadZone');
            var archivoInfo = document.getElementById('archivoInfo');
            var archivoInput = document.getElementById('archivoInput');
            var btnProcesar = document.getElementById('btnProcesar');

            if (uploadZone) uploadZone.classList.remove('d-none');
            if (archivoInfo) archivoInfo.classList.add('d-none');
            if (archivoInput) archivoInput.value = '';
            if (btnProcesar) btnProcesar.disabled = true;
        }
    };

    // ============================================
    // MODULO: PASOS/STEPS
    // ============================================
    COLMED.Steps = {
        actualizarPaso: function(pasoActual) {
            var steps = document.querySelectorAll('.steps-indicator .step');
            var lines = document.querySelectorAll('.steps-indicator .line');

            steps.forEach(function(step, index) {
                var stepNum = index + 1;
                step.classList.remove('active', 'completed');

                if (stepNum < pasoActual) {
                    step.classList.add('completed');
                } else if (stepNum === pasoActual) {
                    step.classList.add('active');
                }
            });

            lines.forEach(function(line, index) {
                if (index < pasoActual - 1) {
                    line.classList.add('active');
                } else {
                    line.classList.remove('active');
                }
            });
        },

        mostrarSeccion: function(seccionId) {
            // Ocultar todas las secciones
            var secciones = document.querySelectorAll('[id^="seccion"]');
            secciones.forEach(function(sec) {
                sec.classList.add('d-none');
            });

            // Mostrar la seccion indicada
            var seccion = document.getElementById(seccionId);
            if (seccion) {
                seccion.classList.remove('d-none');
                seccion.classList.add('fade-in');
            }
        }
    };

    // ============================================
    // MODULO: TABLAS CON FILTROS
    // ============================================
    COLMED.Tabla = {
        filtroActual: 'todos',

        init: function(tbodyId, filtros) {
            var self = this;

            if (filtros) {
                filtros.forEach(function(filtro) {
                    var btn = document.querySelector('[data-filtro="' + filtro.valor + '"]');
                    if (btn) {
                        btn.addEventListener('click', function() {
                            self.filtrar(tbodyId, filtro.valor);
                            self.actualizarBotonesFiltro(this);
                        });
                    }
                });
            }
        },

        filtrar: function(tbodyId, filtro) {
            var filas = document.querySelectorAll('#' + tbodyId + ' tr');
            this.filtroActual = filtro;

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
        },

        actualizarBotonesFiltro: function(botonActivo) {
            document.querySelectorAll('[data-filtro]').forEach(function(btn) {
                btn.classList.remove('active');
            });
            if (botonActivo) {
                botonActivo.classList.add('active');
            }
        },

        poblar: function(tbodyId, registros, renderFn) {
            var tbody = document.getElementById(tbodyId);
            if (!tbody) return;

            var html = '';
            for (var i = 0; i < registros.length; i++) {
                html += renderFn(registros[i], i);
            }

            tbody.innerHTML = html || '<tr><td colspan="10" class="text-center text-muted py-4">No hay registros</td></tr>';
        }
    };

    // ============================================
    // MODULO: AJAX
    // ============================================
    COLMED.Ajax = {
        post: function(url, formData, opciones) {
            opciones = opciones || {};

            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            reject({ error: 'Error al procesar respuesta', detalle: e.message });
                        }
                    } else {
                        reject({ error: 'Error del servidor', codigo: xhr.status });
                    }
                };

                xhr.onerror = function() {
                    reject({ error: 'Error de conexion' });
                };

                if (opciones.timeout) {
                    xhr.timeout = opciones.timeout;
                    xhr.ontimeout = function() {
                        reject({ error: 'Tiempo de espera agotado' });
                    };
                }

                xhr.send(formData);
            });
        }
    };

    // ============================================
    // MODULO: CSV EXPORT
    // ============================================
    COLMED.CSV = {
        descargar: function(datos, columnas, nombreArchivo) {
            var csv = columnas.join(';') + '\n';

            datos.forEach(function(fila) {
                var valores = columnas.map(function(col) {
                    var valor = fila[col] || '';
                    return '"' + String(valor).replace(/"/g, '""') + '"';
                });
                csv += valores.join(';') + '\n';
            });

            var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = (nombreArchivo || 'export') + '_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.style.visibility = 'hidden';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    // ============================================
    // MODULO: VALIDADOR (pagina publica)
    // ============================================
    COLMED.Validador = {
        init: function() {
            var form = document.getElementById('formValidar');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                // El form se envia normalmente, solo agregamos UX
                var btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validando...';
                }
            });

            // Formatear input de codigo
            var inputCodigo = document.getElementById('codigo');
            if (inputCodigo) {
                inputCodigo.addEventListener('input', function() {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                });
            }
        }
    };

    // ============================================
    // MODULO: CONVOCATORIAS
    // ============================================
    COLMED.Convocatorias = {
        seleccionada: null,

        init: function() {
            var self = this;
            var cards = document.querySelectorAll('.card-seleccion');

            cards.forEach(function(card) {
                card.addEventListener('click', function() {
                    self.seleccionar(this);
                });
            });
        },

        seleccionar: function(cardElement) {
            // Quitar seleccion anterior
            document.querySelectorAll('.card-seleccion').forEach(function(c) {
                c.classList.remove('selected');
            });

            // Marcar nueva seleccion
            cardElement.classList.add('selected');

            // Obtener datos
            var id = cardElement.getAttribute('data-id');
            var codigoBase = cardElement.getAttribute('data-codigo');
            var nombre = cardElement.getAttribute('data-nombre');

            this.seleccionada = {
                id: id,
                codigoBase: codigoBase,
                nombre: nombre
            };

            // Actualizar campo oculto
            var inputId = document.getElementById('convocatoriaId');
            if (inputId) inputId.value = id;

            // Mostrar info
            var infoDiv = document.getElementById('convocatoriaInfo');
            if (infoDiv) {
                infoDiv.classList.remove('d-none');
                var spanCodigo = infoDiv.querySelector('.codigo-base');
                if (spanCodigo) spanCodigo.textContent = codigoBase;
            }

            // Scroll a zona de upload
            var uploadSection = document.getElementById('seccionUpload') || document.getElementById('uploadZone');
            if (uploadSection) {
                uploadSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        getSeleccionada: function() {
            return this.seleccionada;
        }
    };

    // ============================================
    // MODULO: FORMULARIOS
    // ============================================
    COLMED.Forms = {
        /**
         * Contador de caracteres para inputs
         */
        initContadorCaracteres: function(inputId, contadorId, maxLength) {
            var input = document.getElementById(inputId);
            var contador = document.getElementById(contadorId);
            if (!input || !contador) return;

            var actualizar = function() {
                var len = input.value.length;
                contador.textContent = len;
                contador.parentElement.className = 'char-count mt-1';

                if (len === maxLength) {
                    contador.parentElement.classList.add('valid');
                } else if (len > maxLength) {
                    contador.parentElement.classList.add('invalid');
                }
            };

            input.addEventListener('input', actualizar);
            actualizar();
        },

        /**
         * Formatear RUT chileno mientras escribe
         */
        initFormateoRut: function(inputId) {
            var input = document.getElementById(inputId);
            if (!input) return;

            input.addEventListener('input', function() {
                var valor = this.value.toUpperCase();
                valor = valor.replace(/[^0-9K]/g, '');
                if (valor.length > 1) {
                    valor = valor.slice(0, -1) + '-' + valor.slice(-1);
                }
                this.value = valor;
            });
        },

        /**
         * Preview de codigo en tiempo real
         */
        initPreviewCodigo: function(inputId, previewId) {
            var input = document.getElementById(inputId);
            var preview = document.getElementById(previewId);
            if (!input || !preview) return;

            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
                preview.textContent = this.value || 'D20251134M';
            });
        }
    };

    // ============================================
    // MODULO: CARGA DE DIPLOMAS (pagina principal)
    // ============================================
    COLMED.CargaDiplomas = {
        sessionId: null,
        datosRegistros: [],
        convocatoriaInfo: null,
        registrosNoCargados: [],

        init: function() {
            var self = this;
            var formCarga = document.getElementById('formCarga');
            if (!formCarga) return;

            // Generar session ID
            this.sessionId = COLMED.Utils.generarSessionId('CARGA');
            var sessionInput = document.getElementById('sessionId');
            if (sessionInput) sessionInput.value = this.sessionId;

            // Inicializar modulos
            COLMED.Alertas.init('alertaGlobal');
            COLMED.Modal.init('modalCargando');
            COLMED.Convocatorias.init();
            COLMED.Upload.init({
                onError: function(msg) {
                    COLMED.Alertas.error(msg);
                }
            });

            // Submit del formulario
            formCarga.addEventListener('submit', function(e) {
                e.preventDefault();
                self.procesarArchivo();
            });

            // Botones
            this.initBotones();

            // Filtros de tabla
            COLMED.Tabla.init('tbodyPreview', [
                { valor: 'todos' },
                { valor: 'valido' },
                { valor: 'error' }
            ]);
        },

        initBotones: function() {
            var self = this;

            var btnCancelar = document.getElementById('btnCancelar');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function() {
                    if (confirm('Esta seguro de cancelar? Se perderan los datos precargados.')) {
                        self.limpiarYReiniciar();
                    }
                });
            }

            var btnConfirmar = document.getElementById('btnConfirmar');
            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', function() {
                    self.confirmarCarga();
                });
            }

            var btnNuevaCarga = document.getElementById('btnNuevaCarga');
            if (btnNuevaCarga) {
                btnNuevaCarga.addEventListener('click', function() {
                    window.location.reload();
                });
            }

            var btnDescargarReporte = document.getElementById('btnDescargarReporte');
            if (btnDescargarReporte) {
                btnDescargarReporte.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.descargarReporteNoCargados();
                });
            }
        },

        procesarArchivo: function() {
            var self = this;
            var convId = document.getElementById('convocatoriaId');

            if (!convId || !convId.value) {
                COLMED.Alertas.error('Debe seleccionar una convocatoria antes de procesar el archivo.');
                return;
            }

            var formData = new FormData(document.getElementById('formCarga'));
            COLMED.Modal.mostrar('Procesando archivo...');

            COLMED.Ajax.post('procesar-carga.php', formData)
                .then(function(response) {
                    COLMED.Modal.ocultar();
                    if (response.success) {
                        self.datosRegistros = response.registros;
                        self.convocatoriaInfo = response.convocatoria;
                        self.mostrarPreview(response);
                    } else {
                        COLMED.Alertas.error(response.mensaje);
                    }
                })
                .catch(function(err) {
                    COLMED.Modal.ocultar();
                    COLMED.Alertas.error(err.error || 'Error de conexion');
                });
        },

        mostrarPreview: function(response) {
            var self = this;

            // Actualizar pasos
            COLMED.Steps.actualizarPaso(2);
            COLMED.Steps.mostrarSeccion('seccionPreview');

            // Info de convocatoria
            if (this.convocatoriaInfo) {
                var codigoEl = document.getElementById('codigoBasePreview');
                var nombreEl = document.getElementById('nombreConvPreview');
                if (codigoEl) codigoEl.textContent = this.convocatoriaInfo.codigo_base;
                if (nombreEl) nombreEl.textContent = this.convocatoriaInfo.nombre;
            }

            // Generar resumen
            this.generarResumen(response);

            // Poblar tabla
            COLMED.Tabla.poblar('tbodyPreview', response.registros, function(reg, idx) {
                return self.renderFilaPreview(reg, idx);
            });

            // Estado del boton confirmar
            var btnConfirmar = document.getElementById('btnConfirmar');
            if (btnConfirmar) {
                btnConfirmar.disabled = (response.validos <= 0);
            }
        },

        generarResumen: function(response) {
            var resumenEl = document.getElementById('resumenCarga');
            if (!resumenEl) return;

            var codigosInvalidos = response.codigos_invalidos || 0;
            var erroresOtros = response.errores - codigosInvalidos;

            var html = '<div class="row g-3 mb-3">' +
                '<div class="col-md-3"><div class="stats-box total"><div class="numero">' + response.total + '</div><div class="etiqueta">Total</div></div></div>' +
                '<div class="col-md-3"><div class="stats-box validos"><div class="numero">' + response.validos + '</div><div class="etiqueta">Validos</div></div></div>';

            if (codigosInvalidos > 0) {
                html += '<div class="col-md-3"><div class="stats-box advertencias"><div class="numero">' + codigosInvalidos + '</div><div class="etiqueta">Codigo Invalido</div></div></div>';
            }

            if (erroresOtros > 0) {
                html += '<div class="col-md-3"><div class="stats-box errores"><div class="numero">' + erroresOtros + '</div><div class="etiqueta">Errores</div></div></div>';
            }

            html += '</div>';

            // Alertas
            if (codigosInvalidos > 0 && this.convocatoriaInfo) {
                html += '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><strong>' + codigosInvalidos + ' registro(s)</strong> tienen codigos que no coinciden con <code>' + this.convocatoriaInfo.codigo_base + '</code></div>';

                var opcionesEl = document.getElementById('opcionesCarga');
                if (opcionesEl) opcionesEl.classList.remove('d-none');
            }

            if (erroresOtros > 0) {
                html += '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><strong>' + erroresOtros + ' registro(s)</strong> tienen errores criticos y no se cargaran.</div>';
            }

            if (response.validos > 0 && response.errores === 0) {
                html += '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Todos los registros son validos.</div>';
            }

            resumenEl.innerHTML = html;
        },

        renderFilaPreview: function(reg, idx) {
            var rowClass = '';
            var estadoBadge = '';

            if (reg.estado === 'valido') {
                rowClass = 'table-success';
                estadoBadge = '<span class="badge badge-estado-valido"><i class="bi bi-check-circle"></i> Valido</span>';
            } else if (reg.estado === 'codigo_invalido') {
                rowClass = 'table-warning';
                estadoBadge = '<span class="badge badge-estado-advertencia"><i class="bi bi-exclamation-triangle"></i> Codigo</span>';
            } else {
                rowClass = 'table-danger';
                estadoBadge = '<span class="badge badge-estado-error"><i class="bi bi-x-circle"></i> Error</span>';
            }

            return '<tr class="' + rowClass + '" data-estado="' + reg.estado + '">' +
                '<td>' + (idx + 1) + '</td>' +
                '<td>' + estadoBadge + '</td>' +
                '<td><code class="codigo-base">' + COLMED.Utils.escapeHtml(reg.codigo) + '</code></td>' +
                '<td>' + COLMED.Utils.escapeHtml(COLMED.Utils.truncar(reg.autores, 40)) + '</td>' +
                '<td>' + COLMED.Utils.escapeHtml(COLMED.Utils.truncar(reg.tema, 50)) + '</td>' +
                '<td><small class="text-muted">' + COLMED.Utils.escapeHtml(reg.mensaje_error || '-') + '</small></td>' +
                '</tr>';
        },

        confirmarCarga: function() {
            var self = this;
            COLMED.Modal.mostrar('Insertando registros...');

            var formData = new FormData();
            formData.append('session_id', this.sessionId);

            var tipoCarga = document.querySelector('input[name="tipoCarga"]:checked');
            formData.append('tipo_carga', tipoCarga ? tipoCarga.value : 'parcial');

            COLMED.Ajax.post('confirmar-carga.php', formData)
                .then(function(response) {
                    COLMED.Modal.ocultar();
                    self.mostrarResultado(response);
                })
                .catch(function(err) {
                    COLMED.Modal.ocultar();
                    COLMED.Alertas.error(err.error || 'Error al confirmar carga');
                });
        },

        mostrarResultado: function(response) {
            COLMED.Steps.actualizarPaso(3);
            COLMED.Steps.mostrarSeccion('seccionResultado');

            var resultadoEl = document.getElementById('resultadoFinal');
            if (!resultadoEl) return;

            var html = '';
            if (response.success) {
                html = '<div class="text-center py-5">' +
                    '<i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>' +
                    '<h3 class="mt-3 text-success">Carga Completada</h3>' +
                    '<p class="lead">Se insertaron <strong>' + response.insertados + '</strong> diploma(s).</p>' +
                    '</div>';

                if (response.no_cargados && response.no_cargados > 0) {
                    this.registrosNoCargados = response.registros_no_cargados;
                    var reporteEl = document.getElementById('reporteNoCargados');
                    if (reporteEl) reporteEl.classList.remove('d-none');
                }
            } else {
                html = '<div class="text-center py-5">' +
                    '<i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>' +
                    '<h3 class="mt-3 text-danger">Error en la Carga</h3>' +
                    '<p class="lead">' + COLMED.Utils.escapeHtml(response.mensaje) + '</p>' +
                    '</div>';
            }

            resultadoEl.innerHTML = html;
        },

        limpiarYReiniciar: function() {
            var formData = new FormData();
            formData.append('session_id', this.sessionId);

            COLMED.Ajax.post('limpiar-temporal.php', formData)
                .finally(function() {
                    window.location.reload();
                });
        },

        descargarReporteNoCargados: function() {
            if (!this.registrosNoCargados || this.registrosNoCargados.length === 0) {
                COLMED.Alertas.advertencia('No hay registros para descargar.');
                return;
            }

            COLMED.CSV.descargar(
                this.registrosNoCargados,
                ['codigo', 'autores', 'tema', 'mensaje_error'],
                'registros_no_cargados'
            );
        }
    };

    // ============================================
    // INICIALIZACION GLOBAL
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar modulo de alertas siempre
        COLMED.Alertas.init();

        // Detectar pagina actual e inicializar modulo correspondiente
        var pagina = document.body.getAttribute('data-pagina');

        switch (pagina) {
            case 'carga-diplomas':
                COLMED.CargaDiplomas.init();
                break;
            case 'validador':
                COLMED.Validador.init();
                break;
            default:
                // Inicializaciones genericas
                COLMED.Convocatorias.init();
                break;
        }
    });

})();
