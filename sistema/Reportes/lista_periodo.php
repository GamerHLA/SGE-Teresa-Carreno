<?php
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
        $this->Cell(0, 10, 'LISTA DE PERIODOS ESCOLARES', 0, 1, 'C');
        $this->SetFont('Arial', '', 11);
        
        // Título dinámico basado en filtro
        global $filtroAnio;
        $titulo = 'Registro de Periodos Escolares';
        if ($filtroAnio && $filtroAnio !== 'all') {
            global $periodoSeleccionado;
            $titulo = 'Periodo Escolar: ' . $periodoSeleccionado;
        }
        
        $this->Cell(0, 6, utf8_decode($titulo), 0, 1, 'C');
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
    
    function PeriodoCard($data) {
        $x = 15;
        $y = $this->GetY();
        $cardWidth = 186;
        $cardHeight = 35;
        
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
        
        // Barra lateral de color según estatus
        if($data['estatus'] == 'Activo') {
            $this->SetFillColor(76, 175, 80); // Verde
        } else {
            $this->SetFillColor(200, 200, 200); // Gris
        }
        $this->Rect($x, $y, 3, $cardHeight, 'F');
        
        // Contenido de la tarjeta
        $contentX = $x + 6;
        $contentY = $y + 3;
        
        // Nombre del periodo (destacado)
        $this->SetXY($contentX, $contentY);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $periodoNombre = utf8_decode('Periodo Escolar ' . $data['anio_inicio'] . ' - ' . $data['anio_fin']);
        $this->Cell(0, 5, $periodoNombre, 0, 1);
        
        // Línea separadora
        $this->SetDrawColor(200, 200, 200);
        $this->Line($contentX, $contentY + 6, $x + $cardWidth - 5, $contentY + 6);
        
        // Información en dos columnas
        $col1X = $contentX;
        $col2X = $contentX + 90;
        $infoY = $contentY + 9;
        
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        
        // Columna 1
        $this->SetXY($col1X, $infoY);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 4, utf8_decode('Año Inicio:'), 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, $data['fecha_inicio'], 0, 1);
        
        $this->SetXY($col1X, $infoY + 5);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(30, 4, utf8_decode('Año Fin:'), 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, $data['fecha_fin'], 0, 1);
        
        // Columna 2
        $this->SetXY($col2X, $infoY);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 4, 'Estatus:', 0, 0);
        $this->SetFont('Arial', '', 9);
        
        // Badge para estatus
        if($data['estatus'] == 'Activo') {
            $this->SetFillColor(76, 175, 80);
            $this->SetTextColor(255, 255, 255);
            $this->Cell(18, 4, 'Activo', 0, 0, 'C', true);
        } else {
            $this->SetFillColor(200, 200, 200);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(18, 4, 'Inactivo', 0, 0, 'C', true);
        }
        $this->SetTextColor(0, 0, 0);
        
        // Mover Y para la siguiente tarjeta
        $this->SetY($y + $cardHeight + 3);
    }
}

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

// Obtener filtro de URL
$filtroAnio = isset($_GET['periodo_id']) ? $_GET['periodo_id'] : 'all';
$periodoSeleccionado = '';

// Construir WHERE clause
$whereClauses = array();
$whereClauses[] = "estatus != 0"; // Excluir eliminados

if ($filtroAnio !== 'all') {
    $whereClauses[] = "periodo_id = " . intval($filtroAnio);
    
    // Obtener nombre del periodo seleccionado
    $sqlNombre = "SELECT CONCAT(anio_inicio, ' - ', anio_fin) as nombre FROM periodo_escolar WHERE periodo_id = " . intval($filtroAnio);
    $resultNombre = $conexion->query($sqlNombre);
    if ($resultNombre && $resultNombre->num_rows > 0) {
        $rowNombre = $resultNombre->fetch_assoc();
        $periodoSeleccionado = $rowNombre['nombre'];
    }
}

$whereSQL = implode(' AND ', $whereClauses);

$sql = "SELECT 
            periodo_id,
            anio_inicio,
            anio_fin,
            estatus
        FROM periodo_escolar
        WHERE $whereSQL
        ORDER BY anio_inicio DESC, anio_fin DESC";

$result = $conexion->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conexion->error);
}

$pdf->SetY(60);
$totalPeriodos = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data = array(
            'anio_inicio' => $row['anio_inicio'],
            'anio_fin' => $row['anio_fin'],
            'fecha_inicio' => $row['anio_inicio'], // Usar año como fecha
            'fecha_fin' => $row['anio_fin'], // Usar año como fecha
            'estatus' => ($row['estatus'] == 1) ? 'Activo' : 'Inactivo'
        );
        
        $pdf->PeriodoCard($data);
        $totalPeriodos++;
    }
    
    // Total
    $pdf->Ln(5);
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(100, 10, 'Total de Periodos: ' . $totalPeriodos, 0, 0, 'L');

    // Fecha
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(86, 10, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');

} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'No hay periodos escolares registrados.', 0, 1, 'C');
}

$conexion->close();
$pdf->Output('lista_periodos_escolares.pdf', 'I');
?>