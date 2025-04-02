<?php
session_start();

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'es';
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function translate($text) {
    static $translator = null;
    if ($_SESSION['lang'] == 'es') return $text;
    if ($translator === null) {
        $translator = new Stichoza\GoogleTranslate\GoogleTranslate();
        $translator->setSource('es');
        $translator->setTarget($_SESSION['lang']);
    }
    try {
        return $translator->translate($text);
    } catch (Exception $e) {
        return $text;
    }
}

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = translate('El formato del correo electrónico no es válido');
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Generar token
            $token = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()");
            $stmt->bind_param("sss", $email, $token, $token);
            $stmt->execute();

            // Enviar correo con PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'localhost';
                $mail->Port = 1025;
                $mail->SMTPAuth = false;
                $mail->SMTPSecure = '';

                $mail->setFrom('no-reply@climasdelnorte.com', 'Climas del Norte');
                $mail->addAddress($email);
                $mail->Subject = translate('Restablecer tu contraseña - Climas del Norte');
                $mail->Body = translate('Hola,') . "\n\n" .
                              translate('Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:') . "\n" .
                              "http://localhost/UT/ANTONIO/CDN/reset_password.php?token=" . $token . "\n\n" .
                              translate('Si no solicitaste esto, ignora este correo.') . "\n\n" .
                              translate('Saludos,') . "\n" .
                              "Climas del Norte";

                $mail->send();
                $success = translate('Se ha enviado un enlace de restablecimiento a tu correo. Revisa tu bandeja de entrada o spam.');
                $email = '';
            } catch (Exception $e) {
                $error = translate('Error al enviar el correo: ') . $mail->ErrorInfo;
            }
        } else {
            $error = translate('No existe una cuenta asociada a este correo.');
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('Recuperar Contraseña - Climas del Norte') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <div class="logo">
                <div class="logo-text">CDN</div>
                <span>Climas del Norte</span>
            </div>
            <div class="menu-toggle"><i class="fas fa-bars"></i></div>
            <div class="language-switcher">
                <a href="?lang=es" class="<?= $_SESSION['lang'] == 'es' ? 'active' : '' ?>">ES</a> |
                <a href="?lang=en" class="<?= $_SESSION['lang'] == 'en' ? 'active' : '' ?>">EN</a>
            </div>
        </div>
    </header>

    <div class="auth-container">
        <h2><?= translate('Recuperar Contraseña') ?></h2>
        <?php if ($error): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="<?= translate('Correo electrónico') ?>" 
                       value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <button type="submit" class="auth-btn"><?= translate('Enviar enlace de recuperación') ?></button>
            <a href="login.php" class="auth-link"><?= translate('Volver a Iniciar Sesión') ?></a>
            <a href="index.php" class="back-to-home"><?= translate('Volver a la página principal') ?></a>
        </form>
    </div>
</body>
</html>