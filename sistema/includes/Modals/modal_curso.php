<div class="modal fade" id="modalFormCurso" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nuevo Grado/Sección</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                    <form id="formCurso" name="formCurso">
                        <input type="hidden" name="idCurso" id="idCurso" value="">

                        <!-- Grado -->
                        <div class="form-group">
                            <label for="txtGrado">Grado *</label>
                            <select class="form-control" name="txtGrado" id="txtGrado" required
                                style="pointer-events: none; background-color: #e9ecef;" tabindex="-1">
                                <option value="">Seleccionar Grado</option>
                                <option value="1">1° Grado</option>
                                <option value="2">2° Grado</option>
                                <option value="3">3° Grado</option>
                                <option value="4">4° Grado</option>
                                <option value="5">5° Grado</option>
                                <option value="6">6° Grado</option>
                            </select>
                        </div>

                        <!-- Sección -->
                        <div class="form-group">
                            <label for="txtSeccion">Sección *</label>
                            <select class="form-control" name="txtSeccion" id="txtSeccion" required
                                style="pointer-events: none; background-color: #e9ecef;" tabindex="-1">
                                <option value="">Seleccionar Sección</option>
                                <option value="A">Sección A</option>
                                <option value="B">Sección B</option>
                                <option value="C">Sección C</option>
                                <option value="D">Sección D</option>

                            </select>
                        </div>

                        <!-- Periodo -->
                        <div class="form-group">
                            <label for="listPeriodo">Período Escolar *</label>
                            <select class="form-control" name="listPeriodo" id="listPeriodo"
                                style="pointer-events: none; background-color: #e9ecef;" readonly>
                                <option value="">Seleccionar Período</option>
                                <!-- CONTENIDO AJAX -->
                            </select>
                        </div>

                        <!-- Cupo -->
                        <div class="form-group">
                            <label for="txtCupo">Cupo Máximo *</label>
                            <input class="form-control" type="number" name="txtCupo" id="txtCupo"
                                placeholder="Cantidad de estudiantes" max="50" onkeypress="return (event.charCode >= 48 && event.charCode <= 57)">
                        </div>

                        <!-- Turno -->
                        <div class="form-group">
                            <label for="listTurno">Turno *</label>
                            <select class="form-control" name="listTurno" id="listTurno" required
                                style="pointer-events: none; background-color: #e9ecef;" readonly>
                                <option value="">Seleccionar Turno</option>
                                <!-- CONTENIDO AJAX -->
                            </select>
                            <small class="text-muted">El turno se asigna automáticamente</small>
                        </div>

                        <!-- Profesor -->
                        <div class="form-group">
                            <label for="listProfesor">Profesor *</label>
                            <select class="form-control" name="listProfesor" id="listProfesor">
                                <option value="">Seleccionar Profesor</option>
                                <!-- CONTENIDO AJAX -->
                            </select>
                        </div>

                        <div class="tile-footer">
                            <button id="btnActionForm" class="btn btn-primary" type="submit">
                                <i class="fa fa-fw fa-lg fa-check-circle"></i>
                                <span id="btnText">Guardar</span>
                            </button>&nbsp;&nbsp;&nbsp;
                            <button class="btn btn-secondary" data-dismiss="modal">
                                <i class="fa fa-fw fa-lg fa-times-circle"></i>Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>