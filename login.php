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
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar reCAPTCHA
    $secretKey = "6Ld-WAcrAAAAABEf1T0eVx_cfsAG6xNNfksSuiYa"; // Reemplaza con tu Secret Key
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $remoteIp = $_SERVER['REMOTE_ADDR'];

    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $remoteIp
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);

    if ($response['success'] !== true) {
        $error = translate('Por favor, verifica que no eres un robot');
    } else {
        $conn = new mysqli("localhost", "root", "", "cdn_servicios");
        if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

        $email = $_POST['email'];
        $password = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = translate('El formato del correo electrónico no es válido');
        } else {
            $stmt = $conn->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();
                if (password_verify($password, $usuario['password'])) {
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit;
                } else {
                    $error = translate('Email o contraseña incorrectos');
                }
            } else {
                $error = translate('Email o contraseña incorrectos');
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('Iniciar Sesión - Climas del Norte') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
        <h2><?= translate('Iniciar Sesión') ?></h2>
        <?php if ($error): ?>
        <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="email" name="email" class="form-control"
                    placeholder="<?= translate('Correo electrónico') ?>" value="<?= htmlspecialchars($email) ?>"
                    required>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="<?= translate('Contraseña') ?>" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('password', this)"></i>
                </div>
            </div>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="6Ld-WAcrAAAAAEPyetILsZeyMT3OovHUyYoMbdOR"></div> <!-- Reemplaza con tu Site Key -->
            </div>
            <button type="submit" class="auth-btn"><?= translate('Iniciar Sesión') ?></button>
            <a href="register.php" class="auth-link"><?= translate('¿No tienes cuenta? Regístrate aquí') ?></a>
            <a href="index.php" class="back-to-home"><?= translate('Volver a la página principal') ?></a>
        </form>
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