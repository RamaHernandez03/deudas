<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Verificar que todas las variables estén definidas
$env_keys = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM', 'SMTP_FROM_NAME_2'];
foreach ($env_keys as $key) {
    if (empty($_ENV[$key])) {
        die("Error: Falta la variable de entorno '$key'. Verificá tu archivo .env.");
    }
}

// Validar y limpiar inputs
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if (empty($nombre) || empty($email) || empty($mensaje)) {
    die('Todos los campos son obligatorios.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('El correo electrónico no es válido.');
}

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = 'tls';
    $mail->Port       = $_ENV['SMTP_PORT'];
    $mail->CharSet    = 'UTF-8';

    // -------- ENVÍA A TU CORREO --------
    $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME_2']);
    $mail->addAddress($_ENV['SMTP_FROM'], 'Consultas desde el sitio');
    $mail->Subject = 'Consulta SOPORTE: Consulta deudores';
    $mail->Body    = "Nombre: $nombre\nEmail: $email\n\nMensaje:\n$mensaje";
    $mail->send();

    // -------- ENVÍA COPIA AL USUARIO --------
    $mail->clearAddresses();
    $mail->addAddress($email, $nombre);
    $mail->Subject = 'Gracias por tu consulta';
    $mail->Body    = "Hola $nombre,\n\nGracias por contactarnos. Recibimos tu mensaje:\n\n\"$mensaje\"\n\nNos pondremos en contacto con vos a la brevedad.\n\nSaludos,\nGisela Rios Abogada";
    $mail->send();

    echo 'Mensaje enviado correctamente.';
} catch (Exception $e) {
    echo "Error al enviar el mensaje: " . $mail->ErrorInfo;
}
