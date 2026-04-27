<?php
require_once __DIR__ . '/../../fpdf/fpdf.php';

class PDF extends FPDF
{
    var $widths;
    var $aligns;
    var $tableYOffset = 0;
    var $periodoEscolar = '';
    var $mostrarEstatus = true;

    function SetTableYOffset($offset)
    {
        $this->tableYOffset = $offset;
    }

    function SetPeriodoEscolar($periodo)
    {
        $this->periodoEscolar = $periodo;
    }

    function SetWidths($w)
    {
        $this->widths = $w;
    }

    function SetAligns($a)
    {
        $this->aligns = $a;
    }

    function Row($data)
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 5 * $nb;
        $this->CheckPageBreak($h);

        $this->SetFont('Arial', '', 8);

        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

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
        $this->Ln(10);

        // Títulos
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'REGISTRO DE INSCRIPCIONES', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, 'Lista de inscripciones del periodo escolar ' . $this->periodoEscolar, 0, 1, 'C');
        $this->Ln(5);

        // Cabeceras de tabla
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(30, 70, 150);
        $this->SetTextColor(255, 255, 255);

        // Definir anchos dinámicamente
        if ($this->mostrarEstatus) {
            // 12 #, 25 Cedula, 32 Nom, 32 Ape, 10 Sexo, 14 Edad, 15 Gr, 15 Sec, 18 Tur, 22 Est = 195
            $w = array(12, 25, 32, 32, 10, 14, 15, 15, 18, 22);
            $headers = array('#', utf8_decode('Cédula'), 'Nombre', 'Apellido', 'Sexo', 'Edad', 'Grado', utf8_decode('Sección'), 'Turno', 'Estatus');
        } else {
            // Se oculta estatus (22). Se suma 11 a Nombre y 11 a Apellido
            // 12 #, 25 Cedula, 43 Nom, 43 Ape, 10 Sexo, 14 Edad, 15 Gr, 15 Sec, 18 Tur = 195
            $w = array(12, 25, 43, 43, 10, 14, 15, 15, 18);
            $headers = array('#', utf8_decode('Cédula'), 'Nombre', 'Apellido', 'Sexo', 'Edad', 'Grado', utf8_decode('Sección'), 'Turno');
        }

        $anchoTotal = array_sum($w);
        $margenIzquierdo = (216 - $anchoTotal) / 2;
        $this->SetX($margenIzquierdo);

        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($w[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(0, 0, 0);

        $footerText = utf8_decode("U.E.D. Teresa Carreño Dirección:Calle Carbonell, Lomas de Urdaneta,frente al bloque 18 pequeño, Caracas, Municipio Libertador, Distrito Capital / Tlfs: 02128710661 / Correo: uedteresacarreno@gmail.com");

        $this->SetX(10);
        $this->MultiCell(196, 5, $footerText, 1, 'C');

        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }
}

// Conexión a la base de datos
require_once __DIR__ . '/../includes/config.php';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

// Obtener filtros
$periodo_id = isset($_GET['periodo_id']) ? intval($_GET['periodo_id']) : 0;
$sexo = isset($_GET['sexo']) ? $_GET['sexo'] : '';
$grado_filtro = isset($_GET['grado']) ? $_GET['grado'] : '';
$seccion_filtro = isset($_GET['seccion']) ? $_GET['seccion'] : '';
$edad_filtro = isset($_GET['edad']) ? intval($_GET['edad']) : 0;
$estatus_filtro = isset($_GET['estatus']) ? intval($_GET['estatus']) : 0; // 0=Todos, 1=Activos, 2=Inactivos

// Obtener el nombre del periodo escolar
$periodoNombre = 'Todos los periodos';
if ($periodo_id > 0) {
    $sqlPeriodo = "SELECT CONCAT(anio_inicio, ' - ', anio_fin) as periodo FROM periodo_escolar WHERE periodo_id = $periodo_id";
    $resultPeriodo = $conexion->query($sqlPeriodo);
    if ($resultPeriodo && $resultPeriodo->num_rows > 0) {
        $rowPeriodo = $resultPeriodo->fetch_assoc();
        $periodoNombre = $rowPeriodo['periodo'];
    }
}

// Consulta actualizada para inscripciones
$sql = "SELECT 
            i.inscripcion_id,
            a.nombre, 
            a.apellido,
            a.cedula,
            a.sexo,
            a.fecha_nac,
            TIMESTAMPDIFF(YEAR, a.fecha_nac, CURDATE()) AS edad,
            n.codigo as nacionalidad,
            g.grado,
            s.seccion,
            t.tipo_turno as turno,
            i.estatusI,
            c.estatusC,
            pe.estatus as estatusPeriodo
        FROM inscripcion as i 
        INNER JOIN alumnos as a ON i.alumno_id = a.alumno_id 
        LEFT JOIN nacionalidades as n ON a.id_nacionalidades = n.id
        INNER JOIN curso as c ON i.curso_id = c.curso_id 
        INNER JOIN grados as g ON c.grados_id = g.id_grado
        INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
        INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id 
        INNER JOIN turno as t ON i.turno_id = t.turno_id 
        WHERE i.estatusI = 1"; // Solo alumnos activos (inscritos, no retirados)

if ($periodo_id > 0) {
    $sql .= " AND pe.periodo_id = $periodo_id";
}

if (!empty($sexo) && $sexo !== 'all') {
    $sql .= " AND a.sexo = '$sexo'";
}

if (!empty($grado_filtro) && $grado_filtro !== 'all') {
    $sql .= " AND g.grado = '$grado_filtro'";
}

if (!empty($seccion_filtro) && $seccion_filtro !== 'all') {
    $sql .= " AND s.seccion = '$seccion_filtro'";
}

if ($edad_filtro > 0) {
    $sql .= " AND TIMESTAMPDIFF(YEAR, a.fecha_nac, CURDATE()) = $edad_filtro";
}

// Filtro de estatus (Lógica de Activo)
if ($estatus_filtro == 1) { // Activos
    $sql .= " AND i.estatusI = 1 AND c.estatusC = 1 AND pe.estatus = 1";
} elseif ($estatus_filtro == 2) { // Inactivos
    $sql .= " AND (i.estatusI != 1 OR c.estatusC != 1 OR pe.estatus != 1)";
}

$sql .= " ORDER BY i.inscripcion_id DESC";

$result = $conexion->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conexion->error);
}

// Ahora crear el PDF después de tener los datos
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 30);
$pdf->SetPeriodoEscolar($periodoNombre);

$offsetTabla = 60;
$pdf->SetTableYOffset($offsetTabla);
// Determinar si debemos mostrar el estatus
// Si no hay resultados todavía no sabemos el estatus del periodo, pero podemos consultar el periodo_id
$mostrarEstatus = true;
if ($periodo_id > 0) {
    $sqlPeriodo = "SELECT estatus FROM periodo_escolar WHERE periodo_id = $periodo_id";
    $resP = $conexion->query($sqlPeriodo);
    if ($resP && $rowP = $resP->fetch_assoc()) {
        if ($rowP['estatus'] == 2) { // 2 = Inactivo/Pasado
            $mostrarEstatus = false;
        }
    }
}

$pdf->mostrarEstatus = $mostrarEstatus;
$pdf->AddPage();

// Configuración de anchos para el cuerpo (debe coincidir con Header)
if ($mostrarEstatus) {
    $w = array(12, 25, 32, 32, 10, 14, 15, 15, 18, 22);
    $aligns = array('C', 'C', 'L', 'L', 'C', 'C', 'C', 'C', 'C', 'C');
} else {
    $w = array(12, 25, 43, 43, 10, 14, 15, 15, 18);
    $aligns = array('C', 'C', 'L', 'L', 'C', 'C', 'C', 'C', 'C');
}

$pdf->SetWidths($w);
$pdf->SetAligns($aligns);

// Sobrescribir header para coincidir anchos
$pdf->headers_width = $w;

// Centrar tabla
$anchoTotal = array_sum($w);
$margenIzquierdo = (216 - $anchoTotal) / 2;

// Headers manuales para coincidir (hack: redefinir Header func en clase o usar lógica dinámica si posible, 
// pero FPDF es estricto. Lo mejor es modificar la clase PDF arriba, pero aquí estamos en el flujo principal.
// VOY A MODIFICAR la clase PDF usando replace también, o simplemente llamar a $pdf->Header() que se llama auto en AddPage.
// PROBLEMA: Header() usa $w hardcodeado. Necesito actualizar la clase PDF también.)
// SOLUCIÓN: Hice una multi-replace para actualizar la clase y el body.

// Datos
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$totalInscripciones = 0;
$contador = 1; // Inicializar contador

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->SetX($margenIzquierdo);

        // Procesar datos
        $nacionalidad = (!empty($row['nacionalidad'])) ? $row['nacionalidad'] : 'N/A';
        $cedula_completa = $nacionalidad . '-' . $row['cedula'];
        $grado = $row['grado'] . '°';

        // Logica estatus: 1 = Activo, != 1 = Inactivo (siempre que no sea 0 que ya filtramos)
        // Además validar si curso y periodo están activos para decir "Activo" real?
        // El usuario dijo "diga si esta activo o no". Usualmente se refiere al estatus de la inscripción.
        // Usaré la misma logica visual que table_inscripciones.php:
        // $isActivo = $row['estatusI'] == 1 && $row['estatusC'] == 1 && $row['estatusPeriodo'] == 1;

        $isActivo = ($row['estatusI'] == 1 && $row['estatusC'] == 1 && $row['estatusPeriodo'] == 1);
        $estatusTexto = $isActivo ? 'Activo' : 'Inactivo';

        // Datos dinámicos
        $data = array(
            $contador,
            $cedula_completa,
            utf8_decode($row['nombre']),
            utf8_decode($row['apellido']),
            $row['sexo'],
            $row['edad'],
            utf8_decode($grado),
            utf8_decode($row['seccion']),
            utf8_decode($row['turno'])
        );

        if ($mostrarEstatus) {
            $data[] = utf8_decode($estatusTexto);
        }

        $pdf->Row($data);
        $totalInscripciones++;
        $contador++;
    }

    // Total
    $pdf->Ln(10);
    $pdf->SetX($margenIzquierdo);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(100, 10, 'Total de Inscripciones: ' . $totalInscripciones, 0, 0, 'L');

    // Fecha generación
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell($anchoTotal - 100, 10, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');

} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'No hay inscripciones registradas.', 0, 1, 'C');
}

$conexion->close();
$pdf->Output('I', 'lista_inscribir.pdf');
?>