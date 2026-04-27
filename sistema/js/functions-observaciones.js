/**
 * FUNCTIONS-OBSERVACIONES.JS
 * ==========================
 * 
 * Gestión del módulo de observaciones de alumnos del sistema escolar.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - DataTable con listado de observaciones de alumnos
 * - Visualización de observaciones con tipos diferenciados por badges
 * - Tipos de observaciones: Inhabilitación, Reactivación, Retiro, Motivo de Retiro, Justificativo
 * - Ordenamiento por fecha descendente (más recientes primero)
 * - Formato HTML enriquecido para observaciones
 * - Decodificación automática de texto base64
 * 
 * DEPENDENCIAS:
 * - jQuery
 * - DataTables
 */

var tableObservaciones;

document.addEventListener('DOMContentLoaded', function () {
    tableObservaciones = $('#tableObservaciones').DataTable({
        "processing": true,
        "serverSide": false,
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
        "ajax": {
            "url": "./models/observaciones/table_observaciones.php",
            "dataSrc": ""
        },
        "columns": [
            {
                "data": "numero_registro",
                "orderable": false,
                "searchable": false,
                "width": "5%"
            },
            {
                "data": "nombre_completo",
                "width": "20%"
            },
            {
                "data": "tipo_badge",
                "orderable": false,
                "width": "5%" // Minimized width for Tipo
            },
            {
                "data": "observacion_html",
                "orderable": false,
                "width": "30%",
                "className": "text-wrap"
            },
            {
                "data": "fecha_hora_completa",
                "width": "15%",
                "render": function (data, type, row) {
                    if (type === 'sort') {
                        return row.fecha_raw;
                    }
                    return data;
                }
            }
        ],
        "responsive": true,
        "destroy": true,
        "pageLength": 10,
        "order": [[4, "desc"]], // Order by date descending (most recent first)
        "autoWidth": false
    });
});
