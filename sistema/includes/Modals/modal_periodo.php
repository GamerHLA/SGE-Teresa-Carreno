<div class="modal fade" id="modalFormPeriodo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header headerRegister">
                <h5 class="modal-title" id="titleModal">Nuevo Periodo Escolar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tile-body">
                    <form id="formPeriodo" name="formPeriodo">
                        <input type="hidden" name="idPeriodo" id="idPeriodo" value="">

                        <div class="container">
                            <div class="periodo-container">
                                <h2 class="text-center mb-4">Periodo Escolar</h2>
                                
                                <div class="form-group">
                                    <label class="form-label fw-bold">Seleccione los años del periodo escolar</label>
                                    <div class="periodo-row">
                                        <div class="periodo-col">
                                            <label class="form-label">Año de inicio</label>
                                            <input type="number" class="form-control" id="anioInicio" name="anioInicio" 
                                                    min="2025" max="9999" value="2025" 
                                                    oninput="validarAnioPeriodo()" onkeypress="return soloNumeros(event)" required>
                                        </div>
                                        
                                        <div class="periodo-separator">-</div>
                                        
                                        <div class="periodo-col">
                                            <label class="form-label">Año de fin</label>
                                            <input type="number" class="form-control" id="anioFin" name="anioFin" 
                                                    min="2025" max="9999" value="2026" 
                                                    oninput="validarAnioFin()" onkeypress="return soloNumeros(event)" required>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted" id="periodoInfo">Periodo: 2025 - 2026</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group d-none">
                            <label for="listStatus">Estado</label>
                            <select class="form-control" name="listStatus" id="listStatus" required>
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
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