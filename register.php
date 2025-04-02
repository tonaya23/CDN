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

// Inicializar variables
$error = '';
$success = '';
$nombre = '';
$email = '';
$telefono = '';

// Configuración de límite de intentos
$max_attempts = 5;
$lockout_time = 300; // 5 minutos en segundos
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 0;
    $_SESSION['register_lockout_time'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar límite de intentos
    if ($_SESSION['register_attempts'] >= $max_attempts && time() < $_SESSION['register_lockout_time']) {
        $remaining_time = $_SESSION['register_lockout_time'] - time();
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
                $_SESSION['register_attempts']++;
            } else {
                $conn = new mysqli("localhost", "root", "", "cdn_servicios");
                if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

                $nombre = trim($_POST['nombre'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $telefono = trim($_POST['telefono'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                $valid = true;

                // Validar nombre
                if (strlen($nombre) < 5 || strlen($nombre) > 50 || !preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
                    $error = translate('El nombre debe tener entre 5 y 50 caracteres y solo letras');
                    $valid = false;
                }

                // Validar email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = translate('El formato del correo electrónico no es válido');
                    $valid = false;
                } else {
                    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $error = translate('Este correo electrónico ya está registrado');
                        $valid = false;
                    }
                    $stmt->close();
                }

                // Validar teléfono
                if (!empty($telefono) && !preg_match('/^[0-9]{10}$/', $telefono)) {
                    $error = translate('El teléfono debe tener 10 dígitos y solo números');
                    $valid = false;
                }

                // Validar contraseña
                if (strlen($password) < 8 || strlen($password) > 20) {
                    $error = translate('La contraseña debe tener entre 8 y 20 caracteres');
                    $valid = false;
                } elseif ($password !== $confirm_password) {
                    $error = translate('Las contraseñas no coinciden');
                    $valid = false;
                }

                if ($valid) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, telefono) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $nombre, $email, $hashed_password, $telefono);

                    if ($stmt->execute()) {
                        $success = translate('¡Registro exitoso! Ahora puedes iniciar sesión');
                        $nombre = $email = $telefono = '';
                        $_SESSION['register_attempts'] = 0; // Reiniciar intentos tras éxito
                    } else {
                        $error = translate('Error al registrar: ') . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['register_attempts']++;
                    if ($_SESSION['register_attempts'] >= $max_attempts) {
                        $_SESSION['register_lockout_time'] = time() + $lockout_time;
                        $error = translate('Demasiados intentos fallidos. Intenta de nuevo en 5 minutos.');
                    }
                }
                $conn->close();
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
    <title><?= translate('Registro - Climas del Norte') ?></title>
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
        <h2><?= translate('Crear Cuenta') ?></h2>
        <?php if ($error): ?>
        <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="success-message">
            <?= $success ?>
            <div><a href="login.php" class="auth-link"><?= translate('Ir a iniciar sesión') ?></a></div>
        </div>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
            <div class="form-group">
                <input type="text" name="nombre" class="form-control" placeholder="<?= translate('Nombre completo') ?>"
                    value="<?= htmlspecialchars($nombre) ?>" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" minlength="5"
                    maxlength="50" title="<?= translate('Solo letras, entre 5 y 50 caracteres') ?>" required>
                <small class="error-message" id="nombre-error"></small>
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="<?= translate('Correo electrónico') ?>"
                    value="<?= htmlspecialchars($email) ?>" required>
                <small class="error-message" id="email-error"></small>
            </div>
            <div class="form-group">
                <input type="tel" name="telefono" class="form-control" placeholder="<?= translate('Teléfono (10 dígitos)') ?>"
                    value="<?= htmlspecialchars($telefono) ?>" pattern="[0-9]{10}" title="<?= translate('Solo números, 10 dígitos') ?>">
                <small class="error-message" id="telefono-error"></small>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="<?= translate('Contraseña') ?>" minlength="8" maxlength="20" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('password', this)"></i>
                </div>
                <small class="password-requirements"><?= translate('Entre 8 y 20 caracteres') ?></small>
                <small class="error-message" id="password-error"></small>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                        placeholder="<?= translate('Confirmar contraseña') ?>" minlength="8" maxlength="20" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('confirm_password', this)"></i>
                </div>
                <small class="error-message" id="confirm-password-error"></small>
            </div>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="6Ld-WAcrAAAAAEPyetILsZeyMT3OovHUyYoMbdOR"></div>
                <small class="error-message" id="recaptcha-error"></small>
            </div>
            <button type="submit" class="auth-btn"><?= translate('Registrarse') ?></button>
            <a href="login.php" class="auth-link"><?= translate('¿Ya tienes cuenta? Inicia sesión aquí') ?></a>
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

    document.getElementById('registerForm').addEventListener('submit', function(event) {
        let isValid = true;
        document.querySelectorAll('.error-message').forEach(e => e.textContent = '');

        const nombre = document.querySelector('[name="nombre"]');
        if (!nombre.checkValidity()) {
            document.getElementById('nombre-error').textContent =
                '<?= translate('El nombre debe tener entre 5 y 50 caracteres y solo letras') ?>';
            isValid = false;
        }

        const email = document.querySelector('[name="email"]');
        if (!email.checkValidity()) {
            document.getElementById('email-error').textContent =
                '<?= translate('El formato del correo electrónico no es válido') ?>';
            isValid = false;
        }

        const telefono = document.querySelector('[name="telefono"]');
        if (telefono.value && !telefono.checkValidity()) {
            document.getElementById('telefono-error').textContent =
                '<?= translate('El teléfono debe tener 10 dígitos y solo números') ?>';
            isValid = false;
        }

        const password = document.querySelector('[name="password"]');
        if (!password.checkValidity()) {
            document.getElementById('password-error').textContent =
                '<?= translate('La contraseña debe tener entre 8 y 20 caracteres') ?>';
            isValid = false;
        }

        const confirmPassword = document.querySelector('[name="confirm_password"]');
        if (!confirmPassword.checkValidity() || password.value !== confirmPassword.value) {
            document.getElementById('confirm-password-error').textContent =
                '<?= translate('Las contraseñas no coinciden o no cumplen los requisitos') ?>';
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