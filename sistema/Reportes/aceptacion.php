<?php
require_once __DIR__ . '/../../fpdf/fpdf.php';
require_once __DIR__ . '/../../phpqrcode/qrlib.php';

class PDF extends FPDF
{
    public $tableYOffset = 0;
    private $qrCodeDir = __DIR__ . '/../../temp_qr/';

    function __construct()
    {
        parent::__construct();
        if (!file_exists($this->qrCodeDir)) {
            mkdir($this->qrCodeDir, 0777, true);
        }
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
    }

    function Footer()
    {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(0, 0, 0);

        $footerText = utf8_decode("U.E.D. Teresa Carreño Dirección:Calle Carbonell, Lomas de Urdaneta,frente al bloque 18 pequeño, Caracas, Municipio Libertador, Distrito Capital / Tlfs: 0212-8710661 / Correo: uedteresacarreno@gmail.com");

        $this->SetX(10);
        $this->MultiCell(190, 5, $footerText, 1, 'C');

        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }

    public function getDirectorData($pdo)
    {
        $sql = "SELECT 
                p.nombre as nombre_profesor,
                p.apellido as apellido_profesor,
                p.cedula,
                n.codigo as nacionalidad
            FROM profesor p
            LEFT JOIN nacionalidades n ON p.id_nacionalidades = n.id
            WHERE p.es_director = 1 AND p.estatus = 1
            LIMIT 1";

        try {
            $query = $pdo->prepare($sql);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'nombre_profesor' => 'Nombre del Director',
                    'apellido_profesor' => 'Apellido del Director',
                    'cedula' => '0000000',
                    'nacionalidad' => 'V'
                ];
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en getDirectorData: " . $e->getMessage());
            return [
                'nombre_profesor' => 'Nombre del Director',
                'apellido_profesor' => 'Apellido del Director',
                'cedula' => '0000000',
                'nacionalidad' => 'V'
            ];
        }
    }

    public function generateQRCode($alumnoData, $directorData, $periodo)
    {
        $qrContent = "CONSTANCIA DE ACEPTACIÓN DE CUPO\n";
        $qrContent .= "===============================\n";
        $qrContent .= "Alumno(a): " . $alumnoData['nombre'] . " " . $alumnoData['apellido'] . "\n";
        $qrContent .= "Cédula del Alumno(a): " . $alumnoData['nacionalidad'] . "-" . $alumnoData['cedula'] . "\n";
        $qrContent .= "Grado: " . $alumnoData['grado'] . "°\n";
        $qrContent .= "Periodo: " . $periodo . "\n";
        $qrContent .= "Director(a): " . $directorData['nombre_profesor'] . " " . $directorData['apellido_profesor'] . "\n";
        $qrContent .= "Cédula del Director(a): " . $directorData['nacionalidad'] . "-" . $directorData['cedula'] . "\n";
        $qrContent .= "Fecha: " . date('d/m/Y') . "\n";
        $qrContent .= "U.E.D. Teresa Carreño";

        $qrFilename = $this->qrCodeDir . 'qr_' . uniqid() . '.png';
        QRcode::png($qrContent, $qrFilename, QR_ECLEVEL_L, 6, 2);
        return $qrFilename;
    }

    public function insertQRFixedPosition($qrFilePath)
    {
        if (!file_exists($qrFilePath)) {
            return false;
        }

        $currentY = $this->GetY();
        $this->Image($qrFilePath, 160, 40, 35, 35);

        $this->SetFont('Arial', 'I', 7);
        $this->SetXY(160, 75);
        $this->Cell(35, 5, utf8_decode('Código de verificación'), 0, 1, 'C');

        $this->SetY($currentY);
        return true;
    }

    public function setContentStartPosition()
    {
        $this->SetY(85);
        return $this->GetY();
    }

    public function cleanupQRFiles()
    {
        $files = glob($this->qrCodeDir . 'qr_*.png');
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    function getMesEspanol($mes)
    {
        $meses = [
            '01' => 'enero',
            '02' => 'febrero',
            '03' => 'marzo',
            '04' => 'abril',
            '05' => 'mayo',
            '06' => 'junio',
            '07' => 'julio',
            '08' => 'agosto',
            '09' => 'septiembre',
            '10' => 'octubre',
            '11' => 'noviembre',
            '12' => 'diciembre'
        ];
        return $meses[$mes] ?? $mes;
    }

    function WriteUTF8($h, $txt)
    {
        $this->Write($h, utf8_decode($txt));
    }

    function CellUTF8($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        parent::Cell($w, $h, utf8_decode($txt), $border, $ln, $align, $fill, $link);
    }

    function MultiCellUTF8($w, $h, $txt, $border = 0, $align = 'J', $fill = false)
    {
        parent::MultiCell($w, $h, utf8_decode($txt), $border, $align, $fill);
    }
}

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../includes/config.php';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

// PDO instance ($pdo) is already created in config.php

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);

$director = $pdf->getDirectorData($pdo);
$id_param = isset($_GET['id']) ? $_GET['id'] : 0;
$qrFiles = [];

if ($id_param === 'all') {
    $sql = "SELECT 
                a.cedula, 
                a.nombre, 
                a.apellido,
                n.codigo as nacionalidad,
                g.grado,
                pe.anio_inicio,
                pe.anio_fin
            FROM alumnos a
            INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id
            INNER JOIN curso c ON i.curso_id = c.curso_id
            INNER JOIN grados g ON c.grados_id = g.id_grado
            INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
            LEFT JOIN nacionalidades n ON a.id_nacionalidades = n.id
            WHERE a.estatus = 1 AND i.estatusI = 1 AND pe.estatus = 1
            ORDER BY g.grado, c.seccion_id, a.apellido, a.nombre";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

} else {
    $id_val = $id_param;
    $is_alumno_id = false;
    $target_id = 0;

    if (strpos($id_val, 'ALU_') === 0) {
        $is_alumno_id = true;
        $target_id = intval(substr($id_val, 4));
    } else {
        $target_id = intval($id_val);
    }

    if ($target_id <= 0) {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(150, 0, 0);
        $pdf->CellUTF8(0, 10, 'ERROR: ID NO VÁLIDO', 0, 1, 'C');
        $pdf->Output();
        exit;
    }

    if ($is_alumno_id) {
        $sql = "SELECT 
                a.cedula, 
                COALESCE(a.nombre, '') as nombre, 
                COALESCE(a.apellido, '') as apellido,
                n.codigo as nacionalidad,
                COALESCE(g.grado, '____') as grado,
                COALESCE(pe.anio_inicio, '____') as anio_inicio,
                COALESCE(pe.anio_fin, '____') as anio_fin
            FROM alumnos a
            INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id AND i.estatusI = 1
            INNER JOIN curso c ON i.curso_id = c.curso_id
            LEFT JOIN grados g ON c.grados_id = g.id_grado
            LEFT JOIN nacionalidades n ON a.id_nacionalidades = n.id
            INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
            WHERE a.alumno_id = ? 
            ORDER BY i.inscripcion_id DESC
            LIMIT 1";
    } else {
        $sql = "SELECT 
                    a.cedula, 
                    COALESCE(a.nombre, '') as nombre, 
                    COALESCE(a.apellido, '') as apellido,
                    n.codigo as nacionalidad,
                    COALESCE(g.grado, '____') as grado,
                    COALESCE(pe.anio_inicio, '____') as anio_inicio,
                    COALESCE(pe.anio_fin, '____') as anio_fin
                FROM alumnos a
                INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id
                INNER JOIN curso c ON i.curso_id = c.curso_id
                LEFT JOIN grados g ON c.grados_id = g.id_grado
                LEFT JOIN nacionalidades n ON a.id_nacionalidades = n.id
                INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                WHERE i.inscripcion_id = ? AND i.estatusI = 1
                LIMIT 1";
    }

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $target_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result->num_rows > 0) {
    while ($alumno = $result->fetch_assoc()) {
        $pdf->AddPage();

        $periodo = $alumno['anio_inicio'] . ' - ' . $alumno['anio_fin'];
        if ($director) {
            $qrFilePath = $pdf->generateQRCode($alumno, $director, $periodo);
            $qrFiles[] = $qrFilePath;
            $pdf->insertQRFixedPosition($qrFilePath);
        }

        $pdf->setContentStartPosition();

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 8, utf8_decode('CONSTANCIA DE ACEPTACIÓN DE CUPO'), 0, 1, 'C');
        $pdf->Ln(15);

        $nombreDirector = '_______________________________';
        $cedulaDirector = 'V-__________________';
        if ($director) {
            if (!empty($director['nombre_profesor']) && !empty($director['apellido_profesor'])) {
                $nombreDirector = $director['nombre_profesor'] . ' ' . $director['apellido_profesor'];
            }
            if (!empty($director['cedula'])) {
                $nacDir = !empty($director['nacionalidad']) ? $director['nacionalidad'] : 'V';
                $cedulaDirector = $nacDir . '-' . $director['cedula'];
            }
        }

        $nombreAlumno = $alumno['nombre'] . ' ' . $alumno['apellido'];
        $nacAlumno = !empty($alumno['nacionalidad']) ? $alumno['nacionalidad'] : '';
        $cedulaAlumno = $nacAlumno . '-' . $alumno['cedula'];
        $grado = $alumno['grado'] . '°';
        $periodo = $alumno['anio_inicio'] . ' - ' . $alumno['anio_fin'];

        $pdf->SetFont('Arial', '', 12);
        $pdf->SetX(25);

        $pdf->WriteUTF8(7, 'Quien suscribe, ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, $nombreDirector);
        $pdf->SetFont('Arial', '', 12);
        $pdf->WriteUTF8(7, ', titular de la cédula de identidad N° ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, $cedulaDirector);
        $pdf->SetFont('Arial', '', 12);
        $pdf->WriteUTF8(7, ', Director de la U.E.D. "TERESA CARREÑO", hago constar por medio de la presente que se ha concedido el cupo al estudiante ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, $nombreAlumno);
        $pdf->SetFont('Arial', '', 12);
        $pdf->WriteUTF8(7, ', cédula escolar o de identidad N° ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, $cedulaAlumno);
        $pdf->WriteUTF8(7, ', para el ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, $grado);
        $pdf->SetFont('Arial', '', 12);
        $pdf->WriteUTF8(7, ' Grado');
        $pdf->WriteUTF8(7, ', por lo que se requiere con carácter urgente le sea entregados los documentos de retiro para formalizar en esta institución por parte de su representante para el año escolar ');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, $periodo . '.');

        $pdf->Ln(20);

        $pdf->SetFont('Arial', '', 12);
        $dia = date('d');
        $mes = $pdf->getMesEspanol(date('m'));
        $anio = date('Y');



        $pdf->SetY(-110);

        $texto_constancia = 'Constancia que se expide a petición de la parte interesada en Caracas a los ' .
            $dia . ' días del mes de ' . $mes . ' del ' . $anio . '.';

        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCellUTF8(0, 8, $texto_constancia, 0, 'C');

        $pdf->SetY(-80);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 6, 'Atentamente,', 0, 1, 'C');
        $pdf->Ln(8);
        $pdf->Cell(0, 6, '_________________________', 0, 1, 'C');
        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->CellUTF8(0, 6, $nombreDirector, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 6, 'Director(a)', 0, 1, 'C');
    }

} else {
    $pdf->AddPage();
    $pdf->setContentStartPosition();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, 'NO SE ENCONTRARON ALUMNOS', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, 'No hay alumnos activos inscritos en el periodo actual que coincidan con la búsqueda.', 0, 'C');
}

$conexion->close();

foreach ($qrFiles as $qrFile) {
    if (file_exists($qrFile)) {
        unlink($qrFile);
    }
}

$pdf->cleanupQRFiles();
$pdf->Output('aceptacion_cupo.pdf', 'I');
?>