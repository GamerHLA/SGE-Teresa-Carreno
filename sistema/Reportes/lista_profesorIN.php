<?php
// Evitar que se envíe cualquier output antes del PDF
ob_start();

require_once __DIR__ . '/../../fpdf/fpdf.php';

class PDF extends FPDF {
    
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
        $this->Cell(0, 6, 'Lista Completa de Inactivos', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
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
    
    // Función para crear una tarjeta de profesor más compacta
    function ProfesorCard($data) {
        $x = 15;
        $y = $this->GetY();
        $cardWidth = 186;
        $cardHeight = 42; // Reducido para más profesores por página
        
        // Verificar si necesitamos nueva página
        if($y + $cardHeight > 250) {
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
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $nombreCompleto = utf8_decode('#' . $data['nro'] . ' - ' . $data['nombre'] . ' ' . $data['apellido']);
        $this->Cell(0, 4, $nombreCompleto, 0, 1);
        
        // Línea separadora
        $this->SetDrawColor(200, 200, 200);
        $this->Line($contentX, $contentY + 5, $x + $cardWidth - 5, $contentY + 5);
        
        // Primera columna de información
        $col1X = $contentX;
        $col2X = $contentX + 90;
        $infoY = $contentY + 7;
        
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(0, 0, 0);
        
        // Cédula
        $this->SetXY($col1X, $infoY);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(22, 3.5, utf8_decode('Cédula:'), 0, 0);
        $this->SetFont('Arial', '', 7);
        $cedula = $data['nacionalidad'] ? $data['nacionalidad'] . '-' . $data['cedula'] : $data['cedula'];
        $this->Cell(0, 3.5, $cedula, 0, 1);
        
        // Teléfono
        $this->SetXY($col1X, $infoY + 4);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(22, 3.5, utf8_decode('Teléfono:'), 0, 0);
        $this->SetFont('Arial', '', 7);
        $telefono = $data['telefono'];
        if (strlen($telefono) == 11) {
            $telefono = substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        }
        $this->Cell(0, 3.5, $telefono, 0, 1);
        
        // Correo
        $this->SetXY($col1X, $infoY + 8);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(22, 3.5, 'Correo:', 0, 0);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 3.5, utf8_decode($data['correo']), 0, 1);
        
        // Nivel de Estudio
        $this->SetXY($col1X, $infoY + 12);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(30, 3.5, 'Nivel de Estudio:', 0, 0);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 3.5, utf8_decode($data['nivel_estudio']), 0, 1);
        
        // Segunda columna
        // Grado/Sección
        $this->SetXY($col2X, $infoY);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(28, 3.5, utf8_decode('Grado/Sección:'), 0, 0);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 3.5, utf8_decode($data['grado_seccion']), 0, 1);
        
        // Es Representante
        $this->SetXY($col2X, $infoY + 4);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(28, 3.5, 'Representante:', 0, 0);
        $this->SetFont('Arial', '', 7);
        
        // Badge para representante
        if($data['es_representante'] == 'Si') {
            $this->SetFillColor(76, 175, 80);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(10, 3.5, utf8_decode('Sí'), 0, 0, 'C', true);
        } else {
            $this->SetFillColor(200, 200, 200);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(10, 3.5, 'No', 0, 0, 'C', true);
        }
        $this->SetTextColor(0, 0, 0);
        $this->Ln();
        
        // Dirección (ahora con MultiCell para múltiples líneas)
        $this->SetXY($col2X, $infoY + 8);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell(28, 3.5, utf8_decode('Dirección:'), 0, 0);
        $this->SetXY($col2X + 28, $infoY + 8);
        $this->SetFont('Arial', '', 6.5);
        $direccionText = utf8_decode($data['direccion']);
        // Usar MultiCell para permitir múltiples líneas
        $this->MultiCell(60, 2.5, $direccionText, 0, 'L');
        
        // Mover Y para la siguiente tarjeta
        $this->SetY($y + $cardHeight + 2);
    }
}

// Limpiar cualquier output previo
ob_end_clean();

$pdf = new PDF('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

require_once __DIR__ . '/../includes/config.php';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

$conexion->set_charset("utf8");

$sql = "SELECT 
            pr.*,
            n.codigo as nacionalidad,
            e.estado as nombre_estado,
            c.ciudad as nombre_ciudad,
            m.municipio as nombre_municipio,
            p.parroquia as nombre_parroquia,
            g.grado,
            s.seccion,
            CASE WHEN r.representantes_id IS NOT NULL THEN 'Si' ELSE 'No' END as es_representante
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
        WHERE pr.estatus = 2
        ORDER BY pr.profesor_id ASC";

$result = $conexion->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conexion->error);
}

$pdf->SetY(60);
$totalProfesores = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $direccion = $row['direccion'] ?? '';
        $partes_geo = [];
        if(!empty($row['nombre_parroquia'])) $partes_geo[] = $row['nombre_parroquia'];
        if(!empty($row['nombre_municipio'])) $partes_geo[] = $row['nombre_municipio'];
        if(!empty($row['nombre_estado'])) $partes_geo[] = $row['nombre_estado'];
        
        if(!empty($partes_geo)) {
            if(!empty($direccion)) $direccion .= ', ';
            $direccion .= implode(', ', $partes_geo);
        }

        $nacionalidad = isset($row['nacionalidad']) ? $row['nacionalidad'] : ''; 
        
        $gradoSeccion = '';
        if (!empty($row['grado']) && !empty($row['seccion'])) {
            $gradoSeccion = $row['grado'] . '° - Sección ' . $row['seccion'];
        } else {
            $gradoSeccion = 'No asignado';
        }
        
        $nivelEstudio = $row['nivel_est'] ?? 'No especificado';
        
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
    
    // Total
    $pdf->Ln(5);
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(100, 10, 'Total de Profesores: ' . $totalProfesores, 0, 0, 'L');

    // Fecha
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(86, 10, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');

} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'No hay profesores registrados.', 0, 1, 'C');
}

$conexion->close();

// Limpiar buffer antes de output
ob_end_clean();
$pdf->Output('lista_profesores_inactivos.pdf', 'I');
?>