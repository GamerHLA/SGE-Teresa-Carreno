<?php
// Evitar que se envíe cualquier output antes del PDF
ob_start();

require_once __DIR__ . '/../../fpdf/fpdf.php';

class PDF extends FPDF
{
    var $tableYOffset = 0;

    function SetTableYOffset($offset)
    {
        $this->tableYOffset = $offset;
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

        $this->SetY(26);
        $this->SetDrawColor(0, 0, 200);
        $this->SetLineWidth(0.5);
        $this->Line(10, 25, 200, 25);
        $this->Ln(10);

        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'REGISTRO DE PROFESORES', 0, 1, 'C');
        $this->SetFont('Arial', '', 11);

        // Título dinámico basado en filtros
        global $filtroEstatus, $filtroSexo, $filtroEsRepresentante;
        $titulo = 'Lista de Profesores';
        if ($filtroEstatus == '1') {
            $titulo .= ' Activos';
        } elseif ($filtroEstatus == '2') {
            $titulo .= ' Inactivos';
        }
        if ($filtroSexo == 'M') {
            $titulo .= ' (Masculino)';
        } elseif ($filtroSexo == 'F') {
            $titulo .= ' (Femenino)';
        }
        if ($filtroEsRepresentante == '1') {
            $titulo .= ' - Son Representantes';
        } elseif ($filtroEsRepresentante == '0') {
            $titulo .= ' - No son Representantes';
        }

        $this->Cell(0, 6, utf8_decode($titulo), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(0, 0, 0);

        $footerText = utf8_decode("U.E.D. Teresa Carreño Dirección:Calle Carbonell, Lomas de Urdaneta,frente al bloque 18 pequeño, Caracas, Municipio Libertador, Distrito Capital / Tlfs: 0212-8710661 / Correo: uedteresacarreno@gmail.com");

        $this->SetX(10);
        $this->MultiCell(196, 5, $footerText, 1, 'C');

        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }

    // Función para crear una tarjeta de profesor más compacta y ajustada
    function ProfesorCard($data)
    {
        $x = 15;
        $y = $this->GetY();
        $cardWidth = 186;
        $cardHeight = 32; // Reducido agresivamente a 32mm

        // Verificar espacio disponible (considerando margen inferior manual ~20mm)
        if ($y + $cardHeight > 255) { // Un poco más permisivo con el margen inferior (footer está en -25)
            $this->AddPage();
            $y = $this->GetY();
        }

        // Fondo de la tarjeta
        $this->SetFillColor(245, 247, 250);
        $this->Rect($x, $y, $cardWidth, $cardHeight, 'F');

        // Borde de la tarjeta
        $this->SetDrawColor(70, 130, 180);
        $this->SetLineWidth(0.5);
        $this->Rect($x, $y, $cardWidth, $cardHeight);

        // Barra lateral de color
        $this->SetFillColor(70, 130, 180);
        $this->Rect($x, $y, 3, $cardHeight, 'F');

        // Contenido de la tarjeta
        $contentX = $x + 6;
        $contentY = $y + 2;

        // Nombre del profesor (destacado) con numeración
        $this->SetXY($contentX, $contentY);
        $this->SetFont('Arial', 'B', 9.2); // Aumentado
        $this->SetTextColor(0, 0, 0);
        $nombreCompleto = utf8_decode('#' . $data['nro'] . ' - ' . $data['nombre'] . ' ' . $data['apellido']);
        $this->Cell(0, 4, $nombreCompleto, 0, 1);

        // Línea separadora
        $this->SetDrawColor(200, 200, 200);
        $this->Line($contentX, $contentY + 4.5, $x + $cardWidth - 5, $contentY + 4.5);

        // Primera columna de información
        $col1X = $contentX;
        $col2X = $contentX + 90;
        $infoY = $contentY + 5.5; // Subido para compactar
        $lineHeight = 3.3; // Aumentado ligeramente para legibilidad

        $this->SetFont('Arial', '', 7.2); // Aumentado
        $this->SetTextColor(0, 0, 0);

        // Cédula
        $this->SetXY($col1X, $infoY);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(22, $lineHeight, utf8_decode('Cédula:'), 0, 0);
        $this->SetFont('Arial', '', 7.2);
        $cedula = $data['nacionalidad'] ? $data['nacionalidad'] . '-' . $data['cedula'] : $data['cedula'];
        $this->Cell(0, $lineHeight, $cedula, 0, 1);

        // Teléfono
        $this->SetXY($col1X, $infoY + 4);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(22, $lineHeight, utf8_decode('Teléfono:'), 0, 0);
        $this->SetFont('Arial', '', 7.2);
        $telefono = $data['telefono'];
        if (strlen($telefono) == 11) {
            $telefono = substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        }
        $this->Cell(0, $lineHeight, $telefono, 0, 1);

        // Correo
        $this->SetXY($col1X, $infoY + 8);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(22, $lineHeight, 'Correo:', 0, 0);
        $this->SetFont('Arial', '', 7.2);
        $this->Cell(0, $lineHeight, utf8_decode($data['correo']), 0, 1);

        // Nivel de Estudio
        $this->SetXY($col1X, $infoY + 12);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(28, $lineHeight, 'Nivel de Estudio:', 0, 0);
        $this->SetFont('Arial', '', 7.2);
        // MultiCell para que si es muy largo (incluyendo el nivel) no se corte
        $this->MultiCell(140, 3, utf8_decode($data['nivel_estudio']), 0, 'L');

        // Segunda columna
        // Grado/Sección
        $this->SetXY($col2X, $infoY);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(28, $lineHeight, utf8_decode('Grado/Sección:'), 0, 0);
        $this->SetFont('Arial', '', 7.2);
        $this->Cell(0, $lineHeight, utf8_decode($data['grado_seccion']), 0, 1);

        // Es Representante
        $this->SetXY($col2X, $infoY + 4);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(28, $lineHeight, 'Representante:', 0, 0);
        $this->SetFont('Arial', '', 7.2);

        // Badge para representante
        if ($data['es_representante'] == 'Si') {
            $this->SetFillColor(76, 175, 80);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(10, 3, utf8_decode('Sí'), 0, 0, 'C', true);
        } else {
            $this->SetFillColor(200, 200, 200);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(10, 3, 'No', 0, 0, 'C', true);
        }
        $this->SetTextColor(0, 0, 0);

        // Dirección (ahora con MultiCell para múltiples líneas)
        $this->SetXY($col2X, $infoY + 8);
        $this->SetFont('Arial', 'B', 7.2);
        $this->Cell(28, $lineHeight, utf8_decode('Dirección:'), 0, 0);
        $this->SetXY($col2X + 28, $infoY + 8);
        $this->SetFont('Arial', '', 6.8);
        $direccionText = utf8_decode($data['direccion']);
        // Usar MultiCell para permitir múltiples líneas, maxheight restricted
        $this->MultiCell(60, 3, $direccionText, 0, 'L');

        // Mover Y para la siguiente tarjeta (espacio reducido entre tarjetas)
        $this->SetY($y + $cardHeight + 1); // Gap reducido a 1mm
    }
}

// Limpiar cualquier output previo
ob_end_clean();

$pdf = new PDF('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->AddPage();

require_once __DIR__ . '/../includes/config.php';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

$conexion->set_charset("utf8");

// Obtener filtros de URL
$filtroSexo = isset($_GET['sexo']) ? $_GET['sexo'] : 'all';
$filtroEstatus = isset($_GET['estatus']) ? $_GET['estatus'] : 'all';
$filtroEsRepresentante = isset($_GET['es_representante']) ? $_GET['es_representante'] : 'all';

// Construir WHERE clause
$whereClauses = array();

// Filtro de estatus
if ($filtroEstatus !== 'all') {
    $whereClauses[] = "pr.estatus = " . intval($filtroEstatus);
} else {
    $whereClauses[] = "pr.estatus != 0"; // Excluir eliminados
}

// Filtro de sexo
if ($filtroSexo !== 'all') {
    $whereClauses[] = "pr.sexo = '" . $conexion->real_escape_string($filtroSexo) . "'";
}

// Filtro de es_representante
if ($filtroEsRepresentante !== 'all') {
    if ($filtroEsRepresentante == '1') {
        $whereClauses[] = "r.representantes_id IS NOT NULL";
    } else {
        $whereClauses[] = "r.representantes_id IS NULL";
    }
}

$whereSQL = implode(' AND ', $whereClauses);

$sql = "SELECT 
            pr.*,
            n.codigo as nacionalidad,
            e.estado as nombre_estado,
            c.ciudad as nombre_ciudad,
            m.municipio as nombre_municipio,
            p.parroquia as nombre_parroquia,
            CASE WHEN r.representantes_id IS NOT NULL THEN 'Si' ELSE 'No' END as es_representante,
            GROUP_CONCAT(CONCAT(g.grado, '° - Sección ', s.seccion) SEPARATOR ', ') as cursos_asignados
        FROM profesor pr
        LEFT JOIN nacionalidades n ON pr.id_nacionalidades = n.id
        LEFT JOIN estados e ON pr.id_estado = e.id_estado
        LEFT JOIN ciudades c ON pr.id_ciudad = c.id_ciudad
        LEFT JOIN municipios m ON pr.id_municipio = m.id_municipio
        LEFT JOIN parroquias p ON pr.id_parroquia = p.id_parroquia
        LEFT JOIN curso cu ON pr.profesor_id = cu.profesor_id AND cu.estatusC = 1
        LEFT JOIN grados g ON cu.grados_id = g.id_grado
        LEFT JOIN seccion s ON cu.seccion_id = s.id_seccion
        LEFT JOIN representantes r ON pr.cedula = r.cedula AND r.estatus = 1
        WHERE $whereSQL
        GROUP BY pr.profesor_id
        ORDER BY pr.profesor_id ASC";

$result = $conexion->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conexion->error);
}

// Desactivar salto de página automático para manejarlo manualmente
$pdf->SetAutoPageBreak(false);

$pdf->SetY(60);
$totalProfesores = 0;

if ($result->num_rows > 0) {
    $stmtNiveles = $conexion->prepare("SELECT ee.nombre as nivel_estudio, ne.nombre as categoria 
                                           FROM profesor_niveles_estudio pne
                                           JOIN especialidades_estudio ee ON pne.especializacion_id = ee.id
                                           JOIN niveles_estudio ne ON ee.nivel_id = ne.id
                                           WHERE pne.profesor_id = ?
                                           ORDER BY ne.id, ee.nombre");

    while ($row = $result->fetch_assoc()) {
        $direccion = $row['direccion'] ?? '';
        $partes_geo = [];
        if (!empty($row['nombre_parroquia']))
            $partes_geo[] = $row['nombre_parroquia'];
        if (!empty($row['nombre_municipio']))
            $partes_geo[] = $row['nombre_municipio'];
        if (!empty($row['nombre_estado']))
            $partes_geo[] = $row['nombre_estado'];

        if (!empty($partes_geo)) {
            if (!empty($direccion))
                $direccion .= ', ';
            $direccion .= implode(', ', $partes_geo);
        }

        $nacionalidad = isset($row['nacionalidad']) ? $row['nacionalidad'] : '';

        $gradoSeccion = !empty($row['cursos_asignados']) ? $row['cursos_asignados'] : 'No asignado';

        // Fetch study levels correctly
        $nivelEstudio = 'No especificado';
        $stmtNiveles->bind_param("i", $row['profesor_id']);
        $stmtNiveles->execute();
        $resNiveles = $stmtNiveles->get_result();

        $nivelesGrouped = [];
        while ($nivLevel = $resNiveles->fetch_assoc()) {
            $cat = $nivLevel['categoria'];
            $esp = $nivLevel['nivel_estudio'];
            if (!isset($nivelesGrouped[$cat])) {
                $nivelesGrouped[$cat] = [];
            }
            $nivelesGrouped[$cat][] = $esp;
        }

        if (!empty($nivelesGrouped)) {
            $partesNivel = [];
            foreach ($nivelesGrouped as $cat => $especialidades) {
                $partesNivel[] = $cat . ": " . implode(', ', $especialidades);
            }
            $nivelEstudio = implode(' / ', $partesNivel);
        } else {
            // Fallback to old field if empty (optional, but staying safe)
            $nivelEstudio = !empty($row['nivel_est']) ? $row['nivel_est'] : 'No especificado';
        }

        $data = array(
            'nro' => $totalProfesores + 1,
            'nacionalidad' => $nacionalidad,
            'cedula' => $row['cedula'],
            'nombre' => $row['nombre'],
            'apellido' => $row['apellido'],
            'grado_seccion' => $gradoSeccion,
            'telefono' => $row['telefono'],
            'correo' => $row['correo'],
            'nivel_estudio' => $nivelEstudio,
            'es_representante' => $row['es_representante'],
            'direccion' => $direccion
        );

        $pdf->ProfesorCard($data);
        $totalProfesores++;
    }
    $stmtNiveles->close();
} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'No hay profesores registrados.', 0, 1, 'C');
}

// Bloque Final: Total + Fecha
// Calculamos espacio necesario: 3 (Ln) + 6 (Total) + 3 (Ln) + 6 (Fecha) = ~18mm
// Límite seguro inferior: 255mm

if ($pdf->GetY() + 20 > 255) {
    $pdf->AddPage();
    $pdf->SetY(40); // Reset Y after header
}

// Total
if ($result->num_rows > 0) {
    $pdf->Ln(5);
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(100, 6, 'Total de Profesores: ' . $totalProfesores, 0, 0, 'L');

    // Fecha
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(86, 6, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');
}

$conexion->close();

// Limpiar buffer antes de output
ob_end_clean();
$pdf->Output('lista_profesores.pdf', 'I');
?>