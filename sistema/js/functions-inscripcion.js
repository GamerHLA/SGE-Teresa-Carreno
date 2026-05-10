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
        var grado = document.querySelector('#listGrado').value;
        var seccion = document.querySelector('#listSeccion').value;
        var turno = document.querySelector('#listTurno').value;
        var profesor = document.querySelector('#listProfesor').value;
        var status = document.querySelector('#listStatus').value;
        var representante = document.querySelector('#txtRepresentante').value;

        if (alumno == '' || grado == '' || seccion == '' || turno == '' || profesor == '' || status == '') {
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
                    if (typeof tableInscripciones !== 'undefined' && tableInscripciones !== null) {
                        tableInscripciones.ajax.reload();
                    }

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
    getOptionGrados();
    getOptionSecciones();
    getOptionTurnos();
    getOptionProfesores();
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

function getOptionGrados(callback) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-grados.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var optionsHtml = '<option value="">Seleccionar Grado</option>';
            data.forEach(function (valor) {
                optionsHtml += '<option value="' + valor.id_grado + '">' + valor.grado + '°</option>';
            });
            document.querySelector('#listGrado').innerHTML = optionsHtml;
            if (callback) callback();
        }
    };
}

function getOptionSecciones(callback) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-secciones.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var optionsHtml = '<option value="">Seleccionar Sección</option>';
            data.forEach(function (valor) {
                optionsHtml += '<option value="' + valor.id_seccion + '">' + valor.seccion + '</option>';
            });
            document.querySelector('#listSeccion').innerHTML = optionsHtml;
            if (callback) callback();
        }
    };
}

function getOptionTurnos(callback) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-turnos.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var optionsHtml = '<option value="">Seleccionar Turno</option>';
            data.forEach(function (valor) {
                optionsHtml += '<option value="' + valor.turno_id + '">' + valor.tipo_turno + '</option>';
            });
            document.querySelector('#listTurno').innerHTML = optionsHtml;
            if (callback) callback();
        }
    };
}

function getOptionProfesores(callback) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-profesor.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var optionsHtml = '<option value="">Seleccionar Profesor</option>';
            data.forEach(function (valor) {
                optionsHtml += '<option value="' + valor.profesor_id + '">' + valor.nombre + ' ' + valor.apellido + '</option>';
            });
            document.querySelector('#listProfesor').innerHTML = optionsHtml;
            if (callback) callback();
        }
    };
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
            } else {
                previousEnrollmentData = null;
                // No hay inscripción anterior, ocultar el contenedor
                previousContainer.style.display = 'none';
                previousInfo.innerHTML = '';
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

    // Limpiar campos y ocultar información
    document.querySelector('#listPeriodoId').value = '';
    document.querySelector('#txtRepresentante').value = '';
    document.querySelector('#txtParentesco').value = '';

    // Ocultar contenedor de inscripción anterior al abrir el modal
    document.querySelector('#previousEnrollmentContainer').style.display = 'none';
    document.querySelector('#previousEnrollmentInfo').innerHTML = '';
    previousEnrollmentData = null;

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

                // Cargar alumnos y seleccionar
                getOptionAlumnos(function () {
                    document.querySelector('#listAlumno').value = objData.data.alumno_id;
                    getRepresentanteAlumno(objData.data.alumno_id);
                    getPreviousEnrollment(objData.data.alumno_id);
                });

                document.querySelector('#listGrado').value = objData.data.grado_id;
                document.querySelector('#listSeccion').value = objData.data.seccion_id;
                document.querySelector('#listTurno').value = objData.data.turno_id;
                document.querySelector('#listProfesor').value = objData.data.profesor_id;
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