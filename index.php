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
function __($text) {
    static $translator = null;
    
    if ($_SESSION['lang'] == 'es') {
        return $text;
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

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Vaciar el carrito completamente
if (isset($_GET['vaciar'])) {
$_SESSION['carrito'] = [];
header("Location: index.php");
exit;
}

// Manejar agregar al carrito
if (isset($_GET['agregar']) && !empty($_GET['agregar'])) {
$servicio = $_GET['agregar'];
$precio = 0;

// Asignar precios según el servicio
switch ($servicio) {
case 'Reparacion':
$precio = 1500;
break;
case 'Instalacion':
$precio = 2500;
break;
case 'Mantenimiento':
$precio = 800;
break;
}

// Agregar al carrito
$_SESSION['carrito'][] = [
'servicio' => $servicio,
'precio' => $precio
];

// Redirigir a la sección de contacto
header("Location: index.php#contacto");
exit;
}

// Eliminar un elemento del carrito
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar']) && isset($_SESSION['carrito'][$_GET['eliminar']])) {
unset($_SESSION['carrito'][$_GET['eliminar']]);
$_SESSION['carrito'] = array_values($_SESSION['carrito']); // Reindexar el array
header("Location: index.php#contacto");
exit;
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Climas del Norte - Servicios Profesionales de Climatización') ?></title>
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
            <div class="language-switcher">
                <a href="?lang=es" class="<?= $_SESSION['lang'] == 'es' ? 'active' : '' ?>">ES</a> |
                <a href="?lang=en" class="<?= $_SESSION['lang'] == 'en' ? 'active' : '' ?>">EN</a>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="#inicio"><?= __('Inicio') ?></a></li>
                    <li><a href="#servicios"><?= __('Servicios') ?></a></li>
                    <li><a href="#nosotros"><?= __('¿Por qué Elegirnos?') ?></a></li>
                    <li><a href="#quienes_somos"><?= __('Quienes Somos') ?></a></li>
                    <li><a href="#galeria"><?= __('Galería') ?></a></li>
                    <li><a href="#testimonios"><?= __('Testimonios') ?></a></li>
                    <li><a href="#contacto"><?= __('Contacto') ?></a></li>
                    <li class="cart-icon" id="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo count($_SESSION['carrito']); ?></span>

                        <div class="cart-dropdown" id="cart-dropdown">
                            <h3><?= __('Mi Carrito') ?></h3>
                            <?php if (empty($_SESSION['carrito'])): ?>
                            <p class="empty-cart"><?= __('Tu carrito está vacío') ?></p>
                            <?php else: ?>
                            <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                            <div class="cart-item">
                                <div>
                                    <strong><?php echo $item['servicio']; ?></strong>
                                </div>
                                <div>
                                    $<?php echo number_format($item['precio'], 2); ?>
                                    <a href="?eliminar=<?php echo $key; ?>" class="delete-item"><i
                                            class="fas fa-trash"></i></a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="cart-total">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="#contacto" class="add-to-cart"><?= __('Completar Solicitud') ?></a>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['carrito'])): ?>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="?vaciar" class="clear-cart"><?= __('Vaciar carrito') ?></a>

                            </div>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero" id="inicio">
        <div class="slider-container">
            <div class="slider">
                <div class="slide fade">
                    <img src="images/pic3.jpg" alt="Climatización 1">
                </div>
                <div class="slide fade">
                    <img src="images/pic2.jpg" alt="Climatización 2">
                </div>
                <div class="slide fade">
                    <img src="images/pic1.jpg" alt="Climatización 3">
                </div>
            </div>

            <!-- Botones de navegación -->
            <a class="prev" onclick="changeSlide(-1)">&#10094;</a>
            <a class="next" onclick="changeSlide(1)">&#10095;</a>

            <!-- Indicadores de punto -->
            <div class="dots">
                <span class="dot" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
            </div>

            <div class="hero-content">
                <h1><?= __('Expertos en Climatización y Confort Térmico') ?></h1>
                <p><?= __('Soluciones profesionales de aire acondicionado...') ?></p>
                <a href="#contacto" class="cta-button"><?= __('Solicitar Cotización') ?></a>
            </div>
        </div>
    </section>

    <section class="services scroll-animation" id="servicios">
        <div class="container">
            <h2 class="section-title"><?= __('Nuestros Servicios') ?></h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-image">
                        <img src="img/reparacion.jpg" alt="Aire Acondicionado">
                    </div>
                    <div class="service-content">
                        <h3><?= __('Reparación') ?></h3>
                        <p><?= __('Nuestros Técnicos están capacitados y certificados en el área de refrigeración y aire
                            acondicionado así como en el manejo de refrigerantes.') ?></p>
                        <a href="?agregar=Reparacion" class="add-to-cart"><?= __('Agregar') ?></a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="img/instalacion.jpg" alt="instalacion">
                    </div>
                    <div class="service-content">
                        <h3><?= __('Instalación') ?></h3>
                        <p><?= __('Las instalaciones realizadas de forma profesional le garantizan un mejor rendimiento de su
                            equipo de climatización.') ?></p>
                        <a href="?agregar=Instalacion" class="add-to-cart"><?= __('Agregar') ?></a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="img/mantenimiento.jpg" alt="Mantenimiento">
                    </div>
                    <div class="service-content">
                        <h3><?= __('Mantenimiento') ?></h3>
                        <p><?= __('Un mantenimiento preventivo oportuno puede asegurarle larga vida a su equipo y un ahorro a su
                            inversión') ?></p>
                        <a href="?agregar=Mantenimiento" class="add-to-cart"><?= __('Agregar') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-us scroll-animation" id="nosotros">
        <div class="container">
            <h2 class="section-title"><?= __('¿Por Qué Elegirnos?') ?></h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?= __('Experiencia') ?></h3>
                    <p><?= __('Más de 20 años brindando servicios de calidad en la región.') ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3><?= __('Profesionalismo') ?></h3>
                    <p><?= __('Personal altamente capacitado y certificado.') ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?= __('Puntualidad') ?></h3>
                    <p><?= __('Respetamos tu tiempo y cumplimos con los plazos establecidos.') ?></p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3><?= __('Garantía') ?></h3>
                    <p><?= __('Todos nuestros servicios cuentan con garantía por escrito.') ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-us scroll-animation" id="quienes_somos">
        <div class="container">
            <h2 class="section-title"><?= __('Quiénes Somos') ?></h2>
            <div class="about-content">
                <p class="about-description">
                    <?= __('Somos una empresa líder en servicios de climatización con más de 20 años de experiencia, comprometidos con brindar soluciones de calidad y confort térmico a nuestros clientes.') ?>
                </p>
                <div class="pillars-grid">
                    <div class="pillar-card">
                        <div class="pillar-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3><?= __('Misión') ?></h3>
                        <p><?= __('Proporcionar soluciones integrales de climatización que mejoren la calidad de vida de nuestros clientes, garantizando eficiencia energética y satisfacción total.') ?>
                        </p>
                    </div>
                    <div class="pillar-card">
                        <div class="pillar-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3><?= __('Visión') ?></h3>
                        <p><?= __('Ser la empresa líder en soluciones de climatización en la región, reconocida por nuestra excelencia, innovación y compromiso con el medio ambiente.') ?>
                        </p>
                    </div>

                    <div class="pillar-card">
                        <div class="pillar-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3><?= __('Valores') ?></h3>
                        <ul class="values-list">
                            <li><?= __('Honestidad') ?></li>
                            <li><?= __('Excelencia') ?></li>
                            <li><?= __('Compromiso') ?></li>
                            <li><?= __('Innovación') ?></li>
                            <li><?= __('Responsabilidad') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="gallery scroll-animation" id="galeria">
        <div class="container">
            <h2 class="section-title"><?= __('Galería de Proyectos') ?></h2>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="img/pic4.jpg" alt="Proyecto 1">
                    <div class="gallery-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="img/pic5.jpg" alt="Proyecto 1">
                    <div class="gallery-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="img/pic6.jpg" alt="Proyecto 1">
                    <div class="gallery-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="img/pic7.jpg" alt="Proyecto 1">
                    <div class="gallery-overlay">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials scroll-animation" id="testimonios">
        <div class="container">
            <h2 class="section-title"><?= __('Lo Que Dicen Nuestros Clientes') ?></h2>
            <div class="testimonial-slider">
                <div class="testimonial-card">
                    <div class="testimonial-image">
                        <img src="img/clientefiel.jpg" alt="Cliente 1">
                    </div>
                    <p class="testimonial-text">
                        <?= __('"Excelente servicio, muy profesionales y puntuales. Totalmente recomendados."') ?></p>
                    <h4>Juan Pérez</h4>
                    <p class="testimonial-role"><?= __('Cliente Residencial') ?></p>
                </div>
            </div>
        </div>
    </section>

    <?php
// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Conexión a la base de datos
    $conn = new mysqli("localhost", "root", "", "cdn_servicios");
    
    // Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    // Obtener datos del formulario
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $mensaje = $_POST['mensaje'];
    
    // Insertar cliente
    $sql = "INSERT INTO clientes (nombre, email, telefono) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $telefono);
    $stmt->execute();
    $cliente_id = $conn->insert_id;
    
    // Verificar si hay servicios en el carrito
    if (!empty($_SESSION['carrito'])) {
        // Calcular total
        $total = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $total += $item['precio'];
        }
        
        // Crear pedido
        $sql = "INSERT INTO pedidos (cliente_id, total) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $cliente_id, $total);
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        
        // Insertar detalles del pedido
        $sql = "INSERT INTO detalle_pedidos (pedido_id, servicio, precio) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        foreach ($_SESSION['carrito'] as $item) {
            $stmt->bind_param("isd", $pedido_id, $item['servicio'], $item['precio']);
            $stmt->execute();
        }
        
        // Limpiar carrito
        $_SESSION['carrito'] = [];
        
        // Mensaje de éxito
        $mensaje_exito = "¡Gracias por tu solicitud! Nos pondremos en contacto contigo pronto.";
    }
    
    $conn->close();
}
?>
    <section class="contact scroll-animation" id="contacto">
        <div class="container">
            <h2 class="section-title"><?= __('Contáctanos') ?></h2>
            =
            <?php if(isset($mensaje_exito)): ?>
            <div class="success-message">
                <?php echo $mensaje_exito; ?>
            </div>
            <?php endif; ?>

            <div class="contact-grid">
                <div class="contact-info">
                    <!-- Información de contacto - sin cambios -->
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h3><?= __('Teléfonos') ?></h3>
                            <p>878-763-5533</p>
                            <p>878-795-2019</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h3><?= __('Dirección') ?></h3>
                            <p><?= __('Venustiano Carranza No. 909 Col. Villa de Fuente. Piedras Negras Coah. MX') ?>
                            </p>
                            <p><?= __('Sucursal: Lib. Armando Treviño 704 Col. Guillén. Piedras Negras Coah. MX') ?></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div>
                            <h3><?= __('Ubicación') ?></h3>
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
                            <h3><?= __('Horario') ?></h3>
                            <p><?= __('Lunes a Viernes: 9:00 AM - 6:00 PM') ?></p>
                            <p><?= __('Sábados: 9:00 AM - 1:00 PM') ?></p>
                        </div>
                    </div>
                </div>

                <form class="contact-form" method="POST" action="index.php#contacto"
                    onsubmit="return validarFormulario()">
                    <div class="form-group">
                        <input type="text" name="nombre" class="form-control" placeholder="<?= __('Nombre completo') ?>"
                            required minlength="3" maxlength="50" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+"
                            title="<?= __('Solo letras y espacios') ?>">
                        <small class="error-message" id="nombre-error"></small>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" class="form-control"
                            placeholder="<?= __('Correo electrónico') ?>" required
                            pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        <small class="error-message" id="email-error"></small>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="telefono" class="form-control" placeholder="<?= __('Teléfono') ?>"
                            required pattern="[0-9]{10}" title="<?= __('10 dígitos sin espacios') ?>">
                        <small class="error-message" id="telefono-error"></small>
                    </div>

                    <!-- Servicios seleccionados -->
                    <div class="selected-services">
                        <h4><?= __('Servicios seleccionados:') ?></h4>
                        <?php if (empty($_SESSION['carrito'])): ?>
                        <p><?= __('No has seleccionado ningún servicio') ?></p>
                        <?php else: ?>
                        <ul>
                            <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                            <li>
                                <?php echo $item['servicio']; ?> - $<?php echo number_format($item['precio'], 2); ?>
                                <a href="?eliminar=<?php echo $key; ?>" class="delete-item"><i
                                        class="fas fa-times"></i></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="cart-total"><?= __('Total:') ?> $<?php echo number_format($total, 2); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <textarea name="mensaje" class="form-control" rows="5" placeholder="<?= __('Mensaje') ?>"
                            required></textarea>
                    </div>
                    <button type="submit" class="cta-button"><?= __('Enviar Solicitud') ?></button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3><?= __('Sobre Nosotros') ?></h3>
                    <p><?= __('Climas del Norte es tu aliado en soluciones de climatización. Expertos en instalación y mantenimiento de sistemas de aire acondicionado.') ?>
                    </p>
                    <div class="social-links">
                        <a href="https://m.facebook.com/profile.php?id=142211439205918"><i
                                class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/climas.del.norte/?hl=es-la"><i
                                class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/8787635533"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3><?= __('Enlaces Rápidos') ?></h3>
                    <ul class="footer-links">
                        <li><a href="#inicio"><?= __('Inicio') ?></a></li>
                        <li><a href="#servicios"><?= __('Servicios') ?></a></li>
                        <li><a href="#galeria"><?= __('Galería') ?></a></li>
                        <li><a href="#contacto"><?= __('Contacto') ?></a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3><?= __('Servicios') ?></h3>
                    <ul class="footer-links">
                        <li><?= __('Reparacion') ?></li>
                        <li><?= __('Instalacion') ?></li>
                        <li><?= __('Mantenimiento') ?></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?= __('Soluciones Confortables S. A. de C. V. Todos los derechos reservados.') ?></p>
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
    // Funcionalidad del carrito
    document.addEventListener('DOMContentLoaded', function() {
        const cartIcon = document.getElementById('cart-icon');
        const cartDropdown = document.getElementById('cart-dropdown');

        // Mostrar/ocultar carrito al hacer clic
        cartIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            if (cartDropdown.style.display === 'block') {
                cartDropdown.style.display = 'none';
            } else {
                cartDropdown.style.display = 'block';
            }
        });

        // Cerrar carrito al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!cartIcon.contains(e.target)) {
                cartDropdown.style.display = 'none';
            }
        });

        // Preseleccionar servicios en el formulario según carrito
        const servicioSelect = document.getElementById('servicio-select');
        if (servicioSelect) {
            // Código PHP ya maneja la preselección con sus atributos "selected"
        }
    });
    </script>
</body>

</html>