  // Menú móvil
  const menuToggle = document.querySelector('.menu-toggle');
  const navLinks = document.querySelector('.nav-links');

  menuToggle.addEventListener('click', () => {
      navLinks.classList.toggle('active');
  });

  // Header scroll effect
  window.addEventListener('scroll', () => {
      const header = document.querySelector('header');
      if (window.scrollY > 100) {
          header.classList.add('scrolled');
      } else {
          header.classList.remove('scrolled');
      }
  });

  // Animaciones de scroll
  const scrollElements = document.querySelectorAll('.scroll-animation');

  const elementInView = (el, dividend = 1) => {
      const elementTop = el.getBoundingClientRect().top;
      return (
          elementTop <=
          (window.innerHeight || document.documentElement.clientHeight) / dividend
      );
  };

  const displayScrollElement = (element) => {
      element.classList.add('active');
  };

  const handleScrollAnimation = () => {
      scrollElements.forEach((el) => {
          if (elementInView(el, 1.25)) {
              displayScrollElement(el);
          }
      });
  };

  window.addEventListener('scroll', () => {
      handleScrollAnimation();
  });

  // Modal de galería
  const galleryItems = document.querySelectorAll('.gallery-item');
  const modal = document.getElementById('gallery-modal');
  const modalImg = document.querySelector('.modal-image');
  const closeModal = document.querySelector('.close-modal');

  galleryItems.forEach(item => {
      item.addEventListener('click', () => {
          modal.style.display = 'block';
          modalImg.src = item.querySelector('img').src;
      });
  });

  closeModal.addEventListener('click', () => {
      modal.style.display = 'none';
  });

  window.addEventListener('click', (e) => {
      if (e.target === modal) {
          modal.style.display = 'none';
      }
  });

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
          e.preventDefault();
          document.querySelector(this.getAttribute('href')).scrollIntoView({
              behavior: 'smooth'
          });
      });
  });


let slideIndex = 1;
let slideInterval;

// Función para iniciar el cambio automático
function startAutoSlide() {
    // Limpia el intervalo anterior si existe
    if (slideInterval) {
        clearInterval(slideInterval);
    }
    // Crea un nuevo intervalo
    slideInterval = setInterval(() => {
        changeSlide(1);
    }, 5000); // Cambiar cada 5 segundos
}

// Iniciar el slider y el cambio automático
showSlides(slideIndex);
startAutoSlide();

// Controles de siguiente/anterior
function changeSlide(n) {
    showSlides(slideIndex += n);
    startAutoSlide(); // Reinicia el contador al cambiar manualmente
}

// Control de puntos
function currentSlide(n) {
    showSlides(slideIndex = n);
    startAutoSlide(); // Reinicia el contador al cambiar manualmente
}

function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("slide");
    let dots = document.getElementsByClassName("dot");
    
    // Manejo circular del índice
    if (n > slides.length) {slideIndex = 1}    
    if (n < 1) {slideIndex = slides.length}

    // Ocultar todas las diapositivas
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";  
    }

    // Desactivar todos los puntos
    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }

    // Mostrar la diapositiva actual y activar el punto correspondiente
    slides[slideIndex-1].style.display = "block";  
    dots[slideIndex-1].className += " active";
}

function validarFormulario() {
    let valido = true;
    
    // Validar nombre
    const nombre = document.querySelector('input[name="nombre"]');
    if (!nombre.checkValidity()) {
        document.getElementById('nombre-error').textContent = 'Nombre inválido (solo letras, 3-50 caracteres)';
        valido = false;
    } else {
        document.getElementById('nombre-error').textContent = '';
    }
    
    // Validar email
    const email = document.querySelector('input[name="email"]');
    if (!email.checkValidity()) {
        document.getElementById('email-error').textContent = 'Correo electrónico inválido';
        valido = false;
    } else {
        document.getElementById('email-error').textContent = '';
    }
    
    // Validar teléfono
    const telefono = document.querySelector('input[name="telefono"]');
    if (!telefono.checkValidity()) {
        document.getElementById('telefono-error').textContent = 'Teléfono inválido (10 dígitos)';
        valido = false;
    } else {
        document.getElementById('telefono-error').textContent = '';
    }
    
    return valido;
}