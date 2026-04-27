<?php
require_once __DIR__ . '/../../fpdf/fpdf.php';

class PDF extends FPDF {
    var $widths;
    var $aligns;
    var $tableYOffset = 0;

    function SetTableYOffset($offset) {
        $this->tableYOffset = $offset;
    }

    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    function Row($data) {
        $nb = 0;
        for($i=0;$i<count($data);$i++)
            $nb = max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        $h = 6*$nb;
        $this->CheckPageBreak($h);
        
        $this->SetFont('Arial', '', 7);
        
        for($i=0;$i<count($data);$i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x,$y,$w,$h);
            $this->MultiCell($w,6,$data[$i],0,$a);
            $this->SetXY($x+$w,$y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w,$txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep = $i;
            $l += $cw[$c];
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j)
                        $i++;
                }
                else
                    $i = $sep+1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            }
            else
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

        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'LISTA DE REPRESENTANTES', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 6, 'Lista Completa de Inactivos', 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(30, 70, 150);
        $this->SetTextColor(255, 255, 255);
        
        // Nuevos anchos: Total 196 (aprox)
        // #, Cédula, Nombre, Apellido, Alumno, Parentesco, Dirección, Teléfono, Correo
        $w = array(12, 18, 19, 19, 28, 18, 33, 22, 27); 
        $anchoTotal = array_sum($w);
        $margenIzquierdo = (216 - $anchoTotal) / 2;
        $this->SetX($margenIzquierdo);
        
        $headers = array('#', utf8_decode('Cédula'), 'Nombre', 'Apellido', 'Alumno', 'Parentesco', utf8_decode('Dirección'), utf8_decode('Teléfono'), 'Correo');
        for($i=0; $i<count($headers); $i++) {
            $this->Cell($w[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
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
}

$pdf = new PDF('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 30);

$offsetTabla = 60;
$pdf->SetTableYOffset($offsetTabla);

$pdf->AddPage();

require_once __DIR__ . '/../includes/config.php';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

$conexion->set_charset("utf8");

$sql = "SELECT 
            r.*,
            n.codigo as nacionalidad,
            e.estado as nombre_estado,
            c.ciudad as nombre_ciudad,
            m.municipio as nombre_municipio,
            p.parroquia as nombre_parroquia,
            GROUP_CONCAT(DISTINCT CONCAT(a.nombre, ' ', a.apellido) SEPARATOR \"\n\") as alumno_nombre_completo,
            GROUP_CONCAT(DISTINCT par.parentesco SEPARATOR \"\n\") as parentesco_list
        FROM representantes r
        LEFT JOIN nacionalidades n ON r.id_nacionalidades = n.id
        LEFT JOIN estados e ON r.id_estado = e.id_estado
        LEFT JOIN ciudades c ON r.id_ciudad = c.id_ciudad
        LEFT JOIN municipios m ON r.id_municipio = m.id_municipio
        LEFT JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        LEFT JOIN alumno_representante ar ON r.representantes_id = ar.representante_id AND ar.estatus = 1
        LEFT JOIN parentesco par ON ar.parentesco_id = par.id_parentesco
        LEFT JOIN alumnos a ON ar.alumno_id = a.alumno_id AND a.estatus != 0
        WHERE r.estatus = 2
        GROUP BY r.representantes_id
        ORDER BY r.apellido, r.nombre";

$result = $conexion->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conexion->error);
}

// Mismos anchos que en Header
$w = array(12, 18, 19, 19, 28, 18, 33, 22, 27); 
$pdf->SetWidths($w);
$pdf->SetAligns(array('C','C','L','L','L','C','L','C','L'));

$anchoTotal = array_sum($w);
$margenIzquierdo = (216 - $anchoTotal) / 2;

$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(0, 0, 0);
$totalRepresentantes = 0;
$contador = 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->SetX($margenIzquierdo);
        
        $direccion = '';
        $partes_geo = [];
        if(!empty($row['nombre_parroquia'])) $partes_geo[] = $row['nombre_parroquia'];
        if(!empty($row['nombre_municipio'])) $partes_geo[] = $row['nombre_municipio'];
        if(!empty($row['nombre_estado'])) $partes_geo[] = $row['nombre_estado'];
        
        if(!empty($partes_geo)) {
            $direccion = implode(', ', $partes_geo);
        }

        $nacionalidad = isset($row['nacionalidad']) ? $row['nacionalidad'] : ''; 
        $cedula_completa = $nacionalidad . '-' . $row['cedula'];
        
        $alumno = !empty($row['alumno_nombre_completo']) ? $row['alumno_nombre_completo'] : 'No asignado';
        $parentesco = !empty($row['parentesco_list']) ? $row['parentesco_list'] : 'N/A';
        
        $data = array(
            $contador,
            $cedula_completa,
            utf8_decode($row['nombre']),
            utf8_decode($row['apellido']),
            utf8_decode($alumno),
            utf8_decode($parentesco),
            utf8_decode($direccion),
            $row['telefono'],
            utf8_decode($row['correo'])
        );
        
        $pdf->Row($data);

        // Línea de separación para mejor legibilidad
        $pdf->SetX($margenIzquierdo);
        $pdf->Cell($anchoTotal, 0, '', 'T');
        $pdf->Ln(0.1); 

        $totalRepresentantes++;
        $contador++;
    }
    
    // Total
    $pdf->Ln(10);
    $pdf->SetX($margenIzquierdo);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(100, 10, 'Total de Representantes: ' . $totalRepresentantes, 0, 0, 'L');

    // Fecha
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell($anchoTotal - 100, 10, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');

} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'No hay representantes registrados.', 0, 1, 'C');
}

$conexion->close();
$pdf->Output('lista_representantes_inactivos.pdf', 'I');
?>