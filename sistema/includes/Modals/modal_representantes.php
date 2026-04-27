<div class="modal fade" id="modalFormRepresentantes" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nuevo Representante</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                <form id="formRepresentantes" name="formRepresentantes">
                    <input type="hidden" name="idRepresentantes" id="idRepresentantes" value="">
                    

                    
                    <!-- Nacionalidad -->
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="listNacionalidadRepresentante">Seleccione</label>
                            <select class="form-control" name="listNacionalidadRepresentante" id="listNacionalidadRepresentante" required>
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
                            <div class="invalid-feedback" id="cedula-representante-error"></div>
                            <small class="form-text text-muted">Al ingresar la cédula, se verificará automáticamente si ya existe en el sistema</small>
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
                            placeholder="Ingrese Teléfono (11 dígitos, comenzando con 0)" required>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" 
                            placeholder="Correo electrónico, ej: ejemplo@dominio.com" required>
                    </div>
                    <div class="tile-footer">
                        <button id="btnActionForm" class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i><span id="btnText">Guardar</span></button>&nbsp;&nbsp;&nbsp;
                        <button class="btn btn-secondary" type="button" data-dismiss="modal"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancelar</button>
                    </div>
                </form>
                </div>
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
        display: block !important;
    }
</style>