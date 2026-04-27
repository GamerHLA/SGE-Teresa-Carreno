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

    public function generateQRCode($alumnoData, $directorData, $gradoInicio, $anioInicio, $anioFin, $representante, $motivo = '')
    {
        $qrContent = "BOLETA DE RETIRO\n";
        $qrContent .= "================\n";
        $qrContent .= "Alumno(a): " . $alumnoData['nombre'] . " " . ($alumnoData['apellido'] ?? '') . "\n";
        $qrContent .= "Cédula Alumno(a): " . ($alumnoData['nacionalidad']) . '-' . ($alumnoData['cedula'] ?? '') . "\n";
        $qrContent .= "Periodo: " . $anioInicio . " - " . $anioFin . "\n";
        $qrContent .= "Grado Inicio: " . $gradoInicio . "°\n";
        $qrContent .= "Grado Final: " . ($alumnoData['grado'] ?? '') . "°\n";

        if ($representante && !empty($representante['nombre'])) {
            $qrContent .= "Representante: " . $representante['nombre'] . "\n";
            $qrContent .= "Cédula Representante: " . ($representante['cedula'] ?? '') . "\n";
        }

        if (!empty($motivo)) {
            $qrContent .= "Motivo: " . $motivo . "\n";
        }

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

ob_start();
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../includes/config.php';

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    ob_end_clean();
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

$id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo_param = isset($_GET['tipo']) ? $_GET['tipo'] : 'inscripcion';

$motivo = '';
if (isset($_GET['motivo']) && !empty($_GET['motivo'])) {
    $motivo = urldecode($_GET['motivo']);
} elseif (isset($_GET['razon']) && !empty($_GET['razon'])) {
    $motivo = urldecode($_GET['razon']);
} elseif (isset($_GET['causa']) && !empty($_GET['causa'])) {
    $motivo = urldecode($_GET['causa']);
}

if ($id_param <= 0) {
    $pdf->setContentStartPosition();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, 'ERROR: ID NO VÁLIDO', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, 'Por favor, proporcione un ID válido en la URL.', 0, 'C');
    $pdf->Ln(2);
    $pdf->MultiCellUTF8(0, 8, 'Ejemplo: retiro.php?id=1&tipo=inscripcion&motivo=Cambio%20de%20residencia', 0, 'C');
    $conexion->close();
    $pdf->cleanupQRFiles();
    ob_end_clean();
    $pdf->Output('boleta_retiro.pdf', 'I');
    exit;
}

if ($tipo_param === 'alumno') {
    $sql = "SELECT 
                a.cedula, 
                COALESCE(a.nombre, '') as nombre, 
                COALESCE(a.apellido, '') as apellido,
                g.grado,
                s.seccion,
                i.inscripcion_id,
                pe.anio_fin,
                r.nombre as nombre_representante,
                r.apellido as apellido_representante,
                r.cedula as cedula_representante,
                nr.codigo as nacionalidad_representante,
                na.codigo as nacionalidad,
                a.alumno_id
            FROM alumnos a
            INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id
            INNER JOIN curso c ON i.curso_id = c.curso_id
            INNER JOIN grados g ON c.grados_id = g.id_grado
            INNER JOIN seccion s ON c.seccion_id = s.id_seccion
            INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
            LEFT JOIN nacionalidades na ON a.id_nacionalidades = na.id
            LEFT JOIN alumno_representante ar ON a.alumno_id = ar.alumno_id AND ar.es_principal = 1
            LEFT JOIN representantes r ON ar.representante_id = r.representantes_id
            LEFT JOIN nacionalidades nr ON r.id_nacionalidades = nr.id
            WHERE a.alumno_id = ? 
            ORDER BY i.inscripcion_id DESC
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_param);
} else {
    $sql = "SELECT 
                a.cedula, 
                COALESCE(a.nombre, '') as nombre, 
                COALESCE(a.apellido, '') as apellido,
                g.grado,
                s.seccion,
                i.inscripcion_id,
                pe.anio_fin,
                r.nombre as nombre_representante,
                r.apellido as apellido_representante,
                r.cedula as cedula_representante,
                nr.codigo as nacionalidad_representante,
                na.codigo as nacionalidad,
                a.alumno_id
            FROM alumnos a
            INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id
            INNER JOIN curso c ON i.curso_id = c.curso_id
            INNER JOIN grados g ON c.grados_id = g.id_grado
            INNER JOIN seccion s ON c.seccion_id = s.id_seccion
            INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
            LEFT JOIN nacionalidades na ON a.id_nacionalidades = na.id
            LEFT JOIN alumno_representante ar ON a.alumno_id = ar.alumno_id AND ar.es_principal = 1
            LEFT JOIN representantes r ON ar.representante_id = r.representantes_id
            LEFT JOIN nacionalidades nr ON r.id_nacionalidades = nr.id
            WHERE i.inscripcion_id = ?
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_param);
}

// Lógica para obtener el motivo de la tabla observaciones si no viene por GET
if (empty($motivo) && $id_param > 0) {
    $alumnoIdForObs = 0;

    // Si tenemos el ID directamente
    if ($tipo_param === 'alumno') {
        $alumnoIdForObs = $id_param;
    } else {
        // Si es inscripción, necesitamos averiguar el alumno_id primero
        // Pero lo obtenemos después de ejecutar la query principal, 
        // así que podemos hacerlo después del fetch.
    }
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $alumno = $result->fetch_assoc();

    // Buscamos el motivo en BDD si no vino por URL
    if (empty($motivo) && isset($alumno['alumno_id'])) {
        $sqlObs = "SELECT observacion FROM observaciones 
                   WHERE alumno_id = ? AND tipo_observacion IN ('inhabilitacion', 'motivo_retiro') 
                   ORDER BY observacion_id DESC LIMIT 1";
        $stmtObs = $conexion->prepare($sqlObs);
        $stmtObs->bind_param("i", $alumno['alumno_id']);
        $stmtObs->execute();
        $resObs = $stmtObs->get_result();

        if ($resObs && $rowObs = $resObs->fetch_assoc()) {
            $fullObs = $rowObs['observacion'];
            // Intentar extraer solo la parte del motivo
            // Formato esperado: "... . Motivo: XXXXX"
            $parts = explode('Motivo:', $fullObs);
            if (count($parts) > 1) {
                $motivo = trim(end($parts));
            } else {
                $motivo = $fullObs;
            }
        }
    }

    $anioInicio = '____';
    $gradoInicio = '____';

    if (isset($alumno['alumno_id'])) {
        $sqlFirst = "SELECT pe.anio_inicio, g.grado as grado_inicio 
                     FROM inscripcion i
                     INNER JOIN curso c ON i.curso_id = c.curso_id
                     INNER JOIN grados g ON c.grados_id = g.id_grado
                     INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                     WHERE i.alumno_id = ?
                     ORDER BY i.inscripcion_id ASC
                     LIMIT 1";

        $stmtFirst = $conexion->prepare($sqlFirst);
        $stmtFirst->bind_param("i", $alumno['alumno_id']);
        $stmtFirst->execute();
        $resFirst = $stmtFirst->get_result();

        if ($resFirst && $rowFirst = $resFirst->fetch_assoc()) {
            $anioInicio = $rowFirst['anio_inicio'] ?? '____';
            $gradoInicio = $rowFirst['grado_inicio'] ?? '____';
        }
    }

    $representanteQR = null;
    if (!empty($alumno['nombre_representante'])) {
        $representanteQR = [
            'nombre' => ($alumno['nombre_representante'] ?? '') . ' ' . ($alumno['apellido_representante'] ?? ''),
            'cedula' => ($alumno['nacionalidad_representante'] ?? 'V') . '-' . ($alumno['cedula_representante'] ?? '')
        ];
    }

    $qrFilePath = $pdf->generateQRCode($alumno, $director, $gradoInicio, $anioInicio, $alumno['anio_fin'] ?? '____', $representanteQR, $motivo);
    $qrFiles[] = $qrFilePath;
    $pdf->insertQRFixedPosition($qrFilePath);

    $pdf->setContentStartPosition();

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->CellUTF8(0, 10, 'BOLETA DE RETIRO', 0, 1, 'C');
    $pdf->Ln(15);

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetX(25);

    $nombreDirector = '__________________________';
    $cedulaDirector = '__________________________';

    if ($director) {
        if (!empty($director['nombre_profesor']) && !empty($director['apellido_profesor'])) {
            $nombreDirector = $director['nombre_profesor'] . ' ' . $director['apellido_profesor'];
        }

        if (!empty($director['cedula'])) {
            $nacionalidad = !empty($director['nacionalidad']) ? $director['nacionalidad'] . '-' : 'V-';
            $cedulaDirector = $nacionalidad . $director['cedula'];
        }
    }

    $texto_constancia = 'Quien suscribe, Director de la UNIDAD EDUCATIVA DISTRITAL "TERESA CARREÑO", ' .
        $nombreDirector . ', C. I. N° ' . $cedulaDirector .
        ' hace constar que el(la) Alumno(a) ';

    $pdf->WriteUTF8(7, $texto_constancia);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $alumno['nombre'] . ' ' . $alumno['apellido']);

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' cursó el ');

    $pdf->SetFont('Arial', 'B', 12);
    if ($gradoInicio == ($alumno['grado'] ?? '')) {
        $rango_grados = $gradoInicio . '° Grado';
    } else {
        $rango_grados = $gradoInicio . '° Grado hasta el ' . ($alumno['grado'] ?? '____') . '° Grado';
    }
    $pdf->WriteUTF8(7, $rango_grados);

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' de educación primaria en esta institución durante los años escolares ');

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $anioInicio);
    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' al ');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $alumno['anio_fin'] ?? '____');
    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' fecha en que fue retirado(a) por su representante legal ');

    $pdf->SetFont('Arial', 'B', 12);
    if (!empty($alumno['nombre_representante']) && !empty($alumno['apellido_representante'])) {
        $representante = $alumno['nombre_representante'] . ' ' . $alumno['apellido_representante'];
    } else {
        $representante = '__________________________';
    }
    $pdf->WriteUTF8(7, $representante);

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' C. I. N° ');

    $pdf->SetFont('Arial', 'B', 12);
    if (!empty($alumno['cedula_representante'])) {
        $cedula_rep = ($alumno['nacionalidad_representante'] ?? 'V') . '-' . $alumno['cedula_representante'];
    } else {
        $cedula_rep = '__________';
    }
    $pdf->WriteUTF8(7, $cedula_rep);

    $pdf->Ln(12);

    if (!empty($motivo)) {
        $pdf->SetX(25);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->WriteUTF8(7, 'Motivo: ');
        $pdf->SetFont('Arial', '', 12);
        $pdf->WriteUTF8(7, $motivo);
        $pdf->Ln(15);
    } else {
        $pdf->SetX(25);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->CellUTF8(0, 10, 'Motivo:', 0, 1);
        $pdf->Ln(5);

        $pdf->SetX(25);
        $pdf->SetFont('Arial', '', 12);
        $pdf->CellUTF8(160, 1, '', 'B', 1);
        $pdf->Ln(5);
        $pdf->SetX(25);
        $pdf->CellUTF8(160, 1, '', 'B', 1);
        $pdf->Ln(5);
        $pdf->SetX(25);
        $pdf->CellUTF8(160, 1, '', 'B', 1);
        $pdf->Ln(15);
    }

    $pdf->SetY(-110);

    $dia = date('d');
    $mes = $pdf->getMesEspanol(date('m'));
    $anio = date('Y');

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

    if ($director) {
        $nombreFirma = $director['nombre_profesor'] . ' ' . $director['apellido_profesor'];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->CellUTF8(0, 6, $nombreFirma, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 6, 'Director(a)', 0, 1, 'C');
    }
} else {
    $pdf->setContentStartPosition();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, 'DATOS NO ENCONTRADOS', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, 'No se encontró información para el ID proporcionado o el alumno no tiene inscripciones registradas.', 0, 'C');
}

$conexion->close();

foreach ($qrFiles as $qrFile) {
    if (file_exists($qrFile)) {
        unlink($qrFile);
    }
}

$pdf->cleanupQRFiles();
ob_end_clean();
$pdf->Output('boleta_retiro.pdf', 'I');
?>