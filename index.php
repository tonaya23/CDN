<?php
// Iniciar la sesión para manejar los datos del carrito
session_start();

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
    <title>Climas del Norte - Servicios Profesionales de Climatización</title>
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
            <nav>
                <ul class="nav-links">
                    <li><a href="#inicio">Inicio</a></li>
                    <li><a href="#servicios">Servicios</a></li>
                    <li><a href="#nosotros">¿Por qué Elegirnos?</a></li>
                    <li><a href="#quienes_somos">Quienes Somos</a></li>
                    <li><a href="#galeria">Galería</a></li>
                    <li><a href="#testimonios">Testimonios</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                    <li class="cart-icon" id="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo count($_SESSION['carrito']); ?></span>
                        
                        <div class="cart-dropdown" id="cart-dropdown">
                            <h3>Mi Carrito</h3>
                            <?php if (empty($_SESSION['carrito'])): ?>
                                <p class="empty-cart">Tu carrito está vacío</p>
                            <?php else: ?>
                                <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                                <div class="cart-item">
                                    <div>
                                        <strong><?php echo $item['servicio']; ?></strong>
                                    </div>
                                    <div>
                                        $<?php echo number_format($item['precio'], 2); ?>
                                        <a href="?eliminar=<?php echo $key; ?>" class="delete-item"><i class="fas fa-trash"></i></a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="cart-total">
                                    <span>Total:</span>
                                    <span>$<?php echo number_format($total, 2); ?></span>
                                </div>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="#contacto" class="add-to-cart">Completar Solicitud</a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['carrito'])): ?>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="?vaciar" class="clear-cart" style="color: red; text-decoration: underline;">Vaciar carrito</a>
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
                <h1>Expertos en Climatización<br>y Confort Térmico</h1>
                <p>Soluciones profesionales de aire acondicionado, calefacción y ventilación para hogares y empresas. Más de 20 años de experiencia nos respaldan.</p>
                <a href="#contacto" class="cta-button">Solicitar Cotización</a>
            </div>
        </div>
    </section>

    <section class="services scroll-animation" id="servicios">
        <div class="container">
            <h2 class="section-title">Nuestros Servicios</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-image">
                        <img src="img/reparacion.jpg" alt="Aire Acondicionado">
                    </div>
                    <div class="service-content">
                        <h3>Reparación</h3>
                        <p>Nuestros Técnicos están capacitados y certificados en el área de refrigeración y aire acondicionado así como en el manejo de refrigerantes.</p>
                        <a href="?agregar=Reparacion" class="add-to-cart">Agregar</a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="img/instalacion.jpg" alt="instalacion">
                    </div>
                    <div class="service-content">
                        <h3>Instalación</h3>
                        <p>Las instalaciones realizadas de forma profesional le garantizan un mejor rendimiento de su equipo de climatización.</p>
                        <a href="?agregar=Instalacion" class="add-to-cart">Agregar</a>
                    </div>
                </div>
                <div class="service-card">
                    <div class="service-image">
                        <img src="img/mantenimiento.jpg" alt="Mantenimiento">
                    </div>
                    <div class="service-content">
                        <h3>Mantenimiento</h3>
                        <p>Un mantenimiento preventivo oportuno puede asegurarle larga vida a su equipo y un ahorro a su inversión</p>
                        <a href="?agregar=Mantenimiento" class="add-to-cart">Agregar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-us scroll-animation" id="nosotros">
        <div class="container">
            <h2 class="section-title">¿Por Qué Elegirnos?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Experiencia</h3>
                    <p>Más de 20 años brindando servicios de calidad en la región.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Profesionalismo</h3>
                    <p>Personal altamente capacitado y certificado.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Puntualidad</h3>
                    <p>Respetamos tu tiempo y cumplimos con los plazos establecidos.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Garantía</h3>
                    <p>Todos nuestros servicios cuentan con garantía por escrito.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-us scroll-animation" id="quienes_somos">
        <div class="container">
            <h2 class="section-title">Quiénes Somos</h2>
            <div class="about-content">
                <p class="about-description">Somos una empresa líder en servicios de climatización con más de 20 años de experiencia, comprometidos con brindar soluciones de calidad y confort térmico a nuestros clientes.</p>
                
                <div class="pillars-grid">
                    <div class="pillar-card">
                        <div class="pillar-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Misión</h3>
                        <p>Proporcionar soluciones integrales de climatización que mejoren la calidad de vida de nuestros clientes, garantizando eficiencia energética y satisfacción total.</p>
                    </div>

                    <div class="pillar-card">
                        <div class="pillar-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Visión</h3>
                        <p>Ser la empresa líder en soluciones de climatización en la región, reconocida por nuestra excelencia, innovación y compromiso con el medio ambiente.</p>
                    </div>

                    <div class="pillar-card">
                        <div class="pillar-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Valores</h3>
                        <ul class="values-list">
                            <li>Honestidad</li>
                            <li>Excelencia</li>
                            <li>Compromiso</li>
                            <li>Innovación</li>
                            <li>Responsabilidad</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="gallery scroll-animation" id="galeria">
        <div class="container">
            <h2 class="section-title">Galería de Proyectos</h2>
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
            <h2 class="section-title">Lo Que Dicen Nuestros Clientes</h2>
            <div class="testimonial-slider">
                <div class="testimonial-card">
                    <div class="testimonial-image">
                        <img src="img/clientefiel.jpg" alt="Cliente 1">
                    </div>
                    <p class="testimonial-text">"Excelente servicio, muy profesionales y puntuales. Totalmente recomendados."</p>
                    <h4>Juan Pérez</h4>
                    <p class="testimonial-role">Cliente Residencial</p>
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
        <h2 class="section-title">Contáctanos</h2>
        
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
                        <h3>Teléfonos</h3>
                        <p>878-763-5533</p>
                        <p>878-795-2019</p>
                    </div>
                </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h3>Dirección</h3>
                            <p>Venustiano Carranza No. 909 Col. Villa de Fuente. Piedras Negras Coah. MX</p>
                            <p> Sucursal: Lib. Armando Treviño 704 Col. Guillén. Piedras Negras Coah. MX</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3>Horario</h3>
                            <p>Lunes a Viernes: 9:00 AM - 6:00 PM</p>
                            <p>Sábados: 9:00 AM - 1:00 PM</p>
                        </div>
                    </div>
            </div>
            
            <!-- Formulario de contacto actualizado -->
            <form class="contact-form" method="POST" action="index.php#contacto">
                <div class="form-group">
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="telefono" class="form-control" placeholder="Teléfono" required>
                </div>
                
                <!-- Servicios seleccionados -->
                <div class="selected-services">
                    <h4>Servicios seleccionados:</h4>
                    <?php if (empty($_SESSION['carrito'])): ?>
                        <p>No has seleccionado ningún servicio</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                                <li>
                                    <?php echo $item['servicio']; ?> - $<?php echo number_format($item['precio'], 2); ?>
                                    <a href="?eliminar=<?php echo $key; ?>" class="delete-item"><i class="fas fa-times"></i></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="cart-total">Total: $<?php echo number_format($total, 2); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <textarea name="mensaje" class="form-control" rows="5" placeholder="Mensaje" required></textarea>
                </div>
                <button type="submit" class="cta-button">Enviar Solicitud</button>
            </form>
        </div>
    </div>
</section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>Sobre Nosotros</h3>
                    <p>Climas del Norte es tu aliado en soluciones de climatización. Expertos en instalación y mantenimiento de sistemas de aire acondicionado.</p>
                    <div class="social-links">
                        <a href="https://m.facebook.com/profile.php?id=142211439205918"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/climas.del.norte/?hl=es-la"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/8787635533"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Enlaces Rápidos</h3>
                    <ul class="footer-links">
                        <li><a href="#inicio">Inicio</a></li>
                        <li><a href="#servicios">Servicios</a></li>
                        <li><a href="#galeria">Galería</a></li>
                        <li><a href="#contacto">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Servicios</h3>
                    <ul class="footer-links">
                        <li>Reparacion</li>
                        <li>Instalacion</li>
                        <li>Mantenimiento</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; Soluciones Confortables S. A. de C. V. Todos los derechos reservados.</p>
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