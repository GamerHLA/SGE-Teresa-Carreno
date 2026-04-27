<?php
/**
 * MODAL.PHP
 * =========
 * 
 * Modal para crear y editar usuarios del sistema.
 * 
 * CAMPOS:
 * - Profesor (select con opciones cargadas vía AJAX)
 * - Usuario (texto, máx 20 caracteres)
 * - Opción de cambiar contraseña (solo en modo edición)
 * - Contraseña (con toggle de visibilidad)
 * - Confirmar contraseña (con validación en tiempo real)
 * - Rol (Administrador/Asistente)
 * - Estado (Activo/Inactivo)
 * 
 * FUNCIONALIDADES:
 * - Auto-llenado de nombre desde profesor seleccionado
 * - Validación de coincidencia de contraseñas
 * - Toggle de visibilidad de contraseñas
 * - Modo crear/editar dinámico
 * 
 * USADO EN: lista_usuarios.php
 * SCRIPT: functions-usuarios.js
 */
?>
<!-- Vertically centered scrollable modal -->
<div class="modal fade" id="modalFormUser" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nuevo Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                    <form id="formUser" name="formUser">
                        <input type="hidden" name="idUser" id="idUser" value="">
                        <input type="hidden" name="txtNombre" id="txtNombre" value="">
                        <div class="form-group">
                            <label for="listProfesor">Profesor</label>
                            <select class="form-control" name="listProfesor" id="listProfesor" required>
                                <!-- Options loaded via AJAX -->
                            </select>
                            <small class="form-text text-muted">Seleccione el profesor que se registrará en el
                                sistema.</small>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Usuario</label>
                            <input type="text" class="form-control" id="txtUsuario" name="txtUsuario"
                                placeholder="Usuario" required maxlength="20">
                        </div>
                        <div class="form-group" id="containerChangePass" style="display: none;">
                            <label for="listChangePass">¿Cambiar Contraseña?</label>
                            <select class="form-control" name="listChangePass" id="listChangePass">
                                <option value="si">Sí</option>
                                <option value="no" selected>No</option>
                            </select>
                        </div>
                        <div id="passwordFields">
                            <div class="form-group">
                                <label class="control-label">Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control validPass" id="clave" name="clave"
                                        placeholder="Contraseña" maxlength="20">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Confirmar Contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control validPass" id="claveConfirm"
                                        name="claveConfirm" placeholder="Confirmar contraseña" maxlength="20">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button"
                                            id="togglePasswordConfirm">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text" id="passwordMatchFeedback"></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="exampleSelect1">Rol</label>
                            <select class="form-control" name="listRol" id="listRol" required>
                                <option value="1">Administrador</option>
                                <option value="2" selected>Asistente</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exampleSelect1">Estado</label>
                            <select class="form-control" name="listStatus" id="listStatus" required>
                                <option value="1" selected>Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                        <div class="tile-footer">
                            <button id="btnActionForm" class="btn btn-primary" type="submit"><i
                                    class="fa fa-fw fa-lg fa-check-circle"></i><span
                                    id="btnText">Guardar</span></button>&nbsp;&nbsp;&nbsp;
                            <button class="btn btn-secondary" data-dismiss="modal"><i
                                    class="fa fa-fw fa-lg fa-times-circle"></i>Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>