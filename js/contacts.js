document.addEventListener('DOMContentLoaded', function() {
    // Анимация кнопки "Наверх"
    const toTopButton = document.querySelector('.to-top');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            toTopButton.classList.add('visible');
        } else {
            toTopButton.classList.remove('visible');
        }
    });
    
    // Плавная прокрутка для всех якорных ссылок
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Валидация формы обратной связи
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phoneInput = document.getElementById('contact-phone');
            const phonePattern = /^\+7\s?[0-9]{3}\s?[0-9]{3}[0-9\-]{3,5}$/;
            
            if (!phonePattern.test(phoneInput.value)) {
                showAlert('Пожалуйста, введите корректный номер телефона', 'error');
                phoneInput.focus();
                return false;
            }
            
            // Здесь можно добавить AJAX-отправку формы
            showAlert('Спасибо! Ваша заявка принята. Мы свяжемся с вами в ближайшее время.', 'success');
            this.reset();
        });
    }
    
    // Функция показа уведомлений
    function showAlert(message, type) {
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        
        document.body.appendChild(alertBox);
        
        setTimeout(() => {
            alertBox.classList.add('fade-out');
            setTimeout(() => alertBox.remove(), 500);
        }, 3000);
    }
    
    // Анимация при наведении на карточки городов
    const cityCards = document.querySelectorAll('.city-card');
    cityCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
    
    // Инициализация tooltip для цен на выезд
    const paidCities = document.querySelectorAll('.city-card:not(.city-card--free)');
    paidCities.forEach(city => {
        city.addEventListener('click', function() {
            showAlert('Точная стоимость выезда рассчитывается индивидуально в зависимости от локации и программы', 'info');
        });
    });
    
    // Анимация пульсации для телефонов
    const pulseElements = document.querySelectorAll('.pulse');
    pulseElements.forEach(el => {
        el.addEventListener('animationiteration', function() {
            this.style.animation = 'pulse 2s infinite';
        });
    });
});