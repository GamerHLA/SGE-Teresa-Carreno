/**
 * FUNCTIONS-PERIODO.JS
 * ====================
 * 
 * Gestión completa del módulo de períodos escolares del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de períodos escolares
 * - Formulario de creación y edición de períodos
 * - Validación automática de años (inicio y fin)
 * - Cálculo automático del año fin (año inicio + 1)
 * - Validación de años mínimos (>= 2025)
 * - Activación/Inhabilitación de períodos
 * - Visualización dinámica del período completo
 * - Solo permite un período activo a la vez
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

var tablePeriodos;

// FUNCIONES DE VALIDACIÓN PARA PERIODO ESCOLAR
function soloNumeros(event) {
    const charCode = (event.which) ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        event.preventDefault();
        return false;
    }
    return true;
}

function validarLongitud(input) {
    if (input.value.length > 4) {
        input.value = input.value.slice(0, 4);
    }
}

function validarAnioPeriodo() {
    const anioInicio = document.getElementById('anioInicio');
    const anioFin = document.getElementById('anioFin');
    const periodoInfo = document.getElementById('periodoInfo');

    // Validar longitud
    validarLongitud(anioInicio);

    // Calcular año fin automáticamente (un año después)
    if (anioInicio.value !== '') {
        const anioInicioNum = parseInt(anioInicio.value);
        if (!isNaN(anioInicioNum) && anioInicioNum >= 2025) {
            anioFin.value = anioInicioNum + 1;
            actualizarInfoPeriodo();
        }
    }
}

function validarAnioFin() {
    const anioInicio = document.getElementById('anioInicio');
    const anioFin = document.getElementById('anioFin');

    // Validar longitud
    validarLongitud(anioFin);

    // Validar que el año fin no sea menor al inicio
    if (anioInicio.value !== '' && anioFin.value !== '') {
        const anioInicioNum = parseInt(anioInicio.value);
        const anioFinNum = parseInt(anioFin.value);

        if (!isNaN(anioInicioNum) && !isNaN(anioFinNum)) {
            if (anioFinNum <= anioInicioNum) {
                swal('¡Atención!', 'El año de fin debe ser mayor al año de inicio', 'warning');
                anioFin.value = anioInicioNum + 1;
            }
            actualizarInfoPeriodo();
        }
    }
}

function actualizarInfoPeriodo() {
    const anioInicio = document.getElementById('anioInicio').value;
    const anioFin = document.getElementById('anioFin').value;
    const periodoInfo = document.getElementById('periodoInfo');

    if (anioInicio && anioFin) {
        periodoInfo.textContent = `Período: ${anioInicio} - ${anioFin}`;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    tablePeriodos = $('#tablePeriodos').DataTable({
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
            "url": "./models/Periodo/table_periodo.php",
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
            {
                "data": "periodo_completo",
                "className": "text-center"
            },
            { "data": "estatus" },
            { "data": "options" },
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[0, "asc"]]
    });

    // CREAR PERIODO ESCOLAR
    var formPeriodo = document.querySelector('#formPeriodo');
    formPeriodo.onsubmit = function (e) {
        e.preventDefault();
        var idPeriodo = document.querySelector('#idPeriodo').value;
        var anioInicio = document.querySelector('#anioInicio').value;
        var anioFin = document.querySelector('#anioFin').value;
        var status = document.querySelector('#listStatus').value;

        // Validar campos obligatorios
        if (anioInicio == '' || anioFin == '' || status == '') {
            swal('¡Atención!', 'Todos los campos son necesarios', 'error');
            return false;
        }

        // Validar que el año fin sea exactamente un año después del inicio
        const anioInicioNum = parseInt(anioInicio);
        const anioFinNum = parseInt(anioFin);

        if (anioFinNum !== anioInicioNum + 1) {
            swal('¡Atención!', 'El año de fin debe ser exactamente un año después del año de inicio', 'warning');
            return false;
        }

        // Validar que los años sean válidos
        if (anioInicioNum < 2025 || anioFinNum < 2025) {
            swal('¡Atención!', 'Los años deben ser iguales o mayores a 2025', 'error');
            return false;
        }

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/Periodo/ajax-periodo.php';
        request.open('POST', ajaxUrl, true);
        var strData = new FormData(formPeriodo);
        request.send(strData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    $('#modalFormPeriodo').modal('hide');
                    formPeriodo.reset();
                    swal('Crear Período Escolar', objData.msg, 'success');
                    tablePeriodos.ajax.reload(function () {
                        // Event listeners are already delegated
                    })
                } else {
                    swal('¡Atención!', objData.msg, 'error');
                }
            }
        }
    }

    // Inicializar la información del período al cargar
    actualizarInfoPeriodo();
});

window.addEventListener('load', function () {
    editPeriodo();
    delPeriodo();
    activatePeriodo();
}, false);

function editPeriodo() {
    $('#tablePeriodos tbody').on('click', '.btnEditPeriodo', function () {
        document.querySelector('#titleModal').innerHTML = 'Actualizar Período Escolar';
        document.querySelector('.modal-header').classList.replace('headerRegister', 'updateRegister');
        document.querySelector('#btnActionForm').classList.replace('btn-primary', 'btn-info');
        document.querySelector('#btnText').innerHTML = 'Actualizar';

        var idPeriodo = $(this).attr('rl');

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/Periodo/edit_periodo.php?id=' + idPeriodo;
        request.open('GET', ajaxUrl, true);
        request.send();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    document.querySelector('#idPeriodo').value = objData.data.periodo_id;
                    document.querySelector('#anioInicio').value = objData.data.anio_inicio;
                    document.querySelector('#anioFin').value = objData.data.anio_fin;
                    document.querySelector('#listStatus').value = objData.data.estatus;

                    if (objData.data.estatus == 1) {
                        var optionSelect = '<option value="1" selected class="notBlock">Activo</option>';
                    } else {
                        var optionSelect = '<option value="2" selected class="notBlock">Inactivo</option>';
                    }
                    var htmlOption = `${optionSelect}
                                    <option value="1">Activo</option>
                                    <option value="2">Inactivo</option>
                                        `;
                    document.querySelector('#listStatus').innerHTML = htmlOption;

                    // Actualizar la información del período mostrada
                    actualizarInfoPeriodo();

                    $('#modalFormPeriodo').modal('show');
                } else {
                    swal('¡Atención!', objData.msg, 'error');
                }
            }
        }
    });
}

function delPeriodo() {
    $('#tablePeriodos tbody').on('click', '.btnDelPeriodo', function () {
        var idPeriodo = $(this).attr('rl');

        swal({
            title: "¿Realmente desea inhabilitar el período escolar?",
            text: "Al inhabilitar el período escolar no se podrá realizar ninguna acción.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, inhabilitar",
            cancelButtonText: "No, cancelar",
            closeOnConfirm: false,
            closeOnCancel: true
        }, function (Confirm) {
            if (Confirm) {
                var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                var ajaxUrl = './models/Periodo/delet_periodo.php';
                var strData = "idPeriodo=" + idPeriodo;
                request.open('POST', ajaxUrl, true);
                request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                request.send(strData);
                request.onreadystatechange = function () {
                    if (request.readyState == 4 && request.status == 200) {
                        var objData = JSON.parse(request.responseText);
                        if (objData.status) {
                            swal("¡Inhabilitado!", objData.msg, "success");
                            tablePeriodos.ajax.reload();
                        } else {
                            swal("Atención", objData.msg, "error");
                        }
                    }
                }
            }
        });
    });
}

function activatePeriodo() {
    $('#tablePeriodos tbody').on('click', '.btnActivatePeriodo', function () {
        var idPeriodo = $(this).attr('rl');

        swal({
            title: "Activar Período Escolar",
            text: "¿Realmente desea activar el período escolar?",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, activar",
            cancelButtonText: "No, cancelar",
            closeOnConfirm: false,
            closeOnCancel: true
        }, function (Confirm) {
            if (Confirm) {
                var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                var ajaxActivatePeriodo = './models/Periodo/active_periodo.php';
                var strData = "idPeriodo=" + idPeriodo;
                request.open('POST', ajaxActivatePeriodo, true);
                request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                request.send(strData);
                request.onreadystatechange = function () {
                    if (request.readyState == 4 && request.status == 200) {
                        var objData = JSON.parse(request.responseText);
                        if (objData.status) {
                            swal("¡Activado!", objData.msg, "success");
                            tablePeriodos.ajax.reload();
                        } else {
                            swal("Atención", objData.msg, "error");
                        }
                    }
                }
            }
        });
    });
}

function openModalPeriodo() {
    document.querySelector('#idPeriodo').value = "";
    document.querySelector('#titleModal').innerHTML = 'Nuevo Período Escolar';
    document.querySelector('.modal-header').classList.replace('updateRegister', 'headerRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-info', 'btn-primary');
    document.querySelector('#btnText').innerHTML = 'Guardar';
    document.querySelector('#formPeriodo').reset();
    $('#modalFormPeriodo').modal('show');
    actualizarInfoPeriodo();
}