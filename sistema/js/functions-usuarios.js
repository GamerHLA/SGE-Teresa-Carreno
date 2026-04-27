/**
 * FUNCTIONS-USUARIOS.JS
 * =====================
 * 
 * Gestión completa del módulo de usuarios del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de usuarios del sistema
 * - Formulario de creación y edición de usuarios
 * - Asignación de roles (Administrador/Asistente)
 * - Vinculación de usuario con profesor
 * - Auto-llenado de nombre desde profesor seleccionado
 * - Gestión de contraseñas con toggle de visibilidad
 * - Validación en tiempo real de coincidencia de contraseñas
 * - Opción de cambiar contraseña en modo edición
 * - Activación/Inhabilitación de usuarios
 * - Actualización dinámica del nombre en sidebar al editar usuario actual
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

/***********************USUARIOS *******************************/

var tableUsuarios;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializa la tabla de usuarios con DataTables y configuración de AJAX
    tableUsuarios = $('#tableUsuarios').DataTable({
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
            "url": "./models/usuarios/table_usuarios.php",
            "dataSrc": ""
        },
        "columns": [
            {
                "data": null,
                "render": function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { "data": "nombre" },
            { "data": "usuario" },
            { "data": "nombre_rol" },
            { "data": "estatus" },
            { "data": "options" }
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[0, "asc"]],
        "drawCallback": function () {
            editUser();
            delUser();
            activateUsuario();
        }
    });

    // Validación en tiempo real para el campo nombre - DESHABILITADA
    /*
    var txtNombre = document.querySelector('#txtNombre');
    if (txtNombre) {
        txtNombre.addEventListener('input', function () {
            // Remover números y caracteres especiales
            this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑ\s]/g, '');
        });

        // Prevenir pegado de texto con caracteres no permitidos
        txtNombre.addEventListener('paste', function (e) {
            var pastedText = (e.clipboardData || window.clipboardData).getData('text');
            if (/[^A-Za-záéíóúÁÉÍÓÚñÑ\s]/.test(pastedText)) {
                e.preventDefault();
                swal("Atención", "No se permiten números ni caracteres especiales en el nombre", "warning");
            }
        });
    }
    */

    // Auto-llenar nombre cuando se selecciona un profesor
    var listProfesor = document.querySelector('#listProfesor');
    if (listProfesor) {
        listProfesor.addEventListener('change', function () {
            var selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                var profesorNombre = selectedOption.textContent;
                document.querySelector('#txtNombre').value = profesorNombre;
            } else {
                document.querySelector('#txtNombre').value = '';
            }
        });
    }

    // Toggle para mostrar/ocultar contraseña
    var togglePassword = document.querySelector('#togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function () {
            var passwordField = document.querySelector('#clave');
            var icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Toggle visibilidad de campos de contraseña
    var listChangePass = document.querySelector('#listChangePass');
    if (listChangePass) {
        listChangePass.addEventListener('change', function () {
            var passwordFields = document.querySelector('#passwordFields');
            var clave = document.querySelector('#clave');
            var claveConfirm = document.querySelector('#claveConfirm');

            if (this.value === 'si') {
                passwordFields.style.display = 'block';
                clave.required = true;
                claveConfirm.required = true;
            } else {
                passwordFields.style.display = 'none';
                clave.required = false;
                claveConfirm.required = false;
                clave.value = '';
                claveConfirm.value = '';
                checkPasswordMatch(); // Limpiar feedback
            }
        });
    }

    // Toggle para mostrar/ocultar confirmación de contraseña
    var togglePasswordConfirm = document.querySelector('#togglePasswordConfirm');
    if (togglePasswordConfirm) {
        togglePasswordConfirm.addEventListener('click', function () {
            var passwordField = document.querySelector('#claveConfirm');
            var icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Validación en tiempo real de coincidencia de contraseñas
    var claveConfirm = document.querySelector('#claveConfirm');
    var clave = document.querySelector('#clave');
    var feedback = document.querySelector('#passwordMatchFeedback');

    function checkPasswordMatch() {
        if (claveConfirm.value === '' && clave.value === '') {
            feedback.textContent = '';
            feedback.className = 'form-text';
            return;
        }

        if (claveConfirm.value === '') {
            feedback.textContent = '';
            feedback.className = 'form-text';
            return;
        }

        if (clave.value === claveConfirm.value) {
            feedback.textContent = '✓ Las contraseñas coinciden';
            feedback.className = 'form-text text-success';
        } else {
            feedback.textContent = '✗ Las contraseñas no coinciden';
            feedback.className = 'form-text text-danger';
        }
    }

    if (claveConfirm) {
        claveConfirm.addEventListener('input', checkPasswordMatch);
    }

    if (clave) {
        clave.addEventListener('input', checkPasswordMatch);
    }

    // CREAR USUARIO
    var formUser = document.querySelector('#formUser');
    formUser.onsubmit = function (e) {
        e.preventDefault();
        var idUser = document.querySelector('#idUser').value;
        var strNombre = document.querySelector('#txtNombre').value;
        var strUsuario = document.querySelector('#txtUsuario').value;
        var password = document.querySelector('#clave').value;
        var passwordConfirm = document.querySelector('#claveConfirm').value;
        var strRol = document.querySelector('#listRol').value;
        var intProfesor = document.querySelector('#listProfesor').value;
        var intStatus = document.querySelector('#listStatus').value;

        // Validación de campos vacíos
        if (strNombre == '' || strUsuario == '' || strRol == '' || intProfesor == '' || intStatus == '') {
            swal("Atención", "Todos los campos son necesarios", "error");
            return false;
        }

        // Si se va a cambiar la contraseña o es un usuario nuevo
        var changePass = document.querySelector('#listChangePass').value;
        if (idUser == 0 || changePass === 'si') {
            if (password == '') {
                swal("Atención", "La contraseña es obligatoria", "error");
                return false;
            }
            if (password !== passwordConfirm) {
                swal("Atención", "Las contraseñas no coinciden", "error");
                return false;
            }
        }

        // Validación de solo letras en el nombre (máximo 20 caracteres)
        // Eliminada validación estricta de solo letras a petición del usuario
        /*
        var letrasRegex = /^[A-Za-záéíóúÁÉÍÓÚñÑ\s]{1,20}$/;
        if (!letrasRegex.test(strNombre)) {
            swal("Atención", "El nombre solo puede contener letras y espacios (máximo 20 caracteres)", "error");
            return false;
        }
        */

        var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        var ajaxUrl = './models/usuarios/ajax-usuarios.php';
        var formData = new FormData(formUser);
        request.open('POST', ajaxUrl, true);
        request.send(formData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    $('#modalFormUser').modal('hide');
                    formUser.reset();
                    swal('Listo', objData.msg, 'success');
                    tableUsuarios.ajax.reload(null, false);
                    if (objData.new_name) {
                        var sidebarName = document.querySelector('.app-sidebar__user-designation');
                        if (sidebarName) sidebarName.innerText = objData.new_name;
                    }
                } else {
                    swal('Atención', objData.msg, 'error');
                }
            }
        }
    }
});

// Función para abrir el modal de usuario nuevo
function openModal() {
    document.querySelector('#idUser').value = "";
    document.querySelector('#titleModal').innerHTML = 'Nuevo Usuario';
    document.querySelector('.modal-header').classList.replace('updateRegister', 'headerRegister');
    document.querySelector('#btnActionForm').classList.replace('btn-info', 'btn-primary');
    document.querySelector('#btnText').innerHTML = 'Guardar';
    document.querySelector('#formUser').reset();

    // Asegurar que el campo contraseña sea requerido
    document.querySelector('#containerChangePass').style.display = 'none';
    document.querySelector('#listChangePass').value = 'si';
    document.querySelector('#passwordFields').style.display = 'block';
    document.querySelector('#clave').required = true;
    document.querySelector('#claveConfirm').required = true;

    // Restaurar select de roles y estatus con valores por defecto
    document.querySelector("#listRol").innerHTML = `
        <option value="1">Administrador</option>
        <option value="2" selected>Asistente</option>
    `;
    document.querySelector("#listStatus").innerHTML = `
        <option value="1" selected>Activo</option>
        <option value="2">Inactivo</option>
    `;

    // Limpiar campo nombre
    document.querySelector('#txtNombre').value = '';

    // Limpiar feedback de contraseñas
    var feedback = document.querySelector('#passwordMatchFeedback');
    if (feedback) {
        feedback.textContent = '';
        feedback.className = 'form-text';
    }

    // Desmarcar checkbox de director
    // document.querySelector('#checkDirector').checked = false;

    // Verificar si ya existe un director y ocultar/mostrar checkbox
    // checkDirectorExists();

    // Cargar lista de profesores
    loadProfesores();

    $('#modalFormUser').modal('show');
}



// Función para editar usuario
function editUser() {
    var btnEditUser = document.querySelectorAll('.btnEditUser');
    btnEditUser.forEach(function (btnEditUser) {
        btnEditUser.onclick = function () {
            document.querySelector('#titleModal').innerHTML = 'Actualizar Usuario';
            document.querySelector('.modal-header').classList.replace('headerRegister', 'updateRegister');
            document.querySelector('#btnActionForm').classList.replace('btn-primary', 'btn-info');
            document.querySelector('#btnText').innerHTML = 'Actualizar';

            var idUser = this.getAttribute('rl');
            var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            var ajaxUrl = './models/usuarios/edit_usuarios.php?id=' + idUser;
            request.open('GET', ajaxUrl, true);
            request.send();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    var objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        document.querySelector('#idUser').value = objData.data.user_id;
                        document.querySelector('#txtNombre').value = objData.data.nombre;
                        document.querySelector('#txtUsuario').value = objData.data.usuario;

                        // Select de roles
                        let rolOptions = `
                            <option value="1"${objData.data.rol == 1 ? ' selected' : ''}>Administrador</option>
                            <option value="2"${objData.data.rol == 2 ? ' selected' : ''}>Asistente</option>
                        `;
                        document.querySelector("#listRol").innerHTML = rolOptions;

                        // Select de estatus
                        let statusOptions = `
                            <option value="1"${objData.data.estatus == 1 ? ' selected' : ''}>Activo</option>
                            <option value="2"${objData.data.estatus == 2 ? ' selected' : ''}>Inactivo</option>
                        `;
                        document.querySelector("#listStatus").innerHTML = statusOptions;

                        // Marcar checkbox de director
                        // document.querySelector('#checkDirector').checked = objData.data.es_director == 1;

                        // Verificar si ya existe un director (excepto el actual)
                        // checkDirectorExists(objData.data.user_id);

                        // En modo edición, mostrar el selector de cambio de contraseña
                        document.querySelector('#containerChangePass').style.display = 'block';
                        document.querySelector('#listChangePass').value = 'no';
                        document.querySelector('#passwordFields').style.display = 'none';
                        document.querySelector('#clave').required = false;
                        document.querySelector('#claveConfirm').required = false;
                        document.querySelector('#clave').placeholder = "coloque una nueva contraseña";
                        document.querySelector('#claveConfirm').placeholder = "confirme la nueva contraseña";

                        // Cargar profesores y seleccionar el asignado
                        loadProfesores(objData.data.profesor_id);

                        $("#modalFormUser").modal("show");
                    } else {
                        swal('Atención', objData.msg, 'error');
                    }
                }
            }
        }
    });
}

// Función para eliminar usuario
function delUser() {
    var btnDelUser = document.querySelectorAll('.btnDelUser');
    btnDelUser.forEach(function (btnDelUser) {
        btnDelUser.onclick = function () {
            var idUser = this.getAttribute('rl');

            swal({
                title: "¿Inhabilitar Usuario?",
                text: "¿Realmente desea inhabilitar al usuario?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, inhabilitar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (Confirm) {
                if (Confirm) {
                    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxDelUser = './models/usuarios/delet_usuarios.php';
                    var strData = "idUser=" + idUser;
                    request.open('POST', ajaxDelUser, true);
                    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    request.send(strData);
                    request.onreadystatechange = function () {
                        if (request.readyState == 4 && request.status == 200) {
                            var objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                swal("¡Inhabilitado!", objData.msg, "success");
                                tableUsuarios.ajax.reload(null, false);
                            } else {
                                swal("Atención", objData.msg, "error");
                            }
                        }
                    }
                }
            });
        }
    });
}

// Función para cargar la lista de profesores
function loadProfesores(selectedProfesorId = null) {
    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    var ajaxUrl = './models/options/options-profesor.php';
    request.open('GET', ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            var profesores = JSON.parse(request.responseText);
            var selectProfesor = document.querySelector('#listProfesor');

            // Limpiar opciones existentes
            selectProfesor.innerHTML = '<option value="">Seleccione un profesor</option>';

            // Agregar profesores
            profesores.forEach(function (profesor) {
                var option = document.createElement('option');
                option.value = profesor.profesor_id;
                option.textContent = profesor.nombre + ' ' + profesor.apellido;

                // Seleccionar si coincide con el ID proporcionado
                if (selectedProfesorId && profesor.profesor_id == selectedProfesorId) {
                    option.selected = true;
                    // Auto-llenar nombre si se está editando
                    document.querySelector('#txtNombre').value = profesor.nombre + ' ' + profesor.apellido;
                }

                selectProfesor.appendChild(option);
            });
        }
    }
}

// Función para verificar si ya existe un director y ocultar/mostrar checkbox
// function checkDirectorExists(currentUserId = null) {
//     var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
//     var ajaxUrl = './models/usuarios/check_director.php';
//     if (currentUserId) {
//         ajaxUrl += '?exclude_id=' + currentUserId;
//     }
//     request.open('GET', ajaxUrl, true);
//     request.send();
//     request.onreadystatechange = function () {
//         if (request.readyState == 4 && request.status == 200) {
//             var response = JSON.parse(request.responseText);
//             var checkboxGroup = document.querySelector('#checkDirector').closest('.form-group');

//             if (response.exists) {
//                 // Ocultar checkbox si ya existe un director
//                 checkboxGroup.style.display = 'none';
//             } else {
//                 // Mostrar checkbox si no existe director
//                 checkboxGroup.style.display = 'block';
//             }
//         }
//     }
// }

/*********************** FIN USUARIOS *******************************/
