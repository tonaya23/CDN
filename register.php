<?php
session_start();

// Configuración de idioma
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'es'; // Idioma por defecto
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: ".strtok($_SERVER['REQUEST_URI'], '?')); // Elimina parámetros de URL
    exit;
}

// Carga el autoload de Composer
require_once 'vendor/autoload.php';

// Función de traducción automática
function translate($text) {
    static $translator = null;
    
    if ($_SESSION['lang'] == 'es') {
        return $text; // No traducir si ya está en español
    }
    
    if ($translator === null) {
        $translator = new Stichoza\GoogleTranslate\GoogleTranslate();
        $translator->setSource('es');
        $translator->setTarget($_SESSION['lang']);
    }
    
    try {
        return $translator->translate($text);
    } catch (Exception $e) {
        return $text; // Si falla la traducción, devuelve el texto original
    }
}

// Verificar si el usuario ya está logueado
if (isset($_SESSION['usuario_id'])) {
    // Redirigir al index
    header("Location: index.php");
    exit;
}

// Variables para mensajes y datos
$error = '';
$success = '';
$nombre = '';
$email = '';
$telefono = '';

// Procesar el formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Conexión a la base de datos
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    $valid = true;
    
    // Validar nombre
    if (strlen($nombre) < 3 || strlen($nombre) > 50) {
        $error = translate('El nombre debe tener entre 3 y 50 caracteres');
        $valid = false;
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = translate('El formato del correo electrónico no es válido');
        $valid = false;
    } else {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = translate('Este correo electrónico ya está registrado');
            $valid = false;
        }
        
        $stmt->close();
    }
    
    // Validar teléfono (opcional pero debe tener formato correcto si se proporciona)
    if (!empty($telefono) && !preg_match('/^[0-9]{10}$/', $telefono)) {
        $error = translate('El teléfono debe tener 10 dígitos');
        $valid = false;
    }
    
    // Validar contraseña
    if (strlen($password) < 8 || strlen($password) > 20) {
        $error = translate('La contraseña debe tener entre 8 y 20 caracteres');
        $valid = false;
    }
    
    // Verificar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $error = translate('Las contraseñas no coinciden');
        $valid = false;
    }
    
    // Si todo es válido, registrar al usuario
    if ($valid) {
        // Hash de la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, telefono) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $email, $hashed_password, $telefono);
        
        if ($stmt->execute()) {
            $success = translate('¡Registro exitoso! Ahora puedes iniciar sesión');
            // Limpiar variables
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
    <style>
        .register-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }

        .success-message {
            color: #2ecc71;
            font-size: 16px;
            padding: 10px;
            background-color: #d4edda;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .register-btn:hover {
            background-color: #2980b9;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .back-to-home {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #777;
            text-decoration: none;
            font-size: 14px;
        }

        .back-to-home:hover {
            color: #555;
        }

        .password-requirements {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <header>
        <div class="container nav-container">
            <div class="logo">
                <div class="logo-text">CDN</div>
                <span>Climas del Norte</span>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="language-switcher">
                <a href="?lang=es" class="<?= $_SESSION['lang'] == 'es' ? 'active' : '' ?>">ES</a> |
                <a href="?lang=en" class="<?= $_SESSION['lang'] == 'en' ? 'active' : '' ?>">EN</a>
            </div>
        </div>
    </header>

    <div class="register-container">
        <h2><?= translate('Crear Cuenta') ?></h2>
        
        <?php if ($error): ?>
            <div class="error-message" style="text-align: center; margin-bottom: 15px;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?= $success ?>
                <div style="margin-top: 10px;">
                    <a href="login.php" style="color: #218838; text-decoration: underline;">
                        <?= translate('Ir a iniciar sesión') ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
            <div class="form-group">
                <input type="text" name="nombre" class="form-control" 
                    placeholder="<?= translate('Nombre completo') ?>" 
                    value="<?= htmlspecialchars($nombre); ?>"
                    pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+"
                    title="<?= translate('Solo letras y espacios') ?>"
                    required>
                <small class="error-message" id="nombre-error"></small>
            </div>
            
            <div class="form-group">
                <input type="email" name="email" class="form-control" 
                    placeholder="<?= translate('Correo electrónico') ?>" 
                    value="<?= htmlspecialchars($email); ?>"
                    required>
                <small class="error-message" id="email-error"></small>
            </div>
            
            <div class="form-group">
                <input type="tel" name="telefono" class="form-control" 
                    placeholder="<?= translate('Teléfono (10 dígitos)') ?>" 
                    value="<?= htmlspecialchars($telefono); ?>"
                    pattern="[0-9]{10}"
                    title="<?= translate('10 dígitos sin espacios') ?>">
                <small class="error-message" id="telefono-error"></small>
            </div>
            
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control" 
                        placeholder="<?= translate('Contraseña') ?>" 
                        required>
                    <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('password', this)"></i>
                </div>
                <small class="password-requirements">
                    <?= translate('La contraseña debe tener entre 8 y 20 caracteres') ?>
                </small>
                <small class="error-message" id="password-error"></small>
            </div>
            
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                        placeholder="<?= translate('Confirmar contraseña') ?>" 
                        required>
                    <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility('confirm_password', this)"></i>
                </div>
                <small class="error-message" id="confirm-password-error"></small>
            </div>
            
            <button type="submit" class="register-btn"><?= translate('Registrarse') ?></button>
            
            <a href="login.php" class="login-link">
                <?= translate('¿Ya tienes cuenta? Inicia sesión aquí') ?>
            </a>
            
            <a href="index.php" class="back-to-home">
                <?= translate('Volver a la página principal') ?>
            </a>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(inputId, icon) {
            const passwordInput = document.getElementById(inputId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validación cliente
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            let isValid = true;
            
            // Validar nombre
            const nombre = document.getElementsByName('nombre')[0];
            if (nombre.value.length < 3 || nombre.value.length > 50) {
                document.getElementById('nombre-error').textContent = 
                    '<?= translate('El nombre debe tener entre 3 y 50 caracteres') ?>';
                isValid = false;
            } else {
                document.getElementById('nombre-error').textContent = '';
            }
            
            // Validar email
            const email = document.getElementsByName('email')[0];
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                document.getElementById('email-error').textContent = 
                    '<?= translate('El formato del correo electrónico no es válido') ?>';
                isValid = false;
            } else {
                document.getElementById('email-error').textContent = '';
            }
            
            // Validar teléfono (si está presente)
            const telefono = document.getElementsByName('telefono')[0];
            if (telefono.value && !(/^[0-9]{10}$/.test(telefono.value))) {
                document.getElementById('telefono-error').textContent = 
                    '<?= translate('El teléfono debe tener 10 dígitos') ?>';
                isValid = false;
            } else {
                document.getElementById('telefono-error').textContent = '';
            }
            
            // Validar contraseña
            const password = document.getElementsByName('password')[0];
            if (password.value.length < 8 || password.value.length > 20) {
                document.getElementById('password-error').textContent = 
                    '<?= translate('La contraseña debe tener entre 8 y 20 caracteres') ?>';
                isValid = false;
            } else {
                document.getElementById('password-error').textContent = '';
            }
            
            // Verificar que las contraseñas coinciden
            const confirmPassword = document.getElementsByName('confirm_password')[0];
            if (password.value !== confirmPassword.value) {
                document.getElementById('confirm-password-error').textContent = 
                    '<?= translate('Las contraseñas no coinciden') ?>';
                isValid = false;
            } else {
                document.getElementById('confirm-password-error').textContent = '';
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>