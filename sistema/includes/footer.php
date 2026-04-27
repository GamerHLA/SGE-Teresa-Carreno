<?php
/**
 * FOOTER.PHP
 * ==========
 * 
 * Pie de página HTML del sistema escolar.
 * Incluido en todas las páginas del sistema.
 * 
 * COMPONENTES:
 * - Scripts esenciales de JavaScript (jQuery, Popper, Bootstrap)
 * - FontAwesome para iconos
 * - Scripts de plugins (Pace loader, SweetAlert, DataTables, Select2)
 * - Scripts de funcionalidades del sistema:
 *   * functions-alumnos.js
 *   * functions-representantes.js
 *   * functions-usuarios.js
 *   * functions-profesores.js
 *   * modal-niveles-functions.js
 *   * functions-curso.js
 *   * functions-periodo.js
 *   * functions-inscripcion.js
 *   * activate-functions.js
 *   * session-timeout.js
 *   * es_custom.js
 * - Cierre de etiquetas HTML (body, html)
 * 
 * NOTA: Este archivo se carga al final de cada página para optimizar
 * el tiempo de carga y asegurar que el DOM esté completamente cargado.
 */
?>
<!-- Essential javascripts for application to work-->
<script src="js/jquery-3.3.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/fontawesome.js"></script>
<!--<script src="https://kit.fontawesome.com/16e860252b.js" crossorigin="anonymous"></script>-->
<script src="js/main.js"></script>
<!-- The javascript plugin to display page loading on top-->
<script src="js/plugins/pace.min.js"></script>
<script type="text/javascript" src="js/plugins/sweetalert.min.js"></script>
<!-- Data table plugin-->
<script type="text/javascript" src="js/plugins/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/plugins/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="js/plugins/select2.min.js"></script>

<script src="js/functions-alumnos.js"></script>
<script src="js/functions-representantes.js?v=1.0"></script>
<script src="js/functions-usuarios.js"></script>
<script src="js/functions-profesores.js"></script>
<script src="js/modal-niveles-functions.js"></script>
<script src="js/functions-curso.js"></script>
<script src="js/functions-periodo.js"></script>
<script src="js/functions-inscripcion.js"></script>
<script src="js/activate-functions.js"></script>
<script src="js/session-timeout.js"></script>
<script src="js/es_custom.js"></script>
</body>

</html>