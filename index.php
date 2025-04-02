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

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    header("Location: index.php#contacto");
    exit;
}

if (isset($_GET['agregar']) && !empty($_GET['agregar'])) {
    $servicio = $_GET['agregar'];
    $precio = 0;
    switch ($servicio) {
        case 'Reparacion': $precio = 1500; break;
        case 'Instalacion': $precio = 2500; break;
        case 'Mantenimiento': $precio = 800; break;
    }
    $_SESSION['carrito'][] = ['servicio' => $servicio, 'precio' => $precio];
    header("Location: index.php#servicios");
    exit;
}

if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar']) && isset($_SESSION['carrito'][$_GET['eliminar']])) {
    unset($_SESSION['carrito'][$_GET['eliminar']]);
    $_SESSION['carrito'] = array_values($_SESSION['carrito']);
    header("Location: index.php#contacto");
    exit;
}

$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'];
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['usuario_id'])) {
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

    $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
    $mensaje = filter_var($_POST['mensaje'], FILTER_SANITIZE_STRING);
    $usuario_id = $_SESSION['usuario_id'];

    if (empty($_SESSION['carrito'])) {
        $error = translate('Debes seleccionar al menos un servicio para enviar la solicitud');
    } elseif (strlen($nombre) < 5) {
        $error = translate('El nombre debe tener al menos 5 caracteres');
    } else {
        $sql = "INSERT INTO clientes (usuario_id, nombre, email, telefono) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE nombre = ?, email = ?, telefono = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $usuario_id, $nombre, $email, $telefono, $nombre, $email, $telefono);
        $stmt->execute();
        $cliente_id = $conn->insert_id ?: $conn->query("SELECT id FROM clientes WHERE usuario_id = $usuario_id")->fetch_assoc()['id'];

        if (!empty($_SESSION['carrito'])) {
            $sql = "INSERT INTO pedidos (usuario_id, cliente_id, total) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iid", $usuario_id, $cliente_id, $total);
            $stmt->execute();
            $pedido_id = $conn->insert_id;

            $sql = "INSERT INTO detalle_pedidos (pedido_id, servicio, precio) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($_SESSION['carrito'] as $item) {
                $stmt->bind_param("isd", $pedido_id, $item['servicio'], $item['precio']);
                $stmt->execute();
            }

            $_SESSION['carrito'] = [];
            $success = translate('¡Gracias por tu solicitud! Nos pondremos en contacto contigo pronto.');
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= translate('Climas del Norte - Servicios Profesionales de Climatización') ?></title>
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
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="<?= translate('Buscar en la página...') ?>" onkeyup="searchPage()">
                <i class="fas fa-search"></i>
            </div>
            <div class="language-switcher">
                <a href="?lang=es" class="<?= $_SESSION['lang'] == 'es' ? 'active' : '' ?>">ES</a> |
                <a href="?lang=en" class="<?= $_SESSION['lang'] == 'en' ? 'active' : '' ?>">EN</a>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="#inicio"><?= translate('Inicio') ?></a></li>
                    <li><a href="#servicios"><?= translate('Servicios') ?></a></li>
                    <li><a href="#nosotros"><?= translate('¿Por qué Elegirnos?') ?></a></li>
                    <li><a href="#quienes_somos"><?= translate('Quienes Somos') ?></a></li>
                    <li><a href="#galeria"><?= translate('Galería') ?></a></li>
                    <li><a href="#testimonios"><?= translate('Testimonios') ?></a></li>
                    <li><a href="#contacto"><?= translate('Contacto') ?></a></li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li class="user-menu">
                        <a href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></a>
                        <div class="user-dropdown">
                            <a href="?logout"><?= translate('Cerrar Sesión') ?></a>
                        </div>
                    </li>
                    <?php else: ?>
                    <li><a href="login.php"><?= translate('Iniciar Sesión') ?></a></li>
                    <li><a href="register.php"><?= translate('Registrarse') ?></a></li>
                    <?php endif; ?>
                    <li class="cart-icon" id="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo count($_SESSION['carrito']); ?></span>
                        <div class="cart-dropdown" id="cart-dropdown">
                            <h3><?= translate('Mi Carrito') ?></h3>
                            <?php if (empty($_SESSION['carrito'])): ?>
                            <p class="empty-cart"><?= translate('Tu carrito está vacío') ?></p>
                            <?php else: ?>
                            <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                            <div class="cart-item">
                                <div><strong><?= htmlspecialchars($item['servicio']) ?></strong></div>
                                <div>
                                    $<?= number_format($item['precio'], 2) ?>
                                    <a href="?eliminar=<?= $key ?>" class="delete-item"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="cart-total">
                                <span>Total:</span>
                                <span>$<?= number_format($total, 2) ?></span>
                            </div>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="#contacto" class="add-to-cart"><?= translate('Completar Solicitud') ?></a>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['carrito'])): ?>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="?vaciar" class="clear-cart"><?= translate('Vaciar carrito') ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Resto del HTML sin cambios -->
    <section class="hero" id="inicio">
        <div class="slider-container">
            <div class="slider">
                <div class="slide fade"><img src="images/pic3.jpg" alt="Climatización 1"></div>
                <div class="slide fade"><img src="images/pic2.jpg" alt="Climatización 2"></div>
                <div class="slide fade"><img src="images/pic1.jpg" alt="Climatización 3"></div>
            </div>
            <a class="prev" onclick="changeSlide(-1)">&#10094;</a>
            <a class="next" onclick="changeSlide(1)">&#10095;</a>
            <div class="dots">
                <span class="dot" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
            </div>
            <div class="hero-content">
                <h1><?= translate('Expertos en Climatización y Confort Térmico') ?></h1>
                <p><?= translate('Soluciones profesionales de aire acondicionado...') ?></p>
                <a href="#contacto" class="cta-button"><?= translate('Solicitar Cotización') ?></a>
            </div>
        </div>
    </section>

    <section class="services scroll-animation" id="servicios">
        <div class="container">
            <h2 class="section-title"><?= translate('Nuestros Servicios') ?></h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-image"><img src="img/reparacion.jpg" alt="Reparación"></div>
                    <div class="service-content">
                        <h3><?= translate('Reparación') ?></h3>
                        <p><?= translate('Nuestros Técnicos están capacitados y certificados en el área de refrigeración y aire acondicionado...') ?></p>
                        <a href="?agregar=Reparacion" class="add-to-cart"><?= translate('Agregar') ?></a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image"><img src="img/instalacion.jpg" alt="Instalación"></div>
                    <div class="service-content">
                        <h3><?= translate('Instalación') ?></h3>
                        <p><?= translate('Las instalaciones realizadas de forma profesional le garantizan un mejor rendimiento...') ?></p>
                        <a href="?agregar=Instalacion" class="add-to-cart"><?= translate('Agregar') ?></a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image"><img src="img/mantenimiento.jpg" alt="Mantenimiento"></div>
                    <div class="service-content">
                        <h3><?= translate('Mantenimiento') ?></h3>
                        <p><?= translate('Un mantenimiento preventivo oportuno puede asegurarle larga vida a su equipo...') ?></p>
                        <a href="?agregar=Mantenimiento" class="add-to-cart"><?= translate('Agregar') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-us scroll-animation" id="nosotros">
        <div class="container">
            <h2 class="section-title"><?= translate('¿Por Qué Elegirnos?') ?></h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-check-circle"></i></div>
                    <h3><?= translate('Experiencia') ?></h3>
                    <p><?= translate('Más de 20 años brindando servicios de calidad en la región.') ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-tools"></i></div>
                    <h3><?= translate('Profesionalismo') ?></h3>
                    <p><?= translate('Personal altamente capacitado y certificado.') ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h3><?= translate('Puntualidad') ?></h3>
                    <p><?= translate('Respetamos tu tiempo y cumplimos con los plazos establecidos.') ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-star"></i></div>
                    <h3><?= translate('Garantía') ?></h3>
                    <p><?= translate('Todos nuestros servicios cuentan con garantía por escrito.') ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-us scroll-animation" id="quienes_somos">
        <div class="container">
            <h2 class="section-title"><?= translate('Quiénes Somos') ?></h2>
            <div class="about-content">
                <p class="about-description">
                    <?= translate('Somos una empresa líder en servicios de climatización con más de 20 años de experiencia...') ?>
                </p>
                <div class="pillars-grid">
                    <div class="pillar-card">
                        <div class="pillar-icon"><i class="fas fa-bullseye"></i></div>
                        <h3><?= translate('Misión') ?></h3>
                        <p><?= translate('Proporcionar soluciones integrales de climatización que mejoren la calidad de vida...') ?></p>
                    </div>
                    <div class="pillar-card">
                        <div class="pillar-icon"><i class="fas fa-eye"></i></div>
                        <h3><?= translate('Visión') ?></h3>
                        <p><?= translate('Ser la empresa líder en soluciones de climatización en la región...') ?></p>
                    </div>
                    <div class="pillar-card">
                        <div class="pillar-icon"><i class="fas fa-star"></i></div>
                        <h3><?= translate('Valores') ?></h3>
                        <ul class="values-list">
                            <li><?= translate('Honestidad') ?></li>
                            <li><?= translate('Excelencia') ?></li>
                            <li><?= translate('Compromiso') ?></li>
                            <li><?= translate('Innovación') ?></li>
                            <li><?= translate('Responsabilidad') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="gallery scroll-animation" id="galeria">
        <div class="container">
            <h2 class="section-title"><?= translate('Galería de Proyectos') ?></h2>
            <div class="gallery-grid">
                <div class="gallery-item"><img src="img/pic4.jpg" alt="Proyecto 1">
                    <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                </div>
                <div class="gallery-item"><img src="img/pic5.jpg" alt="Proyecto 2">
                    <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                </div>
                <div class="gallery-item"><img src="img/pic6.jpg" alt="Proyecto 3">
                    <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                </div>
                <div class="gallery-item"><img src="img/pic7.jpg" alt="Proyecto 4">
                    <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials scroll-animation" id="testimonios">
        <div class="container">
            <h2 class="section-title"><?= translate('Lo Que Dicen Nuestros Clientes') ?></h2>
            <div class="testimonial-slider">
                <div class="testimonial-card">
                    <div class="testimonial-image"><img src="img/clientefiel.jpg" alt="Cliente 1"></div>
                    <p class="testimonial-text">
                        <?= translate('"Excelente servicio, muy profesionales y puntuales. Totalmente recomendados."') ?>
                    </p>
                    <h4>Juan Pérez</h4>
                    <p class="testimonial-role"><?= translate('Cliente Residencial') ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact scroll-animation" id="contacto">
        <div class="container">
            <h2 class="section-title"><?= translate('Contáctanos') ?></h2>
            <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h3><?= translate('Teléfonos') ?></h3>
                            <p>878-763-5533</p>
                            <p>878-795-2019</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h3><?= translate('Dirección') ?></h3>
                            <p><?= translate('Venustiano Carranza No. 909 Col. Villa de Fuente. Piedras Negras Coah. MX') ?></p>
                            <p><?= translate('Sucursal: Lib. Armando Treviño 704 Col. Guillén. Piedras Negras Coah. MX') ?></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div>
                            <h3><?= translate('Ubicación') ?></h3>
                            <div class="google-map">
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1237.571915066888!2d-100.56144852170576!3d28.678218303007753!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x865f8b0826f89e63%3A0xdae232dc26abc76a!2sClimas%20del%20Norte!5e0!3m2!1ses!2smx!4v1743301110858!5m2!1ses!2smx"
                                    width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3><?= translate('Horario') ?></h3>
                            <p><?= translate('Lunes a Viernes: 9:00 AM - 6:00 PM') ?></p>
                            <p><?= translate('Sábados: 9:00 AM - 1:00 PM') ?></p>
                        </div>
                    </div>
                </div>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                <form class="contact-form" method="POST" action="index.php#contacto" onsubmit="return validarFormulario()">
                    <div class="form-group">
                        <input type="text" name="nombre" class="form-control" placeholder="<?= translate('Nombre completo') ?>" required minlength="5" maxlength="50" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" title="<?= translate('Solo letras y espacios, mínimo 5 caracteres') ?>">
                        <small class="error-message" id="nombre-error"></small>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" class="form-control" placeholder="<?= translate('Correo electrónico') ?>" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        <small class="error-message" id="email-error"></small>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="telefono" class="form-control" placeholder="<?= translate('Teléfono') ?>" required pattern="[0-9]{10}" title="<?= translate('10 dígitos sin espacios') ?>">
                        <small class="error-message" id="telefono-error"></small>
                    </div>
                    <div class="selected-services">
                        <h4><?= translate('Servicios seleccionados:') ?></h4>
                        <?php if (empty($_SESSION['carrito'])): ?>
                        <p><?= translate('No has seleccionado ningún servicio') ?></p>
                        <?php else: ?>
                        <ul>
                            <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                            <li>
                                <?= htmlspecialchars($item['servicio']) ?> - $<?= number_format($item['precio'], 2) ?>
                                <a href="?eliminar=<?= $key ?>" class="delete-item"><i class="fas fa-times"></i></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="cart-total"><?= translate('Total:') ?> $<?= number_format($total, 2) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <textarea name="mensaje" class="form-control" rows="5" placeholder="<?= translate('Mensaje') ?>" required></textarea>
                    </div>
                    <button type="submit" class="cta-button"><?= translate('Enviar Solicitud') ?></button>
                </form>
                <?php else: ?>
                <div class="login-required">
                    <div class="login-message">
                        <i class="fas fa-lock"></i>
                        <h3><?= translate('Inicia sesión para completar tu solicitud') ?></h3>
                        <p><?= translate('Para poder enviar una solicitud de servicio, debes iniciar sesión o crear una cuenta.') ?></p>
                        <div class="login-buttons">
                            <a href="login.php" class="cta-button"><?= translate('Iniciar Sesión') ?></a>
                            <a href="register.php" class="secondary-button"><?= translate('Crear Cuenta') ?></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3><?= translate('Sobre Nosotros') ?></h3>
                    <p><?= translate('Climas del Norte es tu aliado en soluciones de climatización...') ?></p>
                    <div class="social-links">
                        <a href="https://m.facebook.com/profile.php?id=142211439205918"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/climas.del.norte/?hl=es-la"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/8787635533"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3><?= translate('Enlaces Rápidos') ?></h3>
                    <ul class="footer-links">
                        <li><a href="#inicio"><?= translate('Inicio') ?></a></li>
                        <li><a href="#servicios"><?= translate('Servicios') ?></a></li>
                        <li><a href="#galeria"><?= translate('Galería') ?></a></li>
                        <li><a href="#contacto"><?= translate('Contacto') ?></a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3><?= translate('Servicios') ?></h3>
                    <ul class="footer-links">
                        <li><?= translate('Reparacion') ?></li>
                        <li><?= translate('Instalacion') ?></li>
                        <li><?= translate('Mantenimiento') ?></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?= translate('Soluciones Confortables S. A. de C. V. Todos los derechos reservados.') ?></p>
            </div>
        </div>
    </footer>

    <a href="https://wa.me/8787635533" class="whatsapp-button" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <div class="modal" id="gallery-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <img src="" alt="" class="modal-image">
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    function validarFormulario() {
        let isValid = true;
        const nombre = document.querySelector('input[name="nombre"]');
        const email = document.querySelector('input[name="email"]');
        const telefono = document.querySelector('input[name="telefono"]');
        const carrito = <?php echo json_encode($_SESSION['carrito']); ?>;

        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

        if (!nombre.checkValidity() || nombre.value.length < 5) {
            document.getElementById('nombre-error').textContent =
                '<?= translate('El nombre debe tener al menos 5 caracteres y solo letras') ?>';
            isValid = false;
        }
        if (!email.checkValidity()) {
            document.getElementById('email-error').textContent =
                '<?= translate('Por favor, ingresa un correo válido') ?>';
            isValid = false;
        }
        if (!telefono.checkValidity()) {
            document.getElementById('telefono-error').textContent =
                '<?= translate('Por favor, ingresa un teléfono válido de 10 dígitos') ?>';
            isValid = false;
        }
        if (!carrito || carrito.length === 0) {
            alert('<?= translate('Debes seleccionar al menos un servicio para enviar la solicitud') ?>');
            isValid = false;
        }

        if (!isValid) return false;
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cartIcon = document.getElementById('cart-icon');
        const cartDropdown = document.getElementById('cart-dropdown');

        cartIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            cartDropdown.style.display = cartDropdown.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function(e) {
            if (!cartIcon.contains(e.target)) {
                cartDropdown.style.display = 'none';
            }
        });
    });

    function searchPage() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const sections = document.querySelectorAll('section');
        
        sections.forEach(section => {
            const textContent = section.textContent.toLowerCase();
            if (textContent.includes(input) || input === '') {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });

        // Asegurar que el header y footer siempre estén visibles
        document.querySelector('header').style.display = 'block';
        document.querySelector('footer').style.display = 'block';
    }
    </script>
</body>
</html>