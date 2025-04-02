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

$error = '';
$email = '';

// Procesar el formulario de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Conexión a la base de datos
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    // Obtener datos del formulario
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = translate('El formato del correo electrónico no es válido');
    } else {
        // Buscar usuario en la base de datos
        $stmt = $conn->prepare("SELECT id, nombre, password FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();

            // Verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                // Contraseña correcta, iniciar sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                
                // Redirigir 
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                // Contraseña incorrecta
                $error = translate('Email o contraseña incorrectos');
            }
        } else {
            // Usuario no encontrado
            $error = translate('Email o contraseña incorrectos');
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
    <title><?= translate('Iniciar Sesión - Climas del Norte') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
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

        .login-btn {
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

        .login-btn:hover {
            background-color: #2980b9;
        }

        .register-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .register-link:hover {
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

    <div class="login-container">
        <h2><?= translate('Iniciar Sesión') ?></h2>
        
        <?php if ($error): ?>
            <div class="error-message" style="text-align: center; margin-bottom: 15px;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="email" name="email" class="form-control" 
                    placeholder="<?= translate('Correo electrónico') ?>" 
                    value="<?= htmlspecialchars($email); ?>" 
                    required>
            </div>
            
            <div class="form-group">
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control" 
                        placeholder="<?= translate('Contraseña') ?>" 
                        required>
                    <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility()"></i>
                </div>
            </div>
            
            <button type="submit" class="login-btn"><?= translate('Iniciar Sesión') ?></button>
            
            <a href="register.php" class="register-link">
                <?= translate('¿No tienes cuenta? Regístrate aquí') ?>
            </a>
            
            <a href="index.php" class="back-to-home">
                <?= translate('Volver a la página principal') ?>
            </a>
        </form>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            
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
    </script>
</body>
</html>