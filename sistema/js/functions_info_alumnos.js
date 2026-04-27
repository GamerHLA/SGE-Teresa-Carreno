/**
 * FUNCTIONS_INFO_ALUMNOS.JS
 * =========================
 * 
 * Gestión completa de la información adicional médica y personal de alumnos.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de información adicional de alumnos
 * - Formulario de dos pasos para información médica y extra
 * - Gestión de enfermedades con detalles (fecha, diagnóstico, tratamiento, restricciones)
 * - Gestión de discapacidades con detalles completos
 * - Gestión de vacunas aplicadas al alumno
 * - Información de contacto de emergencia con validaciones
 * - Autocompletado de contacto de emergencia desde representantes
 * - Atención médica especializada (Médico, Psicólogo, Neurólogo, Psicopedagogo)
 * - Validación de fechas de diagnóstico posteriores a fecha de nacimiento
 * - Talla de uniforme y actividades extracurriculares
 * - Grupo sanguíneo y observaciones generales
 * - Creación dinámica de nuevas opciones (enfermedades, discapacidades, vacunas)
 * - Inhabilitación de opciones médicas
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 * - SweetAlert
 * - Bootstrap Modals
 */

// Parche para permitir foco en SweetAlert dentro de Modales de Bootstrap
if ($.fn.modal) {
    $.fn.modal.Constructor.prototype._enforceFocus = function () { };
}

var tableInfoAlumnos = null;
window.listEnfermedades = [];
window.listDiscapacidades = [];
window.listVacunas = []; // Still used for display
window.listVacunasIds = []; // NEW: to store IDs

document.addEventListener('DOMContentLoaded', function () {
    var tableInfoAlumnosElement = document.getElementById('tableInfoAlumnos');

    // STARTUP
    if (tableInfoAlumnosElement) {
        var seccionInfo = document.getElementById('seccionInfoAlumnos');
        if (!seccionInfo || seccionInfo.style.display !== 'none') {
            initializeInfoTable();
        }
    }
    loadMedicalOptions();
    setupNewOptionButtons();
    initParentescoEmergencyManagement(); // NUEVO

    // Validacion inmediata de fechas
    function validateDateInput(inputId) {
        let input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('change', function () {
            let fecha = this.value;
            let fNac = document.getElementById('fechaNacimientoInfo').value;

            if (fecha && fNac) {
                if (fecha <= fNac) {
                    swal("Atención", `La fecha de diagnóstico debe ser posterior a la fecha de nacimiento (${fNac})`, "warning");
                    this.value = ''; // Limpiar campo
                }
            }
        });
    }

    validateDateInput('enfFecha');
    validateDateInput('discFecha');

    // MAIN TABLE LISTENER for "Información Adicional" button
    if ($('#tableAlumnos').length > 0) {
        $('#tableAlumnos tbody').on('click', '.btnEditInfoAlumno', function () {
            var id = $(this).attr('rl');
            openModalInfoAlumno(id);
        });
    }

    // EVENT LISTENERS
    setupToggle('poseeEnfermedadSi', 'poseeEnfermedadNo', 'divEnfermedades');
    setupToggle('poseeDiscapacidadSi', 'poseeDiscapacidadNo', 'divDiscapacidades');
    setupToggle('atencionSi', 'atencionNo', 'divAtencionDetalles');

    setupInlineForm('btnAddEnfermedadShow', 'formEnfermedad', 'btnCancelEnfermedad');
    setupInlineForm('btnAddDiscapacidadShow', 'formDiscapacidad', 'btnCancelDiscapacidad');



    ['checkMedico', 'checkPsico', 'checkNeuro', 'checkPsicoped'].forEach(id => {
        setupDocToggle(id, id.replace('check', 'info'));
    });

    const selPar = document.getElementById('emergenciaParentesco');
    if (selPar) selPar.addEventListener('change', function () {
        let val = this.value;
        let txt = this.options[this.selectedIndex].text;
        let oth = document.getElementById('otrosParentescoGroup');
        let inp = document.getElementById('otrosParentesco');
        if (txt === 'Otro' || txt === 'Otros' || val === 'Otros') {
            oth.style.display = 'block';
            inp.required = true;
        } else {
            oth.style.display = 'none';
            inp.required = false;
        }
        autocompleteEmergencyByRel(val); // Trigger autocomplete
        validateEmergency();
    });

    // Lógica de limpieza al cambiar parentesco (Mismo que representantes)
    // Se insertará abajo en initParentescoEmergencyManagement



    function autocompleteEmergencyByRel(relId) {
        const emName = document.getElementById('emergenciaNombre');
        const emTel = document.getElementById('emergenciaTelefono');

        if (!relId || !window.currentStudentRepresentatives) return;

        // Search for a match in current representatives by parentesco_id
        const match = window.currentStudentRepresentatives.find(r => r.parentesco_id == relId);

        if (match) {
            emName.value = match.nombre + ' ' + match.apellido;
            emTel.value = match.telefono;
            emName.classList.remove('is-invalid');
            emTel.classList.remove('is-invalid');
        }
    }

    function autocompleteEmergencyByName(nameValue) {
        const emTel = document.getElementById('emergenciaTelefono');
        const emPar = document.getElementById('emergenciaParentesco');
        const emOth = document.getElementById('otrosParentesco');
        const emOthGrp = document.getElementById('otrosParentescoGroup');

        if (!nameValue || !window.currentStudentRepresentatives) return;

        const match = window.currentStudentRepresentatives.find(r =>
            (r.nombre + ' ' + r.apellido).toLowerCase().trim() === nameValue.toLowerCase().trim()
        );

        if (match) {
            emTel.value = match.telefono || '';
            if (match.parentesco_id) {
                emPar.value = match.parentesco_id;
                emOthGrp.style.display = 'none';
                emOth.value = '';
            }
            validateEmergency();
        }
    }

    document.getElementById('btnContinuarPaso2').addEventListener('click', function () {
        // Validation: If "Si" is checked, verify list is not empty
        if (document.getElementById('poseeEnfermedadSi').checked && window.listEnfermedades.length === 0) {
            swal("Atención", "Ha indicado que posee enfermedad. Por favor registre al menos una.", "warning");
            return;
        }
        if (document.getElementById('poseeDiscapacidadSi').checked && window.listDiscapacidades.length === 0) {
            swal("Atención", "Ha indicado que posee discapacidad. Por favor registre al menos una.", "warning");
            return;
        }

        if (document.getElementById('atencionSi').checked) {
            if (!document.getElementById('checkMedico').checked &&
                !document.getElementById('checkPsico').checked &&
                !document.getElementById('checkNeuro').checked &&
                !document.getElementById('checkPsicoped').checked) {
                swal("Atención", "Ha indicado que está bajo atención médica. Por favor seleccione al menos un tipo.", "warning");
                return;
            }

            if (!validateDoc('checkMedico', 'docMedicoNombre', 'docMedicoTelf', 'Médico')) return;
            if (!validateDoc('checkPsico', 'docPsicoNombre', 'docPsicoTelf', 'Psicólogo')) return;
            if (!validateDoc('checkNeuro', 'docNeuroNombre', 'docNeuroTelf', 'Neurológo')) return;
            if (!validateDoc('checkPsicoped', 'docPsicopedNombre', 'docPsicopedTelf', 'Psicopedagogo')) return;
        }

        switchStep(2);
    });
    document.getElementById('btnVolverPaso1').addEventListener('click', function () { switchStep(1); });

    // LIST MANAGEMENT (Input Logic)
    var btnAddEnf = document.getElementById('btnAddEnfermedad');
    if (btnAddEnf) {
        btnAddEnf.addEventListener('click', function () {
            let sel = document.getElementById('enfNombre');
            let idRef = sel.value;
            let nombre = sel.options[sel.selectedIndex].text;

            let fecha = document.getElementById('enfFecha').value.trim();
            let diag = document.getElementById('enfDiagnostico').value.trim();
            let trat = document.getElementById('enfTratamiento').value.trim();
            let rest = document.getElementById('enfRestricciones').value.trim();



            if (!idRef) return swal("Atención", "Seleccione la enfermedad", "warning");
            if (!fecha) return swal("Atención", "Ingrese la fecha de diagnóstico", "warning");

            if (!diag) return swal("Atención", "Ingrese el detalle del diagnóstico", "warning");
            if (!trat) return swal("Atención", "Ingrese el tratamiento", "warning");
            if (!rest) return swal("Atención", "Ingrese las restricciones", "warning");
            if (!rest) return swal("Atención", "Ingrese las restricciones", "warning");

            // Validar fecha nacimiento
            let fNac = document.getElementById('fechaNacimientoInfo').value;
            if (fNac) {
                let dNac = new Date(fNac);
                let dDiag = new Date(fecha);
                // Sumar 1 día a la fecha de nacimiento para la comparación (dNac + 1 día <= dDiag)
                // Es decir, dDiag debe ser MAYOR a dNac. 
                // El usuario pide "minimo del 02/01/2020 en adelante (con un dia de adelanto)" si nació el 01/01/2020.
                // O sea dDiag > dNac.

                // Ajustamos timezone offset para evitar errores por huso horario al crear Date desde string YYYY-MM-DD
                // Una forma segura es comparar timestamps o usar strings.
                // YYYY-MM-DD orden ascii funciona.
                if (fecha <= fNac) {
                    return swal("Atención", `La fecha de diagnóstico debe ser posterior a la fecha de nacimiento (${fNac})`, "warning");
                }
            }
            window.listEnfermedades.push({
                tipo: 'enfermedad', id_ref: idRef, nombre: nombre,
                fecha: fecha, diagnostico: diag, tratamiento: trat, restricciones: rest, alergias: ''
            });
            renderEnfermedades();
            document.getElementById('formEnfermedad').style.display = 'none';
            document.getElementById('btnAddEnfermedadShow').style.display = 'inline-block';
        });

        document.getElementById('btnAddDiscapacidad')?.addEventListener('click', function () {
            let sel = document.getElementById('discNombre');
            let idRef = sel.value;
            let nombre = sel.options[sel.selectedIndex].text;

            let fecha = document.getElementById('discFecha').value.trim();
            let diag = document.getElementById('discDiagnostico').value.trim();
            let trat = document.getElementById('discTratamiento').value.trim();
            let rest = document.getElementById('discRestricciones').value.trim();

            if (!idRef) return swal("Atención", "Seleccione la discapacidad", "warning");
            if (!fecha) return swal("Atención", "Ingrese la fecha de diagnóstico", "warning");
            if (!diag) return swal("Atención", "Ingrese el detalle del diagnóstico", "warning");
            if (!trat) return swal("Atención", "Ingrese el tratamiento", "warning");
            if (!rest) return swal("Atención", "Ingrese las restricciones", "warning");
            if (!rest) return swal("Atención", "Ingrese las restricciones", "warning");

            // Validar fecha nacimiento
            let fNac = document.getElementById('fechaNacimientoInfo').value;
            if (fNac) {
                if (fecha <= fNac) {
                    return swal("Atención", `La fecha de diagnóstico debe ser posterior a la fecha de nacimiento (${fNac})`, "warning");
                }
            }
            window.listDiscapacidades.push({
                tipo: 'discapacidad', id_ref: idRef, nombre: nombre,
                fecha: fecha, diagnostico: diag, tratamiento: trat, restricciones: rest, alergias: ''
            });
            renderDiscapacidades();
            document.getElementById('formDiscapacidad').style.display = 'none';
            document.getElementById('btnAddDiscapacidadShow').style.display = 'inline-block';
        });
    }

    var btnAddVac = document.getElementById('btnAddVacuna');
    if (btnAddVac) {
        btnAddVac.addEventListener('click', function () {
            let sel = document.getElementById('vacunaSelect');
            let id = sel.value;
            let txt = sel.options[sel.selectedIndex].text;
            if (id && !window.listVacunasIds.includes(id)) {
                window.listVacunasIds.push(id);
                window.listVacunas.push(txt);
                renderVacunas();
            }
            sel.value = "";
        });
    }

    // Validations Initialization
    const emName = document.getElementById('emergenciaNombre');
    const emTel = document.getElementById('emergenciaTelefono');
    const emPar = document.getElementById('emergenciaParentesco');
    const emOth = document.getElementById('otrosParentesco');

    if (emName) {
        emName.addEventListener('input', function () {
            // Only allow letters and spaces
            this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '');
            autocompleteEmergencyByName(this.value);
            validateEmergency();
        });
    }
    if (emTel) emTel.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        validateEmergency();
    });
    if (emPar) emPar.addEventListener('change', validateEmergency);
    if (emOth) emOth.addEventListener('input', validateEmergency);

    const docNames = ['docMedicoNombre', 'docPsicoNombre', 'docNeuroNombre', 'docPsicopedNombre'];
    const docTels = ['docMedicoTelf', 'docPsicoTelf', 'docNeuroTelf', 'docPsicopedTelf'];

    docNames.forEach(id => {
        let el = document.getElementById(id);
        if (el) el.addEventListener('input', function () { this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, ''); });
    });
    docTels.forEach(id => {
        let el = document.getElementById(id);
        if (el) el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });
    });

    // FORM SUBMIT
    const form = document.querySelector("#formInfoAlumno");
    if (form) {
        form.onsubmit = function (e) {
            e.preventDefault();

            // 1. Emergency Check
            let vName = emName.value.trim();
            let vTel = emTel.value.trim();
            let vPar = emPar.value;
            if (vPar === 'Otros' && !emOth.value.trim()) vPar = '';
            if ((vName || vTel || vPar) && (!vName || !vTel || !vPar)) {
                swal("Error", "Debe completar todos los datos de emergencia", "error");
                validateEmergency(); return;
            }

            // 2. Doctor Validation
            if (document.getElementById('atencionSi').checked) {
                if (!validateDoc('checkMedico', 'docMedicoNombre', 'docMedicoTelf', 'Médico')) return;
                if (!validateDoc('checkPsico', 'docPsicoNombre', 'docPsicoTelf', 'Psicólogo')) return;
                if (!validateDoc('checkNeuro', 'docNeuroNombre', 'docNeuroTelf', 'Neurológo')) return;
                if (!validateDoc('checkPsicoped', 'docPsicopedNombre', 'docPsicopedTelf', 'Psicopedagogo')) return;
            }

            // Persistence
            let finalEnf = document.getElementById('poseeEnfermedadSi').checked ? window.listEnfermedades : [];
            let finalDisc = document.getElementById('poseeDiscapacidadSi').checked ? window.listDiscapacidades : [];

            document.getElementById('jsonEnfermedades').value = JSON.stringify(finalEnf);
            document.getElementById('jsonDiscapacidades').value = JSON.stringify(finalDisc);
            document.getElementById('vacunasIds').value = JSON.stringify(window.listVacunasIds);
            document.getElementById('vacunasInfo').value = window.listVacunas.join(', ');

            var request = new XMLHttpRequest();
            var formData = new FormData(form);
            request.open("POST", './models/info_alumnos/ajax_info_alumnos.php', true);
            request.send(formData);
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    try {
                        var obj = JSON.parse(request.responseText);
                        if (obj.status) {
                            $('#modalInfoAlumno').modal('hide');
                            form.reset();
                            if (tableInfoAlumnos) tableInfoAlumnos.ajax.reload();
                            swal('Éxito', obj.msg, 'success');
                        } else {
                            swal('Error', obj.msg, 'error');
                        }
                    } catch (e) { console.error(e); }
                }
            }
        }
    }
});

// Helper to format date YYYY-MM-DD to DD/MM/YYYY
function formatDateToES(dateStr) {
    if (!dateStr || dateStr.includes('/')) return dateStr;
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

// GLOBAL FUNCTIONS
function setupNewOptionButtons() {
    setupAddButton('btnNewEnfermedad', 'add_disease', 'enfNombre', 'Enfermedad');
    setupAddButton('btnNewDiscapacidad', 'add_disability', 'discNombre', 'Discapacidad');
    setupAddButton('btnNewVacuna', 'add_vaccine', 'vacunaSelect', 'Vacuna');

    setupDeleteButton('btnDelEnfermedad', 'delete_disease', 'enfNombre', 'Enfermedad');
    setupDeleteButton('btnDelDiscapacidad', 'delete_disability', 'discNombre', 'Discapacidad');
    setupDeleteButton('btnDelVacuna', 'delete_vaccine', 'vacunaSelect', 'Vacuna');
}

function setupDeleteButton(btnId, action, selectId, label) {
    let btn = document.getElementById(btnId);
    if (!btn) return;
    btn.addEventListener('click', function () {
        let sel = document.getElementById(selectId);
        let idVal = sel.value;
        let txt = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
        if (!idVal) { swal("Atención", "Seleccione una " + label + " para inhabilitar.", "warning"); return; }
        swal({
            title: "¿Inhabilitar " + label + "?",
            text: "Se inhabilitará: " + txt,
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, inhabilitar",
            cancelButtonText: "Cancelar",
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, function () {
            let req = new XMLHttpRequest();
            let fd = new FormData();
            fd.append('action', action);
            fd.append('id', idVal);
            req.open("POST", './models/info_alumnos/ajax_info_alumnos.php', true);
            req.send(fd);
            req.onreadystatechange = function () {
                if (req.readyState == 4) {
                    if (req.status == 200) {
                        try {
                            let res = JSON.parse(req.responseText);
                            if (res.status) {
                                swal("¡Inhabilitado!", res.msg, "success");
                                loadMedicalOptions(); // Refresh lists
                            } else {
                                swal("Error", res.msg, "error");
                            }
                        } catch (e) {
                            swal("Error", "Error al procesar respuesta del servidor.", "error");
                        }
                    } else {
                        swal("Error", "Error de conexión.", "error");
                    }
                }
            };
        });
    });
}

function setupAddButton(btnId, action, selectId, label) {
    let btn = document.getElementById(btnId);
    if (!btn) return;
    btn.addEventListener('click', function () {
        swal({
            title: "Nueva " + label,
            text: "Ingrese el nombre de la nueva " + label + ":",
            type: "input",
            showCancelButton: true,
            closeOnConfirm: false,
            inputPlaceholder: "Escriba el nombre..."
        }, function (inputValue) {
            if (inputValue === false) return false;
            if (inputValue === "") { swal.showInputError("Debe escribir un nombre"); return false; }

            let req = new XMLHttpRequest();
            let fd = new FormData();
            fd.append('action', action);
            fd.append('nombre', inputValue);
            req.open("POST", './models/info_alumnos/ajax_info_alumnos.php', true);
            req.send(fd);

            req.onreadystatechange = function () {
                if (req.readyState == 4) {
                    if (req.status == 200) {
                        try {
                            let res = JSON.parse(req.responseText);
                            if (res.status) {
                                swal("¡Guardado!", "Se agregó correctamente: " + res.nombre, "success");
                                let sel = document.getElementById(selectId);
                                if (sel) {
                                    addOptionSorted(sel, res.data ? res.data.id : res.id, res.data ? res.data.nombre : res.nombre);
                                }
                            } else {
                                // Show Debug Info if available
                                let debugMsg = res.msg;
                                if (res.debug_action) debugMsg += "\nAction: " + res.debug_action;
                                if (res.debug_hex) debugMsg += "\nHex: " + res.debug_hex;
                                if (res.debug_post) debugMsg += "\nPost: " + JSON.stringify(res.debug_post);
                                swal("Error", debugMsg, "error");
                            }
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            console.log('Raw response:', req.responseText);
                            var preview = req.responseText.substring(0, 500).replace(/</g, "&lt;");
                            swal({
                                title: "Error al Agregar",
                                text: "Respuesta ilegible del servidor: " + preview,
                                type: "error",
                                html: true
                            });
                        }
                    } else {
                        swal("Error", "Error de conexión: " + req.status, "error");
                    }
                }
            };
        });
    });
}

/**
 * Helper to add option sorted alphabetically
 */
function addOptionSorted(selectElement, value, text) {
    let opt = document.createElement('option');
    opt.value = value;
    opt.text = text;

    // Find the correct position
    let options = Array.from(selectElement.options);
    // Skip the first option usually "Seleccione" if it has empty value
    let startIndex = (options.length > 0 && options[0].value === "") ? 1 : 0;

    let inserted = false;
    for (let i = startIndex; i < options.length; i++) {
        if (text.localeCompare(options[i].text, 'es', { sensitivity: 'base' }) < 0) {
            selectElement.add(opt, i);
            inserted = true;
            break;
        }
    }

    if (!inserted) {
        selectElement.add(opt);
    }

    selectElement.value = value;
}

function initializeInfoTable() {
    if (tableInfoAlumnos) return tableInfoAlumnos;
    tableInfoAlumnos = $('#tableInfoAlumnos').DataTable({
        "aProcessing": true, "aServerSide": true,
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
        "ajax": { "url": "./models/info_alumnos/table_info_alumnos.php", "dataSrc": "" },
        "columns": [
            { "data": "numero_registro" },
            { "data": "nombre_completo", "width": "15%", "render": function (d) { return '<div style="word-wrap:break-word;">' + d + '</div>'; } },
            { "data": "info_medica_adicional", "width": "20%", "render": function (d) { return '<div style="font-size: 1.1em; white-space: normal;">' + d + '</div>'; } },
            { "data": "enfermedades", "width": "20%", "render": function (d) { return '<div style="font-size: 1.1em; white-space: normal;">' + d + '</div>'; } },
            { "data": "talla_uniforme", "width": "10%" },
            { "data": "actividad_extra", "width": "15%", "render": function (d) { return '<div style="white-space: normal; min-width: 150px;">' + d + '</div>'; } },
            {
                "data": "observaciones", "width": "20%", "render": function (d) {
                    if (!d) return '<div style="white-space: normal; min-width: 150px;"><span class="text-muted">Sin observaciones</span></div>';

                    // Parse format: tipo|fecha|texto###tipo|fecha|texto
                    const obs = d.split('###');
                    let html = '<div style="white-space: normal; min-width: 150px;">';

                    obs.forEach(function (item) {
                        const parts = item.split('|');
                        if (parts.length === 3) {
                            const tipo = parts[0];
                            const fecha = parts[1];
                            let texto = '';
                            try {
                                texto = decodeURIComponent(escape(window.atob(parts[2])));
                            } catch (e) {
                                texto = parts[2]; // Fallback
                            }

                            // Color mapping for label only
                            let bgColor = '#6c757d';
                            let textColor = '#fff';
                            let label = 'General';

                            if (tipo === 'inhabilitacion') {
                                bgColor = '#ffc107';
                                textColor = '#000';
                                label = 'Inhabilitación';
                            } else if (tipo === 'reactivacion') {
                                bgColor = '#28a745';
                                label = 'Reactivación';
                            } else if (tipo === 'retiro') {
                                bgColor = '#dc3545';
                                label = 'Retiro de Institución';
                            } else if (tipo === 'motivo_retiro') {
                                bgColor = '#dc3545';
                                label = 'Motivo de Retiro';
                            } else if (tipo === 'motivo_justificativo') {
                                bgColor = '#17a2b8';
                                label = 'Justificativo Representante';
                            }

                            html += '<div style="margin-bottom:6px;">';
                            html += '<span style="display:inline-block; padding:2px 6px; border-radius:3px; font-size:0.75em; font-weight:bold; background:' + bgColor + '; color:' + textColor + ';">' + label + '</span> ';
                            html += '<span style="font-size:0.85em; color:#666;">[' + fecha + ']</span> ';
                            html += '<span>' + texto + '</span>';
                            html += '</div>';
                        }
                    });

                    html += '</div>';
                    return html;
                }
            }
        ],
        "responsive": true, "bDestroy": true, "iDisplayLength": 10, "order": [[0, "asc"]]
    });
    return tableInfoAlumnos;
}
window.initializeInfoTable = initializeInfoTable;

function setupToggle(siId, noId, divId) {
    let si = document.getElementById(siId);
    let no = document.getElementById(noId);
    let div = document.getElementById(divId);
    if (si && no && div) {
        si.addEventListener('change', () => div.style.display = 'block');
        no.addEventListener('change', () => div.style.display = 'none');
    }
}

function setupInlineForm(btnShowId, formId, btnCancelId) {
    let btnShow = document.getElementById(btnShowId);
    let form = document.getElementById(formId);
    let btnCancel = document.getElementById(btnCancelId);
    if (btnShow && form && btnCancel) {
        btnShow.addEventListener('click', () => {
            form.classList.remove('d-none');
            form.style.display = 'block';
            btnShow.style.display = 'none';
            resetForm(formId);

            // Mostrar campos de detalle para enfermedades
            if (formId === 'formEnfermedad') {
                let divDetalle = document.getElementById('divDetalleEnfermedad');
                if (divDetalle) divDetalle.style.display = 'block';
            }
        });
        btnCancel.addEventListener('click', () => {
            form.classList.add('d-none');
            form.style.display = 'none';
            btnShow.style.display = 'inline-block';

            // Ocultar campos de detalle para enfermedades
            if (formId === 'formEnfermedad') {
                let divDetalle = document.getElementById('divDetalleEnfermedad');
                if (divDetalle) divDetalle.style.display = 'none';
            }
        });
    }
}

function resetForm(formId) {
    let form = document.getElementById(formId);
    form.querySelectorAll('input, select').forEach(i => i.value = '');
    if (formId === 'formEnfermedad') {
        if (document.getElementById('divDetalleEnfermedad')) document.getElementById('divDetalleEnfermedad').style.display = 'none';
    }
    form.querySelectorAll('.is-invalid').forEach(e => e.classList.remove('is-invalid'));
}

function setupDocToggle(chkId, divId) {
    let c = document.getElementById(chkId), d = document.getElementById(divId);
    if (c && d) c.addEventListener('change', () => {
        d.style.display = c.checked ? 'block' : 'none';
        if (c.checked) {
            d.querySelector('input').focus();
        }
    });
}

function validateEmergency() {
    const emName = document.getElementById('emergenciaNombre');
    const emTel = document.getElementById('emergenciaTelefono');
    const emPar = document.getElementById('emergenciaParentesco');
    const emOth = document.getElementById('otrosParentesco');

    let vName = emName.value.trim();
    let vTel = emTel.value.trim();
    let vPar = emPar.value;
    if (vPar === 'Otros' && !emOth.value.trim()) vPar = '';

    let any = (vName || vTel || vPar);
    let all = (vName && vTel && vPar);

    if (any && !all) {
        if (!vName) emName.classList.add('is-invalid'); else emName.classList.remove('is-invalid');
        if (!vTel) emTel.classList.add('is-invalid'); else emTel.classList.remove('is-invalid');
        if (!vPar) emPar.classList.add('is-invalid'); else emPar.classList.remove('is-invalid');
    } else {
        emName.classList.remove('is-invalid');
        emTel.classList.remove('is-invalid');
        emPar.classList.remove('is-invalid');
    }
}

function validateDoc(chkId, nameId, telId, label) {
    if (document.getElementById(chkId).checked) {
        let nm = document.getElementById(nameId).value.trim();
        let tl = document.getElementById(telId).value.trim();
        if (!nm || !tl) {
            swal("Atención", `Debe completar Nombre y Teléfono del ${label}`, "warning");
            return false;
        }
    }
    return true;
}

function switchStep(step) {
    let paso1 = document.getElementById('paso1InfoMedica');
    let paso2 = document.getElementById('paso2InfoExtra');

    if (step === 1) {
        if (paso1) {
            paso1.setAttribute('style', 'display: block !important;');
            paso1.classList.remove('d-none');
        }
        if (paso2) {
            paso2.setAttribute('style', 'display: none !important;');
            paso2.classList.add('d-none');
        }
    } else {
        if (paso1) {
            paso1.setAttribute('style', 'display: none !important;');
            paso1.classList.add('d-none');
        }
        if (paso2) {
            paso2.setAttribute('style', 'display: block !important;');
            paso2.classList.remove('d-none');
        }
    }

    // Scroll to top of modal
    var modalBody = document.querySelector('#modalInfoAlumno .modal-body');
    if (modalBody) modalBody.scrollTop = 0;
}

function renderEnfermedades() {
    let tbody = document.getElementById('listEnfermedades');
    tbody.innerHTML = '';
    window.listEnfermedades.forEach((item, idx) => {
        tbody.innerHTML += `<tr>
            <td>${item.nombre}</td>
            <td>${formatDateToES(item.fecha)}</td>
            <td>${item.diagnostico}</td>
            <td>${item.tratamiento}</td>
            <td>${item.restricciones}</td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="remEnf(${idx})"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    });
}
window.remEnf = function (idx) { window.listEnfermedades.splice(idx, 1); renderEnfermedades(); }

function renderDiscapacidades() {
    let tbody = document.getElementById('listDiscapacidades');
    tbody.innerHTML = '';
    window.listDiscapacidades.forEach((item, idx) => {
        if (!item.nombre || item.nombre === 'null') return;
        tbody.innerHTML += `<tr>
            <td>${item.nombre}</td>
            <td>${formatDateToES(item.fecha)}</td>
            <td>${item.diagnostico}</td>
            <td>${item.tratamiento}</td>
            <td>${item.restricciones}</td>
            <td><button type="button" class="btn btn-sm btn-info" onclick="remDisc(${idx})"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    });
}
window.remDisc = function (idx) { window.listDiscapacidades.splice(idx, 1); renderDiscapacidades(); }

function renderVacunas() {
    let div = document.getElementById('divListaVacunas');
    div.innerHTML = '';
    window.listVacunas.forEach((v, idx) => {
        if (!v) return;
        div.innerHTML += `<span class="badge badge-primary p-2 mr-1 mb-1">${v} <i class="fas fa-times ml-1" style="cursor:pointer" onclick="remVac(${idx})"></i></span>`;
    });
}
window.remVac = function (idx) {
    window.listVacunas.splice(idx, 1);
    window.listVacunasIds.splice(idx, 1);
    renderVacunas();
}

function loadMedicalOptions() {
    var req = new XMLHttpRequest();
    req.open("POST", './models/info_alumnos/ajax_info_alumnos.php', true);
    var fd = new FormData(); fd.append('action', 'get_medical_options');
    req.send(fd);
    req.onreadystatechange = function () {
        if (req.readyState == 4 && req.status == 200) {
            try {
                var d = JSON.parse(req.responseText);
                if (d.status) {
                    fillSelect('enfNombre', d.enfermedades);
                    fillSelect('discNombre', d.discapacidades);
                    fillSelect('vacunaSelect', d.vacunas);
                    fillSelect('grupoSanguineo', d.gruposSanguineos);
                    fillSelect('emergenciaParentesco', d.parentescos);
                }
            } catch (e) { }
        }
    }
}

function fillSelect(id, arr) {
    let s = document.getElementById(id);
    if (!s) return;

    // Capture current value
    let currentValue = s.value;

    s.innerHTML = '<option value="">Seleccione</option>';
    let valueExists = false;

    arr.forEach(i => {
        let o = document.createElement('option');
        o.value = i.id;
        o.text = i.nombre;
        s.appendChild(o);

        // Check if the current value matches this option
        if (currentValue && i.id == currentValue) {
            valueExists = true;
        }
    });

    // Restore value if it still exists in the new list
    if (valueExists) {
        s.value = currentValue;
    }
}


function openModalInfoAlumno(id) {
    document.getElementById('titleModal').innerHTML = "Información del Alumno";

    // Reset View Logic to Step 1 - Force display with !important
    let paso1 = document.getElementById('paso1InfoMedica');
    let paso2 = document.getElementById('paso2InfoExtra');
    if (paso1) {
        paso1.setAttribute('style', 'display: block !important;');
        paso1.classList.remove('d-none');
    }
    if (paso2) {
        paso2.setAttribute('style', 'display: none !important;');
        paso2.classList.add('d-none');
    }

    // Reset Forms
    var mainForm = document.querySelector("#formInfoAlumno");
    if (mainForm) mainForm.reset();

    // AJAX Call
    var request = new XMLHttpRequest();
    request.open("POST", './models/info_alumnos/ajax_info_alumnos.php', true);
    var formData = new FormData();
    formData.append("action", "get_alumno");
    formData.append("id", id);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    var obj = JSON.parse(request.responseText);
                    if (obj.status) {
                        var d = obj.data;
                        document.getElementById('idAlumnoInfo').value = d.alumno_id;
                        document.getElementById('fechaNacimientoInfo').value = d.fecha_nac;
                        document.getElementById('nombreApellidoInfo').value = d.nombre + ' ' + d.apellido;
                        document.getElementById('nombreApellidoInfo2').value = d.nombre + ' ' + d.apellido;

                        document.getElementById('tallaCamisaInfo').value = d.talla_camisa;
                        document.getElementById('tallaPantalonInfo').value = d.talla_pantalon;
                        document.getElementById('actividadExtraInfo').value = d.actividad_extra;

                        // Restore Lists (Conditions)
                        window.listEnfermedades = [];
                        window.listDiscapacidades = [];

                        if (d.condiciones) {
                            d.condiciones.forEach(c => {
                                if (c.enfermedad_id) {
                                    window.listEnfermedades.push({
                                        tipo: 'enfermedad', id_ref: c.enfermedad_id, nombre: c.nombre_enfermedad,
                                        fecha: c.fecha_diagnostico, diagnostico: c.diagnostico, tratamiento: c.tratamiento, restricciones: c.restricciones, alergias: c.alergias
                                    });
                                }
                                if (c.discapacidad_id) {
                                    window.listDiscapacidades.push({
                                        tipo: 'discapacidad', id_ref: c.discapacidad_id, nombre: c.nombre_discapacidad,
                                        fecha: c.fecha_diagnostico, diagnostico: c.diagnostico, tratamiento: c.tratamiento, restricciones: c.restricciones, alergias: ''
                                    });
                                }
                                // Set Blood Group (from any row)
                                if (c.grupo_sanguineo_id) document.getElementById('grupoSanguineo').value = c.grupo_sanguineo_id;
                            });
                        }
                        renderEnfermedades();
                        renderDiscapacidades();

                        // Restore Vaccines
                        window.listVacunas = [];
                        window.listVacunasIds = [];
                        if (d.vacunas_list) {
                            d.vacunas_list.forEach(v => {
                                window.listVacunasIds.push(v.id);
                                window.listVacunas.push(v.nombre);
                            });
                        }
                        renderVacunas();

                        // Global Toggles
                        if (window.listEnfermedades.length > 0) {
                            document.getElementById('poseeEnfermedadSi').checked = true;
                            document.getElementById('divEnfermedades').style.display = 'block';
                        } else {
                            document.getElementById('poseeEnfermedadNo').checked = true;
                            document.getElementById('divEnfermedades').style.display = 'none';
                        }

                        if (window.listDiscapacidades.length > 0) {
                            document.getElementById('poseeDiscapacidadSi').checked = true;
                            document.getElementById('divDiscapacidades').style.display = 'block';
                        } else {
                            document.getElementById('poseeDiscapacidadNo').checked = true;
                            document.getElementById('divDiscapacidades').style.display = 'none';
                        }

                        // Medical Attention
                        if (d.atencion_medica && d.atencion_medica.length > 0) {
                            document.getElementById('atencionSi').checked = true;
                            document.getElementById('divAtencionDetalles').style.display = 'block';

                            d.atencion_medica.forEach(att => {
                                if (att.tipo_atencion === 'Médico') {
                                    document.getElementById('checkMedico').checked = true;
                                    document.getElementById('infoMedico').style.display = 'block';
                                    document.getElementById('docMedicoNombre').value = att.nombre_doctor;
                                    document.getElementById('docMedicoTelf').value = att.telefono;
                                }
                                if (att.tipo_atencion === 'Psicológico') {
                                    document.getElementById('checkPsico').checked = true;
                                    document.getElementById('infoPsico').style.display = 'block';
                                    document.getElementById('docPsicoNombre').value = att.nombre_doctor;
                                    document.getElementById('docPsicoTelf').value = att.telefono;
                                }
                                if (att.tipo_atencion === 'Neurológico') {
                                    document.getElementById('checkNeuro').checked = true;
                                    document.getElementById('infoNeuro').style.display = 'block';
                                    document.getElementById('docNeuroNombre').value = att.nombre_doctor;
                                    document.getElementById('docNeuroTelf').value = att.telefono;
                                }
                                if (att.tipo_atencion === 'Psicopedagógico') {
                                    document.getElementById('checkPsicoped').checked = true;
                                    document.getElementById('infoPsicoped').style.display = 'block';
                                    document.getElementById('docPsicopedNombre').value = att.nombre_doctor;
                                    document.getElementById('docPsicopedTelf').value = att.telefono;
                                }
                            });
                        } else {
                            document.getElementById('atencionNo').checked = true;
                            document.getElementById('divAtencionDetalles').style.display = 'none';
                            // Uncheck all
                            ['checkMedico', 'checkPsico', 'checkNeuro', 'checkPsicoped'].forEach(k => {
                                let el = document.getElementById(k);
                                if (el) { el.checked = false; el.dispatchEvent(new Event('change')); }
                            });
                        }

                        // Emergency
                        if (d.emergencia_contacto_nombre) {
                            document.getElementById('emergenciaNombre').value = d.emergencia_contacto_nombre;
                            document.getElementById('emergenciaTelefono').value = d.emergencia_contacto_telefono;
                            document.getElementById('emergenciaParentesco').value = d.emergencia_contacto_parentesco_id || '';
                        }

                        // IMPORTANT: Store representatives for parentesco-based autocomplete
                        window.currentStudentRepresentatives = d.representantes || [];
                        let dl = document.getElementById('listRepsEmergencia');
                        if (dl) {
                            dl.innerHTML = '';
                            window.currentStudentRepresentatives.forEach(r => {
                                let opt = document.createElement('option');
                                opt.value = r.nombre + ' ' + r.apellido;
                                dl.appendChild(opt);
                            });
                        }

                        $('#modalInfoAlumno').modal('show');

                    } else {
                        swal("Error", obj.msg, "error");
                    }
                } catch (e) { console.error(e); }
            } else {
                swal('Error', 'Error de conexión: ' + request.status, 'error');
            }
        }
    }
}

// Helper functions (setDoc, etc)
function setDoc(cid, did, nid, tid, chk, nv, tv) {
    let c = document.getElementById(cid), d = document.getElementById(did);
    if (c && d) {
        c.checked = (chk == 1); d.style.display = (chk == 1) ? 'block' : 'none';
        document.getElementById(nid).value = nv || ''; document.getElementById(tid).value = tv || '';
    }
}

// NUEVA FUNCIÓN: Gestión de parentesco en emergencia
function initParentescoEmergencyManagement() {
    const sel = document.getElementById('emergenciaParentesco');
    const btnNew = document.getElementById('btnNewParentescoEmergencia');
    const btnDel = document.getElementById('btnDelParentescoEmergencia');

    if (btnNew) {
        btnNew.addEventListener('click', function () {
            swal({
                title: "Nuevo Parentesco",
                text: "Ingrese el nombre del nuevo parentesco:",
                type: "input",
                showCancelButton: true,
                closeOnConfirm: false,
                inputPlaceholder: "Escriba aquí..."
            }, function (inputValue) {
                if (inputValue === false) return false;
                if (inputValue === "") { swal.showInputError("Debe escribir un nombre"); return false; }

                let fd = new FormData();
                fd.append('action', 'addParentesco');
                fd.append('nombre', inputValue);

                fetch('./models/options/ajax-options.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status) {
                            swal("¡Guardado!", res.msg, "success");
                            // Recargar parentescos en TODOS los selects de la página para consistencia
                            if (typeof loadParentescos === 'function') loadParentescos(res.id);

                            // Recargar localmente si es necesario (aunque loadParentescos debería alcanzarlo)
                            loadMedicalOptions();
                        } else {
                            swal("Error", res.msg, "error");
                        }
                    }).catch(e => swal("Error", "Error de conexión", "error"));
            });
        });
    }

    if (btnDel) {
        btnDel.addEventListener('click', function () {
            if (!sel || !sel.value) return swal("Atención", "Seleccione un parentesco para inhabilitar", "warning");

            let name = sel.options[sel.selectedIndex].text;
            swal({
                title: "¿Inhabilitar Parentesco?",
                text: "¿Está seguro que desea inhabilitar '" + name + "'?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Sí, inhabilitar",
                closeOnConfirm: false
            }, function () {
                let fd = new FormData();
                fd.append('action', 'deleteParentesco');
                fd.append('id', sel.value);

                fetch('./models/options/ajax-options.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status) {
                            swal("Inhabilitado", res.msg, "success");
                            if (typeof loadParentescos === 'function') loadParentescos();
                            loadMedicalOptions();
                        } else {
                            swal("Error", res.msg, "error");
                        }
                    }).catch(e => swal("Error", "Error de conexión", "error"));
            });
        });
    }

    // Lógica de limpieza al cambiar (Requisito Especial)
    if (sel) {
        sel.addEventListener('change', function () {
            const nom = document.getElementById('emergenciaNombre');
            const tel = document.getElementById('emergenciaTelefono');
            // Solo limpiar si ya tiene datos y el nuevo valor no es vacío
            if (nom && nom.value.trim() !== '' && this.value !== "") {
                nom.value = "";
                tel.value = "";
                nom.classList.remove('is-valid', 'is-invalid');
                tel.classList.remove('is-valid', 'is-invalid');
                // swal removed by user request
            }
        });
    }
}

// Assign to window to ensure global access
window.openModalInfoAlumno = openModalInfoAlumno;

