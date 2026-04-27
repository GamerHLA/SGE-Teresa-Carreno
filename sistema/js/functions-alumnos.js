/**
 * FUNCTIONS-ALUMNOS.JS
 * ====================
 * 
 * Gestión completa del módulo de alumnos del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de alumnos (búsqueda, paginación, ordenamiento)
 * - Formulario de creación y edición de alumnos con validación en tiempo real
 * - Gestión de representantes (principal y secundario)
 * - Sistema geográfico (estados, ciudades, municipios, parroquias)
 * - Gestión de parentescos con opciones de agregar/eliminar
 * - Validación de cédulas (alumno y representantes) con verificación global
 * - Cálculo automático de edad basado en fecha de nacimiento
 * - Manejo de cédula escolar para alumnos sin cédula
 * - Inscripción de alumnos y generación de PDFs
 * - Información adicional médica (enfermedades, discapacidades, vacunas, doctor)
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

// Parche para permitir foco en SweetAlert dentro de Modales de Bootstrap
if ($.fn.modal) {
    $.fn.modal.Constructor.prototype._enforceFocus = function () { };
}

var tableAlumnos;

// Objeto para mapear campos con sus mensajes de error
var fieldErrorMap = {
    'txtNombre': 'nombre',
    'txtApellido': 'apellido',
    'cedula': 'cedula',
    'fechaNac': 'fechaNac',
    'listStatus': 'estado'
};

document.addEventListener('DOMContentLoaded', function () {
    tableAlumnos = $('#tableAlumnos').DataTable({
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
            "url": "./models/alumnos/table_alumnos.php",
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
                    // Mapeo de ID a código de nacionalidad
                    const nacionalidadMap = {
                        1: 'E',  // Extranjero
                        2: 'P',  // Pasaporte
                        3: 'V'   // Venezolano
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
            {
                "data": "edad",
                "className": "text-center"
            },
            { "data": "fecha_nac" },
            { "data": "representante" },
            { "data": "direccion" },
            { "data": "estatus" },
            { "data": "options" }
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[0, "asc"]],
        "drawCallback": function () {
            editAlumno();
            delAlumno();
            inscribirAlumno();
            pdfInscripcion();
            activateAlumno();
        }
    });

    // Event Delegation for "Información Adicional" button
    $(document).on('click', '.btnEditInfoAlumno', function (e) {
        e.preventDefault();
        var idAlumno = $(this).attr('rl');

        if (idAlumno && idAlumno > 0) {
            if (window.openModalInfoAlumno) {
                window.openModalInfoAlumno(idAlumno);
            } else {
                // Fallback attempt to find the function if window. doesn't verify
                if (typeof openModalInfoAlumno === 'function') {
                    openModalInfoAlumno(idAlumno);
                } else {
                    swal('Atención', 'La función no está cargada. Por favor presione CTRL+F5.', 'warning');
                }
            }
        }
    });

    // Inicializar validaciones en tiempo real
    initRealTimeValidation();

    // Inicializar búsqueda de representante por cédula
    initBuscarRepresentante();

    // Inicializar lógica de "Posee Cedula"
    initPoseeCedulaLogic();

    // Inicializar generación de cédula escolar
    initCedulaEscolar();

    // Inicializar ayuda nº hijos
    initAyudaNHijos();

    // Inicializar cálculo de edad
    initCalculoEdad();

    // Cargar Estados al iniciar
    loadEstados();
    loadParentescos(); // Cargar Parentescos
    initParentescoManagement(); // NUEVO: Gestión de botones y limpieza

    // Event Listeners for Dependent Dropdowns
    document.querySelector('#listEstado').addEventListener('change', function () {
        var idEstado = this.value;
        loadCiudades(idEstado);
        loadMunicipios(idEstado);
        document.querySelector('#listParroquia').innerHTML = '<option value="">Seleccione una Parroquia</option>';
    });

    document.querySelector('#listMunicipio').addEventListener('change', function () {
        var idMunicipio = this.value;
        loadParroquias(idMunicipio);
    });

    // CREAR ALUMNOS - FORMULARIO MEJORADO
    var formAlumnos = document.querySelector('#formAlumno');
    formAlumnos.onsubmit = function (e) {
        e.preventDefault();

        // Limpiar errores previos
        clearAllErrors();

        // Validar campos antes de enviar
        if (!validateForm()) {
            return false;
        }

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/alumnos/ajax-alumnos.php';
        var formAlumno = new FormData(formAlumnos);

        // Mostrar loading
        showLoading(true);

        request.open('POST', ajaxUrl, true);
        request.send(formAlumno);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                showLoading(false);
                var objData = JSON.parse(request.responseText);

                if (objData.status) {
                    $('#modalFormAlumno').modal('hide');
                    formAlumnos.reset();
                    clearAllErrors();
                    swal('Éxito', objData.msg, 'success');
                    tableAlumnos.ajax.reload();
                } else {
                    // Mostrar errores específicos del servidor
                    if (objData.errors && objData.errors.length > 0) {
                        displayServerErrors(objData.errors);
                    } else {
                        swal('Atención', objData.msg, 'error');
                    }
                }
            }
        }
    }

    // Event listener para verificación de cédula en alumno
    let timeoutCedulaAlumno = null;
    $('#cedulaAlumno').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) this.value = this.value.slice(0, 10);

        const cedula = $(this).val().trim();

        if (timeoutCedulaAlumno) clearTimeout(timeoutCedulaAlumno);

        if (cedula.length >= 7) {
            timeoutCedulaAlumno = setTimeout(() => {
                verificarCedulaAlumnoGlobal(cedula, 'alumno');
            }, 1000);
        }
    });

    $('#cedulaAlumno').on('blur', function () {
        const cedula = $(this).val().trim();
        if (cedula.length > 0 && (cedula.length < 7 || cedula.length > 10)) {
            swal("Atención", "La cédula debe tener entre 7 y 10 dígitos", "warning");
            $(this).addClass('is-invalid');
        }
    });
});

// FUNCIÓN PARA VERIFICAR CÉDULA GLOBAL ALUMNO
function verificarCedulaAlumnoGlobal(cedula, modulo) {
    console.log('Verificando cédula global alumno:', cedula, modulo);

    var request = new XMLHttpRequest();
    var ajaxUrl = './models/verificar_cedula_global.php';
    var formData = new FormData();
    formData.append('cedula', cedula);
    formData.append('modulo', modulo);

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                var objData = JSON.parse(request.responseText);
                console.log('Respuesta global:', objData);

                var cedulaField = document.querySelector('#cedulaAlumno');

                if (objData.status) {
                    if (objData.existe) {
                        // ERROR: Ya existe - Solo limpiar el campo de cédula
                        if (cedulaField) {
                            cedulaField.value = '';
                            cedulaField.classList.add('is-invalid');
                            cedulaField.focus();
                        }

                        swal({
                            title: "Atención",
                            text: objData.msg,
                            type: "error",
                            confirmButtonText: "Aceptar"
                        });
                    } else {
                        // ÉXITO: Cédula disponible
                        if (cedulaField) {
                            cedulaField.classList.remove('is-invalid');
                            cedulaField.classList.add('is-valid');
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

// FUNCIONES PARA SISTEMA GEOGRÁFICO
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

function loadParentescos(selectedId = null, selectedId2 = null) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/ajax-options.php';
    var formData = new FormData();
    formData.append('action', 'getParentescos');
    request.open('POST', ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                var data = JSON.parse(request.responseText);
                var htmlOptions = '<option value="">Seleccione una opción</option>';
                data.forEach(function (item) {
                    htmlOptions += '<option value="' + item.id_parentesco + '">' + item.parentesco + '</option>';
                });

                var listParentesco = document.querySelector('#listParentesco');
                var listParentesco2 = document.querySelector('#listParentesco2');

                // Guardar valor actual antes de recargar
                let cur1 = selectedId || (listParentesco ? listParentesco.value : '');
                let cur2 = selectedId2 || (listParentesco2 ? listParentesco2.value : '');

                if (listParentesco) {
                    listParentesco.innerHTML = htmlOptions;
                    listParentesco.value = cur1;
                }
                if (listParentesco2) {
                    listParentesco2.innerHTML = htmlOptions;
                    listParentesco2.value = cur2;
                }
            } catch (e) {
                console.error("Error cargando parentescos", e);
            }
        }
    }
}

// NUEVA FUNCIÓN: Gestión de botones de parentesco y limpieza de campos
function initParentescoManagement() {
    // Configurar botones para parentesco 1
    setupParentescoButtons('listParentesco', 'btnAddParentesco', 'btnDelParentesco');
    // Configurar botones para parentesco 2
    setupParentescoButtons('listParentesco2', 'btnAddParentesco2', 'btnDelParentesco2');

    // Lógica de limpieza al cambiar parentesco (Solo para Info Adicional, removido de aquí por petición de usuario)
    // Se mantiene la estructura pero sin el event listener de limpieza automática que molestaba al usuario
}

function setupParentescoButtons(selectId, addBtnId, delBtnId) {
    const btnAdd = document.getElementById(addBtnId);
    const btnDel = document.getElementById(delBtnId);
    const sel = document.getElementById(selectId);

    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            swal({
                title: "Nuevo Parentesco",
                text: "Ingrese el nombre del nuevo parentesco:",
                type: "input",
                showCancelButton: true,
                closeOnConfirm: false,
                inputPlaceholder: "Escriba aquí (Ej: Tío, Abuelo...)"
            }, function (inputValue) {
                if (inputValue === false) return false;
                if (inputValue === "") { swal.showInputError("Debe escribir un nombre"); return false; }

                let fd = new FormData();
                fd.append('action', 'addParentesco');
                fd.append('nombre', inputValue);

                fetch('./models/options/ajax-options.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status) {
                            swal("¡Guardado!", res.msg, "success");
                            loadParentescos(res.id); // Recargar y seleccionar el nuevo
                            if (sel) sel.value = res.id;
                        } else {
                            swal("Error", res.msg, "error");
                        }
                    }).catch(e => swal("Error", "Error de conexión", "error"));
            });
        });
    }

    if (btnDel) {
        btnDel.addEventListener('click', function () {
            if (!sel || !sel.value) return swal("Atención", "Seleccione un parentesco para eliminar", "warning");

            let name = sel.options[sel.selectedIndex].text;
            swal({
                title: "¿Eliminar Parentesco?",
                text: "¿Está seguro que desea eliminar '" + name + "'? Esta opción ya no aparecerá en la lista.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Sí, eliminar",
                closeOnConfirm: false
            }, function () {
                let fd = new FormData();
                fd.append('action', 'deleteParentesco');
                fd.append('id', sel.value);

                fetch('./models/options/ajax-options.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status) {
                            swal("Eliminado", res.msg, "success");
                            loadParentescos(); // Recargar opciones
                        } else {
                            swal("Error", res.msg, "error");
                        }
                    }).catch(e => swal("Error", "Error de conexión", "error"));
            });
        });
    }
}

// FUNCIÓN PARA VALIDACIÓN EN TIEMPO REAL
function initRealTimeValidation() {
    const fields = ['txtNombre', 'txtApellido', 'edad', 'cedulaAlumno', 'fechaNac', 'listStatus', 'cedulaRepresentante', 'poseeCedula', 'listParentesco', 'cedulaRepresentante2', 'listParentesco2'];

    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', function () {
                validateField(this.id, this.value);
            });

            // Para campos numéricos y específicos
            if (fieldId === 'cedula' || fieldId === 'cedulaRepresentante' || fieldId === 'cedulaAlumno' || fieldId === 'cedulaRepresentante2') {
                field.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }

            if (fieldId === 'edad') {
                field.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value > 15) this.value = 15;
                    if (this.value < 4) this.value = 4;
                });
            }
        }
    });
}

// ... (existing code for initAgeDateSync, calculateAgeFromBirthDate, validateAgeDateConsistency) ...

// VALIDACIÓN DE CAMPO INDIVIDUAL
function validateField(fieldId, value) {
    const errorElement = document.getElementById(`error-${fieldId}`);

    // Si el campo no existe, no validar
    const field = document.getElementById(fieldId);
    if (!field) return true;

    // Limpiar error previo
    if (errorElement) {
        errorElement.remove();
    }

    let isValid = true;
    let errorMessage = '';

    switch (fieldId) {
        case 'txtNombre':
        case 'txtApellido':
            if (!value.trim()) {
                errorMessage = 'Este campo es obligatorio';
                isValid = false;
            }
            break;

        case 'edad':
            if (!value || value < 4 || value > 15) {
                errorMessage = 'La edad debe ser entre 4 y 15 años';
                isValid = false;
            }
            break;

        case 'cedula':
        case 'cedulaAlumno':
            // Ignorar validación si el campo tiene el atributo data-validation-ignore
            if (field.hasAttribute('data-validation-ignore')) {
                isValid = true;
                break;
            }
            // Validación opcional: solo validar si tiene valor
            if (value.trim()) {
                if (!/^[0-9]{7,10}$/.test(value)) {
                    errorMessage = 'La cédula debe tener entre 7 y 10 dígitos';
                    isValid = false;
                }
            }
            break;

        case 'cedulaRepresentante':
            if (!value.trim()) {
                errorMessage = 'La cédula del representante es obligatoria';
                isValid = false;
            } else if (!/^[0-9]{7,10}$/.test(value)) {
                errorMessage = 'La cédula debe tener entre 7 y 10 dígitos';
                isValid = false;
            }
            break;

        case 'cedulaRepresentante2':
            // Opcional, pero si se escribe algo debe ser válido
            if (value.trim()) {
                if (!/^[0-9]{7,10}$/.test(value)) {
                    errorMessage = 'La cédula debe tener entre 7 y 10 dígitos';
                    isValid = false;
                }
                const cedulaRep1 = document.getElementById('cedulaRepresentante').value;
                if (value.trim() === cedulaRep1) {
                    errorMessage = 'El representante 2 no puede ser el mismo que el representante principal';
                    isValid = false;
                }
            }
            break;

        case 'poseeCedula':
            if (!value || (value !== 'SI' && value !== 'NO')) {
                errorMessage = 'Por favor, seleccione si el alumno posee cédula';
                isValid = false;
            }
            break;

        case 'listNacionalidadAlumno':
            // Validación opcional: solo validar si tiene valor y no está deshabilitado
            if (!field.hasAttribute('data-validation-ignore') && value.trim()) {
                if (value !== 'V' && value !== 'E' && value !== 'P') {
                    errorMessage = 'Seleccione una nacionalidad válida';
                    isValid = false;
                }
            }
            break;

        case 'fechaNac':
            if (!value.trim()) {
                errorMessage = 'La fecha de nacimiento es obligatoria';
                isValid = false;
            } else if (!isValidDate(value)) {
                errorMessage = 'La fecha no es válida (AAAA-MM-DD)';
                isValid = false;
            }
            break;

        case 'listStatus':
            if (!value || (value !== '1' && value !== '2')) {
                errorMessage = 'Seleccione un estado válido';
                isValid = false;
            }
            break;

        case 'listParentesco':
            if (!value || value.trim() === '') {
                errorMessage = 'Por favor, seleccione un parentesco';
                isValid = false;
            }
            break;

        case 'listParentesco2':
            // Validar solo si hay cédula 2
            const cedulaRep2Field = document.getElementById('cedulaRepresentante2');
            const cedulaRep2Val = cedulaRep2Field ? cedulaRep2Field.value : '';

            if (cedulaRep2Val && cedulaRep2Val.trim().length > 0) {
                if (!value || value.trim() === '') {
                    errorMessage = 'Seleccione un parentesco para el representante 2';
                    isValid = false;
                }
            }
            break;

        case 'listEstado':
        case 'listCiudad':
        case 'listMunicipio':
        case 'listParroquia':
            if (!value || value.trim() === '') {
                errorMessage = 'Este campo es obligatorio';
                isValid = false;
            }
            break;
    }

    // Aplicar estilos y mostrar error
    if (!isValid) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        showFieldError(fieldId, errorMessage);
    } else {
        // Solo marcar como válido si no está vacío (para opcionales) o si es obligatorio
        if (value.trim() !== '' || ['cedulaRepresentante2', 'listParentesco2'].indexOf(fieldId) === -1) {
            // Solo agregar success si es realmente valido y tiene valor
            if (value.trim() !== '') {
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
            }
        } else {
            field.classList.remove('is-valid'); // Si es opcional y vacío, quitar valid
        }
        field.classList.remove('is-invalid');
    }

    return isValid;
}

// VALIDACIÓN COMPLETA DEL FORMULARIO
function validateForm() {
    const fields = ['txtNombre', 'txtApellido', 'edad', 'cedulaAlumno', 'fechaNac', 'listStatus', 'cedulaRepresentante', 'poseeCedula', 'listParentesco', 'listEstado', 'listCiudad', 'listMunicipio', 'listParroquia', 'cedulaRepresentante2', 'listParentesco2'];
    let isValid = true;
    let firstErrorField = null;

    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        // Si el campo existe
        if (field) {
            // Ignorar validación explícitamente ignorada
            if (field.hasAttribute('data-validation-ignore')) {
                // skip
            } else {
                // Validar
                const fieldValid = validateField(fieldId, field.value);

                // Lógica de validación cruzada específica
                if (!fieldValid) {
                    // Check if failures are allowed (e.g. empty optional fields being flagged by generic logic)
                    if (fieldId === 'cedulaRepresentante2' && field.value.trim() === '') {
                        // Empty optional field is OK
                    } else if (fieldId === 'listParentesco2') {
                        // Parentesco 2 only valid if Cedula 2 has value
                        const cedula2 = document.getElementById('cedulaRepresentante2').value;
                        if (!cedula2 || cedula2.trim() === '') {
                            // Ignorar error de parentesco si no hay cédula
                        } else {
                            isValid = false;
                            if (!firstErrorField) firstErrorField = field;
                        }
                    } else {
                        isValid = false;
                        if (!firstErrorField) firstErrorField = field;
                    }
                }
            }
        }
    });

    // Validar nacionalidad solo si tiene valor (validación opcional)
    const nacionalidadAlumnoField = document.getElementById('listNacionalidadAlumno');
    if (nacionalidadAlumnoField && !nacionalidadAlumnoField.hasAttribute('data-validation-ignore')) {
        if (nacionalidadAlumnoField.value && !validateField('listNacionalidadAlumno', nacionalidadAlumnoField.value)) {
            isValid = false;
            if (!firstErrorField) firstErrorField = nacionalidadAlumnoField;
        }
    }

    // Validar coherencia entre edad y fecha de nacimiento
    // (Asumiendo que validateAgeDateConsistency existe y muestra sus propios errores)
    // if (isValid && !validateAgeDateConsistency()) {
    //    isValid = false;
    // }

    if (!isValid && firstErrorField) {
        firstErrorField.focus();
        swal("Atención", "Por favor verifique los campos marcados en rojo", "error");
    }

    return isValid;
}

// MOSTRAR ERROR EN CAMPO ESPECÍFICO
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    // Remover error previo
    const existingError = document.getElementById(`error-${fieldId}`);
    if (existingError) {
        existingError.remove();
    }

    // Crear elemento de error
    const errorDiv = document.createElement('div');
    errorDiv.id = `error-${fieldId}`;
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;

    // Insertar después del campo
    field.parentNode.appendChild(errorDiv);
}

// LIMPIAR TODOS LOS ERRORES
function clearAllErrors() {
    // Remover mensajes de error
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    // Remover clases de validación
    document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

// MOSTRAR ERRORES DEL SERVIDOR
function displayServerErrors(errors) {
    errors.forEach(error => {
        // Buscar el campo correspondiente al error
        for (const [fieldId, errorType] of Object.entries(fieldErrorMap)) {
            if (error.toLowerCase().includes(errorType)) {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.add('is-invalid');
                    showFieldError(fieldId, error);
                }
                break;
            }
        }
    });

    // Si hay errores generales, mostrarlos con swal
    const generalErrors = errors.filter(error => {
        return !Object.values(fieldErrorMap).some(errorType =>
            error.toLowerCase().includes(errorType)
        );
    });

    if (generalErrors.length > 0) {
        swal('Atención', generalErrors.join('\n'), 'error');
    }
}

// VALIDAR FECHA
function isValidDate(dateString) {
    const regex = /^\d{4}-\d{2}-\d{2}$/;
    if (!regex.test(dateString)) return false;

    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date) && dateString === date.toISOString().split('T')[0];
}

// MOSTRAR/OCULTAR LOADING
function showLoading(show) {
    const btn = document.querySelector('#btnActionForm');
    const btnText = document.querySelector('#btnText');

    if (show) {
        btn.disabled = true;
        btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    } else {
        btn.disabled = false;
        btnText.innerHTML = btn.classList.contains('btn-primary') ? 'Guardar' : 'Actualizar';
    }
}

// FUNCIONES EXISTENTES (mejoradas)
function editAlumno() {
    var btnEditAlumno = document.querySelectorAll('.btnEditAlumno');
    btnEditAlumno.forEach(function (btnEditAlumno) {
        btnEditAlumno.removeEventListener('click', handleEditClick);
        btnEditAlumno.addEventListener('click', handleEditClick);
    });
}

function handleEditClick() {
    document.querySelector('#titleModal').innerHTML = 'Actualizar Alumno';
    document.querySelector('.modal-header').classList.replace('headerRegister', 'updateRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-primary', 'btn-info');
    document.querySelector('#btnText').innerHTML = 'Actualizar';

    // Limpiar errores al editar
    clearAllErrors();

    var idAlumno = this.getAttribute('rl');
    console.log('Editando alumno ID:', idAlumno); // Debug

    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/alumnos/edit_alumnos.php?id=' + idAlumno;

    request.open('GET', ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            console.log('Respuesta recibida:', request.responseText); // Debug
            try {
                var objData = JSON.parse(request.responseText);
                console.log('Datos parseados:', objData); // Debug

                if (objData.status) {
                    // Llenar campos básicos
                    document.querySelector('#idAlumno').value = objData.data.alumno_id || '';
                    document.querySelector('#txtNombre').value = objData.data.nombre || '';
                    document.querySelector('#txtApellido').value = objData.data.apellido || '';
                    if (document.querySelector('#edad')) {
                        document.querySelector('#edad').value = objData.data.edad || '';
                    }
                    document.querySelector('#listSexo').value = objData.data.sexo || '';
                    // La cédula se maneja más abajo con la lógica de separación
                    document.querySelector('#fechaNac').value = objData.data.fecha_nac || '';
                    document.querySelector('#listStatus').value = objData.data.estatus || '';

                    // Cargar datos de dirección
                    if (objData.data.id_estado) {
                        document.querySelector('#listEstado').value = objData.data.id_estado;
                        // Cargar ciudades y municipios con delay para asegurar la carga
                        setTimeout(function () {
                            loadCiudades(objData.data.id_estado, objData.data.id_ciudad);
                            loadMunicipios(objData.data.id_estado, objData.data.id_municipio);

                            // Cargar parroquias después de cargar municipios
                            setTimeout(function () {
                                if (objData.data.id_municipio) {
                                    loadParroquias(objData.data.id_municipio, objData.data.id_parroquia);
                                }
                            }, 500);
                        }, 300);
                    }

                    // Cargar datos del representante
                    if (objData.data.rep_cedula) {
                        document.querySelector('#cedulaRepresentante').value = objData.data.rep_cedula;
                        // Buscar automáticamente el representante
                        setTimeout(function () {
                            buscarRepresentante(objData.data.rep_cedula);
                        }, 1000);
                    }

                    // Cargar parentesco
                    if (objData.data.parentesco_id) {
                        // Reload parentescos setting the selected one
                        loadParentescos(objData.data.parentesco_id, (objData.data.representante2 ? objData.data.representante2.parentesco_id : null));
                    }

                    // Cargar Nacionalidad
                    if (objData.data.id_nacionalidades) {
                        const nacionalidadMap = {
                            1: 'E',  // Extranjero
                            2: 'P',  // Pasaporte
                            3: 'V'   // Venezolano
                        };
                        const nacionalidadCodigo = nacionalidadMap[objData.data.id_nacionalidades];
                        if (nacionalidadCodigo) {
                            const listNacionalidadAlumno = document.querySelector('#listNacionalidadAlumno');
                            if (listNacionalidadAlumno) {
                                listNacionalidadAlumno.value = nacionalidadCodigo;
                            }
                        }
                    }

                    // Cargar Representante 2 (Opcional)
                    if (objData.data.representante2 && objData.data.representante2.cedula) {
                        document.querySelector('#cedulaRepresentante2').value = objData.data.representante2.cedula;
                        // Cargar parentesco R2
                        if (objData.data.representante2.parentesco) {
                            const listParentesco2 = document.querySelector('#listParentesco2');
                            if (listParentesco2) {
                                listParentesco2.value = objData.data.representante2.parentesco;
                                if (objData.data.representante2.parentesco === 'Otros' && objData.data.representante2.parentesco_otros) {
                                    const txtParentescoOtros2 = document.querySelector('#txtParentescoOtros2');
                                    const parentescoOtrosGroup2 = document.querySelector('#parentescoOtrosGroup2');
                                    if (txtParentescoOtros2 && parentescoOtrosGroup2) {
                                        parentescoOtrosGroup2.style.display = 'block';
                                        txtParentescoOtros2.value = objData.data.representante2.parentesco_otros;
                                        txtParentescoOtros2.setAttribute('required', 'required');
                                    }
                                }
                            }
                        }
                        // Buscar nombre R2
                        setTimeout(function () {
                            buscarRepresentante(objData.data.representante2.cedula, 'cedulaRepresentante2', 'txtNombreRepresentante2', 'cedula-representante2-error');
                        }, 1200);
                    } else {
                        // Limpiar campos R2 si no tiene
                        document.querySelector('#cedulaRepresentante2').value = '';
                        document.querySelector('#txtNombreRepresentante2').value = '';
                        document.querySelector('#listParentesco2').value = '';
                        const parentescoOtrosGroup2 = document.querySelector('#parentescoOtrosGroup2');
                        if (parentescoOtrosGroup2) parentescoOtrosGroup2.style.display = 'none';
                    }

                    // Manejar lógica de "Posee Cédula" y separar cédula escolar de cédula regular
                    const poseeCedulaField = document.querySelector('#poseeCedula');
                    const cedulaAlumnoField = document.querySelector('#cedulaAlumno');
                    const cedulaEscolarField = document.querySelector('#cedulaEscolar');

                    if (poseeCedulaField && cedulaAlumnoField && cedulaEscolarField) {
                        const cedulaValue = objData.data.cedula || '';

                        // Detectar si es cédula escolar (alfanumérica o no solo dígitos)
                        // Cédula regular: solo dígitos de 7-10 caracteres
                        // Cédula escolar: cualquier otro formato
                        const esCedulaRegular = /^[0-9]{7,10}$/.test(cedulaValue);

                        if (cedulaValue && cedulaValue.trim() !== '') {
                            if (esCedulaRegular) {
                                // Es cédula regular (numérica)
                                poseeCedulaField.value = 'SI';
                                cedulaAlumnoField.value = cedulaValue;
                                cedulaEscolarField.value = '';
                            } else {
                                // Es cédula escolar (alfanumérica o formato especial)
                                poseeCedulaField.value = 'NO';
                                cedulaAlumnoField.value = '';
                                cedulaEscolarField.value = cedulaValue;
                            }
                        } else {
                            // No tiene cédula
                            poseeCedulaField.value = 'NO';
                            cedulaAlumnoField.value = '';
                            cedulaEscolarField.value = '';
                            // Generar cédula escolar si no tiene cédula
                            setTimeout(function () {
                                generarCedulaEscolar();
                            }, 1500);
                        }

                        // Disparar evento change para actualizar estado de campos dependientes
                        poseeCedulaField.dispatchEvent(new Event('change'));
                    }

                    $('#modalFormAlumno').modal('show');
                } else {
                    swal('Atención', objData.msg || 'Error al cargar datos del alumno', 'error');
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                swal('Error', 'Error al procesar los datos del alumno', 'error');
            }
        } else if (request.readyState == 4) {
            swal('Error', 'Error de conexión al cargar datos del alumno', 'error');
        }
    }
}

function delAlumno() {
    var btnDelAlumno = document.querySelectorAll('.btnDelAlumno');
    btnDelAlumno.forEach(function (btnDelAlumno) {
        btnDelAlumno.removeEventListener('click', handleDeleteClick);
        btnDelAlumno.addEventListener('click', handleDeleteClick);
    });
}

function handleDeleteClick() {
    var idAlumno = this.getAttribute('rl');
    swal({
        title: "¿Inhabilitar Alumno?",
        text: "Por favor, ingrese el motivo de la inhabilitación:",
        type: "input",
        showCancelButton: true,
        confirmButtonText: "Sí, inhabilitar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false,
        inputPlaceholder: "Escriba el motivo..."
    }, function (inputValue) {
        if (inputValue === false) return false;
        if (inputValue === "") {
            swal.showInputError("El motivo es obligatorio");
            return false;
        }

        // Proceder con la inhabilitación mediante AJAX
        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/alumnos/del_alumnos.php';
        var strData = "idAlumno=" + idAlumno + "&motivo=" + encodeURIComponent(inputValue);
        request.open('POST', ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);
        request.onreadystatechange = function () {
            if (request.readyState == 4) {
                if (request.status == 200) {
                    try {
                        var objData = JSON.parse(request.responseText);
                        if (objData.status) {
                            swal("¡Inhabilitado!", objData.msg, "success");
                            tableAlumnos.ajax.reload(function () {
                                setTimeout(function () {
                                    delAlumno2();
                                }, 100);
                            });
                        } else {
                            swal("Atención", objData.msg, "error");
                        }
                    } catch (e) {
                        console.error('Error al parsear respuesta:', e);
                        console.error('Respuesta recibida:', request.responseText);
                        swal("Error", "Error al procesar la respuesta del servidor", "error");
                    }
                } else {
                    swal("Error", "Error de conexión con el servidor (Código: " + request.status + ")", "error");
                }
            }
        }
    });
}

function activateAlumno() {
    var btnActivateAlumno = document.querySelectorAll('.btnActivateAlumno');
    btnActivateAlumno.forEach(function (btnActivateAlumno) {
        btnActivateAlumno.removeEventListener('click', handleActivateClick);
        btnActivateAlumno.addEventListener('click', handleActivateClick);
    });
}

function handleActivateClick() {
    var idAlumno = this.getAttribute('rl');
    swal({
        title: "¿Activar Alumno?",
        text: "Por favor, ingrese el motivo de la reactivación:",
        type: "input",
        showCancelButton: true,
        closeOnConfirm: false,
        inputPlaceholder: "Escriba el motivo..."
    }, function (inputValue) {
        if (inputValue === false) return false;
        if (inputValue === "") {
            swal.showInputError("El motivo es obligatorio");
            return false;
        }

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/alumnos/activate_alumno.php';
        var strData = 'idAlumno=' + idAlumno + '&motivo=' + encodeURIComponent(inputValue);
        request.open('POST', ajaxUrl, true);
        request.setRequestHeader('Content-type', 'Application/x-www-form-urlencoded');
        request.send(strData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    swal("¡Activado!", objData.msg, "success");
                    tableAlumnos.ajax.reload();
                } else {
                    swal("Atención", objData.msg, "error");
                }
            }
        }
    })
}

function openModalAlumno() {
    document.querySelector('#idAlumno').value = "";
    document.querySelector('#titleModal').innerHTML = 'Nuevo Alumno';
    document.querySelector('.modal-header').classList.replace('updateRegister', 'headerRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-info', 'btn-primary');
    document.querySelector('#btnText').innerHTML = 'Guardar';

    // Limpiar errores y resetear formulario
    clearAllErrors();
    document.querySelector('#formAlumno').reset();

    // Resetear campos a estado inicial
    resetCamposModal();

    // Resetear campos geográficos
    document.querySelector('#listEstado').value = '';
    document.querySelector('#listCiudad').innerHTML = '<option value="">Seleccione una Ciudad</option>';
    document.querySelector('#listMunicipio').innerHTML = '<option value="">Seleccione un Municipio</option>';
    document.querySelector('#listParroquia').innerHTML = '<option value="">Seleccione una Parroquia</option>';

    $('#modalFormAlumno').modal('show');
}

// RESETEAR CAMPOS DEL MODAL A ESTADO INICIAL
function resetCamposModal() {
    const cedulaEscolarField = document.getElementById('cedulaEscolar');
    const numeroInicialField = document.getElementById('numeroInicialCedulaEscolar');
    const nacionalidadAlumnoField = document.getElementById('listNacionalidadAlumno');
    const cedulaAlumnoField = document.getElementById('cedulaAlumno');

    if (cedulaEscolarField) {
        cedulaEscolarField.disabled = false;
    }
    if (numeroInicialField) {
        numeroInicialField.disabled = false;
        numeroInicialField.value = '';
    }
    if (nacionalidadAlumnoField) {
        nacionalidadAlumnoField.disabled = false;
        nacionalidadAlumnoField.required = true;
        nacionalidadAlumnoField.value = 'V'; // Default a V
        nacionalidadAlumnoField.removeAttribute('data-validation-ignore');
    }
    if (cedulaAlumnoField) {
        cedulaAlumnoField.disabled = false;
        cedulaAlumnoField.required = false;
        cedulaAlumnoField.removeAttribute('data-validation-ignore');
    }
}

function inscribirAlumno() {
    var btnInscribirAlumno = document.querySelectorAll('.btnInscribirAlumno');
    btnInscribirAlumno.forEach(function (btnInscribirAlumno) {
        btnInscribirAlumno.removeEventListener('click', handleInscribirClick);
        btnInscribirAlumno.addEventListener('click', handleInscribirClick);
    });
}

function handleInscribirClick() {
    var alumnoId = this.getAttribute('rl');
    var repActive = this.getAttribute('rep_active');

    // Validación estricta: Representante Principal debe estar activo
    if (repActive === '0') {
        swal("Atención", "El alumno debe tener un representante activo asignado para poder inscribirse.", "error");
        return;
    }

    // Verificar si la función openModalInscripcion existe (está en functions-inscripcion.js)
    if (typeof openModalInscripcion === 'function') {
        openModalInscripcion(alumnoId);
    } else {
        swal('Atención', 'Error al abrir el modal de inscripción', 'error');
    }
}

// MANEJAR BOTÓN DE PDF DE INSCRIPCIÓN
function pdfInscripcion() {
    var btnPdfInscripcion = document.querySelectorAll('.btnPdfInscripcion');
    btnPdfInscripcion.forEach(function (btnPdfInscripcion) {
        btnPdfInscripcion.removeEventListener('click', handlePdfClick);
        btnPdfInscripcion.addEventListener('click', handlePdfClick);
    });
}

function handlePdfClick() {
    var alumnoId = this.getAttribute('rl');

    // Validar si existe director activo
    if (!hasDirector) {
        swal("Atención", "No es posible generar este documento porque no hay un director activo asignado en el sistema.", "error");
        return;
    }

    if (alumnoId && alumnoId > 0) {
        // Abrir el PDF en una nueva ventana con el ID del alumno
        window.open('Reportes/constancia_inscripcion.php?id=' + alumnoId, '_blank');
    } else {
        swal('Atención', 'ID de alumno no válido', 'error');
    }
}

// MANEJAR BOTÓN DE INFORMACIÓN ADICIONAL
// function editInfoAlumno removed (replaced by delegation)


// INICIALIZAR BÚSQUEDA DE REPRESENTANTE POR CÉDULA
function initBuscarRepresentante() {
    console.log("Inicializando búsqueda de representantes...");
    setupBuscarRepresentanteListener('cedulaRepresentante', 'txtNombreRepresentante', 'cedula-representante-error');
    setupBuscarRepresentanteListener('cedulaRepresentante2', 'txtNombreRepresentante2', 'cedula-representante2-error');
}

function setupBuscarRepresentanteListener(cedulaId, nombreId, errorId) {
    const cedulaField = document.getElementById(cedulaId);
    const nombreField = document.getElementById(nombreId);

    if (cedulaField && nombreField) {
        console.log(`Listener configurado para: ${cedulaId}`);
        cedulaField.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        cedulaField.addEventListener('blur', function () {
            const cedula = this.value.trim();
            console.log(`Blur en ${cedulaId}, valor: ${cedula}`);

            // Verificar duplicados antes de buscar
            let duplicateError = false;
            let otherFieldId = (cedulaId === 'cedulaRepresentante') ? 'cedulaRepresentante2' : 'cedulaRepresentante';
            let otherFieldName = (cedulaId === 'cedulaRepresentante') ? 'Representante 2 (Opcional)' : 'Representante 1 Principal';
            let otherField = document.getElementById(otherFieldId);

            if (otherField && otherField.value.trim() === cedula && cedula.length > 0) {
                swal("Atención", "Esta cédula ya está asignada en " + otherFieldName + ". Por favor verifique.", "warning");
                cedulaField.value = '';
                nombreField.value = '';
                cedulaField.classList.remove('is-valid', 'is-invalid');
                duplicateError = true;
            }

            if (!duplicateError) {
                if (cedula.length >= 7 && cedula.length <= 10) {
                    console.log(`Llamando buscarRepresentante para ${cedula}`);
                    buscarRepresentante(cedula, cedulaId, nombreId, errorId);
                } else if (cedula.length > 0) {
                    nombreField.value = '';
                    showFieldError(cedulaId, 'La cédula debe tener entre 7 y 10 dígitos');
                } else {
                    nombreField.value = '';
                    // Limpiar error si está vacío (para el opcional)
                    const errorElement = document.getElementById(errorId);
                    if (errorElement) errorElement.style.display = 'none';
                    cedulaField.classList.remove('is-invalid');
                    cedulaField.classList.remove('is-valid');
                }
            }

            // Lógica extra para el principal (generar cédula escolar)
            if (cedulaId === 'cedulaRepresentante') {
                const poseeCedulaField = document.getElementById('poseeCedula');
                if (poseeCedulaField && poseeCedulaField.value === 'NO') {
                    generarCedulaEscolar();
                }
            }
        });
    } else {
        console.error(`No se encontraron elementos para listener: ${cedulaId}, ${nombreId}`);
    }
}

// BUSCAR REPRESENTANTE POR CÉDULA
function buscarRepresentante(cedula, cedulaId = 'cedulaRepresentante', nombreId = 'txtNombreRepresentante', errorId = 'cedula-representante-error') {
    const nombreField = document.getElementById(nombreId);
    const cedulaField = document.getElementById(cedulaId);

    console.log(`Ejecutando buscarRepresentante: cedula=${cedula}, targetId=${nombreId}`);

    if (!cedulaField || !nombreField) {
        console.error("Campos no encontrados en buscarRepresentante");
        return;
    }

    // Mostrar indicador de carga
    nombreField.value = 'Buscando...';
    nombreField.style.color = '#666';

    const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    const ajaxUrl = './models/representantes/buscar_representante.php?cedula=' + encodeURIComponent(cedula);

    request.open('GET', ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            console.log(`Respuesta AJAX recibida: ${request.responseText}`);
            try {
                const objData = JSON.parse(request.responseText);

                if (objData.status && objData.data) {
                    console.log(`Datos encontrados: ${objData.data.nombre_completo}`);
                    nombreField.value = objData.data.nombre_completo;
                    nombreField.style.color = '#28a745';
                    cedulaField.classList.remove('is-invalid');
                    cedulaField.classList.add('is-valid');

                    // Limpiar error si existe
                    const errorElement = document.getElementById(errorId);
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.classList.remove('d-block');
                        errorElement.style.display = 'none';
                    }

                    // Actualizar cédula escolar automáticamente si el alumno no posee cédula
                    // Solo para el representante principal
                    if (cedulaId === 'cedulaRepresentante') {
                        const poseeCedulaField = document.getElementById('poseeCedula');
                        const cedulaAlumnoField = document.getElementById('cedulaAlumno');

                        // Verificar si el alumno no posee cédula propia
                        if (poseeCedulaField && poseeCedulaField.value === 'NO') {
                            // Verificar que el campo de cédula del alumno esté vacío o deshabilitado
                            const noTieneCedulaPropia = !cedulaAlumnoField ||
                                cedulaAlumnoField.value.trim() === '' ||
                                cedulaAlumnoField.disabled;

                            if (noTieneCedulaPropia) {
                                // Actualizar la cédula escolar con la nueva cédula del representante
                                generarCedulaEscolar();
                            }
                        }
                    }
                } else {
                    console.log("Representante no encontrado o status false");
                    nombreField.value = '';
                    nombreField.style.color = '#dc3545';
                    cedulaField.classList.remove('is-valid');
                    cedulaField.classList.add('is-invalid');
                    showFieldError(cedulaId, objData.msg || 'Representante no encontrado');
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                nombreField.value = '';
                nombreField.style.color = '#dc3545';
                cedulaField.classList.remove('is-valid');
                cedulaField.classList.add('is-invalid');
                showFieldError(cedulaId, 'Error al buscar representante');
            }
        } else if (request.readyState == 4 && request.status != 200) {
            console.error('Error status:', request.status);
            nombreField.value = '';
            nombreField.style.color = '#dc3545';
            cedulaField.classList.remove('is-valid');
            cedulaField.classList.add('is-invalid');
            showFieldError(cedulaId, 'Error en la conexión (' + request.status + ')');
        }
    };
}

// INICIALIZAR LÓGICA DE "POSEE CEDULA"
function initPoseeCedulaLogic() {
    const poseeCedulaField = document.getElementById('poseeCedula');
    const cedulaEscolarField = document.getElementById('cedulaEscolar');
    const numeroInicialField = document.getElementById('numeroInicialCedulaEscolar');
    const nacionalidadAlumnoField = document.getElementById('listNacionalidadAlumno');
    const cedulaAlumnoField = document.getElementById('cedulaAlumno');
    const cedulaRepresentanteField = document.getElementById('cedulaRepresentante');

    if (!poseeCedulaField) return;

    poseeCedulaField.addEventListener('change', function () {
        const tieneCedula = this.value === 'SI';
        const noTieneCedula = this.value === 'NO';

        // Si tiene cédula: bloquear cédula escolar y número inicial, desbloquear nacionalidad y cédula del alumno
        if (tieneCedula) {
            if (cedulaEscolarField) {
                cedulaEscolarField.disabled = true;
                cedulaEscolarField.value = '';
            }
            if (numeroInicialField) {
                numeroInicialField.disabled = true;
            }
            if (nacionalidadAlumnoField) {
                nacionalidadAlumnoField.disabled = false;
                nacionalidadAlumnoField.required = true; // Siempre obligatorio
                nacionalidadAlumnoField.removeAttribute('data-validation-ignore');
                // Desbloquear visualmente
                nacionalidadAlumnoField.style.pointerEvents = 'auto';
                nacionalidadAlumnoField.style.backgroundColor = '#fff';

                // Habilitar opción P (Pasaporte)
                const optionP = nacionalidadAlumnoField.querySelector('option[value="P"]');
                if (optionP) {
                    optionP.disabled = false;
                    optionP.hidden = false;
                }
            }
            if (cedulaAlumnoField) {
                cedulaAlumnoField.disabled = false;
                cedulaAlumnoField.required = true;
                cedulaAlumnoField.removeAttribute('data-validation-ignore');
            }
        }

        // Si NO tiene cédula: desbloquear cédula escolar y número inicial, bloquear cédula del alumno
        if (noTieneCedula) {
            if (cedulaEscolarField) {
                cedulaEscolarField.disabled = false;
            }
            if (numeroInicialField) {
                numeroInicialField.disabled = false;
            }
            if (nacionalidadAlumnoField) {
                nacionalidadAlumnoField.disabled = false;
                nacionalidadAlumnoField.required = true;
                nacionalidadAlumnoField.removeAttribute('data-validation-ignore');

                // Desbloquear visualmente (permitir selección manual)
                nacionalidadAlumnoField.style.pointerEvents = 'auto';
                nacionalidadAlumnoField.style.backgroundColor = '#fff';

                // Deshabilitar opción P (Pasaporte)
                const optionP = nacionalidadAlumnoField.querySelector('option[value="P"]');
                if (optionP) {
                    optionP.disabled = true;
                    optionP.hidden = true;
                    // Si estaba seleccionado P, cambiar a V
                    if (nacionalidadAlumnoField.value === 'P') {
                        nacionalidadAlumnoField.value = 'V';
                    }
                }

                // Si no hay selección válida (o estaba vacío), poner V por defecto
                if (!nacionalidadAlumnoField.value || nacionalidadAlumnoField.value === '') {
                    nacionalidadAlumnoField.value = 'V';
                }
            }
            if (cedulaAlumnoField) {
                cedulaAlumnoField.disabled = true;
                cedulaAlumnoField.required = false;
                cedulaAlumnoField.value = '';
                cedulaAlumnoField.setAttribute('data-validation-ignore', 'true');
                // Remover clases de validación
                cedulaAlumnoField.classList.remove('is-invalid', 'is-valid');
                // Limpiar error si existe
                const errorElement = document.getElementById('cedula-alumno-error');
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.classList.remove('d-block');
                }
            }
            // Generar cédula escolar automáticamente
            generarCedulaEscolar();
        }

        // Si no hay selección, resetear campos
        if (!tieneCedula && !noTieneCedula) {
            if (cedulaEscolarField) {
                cedulaEscolarField.disabled = false;
                cedulaEscolarField.value = '';
            }
            if (numeroInicialField) {
                numeroInicialField.disabled = false;
            }
            if (nacionalidadAlumnoField) {
                nacionalidadAlumnoField.disabled = false;
                nacionalidadAlumnoField.required = true;
                nacionalidadAlumnoField.removeAttribute('data-validation-ignore');
                // Restaurar estilo
                nacionalidadAlumnoField.style.pointerEvents = 'auto';
                nacionalidadAlumnoField.style.backgroundColor = '#fff';

                // Restaurar opción P
                const optionP = nacionalidadAlumnoField.querySelector('option[value="P"]');
                if (optionP) {
                    optionP.disabled = false;
                    optionP.hidden = false;
                }
            }
            if (cedulaAlumnoField) {
                cedulaAlumnoField.disabled = false;
                cedulaAlumnoField.required = false;
                cedulaAlumnoField.removeAttribute('data-validation-ignore');
            }
        }
    });
}

// INICIALIZAR GENERACIÓN DE CÉDULA ESCOLAR
function initCedulaEscolar() {
    const fechaNacField = document.getElementById('fechaNac');
    const cedulaEscolarField = document.getElementById('cedulaEscolar');
    const numeroInicialField = document.getElementById('numeroInicialCedulaEscolar');
    const poseeCedulaField = document.getElementById('poseeCedula');

    if (fechaNacField && cedulaEscolarField) {
        // Generar cédula escolar cuando cambie la fecha de nacimiento y NO tenga cédula
        fechaNacField.addEventListener('change', function () {
            if (poseeCedulaField && poseeCedulaField.value === 'NO' && this.value) {
                generarCedulaEscolar();
            }
        });
    }

    // Generar cédula escolar cuando cambie el número inicial y NO tenga cédula
    if (numeroInicialField && cedulaEscolarField) {
        numeroInicialField.addEventListener('change', function () {
            if (poseeCedulaField && poseeCedulaField.value === 'NO') {
                generarCedulaEscolar();
            }
            // Mostrar aviso al seleccionar (Eliminado para usar botón de ayuda)
            // if (this.value) {
            //     swal("Información", "Indique cuantos partos se realizaron en el año, dependiendo va ser el numero para la cédula escolar", "info");
            // }
        });
    }
}

// GENERAR CÉDULA ESCOLAR AUTOMÁTICAMENTE
function generarCedulaEscolar() {
    const fechaNacField = document.getElementById('fechaNac');
    const cedulaEscolarField = document.getElementById('cedulaEscolar');
    const numeroInicialField = document.getElementById('numeroInicialCedulaEscolar');
    const cedulaRepresentanteField = document.getElementById('cedulaRepresentante');

    if (!fechaNacField || !cedulaEscolarField) return;

    const fechaNac = fechaNacField.value;
    const cedulaRepresentante = cedulaRepresentanteField ? cedulaRepresentanteField.value.trim() : '';
    const numeroInicial = numeroInicialField ? numeroInicialField.value : ' ';

    if (!fechaNac) {
        cedulaEscolarField.value = '';
        return;
    }

    // Obtener últimos 2 dígitos del año de nacimiento
    const anio = fechaNac.split('-')[0];
    const ultimosDosDigitosAnio = anio.substring(anio.length - 2);

    // Formato: número inicial (1-10) + últimos 2 dígitos del año + cédula del representante
    let cedulaEscolar = numeroInicial + ultimosDosDigitosAnio;

    if (cedulaRepresentante) {
        cedulaEscolar += cedulaRepresentante;
    }

    cedulaEscolarField.value = cedulaEscolar;
}

// INICIALIZAL AYUDA Nº HIJOS
function initAyudaNHijos() {
    const btnAyuda = document.getElementById('btnAyudaNHijos');
    if (btnAyuda) {
        btnAyuda.addEventListener('click', function () {
            swal("Información", "Indique cuantos partos se realizaron en el año, dependiendo va ser el numero para la cédula escolar", "info");
        });
    }
}

// INICIALIZAR CÁLCULO DE EDAD
function initCalculoEdad() {
    const fechaNacField = document.getElementById('fechaNac');
    const edadField = document.getElementById('edad');

    if (fechaNacField) {
        // Función para validar y mostrar la edad
        const validarYMostrarEdad = function () {
            if (fechaNacField.value) {
                const edad = calculateAge(fechaNacField.value);
                if (edadField) edadField.value = edad;

                // Validar rango de edad (4-15 años)
                if (edad < 4 || edad > 15) {
                    // Mostrar error en tiempo real
                    fechaNacField.classList.add('is-invalid');
                    fechaNacField.classList.remove('is-valid');
                    if (edadField) {
                        edadField.classList.add('is-invalid');
                        edadField.classList.remove('is-valid');
                    }

                    // Mostrar mensaje de error
                    let errorMsg = '';
                    if (edad < 4) {
                        errorMsg = 'La edad debe ser mínimo 4 años';
                    } else if (edad > 15) {
                        errorMsg = 'La edad debe ser máximo 15 años';
                    }
                    showFieldError('fechaNac', errorMsg);
                } else {
                    // Edad válida
                    fechaNacField.classList.remove('is-invalid');
                    fechaNacField.classList.add('is-valid');
                    if (edadField) {
                        edadField.classList.remove('is-invalid');
                        edadField.classList.add('is-valid');
                    }

                    // Limpiar error si existe
                    const errorElement = document.getElementById('error-fechaNac');
                    if (errorElement) {
                        errorElement.remove();
                    }
                }
            } else {
                if (edadField) edadField.value = '';
                fechaNacField.classList.remove('is-invalid', 'is-valid');
                if (edadField) {
                    edadField.classList.remove('is-invalid', 'is-valid');
                }

                // Limpiar error si existe
                const errorElement = document.getElementById('error-fechaNac');
                if (errorElement) {
                    errorElement.remove();
                }
            }
        };

        // Calcular edad cuando cambie la fecha de nacimiento
        fechaNacField.addEventListener('change', validarYMostrarEdad);

        // También calcular al escribir (blur)
        fechaNacField.addEventListener('blur', validarYMostrarEdad);

        // Validar en tiempo real mientras escribe (input)
        fechaNacField.addEventListener('input', validarYMostrarEdad);
    }
}

// CALCULAR EDAD A PARTIR DE FECHA DE NACIMIENTO
function calculateAge(birthDate) {
    if (!birthDate) return '';

    const today = new Date();
    const birth = new Date(birthDate);

    // Verificar que la fecha sea válida
    if (isNaN(birth.getTime())) return '';

    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    // Ajustar si aún no ha cumplido años este año
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    return age;
}
/* function infoAlumno removed - using inline onclick */