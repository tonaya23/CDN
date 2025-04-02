<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Configuración SMTP para MailHog
    $mail->isSMTP();
    $mail->Host = 'localhost';
    $mail->Port = 1025;
    $mail->SMTPAuth = false; // MailHog no requiere autenticación
    $mail->SMTPSecure = '';  // Sin cifrado para MailHog

    // Detalles del correo
    $mail->setFrom('test@local.com', 'Test');
    $mail->addAddress('test@example.com');
    $mail->Subject = 'Prueba PHPMailer';
    $mail->Body = 'Este es un correo de prueba con PHPMailer.';

    // Enviar
    $mail->send();
    echo "Correo enviado exitosamente.";
} catch (Exception $e) {
    echo "Fallo al enviar: " . $mail->ErrorInfo;
}
?>