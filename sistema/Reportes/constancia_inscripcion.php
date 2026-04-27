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
        $qrContent = "CONSTANCIA DE INSCRIPCIÓN\n";
        $qrContent .= "========================\n";
        $qrContent .= "Alumno(a): " . $alumnoData['nombre'] . " " . $alumnoData['apellido'] . "\n";
        $qrContent .= "Cédula: " . $alumnoData['nacionalidad'] . "-" . $alumnoData['cedula'] . "\n";
        $qrContent .= "Fecha Nacimiento: " . ($alumnoData['fecha_nac'] ? date('d/m/Y', strtotime($alumnoData['fecha_nac'])) : 'No especificada') . "\n";
        $qrContent .= "Grado: " . $alumnoData['grado'] . "° Sección " . $alumnoData['seccion'] . "\n";
        $qrContent .= "Turno: " . $alumnoData['tipo_turno'] . "\n";
        $qrContent .= "Periodo Escolar: " . $periodo . "\n";
        $qrContent .= "Director(a): " . $directorData['nombre_profesor'] . " " . $directorData['apellido_profesor'] . "\n";
        $qrContent .= "Cédula Director(a): " . $directorData['nacionalidad'] . "-" . $directorData['cedula'] . "\n";
        $qrContent .= "Fecha Emisión: " . date('d/m/Y') . "\n";
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
$pdf->AddPage();

$director = $pdf->getDirectorData($pdo);
$qrFiles = [];

$inscripcion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($inscripcion_id <= 0) {
    $pdf->setContentStartPosition();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, 'ERROR: ID DE INSCRIPCIÓN NO VÁLIDO', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, 'Por favor, proporcione un ID de inscripción válido en la URL.', 0, 'C');
    $pdf->Ln(2);
    $pdf->MultiCellUTF8(0, 8, 'Ejemplo: constancia_inscripcion.php?id=1', 0, 'C');
    $conexion->close();
    $pdf->cleanupQRFiles();
    $pdf->Output('constancia_inscripcion.pdf', 'I');
    exit;
}

$sql = "SELECT 
            a.cedula, 
            COALESCE(a.nombre, '') as nombre, 
            COALESCE(a.apellido, '') as apellido,
            a.fecha_nac,
            n.codigo as nacionalidad,
            t.tipo_turno,
            g.grado,
            s.seccion,
            pe.periodo_id,
            CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo_completo,
            i.inscripcion_id,
            i.estatusI,
            est.estado as nombre_estado,
            ciu.ciudad as nombre_ciudad
        FROM alumnos a
        LEFT JOIN inscripcion i ON a.alumno_id = i.alumno_id
        LEFT JOIN curso c ON i.curso_id = c.curso_id
        LEFT JOIN grados g ON c.grados_id = g.id_grado
        LEFT JOIN seccion s ON c.seccion_id = s.id_seccion
        LEFT JOIN turno t ON i.turno_id = t.turno_id
        LEFT JOIN nacionalidades n ON a.id_nacionalidades = n.id
        LEFT JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
        LEFT JOIN estados est ON a.id_estado = est.id_estado
        LEFT JOIN ciudades ciu ON a.id_ciudad = ciu.id_ciudad
        WHERE i.inscripcion_id = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $inscripcion_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $alumno = $result->fetch_assoc();

    $periodo = $alumno['periodo_completo'] ?? date('Y');
    $qrFilePath = $pdf->generateQRCode($alumno, $director, $periodo);
    $qrFiles[] = $qrFilePath;
    $pdf->insertQRFixedPosition($qrFilePath);

    $pdf->setContentStartPosition();

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->CellUTF8(0, 10, 'CONSTANCIA DE INSCRIPCIÓN', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetX(25);

    $nombreDirector = '__________________________';
    $cedulaDirector = '__________';

    if ($director) {
        if (!empty($director['nombre_profesor']) && !empty($director['apellido_profesor'])) {
            $nombreDirector = $director['nombre_profesor'] . ' ' . $director['apellido_profesor'];
        }

        if (!empty($director['cedula'])) {
            $nacionalidad = !empty($director['nacionalidad']) ? $director['nacionalidad'] . '-' : 'V-';
            $cedulaDirector = $nacionalidad . $director['cedula'];
        }
    }

    $texto_completo = 'Quien suscribe, ' . $nombreDirector . ', titular de la Cédula de Identidad ' . $cedulaDirector . ', Director de la UNIDAD EDUCATIVA DISTRITAL "TERESA CARREÑO". Hace constar por medio de la presente que: el(la) alumno(a) ';

    $pdf->WriteUTF8(7, $texto_completo);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $alumno['nombre'] . ' ' . $alumno['apellido']);

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ', titular de la Cédula de Identidad N°: ');
    $pdf->SetFont('Arial', 'B', 12);
    $nacionalidad = $alumno['nacionalidad'] ? $alumno['nacionalidad'] . '-' : '';
    $pdf->WriteUTF8(7, $nacionalidad . $alumno['cedula']);

    $fecha_nacimiento = $alumno['fecha_nac'] ?? '';
    $fecha_obj = !empty($fecha_nacimiento) ? DateTime::createFromFormat('Y-m-d', $fecha_nacimiento) : false;
    $dia_nacimiento = $fecha_obj ? $fecha_obj->format('d') : '';
    $mes_nacimiento = $fecha_obj ? $fecha_obj->format('m') : '';
    $anio_nacimiento = $fecha_obj ? $fecha_obj->format('Y') : '';

    $edad = $fecha_obj ? $fecha_obj->diff(new DateTime())->y : '';

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ', nacido(a) el ');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $dia_nacimiento . ' de ' . $pdf->getMesEspanol($mes_nacimiento) . ' de ' . $anio_nacimiento);
    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ', con ');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $edad . ' años');
    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' de edad y natural de ');
    $pdf->SetFont('Arial', 'B', 12);

    $lugar_origen = 'Caracas';
    if (!empty($alumno['nombre_ciudad']) && !empty($alumno['nombre_estado'])) {
        $lugar_origen = $alumno['nombre_ciudad'] . ', Estado ' . $alumno['nombre_estado'];
    } elseif (!empty($alumno['nombre_ciudad'])) {
        $lugar_origen = $alumno['nombre_ciudad'];
    } elseif (!empty($alumno['nombre_estado'])) {
        $lugar_origen = $alumno['nombre_estado'];
    }

    $pdf->WriteUTF8(7, $lugar_origen);

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ', se encuentra formalmente inscrito(a) en este plantel en el ');
    $pdf->SetFont('Arial', 'B', 12);

    $grado = $alumno['grado'] ?? '';
    $seccion = $alumno['seccion'] ?? '';
    $turno = $alumno['tipo_turno'] ?? '';

    $grado_seccion_turno = !empty($grado) ? $grado . '° Grado Sección "' . $seccion . '"' : 'Grado no asignado';
    if (!empty($turno)) {
        $grado_seccion_turno .= ' en el turno ' . $turno;
    }

    $pdf->WriteUTF8(7, $grado_seccion_turno);
    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' de Educación Primaria, durante el año escolar ');
    $pdf->SetFont('Arial', 'B', 12);
    $periodo = $alumno['periodo_completo'] ?? date('Y');
    $pdf->WriteUTF8(7, $periodo . '.');

    $pdf->Ln(12);

    $pdf->SetX(25);
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->Ln(15);

    $pdf->SetY(-110);

    $dia = date('d');
    $mes = $pdf->getMesEspanol(date('m'));
    $anio = date('Y');

    $texto_constancia = 'Constancia que se expide a solicitud de la parte interesada en Caracas a los ' .
        $dia . ' días del mes de ' . $mes . ' del ' . $anio . '.';

    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCellUTF8(0, 8, $texto_constancia, 0, 'C');

    $pdf->SetY(-80);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 6, 'Atentamente,', 0, 1, 'C');
    $pdf->Ln(8);
    $pdf->Cell(0, 6, '_________________________', 0, 1, 'C');
    $pdf->Ln(6);

    if ($director) {
        $nombreFirma = $director['nombre_profesor'] . ' ' . $director['apellido_profesor'];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->CellUTF8(0, 6, $nombreFirma, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 6, 'Director(a)', 0, 1, 'C');
    }

} else {
    $pdf->setContentStartPosition();
    $pdf->SetY(100);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, 'ALUMNO NO ENCONTRADO', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, 'No se encontró ningún alumno activo con el ID proporcionado.', 0, 'C');
}

$conexion->close();

foreach ($qrFiles as $qrFile) {
    if (file_exists($qrFile)) {
        unlink($qrFile);
    }
}

$pdf->cleanupQRFiles();
$pdf->Output('constancia_inscripcion.pdf', 'I');
?>