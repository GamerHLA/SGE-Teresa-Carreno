// Funciones para modales de categoría y especialización

/**
 * Abrir modal para agregar nueva categoría
 */
function openModalAddCategoria() {
    document.querySelector('#formAddCategoria').reset();
    $('#modalAddCategoria').modal('show');
}

/**
 * Abrir modal para agregar nueva especialización
 */
function openModalAddEspecializacion() {
    loadCategoriasParaEspecializacion();
    document.querySelector('#formAddEspecializacion').reset();
    $('#modalAddEspecializacion').modal('show');
}

/**
 * Guardar nueva categoría
 */
function guardarNuevaCategoria() {
    var nombreCategoria = document.querySelector('#txtNombreCategoria').value.trim();

    if (!nombreCategoria) {
        swal('Atención', 'Ingrese el nombre de la categoría', 'warning');
        return;
    }

    // Validar que solo contenga letras
    var regex = /^[A-Za-záéíóúÁÉÍÓÚñÑ\s]+$/;
    if (!regex.test(nombreCategoria)) {
        swal('Atención', 'El nombre solo puede contener letras y espacios', 'warning');
        return;
    }

    var request = new XMLHttpRequest();
    var ajaxUrl = './models/niveles_estudio/ajax-niveles-estudio.php';
    var formData = new FormData();
    formData.append('action', 'addCategoria');
    formData.append('categoria', nombreCategoria);

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    swal('¡Éxito!', objData.msg, 'success');
                    $('#modalAddCategoria').modal('hide');
                    // Recargar las listas de categorías en el formulario principal
                    if (typeof loadCategoriasEducacion === 'function') {
                        loadCategoriasEducacion();
                    }
                    if (typeof loadCategoriasParaEspecializacion === 'function') {
                        loadCategoriasParaEspecializacion();
                    }
                } else {
                    swal('Error', objData.msg, 'error');
                }
            } catch (e) {
                console.error('Error al procesar respuesta:', e);
                swal('Error', 'No se pudo procesar la respuesta del servidor', 'error');
            }
        }
    }
}

/**
 * Cargar categorías para el selector de especialización
 */
function loadCategoriasParaEspecializacion() {
    var request = new XMLHttpRequest();
    var ajaxUrl = './models/niveles_estudio/ajax-niveles-estudio.php';
    var formData = new FormData();
    formData.append('action', 'getCategorias');

    request.open('POST', ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                var objData = JSON.parse(request.responseText);
                if (objData.status) {
                    var htmlOptions = '<option value="">Seleccione una categoría</option>';
                    objData.data.forEach(function (categoria) {
                        htmlOptions += '<option value="' + categoria + '">' + categoria + '</option>';
                    });

                    if (document.querySelector('#listCategoriaNuevaEspecializacion')) {
                        document.querySelector('#listCategoriaNuevaEspecializacion').innerHTML = htmlOptions;
                    }
                }
            } catch (e) {
                console.error('Error al cargar categorías:', e);
            }
        }
    }
}

