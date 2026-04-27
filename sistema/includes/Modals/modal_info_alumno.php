<div class="modal fade" id="modalInfoAlumno" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header headerRegister p-3">
                <h5 class="modal-title" id="titleModal" style="font-size: 1.2rem;">Información del Alumno</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="tile-body">
                    <form id="formInfoAlumno" name="formInfoAlumno">
                        <input type="hidden" name="idAlumnoInfo" id="idAlumnoInfo" value="">
                        <input type="hidden" name="fechaNacimientoInfo" id="fechaNacimientoInfo" value="">

                        <!-- PASO 1: INFORMACIÓN MÉDICA -->
                        <div id="paso1InfoMedica">
                            <!-- Nombre y Apellido -->
                            <div class="form-group mb-3">
                                <label class="control-label font-weight-bold">Nombre y Apellido</label>
                                <input class="form-control" id="nombreApellidoInfo" name="nombreApellidoInfo"
                                    type="text" readonly>
                            </div>

                            <div class="mb-3">
                                <h5 class="text-primary font-weight-bold">Información Médica</h5>
                            </div>

                            <!-- SECCIÓN 1: ENFERMEDADES -->
                            <div class="card mb-3 border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="font-weight-bold mb-0">Enfermedades</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-8">
                                            <span class="font-weight-bold">¿Sufre el alumno alguna enfermedad?</span>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="form-check form-check-inline mr-3">
                                                <input class="form-check-input" type="radio" name="poseeEnfermedad"
                                                    id="poseeEnfermedadSi" value="SI">
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeEnfermedadSi">SÍ</label>
                                            </div>
                                            <div class="form-check form-check-inline mr-0">
                                                <input class="form-check-input" type="radio" name="poseeEnfermedad"
                                                    id="poseeEnfermedadNo" value="NO" checked>
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeEnfermedadNo">NO</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="divEnfermedades" style="display: none;">
                                        <div class="table-responsive mb-3">
                                            <table class="table table-bordered mb-0" id="tableEnfermedades">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="width: 25%;">Enfermedad</th>
                                                        <th style="width: 15%;">Fecha</th>
                                                        <th>Detalles</th>
                                                        <th>Tratamiento</th>
                                                        <th>Restricciones</th>
                                                        <th style="width: 50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="listEnfermedades"></tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger"
                                            id="btnAddEnfermedadShow"><i class="fas fa-plus"></i> Agregar
                                            Enfermedad</button>

                                        <!-- Formulario Agregar -->
                                        <div class="mt-3 p-3 bg-light border rounded d-none" id="formEnfermedad"
                                            style="display: none;">
                                            <div class="form-row">
                                                <div class="form-group col-md-6 mb-2">
                                                    <label class="mb-1">Enfermedad</label>
                                                    <div class="input-group">
                                                        <select class="form-control" id="enfNombre"></select>
                                                        <?php if ($_SESSION['rol'] == 1) { ?>
                                                            <button class="btn btn-success" type="button"
                                                                id="btnNewEnfermedad" title="Registrar Nueva Enfermedad"><i
                                                                    class="fas fa-plus-circle"></i></button>
                                                            <button class="btn btn-danger" type="button"
                                                                id="btnDelEnfermedad"
                                                                title="Eliminar Enfermedad Seleccionada"><i
                                                                    class="fa-solid fa-ban"></i></button>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-6 mb-2">
                                                    <label class="mb-1">Fecha Diagnóstico</label>
                                                    <input type="date" class="form-control" id="enfFecha">
                                                </div>
                                            </div>

                                            <!-- Campos de detalle ocultos por defecto -->
                                            <div id="divDetalleEnfermedad" style="display: none;">
                                                <div class="form-group mb-2">
                                                    <label class="mb-1">Detalle Diagnóstico</label>
                                                    <input type="text" class="form-control" id="enfDiagnostico">
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6 mb-2">
                                                        <label class="mb-1">Tratamiento</label>
                                                        <input type="text" class="form-control" id="enfTratamiento">
                                                    </div>
                                                    <div class="form-group col-md-6 mb-2">
                                                        <label class="mb-1">Restricciones</label>
                                                        <input type="text" class="form-control" id="enfRestricciones">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right mt-2">
                                                <button type="button" class="btn btn-secondary"
                                                    id="btnCancelEnfermedad">Cancelar</button>
                                                <button type="button" class="btn btn-danger"
                                                    id="btnAddEnfermedad">Agregar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: DISCAPACIDADES -->
                            <div class="card mb-3 border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="font-weight-bold mb-0">Discapacidades</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-8">
                                            <span class="font-weight-bold">¿Sufre el alumno alguna discapacidad?</span>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <div class="form-check form-check-inline mr-3">
                                                <input class="form-check-input" type="radio" name="poseeDiscapacidad"
                                                    id="poseeDiscapacidadSi" value="SI">
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeDiscapacidadSi">SÍ</label>
                                            </div>
                                            <div class="form-check form-check-inline mr-0">
                                                <input class="form-check-input" type="radio" name="poseeDiscapacidad"
                                                    id="poseeDiscapacidadNo" value="NO" checked>
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeDiscapacidadNo">NO</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="divDiscapacidades" style="display: none;">
                                        <div class="table-responsive mb-3">
                                            <table class="table table-bordered mb-0" id="tableDiscapacidades">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="width: 25%;">Discapacidad</th>
                                                        <th style="width: 15%;">Fecha</th>
                                                        <th>Detalles</th>
                                                        <th>Tratamiento</th>
                                                        <th>Restricciones</th>
                                                        <th style="width: 50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="listDiscapacidades"></tbody>
                                            </table>
                                        </div>
                                        <button type="button" class="btn btn-outline-info"
                                            id="btnAddDiscapacidadShow"><i class="fas fa-plus"></i> Agregar
                                            Discapacidad</button>

                                        <!-- Formulario Agregar -->
                                        <div class="mt-3 p-3 bg-light border rounded d-none" id="formDiscapacidad"
                                            style="display: none;">
                                            <div class="form-row">
                                                <div class="form-group col-md-6 mb-2">
                                                    <label class="mb-1">Discapacidad</label>
                                                    <div class="input-group">
                                                        <select class="form-control" id="discNombre"></select>
                                                        <?php if ($_SESSION['rol'] == 1) { ?>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-success" type="button"
                                                                    id="btnNewDiscapacidad"
                                                                    title="Registrar Nueva Discapacidad"><i
                                                                        class="fas fa-plus-circle"></i></button>
                                                                <button class="btn btn-danger" type="button"
                                                                    id="btnDelDiscapacidad"
                                                                    title="Eliminar Discapacidad Seleccionada"><i
                                                                        class="fa-solid fa-ban"></i></button>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-6 mb-2">
                                                    <label class="mb-1">Fecha Diagnóstico</label>
                                                    <input type="date" class="form-control" id="discFecha">
                                                </div>
                                            </div>
                                            <div class="form-group mb-2">
                                                <label class="mb-1">Detalle Diagnóstico</label>
                                                <input type="text" class="form-control" id="discDiagnostico">
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6 mb-2">
                                                    <label class="mb-1">Tratamiento</label>
                                                    <input type="text" class="form-control" id="discTratamiento">
                                                </div>
                                                <div class="form-group col-md-6 mb-2">
                                                    <label class="mb-1">Restricciones</label>
                                                    <input type="text" class="form-control" id="discRestricciones">
                                                </div>
                                            </div>
                                            <div class="text-right mt-2">
                                                <button type="button" class="btn btn-secondary"
                                                    id="btnCancelDiscapacidad">Cancelar</button>
                                                <button type="button" class="btn btn-info"
                                                    id="btnAddDiscapacidad">Agregar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 3: SANGRE Y VACUNAS -->
                            <div class="card mb-3" style="border-color: #003366;">
                                <div class="card-header text-white" style="background-color: #003366;">
                                    <h6 class="font-weight-bold mb-0">Grupo Sanguíneo y Vacunas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-4 mb-2">
                                            <label class="font-weight-bold">Grupo Sanguíneo</label>
                                            <select class="form-control" id="grupoSanguineo" name="grupoSanguineo">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-8 mb-2">
                                            <label class="font-weight-bold">Vacunas</label>
                                            <div class="input-group">
                                                <select class="form-control" id="vacunaSelect">
                                                    <option value="">Seleccione Vacuna</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <?php if ($_SESSION['rol'] == 1) { ?>
                                                        <button class="btn btn-success" type="button" id="btnNewVacuna"
                                                            title="Registrar Nueva Vacuna"><i
                                                                class="fas fa-plus-circle"></i></button>
                                                        <button class="btn btn-danger" type="button" id="btnDelVacuna"
                                                            title="Eliminar Vacuna Seleccionada"><i
                                                                class="fa-solid fa-ban"></i></button>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <!-- Lista de vacunas en forma de tags -->
                                            <div id="divListaVacunas" class="mt-2"></div>
                                            <div class="form-row mt-2">
                                                <div class="col-md-12 text-right">
                                                    <button class="btn btn-primary" type="button" id="btnAddVacuna">
                                                        <i class="fas fa-plus"></i> Agregar
                                                    </button>
                                                </div>
                                            </div>
                                            <input type="hidden" name="vacunasInfo" id="vacunasInfo">
                                            <input type="hidden" name="vacunasIds" id="vacunasIds">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 4: EMERGENCIA -->
                            <div class="card border-warning mb-3">
                                <div class="card-body bg-light">
                                    <label class="text-danger font-weight-bold mb-2" style="font-size: 1.1em;">En caso
                                        de emergencia llamar a:</label>
                                    <div class="form-row">
                                        <div class="form-group col-md-4 mb-2">
                                            <label>Nombre</label>
                                            <input class="form-control" id="emergenciaNombre" name="emergenciaNombre"
                                                type="text" list="listRepsEmergencia">
                                            <datalist id="listRepsEmergencia"></datalist>
                                            <div class="invalid-feedback">Debe llenar Nombre, Teléfono y Parentesco.
                                            </div>
                                        </div>
                                        <div class="form-group col-md-4 mb-2">
                                            <label>Teléfono</label>
                                            <input class="form-control" id="emergenciaTelefono"
                                                name="emergenciaTelefono" type="tel" maxlength="11">
                                            <div class="invalid-feedback">Solo números permitidos.</div>
                                        </div>
                                        <div class="form-group col-md-4 mb-2">
                                            <label>Parentesco</label>
                                            <div class="input-group">
                                                <select class="form-control" id="emergenciaParentesco" name="emergenciaParentesco">
                                                    <option value="">Seleccione</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <button id="btnNewParentescoEmergencia" class="btn btn-success" type="button" title="Registrar Nuevo Parentesco">
                                                        <i class="fas fa-plus-circle"></i>
                                                    </button>
                                                    <button id="btnDelParentescoEmergencia" class="btn btn-danger" type="button" title="Eliminar Parentesco Seleccionado">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0" id="otrosParentescoGroup" style="display: none;">
                                        <input class="form-control" id="otrosParentesco" name="otrosParentesco"
                                            type="text" placeholder="Especifique parentesco">
                                    </div>
                                </div>
                            </div>


                            <!-- SECCIÓN 5: ATENCIÓN MÉDICA -->
                            <div class="card mb-3" style="border-color: #003366;">
                                <div class="card-header text-white" style="background-color: #003366;">
                                    <h6 class="font-weight-bold mb-0">Atención Médica</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group row mb-2 align-items-center">
                                        <label class="col-md-8 col-form-label font-weight-bold">¿Está bajo alguna
                                            atención médica?</label>
                                        <div class="col-md-4 text-right">
                                            <div class="form-check form-check-inline mr-3">
                                                <input class="form-check-input" type="radio" name="atencionMedica"
                                                    id="atencionSi" value="SI">
                                                <label class="form-check-label font-weight-bold"
                                                    for="atencionSi">SÍ</label>
                                            </div>
                                            <div class="form-check form-check-inline mr-0">
                                                <input class="form-check-input" type="radio" name="atencionMedica"
                                                    id="atencionNo" value="NO" checked>
                                                <label class="form-check-label font-weight-bold"
                                                    for="atencionNo">NO</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="divAtencionDetalles" style="display: none;" class="mt-3 pl-2">
                                        <!-- Médico -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkMedico"
                                                        name="infMedico">
                                                    <label class="form-check-label font-weight-bold"
                                                        for="checkMedico">Médico</label>
                                                </div>
                                            </div>
                                            <div class="col-md-9" id="infoMedico" style="display: none;">
                                                <div class="form-row">
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docMedicoNombre" name="docMedicoNombre"
                                                            placeholder="Dr. Nombre"></div>
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docMedicoTelf" name="docMedicoTelf" placeholder="Telf.">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Psicológico -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkPsico"
                                                        name="infPsico">
                                                    <label class="form-check-label font-weight-bold"
                                                        for="checkPsico">Psicológico</label>
                                                </div>
                                            </div>
                                            <div class="col-md-9" id="infoPsico" style="display: none;">
                                                <div class="form-row">
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docPsicoNombre" name="docPsicoNombre"
                                                            placeholder="Dr. Nombre"></div>
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docPsicoTelf" name="docPsicoTelf" placeholder="Telf.">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Neurológico -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkNeuro"
                                                        name="infNeuro">
                                                    <label class="form-check-label font-weight-bold"
                                                        for="checkNeuro">Neurológico</label>
                                                </div>
                                            </div>
                                            <div class="col-md-9" id="infoNeuro" style="display: none;">
                                                <div class="form-row">
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docNeuroNombre" name="docNeuroNombre"
                                                            placeholder="Dr. Nombre"></div>
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docNeuroTelf" name="docNeuroTelf" placeholder="Telf.">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Psicopedagógico -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkPsicoped"
                                                        name="infPsicoPed">
                                                    <label class="form-check-label font-weight-bold"
                                                        for="checkPsicoped">Psicopedagógico.</label>
                                                </div>
                                            </div>
                                            <div class="col-md-9" id="infoPsicoped" style="display: none;">
                                                <div class="form-row">
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docPsicopedNombre" name="docPsicopedNombre"
                                                            placeholder="Dr. Nombre"></div>
                                                    <div class="col-6"><input type="text" class="form-control"
                                                            id="docPsicopedTelf" name="docPsicopedTelf"
                                                            placeholder="Telf."></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="jsonEnfermedades" id="jsonEnfermedades">
                            <input type="hidden" name="jsonDiscapacidades" id="jsonDiscapacidades">

                            <div class="text-right mt-4">
                                <button type="button" class="btn btn-primary" id="btnContinuarPaso2">
                                    Continuar <i class="fas fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- PASO 2: INFORMACIÓN EXTRA -->
                        <div id="paso2InfoExtra" class="d-none" style="display: none;">
                            <div class="form-group mb-3">
                                <label class="control-label font-weight-bold">Nombre y Apellido</label>
                                <input class="form-control" id="nombreApellidoInfo2" type="text" readonly>
                            </div>

                            <div class="mb-3">
                                <h5 class="text-primary font-weight-bold">Información Extra</h5>
                            </div>
                            <div class="card p-3 mb-3">
                                <div class="form-row">
                                    <div class="form-group col-md-6 mb-3">
                                        <label class="control-label">Talla de Camisa</label>
                                        <select class="form-control" id="tallaCamisaInfo" name="tallaCamisaInfo">
                                            <option value="">Seleccione</option>
                                            <optgroup label="Tallas Numéricas">
                                                <option value="8">8</option>
                                                <option value="10">10</option>
                                                <option value="12">12</option>
                                                <option value="14">14</option>
                                            </optgroup>
                                            <optgroup label="Tallas en Letra">
                                                <option value="S">S</option>
                                                <option value="M">M</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6 mb-3">
                                        <label class="control-label">Talla de Pantalón</label>
                                        <select class="form-control" id="tallaPantalonInfo" name="tallaPantalonInfo">
                                            <option value="">Seleccione</option>
                                            <optgroup label="Tallas Numéricas">
                                                <option value="8">8</option>
                                                <option value="10">10</option>
                                                <option value="12">12</option>
                                                <option value="14">14</option>
                                            </optgroup>
                                            <optgroup label="Tallas en Letra">
                                                <option value="S">S</option>
                                                <option value="M">M</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="control-label">Actividad extra que realiza</label>
                                    <input class="form-control" id="actividadExtraInfo" name="actividadExtraInfo"
                                        type="text" placeholder="Actividad extracurricular">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" id="btnVolverPaso1">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver a Info Médica
                                </button>
                                <div>
                                    <button id="btnActionForm" class="btn btn-primary" type="submit">
                                        <i class="fa fa-fw fa-check-circle"></i>
                                        <span id="btnText">Guardar Todo</span>
                                    </button>
                                    &nbsp;&nbsp;
                                    <button class="btn btn-secondary" type="button" data-dismiss="modal">
                                        <i class="fa fa-fw fa-times-circle"></i>Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>