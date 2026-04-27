<?php
/**
 * LISTA_ALUMNO.PHP
 * ================
 * 
 * Generador de PDF para lista completa de alumnos.
 * 
 * FUNCIONALIDADES:
 * - Genera reporte PDF con listado de alumnos
 * - Filtros: sexo (M/F/todos), estatus (activo/inactivo/todos)
 * - Columnas: #, Cédula, Nombre y Apellido, Edad, F. Nac, Representante, Parentesco, Dirección
 * - Título dinámico según filtros aplicados
 * - Contador total de estudiantes
 * - Fecha y hora de generación
 * - Soporte para múltiples páginas con encabezado/pie repetido
 * 
 * PARÁMETROS:
 * - GET['sexo']: Filtro por sexo (M/F/all)
 * - GET['estatus']: Filtro por estatus (1=activo, 2=inactivo, all=todos)
 * 
 * DEPENDENCIAS:
 * - FPDF (generación de PDF)
 * - includes/config.php (conexión BD)
 */
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
        $h = 5*$nb;
        $this->CheckPageBreak($h);
        
        $this->SetFont('Arial', '', 8);
        
        for($i=0;$i<count($data);$i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x,$y,$w,$h);
            $this->MultiCell($w,5,$data[$i],0,$a);
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
        $this->Cell(0, 10, 'REGISTRO ESCOLAR', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        
        // Título dinámico basado en filtros
        global $filtroEstatus, $filtroSexo;
        $titulo = 'Lista Completa de Alumnos';
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
        
        $this->Cell(0, 6, utf8_decode($titulo), 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(30, 70, 150);
        $this->SetTextColor(255, 255, 255);
        
        // Nuevos anchos: Total 196
        // #, Cédula, Nombre y Apellido, Edad, F. Nac, Representante, Parentesco, Dirección
        $w = array(12, 24, 38, 11, 19, 34, 20, 38);
        
        $anchoTotal = array_sum($w);
        $margenIzquierdo = (216 - $anchoTotal) / 2;
        $this->SetX($margenIzquierdo);
        
        $headers = array('#', utf8_decode('Cédula'), 'Nombre y Apellido', 'Edad', 'F. Nac', 'Representante', 'Parentesco', utf8_decode('Dirección'));
        for($i=0; $i<count($headers); $i++) {
            $this->Cell($w[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }
    
    function Footer() {
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

// Obtener filtros de URL
$filtroSexo = isset($_GET['sexo']) ? $_GET['sexo'] : 'all';
$filtroEstatus = isset($_GET['estatus']) ? $_GET['estatus'] : 'all';

// Construir WHERE clause
$whereClauses = array();

// Filtro de estatus
if ($filtroEstatus !== 'all') {
    $whereClauses[] = "a.estatus = " . intval($filtroEstatus);
} else {
    $whereClauses[] = "a.estatus != 0"; // Excluir eliminados
}

// Filtro de sexo
if ($filtroSexo !== 'all') {
    $whereClauses[] = "a.sexo = '" . $conexion->real_escape_string($filtroSexo) . "'";
}

$whereSQL = implode(' AND ', $whereClauses);

$sql = "SELECT 
            a.cedula, 
            a.nombre, 
            a.apellido,
            a.fecha_nac,
            a.edad,
            a.estatus,
            n.codigo as nacionalidad,
            r.nombre as rep_nombre,
            r.apellido as rep_apellido,
            par.parentesco,
            e.estado as nombre_estado,
            c.ciudad as nombre_ciudad,
            m.municipio as nombre_municipio,
            p.parroquia as nombre_parroquia
        FROM alumnos a
        LEFT JOIN nacionalidades n ON a.id_nacionalidades = n.id
        LEFT JOIN alumno_representante ar ON a.alumno_id = ar.alumno_id AND ar.estatus = 1 AND ar.es_principal = 1
        LEFT JOIN representantes r ON ar.representante_id = r.representantes_id AND r.estatus = 1
        LEFT JOIN parentesco par ON ar.parentesco_id = par.id_parentesco
        LEFT JOIN estados e ON a.id_estado = e.id_estado
        LEFT JOIN ciudades c ON a.id_ciudad = c.id_ciudad
        LEFT JOIN municipios m ON a.id_municipio = m.id_municipio
        LEFT JOIN parroquias p ON a.id_parroquia = p.id_parroquia
        WHERE $whereSQL
        ORDER BY a.apellido, a.nombre";

$result = $conexion->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conexion->error);
}

// Mismos anchos que en Header
$w = array(12, 24, 38, 11, 19, 34, 20, 38);
$pdf->SetWidths($w);
$pdf->SetAligns(array('C','C','L','C','C','L','C','L'));

$anchoTotal = array_sum($w);
$margenIzquierdo = (216 - $anchoTotal) / 2;

$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(0, 0, 0);
$totalEstudiantes = 0;
$contador = 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->SetX($margenIzquierdo);
        
        $representante = (!empty($row['rep_nombre'])) ? $row['rep_nombre'] . ' ' . $row['rep_apellido'] : 'No asignado';
        $parentesco = (!empty($row['parentesco'])) ? $row['parentesco'] : 'N/A';
        
        $direccion = '';
        $partes_geo = [];
        if(!empty($row['nombre_parroquia'])) $partes_geo[] = $row['nombre_parroquia'];
        if(!empty($row['nombre_municipio'])) $partes_geo[] = $row['nombre_municipio'];
        if(!empty($row['nombre_estado'])) $partes_geo[] = $row['nombre_estado'];
        
        if(!empty($partes_geo)) {
            $direccion = implode(', ', $partes_geo);
        }

        $cedula_completa = $row['cedula'];
        if (!empty($row['nacionalidad'])) {
            $cedula_completa = $row['nacionalidad'] . '-' . $row['cedula'];
        }

        $nombre_completo = $row['nombre'] . ' ' . $row['apellido'];

        $data = array(
            $contador,
            $cedula_completa,
            utf8_decode($nombre_completo),
            $row['edad'],
            date('d/m/Y', strtotime($row['fecha_nac'])),
            utf8_decode($representante),
            utf8_decode($parentesco),
            utf8_decode($direccion)
        );
        
        $pdf->Row($data);
        $totalEstudiantes++;
        $contador++;
    }
    
    $pdf->Ln(10);
    $pdf->SetX($margenIzquierdo);
    
    // Total a la izquierda y en negrita
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(100, 10, 'Total de Estudiantes: ' . $totalEstudiantes, 0, 0, 'L');

    // Fecha a la derecha en cursiva
    date_default_timezone_set('America/Caracas');
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell($anchoTotal - 100, 10, 'Reporte generado el: ' . date('d/m/Y h:i:s A'), 0, 1, 'R');

} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'No hay alumnos registrados.', 0, 1, 'C');
}

$conexion->close();
$pdf->Output('lista_completa_alumnos.pdf', 'I');
?>