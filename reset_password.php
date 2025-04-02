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
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    $error = translate('Enlace inválido o expirado.');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($token)) {
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8 || strlen($password) > 20) {
        $error = translate('La contraseña debe tener entre 8 y 20 caracteres');
    } elseif ($password !== $confirm_password) {
        $error = translate('Las contraseñas no coinciden');
    } else {
        // Verificar el token
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at > NOW() - INTERVAL 1 HOUR");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $email = $row['email'];

            // Actualizar la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();

            // Eliminar el token usado
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $success = translate('¡Contraseña restablecida con éxito! Ahora puedes iniciar sesión.');
        } else {
            $error = translate('Enlace inválido o expirado.');
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
    <title><?= translate('Restablecer Contraseña - Climas del Norte') ?></title>
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
        <h2><?= translate('Restablecer Contraseña') ?></h2>
        <?php if ($error): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message">
                <?= $success ?>
                <div><a href="login.php" class="auth-link"><?= translate('Ir a iniciar sesión') ?></a></div>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . urlencode($token); ?>">
                <div class="form-group">
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="<?= translate('Nueva contraseña') ?>" required>
                        <i class="toggle-password fas fa-eye" onclick="togglePassword('password', this)"></i>
                    </div>
                    <small class="password-requirements"><?= translate('La contraseña debe tener entre 8 y 20 caracteres') ?></small>
                </div>
                <div class="form-group">
                    <div class="password-container">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                               placeholder="<?= translate('Confirmar contraseña') ?>" required>
                        <i class="toggle-password fas fa-eye" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>
                <button type="submit" class="auth-btn"><?= translate('Restablecer Contraseña') ?></button>
                <a href="login.php" class="auth-link"><?= translate('Volver a Iniciar Sesión') ?></a>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    </script>
</body>
</html>