/**
 * FUNCTIONS-REPORTES.JS
 * =====================
 * 
 * Este archivo contiene todas las funciones JavaScript para el módulo de reportes del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - Inicialización de Select2 para búsqueda de alumnos, representantes y motivos
 * - Gestión dinámica de filtros según el tipo de reporte seleccionado
 * - Validación y generación de reportes PDF
 * - Carga de datos desde el servidor (cursos, períodos, edades)
 * - Manejo de motivos de retiro y asistencia
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - Select2
 * - SweetAlert
 */

// ===== INICIALIZACIÓN AL CARGAR EL DOM =====
document.addEventListener('DOMContentLoaded', function () {
    // ===== CONFIGURACIÓN DE SELECT2 PARA BÚSQUEDA DE ALUMNOS =====
    // Inicializa un campo de búsqueda con autocompletado para seleccionar alumnos
    // Busca por nombre o cédula mediante AJAX
    if ($('.select2').length > 0) {
        $('.select2').select2({
            placeholder: "Buscar alumno...",
            allowClear: true,
            // Textos en español para los mensajes del Select2
            language: {
                noResults: function () { return "No se encontraron resultados"; },
                searching: function () { return "Buscando..."; },
                loadingMore: function () { return "Cargando más resultados..."; },
                errorLoading: function () { return "La carga falló"; },
                inputTooShort: function (args) { var remainingChars = args.minimum - args.input.length; return "Por favor, introduzca " + remainingChars + " car" + (remainingChars === 1 ? "ácter" : "acteres"); },
                inputTooLong: function (args) { var overChars = args.input.length - args.maximum; return "Por favor, elimine " + overChars + " car" + (overChars === 1 ? "ácter" : "acteres"); },
                maximumSelected: function (args) { return "Solo puede seleccionar " + args.maximum + " elemento" + (args.maximum === 1 ? "" : "s"); },
                removeAllItems: function () { return "Eliminar todos los elementos"; }
            },
            ajax: {
                url: 'models/reportes/ajax-reportes.php',
                dataType: 'json',
                delay: 250,
                type: 'POST',
                data: function (params) {
                    var reporte = $('#listReporte').val();
                    var action = 'search_alumno';

                    // Si es retiro, buscar solo alumnos inactivos/retirados
                    if (reporte == 'retiro') {
                        action = 'search_alumno_retiro';
                    }

                    return {
                        action: action,
                        search: params.term
                    };
                },
                processResults: function (data) {
                    var results = [];
                    if (data) {
                        results = data.map(function (item) {
                            // Si es retiro, usamos un prefijo único para evitar colisiones con alumnos activos
                            if ($('#listReporte').val() == 'retiro') {
                                return {
                                    id: 'retiro_' + (item.inscripcion_id ? item.inscripcion_id : item.alumno_id),
                                    text: item.nacionalidad + '-' + item.cedula + ' - ' + item.alumno_nombre + ' ' + item.alumno_apellido + ' (' + item.grado + '° ' + item.seccion + ')',
                                    is_retiro: true,
                                    alumno_id: item.alumno_id // CRITICAL FIX: Include alumno_id here too
                                };
                            }

                            // Lógica estándar para otros reportes
                            var idVal = item.inscripcion_id ? item.inscripcion_id : 'alumno_' + item.alumno_id;
                            return {
                                id: idVal,
                                text: item.nacionalidad + '-' + item.cedula + ' - ' + item.alumno_nombre + ' ' + item.alumno_apellido + ' (' + item.grado + '° ' + item.seccion + ')',
                                alumno_id: item.alumno_id,
                                is_alumno_id: !item.inscripcion_id
                            };
                        });
                    }
                    return {
                        results: results
                    };
                },
                cache: false
            },
            templateSelection: function (data, container) {
                $(container).removeAttr("title");
                return data.text;
            },
            templateResult: function (data) {
                return data.text;
            }
        });

        // Forzar actualización correcta del texto cuando se selecciona un alumno
        $('.select2').on('select2:select', function (e) {
            var data = e.params.data;
            // Forzar que el texto mostrado sea el del elemento seleccionado
            if (data && data.text) {
                var $selection = $(this).next('.select2-container').find('.select2-selection__rendered');
                $selection.text(data.text);
                $selection.attr('title', data.text);
            }
        });
    }

    // Inicializar Select2 para búsqueda de representantes
    if ($('.select2-representante').length > 0) {
        $('.select2-representante').select2({
            placeholder: "Buscar representante...",
            allowClear: true,
            language: {
                noResults: function () { return "No se encontraron resultados"; },
                searching: function () { return "Buscando..."; },
                loadingMore: function () { return "Cargando más resultados..."; },
                errorLoading: function () { return "La carga falló"; },
                inputTooShort: function (args) { var remainingChars = args.minimum - args.input.length; return "Por favor, introduzca " + remainingChars + " car" + (remainingChars === 1 ? "ácter" : "acteres"); },
                inputTooLong: function (args) { var overChars = args.input.length - args.maximum; return "Por favor, elimine " + overChars + " car" + (overChars === 1 ? "ácter" : "acteres"); },
                maximumSelected: function (args) { return "Solo puede seleccionar " + args.maximum + " elemento" + (args.maximum === 1 ? "" : "s"); },
                removeAllItems: function () { return "Eliminar todos los elementos"; }
            },
            ajax: {
                url: 'models/reportes/ajax-reportes.php',
                dataType: 'json',
                delay: 250,
                type: 'POST',
                data: function (params) {
                    return {
                        action: 'search_representante',
                        search: params.term,
                        reporte: $('#listReporte').val()
                    };
                },
                processResults: function (data) {
                    var results = [];
                    if (data) {
                        results = data.map(function (item) {
                            return {
                                id: item.representante_id,
                                text: item.nacionalidad + '-' + item.cedula + ' - ' + item.nombre + ' ' + item.apellido
                            };
                        });
                    }
                    return {
                        results: results
                    };
                },
                cache: false
            },
            templateSelection: function (data, container) {
                $(container).removeAttr("title");
                return data.text || data.id;
            },
            templateResult: function (data) {
                return data.text;
            }
        });

        // Forzar actualización correcta del texto cuando se selecciona un representante
        $('.select2-representante').on('select2:select', function (e) {
            var data = e.params.data;
            if (data && data.text) {
                var $selection = $(this).next('.select2-container').find('.select2-selection__rendered');
                $selection.text(data.text);
                $selection.attr('title', data.text);
            }
        });
    }

    // Inicializar Select2 para motivos con opción de crear nuevos
    if ($('.select2-motivo').length > 0) {
        $('.select2-motivo').select2({
            placeholder: "Seleccione o escriba un motivo...",
            allowClear: false,
            tags: true, // Esto permite crear nuevas opciones
            width: '100%',
            language: {
                noResults: function () { return "No se encontraron resultados"; },
                searching: function () { return "Buscando..."; },
                loadingMore: function () { return "Cargando más resultados..."; },
                errorLoading: function () { return "La carga falló"; },
                inputTooShort: function (args) { var remainingChars = args.minimum - args.input.length; return "Por favor, introduzca " + remainingChars + " car" + (remainingChars === 1 ? "ácter" : "acteres"); },
                inputTooLong: function (args) { var overChars = args.input.length - args.maximum; return "Por favor, elimine " + overChars + " car" + (overChars === 1 ? "ácter" : "acteres"); },
                maximumSelected: function (args) { return "Solo puede seleccionar " + args.maximum + " elemento" + (args.maximum === 1 ? "" : "s"); },
                removeAllItems: function () { return "Eliminar todos los elementos"; }
            },
            ajax: {
                url: 'models/reportes/ajax-reportes.php',
                dataType: 'json',
                delay: 250,
                type: 'POST',
                data: function (params) {
                    var reporte = $('#listReporte').val();
                    var tipoMotivo = 'motivo_%';
                    if (reporte == 'retiro') {
                        tipoMotivo = 'motivo_retiro';
                    } else if (reporte == 'justificativo') {
                        tipoMotivo = 'motivo_justificativo';
                    }
                    return {
                        action: 'get_motivos',
                        search: params.term,
                        tipo: tipoMotivo
                    };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: false
            }
        });

        // Evento al seleccionar/cambiar motivo para mostrar el botón de inhabilitar
        $('.select2-motivo').on('select2:select select2:unselect change', function (e) {
            var val = $(this).val();
            if (val && val.trim() !== "") {
                $('#btnInhabilitarMotivo').show();
            } else {
                $('#btnInhabilitarMotivo').hide();
            }
        });

        // Evento del botón inhabilitar motivo
        $('#btnInhabilitarMotivo').on('click', function () {
            var motivo = $('#listMotivo').val();
            var reporte = $('#listReporte').val();
            var tipoMotivo = '';

            if (reporte == 'retiro') {
                tipoMotivo = 'motivo_retiro';
            } else if (reporte == 'justificativo') {
                tipoMotivo = 'motivo_justificativo';
            }

            if (!motivo) return;

            swal({
                title: "¿Inhabilitar motivo?",
                text: "¿Desea inhabilitar el motivo: \"" + motivo + "\"? Ya no aparecerá en la lista de selección.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, inhabilitar",
                cancelButtonText: "Cancelar",
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    url: 'models/reportes/ajax-reportes.php',
                    type: 'POST',
                    data: {
                        action: 'disable_motivo',
                        motivo: motivo,
                        tipo: tipoMotivo
                    },
                    success: function (response) {
                        var data = JSON.parse(response);
                        if (data.status) {
                            swal("¡Inhabilitado!", data.msg, "success");
                            $('#listMotivo').val(null).trigger('change');
                        } else {
                            swal("Error", data.msg, "error");
                        }
                    },
                    error: function () {
                        swal("Error", "Error de conexión", "error");
                    }
                });
            });
        });
    }

    // Cargar datos iniciales
    cargarCursos();
    cargarPeriodos();
    cargarPeriodosParaFiltro();
    cargarPeriodoActual();
    cargarEdades();

    // Evento al seleccionar un alumno
    $('#listAlumno').on('select2:select', function (e) {
        var data = e.params.data;
        var reporte = $('#listReporte').val();

        if (reporte == 'retiro') {
            cargarMotivoActual(data.alumno_id);
        }
    });

    // Evento al limpiar el buscador de alumnos
    $('#listAlumno').on('select2:unselect', function (e) {
        $('#divMotivoActual').fadeOut();
        $('#txtMotivoActual').val('');
    });

    // Listener para cambio de reporte
    document.getElementById('listReporte').addEventListener('change', function () {
        mostrarFiltros(this.value);
    });

    // Listeners para el toggle de motivo de retiro
    $('input[name="poseeMotivoRetiro"]').on('change', function () {
        if (this.value == 'SI') {
            $('#divFiltroMotivo').fadeIn();
            $('#divMotivoActual').fadeOut();
        } else {
            $('#divFiltroMotivo').fadeOut();
            $('#listMotivo').val(null).trigger('change');
            $('#btnInhabilitarMotivo').fadeOut();

            // Si hay un motivo actual cargado, volver a mostrarlo
            if ($('#txtMotivoActual').val().trim() !== "") {
                $('#divMotivoActual').fadeIn();
            }
        }
    });

    // Listeners para el toggle de motivo de asistencia
    $('input[name="poseeMotivoAsistencia"]').on('change', function () {
        if (this.value == 'SI') {
            $('#divFiltroMotivo').fadeIn();
        } else {
            $('#divFiltroMotivo').fadeOut();
            $('#listMotivo').val(null).trigger('change');
            $('#btnInhabilitarMotivo').fadeOut();
        }
    });
});

function cargarMotivoActual(alumno_id) {
    if (!alumno_id) return;

    $.ajax({
        url: 'models/reportes/ajax-reportes.php',
        type: 'POST',
        data: {
            action: 'get_alumno_motivo',
            alumno_id: alumno_id
        },
        success: function (response) {
            var data = JSON.parse(response);
            if (data.status && data.motivo) {
                $('#txtMotivoActual').val(data.motivo);

                // Solo mostrar si no se ha seleccionado "SI" en el toggle de nuevo motivo
                var deseaNuevo = $('input[name="poseeMotivoRetiro"]:checked').val();
                if (deseaNuevo == 'NO') {
                    $('#divMotivoActual').fadeIn();
                }
            } else {
                $('#divMotivoActual').fadeOut();
                $('#txtMotivoActual').val('');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}

function cargarCursos() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/reportes/ajax-reportes.php';
    var formData = new FormData();
    formData.append('action', 'get_cursos');

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var cursos = JSON.parse(request.responseText);
            var html = '<option value="">Todos los cursos</option>';
            cursos.forEach(function (curso) {
                html += '<option value="' + curso.curso_id + '">' + curso.grado + '° - Sección "' + curso.seccion + '"</option>';
            });
            document.getElementById('listCurso').innerHTML = html;
        }
    }
}

function cargarPeriodos() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/reportes/ajax-reportes.php';
    var formData = new FormData();
    formData.append('action', 'get_periodos');

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var periodos = JSON.parse(request.responseText);

            var html = '<option value="">Todos los periodos</option>';
            periodos.forEach(function (periodo) {
                var estado = (periodo.estatus == 1) ? ' (Activo)' : ' (Inactivo)';
                html += '<option value="' + periodo.periodo_id + '">' + periodo.anio_inicio + ' - ' + periodo.anio_fin + estado + '</option>';
            });
            document.getElementById('listPeriodo').innerHTML = html;
        }
    }
}

function cargarPeriodosParaFiltro() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/reportes/ajax-reportes.php';
    var formData = new FormData();
    formData.append('action', 'get_periodos');

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var periodos = JSON.parse(request.responseText);
            var html = '<option value="all">Todos los periodos</option>';
            periodos.forEach(function (periodo) {
                var estado = (periodo.estatus == 1) ? ' (Activo)' : ' (Inactivo)';
                var label = periodo.anio_inicio + ' - ' + periodo.anio_fin + estado;
                html += '<option value="' + periodo.periodo_id + '">' + label + '</option>';
            });
            document.getElementById('filtroPeriodoAnio').innerHTML = html;
        }
    }
}

function cargarPeriodoActual() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/reportes/ajax-reportes.php';
    var formData = new FormData();
    formData.append('action', 'get_periodo_actual');

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var response = JSON.parse(request.responseText);
            if (response && response.periodo_id) {
                // Para el filtro de grados (oculto usualmente)
                if (document.getElementById('listPeriodoGrado')) {
                    document.getElementById('listPeriodoGrado').value = response.anio_inicio + ' - ' + response.anio_fin;
                }
                if (document.getElementById('listPeriodoGradoId')) {
                    document.getElementById('listPeriodoGradoId').value = response.periodo_id;
                }
                // Para el nuevo campo principal de solo lectura
                if (document.getElementById('periodoDisplay')) {
                    document.getElementById('periodoDisplay').value = response.anio_inicio + ' - ' + response.anio_fin;
                }
                if (document.getElementById('listPeriodo')) {
                    document.getElementById('listPeriodo').value = response.periodo_id;
                }
            }
        }
    }
}

function mostrarFiltros(reporte) {
    // Destruir y reinicializar Select2 para limpiar caché completamente
    if ($('#listAlumno').hasClass('select2-hidden-accessible')) {
        $('#listAlumno').select2('destroy');
    }
    if ($('#listRepresentante').hasClass('select2-hidden-accessible')) {
        $('#listRepresentante').select2('destroy');
    }

    // Limpiar los valores
    $('#listAlumno').val(null);
    $('#listRepresentante').val(null);
    $('#listMotivo').val(null).trigger('change');

    // Reinicializar Select2 para alumnos
    $('#listAlumno').select2({
        placeholder: "Buscar alumno...",
        allowClear: true,
        language: {
            noResults: function () { return "No se encontraron resultados"; },
            searching: function () { return "Buscando..."; },
            loadingMore: function () { return "Cargando más resultados..."; },
            errorLoading: function () { return "La carga falló"; }
        },
        ajax: {
            url: 'models/reportes/ajax-reportes.php',
            dataType: 'json',
            delay: 250,
            type: 'POST',
            data: function (params) {
                var reporte = $('#listReporte').val();
                var action = 'search_alumno';
                if (reporte == 'retiro') {
                    action = 'search_alumno_retiro';
                }
                return {
                    action: action,
                    search: params.term
                };
            },
            processResults: function (data) {
                var results = [];
                if (data) {
                    results = data.map(function (item) {
                        if ($('#listReporte').val() == 'retiro') {
                            return {
                                id: 'retiro_' + (item.inscripcion_id ? item.inscripcion_id : item.alumno_id),
                                text: item.nacionalidad + '-' + item.cedula + ' - ' + item.alumno_nombre + ' ' + item.alumno_apellido + ' (' + item.grado + '° ' + item.seccion + ')',
                                is_retiro: true,
                                alumno_id: item.alumno_id
                            };
                        }
                        var idVal = item.inscripcion_id ? item.inscripcion_id : 'alumno_' + item.alumno_id;
                        return {
                            id: idVal,
                            text: item.nacionalidad + '-' + item.cedula + ' - ' + item.alumno_nombre + ' ' + item.alumno_apellido + ' (' + item.grado + '° ' + item.seccion + ')',
                            alumno_id: item.alumno_id,
                            is_alumno_id: !item.inscripcion_id
                        };
                    });
                }
                return { results: results };
            },
            cache: false  // Desactivar caché para evitar datos obsoletos
        },
        templateSelection: function (data, container) {
            $(container).removeAttr("title");
            return data.text || data.id;
        },
        templateResult: function (data) {
            return data.text;
        }
    });

    // Forzar actualización correcta del texto cuando se selecciona un alumno
    $('#listAlumno').off('select2:select').on('select2:select', function (e) {
        var data = e.params.data;
        if (data && data.text) {
            var $selection = $(this).next('.select2-container').find('.select2-selection__rendered');
            $selection.text(data.text);
            $selection.attr('title', data.text);

            // Cargar motivo si es reporte de retiro
            var reporte = $('#listReporte').val();
            if (reporte == 'retiro' && data.alumno_id) {
                cargarMotivoActual(data.alumno_id);
            }
        }
    });

    // Reinicializar Select2 para representantes
    $('#listRepresentante').select2({
        placeholder: "Buscar representante...",
        allowClear: true,
        language: {
            noResults: function () { return "No se encontraron resultados"; },
            searching: function () { return "Buscando..."; },
            loadingMore: function () { return "Cargando más resultados..."; },
            errorLoading: function () { return "La carga falló"; }
        },
        ajax: {
            url: 'models/reportes/ajax-reportes.php',
            dataType: 'json',
            delay: 250,
            type: 'POST',
            data: function (params) {
                return {
                    action: 'search_representante',
                    search: params.term,
                    reporte: $('#listReporte').val()
                };
            },
            processResults: function (data) {
                var results = [];
                if (data) {
                    results = data.map(function (item) {
                        return {
                            id: item.representante_id,
                            text: item.nacionalidad + '-' + item.cedula + ' - ' + item.nombre + ' ' + item.apellido
                        };
                    });
                }
                return { results: results };
            },
            cache: false  // Desactivar caché para evitar datos obsoletos
        },
        templateSelection: function (data, container) {
            $(container).removeAttr("title");
            return data.text || data.id;
        },
        templateResult: function (data) {
            return data.text;
        }
    });

    // Forzar actualización correcta del texto cuando se selecciona un representante
    $('#listRepresentante').off('select2:select').on('select2:select', function (e) {
        var data = e.params.data;
        if (data && data.text) {
            var $selection = $(this).next('.select2-container').find('.select2-selection__rendered');
            $selection.text(data.text);
            $selection.attr('title', data.text);
        }
    });

    // Ocultar todos los filtros primero
    document.getElementById('filtrosContainer').style.display = 'none';
    document.getElementById('divFiltroAlumno').style.display = 'none';
    document.getElementById('divFiltroRepresentante').style.display = 'none';
    document.getElementById('divFiltroCurso').style.display = 'none';
    document.getElementById('divFiltroPeriodo').style.display = 'none';
    document.getElementById('divMotivoActual').style.display = 'none';
    document.getElementById('divToggleMotivo').style.display = 'none';
    document.getElementById('divToggleMotivoAsistencia').style.display = 'none';
    document.getElementById('divFiltroMotivo').style.display = 'none';
    $('#btnInhabilitarMotivo').hide();

    // Limpiar texto de motivo actual
    document.getElementById('txtMotivoActual').innerText = '';

    // Resetear radios
    document.getElementById('poseeMotivoRetiroNo').checked = true;
    if (document.getElementById('poseeMotivoAsistenciaNo')) document.getElementById('poseeMotivoAsistenciaNo').checked = true;

    // Resetear layout de cabecera
    document.getElementById('divTipoReporteContainer').className = 'col-md-12';

    // Ocultar filtros de grados y secciones
    document.getElementById('divFiltroGrado').style.display = 'none';
    document.getElementById('divFiltroSeccion').style.display = 'none';
    document.getElementById('divFiltroEstatusGrado').style.display = 'none';
    document.getElementById('divFiltroPeriodoGrado').style.display = 'none';

    // Ocultar filtros de listados
    document.getElementById('filtrosListados').style.display = 'none';
    document.getElementById('filtrosAlumnos').style.display = 'none';
    document.getElementById('filtrosProfesores').style.display = 'none';
    document.getElementById('filtrosRepresentantes').style.display = 'none';
    document.getElementById('filtrosPeriodos').style.display = 'none';
    document.getElementById('filtrosInscripciones').style.display = 'none';

    if (!reporte) return;

    document.getElementById('filtrosContainer').style.display = 'block';

    switch (reporte) {
        case 'aceptacion':
            document.getElementById('divFiltroAlumno').style.display = 'block';
            break;

        case 'constancia_estudio':
        case 'constancia_inscripcion':
            document.getElementById('divFiltroAlumno').style.display = 'block';
            break;

        case 'retiro':
            document.getElementById('divFiltroAlumno').style.display = 'block';
            document.getElementById('divToggleMotivo').style.display = 'block';
            // El divFiltroMotivo se controla por los radios y el Listener
            break;

        case 'justificativo':
            document.getElementById('divFiltroRepresentante').style.display = 'block';
            document.getElementById('divToggleMotivoAsistencia').style.display = 'block';
            // El divFiltroMotivo se controla por los radios y el Listener
            break;

        case 'lista_graysec':
            document.getElementById('divFiltroGrado').style.display = 'block';
            document.getElementById('divFiltroSeccion').style.display = 'block';
            document.getElementById('divFiltroEstatusGrado').style.display = 'block';
            document.getElementById('divFiltroPeriodoGrado').style.display = 'block';
            break;

        case 'lista_inscribir':
            document.getElementById('divFiltroPeriodo').style.display = 'block';
            document.getElementById('divTipoReporteContainer').className = 'col-md-8';
            document.getElementById('filtrosListados').style.display = 'block';
            document.getElementById('filtrosInscripciones').style.display = 'block';
            break;

        // Listados con filtros dinámicos
        case 'lista_alumno':
            document.getElementById('filtrosContainer').style.display = 'none';
            document.getElementById('filtrosListados').style.display = 'block';
            document.getElementById('filtrosAlumnos').style.display = 'block';
            break;

        case 'lista_profesor':
            document.getElementById('filtrosContainer').style.display = 'none';
            document.getElementById('filtrosListados').style.display = 'block';
            document.getElementById('filtrosProfesores').style.display = 'block';
            break;

        case 'lista_representante':
            document.getElementById('filtrosContainer').style.display = 'none';
            document.getElementById('filtrosListados').style.display = 'block';
            document.getElementById('filtrosRepresentantes').style.display = 'block';
            break;

        case 'lista_periodo':
            document.getElementById('filtrosContainer').style.display = 'none';
            document.getElementById('filtrosListados').style.display = 'block';
            document.getElementById('filtrosPeriodos').style.display = 'block';
            break;

        default:
            // Listas generales sin filtros
            document.getElementById('filtrosContainer').style.display = 'none';
            break;
    }
}

function generarReporte() {
    var reporte = document.getElementById('listReporte').value;
    if (!reporte) {
        swal("Atención", "Seleccione un tipo de reporte", "warning");
        return;
    }

    // Lista de reportes que requieren firma de director
    var reportesRestringidos = [
        'retiro',
        'aceptacion',
        'justificativo',
        'constancia_estudio',
        'constancia_inscripcion'
    ];

    if (reportesRestringidos.includes(reporte) && !hasDirector) {
        swal("Atención", "No es posible generar este documento porque no hay un director activo asignado en el sistema.", "error");
        return;
    }

    var url = '';
    var params = [];

    switch (reporte) {
        case 'constancia_estudio':
            var idInscripcion = $('#listAlumno').val();
            if (!idInscripcion) {
                swal("Atención", "Debe seleccionar un alumno", "warning");
                return;
            }
            url = 'Reportes/constancia_estudio.php';
            params.push('id=' + idInscripcion);
            break;

        case 'constancia_inscripcion':
            var idInscripcion = $('#listAlumno').val();
            if (!idInscripcion) {
                swal("Atención", "Debe seleccionar un alumno", "warning");
                return;
            }
            url = 'Reportes/constancia_inscripcion.php';
            params.push('id=' + idInscripcion);
            break;

        case 'aceptacion':
            var idInscripcion = $('#listAlumno').val();
            if (!idInscripcion) {
                swal("Atención", "Debe seleccionar un alumno", "warning");
                return;
            }
            url = 'Reportes/aceptacion.php';
            params.push('id=' + idInscripcion);
            break;

        case 'retiro':
            var idSelection = $('#listAlumno').val();
            if (!idSelection) {
                swal("Atención", "Debe seleccionar un alumno", "warning");
                return;
            }
            url = 'Reportes/retiro.php';

            // Verificamos si es ID de inscripción o ID de alumno (prefijo 'alumno_')
            // Verificamos prefijos
            if (String(idSelection).indexOf('alumno_') === 0) {
                // Es id de alumno
                var idAlumno = idSelection.replace('alumno_', '');
                params.push('id=' + idAlumno);
                params.push('tipo=alumno');
            } else if (String(idSelection).indexOf('retiro_') === 0) {
                // Es id de retiro (puede ser inscripción o alumno, pero en el query usamos inscripción si existe)
                // En ajax-reportes devolvemos inscripcion_id preferiblemente.
                var rawId = idSelection.replace('retiro_', '');
                params.push('id=' + rawId);
                params.push('tipo=inscripcion'); // Asumimos inscripción para retiro
            } else {
                // Es id de inscripción normal
                params.push('id=' + idSelection);
                params.push('tipo=inscripcion');
            }

            var poseeMotivo = $('input[name="poseeMotivoRetiro"]:checked').val();
            var motivo = (poseeMotivo == 'SI') ? $('#listMotivo').val() : '';

            if (poseeMotivo == 'SI' && !motivo) {
                swal("Atención", "Debe seleccionar o escribir un motivo si marcó SÍ", "warning");
                return;
            }

            if (motivo) {
                // Si es alumno ID, pasamos alumno_id, si es inscripcion, necesitamos el alumno_id tambien para guardar el motivo...
                // Pero guardarMotivo espera el ID correcto.
                // Obtenemos el objeto data de select2 para sacar el alumno_id real
                var data = $('#listAlumno').select2('data')[0];
                var realAlumnoId = data ? data.alumno_id : 0;

                guardarMotivo(motivo, 'motivo_retiro', realAlumnoId, 0);
                $('#txtMotivoActual').val(motivo); // Sincronizar el campo actual con el nuevo
                params.push('motivo=' + encodeURIComponent(motivo));
            }
            break;

        case 'justificativo':
            var idRepresentante = $('#listRepresentante').val();
            if (!idRepresentante) {
                swal("Atención", "Debe seleccionar un representante", "warning");
                return;
            }
            url = 'Reportes/justificativo.php';
            params.push('id=' + idRepresentante);

            var poseeMotivoAsis = $('input[name="poseeMotivoAsistencia"]:checked').val();
            var motivo = (poseeMotivoAsis == 'SI') ? $('#listMotivo').val() : '';

            if (poseeMotivoAsis == 'SI' && !motivo) {
                swal("Atención", "Debe seleccionar o escribir un motivo si marcó SÍ", "warning");
                return;
            }

            if (motivo) {
                guardarMotivo(motivo, 'motivo_justificativo', 0, idRepresentante);
                params.push('motivo=' + encodeURIComponent(motivo));
            }

            // Fecha automática (hoy)
            var now = new Date();
            var dd = String(now.getDate()).padStart(2, '0');
            var mm = String(now.getMonth() + 1).padStart(2, '0'); //January is 0!
            var yyyy = now.getFullYear();
            var today = yyyy + '-' + mm + '-' + dd;
            params.push('fecha=' + today);
            break;

        case 'lista_alumno':
            url = 'Reportes/lista_alumno.php';
            var sexoAlumno = document.getElementById('filtroSexoAlumno').value;
            var estatusAlumno = document.getElementById('filtroEstatusAlumno').value;
            if (sexoAlumno && sexoAlumno !== 'all') params.push('sexo=' + sexoAlumno);
            if (estatusAlumno && estatusAlumno !== 'all') params.push('estatus=' + estatusAlumno);
            break;

        case 'lista_profesor':
            url = 'Reportes/lista_profesor.php';
            var sexoProfesor = document.getElementById('filtroSexoProfesor').value;
            var estatusProfesor = document.getElementById('filtroEstatusProfesor').value;
            var esRepresentante = document.getElementById('filtroEsRepresentante').value;
            if (sexoProfesor && sexoProfesor !== 'all') params.push('sexo=' + sexoProfesor);
            if (estatusProfesor && estatusProfesor !== 'all') params.push('estatus=' + estatusProfesor);
            if (esRepresentante && esRepresentante !== 'all') params.push('es_representante=' + esRepresentante);
            break;

        case 'lista_representante':
            url = 'Reportes/lista_representante.php';
            var sexoRepresentante = document.getElementById('filtroSexoRepresentante').value;
            var estatusRepresentante = document.getElementById('filtroEstatusRepresentante').value;
            var tipoRepresentante = document.getElementById('filtroTipoRepresentante').value;
            var esProfesor = document.getElementById('filtroEsProfesor').value;
            if (sexoRepresentante && sexoRepresentante !== 'all') params.push('sexo=' + sexoRepresentante);
            if (estatusRepresentante && estatusRepresentante !== 'all') params.push('estatus=' + estatusRepresentante);
            if (tipoRepresentante && tipoRepresentante !== 'all') params.push('tipo=' + tipoRepresentante);
            if (esProfesor && esProfesor !== 'all') params.push('es_profesor=' + esProfesor);
            break;

        case 'lista_periodo':
            url = 'Reportes/lista_periodo.php';
            var periodoAnio = document.getElementById('filtroPeriodoAnio').value;
            if (periodoAnio && periodoAnio !== 'all') params.push('periodo_id=' + periodoAnio);
            break;

        case 'lista_graysec':
            url = 'Reportes/lista_graysec.php';
            var grado = document.getElementById('listGrado').value;
            var seccion = document.getElementById('listSeccion').value;
            var estatus = document.getElementById('listEstatusGrado').value;
            var periodoGrado = document.getElementById('listPeriodoGradoId').value;

            if (grado) params.push('grado=' + grado);
            if (seccion) params.push('seccion=' + encodeURIComponent(seccion));
            if (estatus && estatus !== 'all') params.push('estatus=' + estatus);
            if (periodoGrado) params.push('periodo_id=' + periodoGrado);
            break;

        case 'lista_inscribir':
            url = 'Reportes/lista_inscribir.php';
            var periodo = document.getElementById('listPeriodo').value;
            var sexo = document.getElementById('filtroSexoInscripcion').value;
            var gradoInsc = document.getElementById('filtroGradoInscripcion').value;
            var seccionInsc = document.getElementById('filtroSeccionInscripcion').value;
            var estatusInsc = document.getElementById('filtroEstatusInscripcion').value;
            var edadInsc = document.getElementById('filtroEdadInscripcion').value;

            if (periodo) params.push('periodo_id=' + periodo);
            if (sexo && sexo !== 'all') params.push('sexo=' + sexo);
            if (gradoInsc && gradoInsc !== 'all') params.push('grado=' + gradoInsc);
            if (seccionInsc && seccionInsc !== 'all') params.push('seccion=' + encodeURIComponent(seccionInsc));
            if (estatusInsc && estatusInsc !== 'all') params.push('estatus=' + estatusInsc);
            if (edadInsc) params.push('edad=' + edadInsc);
            break;
    }

    if (url) {
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        window.open(url, '_blank');
    }
}

function guardarMotivo(motivo, tipo, alumno_id, representante_id) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/reportes/ajax-reportes.php';
    var formData = new FormData();
    formData.append('action', 'save_motivo');
    formData.append('motivo', motivo);
    formData.append('tipo', tipo);
    formData.append('alumno_id', alumno_id);
    formData.append('representante_id', representante_id);

    request.open('POST', ajaxUrl, true);
    request.send(formData);
}

function cargarEdades() {
    var selectEdad = document.getElementById('filtroEdadInscripcion');
    if (selectEdad) {
        var html = '<option value="">Todas</option>';
        for (var i = 4; i <= 15; i++) {
            html += '<option value="' + i + '">' + i + ' años</option>';
        }
        selectEdad.innerHTML = html;
    }
}
