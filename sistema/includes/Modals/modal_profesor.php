<div class="modal fade" id="modalFormProfesor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nuevo Profesor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                    <form id="formProfesor" name="formProfesor">
                        <input type="hidden" name="idProfesor" id="idProfesor" value="">



                        <!-- Nacionalidad -->
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="listNacionalidadProfesor">Seleccione</label>
                                <select class="form-control" name="listNacionalidadProfesor"
                                    id="listNacionalidadProfesor" required>
                                    <option value=""> </option>
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                    <option value="P">P</option>
                                </select>
                                <div class="invalid-feedback">Por favor, seleccione una nacionalidad.</div>
                            </div>
                            <div class="form-group col-md-10">
                                <label class="control-label">Cédula</label>
                                <input type="text" class="form-control" id="cedula" name="cedula"
                                    placeholder="Cédula (7-10 dígitos)" maxlength="10" required>
                                <div class="invalid-feedback" id="cedula-profesor-error"></div>
                                <small class="form-text text-muted">Al ingresar la cédula, se verificará automáticamente
                                    si ya existe en el sistema</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Nombre</label>
                            <input class="form-control" id="txtNombre" name="txtNombre" type="text"
                                placeholder="Nombre, máximo 20 caracteres" required>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Apellido</label>
                            <input type="text" class="form-control" id="txtApellido" name="txtApellido"
                                placeholder="Apellido, máximo 20 caracteres" required>
                        </div>

                        <!-- Sexo -->
                        <div class="form-group">
                            <label for="listSexo">Sexo <span class="text-danger">*</span></label>
                            <select class="form-control" name="listSexo" id="listSexo" required>
                                <option value="">Seleccione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <!-- Dirección con Selects Dependientes -->
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="listEstado">Estado</label>
                                <select class="form-control" name="listEstado" id="listEstado" required>
                                    <option value="">Seleccione un Estado</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="listCiudad">Ciudad</label>
                                <select class="form-control" name="listCiudad" id="listCiudad" required>
                                    <option value="">Seleccione una Ciudad</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="listMunicipio">Municipio</label>
                                <select class="form-control" name="listMunicipio" id="listMunicipio" required>
                                    <option value="">Seleccione un Municipio</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="listParroquia">Parroquia</label>
                                <select class="form-control" name="listParroquia" id="listParroquia" required>
                                    <option value="">Seleccione una Parroquia</option>
                                </select>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="control-label">Teléfono</label>
                            <input class="form-control" type="text" id="telefono" name="telefono"
                                placeholder="0XXXXXXXXXX - Debe comenzar con 0" required>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Correo</label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Correo electrónico, ej: ejemplo@dominio.com" required>
                        </div>
                        <!-- Niveles de Educación (Múltiples) -->
                        <div class="form-group">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="font-weight-bold">Nivel de educación <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select class="form-control" id="listCategoriaEducacion"
                                                    name="listCategoriaEducacion">
                                                    <option value="">Seleccione un nivel</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <?php if ($_SESSION['rol'] == 1) { ?>
                                                        <button class="btn btn-success" type="button"
                                                            onclick="openModalAddCategoria()" title="Nueva Categoría"><i
                                                                class="fas fa-plus-circle"></i></button>
                                                        <button class="btn btn-danger" type="button"
                                                            onclick="openModalDeleteCategoria()"
                                                            title="Eliminar Categoría Seleccionada"><i
                                                                class="fa-solid fa-ban"></i></button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="font-weight-bold">Especialización <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select class="form-control" id="listEspecializacion"
                                                    name="listEspecializacion">
                                                    <option value="">Primero seleccione un nivel</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <?php if ($_SESSION['rol'] == 1) { ?>
                                                        <button class="btn btn-success" type="button"
                                                            id="btnAddEspecializacion"
                                                            onclick="openModalAddEspecializacion()"
                                                            title="Nueva Especialización" disabled><i
                                                                class="fas fa-plus-circle"></i></button>
                                                        <button class="btn btn-danger" type="button"
                                                            id="btnDeleteEspecializacion"
                                                            onclick="openModalDeleteEspecializacion()"
                                                            title="Eliminar Especialización Seleccionada"><i
                                                                class="fa-solid fa-ban"></i></button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row mt-1">
                                        <div class="col-md-12 text-right">
                                            <button class="btn btn-primary" type="button" id="btnAgregarNivelProfesor"
                                                onclick="agregarNivelAProfesor()">
                                                <i class="fas fa-plus"></i> Agregar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tabla de niveles agregados -->
                                    <div class="mt-3" id="divNivelesAgregados" style="display: none;">
                                        <h6 class="font-weight-bold">Niveles Agregados:</h6>
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Nivel</th>
                                                    <th>Especialización</th>
                                                    <th width="80">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbodyNivelesProfesor">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group d-none">
                            <label for="listStatus">Estado</label>
                            <select class="form-control" id="listStatus" name="listStatus" required>
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkDirector" name="checkDirector"
                                    value="1">
                                <label class="form-check-label" for="checkDirector">
                                    Marcar como Director
                                </label>
                            </div>
                            <small class="form-text text-muted">Solo puede existir un director en el sistema.</small>
                        </div>

                        <div id="directorDates" style="display: none;">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="control-label">Fecha Inicio Director</label>
                                    <input class="form-control" type="date" id="director_fecha_inicio"
                                        name="director_fecha_inicio">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="control-label">Fecha Fin Director</label>
                                    <input class="form-control" type="date" id="director_fecha_fin"
                                        name="director_fecha_fin">
                                </div>
                            </div>
                        </div>
                        <div class="tile-footer">
                            <button id="btnActionForm" class="btn btn-primary" type="submit"><i
                                    class="fa fa-fw fa-lg fa-check-circle"></i><span
                                    id="btnText">Guardar</span></button>&nbsp;&nbsp;&nbsp;
                            <button class="btn btn-secondary" type="button" data-dismiss="modal"><i
                                    class="fa fa-fw fa-lg fa-times-circle"></i>Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Autocompletar (Dinámico) -->
<div class="modal fade" id="modalConfirmacionAutocompletar" tabindex="-1" role="dialog" aria-hidden="true"
    style="display: none;">
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
                <div class="alert alert-info" id="infoPersonaEncontrada">
                    <strong id="nombreCompleto"></strong><br>
                    <span id="emailInfo"></span><br>
                    <span id="telefonoInfo"></span>
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

<!-- CSS para las validaciones -->
<style>
    .is-invalid {
        border-color: #dc3545 !important;
    }

    .is-valid {
        border-color: #28a745 !important;
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    .invalid-feedback.d-block {
        display: block;
    }



    /* Estilos para el modal de confirmación */
    #modalConfirmacionAutocompletar .modal-header {
        background: #28a745;
        color: #fff;
    }

    #modalConfirmacionAutocompletar .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }
</style>


<!-- Modal para Agregar Nueva Categoría -->
<div class="modal fade" id="modalAddCategoria" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title">Agregar categoría del nivel de estudio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAddCategoria">
                    <div class="form-group">
                        <label class="control-label">Nombre de la nueva categoría del nivel de estudio <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="txtNombreCategoria" name="txtNombreCategoria"
                            placeholder="Ej: Superior Especializado" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+"
                            title="Solo se permiten letras y espacios">
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="guardarNuevaCategoria()">
                    <i class="fa fa-save"></i> Guardar
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar Nueva Especialización -->
<div class="modal fade" id="modalAddEspecializacion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title">Agregar Especialización</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAddEspecializacion">
                    <div class="form-group">
                        <label class="control-label">Nombre de la Especialización <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="txtNuevaEspecializacion"
                            name="txtNuevaEspecializacion"
                            placeholder="Ej: Educación Superior - Ingeniería de Software">
                    </div>
                    <div class="form-group">
                        <label class="control-label">Categoría <span class="text-danger">*</span></label>
                        <select class="form-control" id="listCategoriaNuevaEspecializacion"
                            name="listCategoriaNuevaEspecializacion">
                            <option value="">Seleccione una categoría</option>
                        </select>
                    </div>
                    <small class="form-text text-muted">
                        La nueva especialización se agregará al final de la categoría seleccionada.
                    </small>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="saveNuevaEspecializacion()">
                    <i class="fa fa-save"></i> Guardar
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Categoría -->
<div class="modal fade" id="modalDeleteCategoria" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header headerRegister bg-danger text-white">
                <h5 class="modal-title">Eliminar Categoría</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formDeleteCategoria">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Advertencia:</strong>
                        Al eliminar una categoría, se eliminarán permanentemente todas las especializaciones asociadas a
                        ella.
                    </div>
                    <div class="form-group">
                        <label class="control-label">Seleccione la categoría a eliminar:</label>
                        <select class="form-control" id="listDeleteCategoria" name="listDeleteCategoria">
                            <option value="">Seleccione una categoría</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="deleteCategoria()">
                    <i class="fa-solid fa-ban"></i> Eliminar
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Especialización -->
<div class="modal fade" id="modalDeleteEspecializacion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header headerRegister bg-danger text-white">
                <h5 class="modal-title">Eliminar Especialización</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formDeleteEspecializacion">
                    <div class="form-group">
                        <label class="control-label">Categoría:</label>
                        <select class="form-control" id="listCatForDeleteSpec" name="listCatForDeleteSpec">
                            <option value="">Seleccione una categoría</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Especialización a eliminar:</label>
                        <select class="form-control" id="listDeleteEspecializacion" name="listDeleteEspecializacion"
                            disabled>
                            <option value="">Seleccione una especialización</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="deleteEspecializacion()">
                    <i class="fa-solid fa-ban"></i> Eliminar
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>