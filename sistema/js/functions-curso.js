/**
 * FUNCTIONS-CURSO.JS
 * ==================
 * 
 * Gestión completa del módulo de cursos (grados y secciones) del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de cursos filtrable por período
 * - Formulario de creación y edición de cursos
 * - Asignación de grado (1° a 6°), sección (A, B, C, D)
 * - Gestión de cupos (1-50 alumnos por curso)
 * - Asignación de turno (Mañana/Tarde) con auto-selección según sección
 * - Asignación de profesor al curso
 * - Asignación de período escolar
 * - Validación de disponibilidad del profesor (no puede estar en dos cursos del mismo turno/período)
 * - Generación automática de cursos predeterminados (1° a 6° con secciones A y B)
 * - Activación/Inhabilitación de cursos
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

var tableCursos;

document.addEventListener('DOMContentLoaded', function () {
    tableCursos = $('#tableCursos').DataTable({
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
            "url": "./models/cursos/table_cursos.php",
            "dataSrc": "",
            "data": function (d) {
                var selectPeriodo = document.getElementById('selectPeriodo');
                if (selectPeriodo && selectPeriodo.value) {
                    d.periodo_id = selectPeriodo.value;
                }
                return d;
            }
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
            { "data": "grado_seccion" },
            { "data": "turno" },
            { "data": "cupo" },
            { "data": "profesor_nombre" },
            { "data": "estatusC" },
            { "data": "options" }
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[0, "asc"]],
        "drawCallback": function () {
            setTimeout(function () {
                editCurso();
                delCurso();
                activateCurso();
            }, 100);
        }
    });

    // CREAR CURSO
    var formCurso = document.querySelector('#formCurso');
    formCurso.onsubmit = function (e) {
        e.preventDefault();

        // Obtener todos los valores
        var idCurso = document.querySelector('#idCurso').value;
        var grado = document.querySelector('#txtGrado').value;
        var seccion = document.querySelector('#txtSeccion').value;
        var cupo = document.querySelector('#txtCupo').value;
        var turno = document.querySelector('#listTurno').value;
        var profesor = document.querySelector('#listProfesor').value;
        var idProfesor = document.querySelector('#listProfesor').value;
        var periodo = document.querySelector('#listPeriodo').value;
        // var status = document.querySelector('#listStatus').value; // Removed status field reference

        // Validaciones completa
        if (grado == '' || seccion == '' || turno == '' || periodo == '') { // Removed status, cupo, profesor from validation
            swal('Atención', 'Todos los campos son necesarios', 'error');
            return false;
        }

        if (cupo != '' && (cupo <= 0 || cupo > 50)) {
            swal('Atención', 'El cupo debe ser un número entre 1 y 50', 'error');
            return false;
        }

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/cursos/ajax-cursos.php';
        request.open('POST', ajaxUrl, true);
        var strData = new FormData(formCurso);
        request.send(strData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    $('#modalFormCurso').modal('hide');
                    formCurso.reset();
                    swal('Grados/Secciones', objData.msg, 'success');
                    tableCursos.ajax.reload(function () {
                        // Reasignar eventos después de recargar
                        setTimeout(function () {
                            delCurso();
                            editCurso();
                            activateCurso();
                        }, 100);
                    });
                } else {
                    // Mostrar errores específicos si existen
                    if (objData.errors) {
                        var errorMsg = objData.errors.join('\n');
                        swal('Errores de validación', errorMsg, 'error');
                    } else {
                        swal('Atención', objData.msg, 'error');
                    }
                }
            }
        }
    }

    // Validación en tiempo real para el profesor
    var profesorSelect = document.querySelector('#listProfesor');
    var turnoSelect = document.querySelector('#listTurno');
    var periodoSelect = document.querySelector('#listPeriodo');

    if (profesorSelect && turnoSelect && periodoSelect) {
        var eventFields = [profesorSelect, turnoSelect, periodoSelect];
        eventFields.forEach(function (field) {
            field.addEventListener('change', function () {
                ejecutarValidacionProfesor();
            });
        });
    }
});

function ejecutarValidacionProfesor() {
    var idCurso = document.querySelector('#idCurso').value;
    var profesor = document.querySelector('#listProfesor').value;
    var turno = document.querySelector('#listTurno').value;
    var periodo = document.querySelector('#listPeriodo').value;

    if (profesor != '' && turno != '' && periodo != '') {
        var formData = new FormData();
        formData.append('action', 'checkProfesor');
        formData.append('idCurso', idCurso);
        formData.append('listProfesor', profesor);
        formData.append('listTurno', turno);
        formData.append('listPeriodo', periodo);

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        request.open('POST', './models/cursos/ajax-cursos.php', true);
        request.send(formData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (!objData.status) {
                    swal('Atención', objData.msg, 'error');
                    document.querySelector('#listProfesor').value = '';
                }
            }
        }
    }
}

window.addEventListener('load', function () {
    delCurso();
    editCurso();
    activateCurso();
    getOptionsProfesores();
    getOptionsTurnos();
    getOptionsPeriodos();
    initAutoSelectTurno();
}, false);

// Función para auto-seleccionar turno según sección
function initAutoSelectTurno() {
    var seccionSelect = document.querySelector('#txtSeccion');
    if (seccionSelect) {
        seccionSelect.addEventListener('change', function () {
            var seccion = this.value;
            var turnoSelect = document.querySelector('#listTurno');

            if (!turnoSelect) return;

            // Esperar a que las opciones de turno estén cargadas
            setTimeout(function () {
                var options = turnoSelect.options;
                var turnoSeleccionado = '';

                // A o B = Mañana, C o D = Tarde
                if (seccion === 'A' || seccion === 'B') {
                    // Buscar la opción que contenga "mañana" (case insensitive)
                    for (var i = 0; i < options.length; i++) {
                        if (options[i].text.toLowerCase().includes('mañana')) {
                            turnoSelect.value = options[i].value;
                            break;
                        }
                    }
                } else if (seccion === 'C' || seccion === 'D') {
                    // Buscar la opción que contenga "tarde" (case insensitive)
                    for (var i = 0; i < options.length; i++) {
                        if (options[i].text.toLowerCase().includes('tarde')) {
                            turnoSelect.value = options[i].value;
                            break;
                        }
                    }
                } else {
                    // Si no hay sección seleccionada, resetear turno
                    turnoSelect.value = '';
                }
            }, 100);
        });
    }
}

function getOptionsProfesores() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-profesor.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var optionsHtml = '<option value="">Seleccionar Profesor</option>';
            var data = JSON.parse(request.responseText);
            data.forEach(function (valor) {
                optionsHtml += '<option value="' + valor.profesor_id + '">' + valor.nombre + ' ' + valor.apellido + '</option>';
            });
            document.querySelector('#listProfesor').innerHTML = optionsHtml;
        }
    }
}

function getOptionsTurnos() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-turnos.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var optionsHtml = '<option value="">Seleccionar Turno</option>';
            var data = JSON.parse(request.responseText);
            data.forEach(function (valor) {
                // Mostrar el tipo_turno en el texto de la opción, usando turno_id como valor
                optionsHtml += '<option value="' + valor.turno_id + '">' + valor.tipo_turno + '</option>';
            });
            document.querySelector('#listTurno').innerHTML = optionsHtml;
        }
    }
}

function getOptionsPeriodos() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-periodo.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var optionsHtml = '';
            var data = JSON.parse(request.responseText);
            data.forEach(function (valor, index) {
                // Seleccionar el primero por defecto (el más reciente activo)
                var selected = (index === 0) ? 'selected' : '';
                optionsHtml += '<option value="' + valor.periodo_id + '" ' + selected + '>' + valor.anio_inicio + ' - ' + valor.anio_fin + '</option>';
            });
            document.querySelector('#listPeriodo').innerHTML = optionsHtml;
        }
    }
}

function editCurso() {
    var btnEditCurso = document.querySelectorAll('.btnEditCurso');
    btnEditCurso.forEach(function (btnEditCurso) {
        btnEditCurso.addEventListener('click', function () {
            document.querySelector('#titleModal').innerHTML = 'Actualizar Grado/Sección';
            document.querySelector('.modal-header').classList.replace('headerRegister', 'updateRegister');
            document.querySelector('#btnActionForm').classList.replace('btn-primary', 'btn-info');
            document.querySelector('#btnText').innerHTML = 'Actualizar';

            var idCurso = this.getAttribute('rl');

            var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            var ajaxUrl = './models/cursos/edit_cursos.php?id=' + idCurso;
            request.open('GET', ajaxUrl, true);
            request.send();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    var objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        // Llenar todos los campos del formulario
                        document.querySelector('#idCurso').value = objData.data.curso_id;
                        document.querySelector('#txtGrado').value = objData.data.grado;
                        document.querySelector('#txtSeccion').value = objData.data.seccion;
                        document.querySelector('#txtCupo').value = objData.data.cupo;
                        document.querySelector('#listTurno').value = objData.data.turno_id;
                        document.querySelector('#listProfesor').value = objData.data.profesor_id;
                        document.querySelector('#listPeriodo').value = objData.data.periodo_id;
                        // document.querySelector('#listStatus').value = objData.data.estatusC; // Removed status field reference

                        document.querySelector("#modalFormCurso").querySelector(".modal-title").textContent = 'Editar Grado/Sección'; // Corrected line
                        // Configurar opciones de estado // Removed status field reference
                        // Load all dropdowns
                        // var htmlOption = ''; // Removed status field reference
                        // if (objData.data.estatusC == 1) { // Removed status field reference
                        //     htmlOption = '<option value="1" selected>Activo</option>' + // Removed status field reference
                        //         '<option value="2">Inactivo</option>'; // Removed status field reference
                        // } else { // Removed status field reference
                        //     htmlOption = '<option value="1">Activo</option>' + // Removed status field reference
                        //         '<option value="2" selected>Inactivo</option>'; // Removed status field reference
                        // } // Removed status field reference
                        // document.querySelector('#listStatus').innerHTML = htmlOption; // Removed status field reference

                        $('#modalFormCurso').modal('show');
                    } else {
                        swal('Atención', objData.msg, 'error');
                    }
                }
            }
        })
    })
}

function delCurso() {
    var btnDelCurso = document.querySelectorAll('.btnDelCurso');
    btnDelCurso.forEach(function (btnDelCurso) {
        btnDelCurso.addEventListener('click', function () {
            var idCurso = this.getAttribute('rl');

            swal({
                title: "¿Inhabilitar Grado/Sección?",
                text: "¿Realmente desea inhabilitar el Grado/Sección?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, inhabilitar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (Confirm) {
                if (Confirm) {
                    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxDelCurso = './models/cursos/delet_curso.php';
                    var strData = "idCurso=" + idCurso;
                    request.open('POST', ajaxDelCurso, true);
                    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    request.send(strData);
                    request.onreadystatechange = function () {
                        if (request.readyState == 4 && request.status == 200) {
                            var objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                swal("¡Inhabilitado!", objData.msg, "success");
                                tableCursos.ajax.reload(function () {
                                    setTimeout(function () {
                                        delCurso();
                                        editCurso();
                                        activateCurso();
                                    }, 100);
                                });
                            } else {
                                swal("Atención", objData.msg, "error");
                            }
                        }
                    }
                }
            })
        })
    })
}

function openModalCurso() {
    document.querySelector('#idCurso').value = "";
    document.querySelector('#formCurso').reset();
    document.querySelector('#titleModal').innerHTML = 'Crear Grado/Sección';
    document.querySelector('.modal-header').classList.replace('updateRegister', 'headerRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-info', 'btn-primary');
    document.querySelector('#btnText').innerHTML = 'Guardar';
    $('#modalFormCurso').modal('show');
}

function generarCursosPredeterminados() {
    swal({
        title: "¿Generar Grados Predeterminados?",
        text: "¿Desea generar los grados 1° al 6° con secciones A y B para el período actual? (No se duplicarán los existentes)",
        type: "info",
        showCancelButton: true,
        confirmButtonText: "Sí, generar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false,
        closeOnCancel: true
    }, function (isConfirm) {
        if (isConfirm) {
            var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            var ajaxUrl = './models/cursos/generate_defaults.php';
            request.open('GET', ajaxUrl, true);
            request.send();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    var objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        swal("¡Generado!", objData.msg, "success");
                        tableCursos.ajax.reload(function () {
                            setTimeout(function () {
                                delCurso();
                                editCurso();
                                activateCurso();
                            }, 100);
                        });
                    } else {
                        swal("Atención", objData.msg, "error");
                    }
                }
            }
        }
    });
}