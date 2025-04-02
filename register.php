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
$nombre = '';
$email = '';
$telefono = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $valid = true;

    if (strlen($nombre) < 3 || strlen($nombre) > 50) {
        $error = translate('El nombre debe tener entre 3 y 50 caracteres');
        $valid = false;
    }

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

    if (!empty($telefono) && !preg_match('/^[0-9]{10}$/', $telefono)) {
        $error = translate('El teléfono debe tener 10 dígitos');
        $valid = false;
    }

    if (strlen($password) < 8 || strlen($password) > 20) {
        $error = translate('La contraseña debe tener entre 8 y 20 caracteres');
        $valid = false;
    }

    if ($password !== $confirm_password) {
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
        } else {
            $error = translate('Error al registrar: ') . $conn->error;
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
    <title><?= translate('Registro - Climas del Norte') ?></title>
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
                       value="<?= htmlspecialchars($nombre) ?>" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" 
                       title="<?= translate('Solo letras y espacios') ?>" required>
                <small class="error-message" id="nombre-error"></small>
            </div>
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="<?= translate('Correo electrónico') ?>" 
                       value="<?= htmlspecialchars($email) ?>" required>
                <small class="error-message" id="email-error"></small>
            </div>
            <div class="form-group">
                <input type="tel" name="telefono" class="form-control" placeholder="<?= translate('Teléfono (10 dígitos)') ?>" 
                       value="<?= htmlspecialchars($telefono) ?>" pattern="[0-9]{10}" 
                       title="<?= translate('10 dígitos sin espacios') ?>">
                <small class="error-message" id="telefono-error"></small>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="<?= translate('Contraseña') ?>" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('password', this)"></i>
                </div>
                <small class="password-requirements"><?= translate('La contraseña debe tener entre 8 y 20 caracteres') ?></small>
                <small class="error-message" id="password-error"></small>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           placeholder="<?= translate('Confirmar contraseña') ?>" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('confirm_password', this)"></i>
                </div>
                <small class="error-message" id="confirm-password-error"></small>
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
        const errors = document.querySelectorAll('.error-message');
        errors.forEach(e => e.textContent = '');

        const nombre = document.querySelector('[name="nombre"]');
        if (nombre.value.length < 3 || nombre.value.length > 50) {
            document.getElementById('nombre-error').textContent = '<?= translate('El nombre debe tener entre 3 y 50 caracteres') ?>';
            isValid = false;
        }

        const email = document.querySelector('[name="email"]');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            document.getElementById('email-error').textContent = '<?= translate('El formato del correo electrónico no es válido') ?>';
            isValid = false;
        }

        const telefono = document.querySelector('[name="telefono"]');
        if (telefono.value && !/^[0-9]{10}$/.test(telefono.value)) {
            document.getElementById('telefono-error').textContent = '<?= translate('El teléfono debe tener 10 dígitos') ?>';
            isValid = false;
        }

        const password = document.querySelector('[name="password"]');
        if (password.value.length < 8 || password.value.length > 20) {
            document.getElementById('password-error').textContent = '<?= translate('La contraseña debe tener entre 8 y 20 caracteres') ?>';
            isValid = false;
        }

        const confirmPassword = document.querySelector('[name="confirm_password"]');
        if (password.value !== confirmPassword.value) {
            document.getElementById('confirm-password-error').textContent = '<?= translate('Las contraseñas no coinciden') ?>';
            isValid = false;
        }

        if (!isValid) event.preventDefault();
    });
    </script>
</body>
</html>