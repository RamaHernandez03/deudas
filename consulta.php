<?php

require 'vendor/autoload.php'; 
require 'libs/fpdf.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Archivo de log
$logfile = __DIR__ . '/debug.log';
file_put_contents($logfile, "----- NUEVA CONSULTA: " . date('Y-m-d H:i:s') . " -----\n", FILE_APPEND);

// Validar CUIT
if (!isset($_GET['cuit']) || strlen($_GET['cuit']) !== 11 || !ctype_digit($_GET['cuit'])) {
    file_put_contents($logfile, "CUIT inválido recibido: " . ($_GET['cuit'] ?? 'nulo') . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'CUIT inválido']);
    exit;
}

$cuit = $_GET['cuit'];
$url = "https://api.bcra.gob.ar/CentralDeDeudores/v1.0/Deudas/$cuit";

file_put_contents($logfile, "Consultando URL: $url\n", FILE_APPEND);

// Consulta al BCRA
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para localhost, en producción ponelo en true
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36'
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Validación de errores de CURL
if ($response === false) {
    file_put_contents($logfile, "Error CURL: $error\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error en la conexión CURL: ' . $error]);
    exit;
}

file_put_contents($logfile, "Respuesta HTTP Code: $httpcode\n", FILE_APPEND);

// Validación de respuestas HTTP
if ($httpcode === 404) {
    file_put_contents($logfile, "CUIT no registrado en el BCRA (sin deuda)\n", FILE_APPEND);
    $sinDeuda = true;
    $data = [
        'results' => [
            'identificacion' => $cuit,
            'denominacion' => 'No disponible',
            'periodos' => [[]]
        ]
    ];
} elseif ($httpcode === 200) {
    file_put_contents($logfile, "CUIT encontrado en el BCRA (puede tener deuda)\n", FILE_APPEND);
    $sinDeuda = false;
    $data = json_decode($response, true);
} else {
    file_put_contents($logfile, "Error desconocido HTTP: $httpcode\n", FILE_APPEND);
    http_response_code($httpcode);
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar BCRA. Código HTTP: ' . $httpcode]);
    exit;
}

file_put_contents($logfile, "Armando PDF\n", FILE_APPEND);

// Crear el PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Consulta de Deudas - BCRA', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'CUIT: ' . $data['results']['identificacion'], 0, 1);
$pdf->Cell(0, 10, 'Denominación: ' . $data['results']['denominacion'], 0, 1);
$pdf->Ln(5);

if (!$sinDeuda && !empty($data['results']['periodos'][0]['entidades'])) {
    foreach ($data['results']['periodos'][0]['entidades'] as $entidad) {
        $pdf->Cell(0, 8, 'Entidad: ' . ($entidad['entidad'] ?? 'N/A'), 0, 1);
        $pdf->Cell(0, 8, 'Situación: ' . ($entidad['situacion'] ?? 'N/A'), 0, 1);
        $pdf->Cell(0, 8, 'Monto: $' . number_format($entidad['monto'] ?? 0, 2), 0, 1);
        $pdf->Cell(0, 8, 'Días Atraso: ' . ($entidad['diasAtrasoPago'] ?? '0'), 0, 1);
        $pdf->Ln(4);
    }
} else {
    $pdf->Cell(0, 10, 'No registra deudas en el sistema financiero.', 0, 1);
}

$file_path = "consulta_$cuit.pdf";
$pdf->Output('F', $file_path);

file_put_contents($logfile, "PDF generado en $file_path\n", FILE_APPEND);

// Enviar el correo con PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['SMTP_PORT'];

    $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
    $mail->addAddress('ramiro.tomas.hernandez03@gmail.com'); // Destino
    $mail->Subject = "Consulta BCRA - CUIT $cuit";
    $mail->Body    = "Adjuntamos el resultado de la consulta de deudas en PDF.";
    $mail->addAttachment($file_path);

    $mail->send();
    file_put_contents($logfile, "Correo enviado correctamente\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logfile, "Error al enviar correo: " . $mail->ErrorInfo . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al enviar correo: ' . $mail->ErrorInfo]);
    unlink($file_path);
    exit;
}

// Borrar archivo temporal
unlink($file_path);

file_put_contents($logfile, "Archivo PDF borrado\n", FILE_APPEND);
file_put_contents($logfile, "Finalizando respuesta\n\n", FILE_APPEND);

// Responder al frontend
echo json_encode(['status' => $sinDeuda ? 'sindeuda' : 'deuda']);

?>
