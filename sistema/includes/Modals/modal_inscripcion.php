<div class="modal fade" id="modalFormInscripcion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nueva Inscripción</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                <form id="formInscripcion" name="formInscripcion">
                    <input type="hidden" name="idInscripcion" id="idInscripcion" value="">
                    <div class="form-group">
                        <label for="exampleSelect1">Alumno</label>
                    <select class="form-control" name="listAlumno" id="listAlumno" style="pointer-events: none; background-color: #e9ecef;" required>
                    </select>
                    </div>

                    <!-- Inscripción Anterior (solo informativo) -->
                    <div class="form-group" id="previousEnrollmentContainer" style="display: none;">
                        <label>Inscripción Anterior</label>
                        <div class="alert alert-info" id="previousEnrollmentInfo" style="margin-bottom: 0;">
                            <!-- La información se cargará dinámicamente -->
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="txtRepresentante">Representante</label>
                                <input type="text" class="form-control" name="txtRepresentante" id="txtRepresentante" style="pointer-events: none; background-color: #e9ecef;" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="txtParentesco">Parentesco</label>
                                <input type="text" class="form-control" name="txtParentesco" id="txtParentesco" style="pointer-events: none; background-color: #e9ecef;" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="exampleSelect1">Seleccione Grado/Sección</label>
                        <select class="form-control" name="listCurso" id="listCurso" required>
                            <option value="">Seleccionar Grado/Sección</option>
                        </select>
                    </div>

                    <!-- Alerta de Repitencia (se muestra si el grado/sección es igual al anterior) -->
                    <div class="form-group" id="repetitionWarningContainer" style="display: none;">
                        <div class="alert alert-warning" style="margin-bottom: 0;">
                            <h5 class="alert-heading">¡Atención!</h5>
                            <p style="margin-bottom: 10px;">¿El alumno es repitiente?</p>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="esRepitiente" id="repitienteSi" value="SI">
                                <label class="form-check-label" for="repitienteSi">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="esRepitiente" id="repitienteNo" value="NO">
                                <label class="form-check-label" for="repitienteNo">No</label>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Curso Seleccionado -->
                    <div class="form-group" id="cursoInfoContainer" style="display: none;">
                        <label>Información del Curso</label>
                        <div class="card border-info" style="background-color: #f8f9fa;">
                            <div class="card-body" id="cursoInfoContent">
                                <!-- La información del curso se mostrará aquí -->
                            </div>
                        </div>
                    </div>

                    <!-- Campos ocultos para periodo_id y turno_id -->
                    <input type="hidden" name="listPeriodoId" id="listPeriodoId" value="">
                    <input type="hidden" name="listTurnoId" id="listTurnoId" value="">
                    <div class="form-group d-none">
                        <label for="exampleSelect1">Estado</label>
                        <select class="form-control" name="listStatus" id="listStatus" required>
                            <option value="1">Activo</option>
                            <option value="2">Inactivo</option>
                        </select>
                    </div>
                    <div class="tile-footer">
                        <button id="btnActionForm" class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i><span id="btnText">Guardar</span></button>&nbsp;&nbsp;&nbsp;
                        <button class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-lg fa-times-circle"></i>Cancelar</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>