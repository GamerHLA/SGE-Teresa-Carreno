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

    public function generateQRCode($representanteData, $directorData, $fechaAsistencia, $motivo)
    {
        $qrContent = "CONSTANCIA DE ASISTENCIA DE REPRESENTANTES\n";
        $qrContent .= "==========================================\n";
        $qrContent .= "Representante: " . $representanteData['nombre'] . " " . $representanteData['apellido'] . "\n";
        $qrContent .= "Cédula: " . $representanteData['nacionalidad'] . "-" . $representanteData['cedula'] . "\n";

        $fecha_asistencia = '';
        if (isset($_GET['fecha']) && !empty($_GET['fecha'])) {
            $fecha_timestamp = strtotime($_GET['fecha']);
            if ($fecha_timestamp) {
                $dia = date('d', $fecha_timestamp);
                $mes = $this->getMesEspanol(date('m', $fecha_timestamp));
                $anio = date('Y', $fecha_timestamp);
                $fecha_asistencia = $dia . ' de ' . $mes . ' de ' . $anio;
            }
        } else {
            $dia = date('d');
            $mes = $this->getMesEspanol(date('m'));
            $anio = date('Y');
            $fecha_asistencia = $dia . ' de ' . $mes . ' de ' . $anio;
        }

        $qrContent .= "Fecha Asistencia: " . $fecha_asistencia . "\n";

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
}

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../includes/config.php';

// PDO instance ($pdo) is already created in config.php

$representante_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($representante_id <= 0) {
    renderErrorPDF('ID DE REPRESENTANTE NO VÁLIDO', 'Por favor, proporcione un ID de representante válido.');
    exit;
}

$representante = getRepresentanteData($pdo, $representante_id);

if (!$representante) {
    renderErrorPDF('REPRESENTANTE NO ENCONTRADO', 'No se encontró ningún representante activo con el ID proporcionado.');
    exit;
}

$director = getDirectorData($pdo);
generateConstanciaRepresentantePDF($representante, $director);

exit;

function getRepresentanteData($pdo, $representante_id)
{
    $sql = "SELECT 
                r.*,
                e.estado as nombre_estado,
                c.ciudad as nombre_ciudad,
                m.municipio as nombre_municipio,
                p.parroquia as nombre_parroquia,
                n.codigo as nacionalidad
            FROM representantes r
            LEFT JOIN estados e ON r.id_estado = e.id_estado
            LEFT JOIN ciudades c ON r.id_ciudad = c.id_ciudad
            LEFT JOIN municipios m ON r.id_municipio = m.id_municipio
            LEFT JOIN parroquias p ON r.id_parroquia = p.id_parroquia
            LEFT JOIN nacionalidades n ON r.id_nacionalidades = n.id
            WHERE r.representantes_id = ? AND r.estatus != 0";

    try {
        $query = $pdo->prepare($sql);
        $query->execute([$representante_id]);
        return $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en getRepresentanteData: " . $e->getMessage());
        return null;
    }
}

function getDirectorData($pdo)
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

function renderErrorPDF($titulo, $mensaje)
{
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->setContentStartPosition();

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(150, 0, 0);
    $pdf->CellUTF8(0, 10, $titulo, 0, 1, 'C');

    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCellUTF8(0, 8, $mensaje, 0, 'C');

    $pdf->cleanupQRFiles();
    $pdf->Output('error_constancia_representante.pdf', 'I');
}

function generateConstanciaRepresentantePDF($representante, $director)
{
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    $qrFiles = [];

    $motivo = isset($_GET['motivo']) ? $_GET['motivo'] : '';
    $fecha_asistencia = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : '';

    $qrFilePath = $pdf->generateQRCode($representante, $director, $fecha_asistencia, $motivo);
    $qrFiles[] = $qrFilePath;
    $pdf->insertQRFixedPosition($qrFilePath);

    $pdf->setContentStartPosition();

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->CellUTF8(0, 12, 'CONSTANCIA DE ASISTENCIA DE REPRESENTANTES', 0, 1, 'C');
    $pdf->Ln(15);

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

    $textoCompleto = 'Quien suscribe, ' . $nombreDirector . ', titular de la Cédula de Identidad ' . $cedulaDirector . ', Director de la UNIDAD EDUCATIVA DISTRITAL "TERESA CARREÑO", ubicada al final de la Av. Circunvalación, frente al bloque 18 pequeño, hace constar por medio de la presente que el Ciudadano(a) ';

    $pdf->WriteUTF8(7, $textoCompleto);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->WriteUTF8(7, $representante['nombre'] . ' ' . $representante['apellido']);

    $pdf->SetFont('Arial', '', 12);
    $pdf->WriteUTF8(7, ' titular de la cédula de identidad N° ');
    $pdf->SetFont('Arial', 'B', 12);

    $cedula_completa = (!empty($representante['nacionalidad']) ? $representante['nacionalidad'] : 'V') . '-' . $representante['cedula'];
    $pdf->WriteUTF8(7, $cedula_completa);
    $pdf->SetFont('Arial', '', 12);

    $fecha_asistencia = '_____________';

    if (isset($_GET['fecha']) && !empty($_GET['fecha'])) {
        $fecha_timestamp = strtotime($_GET['fecha']);
        if ($fecha_timestamp) {
            $dia = date('d', $fecha_timestamp);
            $mes = $pdf->getMesEspanol(date('m', $fecha_timestamp));
            $anio = date('Y', $fecha_timestamp);
            $fecha_asistencia = $dia . ' de ' . $mes . ' de ' . $anio;
        }
    } else {
        $dia = date('d');
        $mes = $pdf->getMesEspanol(date('m'));
        $anio = date('Y');
        $fecha_asistencia = $dia . ' de ' . $mes . ' de ' . $anio;
    }

    $pdf->WriteUTF8(7, ', asistió al plantel el día: ' . $fecha_asistencia . '.');

    $pdf->Ln(12);

    $motivo = isset($_GET['motivo']) ? $_GET['motivo'] : '';

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

    foreach ($qrFiles as $qrFile) {
        if (file_exists($qrFile)) {
            unlink($qrFile);
        }
    }

    $pdf->cleanupQRFiles();
    $pdf->Output('constancia_asistencia_representante_' . $representante['cedula'] . '.pdf', 'I');
}

function formatUbicacion($representante)
{
    $partesUbicacion = [];

    if (!empty($representante['nombre_parroquia']))
        $partesUbicacion[] = $representante['nombre_parroquia'];
    if (!empty($representante['nombre_municipio']))
        $partesUbicacion[] = $representante['nombre_municipio'];
    if (!empty($representante['nombre_ciudad']))
        $partesUbicacion[] = $representante['nombre_ciudad'];
    if (!empty($representante['nombre_estado']))
        $partesUbicacion[] = $representante['nombre_estado'];

    return implode(', ', $partesUbicacion);
}

function calcularEdad($fecha_nacimiento)
{
    if (empty($fecha_nacimiento)) {
        return ['fecha no disponible', 'N/A'];
    }

    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
    if (!$fecha_obj) {
        return ['fecha inválida', 'N/A'];
    }

    $dia = $fecha_obj->format('d');
    $mes = $fecha_obj->format('m');
    $anio = $fecha_obj->format('Y');
    $edad = $fecha_obj->diff(new DateTime())->y;

    $pdf = new PDF();
    $fecha_formateada = $dia . ' de ' . $pdf->getMesEspanol($mes) . ' de ' . $anio;

    return [$fecha_formateada, $edad];
}
?>