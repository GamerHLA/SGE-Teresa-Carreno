// ========================================
// FUNCIONES DE ACTIVACIÓN CENTRALIZADAS
// ========================================
// Este archivo contiene todas las funciones para activar registros inactivos
// Se incluye en cada módulo que necesite funcionalidad de activación

// FUNCIÓN ACTIVAR ALUMNO
function activateAlumno() {
    var btnActivateAlumno = document.querySelectorAll('.btnActivateAlumno');
    btnActivateAlumno.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idAlumno = this.getAttribute('rl');

            swal({
                title: "¿Activar Alumno?",
                text: "¿Realmente desea activar al alumno?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, activar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (Confirm) {
                if (Confirm) {
                    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxActivateAlumno = './models/activador/active_alumno.php';
                    var strData = "idAlumno=" + idAlumno;
                    request.open('POST', ajaxActivateAlumno, true);
                    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
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
                    };
                }
            });
        });
    });
}

// FUNCIÓN ACTIVAR CURSO
function activateCurso() {
    var btnActivateCurso = document.querySelectorAll('.btnActivateCurso');
    btnActivateCurso.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idCurso = this.getAttribute('rl');

            swal({
                title: "¿Activar Grado/Sección?",
                text: "¿Realmente desea activar el grado/sección?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, activar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (Confirm) {
                if (Confirm) {
                    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxActivateCurso = './models/activador/active_curso.php';
                    var strData = "idCurso=" + idCurso;
                    request.open('POST', ajaxActivateCurso, true);
                    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    request.send(strData);
                    request.onreadystatechange = function () {
                        if (request.readyState == 4 && request.status == 200) {
                            var objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                swal("¡Activado!", objData.msg, "success");
                                tableCursos.ajax.reload();
                            } else {
                                swal("Atención", objData.msg, "error");
                            }
                        }
                    };
                }
            });
        });
    });
}

// FUNCIÓN ACTIVAR PROFESOR
function activateProfesor() {
    var btnActivateProfesor = document.querySelectorAll('.btnActivateProfesor');
    btnActivateProfesor.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idProfesor = this.getAttribute('rl');

            swal({
                title: "¿Activar Profesor?",
                text: "¿Realmente desea activar al profesor?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, activar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (Confirm) {
                if (Confirm) {
                    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxActivateProfesor = './models/activador/active_profesor.php';
                    var strData = "idProfesor=" + idProfesor;
                    request.open('POST', ajaxActivateProfesor, true);
                    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    request.send(strData);
                    request.onreadystatechange = function () {
                        if (request.readyState == 4 && request.status == 200) {
                            var objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                swal("¡Activado!", objData.msg, "success");
                                tableProfesores.ajax.reload();
                            } else {
                                swal("Atención", objData.msg, "error");
                            }
                        }
                    };
                }
            });
        });
    });
}



// FUNCIÓN ACTIVAR USUARIO
function activateUsuario() {
    var btnActivateUsuario = document.querySelectorAll('.btnActivateUsuario');
    btnActivateUsuario.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idUsuario = this.getAttribute('rl');

            swal({
                title: "¿Activar Usuario?",
                text: "¿Realmente desea activar al usuario?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, activar",
                cancelButtonText: "No, cancelar",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function (Confirm) {
                if (Confirm) {
                    var request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
                    var ajaxActivateUsuario = './models/activador/active_usuario.php';
                    var strData = "idUsuario=" + idUsuario;
                    request.open('POST', ajaxActivateUsuario, true);
                    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    request.send(strData);
                    request.onreadystatechange = function () {
                        if (request.readyState == 4 && request.status == 200) {
                            var objData = JSON.parse(request.responseText);
                            if (objData.status) {
                                swal("¡Activado!", objData.msg, "success");
                                tableUsuarios.ajax.reload();
                            } else {
                                swal("Atención", objData.msg, "error");
                            }
                        }
                    };
                }
            });
        });
    });
}
