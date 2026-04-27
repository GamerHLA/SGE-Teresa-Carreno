<?php
require_once __DIR__ . '/../../fpdf/fpdf.php';

// Clase PDF personalizada con soporte UTF-8
class PDF extends FPDF
{
    public $tableYOffset = 0;

    function Header()
    {
        $logoPath = '';
        if (file_exists('logo_central.png')) {
            $logoPath = 'logo_central.png';
        } else if (file_exists('logo_central.jpg')) {
            $logoPath = 'logo_central.jpg';
        }

        if ($logoPath) {
            $this->Image($logoPath, 0, 0, 205, 25);
        }

        // Línea decorativa exactamente debajo del logo
        $this->SetY(25);
        $this->SetDrawColor(0, 0, 200);
        $this->SetLineWidth(0.5);
        $this->Line(10, 25, 200, 25);
        $this->Ln(4);

        // Título del documento
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->CellUTF8(0, 10, 'REPORTE DE GRADOS Y SECCIONES', 0, 1, 'C');
        $this->Ln(2);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');

        // Pie de página institucional
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(0, 0, 0);

        $footerText = utf8_decode("U.E.D. Teresa Carreño Dirección:Calle Carbonell, Lomas de Urdaneta,frente al bloque 18 pequeño, Caracas, Municipio Libertador, Distrito Capital / Tlfs: 0212-8710661 / Correo: uedteresacarreno@gmail.com");

        $this->SetX(10);
        $this->MultiCell(196, 5, $footerText, 1, 'C');
    }

    // Funciones UTF-8
    function WriteUTF8($h, $txt)
    {
        $this->Write($h, mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8'));
    }

    function CellUTF8($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        parent::Cell($w, $h, mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8'), $border, $ln, $align, $fill, $link);
    }

    function MultiCellUTF8($w, $h, $txt, $border = 0, $align = 'J', $fill = false)
    {
        parent::MultiCell($w, $h, mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8'), $border, $align, $fill);
    }
}

// Configuración inicial
header('Content-Type: text/html; charset=UTF-8');

// Configurar conexión a la base de datos con UTF-8
require_once __DIR__ . '/../includes/config.php';

// PDO instance ($pdo) is already created in config.php

// Obtener filtros de URL
$grado = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : '';
$estatus = isset($_GET['estatus']) ? $_GET['estatus'] : '';
$turno = isset($_GET['turno']) ? $_GET['turno'] : '';
$periodo_id = isset($_GET['periodo_id']) ? intval($_GET['periodo_id']) : 0;

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

// Generar reporte con filtros
generateFilteredCoursesReport($pdf, $pdo, $grado, $seccion, $estatus, $turno, $periodo_id);

$pdf->Output('reporte_grados_secciones.pdf', 'I');

// Funciones para generar reportes
function generateFilteredCoursesReport($pdf, $pdo, $grado, $seccion, $estatus, $turno, $periodo_id)
{
    // Construir WHERE clause dinámico
    $whereClauses = ["c.estatusC != 0"];
    $params = [];

    if ($grado !== '') {
        $whereClauses[] = "g.grado = ?";
        $params[] = $grado;
    }

    if ($seccion !== '') {
        $whereClauses[] = "s.seccion = ?";
        $params[] = $seccion;
    }
    
    // El filtro de estatus es complejo porque depende de estatusC y de si tiene profesor asignado y activo
    // Pero para el query inicial, podemos filtrar por estatusC si es inactivo (2)
    // Sin embargo, "Activo" en el UI significa (estatusC=1 AND profesor_id IS NOT NULL AND profesor_estatus=1)
    // Por simplicidad en la consulta SQL, traeremos los datos y filtraremos en PHP si es necesario, 
    // o ajustamos el query para que sea preciso.
    
    // Mejor ajustar el query para que sea preciso:
    if ($estatus === '1') { // Solo Activos
        $whereClauses[] = "c.estatusC = 1 AND c.profesor_id IS NOT NULL AND p.estatus = 1";
    } elseif ($estatus === '2') { // Solo Inactivos
        $whereClauses[] = "(c.estatusC = 2 OR c.profesor_id IS NULL OR p.estatus != 1)";
    }

    // Turno removido - ahora es automático según sección

    if ($periodo_id > 0) {
        $whereClauses[] = "c.periodo_id = ?";
        $params[] = $periodo_id;
    }

    $whereSQL = implode(' AND ', $whereClauses);

    // Obtener información del periodo
    $nombrePeriodo = 'Todos los periodos';
    if ($periodo_id > 0) {
        $sqlPeriodo = "SELECT CONCAT(anio_inicio, ' - ', anio_fin) as periodo FROM periodo_escolar WHERE periodo_id = ?";
        $stmtPeriodo = $pdo->prepare($sqlPeriodo);
        $stmtPeriodo->execute([$periodo_id]);
        $periodoInfo = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
        $nombrePeriodo = $periodoInfo ? $periodoInfo['periodo'] : 'Desconocido';
    }

    // Obtener cursos filtrados
    $sql = "SELECT 
                c.curso_id,
                g.grado,
                s.seccion,
                c.cupo,
                c.estatusC,
                t.tipo_turno as turno,
                p.profesor_id, 
                CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre, 
                p.estatus as profesor_estatus,
                pe.periodo_id,
                CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo
            FROM curso as c 
            INNER JOIN grados as g ON c.grados_id = g.id_grado
            INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
            LEFT JOIN profesor as p ON c.profesor_id = p.profesor_id 
            INNER JOIN turno as t ON c.turno_id = t.turno_id 
            INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id
            WHERE $whereSQL
            ORDER BY g.grado, s.seccion";

    $query = $pdo->prepare($sql);
    $query->execute($params);
    $cursos = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$cursos) {
        showError($pdf, 'NO HAY CURSOS CON ESTOS FILTROS', 'No se encontraron cursos que cumplan con los criterios seleccionados.');
        return;
    }

    // Contenido del reporte
    $pdf->SetY(45);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->CellUTF8(0, 8, 'Periodo Escolar: ' . $nombrePeriodo, 0, 1, 'C');
    $pdf->Ln(2);

    // Tabla de cursos
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetX(10);

    // Encabezados
    $pdf->CellUTF8(20, 8, 'GRADO', 1, 0, 'C', true);
    $pdf->CellUTF8(20, 8, 'SECCIÓN', 1, 0, 'C', true);
    $pdf->CellUTF8(30, 8, 'TURNO', 1, 0, 'C', true);
    $pdf->CellUTF8(25, 8, 'CUPO', 1, 0, 'C', true);
    $pdf->CellUTF8(60, 8, 'PROFESOR TITULAR', 1, 0, 'C', true);
    $pdf->CellUTF8(35, 8, 'ESTADO', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $fill = false;

    foreach ($cursos as $curso) {
        $pdf->SetX(10);

        $pdf->CellUTF8(20, 7, $curso['grado'] . '°', 1, 0, 'C', $fill);
        $pdf->CellUTF8(20, 7, $curso['seccion'], 1, 0, 'C', $fill);
        $pdf->CellUTF8(30, 7, ucfirst($curso['turno']), 1, 0, 'C', $fill);
        $pdf->CellUTF8(25, 7, $curso['cupo'], 1, 0, 'C', $fill);

        $profesorValido = !empty($curso['profesor_id']) && $curso['profesor_estatus'] == 1;
        $profesor = $profesorValido ? $curso['profesor_nombre'] : 'No asignado';
        $pdf->CellUTF8(60, 7, $profesor, 1, 0, 'L', $fill);

        $estado = ($curso['estatusC'] == 1 && $profesorValido) ? 'Activo' : 'Inactivo';
        $pdf->CellUTF8(35, 7, $estado, 1, 1, 'C', $fill);

        $fill = !$fill;
    }

    // Resumen
    $currentY = $pdf->GetY();
    // Si queda poco espacio (menos de 25mm), saltar página para que el resumen no quede solo o choque
    if ($currentY > 245) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(5);
    }
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(100, 8, 'Total de cursos en el periodo: ' . count($cursos), 0, 0, 'L');

    // Fecha de generación
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(96, 8, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');
}

function generateSingleCourseReport($pdf, $pdo, $curso_id)
{
    // Obtener datos del curso específico
    $sql = "SELECT 
                c.curso_id,
                g.grado,
                s.seccion,
                c.cupo,
                c.estatusC,
                CONCAT(g.grado, '° - Sección ', s.seccion) as grado_seccion,
                t.turno_id,
                t.tipo_turno as turno,
                p.profesor_id, 
                CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre, 
                p.estatus as profesor_estatus,
                pe.periodo_id,
                CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo
            FROM curso as c 
            INNER JOIN grados as g ON c.grados_id = g.id_grado
            INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
            LEFT JOIN profesor as p ON c.profesor_id = p.profesor_id 
            INNER JOIN turno as t ON c.turno_id = t.turno_id 
            INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id
            WHERE c.curso_id = ? AND c.estatusC != 0";

    $query = $pdo->prepare($sql);
    $query->execute([$curso_id]);
    $curso = $query->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        showError($pdf, 'CURSO NO ENCONTRADO', 'No se encontró el curso solicitado.');
        return;
    }

    // Contenido del reporte específico
    $pdf->SetY(45);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->CellUTF8(0, 10, 'INFORMACIÓN DEL GRADO Y SECCIÓN', 0, 1, 'C');
    $pdf->Ln(2);

    // Datos del curso
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetX(20);

    // Información principal en formato tabla
    $data = [
        ['Grado y Sección:', $curso['grado'] . '° Grado - Sección "' . $curso['seccion'] . '"'],
        ['Turno:', ucfirst($curso['turno'])],
        ['Período Escolar:', $curso['periodo']],
        ['Cupo:', $curso['cupo'] . ' estudiantes'],
        ['Profesor Titular:', ($curso['profesor_id'] && $curso['profesor_estatus'] == 1) ? $curso['profesor_nombre'] : 'No asignado'],
        ['Estado:', ($curso['estatusC'] == 1 && $curso['profesor_id'] && $curso['profesor_estatus'] == 1) ? 'Activo' : 'Inactivo']
    ];

    foreach ($data as $row) {
        $pdf->SetX(30);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->CellUTF8(50, 8, $row[0], 0, 0, 'L');
        $pdf->SetFont('Arial', '', 12);
        $pdf->CellUTF8(0, 8, $row[1], 0, 1, 'L');
        $pdf->Ln(2);
    }

    $pdf->Ln(10);

    // Obtener estudiantes inscritos en este curso
    $sql_estudiantes = "SELECT 
                            a.cedula,
                            a.nombre,
                            a.apellido,
                            a.fecha_nac,
                            n.codigo as nacionalidad
                        FROM alumnos a
                        INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id
                        LEFT JOIN nacionalidades n ON a.id_nacionalidades = n.id
                        WHERE i.curso_id = ? AND i.estatusI = 1 AND a.estatus = 1
                        ORDER BY a.apellido, a.nombre";

    $query_estudiantes = $pdo->prepare($sql_estudiantes);
    $query_estudiantes->execute([$curso_id]);
    $estudiantes = $query_estudiantes->fetchAll(PDO::FETCH_ASSOC);

    // Lista de estudiantes
    if (count($estudiantes) > 0) {
        $pdf->SetX(20);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->CellUTF8(0, 10, 'ESTUDIANTES INSCRITOS', 0, 1, 'L');
        $pdf->Ln(5);

        // Encabezado de la tabla
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(15);
        $pdf->CellUTF8(80, 8, 'NOMBRE COMPLETO', 1, 0, 'C', true);
        $pdf->CellUTF8(30, 8, 'CÉDULA', 1, 0, 'C', true);
        $pdf->CellUTF8(30, 8, 'FECHA NAC.', 1, 0, 'C', true);
        $pdf->CellUTF8(20, 8, 'EDAD', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $fill = false;

        foreach ($estudiantes as $estudiante) {
            $pdf->SetX(15);

            // Nombre completo
            $pdf->CellUTF8(80, 7, $estudiante['apellido'] . ', ' . $estudiante['nombre'], 1, 0, 'L', $fill);

            // Cédula
            $cedula = $estudiante['nacionalidad'] ? $estudiante['nacionalidad'] . '-' . $estudiante['cedula'] : $estudiante['cedula'];
            $pdf->CellUTF8(30, 7, $cedula, 1, 0, 'C', $fill);

            // Fecha de nacimiento
            $fecha_nac = $estudiante['fecha_nac'] ? date('d/m/Y', strtotime($estudiante['fecha_nac'])) : 'N/A';
            $pdf->CellUTF8(30, 7, $fecha_nac, 1, 0, 'C', $fill);

            // Edad
            $edad = $estudiante['fecha_nac'] ? calcularEdad($estudiante['fecha_nac']) : 'N/A';
            $pdf->CellUTF8(20, 7, $edad, 1, 1, 'C', $fill);

            $fill = !$fill;
        }

        $pdf->Ln(5);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(80, 8, 'Total de estudiantes: ' . count($estudiantes), 0, 0, 'L');

        // Fecha de generación
        date_default_timezone_set('America/Caracas');
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(116, 8, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');
    }
}

function generatePeriodCoursesReport($pdf, $pdo, $periodo_id)
{
    // Obtener información del periodo
    $sqlPeriodo = "SELECT CONCAT(anio_inicio, ' - ', anio_fin) as periodo FROM periodo_escolar WHERE periodo_id = ?";
    $stmtPeriodo = $pdo->prepare($sqlPeriodo);
    $stmtPeriodo->execute([$periodo_id]);
    $periodoInfo = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
    $nombrePeriodo = $periodoInfo ? $periodoInfo['periodo'] : 'Desconocido';

    // Obtener cursos del periodo
    $sql = "SELECT 
                c.curso_id,
                g.grado,
                s.seccion,
                c.cupo,
                c.estatusC,
                t.tipo_turno as turno,
                p.profesor_id, 
                CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre, 
                p.estatus as profesor_estatus,
                pe.periodo_id,
                CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo
            FROM curso as c 
            INNER JOIN grados as g ON c.grados_id = g.id_grado
            INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
            LEFT JOIN profesor as p ON c.profesor_id = p.profesor_id 
            INNER JOIN turno as t ON c.turno_id = t.turno_id 
            INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id
            WHERE c.estatusC != 0 AND c.periodo_id = ?
            ORDER BY g.grado, s.seccion";

    $query = $pdo->prepare($sql);
    $query->execute([$periodo_id]);
    $cursos = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$cursos) {
        showError($pdf, 'NO HAY CURSOS EN ESTE PERIODO', 'No se encontraron cursos activos para el periodo escolar ' . $nombrePeriodo);
        return;
    }

    // Contenido del reporte por periodo
    $pdf->SetY(45);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->CellUTF8(0, 8, 'Periodo Escolar: ' . $nombrePeriodo, 0, 1, 'C');
    $pdf->Ln(2);

    // Tabla de cursos
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetX(10);

    // Encabezados
    $pdf->CellUTF8(20, 8, 'GRADO', 1, 0, 'C', true);
    $pdf->CellUTF8(20, 8, 'SECCIÓN', 1, 0, 'C', true);
    $pdf->CellUTF8(30, 8, 'TURNO', 1, 0, 'C', true);
    $pdf->CellUTF8(25, 8, 'CUPO', 1, 0, 'C', true);
    $pdf->CellUTF8(60, 8, 'PROFESOR TITULAR', 1, 0, 'C', true);
    $pdf->CellUTF8(35, 8, 'ESTADO', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $fill = false;

    foreach ($cursos as $curso) {
        $pdf->SetX(10);

        $pdf->CellUTF8(20, 7, $curso['grado'] . '°', 1, 0, 'C', $fill);
        $pdf->CellUTF8(20, 7, $curso['seccion'], 1, 0, 'C', $fill);
        $pdf->CellUTF8(30, 7, ucfirst($curso['turno']), 1, 0, 'C', $fill);
        $pdf->CellUTF8(25, 7, $curso['cupo'], 1, 0, 'C', $fill);

        $profesorValido = !empty($curso['profesor_id']) && $curso['profesor_estatus'] == 1;
        $profesor = $profesorValido ? $curso['profesor_nombre'] : 'No asignado';
        $pdf->CellUTF8(60, 7, $profesor, 1, 0, 'L', $fill);

        $estado = ($curso['estatusC'] == 1 && $profesorValido) ? 'Activo' : 'Inactivo';
        $pdf->CellUTF8(35, 7, $estado, 1, 1, 'C', $fill);

        $fill = !$fill;
    }

    // Resumen
    $currentY = $pdf->GetY();
    if ($currentY > 245) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(5);
    }

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(100, 8, 'Total de cursos en el periodo: ' . count($cursos), 0, 0, 'L');

    // Fecha de generación
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(96, 8, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');
}

function generateAllCoursesReport($pdf, $pdo)
{
    // Obtener todos los cursos activos
    $sql = "SELECT 
                c.curso_id,
                g.grado,
                s.seccion,
                c.cupo,
                c.estatusC,
                t.tipo_turno as turno,
                p.profesor_id, 
                CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre, 
                p.estatus as profesor_estatus,
                pe.periodo_id,
                CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo
            FROM curso as c 
            INNER JOIN grados as g ON c.grados_id = g.id_grado
            INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
            LEFT JOIN profesor as p ON c.profesor_id = p.profesor_id 
            INNER JOIN turno as t ON c.turno_id = t.turno_id 
            INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id
            WHERE c.estatusC != 0
            ORDER BY g.grado, s.seccion";

    $query = $pdo->prepare($sql);
    $query->execute();
    $cursos = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$cursos) {
        showError($pdf, 'NO HAY CURSOS DISPONIBLES', 'No se encontraron cursos activos en el sistema.');
        return;
    }

    // Contenido del reporte general
    $pdf->SetY(45);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->CellUTF8(0, 10, 'REPORTE GENERAL DE GRADOS Y SECCIONES', 0, 1, 'C');
    $pdf->Ln(2);

    // Tabla de cursos
    $pdf->SetFillColor(200, 200, 200);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetX(10);

    // Encabezados
    $pdf->CellUTF8(20, 8, 'GRADO', 1, 0, 'C', true);
    $pdf->CellUTF8(20, 8, 'SECCIÓN', 1, 0, 'C', true);
    $pdf->CellUTF8(30, 8, 'TURNO', 1, 0, 'C', true);
    $pdf->CellUTF8(25, 8, 'CUPO', 1, 0, 'C', true);
    $pdf->CellUTF8(60, 8, 'PROFESOR TITULAR', 1, 0, 'C', true);
    $pdf->CellUTF8(35, 8, 'PERÍODO', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $fill = false;

    foreach ($cursos as $curso) {
        $pdf->SetX(10);

        $pdf->CellUTF8(20, 7, $curso['grado'] . '°', 1, 0, 'C', $fill);
        $pdf->CellUTF8(20, 7, $curso['seccion'], 1, 0, 'C', $fill);
        $pdf->CellUTF8(30, 7, ucfirst($curso['turno']), 1, 0, 'C', $fill);
        $pdf->CellUTF8(25, 7, $curso['cupo'], 1, 0, 'C', $fill);

        $profesorValido = !empty($curso['profesor_id']) && $curso['profesor_estatus'] == 1;
        $profesor = $profesorValido ? $curso['profesor_nombre'] : 'No asignado';
        $pdf->CellUTF8(60, 7, $profesor, 1, 0, 'L', $fill);

        $pdf->CellUTF8(35, 7, $curso['periodo'], 1, 1, 'C', $fill);

        $fill = !$fill;
    }

    // Resumen
    $currentY = $pdf->GetY();
    if ($currentY > 245) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(5);
    }

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(100, 8, 'Total de cursos: ' . count($cursos), 0, 0, 'L');

    // Fecha de generación
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(96, 8, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');
}

function calcularEdad($fecha_nacimiento)
{
    $fecha_nac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac);
    return $edad->y;
}

function showError($pdf, $titulo, $mensaje)
{
    $pdf->SetY(100);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, $titulo, 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, $mensaje, 0, 'C');
}
?>