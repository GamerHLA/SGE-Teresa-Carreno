<div class="modal fade" id="modalFormAlumno" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nuevo Alumno</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                    <form id="formAlumno" name="formAlumno" novalidate>
                        <input type="hidden" name="idAlumno" id="idAlumno" value="">

                        <!-- DATOS DEL REPRESENTANTE 1 PRINCIPAL -->
                        <div class="form-group border-top pt-3">
                            <h6 class="text-primary"><i class="fas fa-user-tie"></i> Representante 1 Principal</h6>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="control-label">Cédula Representante <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cedulaRepresentante"
                                    name="cedulaRepresentante" placeholder="Cédula" maxlength="10" required>
                                <div class="invalid-feedback" id="cedula-representante-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Nombre Representante</label>
                                <input class="form-control" id="txtNombreRepresentante" name="txtNombreRepresentante"
                                    type="text" maxlength="100" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Parentesco <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-control" id="listParentesco" name="listParentesco" required>
                                </select>
                                <div class="input-group-append">
                                    <button id="btnAddParentesco" class="btn btn-success" type="button" title="Agregar Parentesco">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button id="btnDelParentesco" class="btn btn-danger" type="button" title="Eliminar Parentesco">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="invalid-feedback">Por favor, seleccione un parentesco.</div>
                        </div>

                        <!-- DATOS DEL REPRESENTANTE 2 (OPCIONAL) -->
                        <div class="form-group border-top pt-3">
                            <h6 class="text-primary"><i class="fas fa-user-friends"></i> Representante 2 (Opcional)</h6>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="control-label">Cédula Representante 2</label>
                                <input type="text" class="form-control" id="cedulaRepresentante2"
                                    name="cedulaRepresentante2" placeholder="Cédula (Opcional)" maxlength="10">
                                <div class="invalid-feedback" id="cedula-representante2-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Nombre Representante 2</label>
                                <input class="form-control" id="txtNombreRepresentante2" name="txtNombreRepresentante2"
                                    type="text" maxlength="100" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Parentesco 2</label>
                            <div class="input-group">
                                <select class="form-control" id="listParentesco2" name="listParentesco2">
                                </select>
                                <div class="input-group-append">
                                    <button id="btnAddParentesco2" class="btn btn-success" type="button" title="Agregar Parentesco">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button id="btnDelParentesco2" class="btn btn-danger" type="button" title="Eliminar Parentesco">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DATOS DEL ALUMNO -->
                        <div class="form-group border-top pt-3">
                            <h6 class="text-primary"><i class="fas fa-user-graduate"></i> Registro de Alumno</h6>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Posee Cédula <span class="text-danger">*</span></label>
                            <select class="form-control" name="poseeCedula" id="poseeCedula" required>
                                <option value="">Seleccione una opción</option>
                                <option value="SI">Sí</option>
                                <option value="NO">No</option>
                            </select>
                            <div class="invalid-feedback d-none" id="error-poseeCedula">Por favor, seleccione si el
                                alumno posee cédula.</div>
                        </div>

                        <!-- Cédula Escolar -->
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="control-label d-flex justify-content-between align-items-center"
                                    style="white-space: nowrap;">Nº Partos<i class="fas fa-question-circle text-info"
                                        style="cursor: pointer;" id="btnAyudaNHijos"
                                        title="Indique cuántos partos se realizaron en el año, dependiendo va ser el número para la cédula escolar"></i></label>
                                <select class="form-control" name="numeroInicialCedulaEscolar"
                                    id="numeroInicialCedulaEscolar">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            <div class="form-group col-md-10">
                                <label class="control-label">Cédula Escolar</label>
                                <input class="form-control" id="cedulaEscolar" name="cedulaEscolar" type="text"
                                    placeholder="(Se genera automáticamente si no posee cédula)" maxlength="50"
                                    readonly>
                            </div>
                        </div>

                        <!-- id_nacionalidades (Nacionalidad) del Alumno -->
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="listNacionalidadAlumno">Seleccione</label>
                                <select class="form-control" name="listNacionalidadAlumno" id="listNacionalidadAlumno"
                                    required>

                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                    <option value="P">P</option>
                                </select>
                                <div class="invalid-feedback d-none" id="error-listNacionalidadAlumno">Por favor,
                                    seleccione una nacionalidad válida.</div>
                            </div>

                            <!-- cedula del Alumno -->
                            <div class="form-group col-md-10">
                                <label class="control-label">Cédula del Alumno</label>
                                <input type="text" class="form-control" id="cedulaAlumno" name="cedulaAlumno"
                                    placeholder="(7-10 dígitos)" maxlength="10">
                                <div class="invalid-feedback" id="cedula-alumno-error"></div>
                            </div>
                        </div>

                        <!-- nombre del Alumno -->
                        <div class="form-group">
                            <label class="control-label">Nombre</label>
                            <input class="form-control" id="txtNombre" name="txtNombre" type="text"
                                placeholder="Nombre del alumno (máximo 20 caracteres)" maxlength="20">
                        </div>

                        <!-- apellido - Orden según tabla alumnos -->
                        <div class="form-group">
                            <label class="control-label">Apellido</label>
                            <input type="text" class="form-control" id="txtApellido" name="txtApellido"
                                placeholder="Apellido de alumno (máximo 20 caracteres)" maxlength="20">
                        </div>

                        <!-- fecha_nac - Orden según tabla alumnos -->
                        <div class="form-group">
                            <label class="control-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fechaNac" name="fechaNac"
                                placeholder="Fecha de Nacimiento" max="<?php echo date('Y-m-d'); ?>">
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



                        <!-- Botones -->
                        <div class="tile-footer">
                            <button id="btnActionForm" class="btn btn-primary" type="submit">
                                <i class="fa fa-fw fa-lg fa-check-circle"></i>
                                <span id="btnText">Guardar</span>
                            </button>
                            &nbsp;&nbsp;&nbsp;
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">
                                <i class="fa fa-fw fa-lg fa-times-circle"></i>Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Solo el CSS mínimo necesario para las validaciones -->
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
</style>

<!-- Script para inicializar fecha máxima -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar fecha máxima como hoy
        var fechaNac = document.getElementById('fechaNac');
        if (fechaNac) {
            fechaNac.max = new Date().toISOString().split('T')[0];
        }

    }
    });
</script>