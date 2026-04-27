/**
 * FUNCTIONS-REPRESENTANTES.JS
 * ===========================
 * 
 * Gestión completa del módulo de representantes del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de representantes
 * - Formulario de creación y edición con validación
 * - Verificación de cédula con autocompletado desde profesores
 * - Sistema geográfico (estados, ciudades, municipios, parroquias)
 * - Validación de asociaciones con alumnos antes de inhabilitar
 * - Sustitución automática de representante principal por secundario
 * - Activación/Inhabilitación con motivos y validaciones
 * - Prevención de inhabilitar representante único de un alumno
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

// Inicializar DataTable para la tabla de representantes
var tableRepresentantes;
let timeoutCedulaRepresentante = null;

// Esperar a que el DOM esté completamente cargado antes de inicializar
document.addEventListener('DOMContentLoaded', function () {
    // Configurar DataTable con opciones específicas
    tableRepresentantes = $('#tableRepresentantes').DataTable({
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
            "url": "./models/representantes/table_representantes.php",
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
                "data": "cedula",
                "render": function (data, type, row) {
                    const nacionalidadMap = {
                        1: 'E',
                        2: 'P',
                        3: 'V'
                    };
                    const nacionalidad = nacionalidadMap[row.id_nacionalidades] || '';
                    return nacionalidad + '-' + data;
                }
            },
            { "data": "nombre" },
            { "data": "apellido" },
            {
                "data": "sexo",
                "className": "text-center"
            },
            { "data": "direccion" },
            { "data": "telefono" },
            { "data": "correo" },
            { "data": "estatus" },
            { "data": "options" }
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[0, "asc"]],
        "drawCallback": function () {
            setTimeout(function () {
                editRepresentantes();
                delRepresentantes();
                activateRepresentante();
            }, 100);
        }
    });

    // Cargar Estados al iniciar
    loadEstados();

    // Check for form existence to isolate logic
    if (document.querySelector('#formRepresentantes')) {

        // Event listener para verificación de cédula en representante
        $('#formRepresentantes #cedula').on('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) this.value = this.value.slice(0, 10);

            const cedula = $(this).val().trim();
            // const nacionalidad = $('#listNacionalidadRepresentante').val(); // Ya no es necesario para la búsqueda

            if (timeoutCedulaRepresentante) clearTimeout(timeoutCedulaRepresentante);

            if (cedula.length >= 7) {
                timeoutCedulaRepresentante = setTimeout(() => {
                    verificarCedulaRepresentanteGlobal(cedula, 'representante');
                }, 1000);
            }
        });

        $('#formRepresentantes #cedula').on('blur', function () {
            const cedula = $(this).val().trim();
            if (cedula.length > 0 && (cedula.length < 7 || cedula.length > 10)) {
                swal("Atención", "La cédula debe tener entre 7 y 10 dígitos", "warning");
                $(this).addClass('is-invalid');
            } else if (cedula.length > 0) {
                // La validación visual (verde/rojo) se maneja en verificarCedulaGlobal
            }
        });

        // MANEJO DEL ENVÍO DEL FORMULARIO
        var formRepresentantes = document.querySelector('#formRepresentantes');
        formRepresentantes.onsubmit = function (e) {
            e.preventDefault();

            if (validarFormulario()) {
                var formData = new FormData(formRepresentantes);
                enviarFormulario(formData);
            }
        };

        // Event Listeners for Dependent Dropdowns
        if (document.querySelector('#listEstado')) {
            document.querySelector('#listEstado').addEventListener('change', function () {
                var idEstado = this.value;
                loadCiudades(idEstado);
                loadMunicipios(idEstado);
                document.querySelector('#listParroquia').innerHTML = '<option value="">Seleccione una Parroquia</option>';
            });
        }

        if (document.querySelector('#listMunicipio')) {
            document.querySelector('#listMunicipio').addEventListener('change', function () {
                var idMunicipio = this.value;
                loadParroquias(idMunicipio);
            });
        }
    }
});

// FUNCIÓN PARA VERIFICAR CÉDULA GLOBAL REPRESENTANTE
function verificarCedulaRepresentanteGlobal(cedula, modulo) {
    console.log('Verificando cédula global representante:', cedula, modulo);

    var request = new XMLHttpRequest();
    var ajaxUrl = './models/verificar_cedula_global.php';
    var formData = new FormData();
    formData.append('cedula', cedula);
    formData.append('modulo', modulo);
    // Ya no enviamos es_profesor

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                var objData = JSON.parse(request.responseText);
                console.log('Respuesta global:', objData);

                var cedulaField = document.querySelector('#cedula');
                var errorDiv = document.querySelector('#cedula-representante-error');

                if (objData.status) {
                    if (objData.existe) {
                        // ERROR: Ya existe o no permitido - Cerrar modal automáticamente
                        $('#modalFormRepresentantes').modal('hide');
                        document.querySelector('#formRepresentantes').reset();

                        swal({
                            title: "Atención",
                            text: objData.msg,
                            type: objData.type || "error",
                            confirmButtonText: "Aceptar"
                        }, function () {
                            if (objData.action && objData.action == 'clear') {
                                document.querySelector('#cedula').value = '';
                                document.querySelector('#cedula').focus();
                            }
                        });
                    } else {
                        // ÉXITO o AUTOFILL
                        if (cedulaField) {
                            cedulaField.classList.remove('is-invalid');
                            cedulaField.classList.add('is-valid');
                            if (errorDiv) errorDiv.classList.remove('d-block');
                        }

                        if (objData.autofill && objData.data) {
                            // Mostrar mensaje de éxito y autocompletar directamente
                            swal("Éxito", objData.msg, "success");
                            autocompletarFormularioRepresentante(objData.data);
                        }
                    }
                } else {
                    console.error('Error en respuesta:', objData.msg);
                }
            } catch (e) {
                console.error('Error al verificar cédula global:', e);
            }
        }
    }
}

// FUNCIÓN PARA MOSTRAR MODAL DE CONFIRMACIÓN EN REPRESENTANTE
function mostrarModalConfirmacionRepresentante(datos, tipo) {
    const modalHTML = `
        <div class="modal fade" id="modalConfirmacionAutocompletarRepresentante" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header headerRegister">
                        <h5 class="modal-title">Persona Encontrada</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Se encontró una persona registrada con esta cédula:</p>
                        <div class="alert alert-info">
                            <strong>${datos.nombre} ${datos.apellido}</strong><br>
                            ${datos.correo ? 'Email: ' + datos.correo : ''}<br>
                            ${datos.telefono ? 'Teléfono: ' + datos.telefono : ''}
                        </div>
                        <p>¿Desea autocompletar el formulario con esta información?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="btnSiAutocompletar">Sí, Autocompletar</button>
                        <button type="button" class="btn btn-secondary" id="btnNoAutocompletar">No, Ingresar Otra</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#modalConfirmacionAutocompletarRepresentante').remove();
    $('body').append(modalHTML);
    $('#modalConfirmacionAutocompletarRepresentante').modal('show');

    $('#btnSiAutocompletar').on('click', function () {
        $('#modalConfirmacionAutocompletarRepresentante').modal('hide');
        autocompletarFormularioRepresentante(datos);
    });

    $('#btnNoAutocompletar').on('click', function () {
        $('#modalConfirmacionAutocompletarRepresentante').modal('hide');
        $('#cedula').val('').focus();
    });
}

// FUNCIÓN PARA AUTOCOMPLETAR FORMULARIO DE REPRESENTANTE
function autocompletarFormularioRepresentante(datos) {
    // $('#idRepresentantes').val(datos.id || ''); // No asignar ID ya que proviene de la otra tabla
    $('#txtNombre').val(datos.nombre || '');
    $('#txtApellido').val(datos.apellido || '');
    $('#telefono').val(datos.telefono || '');
    $('#email').val(datos.correo || '');

    $('#listSexo').val(datos.sexo || '');

    if (datos.nacionalidad_codigo) {
        $('#listNacionalidadRepresentante').val(datos.nacionalidad_codigo);
    }

    if (datos.id_estado) {
        $('#listEstado').val(datos.id_estado);
        loadCiudades(datos.id_estado, datos.id_ciudad);
        loadMunicipios(datos.id_estado, datos.id_municipio);
        if (datos.id_municipio) {
            setTimeout(function () {
                loadParroquias(datos.id_municipio, datos.id_parroquia);
            }, 500);
        }
    }

    swal('Éxito', 'Formulario autocompletado correctamente', 'success');
}

function loadEstados() {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/ajax-options.php';
    var formData = new FormData();
    formData.append('action', 'getEstados');
    request.open('POST', ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var htmlOptions = '<option value="">Seleccione un Estado</option>';
            data.forEach(function (item) {
                htmlOptions += '<option value="' + item.id_estado + '">' + item.estado + '</option>';
            });
            document.querySelector('#listEstado').innerHTML = htmlOptions;
        }
    }
}

function loadCiudades(idEstado, selectedId = null) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/ajax-options.php';
    var formData = new FormData();
    formData.append('action', 'getCiudades');
    formData.append('id_estado', idEstado);
    request.open('POST', ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var htmlOptions = '<option value="">Seleccione una Ciudad</option>';
            data.forEach(function (item) {
                var selected = (selectedId && selectedId == item.id_ciudad) ? 'selected' : '';
                htmlOptions += '<option value="' + item.id_ciudad + '" ' + selected + '>' + item.ciudad + '</option>';
            });
            document.querySelector('#listCiudad').innerHTML = htmlOptions;
        }
    }
}

function loadMunicipios(idEstado, selectedId = null) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/ajax-options.php';
    var formData = new FormData();
    formData.append('action', 'getMunicipios');
    formData.append('id_estado', idEstado);
    request.open('POST', ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var htmlOptions = '<option value="">Seleccione un Municipio</option>';
            data.forEach(function (item) {
                var selected = (selectedId && selectedId == item.id_municipio) ? 'selected' : '';
                htmlOptions += '<option value="' + item.id_municipio + '" ' + selected + '>' + item.municipio + '</option>';
            });
            document.querySelector('#listMunicipio').innerHTML = htmlOptions;
        }
    }
}

function loadParroquias(idMunicipio, selectedId = null) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/ajax-options.php';
    var formData = new FormData();
    formData.append('action', 'getParroquias');
    formData.append('id_municipio', idMunicipio);
    request.open('POST', ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var data = JSON.parse(request.responseText);
            var htmlOptions = '<option value="">Seleccione una Parroquia</option>';
            data.forEach(function (item) {
                var selected = (selectedId && selectedId == item.id_parroquia) ? 'selected' : '';
                htmlOptions += '<option value="' + item.id_parroquia + '" ' + selected + '>' + item.parroquia + '</option>';
            });
            document.querySelector('#listParroquia').innerHTML = htmlOptions;
        }
    }
}

// FUNCIÓN AUXILIAR PARA VALIDACIÓN
function validarFormulario() {
    var nacionalidad = document.querySelector('#listNacionalidadRepresentante').value;
    var cedula = document.querySelector('#cedula').value;
    var nombre = document.querySelector('#txtNombre').value;
    var apellido = document.querySelector('#txtApellido').value;

    var telefono = document.querySelector('#telefono').value;
    var email = document.querySelector('#email').value;

    // Validar nacionalidad
    if (nacionalidad == '') {
        document.querySelector('#listNacionalidadRepresentante').classList.add('is-invalid');
        swal('Atención', 'Por favor, seleccione una nacionalidad', 'error');
        return false;
    } else {
        document.querySelector('#listNacionalidadRepresentante').classList.remove('is-invalid');
        document.querySelector('#listNacionalidadRepresentante').classList.add('is-valid');
    }

    // Validar cédula
    if (cedula == '') {
        document.querySelector('#cedula').classList.add('is-invalid');
        var errorDiv = document.querySelector('#cedula-representante-error');
        if (errorDiv) {
            errorDiv.textContent = 'La cédula es obligatoria';
            errorDiv.classList.add('d-block');
        }
        swal('Atención', 'La cédula es obligatoria', 'error');
        return false;
    } else if (!/^[0-9]{7,10}$/.test(cedula)) {
        document.querySelector('#cedula').classList.add('is-invalid');
        var errorDiv = document.querySelector('#cedula-representante-error');
        if (errorDiv) {
            errorDiv.textContent = 'La cédula debe tener entre 7 y 10 dígitos';
            errorDiv.classList.add('d-block');
        }
        swal('Atención', 'La cédula debe tener entre 7 y 10 dígitos', 'error');
        return false;
    } else {
        document.querySelector('#cedula').classList.remove('is-invalid');
        document.querySelector('#cedula').classList.add('is-valid');
        var errorDiv = document.querySelector('#cedula-representante-error');
        if (errorDiv) {
            errorDiv.classList.remove('d-block');
        }
    }

    // Validar que todos los campos obligatorios estén llenos
    if (nombre == '' || apellido == '' || telefono == '' || email == '') {
        swal('Atención', 'Todos los campos son necesarios', 'error');
        return false;
    }

    // Validar dirección
    var estado = document.querySelector('#listEstado').value;
    var municipio = document.querySelector('#listMunicipio').value;
    var parroquia = document.querySelector('#listParroquia').value;

    if (estado == '' || municipio == '' || parroquia == '') {
        swal('Atención', 'Debe seleccionar Estado, Municipio y Parroquia', 'error');
        return false;
    }

    return true;
}

// FUNCIÓN AUXILIAR PARA ENVIAR FORMULARIO
function enviarFormulario(formData) {
    var request = new XMLHttpRequest();
    var ajaxUrl = './models/representantes/ajax-representantes.php';

    request.open('POST', ajaxUrl, true);

    request.onreadystatechange = function () {
        if (request.readyState === 4) {
            if (request.status === 200) {
                try {
                    var objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        $('#modalFormRepresentantes').modal('hide');
                        document.querySelector('#formRepresentantes').reset();
                        swal('Éxito', objData.msg, 'success');
                        tableRepresentantes.ajax.reload();
                        if (objData.new_user_name) {
                            var sidebarName = document.querySelector('.app-sidebar__user-designation');
                            if (sidebarName) sidebarName.innerText = objData.new_user_name;
                        }
                    } else {
                        swal('Error', objData.msg, 'error');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    swal('Error', 'Error al procesar la respuesta del servidor: ' + request.responseText, 'error');
                }
            } else {
                swal('Error', 'Error de conexión: ' + request.status, 'error');
            }
        }
    };

    request.send(formData);
}

// FUNCIÓN PARA CONFIGURAR BOTONES DE EDITAR
function editRepresentantes() {
    var btnEditRepresentantes = document.querySelectorAll('.btnEditRepresentantes');
    console.log('Configurando botones editar:', btnEditRepresentantes.length);

    btnEditRepresentantes.forEach(function (btn) {
        btn.onclick = function () {
            console.log('Botón editar clickeado, ID:', this.getAttribute('rl'));
            handleEditClickRepresentantes.call(this);
        };
    });
}

// FUNCIÓN QUE SE EJECUTA AL HACER CLIC EN EDITAR
function handleEditClickRepresentantes() {
    document.querySelector('#titleModal').innerHTML = 'Actualizar Representante';
    document.querySelector('.modal-header').classList.replace('headerRegister', 'updateRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-primary', 'btn-info');
    document.querySelector('#btnText').innerHTML = 'Actualizar';

    var idRepresentantes = this.getAttribute('rl');
    console.log('ID del representante a editar:', idRepresentantes);

    if (!idRepresentantes) {
        swal('Error', 'No se pudo obtener el ID del representante', 'error');
        return;
    }

    var request = new XMLHttpRequest();
    var ajaxUrl = './models/representantes/edit_representantes.php?id=' + idRepresentantes;

    console.log('Solicitando datos desde:', ajaxUrl);

    request.open('GET', ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        console.log('ReadyState:', request.readyState, 'Status:', request.status);

        if (request.readyState === 4) {
            if (request.status === 200) {
                console.log('Respuesta recibida:', request.responseText);

                try {
                    var objData = JSON.parse(request.responseText);
                    console.log('Datos parseados:', objData);

                    if (objData.status) {
                        // Limpiar clases de validación
                        var nacionalidadField = document.querySelector('#listNacionalidadRepresentante');
                        if (nacionalidadField) {
                            nacionalidadField.classList.remove('is-invalid', 'is-valid');
                        }
                        var cedulaField = document.querySelector('#cedula');
                        if (cedulaField) {
                            cedulaField.classList.remove('is-invalid', 'is-valid');
                            var errorDiv = document.querySelector('#cedula-representante-error');
                            if (errorDiv) {
                                errorDiv.classList.remove('d-block');
                            }
                        }

                        // Llenar campos del formulario
                        document.querySelector('#idRepresentantes').value = objData.data.representantes_id;
                        document.querySelector('#listNacionalidadRepresentante').value = objData.data.nacionalidad_codigo || '';
                        document.querySelector('#txtNombre').value = objData.data.nombre;
                        document.querySelector('#txtApellido').value = objData.data.apellido;
                        document.querySelector('#listSexo').value = objData.data.sexo || ''; // Add Sex population

                        document.querySelector('#cedula').value = objData.data.cedula;
                        document.querySelector('#telefono').value = objData.data.telefono;
                        document.querySelector('#email').value = objData.data.correo;

                        // Cargar datos de dirección
                        if (objData.data.id_estado) {
                            document.querySelector('#listEstado').value = objData.data.id_estado;
                            loadCiudades(objData.data.id_estado, objData.data.id_ciudad);
                            loadMunicipios(objData.data.id_estado, objData.data.id_municipio);
                            if (objData.data.id_municipio) {
                                setTimeout(function () {
                                    loadParroquias(objData.data.id_municipio, objData.data.id_parroquia);
                                }, 500);
                            }
                        }

                        $('#modalFormRepresentantes').modal('show');
                    } else {
                        console.error('Error del servidor:', objData.msg);
                        swal('Error', objData.msg, 'error');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Respuesta cruda:', request.responseText);
                    swal('Error', 'Error al procesar los datos del representante. Respuesta: ' + request.responseText, 'error');
                }
            } else {
                console.error('Error HTTP:', request.status);
                swal('Error', 'Error de conexión: ' + request.status, 'error');
            }
        }
    }
}

// FUNCIÓN PARA CONFIGURAR BOTONES DE INHABILITAR
function delRepresentantes() {
    var btnDelRepresentantes = document.querySelectorAll('.btnDelRepresentantes');
    console.log('Configurando botones inabilitar:', btnDelRepresentantes.length);

    btnDelRepresentantes.forEach(function (btn) {
        btn.onclick = function () {
            console.log('Botón inabilitar clickeado, ID:', this.getAttribute('rl'));
            handleDeleteClickRepresentantes.call(this);
        };
    });
}

// FUNCIÓN QUE SE EJECUTA AL HACER CLIC EN INHABILITAR
function handleDeleteClickRepresentantes() {
    var idRepresentantes = this.getAttribute('rl');

    if (!idRepresentantes) {
        console.error('No se pudo obtener el ID del representante');
        swal('Error', 'No se pudo obtener el ID del representante', 'error');
        return;
    }

    // PRIMERO: Verificar asociaciones con estudiantes
    var request = new XMLHttpRequest();
    var ajaxUrl = './models/representantes/check_representante_associations.php';
    var formData = new FormData();
    formData.append('idRepresentante', idRepresentantes);

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState === 4 && request.status === 200) {
            try {
                var objData = JSON.parse(request.responseText);

                if (objData.status) {
                    // CASO 1: Bloqueo Total (Principal sin sustituto)
                    if (!objData.can_inactivate) {
                        var studentList = '<ul style="text-align: left;">';
                        objData.students.forEach(function (student) {
                            if (student.total_representantes_restantes == 0) {
                                studentList += '<li><strong>' + student.nombre + ' ' + student.apellido + '</strong></li>';
                            }
                        });
                        studentList += '</ul>';

                        swal({
                            title: "No se puede inhabilitar",
                            text: "<p>Este representante es el ÚNICO para los siguientes alumnos:</p>" + studentList + "<p style='color:red;'><strong>No puede ser inhabilitado a menos que se coloque un representante secundario.</strong></p>",
                            html: true,
                            type: "error",
                            confirmButtonText: "Entendido"
                        });
                        return; // Detener flujo
                    }

                    // CASO 2: Aviso de Sustitución (Principal con sustituto)
                    if (objData.has_substitution_needed) {
                        var substitutionMsg = '<ul style="text-align: left;">';
                        objData.students.forEach(function (student) {
                            if (student.es_principal == 1 && student.nombre_sucesor) {
                                substitutionMsg += '<li>Para el alumno <strong>' + student.nombre + '</strong>, el representante secundario <strong>' + student.nombre_sucesor + '</strong> pasará a ser Principal.</li>';
                            }
                        });
                        substitutionMsg += '</ul>';

                        swal({
                            title: "Aviso de Sustitución",
                            text: "<p>Se detectaron sustitutos para algunos alumnos:</p>" + substitutionMsg + "<p>Al continuar, se realizará el cambio de representante principal.</p>",
                            html: true,
                            type: "warning",
                            showCancelButton: true,
                            cancelButtonText: "No, cancelar",
                            confirmButtonText: "Entendido, continuar",
                            closeOnConfirm: false
                        }, function (isConfirm) {
                            if (isConfirm) {
                                // Proceder a pedir el motivo
                                pedirMotivoInhabilitacion(idRepresentantes);
                            }
                        });
                        return; // Detener flujo actual, esperar al callback del swal
                    }

                    // CASO 3: Motivo Directo (No asociado o solo secundario)
                    pedirMotivoInhabilitacion(idRepresentantes);

                } else {
                    swal("Error", "Error al verificar asociaciones: " + objData.msg, "error");
                }
            } catch (e) {
                console.error('Error al verificar asociaciones:', e);
                console.error('Respuesta del servidor:', request.responseText);
                swal("Error", "Error técnico al procesar la verificación. Respuesta: " + request.responseText, "error");
            }
        }
    };
}

// Función auxiliar para centralizar la solicitud del motivo
function pedirMotivoInhabilitacion(idRepresentantes) {
    swal({
        title: "¿Inhabilitar Representante?",
        text: "Escriba el motivo para inhabilitar al representante:",
        type: "input",
        showCancelButton: true,
        cancelButtonText: "No, cancelar",
        confirmButtonText: "Sí, inhabilitar",
        closeOnCancel: true,
        closeOnConfirm: false,
        inputPlaceholder: "Escriba el motivo..."
    }, function (inputValue) {
        if (inputValue === false) return false;
        if (inputValue === "") {
            swal.showInputError("Debe escribir un motivo");
            return false;
        }
        eliminarRepresentante(idRepresentantes, inputValue);
    });
}
// FUNCIÓN AUXILIAR PARA ELIMINACIÓN
function eliminarRepresentante(idRepresentantes, observacion) {
    var request = new XMLHttpRequest();
    var ajaxUrl = './models/representantes/del_representantes.php';
    var strData = 'idRepresentantes=' + encodeURIComponent(idRepresentantes) + '&observacion=' + encodeURIComponent(observacion);

    request.open('POST', ajaxUrl, true);
    request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    request.onreadystatechange = function () {
        if (request.readyState === 4) {
            if (request.status === 200) {
                try {
                    var objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        swal({
                            title: "¡Inhabilitado!",
                            text: objData.msg,
                            html: true,
                            type: "success",
                            confirmButtonText: "Aceptar"
                        });
                        tableRepresentantes.ajax.reload();
                    } else {
                        swal({
                            title: "Atención",
                            text: objData.msg,
                            html: true,
                            type: "error",
                            confirmButtonText: "Aceptar"
                        });
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Respuesta del servidor:', request.responseText);
                    swal("Error", "Error al procesar la respuesta del servidor: " + request.responseText, "error");
                }
            } else {
                swal("Error", "Error de conexión: " + request.status, "error");
            }
        }
    };

    request.send(strData);
}

// FUNCIÓN PARA ABRIR MODAL DE NUEVO REPRESENTANTE
function openModalRepresentantes() {
    document.querySelector('#idRepresentantes').value = "";
    document.querySelector('#titleModal').innerHTML = 'Nuevo Representante';

    var modalHeader = document.querySelector('.modal-header');
    if (modalHeader.classList.contains('updateRegister')) {
        modalHeader.classList.replace('updateRegister', 'headerRegister');
    }

    document.querySelector('#btnActionForm').classList.replace('btn-info', 'btn-primary');
    document.querySelector('#btnText').innerHTML = 'Guardar';
    document.querySelector('#formRepresentantes').reset();

    // Limpiar clases de validación
    var nacionalidadField = document.querySelector('#listNacionalidadRepresentante');
    if (nacionalidadField) {
        nacionalidadField.classList.remove('is-invalid', 'is-valid');
    }
    var cedulaField = document.querySelector('#cedula');
    if (cedulaField) {
        cedulaField.classList.remove('is-invalid', 'is-valid');
        var errorDiv = document.querySelector('#cedula-representante-error');
        if (errorDiv) {
            errorDiv.classList.remove('d-block');
        }
    }

    $('#modalFormRepresentantes').modal('show');
}
function activateRepresentante() {
    var btnActivate = document.querySelectorAll('.btnActivateRepresentante');
    btnActivate.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idRepresentante = this.getAttribute('rl');
            swal({
                title: "¿Activar Representante?",
                text: "Por favor, escriba el motivo de la activación:",
                type: "input",
                showCancelButton: true,
                confirmButtonText: "Sí, activar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true,
                inputPlaceholder: "Escriba el motivo..."
            }, function (inputValue) {
                if (inputValue === false) return false;
                if (inputValue === "") {
                    swal.showInputError("Debe escribir un motivo");
                    return false;
                }
                var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                var ajaxUrl = './models/representantes/activate_representantes.php';
                var strData = "idRepresentantes=" + idRepresentante + "&observacion=" + encodeURIComponent(inputValue);
                request.open("POST", ajaxUrl, true);
                request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                request.send(strData);
                request.onreadystatechange = function () {
                    if (request.readyState == 4 && request.status == 200) {
                        try {
                            var objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                swal("¡Activado!", objData.msg, "success");
                                tableRepresentantes.ajax.reload();
                            } else {
                                swal("Atención!", objData.msg, "error");
                            }
                        } catch (e) {
                            console.error("Error parsing JSON: ", e);
                            swal("Error", "Error al procesar la respuesta", "error");
                        }
                    }
                }
            });
        });
    });
}