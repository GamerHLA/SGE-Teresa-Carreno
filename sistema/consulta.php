<?php
session_start();
if (empty($_SESSION['active'])) {
    header("Location: ../");
}
require_once 'includes/session.php';
require_once 'includes/header.php';
?>
<main class="app-content">
    <div class="app-title">
        <div>
            <h2>
                <i class="app-menu__icon fas fa-search"></i> Consulta
                <button class="btn btn-primary" type="button" onclick="openModalConsulta()">Buscar</button>
            </h2>
            <br>
            <h5>
                <i class=style="font-weight: bold;"></i> Realiza consultas personalizadas
            </h5>
        </div>
    </div>

    <!-- Contenedor para mostrar los resultados -->
    <div id="resultados-consulta" class="mt-4"></div>

    <!-- Modal para buscar por cédula -->
    <div class="modal fade" id="modalConsulta" tabindex="-1" role="dialog" aria-labelledby="modalConsultaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConsultaLabel">Buscar por Cédula</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formBuscarCedula">
                        <div class="form-group">
                            <label for="cedula">Número de Cédula:</label>
                            <input type="text" class="form-control" id="cedula" name="cedula" required placeholder="Ingrese la cédula a buscar">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="buscarPorCedula()">Buscar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para abrir el modal de búsqueda
        function openModalConsulta() {
            console.log('Abriendo modal de consulta...');
            $('#modalConsulta').modal('show');

            // Enfocar el input cuando se abre el modal
            setTimeout(function() {
                document.getElementById('cedula').focus();
            }, 500);
        }

        // Función principal para buscar por cédula
        function buscarPorCedula() {
            const cedula = document.getElementById('cedula').value.trim();
            console.log('Buscando cédula:', cedula);

            if (!cedula) {
                alert('Por favor, ingrese un número de cédula');
                return;
            }

            // Mostrar loading y cerrar modal
            $('#modalConsulta').modal('hide');
            document.getElementById('resultados-consulta').innerHTML = `
        <div class="alert alert-info text-center">
            <i class="fas fa-spinner fa-spin"></i> Buscando información para cédula: <strong>${cedula}</strong>...
        </div>
    `;

            // Realizar petición AJAX
            fetch('buscar_cedula.php?cedula=' + encodeURIComponent(cedula))
                .then(response => {
                    console.log('Respuesta recibida, status:', response.status);
                    if (!response.ok) {
                        throw new Error('Error del servidor: ' + response.status);
                    }
                    return response.text();
                })
                .then(data => {
                    console.log('Datos recibidos correctamente');
                    document.getElementById('resultados-consulta').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error en la búsqueda:', error);
                    document.getElementById('resultados-consulta').innerHTML = `
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Error al realizar la búsqueda</h4>
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p>Por favor, verifique:</p>
                    <ul>
                        <li>Que el archivo buscar_cedula.php exista</li>
                        <li>Que tenga permisos de lectura</li>
                        <li>Que la conexión a la base de datos funcione</li>
                    </ul>
                    <button class="btn btn-warning" onclick="openModalConsulta()">Reintentar</button>
                </div>
            `;
                });
        }

        // Función para limpiar los resultados
        function limpiarBusqueda() {
            document.getElementById('resultados-consulta').innerHTML = '';
            document.getElementById('cedula').value = '';
        }

        // Cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página de consulta cargada');

            // Permitir buscar con Enter en el input de cédula
            document.getElementById('cedula').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarPorCedula();
                }
            });

            // Limpiar el input cuando se cierre el modal
            $('#modalConsulta').on('hidden.bs.modal', function() {
                document.getElementById('cedula').value = '';
            });

            // Mostrar el modal automáticamente al cargar la página (opcional)
            // setTimeout(openModalConsulta, 500);
        });

        // Función para probar manualmente desde la consola
        function probarBusqueda(cedula) {
            document.getElementById('cedula').value = cedula;
            buscarPorCedula();
        }
    </script>

    <?php require_once 'includes/footer.php'; ?>
