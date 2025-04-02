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

use Stichoza\GoogleTranslate\GoogleTranslate;

function translate($text) {
    static $translator = null;
    if ($_SESSION['lang'] == 'es') return $text;
    if ($translator === null) {
        $translator = new GoogleTranslate();
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

// Configuración de límite de intentos
$max_attempts = 5;
$lockout_time = 60; // 5 minutos en segundos
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_lockout_time'] = 0;
}

$error = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar límite de intentos
    if ($_SESSION['login_attempts'] >= $max_attempts && time() < $_SESSION['login_lockout_time']) {
        $remaining_time = $_SESSION['login_lockout_time'] - time();
        $error = translate('Demasiados intentos fallidos. Intenta de nuevo en ') . ceil($remaining_time / 60) . translate(' minutos.');
    } else {
        // Validar reCAPTCHA
        $secretKey = "6Ld-WAcrAAAAABEf1T0eVx_cfsAG6xNNfksSuiYa"; // Tu Secret Key
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        $remoteIp = $_SERVER['REMOTE_ADDR'];

        if (empty($recaptchaResponse)) {
            $error = translate('Por favor, verifica que no eres un robot');
        } else {
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
                $error = translate('Error en la verificación del reCAPTCHA');
                $_SESSION['login_attempts']++;
            } else {
                $conn = new mysqli("localhost", "root", "", "cdn_servicios");
                if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = translate('El formato del correo electrónico no es válido');
                    $_SESSION['login_attempts']++;
                } elseif (empty($password)) {
                    $error = translate('La contraseña es requerida');
                    $_SESSION['login_attempts']++;
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
                            $_SESSION['login_attempts'] = 0; // Reiniciar intentos tras éxito
                            header("Location: $redirect");
                            exit;
                        } else {
                            $error = translate('Correo o contraseña incorrectos');
                            $_SESSION['login_attempts']++;
                        }
                    } else {
                        $error = translate('Correo o contraseña incorrectos');
                        $_SESSION['login_attempts']++;
                    }
                    $stmt->close();
                }
                $conn->close();

                if ($_SESSION['login_attempts'] >= $max_attempts) {
                    $_SESSION['login_lockout_time'] = time() + $lockout_time;
                    $error = translate('Demasiados intentos fallidos. Intenta de nuevo en 5 minutos.');
                }
            }
        }
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
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="<?= translate('Correo electrónico') ?>"
                    value="<?= htmlspecialchars($email) ?>" required>
                <small class="error-message" id="email-error"></small>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="<?= translate('Contraseña') ?>" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('password', this)"></i>
                </div>
                <small class="error-message" id="password-error"></small>
            </div>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="6Ld-WAcrAAAAAEPyetILsZeyMT3OovHUyYoMbdOR"></div>
                <small class="error-message" id="recaptcha-error"></small>
            </div>
            <button type="submit" class="auth-btn"><?= translate('Iniciar Sesión') ?></button>
            <a href="forgot_password.php" class="auth-link"><?= translate('¿Olvidaste tu contraseña?') ?></a>
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

    document.getElementById('loginForm').addEventListener('submit', function(event) {
        let isValid = true;
        document.querySelectorAll('.error-message').forEach(e => e.textContent = '');

        const email = document.querySelector('[name="email"]');
        if (!email.checkValidity()) {
            document.getElementById('email-error').textContent =
                '<?= translate('El formato del correo electrónico no es válido') ?>';
            isValid = false;
        }

        const password = document.querySelector('[name="password"]');
        if (!password.value) {
            document.getElementById('password-error').textContent =
                '<?= translate('La contraseña es requerida') ?>';
            isValid = false;
        }

        const recaptcha = document.querySelector('[name="g-recaptcha-response"]');
        if (!recaptcha || !recaptcha.value) {
            document.getElementById('recaptcha-error').textContent =
                '<?= translate('Por favor, verifica que no eres un robot') ?>';
            isValid = false;
        }

        if (!isValid) event.preventDefault();
    });
    </script>
</body>
</html>