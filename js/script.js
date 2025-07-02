document.addEventListener('DOMContentLoaded', function() {
  // ========== Глобальные переменные ==========
  const body = document.body;
  const header = document.querySelector('.header');
  const burger = document.querySelector('.burger');
  const nav = document.querySelector('.nav');
  const toTopBtn = document.querySelector('.to-top');
  const modalTriggers = document.querySelectorAll('[data-modal], .btn-program, .btn-order');
  const modals = document.querySelectorAll('.modal');
  const forms = document.querySelectorAll('.form');
  const phoneInputs = document.querySelectorAll('input[type="tel"]');

  // ========== Бургер-меню ==========
  if (burger) {
    burger.addEventListener('click', () => {
      burger.classList.toggle('active');
      if (nav) nav.classList.toggle('active');
      body.classList.toggle('lock');
    });
  }

  // Закрытие меню при клике на ссылку
  if (nav) {
    document.querySelectorAll('.nav__link').forEach(link => {
      link.addEventListener('click', () => {
        if (burger) burger.classList.remove('active');
        nav.classList.remove('active');
        body.classList.remove('lock');
      });
    });
  }

  // ========== Обработка скролла ==========
  window.addEventListener('scroll', () => {
    const scrollPos = window.scrollY;
    
    if (header) {
      if (scrollPos > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
      
      if (scrollPos > 100) {
        header.classList.add('fixed');
      } else {
        header.classList.remove('fixed');
      }
    }
    
    if (toTopBtn) {
      if (scrollPos > 500) {
        toTopBtn.classList.add('visible');
      } else {
        toTopBtn.classList.remove('visible');
      }
    }
  });

  // ========== Кнопка "Наверх" ==========
  if (toTopBtn) {
    toTopBtn.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
      toTopBtn.blur();
    });
  }

  // ========== Плавная прокрутка ==========
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        const headerHeight = header ? header.offsetHeight : 0;
        window.scrollTo({
          top: targetElement.offsetTop - headerHeight,
          behavior: 'smooth'
        });
      }
    });
  });

  // ========== Модальные окна ==========
  // Открытие модального окна
  modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      let modalId;
      
      if (trigger.hasAttribute('data-modal')) {
        modalId = trigger.getAttribute('data-modal');
      } else if (trigger.classList.contains('btn-program')) {
        modalId = 'programModal';
      } else if (trigger.classList.contains('btn-order')) {
        modalId = 'orderModal';
      }
      
      const modal = document.getElementById(modalId);
      
      if (modal) {
        openModal(modal);
      }
    });
  });

  // Закрытие модального окна
  modals.forEach(modal => {
    const closeBtn = modal.querySelector('.modal__close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => closeModal(modal));
    }
    
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal);
      }
    });
  });

  // Функции для работы с модальными окнами
  function openModal(modal) {
    if (!modal) return;
    
    body.classList.add('lock');
    modal.classList.add('active');
    
    setTimeout(() => {
      const modalContent = modal.querySelector('.modal__content');
      if (modalContent) modalContent.classList.add('show');
    }, 10);
  }

  function closeModal(modal) {
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal__content');
    if (modalContent) modalContent.classList.remove('show');
    
    setTimeout(() => {
      modal.classList.remove('active');
      body.classList.remove('lock');
    }, 300);
  }

  // Закрытие по ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      modals.forEach(modal => {
        if (modal.classList.contains('active')) {
          closeModal(modal);
        }
      });
    }
  });

  // ========== Маска для телефона ==========
  if (phoneInputs.length > 0 && typeof IMask !== 'undefined') {
    phoneInputs.forEach(input => {
      IMask(input, {
        mask: '+{7} (000) 000-00-00',
        lazy: false,
        placeholderChar: '_'
      });
    });
  } else if (phoneInputs.length > 0) {
    console.warn('IMask не подключен - маски для телефона не будут работать');
  }

  // ========== Валидация и отправка форм ==========
  forms.forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = new FormData(form);
      const errors = validateForm(form);
      
      if (errors === 0) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Отправка...';
        }
        
        try {
          console.log('Форма отправлена:', Object.fromEntries(formData));
          await new Promise(resolve => setTimeout(resolve, 1500));
          showSuccessModal(form);
        } catch (error) {
          console.error('Ошибка:', error);
          showErrorModal();
        } finally {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Отправить заявку';
          }
        }
      }
    });
  });

  function validateForm(form) {
    let errors = 0;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
      field.classList.remove('error');
      
      if (!field.value.trim()) {
        field.classList.add('error');
        errors++;
      }
      
      if (field.type === 'tel' && field.value.includes('_')) {
        field.classList.add('error');
        errors++;
      }
    });
    
    return errors;
  }

  function showSuccessModal(form) {
    const modalId = form.closest('.modal')?.id || null;
    
    if (modalId) {
      closeModal(document.getElementById(modalId));
    }
    
    alert('Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.');
    form.reset();
  }

  function showErrorModal() {
    alert('Произошла ошибка при отправке. Пожалуйста, попробуйте позже.');
  }

  // ========== Анимации при скролле ==========
  const animateOnScroll = () => {
    const cards = document.querySelectorAll('.costume-card, .character-card, .benefit-card');
    
    cards.forEach(card => {
      const cardPosition = card.getBoundingClientRect().top;
      const screenPosition = window.innerHeight / 1.3;
      
      if (cardPosition < screenPosition) {
        card.classList.add('animated');
      }
    });
  };
  
  window.addEventListener('scroll', animateOnScroll);
  animateOnScroll();
});