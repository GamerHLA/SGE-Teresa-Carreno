/**
 * FUNCTIONS-INHABILITADOS.JS
 * ===========================
 * 
 * Gestión del módulo de elementos inhabilitados del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - Dashboard de elementos inhabilitados del sistema
 * - Visualización de vacunas, enfermedades, discapacidades inhabilitadas
 * - Visualización de parentescos inhabilitados
 * - Visualización de motivos de retiro y asistencia inhabilitados
 * - Visualización de niveles educativos y especializaciones inhabilitadas
 * - Reactivación de elementos inhabilitados
 * - Interfaz organizada por categorías
 * - Mensajes informativos cuando no hay elementos inhabilitados
 * 
 * DEPENDENCIAS:
 * - SweetAlert
 * - Fetch API
 */

document.addEventListener('DOMContentLoaded', function () {
    loadDisabledItems();
});

function loadDisabledItems() {
    fetch('models/ajax_inhabilitados.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }

            // Render sections
            renderSimpleList('container-vacunas', data.vacunas, 'No hay vacunas inhabilitadas');
            renderSimpleList('container-enfermedades', data.enfermedades, 'No hay enfermedades inhabilitadas');
            renderSimpleList('container-discapacidades', data.discapacidades, 'No hay discapacidades inhabilitadas');
            renderSimpleList('container-parentesco', data.parentesco, 'No hay parentescos inhabilitados');
            renderSimpleList('container-motivos-retiro', data.motivos_retiro, 'No hay motivos de retiro inhabilitados');
            renderSimpleList('container-motivos-asistencia', data.motivos_asistencia, 'No hay motivos de asistencia inhabilitados');

            // Render Estudios (Complex because it combines Niveles and Especialidades)
            renderEstudios(data.niveles, data.especialidades);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
}

function renderSimpleList(containerId, items, emptyMsg) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!items || items.length === 0) {
        container.innerHTML = `<div class="list-group-item list-group-item-light text-muted italic">${emptyMsg}</div>`;
        return;
    }

    let actionPrefix = '';
    let endpoint = 'models/info_alumnos/ajax_info_alumnos.php';

    if (containerId.includes('vacunas')) actionPrefix = 'reactivate_vaccine';
    else if (containerId.includes('enfermedades')) actionPrefix = 'reactivate_disease';
    else if (containerId.includes('discapacidades')) actionPrefix = 'reactivate_disability';
    else if (containerId.includes('parentesco')) {
        actionPrefix = 'reactivateParentesco';
        endpoint = 'models/options/ajax-options.php';
    } else if (containerId.includes('motivos-retiro')) {
        actionPrefix = 'reactivate_motivo';
        endpoint = 'models/reportes/ajax-reportes.php';
    } else if (containerId.includes('motivos-asistencia')) {
        actionPrefix = 'reactivate_motivo';
        endpoint = 'models/reportes/ajax-reportes.php';
    }

    let html = '';
    items.forEach(item => {
        let idVal = item.id || item.nombre; // For motives, we use the name as ID if needed
        let typeVal = item.tipo || ''; // For motives
        html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${item.nombre}</span>
                    <button class="btn btn-sm btn-outline-success" onclick="reactivateItem('${actionPrefix}', '${idVal}', '${item.nombre}', '${endpoint}', false, '${typeVal}')">
                        <i class="fas fa-undo"></i> Reactivar
                    </button>
                 </div>`;
    });
    container.innerHTML = html;
}

function renderEstudios(niveles, especialidades) {
    const container = document.getElementById('container-estudios');
    if (!container) return;

    if ((!niveles || niveles.length === 0) && (!especialidades || especialidades.length === 0)) {
        container.innerHTML = `<div class="col-12 text-center p-3 text-muted italic">No hay niveles ni especialidades inhabilitadas</div>`;
        return;
    }

    let html = '';
    const endpoint = 'models/niveles_estudio/ajax-niveles-estudio.php';

    if (niveles && niveles.length > 0) {
        html += `<div class="col-md-6 border-right">
                    <h5 class="mb-3 border-bottom pb-2">Categorías (Niveles)</h5>
                    <div class="list-group list-group-flush mb-3">`;
        niveles.forEach(n => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        ${n.nombre}
                        <button class="btn btn-sm btn-outline-warning" onclick="reactivateItem('reactivateCategoria', '${n.id}', '${n.nombre}', '${endpoint}', true)">
                            <i class="fas fa-undo"></i> Reactivar
                        </button>
                     </div>`;
        });
        html += `</div></div>`;
    }

    if (especialidades && especialidades.length > 0) {
        html += `<div class="col-md-6">
                    <h5 class="mb-3 border-bottom pb-2">Especializaciones</h5>
                    <div class="list-group list-group-flush">`;
        especialidades.forEach(e => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${e.nombre}</strong><br>
                            <small class="text-muted">Categoría: ${e.categoria}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-info" onclick="reactivateItem('reactivateEspecializacion', '${e.id}', '${e.nombre}', '${endpoint}')">
                            <i class="fas fa-undo"></i> Reactivar
                        </button>
                     </div>`;
        });
        html += `</div></div>`;
    }

    container.innerHTML = html;
}

function reactivateItem(action, id, label, endpoint, isCategoria = false, motiveType = '') {
    swal({
        title: "¿Reactivar elemento?",
        text: "¿Desea activar nuevamente: " + label + "?",
        type: "info",
        showCancelButton: true,
        confirmButtonText: "Sí, reactivar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false,
        showLoaderOnConfirm: true
    }, function () {
        let fd = new FormData();
        fd.append('action', action);
        if (isCategoria) {
            fd.append('categoria', label);
        } else if (action === 'reactivate_motivo') {
            fd.append('motivo', label);
            fd.append('tipo', motiveType);
        } else {
            fd.append('id', id);
        }

        fetch(endpoint, {
            method: 'POST',
            body: fd,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(res => {
                if (res.status) {
                    swal("¡Reactivado!", res.msg, "success");
                    loadDisabledItems(); // Reload dashboard
                } else {
                    swal("Error", res.msg, "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                swal("Error", "Error de conexión", "error");
            });
    });
}
