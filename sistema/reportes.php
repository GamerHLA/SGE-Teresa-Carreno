/**
 * MÓDULO DE REPORTES DEL SISTEMA ESCOLAR
 * 
 * Este archivo gestiona la interfaz principal para la generación de reportes del sistema.
 * Permite generar diversos tipos de documentos PDF incluyendo:
 * - Constancias (estudio, inscripción, asistencia)
 * - Boletas de retiro y cartas de aceptación
 * - Listados de alumnos, profesores, representantes
 * - Listas de grados, secciones, períodos escolares e inscripciones
 */

<?php
// ===== VALIDACIÓN DE SESIÓN =====
// Iniciar la sesión para verificar que el usuario esté autenticado
session_start();

// Si no hay sesión activa, redirigir al login
if (empty($_SESSION['active'])) {
    header('location: ../');
}

// ===== INCLUSIÓN DE ARCHIVOS NECESARIOS =====
include "includes/header.php";      // Encabezado HTML y estilos
require_once 'includes/config.php'; // Configuración de base de datos

// ===== VERIFICACIÓN DE DIRECTOR ACTIVO =====
// Verificar si existe un director activo (es_director = 1)
// Nota: La lógica de actualización de estado (1->2) corre en table_profesores.php/ajax.
// Aquí solo leemos el estado actual para validar si se pueden generar ciertos reportes
$sqlDirector = "SELECT COUNT(*) as total FROM profesor WHERE es_director = 1";
$queryDirector = $pdo->prepare($sqlDirector);
$queryDirector->execute();
$hasDirector = $queryDirector->fetch(PDO::FETCH_ASSOC)['total'] > 0;
?>
<!-- Variable JavaScript para validar si existe un director activo -->
<script>
    // Variable global que indica si hay un director activo en el sistema
    // Se usa para validar si se pueden generar ciertos reportes que requieren firma del director
    const hasDirector = <?php echo $hasDirector ? 'true' : 'false'; ?>;
</script>

<!-- Estilos CSS personalizados para el módulo de reportes -->
<style>
    /* ===== CORRECCIÓN DE ESTILOS PARA SELECT2 ===== */
    /* Ajusta la altura del contenedor principal del select2 */
    .select2-container--default .select2-selection--single {
        height: 38px !important;              /* Altura estándar de inputs Bootstrap */
        border: 1px solid #ced4da !important; /* Color de borde Bootstrap */
    }
    
    /* Ajusta la línea de texto dentro del select2 */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;  /* Centrado vertical del texto */
        padding-left: 12px !important; /* Espaciado izquierdo */
    }
    
    /* Ajusta la flecha desplegable del select2 */
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important; /* Altura de la flecha para alineación */
    }
</style>

<!-- ===== CONTENIDO PRINCIPAL DE LA PÁGINA ===== -->
<main class="app-content">
    <!-- Encabezado de la página con título y breadcrumb -->
    <div class="app-title">
        <div>
            <!-- Título principal del módulo -->
            <h1><i class="fa fa-file-pdf-o"></i> Reportes del Sistema</h1>
        </div>
        
        <!-- Navegación breadcrumb (migas de pan) -->
        <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
            <li class="breadcrumb-item"><a href="reportes.php">Reportes</a></li>
        </ul>
    </div>

    <!-- Contenedor principal del formulario de reportes -->
    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">
                    <!-- Formulario para selección de tipo de reporte y filtros -->
                    <form id="formReporte" onsubmit="return false;">
                        <div class="row">
                            <!-- ===== FILTRO DE PERÍODO ESCOLAR (SOLO LECTURA) ===== -->
                            <!-- Este campo muestra el período activo y está oculto por defecto -->
                            <!-- Se muestra automáticamente cuando se selecciona un tipo de reporte -->
                            <div class="col-md-4" id="divFiltroPeriodo" style="display: none;">
                                <div class="form-group">
                                    <label for="periodoDisplay">Periodo Escolar</label>
                                    <!-- Campo visible (deshabilitado) que muestra el nombre del período -->
                                    <input type="text" class="form-control" id="periodoDisplay" readonly disabled
                                        style="background-color: #f8f9fa; cursor: not-allowed;">
                                    <!-- Campo oculto que almacena el ID del período para envío -->
                                    <input type="hidden" id="listPeriodo" name="listPeriodo">
                                </div>
                            </div>
                            <!-- ===== SELECTOR DE TIPO DE REPORTE ===== -->
                            <!-- Dropdown principal para seleccionar qué tipo de reporte generar -->
                            <div class="col-md-12" id="divTipoReporteContainer">
                                <div class="form-group">
                                    <label for="listReporte">Tipo de Reporte</label>
                                    <select class="form-control" id="listReporte" name="listReporte" required>
                                        <option value="">Seleccione un reporte...</option>
                                        
                                        <!-- Grupo 1: Constancias y Documentos Individuales -->
                                        <optgroup label="Constancias y Documentos">
                                            <option value="retiro">Boleta de Retiro</option>
                                            <option value="aceptacion">Carta de Aceptación</option>
                                            <option value="justificativo">Constancia de Asistencia de Representante</option>
                                            <option value="constancia_estudio">Constancia de Estudio</option>
                                            <option value="constancia_inscripcion">Constancia de Inscripción</option>
                                        </optgroup>
                                        
                                        <!-- Grupo 2: Listados Específicos por Criterios -->
                                        <optgroup label="Listados Específicos">
                                            <option value="lista_graysec">Lista de Grados y Secciones</option>
                                            <option value="lista_inscribir">Lista de Inscripciones</option>
                                            <option value="lista_periodo">Lista de Periodos Escolares</option>
                                        </optgroup>
                                        
                                        <!-- Grupo 3: Listados Generales Completos -->
                                        <optgroup label="Listados Generales">
                                            <option value="lista_alumno">Lista Completa de Alumnos</option>
                                            <option value="lista_profesor">Lista de Profesores</option>
                                            <option value="lista_representante">Lista de Representantes</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ===== CONTENEDOR DE FILTROS DINÁMICOS ===== -->
                        <!-- Este contenedor se muestra/oculta según el tipo de reporte seleccionado -->
                        <!-- Los filtros específicos se activan dinámicamente mediante JavaScript -->
                        <div id="filtrosContainer" style="display: none;">
                            <div class="row">
                                <!-- ===== FILTRO DE ALUMNO ===== -->
                                <!-- Select2 con búsqueda para seleccionar un alumno específico -->
                                <!-- Se usa en: constancias de estudio, inscripción, boletas de retiro, etc. -->
                                <div class="col-md-6" id="divFiltroAlumno" style="display: none;">
                                    <div class="form-group">
                                        <label for="listAlumno">Buscar Alumno</label>
                                        <!-- Select2 permite búsqueda por nombre o cédula -->
                                        <select class="form-control select2" id="listAlumno" name="listAlumno"
                                            style="width: 100%;">
                                            <option value="">Buscar por nombre o cédula...</option>
                                        </select>
                                        <small class="text-muted">Seleccione el alumno para generar el documento.</small>
                                    </div>
                                </div>

                                <!-- ===== FILTRO DE REPRESENTANTE ===== -->
                                <!-- Select2 para seleccionar un representante -->
                                <!-- Se usa en: constancias de asistencia de representante -->
                                <div class="col-md-6" id="divFiltroRepresentante" style="display: none;">
                                    <div class="form-group">
                                        <label for="listRepresentante">Buscar Representante</label>
                                        <!-- Select2 permite búsqueda por nombre o cédula del representante -->
                                        <select class="form-control select2-representante" id="listRepresentante"
                                            name="listRepresentante" style="width: 100%;">
                                            <option value="">Buscar por nombre o cédula...</option>
                                        </select>
                                        <small class="text-muted">Seleccione el representante para generar el documento.</small>
                                    </div>
                                </div>

                                <!-- ===== TOGGLE PARA MOTIVO DE ASISTENCIA ===== -->
                                <!-- Pregunta si se desea agregar un motivo de asistencia al documento -->
                                <!-- Se muestra en: constancias de asistencia de representante -->
                                <div class="col-md-12" id="divToggleMotivoAsistencia"
                                    style="display: none; margin-bottom: 20px;">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold d-block">¿Desea colocar un motivo de asistencia?</label>
                                        <div class="mt-2">
                                            <!-- Opción SÍ: Mostrará campo para seleccionar/escribir motivo -->
                                            <div class="form-check form-check-inline mr-3">
                                                <input class="form-check-input" type="radio"
                                                    name="poseeMotivoAsistencia" id="poseeMotivoAsistenciaSi"
                                                    value="SI">
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeMotivoAsistenciaSi">SÍ</label>
                                            </div>
                                            <!-- Opción NO: No se incluirá motivo en el documento (por defecto) -->
                                            <div class="form-check form-check-inline mr-0">
                                                <input class="form-check-input" type="radio"
                                                    name="poseeMotivoAsistencia" id="poseeMotivoAsistenciaNo" value="NO"
                                                    checked>
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeMotivoAsistenciaNo">NO</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== FILTROS PARA GRADOS Y SECCIONES ===== -->
                                <!-- Estos filtros se usan en reportes de listas de grados y secciones -->
                                <!-- Permiten filtrar por grado específico (1ero a 6to) -->
                                <div class="col-md-3" id="divFiltroGrado" style="display: none;">
                                    <div class="form-group">
                                        <label for="listGrado">Grado</label>
                                        <select class="form-control" id="listGrado" name="listGrado">
                                            <option value="">Todos</option>
                                            <option value="1">1ero</option>
                                            <option value="2">2do</option>
                                            <option value="3">3ero</option>
                                            <option value="4">4to</option>
                                            <option value="5">5to</option>
                                            <option value="6">6to</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Filtro de sección (A, B, C, D) -->
                                <div class="col-md-3" id="divFiltroSeccion" style="display: none;">
                                    <div class="form-group">
                                        <label for="listSeccion">Sección</label>
                                        <select class="form-control" id="listSeccion" name="listSeccion">
                                            <option value="">Todos</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Filtro de estatus (Activos/Inactivos) para grados -->
                                <div class="col-md-3" id="divFiltroEstatusGrado" style="display: none;">
                                    <div class="form-group">
                                        <label for="listEstatusGrado">Estatus</label>
                                        <select class="form-control" id="listEstatusGrado" name="listEstatusGrado">
                                            <option value="all">Todos</option>
                                            <option value="1">Activos</option>
                                            <option value="2">Inactivos</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- ===== FILTRO DE PERÍODO PARA GRADOS (SOLO LECTURA) ===== -->
                                <!-- Muestra el período escolar activo para el reporte de grados -->
                                <div class="col-md-3" id="divFiltroPeriodoGrado" style="display: none;">
                                    <div class="form-group">
                                        <label for="listPeriodoGrado">Periodo Escolar</label>
                                        <!-- Campo visible de solo lectura -->
                                        <input type="text" class="form-control" id="listPeriodoGrado"
                                            name="listPeriodoGrado" readonly>
                                        <!-- Campo oculto con el ID del período -->
                                        <input type="hidden" id="listPeriodoGradoId" name="listPeriodoGradoId">
                                    </div>
                                </div>

                                <!-- ===== FILTRO DE CURSO ===== -->
                                <!-- Se usa en el reporte de lista de inscripciones -->
                                <!-- Permite filtrar inscripciones por curso específico -->
                                <div class="col-md-4" id="divFiltroCurso" style="display: none;">
                                    <div class="form-group">
                                        <label for="listCurso">Curso</label>
                                        <!-- Las opciones se cargan dinámicamente desde la base de datos -->
                                        <select class="form-control" id="listCurso" name="listCurso">
                                            <option value="">Todos los cursos</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- ===== DISPLAY DE MOTIVO DE RETIRO ACTUAL ===== -->
                                <!-- Muestra el motivo de retiro actual del alumno (si existe) -->
                                <!-- Campo de solo lectura para información del usuario -->
                                <div class="col-md-12" id="divMotivoActual" style="display: none; margin-bottom: 20px;">
                                    <div class="form-group">
                                        <label for="txtMotivoActual" class="font-weight-bold">Motivo de Retiro Actual</label>
                                        <input type="text" class="form-control" id="txtMotivoActual" readonly 
                                            style="background-color: #f8f9fa; cursor: default; font-style: italic; height: 38px;">
                                    </div>
                                </div>

                                <!-- ===== TOGGLE PARA MOTIVO DE RETIRO ===== -->
                                <!-- Pregunta si se desea agregar un nuevo motivo de retiro -->
                                <!-- Se muestra en: boletas de retiro -->
                                <div class="col-md-12" id="divToggleMotivo" style="display: none; margin-bottom: 20px;">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold d-block">¿Desea colocar un nuevo motivo de retiro?</label>
                                        <div class="mt-2">
                                            <!-- Opción SÍ: Mostrará campo para seleccionar/escribir nuevo motivo -->
                                            <div class="form-check form-check-inline mr-3">
                                                <input class="form-check-input" type="radio" name="poseeMotivoRetiro"
                                                    id="poseeMotivoRetiroSi" value="SI">
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeMotivoRetiroSi">SÍ</label>
                                            </div>
                                            <!-- Opción NO: Usará el motivo actual o ninguno (por defecto) -->
                                            <div class="form-check form-check-inline mr-0">
                                                <input class="form-check-input" type="radio" name="poseeMotivoRetiro"
                                                    id="poseeMotivoRetiroNo" value="NO" checked>
                                                <label class="form-check-label font-weight-bold"
                                                    for="poseeMotivoRetiroNo">NO</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===== SELECTOR DE MOTIVO (RETIRO O ASISTENCIA) ===== -->
                                <!-- Select2 con tags para seleccionar motivo existente o escribir uno nuevo -->
                                <!-- Se usa tanto para motivos de retiro como de asistencia -->
                                <div class="col-md-6" id="divFiltroMotivo" style="display: none;">
                                    <div class="form-group mb-0">
                                        <label for="listMotivo" class="font-weight-bold">Motivo</label>
                                        <div class="d-flex align-items-center">
                                            <!-- Select2 con opción de escribir nuevos motivos (tags: true) -->
                                            <div style="flex-grow: 1; min-width: 0;">
                                                <select class="form-control select2-motivo" id="listMotivo" name="listMotivo"
                                                    style="width: 100%;">
                                                    <option value="">Seleccione o escriba un motivo...</option>
                                                </select>
                                            </div>
                                            <!-- Botón de inhabilitar motivo (solo visible para administradores) -->
                                            <?php if ($_SESSION['rol'] == 1) { ?>
                                                <div class="ml-2">
                                                    <!-- Botón para inhabilitar el motivo seleccionado -->
                                                    <!-- Se oculta hasta que se seleccione un motivo existente -->
                                                    <button class="btn btn-danger" type="button" id="btnInhabilitarMotivo"
                                                        title="Inhabilitar este motivo" style="display: none; height: 38px; width: 38px; padding: 0;">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Puede escribir un nuevo motivo si no está en la lista.</small>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ===== FILTROS ADICIONALES PARA LISTADOS GENERALES ===== -->
                        <!-- Esta sección contiene filtros específicos para reportes de listados completos -->
                        <!-- Los filtros se muestran/ocultan dinámicamente según el tipo de listado -->
                        <div id="filtrosListados" style="display: none; margin-top: 20px;">
                            <hr>
                            <h5 class="mb-3">Filtros de Búsqueda</h5>

                            <!-- ===== FILTROS PARA LISTADO DE ALUMNOS ===== -->
                            <!-- Permite filtrar alumnos por sexo y estatus (activo/inactivo) -->
                            <div id="filtrosAlumnos" class="filtros-section" style="display: none;">
                                <div class="row">
                                    <!-- Filtro por sexo (Masculino/Femenino/Todos) -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="filtroSexoAlumno">Sexo</label>
                                            <select class="form-control" id="filtroSexoAlumno" name="filtroSexoAlumno">
                                                <option value="all">Todos</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por estatus (Activos/Inactivos/Todos) -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="filtroEstatusAlumno">Estatus</label>
                                            <select class="form-control" id="filtroEstatusAlumno"
                                                name="filtroEstatusAlumno">
                                                <option value="all">Todos</option>
                                                <option value="1">Activos</option>
                                                <option value="2">Inactivos</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== FILTROS PARA LISTADO DE PROFESORES ===== -->
                            <!-- Permite filtrar profesores por sexo, estatus y si son representantes -->
                            <div id="filtrosProfesores" class="filtros-section" style="display: none;">
                                <div class="row">
                                    <!-- Filtro por sexo -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="filtroSexoProfesor">Sexo</label>
                                            <select class="form-control" id="filtroSexoProfesor"
                                                name="filtroSexoProfesor">
                                                <option value="all">Todos</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por estatus (Activo/Inactivo) -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="filtroEstatusProfesor">Estatus</label>
                                            <select class="form-control" id="filtroEstatusProfesor"
                                                name="filtroEstatusProfesor">
                                                <option value="all">Todos</option>
                                                <option value="1">Activos</option>
                                                <option value="2">Inactivos</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por si el profesor también es representante -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="filtroEsRepresentante">Es Representante</label>
                                            <select class="form-control" id="filtroEsRepresentante"
                                                name="filtroEsRepresentante">
                                                <option value="all">Todos</option>
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== FILTROS PARA LISTADO DE REPRESENTANTES ===== -->
                            <!-- Permite filtrar representantes por sexo, estatus, tipo y si son profesores -->
                            <div id="filtrosRepresentantes" class="filtros-section" style="display: none;">
                                <div class="row">
                                    <!-- Filtro por sexo -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroSexoRepresentante">Sexo</label>
                                            <select class="form-control" id="filtroSexoRepresentante"
                                                name="filtroSexoRepresentante">
                                                <option value="all">Todos</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por estatus -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroEstatusRepresentante">Estatus</label>
                                            <select class="form-control" id="filtroEstatusRepresentante"
                                                name="filtroEstatusRepresentante">
                                                <option value="all">Todos</option>
                                                <option value="1">Activos</option>
                                                <option value="2">Inactivos</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por tipo (Principal/Secundario) -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroTipoRepresentante">Tipo de Representante</label>
                                            <select class="form-control" id="filtroTipoRepresentante"
                                                name="filtroTipoRepresentante">
                                                <option value="all">Todos</option>
                                                <option value="1">Principal</option>
                                                <option value="2">Secundario</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por si el representante también es profesor -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroEsProfesor">Es Profesor</label>
                                            <select class="form-control" id="filtroEsProfesor" name="filtroEsProfesor">
                                                <option value="all">Todos</option>
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== FILTROS PARA LISTADO DE INSCRIPCIONES ===== -->
                            <!-- Permite filtrar inscripciones por sexo, grado, sección, estatus y edad -->
                            <div id="filtrosInscripciones" class="filtros-section" style="display: none;">
                                <div class="row">
                                    <!-- Filtro por sexo del alumno inscrito -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroSexoInscripcion">Sexo</label>
                                            <select class="form-control" id="filtroSexoInscripcion"
                                                name="filtroSexoInscripcion">
                                                <option value="all">Todos</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por grado (1ero a 6to) -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroGradoInscripcion">Grado</label>
                                            <select class="form-control" id="filtroGradoInscripcion"
                                                name="filtroGradoInscripcion">
                                                <option value="all">Todos</option>
                                                <option value="1">1ero</option>
                                                <option value="2">2do</option>
                                                <option value="3">3ero</option>
                                                <option value="4">4to</option>
                                                <option value="5">5to</option>
                                                <option value="6">6to</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por sección (A, B, C, D) -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroSeccionInscripcion">Sección</label>
                                            <select class="form-control" id="filtroSeccionInscripcion"
                                                name="filtroSeccionInscripcion">
                                                <option value="all">Todas</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtro por estatus de la inscripción -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroEstatusInscripcion">Estatus</label>
                                            <select class="form-control" id="filtroEstatusInscripcion"
                                                name="filtroEstatusInscripcion">
                                                <option value="all">Todos</option>
                                                <option value="1">Activos</option>
                                                <option value="2">Inactivos</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Filtro por edad del alumno -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroEdadInscripcion">Edad</label>
                                            <select class="form-control" id="filtroEdadInscripcion"
                                                name="filtroEdadInscripcion">
                                                <option value="">Todas</option>
                                                <!-- Las opciones se cargarán dinámicamente o por JS -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== FILTROS PARA LISTADO DE PERÍODOS ESCOLARES ===== -->
                            <!-- Permite filtrar períodos escolares por año -->
                            <div id="filtrosPeriodos" class="filtros-section" style="display: none;">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="filtroPeriodoAnio">Año del Periodo</label>
                                            <!-- Las opciones se llenan dinámicamente con JavaScript -->
                                            <select class="form-control" id="filtroPeriodoAnio"
                                                name="filtroPeriodoAnio">
                                                <option value="all">Todos los periodos</option>
                                                <!-- Se llenarán dinámicamente con JavaScript -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== BOTÓN PARA GENERAR REPORTE ===== -->
                        <!-- Al hacer clic, ejecuta la función generarReporte() en functions-reportes.js -->
                        <!-- Esta función valida los datos y genera el PDF correspondiente -->
                        <div class="tile-footer">
                            <button class="btn btn-primary" type="button" onclick="generarReporte()"><i
                                    class="fa fa-fw fa-lg fa-check-circle"></i>Generar Reporte</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Incluir pie de página con scripts necesarios -->
<?php include "includes/footer.php"; ?>
<!-- Script JavaScript con todas las funciones para manejar reportes -->
<script src="js/functions-reportes.js?v=1.9"></script>