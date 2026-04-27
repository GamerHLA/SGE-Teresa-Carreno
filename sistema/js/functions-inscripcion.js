/**
 * FUNCTIONS-INSCRIPCION.JS
 * ========================
 * 
 * Gestión completa del módulo de inscripciones del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de inscripciones por alumno y curso
 * - Formulario de creación y edición de inscripciones
 * - Validación de representantes asignados antes de inscribir
 * - Sistema de filtrado de cursos por grado anterior del alumno
 * - Validación de cupos disponibles en cursos
 * - Detección automática de alumnos repitientes
 * - Visualización de inscripción anterior del alumno
 * - Información detallada del curso (grado, sección, turno, período, profesor, cupo)
 * - Carga automática de representante y parentesco del alumno
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

var tableInscripciones;
var previousEnrollmentData = null; // Variable para almacenar la inscripción anterior
var currentEditingCursoId = null; // Variable para el curso actual al editar

window.addEventListener('DOMContentLoaded', function () {
    tableInscripciones = $('#tableInscripciones').DataTable({
        "processing": true,
        "serverSide": false,
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        "ajax": {
            "url": "./models/inscripciones/table_inscripciones.php",
            "dataSrc": ""
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "render": function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { "data": "cedula_alumno" },
            { "data": "nombre_alumno" },
            { "data": "apellido_alumno" },
            { "data": "grado" },
            { "data": "seccion" },
            { "data": "turno" },
            { "data": "periodo_completo" },
            { "data": "estatusI" },
            { "data": "options" }
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[0, "asc"]]
    });

    // CREAR INSCRIPCION
    var formInscripcion = document.querySelector('#formInscripcion');
    formInscripcion.onsubmit = function (e) {
        e.preventDefault();

        var idInscripcion = document.querySelector('#idInscripcion').value;
        var alumno = document.querySelector('#listAlumno').value;
        var curso = document.querySelector('#listCurso').value;
        var status = document.querySelector('#listStatus').value;
        var representante = document.querySelector('#txtRepresentante').value;

        if (alumno == '' || curso == '' || status == '') {
            swal('Atención', 'Todos los campos son necesarios', 'error');
            return false;
        }

        if (representante == '' || representante == 'No asignado') {
            swal('Atención', 'El alumno debe tener un representante asignado para poder inscribirse.', 'error');
            return false;
        }

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/inscripciones/ajax_inscripciones.php';
        var formData = new FormData(formInscripcion);
        request.open('POST', ajaxUrl, true);
        request.send(formData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    $('#modalFormInscripcion').modal('hide');
                    formInscripcion.reset();
                    swal('¡Crear Inscripción!', objData.msg, 'success');
                    tableInscripciones.ajax.reload();

                    // Si existe la tabla de alumnos (estamos en lista_alumnos.php), recargarla también
                    if (typeof tableAlumnos !== 'undefined' && tableAlumnos !== null) {
                        tableAlumnos.ajax.reload();
                    }
                } else {
                    swal('Atención', objData.msg, 'error');
                }
            }
        }
    };
});

window.addEventListener('load', function () {
    getOptionAlumnos();
    getOptionCursos();
}, false);

function getOptionAlumnos(callback) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-alumnos.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var optionsHtml = '';
            data.forEach(function (valor) {
                var cedulaMostrar = (valor.cedula && valor.cedula != '') ? valor.cedula : 'S/C';
                if (valor.nacionalidad) {
                    cedulaMostrar = valor.nacionalidad + '-' + cedulaMostrar;
                }
                optionsHtml += '<option value="' + valor.alumno_id + '">' + cedulaMostrar + ' - ' + valor.nombre + ' ' + valor.apellido + '</option>';
            });
            document.querySelector('#listAlumno').innerHTML = optionsHtml;

            // Agregar evento change al select de alumno para cargar representante
            var selectAlumno = document.querySelector('#listAlumno');
            if (selectAlumno) {
                selectAlumno.addEventListener('change', function () {
                    getRepresentanteAlumno(this.value);
                });
            }

            // Ejecutar callback si se proporciona
            if (callback && typeof callback === 'function') {
                callback();
            }
        }
    };
}

// Variable global para almacenar los cursos
var cursosData = [];

function getOptionCursos() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-cursos.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            cursosData = data; // Guardar datos globalmente

            // Verificar si ya tenemos datos de inscripción anterior para filtrar
            if (previousEnrollmentData && previousEnrollmentData.grado) {
                renderCursoOptions(previousEnrollmentData.grado);
            } else {
                renderCursoOptions(null);
            }

            // Agregar evento change al select de curso
            var selectCurso = document.querySelector('#listCurso');
            if (selectCurso) {
                selectCurso.addEventListener('change', function () {
                    updatePeriodoAndCursoInfo();
                });
            }
        }
    };
}

function renderCursoOptions(previousGrade, currentCursoId = null) {
    console.log('Rendering options. Previous Grade:', previousGrade, 'Current Curso:', currentCursoId);
    var optionsHtml = '<option value="">Seleccionar Grado/Sección</option>';

    var filteredCursos = cursosData;

    // Primer filtro: Grado (si existe inscripción anterior)
    if (previousGrade !== null) {
        var prevGradeInt = parseInt(previousGrade);
        filteredCursos = filteredCursos.filter(function (curso) {
            var cursoGrade = parseInt(curso.grado);
            return cursoGrade === prevGradeInt || cursoGrade === (prevGradeInt + 1);
        });
    }

    // Segundo filtro: Cupo disponible
    // Solo mostramos cursos con cupo, EXCEPTO si es el curso actual que se está editando
    filteredCursos = filteredCursos.filter(function (curso) {
        var cupoTotal = parseInt(curso.cupo) || 0;
        var inscritos = parseInt(curso.inscritos) || 0;
        var tieneCupo = inscritos < cupoTotal;
        var esCursoActual = currentCursoId != null && curso.curso_id == currentCursoId;

        return tieneCupo || esCursoActual;
    });

    filteredCursos.forEach(function (valor) {
        var turnoTexto = valor.tipo_turno || 'Sin turno';
        var periodoTexto = valor.periodo_completo || 'Sin periodo';
        optionsHtml += '<option value="' + valor.curso_id + '" data-periodo="' + periodoTexto + '" data-periodo-id="' + valor.periodo_id + '" data-turno="' + valor.tipo_turno + '" data-turno-id="' + valor.turno_id + '" data-grado="' + valor.grado + '" data-seccion="' + valor.seccion + '" data-cupo="' + valor.cupo + '" data-inscritos="' + (valor.inscritos || 0) + '">' + valor.grado + '° - Sección ' + valor.seccion + ' - ' + turnoTexto + ' - ' + periodoTexto + '</option>';
    });

    document.querySelector('#listCurso').innerHTML = optionsHtml;
}


function updatePeriodoAndCursoInfo(isInitialLoad = false) {
    var cursoId = document.querySelector('#listCurso').value;
    var cursoInfoContainer = document.querySelector('#cursoInfoContainer');
    var cursoInfoContent = document.querySelector('#cursoInfoContent');

    if (cursoId) {
        var curso = cursosData.find(function (c) { return c.curso_id == cursoId; });
        if (curso) {
            // Validación de repitiente
            if (!isInitialLoad && previousEnrollmentData &&
                curso.grado == previousEnrollmentData.grado) {

                swal({
                    title: "Atención",
                    text: "Alumno ya cursó este Grado. ¿Es repitiente?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí",
                    cancelButtonText: "No",
                    closeOnConfirm: true,
                    closeOnCancel: true
                }, function (isConfirm) {
                    if (isConfirm) {
                        // Si es repitiente, mostrar la información del curso
                        displayCursoInfo(curso);
                    } else {
                        // Si no es repitiente (equivocación), limpiar selección
                        document.querySelector('#listCurso').value = "";
                        cursoInfoContainer.style.display = 'none';
                        cursoInfoContent.innerHTML = '';
                        document.querySelector('#listPeriodoId').value = '';
                        document.querySelector('#listTurnoId').value = '';
                    }
                });
                return; // Detener ejecución hasta que el usuario responda
            }

            displayCursoInfo(curso);
        }
    } else {
        // Ocultar recuadro si no hay curso seleccionado
        cursoInfoContainer.style.display = 'none';
        cursoInfoContent.innerHTML = '';
        document.querySelector('#listPeriodoId').value = '';
        document.querySelector('#listTurnoId').value = '';
    }
}

function displayCursoInfo(curso) {
    var cursoInfoContainer = document.querySelector('#cursoInfoContainer');
    var cursoInfoContent = document.querySelector('#cursoInfoContent');

    // Guardar periodo_id y turno_id en campos hidden
    document.querySelector('#listPeriodoId').value = curso.periodo_id;
    document.querySelector('#listTurnoId').value = curso.turno_id;

    // Calcular cupo disponible
    var cupoTotal = parseInt(curso.cupo) || 0;
    var inscritos = parseInt(curso.inscritos) || 0;
    var disponible = cupoTotal - inscritos;
    if (disponible < 0) disponible = 0;

    // Mostrar información del curso en el recuadro
    var profesorNombre = curso.nombre && curso.apellido ? curso.nombre + ' ' + curso.apellido : 'No asignado';
    var htmlInfo = '<div class="row">' +
        '<div class="col-md-6"><strong>Grado:</strong> ' + curso.grado + '°</div>' +
        '<div class="col-md-6"><strong>Sección:</strong> ' + curso.seccion + '</div>' +
        '<div class="col-md-6"><strong>Periodo:</strong> ' + (curso.periodo_completo || 'No asignado') + '</div>' +
        '<div class="col-md-6"><strong>Turno:</strong> ' + (curso.tipo_turno || 'No asignado') + '</div>' +
        '<div class="col-md-6"><strong>Cupo:</strong> ' + disponible + ' / ' + cupoTotal + ' disponibles</div>' +
        '<div class="col-md-6"><strong>Profesor:</strong> ' + profesorNombre + '</div>' +
        '</div>';

    cursoInfoContent.innerHTML = htmlInfo;
    cursoInfoContainer.style.display = 'block';
}

function getRepresentanteAlumno(alumnoId) {
    if (!alumnoId) {
        document.querySelector('#txtRepresentante').value = '';
        document.querySelector('#txtParentesco').value = '';
        return;
    }

    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-representante-alumno.php?alumno_id=' + alumnoId;
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var objData = JSON.parse(request.responseText);
            if (objData.status && objData.data) {
                document.querySelector('#txtRepresentante').value = objData.data.nombre_completo;
                document.querySelector('#txtParentesco').value = objData.data.parentesco;
            } else {
                document.querySelector('#txtRepresentante').value = objData.data ? objData.data.nombre_completo : 'No asignado';
                document.querySelector('#txtParentesco').value = objData.data ? objData.data.parentesco : 'No asignado';
            }
        }
    };

    // También cargar inscripción anterior del alumno
    getPreviousEnrollment(alumnoId);
}

function getPreviousEnrollment(alumnoId) {
    var previousContainer = document.querySelector('#previousEnrollmentContainer');
    var previousInfo = document.querySelector('#previousEnrollmentInfo');

    if (!alumnoId) {
        previousContainer.style.display = 'none';
        previousInfo.innerHTML = '';
        previousEnrollmentData = null;
        return;
    }

    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/inscripciones/get_previous_enrollment.php?alumno_id=' + alumnoId;
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var objData = JSON.parse(request.responseText);
            if (objData.status && objData.data) {
                previousEnrollmentData = objData.data; // Guardar datos en variable global
                // Mostrar información de inscripción anterior
                var htmlInfo = '<strong>Último Grado/Sección:</strong> ' + objData.data.grado + '° - Sección ' + objData.data.seccion + '<br>' +
                    '<strong>Periodo:</strong> ' + objData.data.periodo + '<br>' +
                    '<strong>Turno:</strong> ' + objData.data.tipo_turno;
                previousInfo.innerHTML = htmlInfo;
                previousContainer.style.display = 'block';

                // RE-RENDER OPTIONS
                // Si estamos EDITANDO (currentEditingCursoId != null), mostramos TODOS los grados
                // Si es NUEVA INSCRIPCIÓN, filtramos por grado
                if (currentEditingCursoId) {
                    renderCursoOptions(null, currentEditingCursoId);
                } else {
                    renderCursoOptions(objData.data.grado, null);
                }
            } else {
                previousEnrollmentData = null;
                // No hay inscripción anterior, ocultar el contenedor
                previousContainer.style.display = 'none';
                previousInfo.innerHTML = '';
                // Render all options (sin filtro de grado)
                renderCursoOptions(null, currentEditingCursoId);
            }
        }
    };
}


function openModalInscripcion(alumnoId) {
    currentEditingCursoId = null; // Reset al crear
    document.querySelector('#idInscripcion').value = "";
    document.querySelector('#titleModal').innerHTML = 'Nueva Inscripción';
    document.querySelector('.modal-header').classList.replace('updateRegister', 'headerRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-info', 'btn-primary');
    document.querySelector('#btnText').innerHTML = 'Guardar';
    document.querySelector('#formInscripcion').reset();

    // Limpiar campos y ocultar información del curso
    document.querySelector('#listPeriodoId').value = '';
    document.querySelector('#listTurnoId').value = '';
    document.querySelector('#txtRepresentante').value = '';
    document.querySelector('#txtParentesco').value = '';
    document.querySelector('#cursoInfoContainer').style.display = 'none';
    document.querySelector('#cursoInfoContent').innerHTML = '';

    // Ocultar contenedor de inscripción anterior al abrir el modal
    document.querySelector('#previousEnrollmentContainer').style.display = 'none';
    document.querySelector('#previousEnrollmentInfo').innerHTML = '';
    previousEnrollmentData = null;

    // Cargar todas las opciones necesarias
    getOptionCursos();

    // Si se proporciona un alumno_id, cargar las opciones y preseleccionar
    if (alumnoId) {
        // Asegurarse de que las opciones estén cargadas antes de preseleccionar
        getOptionAlumnos(function () {
            // Preseleccionar el alumno después de cargar las opciones
            var selectAlumno = document.querySelector('#listAlumno');
            if (selectAlumno) {
                selectAlumno.value = alumnoId;
                // Cargar representante del alumno preseleccionado
                getRepresentanteAlumno(alumnoId);
                // Cargar inscripción anterior del alumno
                getPreviousEnrollment(alumnoId);
            }
        });
    } else {
        // Si no hay alumno_id, solo cargar las opciones normalmente
        getOptionAlumnos();
    }

    $('#modalFormInscripcion').modal('show');
}

function fntEditInscripcion(idInscripcion) {
    currentEditingCursoId = null; // Reset inicial
    document.querySelector('#titleModal').innerHTML = 'Actualizar Inscripción';
    document.querySelector('.modal-header').classList.replace('headerRegister', 'updateRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-primary', 'btn-info');
    document.querySelector('#btnText').innerHTML = 'Actualizar';

    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/inscripciones/edit_inscripciones.php?id=' + idInscripcion;
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var objData = JSON.parse(request.responseText);
            if (objData.status) {
                document.querySelector('#idInscripcion').value = objData.data.inscripcion_id;
                currentEditingCursoId = objData.data.curso_id; // Guardamos el curso actual

                // Cargar alumnos y seleccionar
                getOptionAlumnos(function () {
                    document.querySelector('#listAlumno').value = objData.data.alumno_id;
                    getRepresentanteAlumno(objData.data.alumno_id);
                    getPreviousEnrollment(objData.data.alumno_id);
                });

                // Cargar cursos y seleccionar
                // Nota: Primero aseguramos que las opciones base estén cargadas
                // Luego buscamos el curso específico. Si no está en filtrado inicial, quizás necesitemos lógica extra,
                // pero por ahora asumimos que está en la lista global de cursosData.

                // Forzar carga de opciones si está vacío
                if (cursosData.length == 0) {
                    var requestC = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxUrlC = './models/options/options-cursos.php';
                    requestC.open('GET', ajaxUrlC, true);
                    requestC.send();
                    requestC.onreadystatechange = function () {
                        if (requestC.readyState == 4 && requestC.status == 200) {
                            cursosData = JSON.parse(requestC.responseText);
                            // Renderizar opciones sin filtro de grado pero con filtro de cupo (pasando curso actual)
                            renderCursoOptions(null, currentEditingCursoId);
                            document.querySelector('#listCurso').value = objData.data.curso_id;
                            updatePeriodoAndCursoInfo(true);
                        }
                    }
                } else {
                    renderCursoOptions(null, currentEditingCursoId); // Mostrar opciones con filtro de cupo
                    document.querySelector('#listCurso').value = objData.data.curso_id;
                    updatePeriodoAndCursoInfo(true);
                }

                document.querySelector('#listStatus').value = objData.data.estatusI;

                $('#modalFormInscripcion').modal('show');
            } else {
                swal('Atención', objData.msg, 'error');
            }
        }
    }
}

// Event delegation for edit buttons
$(document).on('click', '.btnEditInscripcion', function () {
    var idInscripcion = $(this).attr('rl');
    fntEditInscripcion(idInscripcion);
});