<?php
// Включение обработки ошибок (для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Старт сессии для CSRF-токена
session_start();

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Создаем папку для логов если ее нет
if (!file_exists(__DIR__.'/logs')) {
    mkdir(__DIR__.'/logs', 0755, true);
}

// Обработка AJAX запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    $response = [
        'success' => false,
        'error' => null,
        'order_id' => null
    ];
    
    try {
        // Проверка CSRF-токена
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            throw new Exception('Неверный CSRF-токен');
        }
        
        // Валидация
        $errors = [];
        if (empty($_POST['name'])) $errors[] = 'Укажите имя';
        if (empty($_POST['phone'])) $errors[] = 'Укажите телефон';
        if (empty($_POST['event'])) $errors[] = 'Выберите тип праздника';
        
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
        
        // Обработка данных
        $orderData = [
            'id' => 'ORD-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)),
            'name' => htmlspecialchars(trim($_POST['name'])),
            'phone' => preg_replace('/[^0-9+]/', '', $_POST['phone']),
            'event' => htmlspecialchars(trim($_POST['event'])),
            'date' => !empty($_POST['date']) ? date('Y-m-d', strtotime($_POST['date'])) : null,
            'guests' => isset($_POST['guests']) ? (int)$_POST['guests'] : null,
            'message' => !empty($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : null,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'received_at' => date('Y-m-d H:i:s')
        ];
        
        // Логирование заявки
        file_put_contents(
            __DIR__.'/logs/orders.log', 
            json_encode($orderData, JSON_UNESCAPED_UNICODE) . PHP_EOL, 
            FILE_APPEND
        );
        
        // Здесь можно добавить отправку в Telegram и на почту
        
        $response['success'] = true;
        $response['order_id'] = $orderData['id'];
        
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        http_response_code(400);
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Праздник сУтками! - Организация детских праздников</title>
    <meta name="description" content="Профессиональная организация детских праздников">
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Comic+Neue:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="canonical" href="https://prazdniksytkami.ru/" />
</head>
<body>
    <!-- Шапка -->
    <header class="header">
        <div class="container">
            <div class="header__inner">
                <a href="index.php" class="logo">
                    <img src="images/logo.png" alt="Логотип Праздник сУтками" class="logo__img">
                    <span class="logo__text">Праздник с<span>Утками</span>!</span>
                </a>
                
                <nav class="nav">
                    <a href="index.php" class="nav__link active">Главная</a>
                    <a href="costumes.php" class="nav__link">Персонажи</a>
                    <a href="programs.php" class="nav__link">Программы</a>
                    <a href="gallery.php" class="nav__link">Галерея</a>
                    <a href="reviews.php" class="nav__link">Отзывы</a>
                    <a href="contacts.php" class="nav__link">Контакты</a>
                </nav>
                
                <div class="header__contacts">
                    <a href="tel:+79786691090" class="header__phone">+7 (978) 669-10-90</a>
                    <div class="social-links">
                        <a href="https://vk.com/prazdnik_sutkami" class="social-link vk" target="_blank" aria-label="ВКонтакте"><i class="fab fa-vk"></i></a>
                        <a href="https://instagram.com/prazdnik_sutkami" class="social-link insta" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://t.me/prazdnik_sutkami" class="social-link tg" target="_blank" aria-label="Telegram"><i class="fab fa-telegram"></i></a>
                        <a href="https://wa.me/79786691090" class="social-link wa" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <button class="burger" aria-label="Меню">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Основное содержимое -->
    <main class="main">
        <!-- Герой -->
        <section class="hero">
            <div class="container">
                <div class="hero__content">
                    <h1 class="hero__title">Незабываемые детские праздники</h1>
                    <p class="hero__text">Более 30 ярких персонажей и профессиональных аниматоров для вашего праздника!</p>
                    <div class="hero__buttons">
                        <a href="costumes.php" class="btn btn--primary pulse btn-costume">Выбрать костюм</a>
                        <button class="btn btn--outline btn-order" id="openModalBtn">Заказать праздник</button>
                    </div>
                </div>
                <div class="hero__image">
                    <img src="images/hero-duck.png" alt="Веселая утка" class="hero__duck floating" width="300" height="300">
                </div>
            </div>
        </section>

        <!-- Преимущества -->
        <section class="section benefits">
            <div class="container">
                <h2 class="section__title">Почему выбирают нас</h2>
                
                <div class="benefits__grid">
                    <div class="benefit-card">
                        <div class="benefit-card__icon">🌟</div>
                        <h3 class="benefit-card__title">Профессиональные аниматоры</h3>
                        <p class="benefit-card__text">Опытные ведущие с педагогическим образованием</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-card__icon">🦆</div>
                        <h3 class="benefit-card__title">Праздник под ключ</h3>
                        <p class="benefit-card__text">Полностью берем организацию праздника на себя</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-card__icon">🎉</div>
                        <h3 class="benefit-card__title">Полный сценарий</h3>
                        <p class="benefit-card__text">Продуманная программа с играми и конкурсами</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Популярные костюмы -->
        <section class="section costumes">
            <div class="container">
                <h2 class="section__title">Популярные персонажи</h2>
                <p class="section__subtitle">Самые востребованные образы этого сезона</p>
                
                <div class="costumes__grid">
                    <div class="costume-card">
                        <div class="costume-card__image">
                            <img src="images/costumes/superhero.jpg" alt="Супергерой" loading="lazy">
                            <div class="costume-card__badge">Выбор многих лет</div>
                        </div>
                        <div class="costume-card__content">
                            <h3 class="costume-card__title">Супергерои</h3>
                            <p class="costume-card__text">Человек-паук, Леди Баг</p>
                            <div class="costume-card__footer">
                                <button class="btn btn--primary btn--small btn-costume">Заказать</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="costume-card">
                        <div class="costume-card__image">
                            <img src="images/costumes/labubu.jpg" alt="Лабубу" loading="lazy">
                            <div class="costume-card__badge">New</div>
                        </div>
                        <div class="costume-card__content">
                            <h3 class="costume-card__title">Лабубу</h3>
                            <p class="costume-card__text">Персонаж из трендов</p>
                            <div class="costume-card__footer">
                                <button class="btn btn--primary btn--small btn-costume">Заказать</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="costume-card">
                        <div class="costume-card__image">
                            <img src="images/costumes/cats.jpg" alt="Коржик и карамелька" loading="lazy">
                            <div class="costume-card__badge">Для самых маленьких</div>
                        </div>
                        <div class="costume-card__content">
                            <h3 class="costume-card__title">Три кота</h3>
                            <p class="costume-card__text">Коржик и Карамелька</p>
                            <div class="costume-card__footer">
                                <button class="btn btn--primary btn--small btn-costume">Заказать</button>
                            </div>
                        </div>
                    </div>
                   
                    <div class="costume-card">
                        <div class="costume-card__image">
                            <img src="images/costumes/bigM.jpg" alt="Большой белый медведь" loading="lazy">
                            <div class="costume-card__badge">Экспресс поздравление</div>
                        </div>
                        <div class="costume-card__content">
                            <h3 class="costume-card__title">Большой белый медведь</h3>
                            <p class="costume-card__text">Веселое дополнение к Вашему празднику</p>
                            <div class="costume-card__footer">
                                <button class="btn btn--primary btn--small btn-costume">Заказать</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="section__footer">
                    <a href="costumes.php" class="btn btn--primary">Все костюмы</a>
                </div>
            </div>
        </section>

        <!-- Наши шоу-программы -->
        <section class="section characters bg-accent">
            <div class="container">
                <h2 class="section__title">Наши шоу-программы</h2>
                <p class="section__subtitle">Динамичные и увлекательные шоу-программы</p>
                
                <div class="characters__slider">
                    <div class="character-card">
                        <div class="character-card__image">
                            <img src="images/characters/orb.jpg" alt="Шоу Гиганских мыльных пузырей" loading="lazy">
                        </div>
                        <div class="character-card__content">
                            <h3 class="character-card__title">Шоу Мыльных Пузырей 🌈✨</h3>
                            <p class="character-card__text">Гигантские пузыри, в которые можно залезть! Настоящая магия и море восторга для детей и взрослых.</p>
                            <button class="btn btn--primary btn--small btn-program">Заказать</button>
                        </div>
                    </div>
                    <div class="character-card">
                        <div class="character-card__image">
                            <img src="images/characters/wow.jpg" alt="Научное Шоу" loading="lazy">
                        </div>
                        <div class="character-card__content">
                            <h3 class="character-card__title">Научное Шоу с Азотом 🧪❄️</h3>
                            <p class="character-card__text">Ледяные взрывы и дымовые эксперименты! Безопасно, но очень впечатляюще.</p>
                            <button class="btn btn--primary btn--small btn-program">Заказать</button>
                        </div>
                    </div>
                    <div class="character-card">
                        <div class="character-card__image">
                            <img src="images/characters/serebro.jpg" alt="Серебро Шоу" loading="lazy">
                        </div>
                        <div class="character-card__content">
                            <h3 class="character-card__title">Серебро Шоу ✨</h3>
                            <p class="character-card__text">Искрящиеся фонтаны серебряного дождя! Танцуем, ловим блеск и делаем волшебные фото.️</p>
                            <button class="btn btn--primary btn--small btn-program">Заказать</button>
                        </div>
                    </div>
                    
                    <div class="character-card">
                        <div class="character-card__image">
                            <img src="images/characters/captain.jpg" alt="Мастер классы" loading="lazy">
                        </div>
                        <div class="character-card__content">
                            <h3 class="character-card__title">Мастер-Классы 🎨</h3>
                            <p class="character-card__text">Творческие и познавательные занятия, где каждый создаст свой шедевр.</p>
                            <button class="btn btn--primary btn--small btn-program">Заказать</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Как мы работаем -->
        <section class="section workflow">
            <div class="container">
                <h2 class="section__title">Как мы работаем</h2>
                
                <div class="workflow__steps">
                    <div class="workflow__step">
                        <div class="workflow__number">1</div>
                        <h3 class="workflow__title">Заявка</h3>
                        <p class="workflow__text">Оставляете заявку на сайте или по телефону</p>
                    </div>
                    
                    <div class="workflow__step">
                        <div class="workflow__number">2</div>
                        <h3 class="workflow__title">Подбор</h3>
                        <p class="workflow__text">Мы помогаем выбрать персонажа и желаемую программу</p>
                    </div>
                    
                    <div class="workflow__step">
                        <div class="workflow__number">3</div>
                        <h3 class="workflow__title">Подтверждение</h3>
                        <p class="workflow__text">Уточняем все детали мероприятия</p>
                    </div>
                    
                    <div class="workflow__step">
                        <div class="workflow__number">4</div>
                        <h3 class="workflow__title">Праздник</h3>
                        <p class="workflow__text">Проводим незабываемое мероприятие</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Подвал -->
    <footer class="footer">
        <div class="container">
            <div class="footer__inner">
                <div class="footer__col">
                    <a href="index.php" class="footer__logo">
                        <img src="images/logo.png" alt="Праздник сУтками">
                        <span>Праздник сУтками!</span>
                    </a>
                    <p class="footer__text">Организация незабываемых праздников с 2024 года</p>
                </div>
                
                <div class="footer__col">
                    <h3 class="footer__title">Меню</h3>
                    <nav class="footer__nav">
                        <a href="index.php" class="footer__link">Главная</a>
                        <a href="costumes.php" class="footer__link">Персонажи</a>
                        <a href="programs.php" class="footer__link">Программы</a>
                        <a href="gallery.php" class="footer__link">Галерея</a>
                        <a href="reviews.php" class="footer__link">Отзывы</a>
                    </nav>
                </div>
                
                <div class="footer__col">
                    <h3 class="footer__title">Услуги</h3>
                    <nav class="footer__nav">
                        <a href="birthdays.php" class="footer__link">Дни рождения</a>
                        <a href="school-events.php" class="footer__link">Школьные праздники</a>
                        <a href="new-year.php" class="footer__link">Новый Год</a>
                        <a href="corporate.php" class="footer__link">Корпоративы</a>
                    </nav>
                </div>
                
                <div class="footer__col">
                    <h3 class="footer__title">Контакты</h3>
                    <div class="footer__phones">
                        <a href="tel:+79786691090" class="footer__phone">+7 (978) 669-10-90</a>
                        <a href="tel:+79788388070" class="footer__phone">+7 (978) 838-80-70</a>
                    </div>
                    
                    <h3 class="footer__title" style="margin-top: 20px;">Мы в соцсетях</h3>
                    <div class="social-links">
                        <a href="https://vk.com/sytkamu" class="social-link vk" target="_blank" aria-label="ВКонтакте"><i class="fab fa-vk"></i></a>
                        <a href="https://www.instagram.com/sytkamu/profilecard/?igsh=MjNhdXppNnJiempx" class="social-link insta" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/+79786691090" class="social-link wa" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://t.me/sytkami" class="social-link tg" target="_blank" aria-label="Telegram"><i class="fab fa-telegram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer__bottom">
                <p class="footer__copyright">© <?= date('Y') ?> Праздник сУтками! Все права защищены.</p>
                <a href="privacy.php" class="footer__link">Политика конфиденциальности</a>
            </div>
        </div>
    </footer>

    <!-- Кнопка "Наверх" -->
    <a href="#" class="to-top" aria-label="Наверх"><i class="fas fa-arrow-up"></i></a>

    <!-- Модальное окно -->
    <div class="modal" id="orderModal">
        <div class="modal__content">
            <button class="modal__close" id="closeModalBtn">&times;</button>
            <h2 class="modal__title">Заказать праздник</h2>
            <p class="modal__text">Заполните форму ниже, и мы свяжемся с вами для уточнения деталей!</p>
            
            <form class="form" id="modalForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form__group">
                    <label class="form__label" for="modal-name">Ваше имя*</label>
                    <input class="form__input" type="text" id="modal-name" name="name" required placeholder="Как к вам обращаться?">
                </div>
                
                <div class="form__group">
                    <label class="form__label" for="modal-phone">Телефон*</label>
                    <input class="form__input" type="tel" id="modal-phone" name="phone" required placeholder="+7 (___) ___-__-__">
                </div>
                
                <div class="form__group">
                    <label class="form__label" for="modal-event">Тип праздника*</label>
                    <select class="form__select" id="modal-event" name="event" required>
                        <option value="" disabled selected>Выберите тип праздника</option>
                        <option value="birthday">День рождения</option>
                        <option value="school">Школьный праздник</option>
                        <option value="newyear">Новый год</option>
                        <option value="graduation">Выпускной</option>
                        <option value="other">Другое</option>
                    </select>
                </div>
                
                <div class="form__group">
                    <label class="form__label" for="modal-date">Дата праздника</label>
                    <input class="form__input" type="date" id="modal-date" name="date">
                </div>
                
                <div class="form__group">
                    <label class="form__label" for="modal-guests">Количество гостей</label>
                    <input class="form__input" type="number" id="modal-guests" name="guests" min="1" max="100" placeholder="Пример: 10">
                </div>
                
                <div class="form__group">
                    <label class="form__label" for="modal-message">Дополнительная информация</label>
                    <textarea class="form__textarea" id="modal-message" name="message" placeholder="Место проведения, особые пожелания и т.д."></textarea>
                </div>
                
                <div class="form__group">
                    <button type="submit" class="btn btn--primary btn--block">Отправить заявку</button>
                </div>
                
                <p class="form__note">* Обязательные поля для заполнения</p>
                <p class="form__note">Нажимая кнопку, вы соглашаетесь с <a href="privacy.php" class="link">политикой конфиденциальности</a></p>
            </form>
        </div>
    </div>

    <!-- Скрипты -->
    <script src="js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Управление модальным окном
        const modal = document.getElementById('orderModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        
        function openModal() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        openModalBtn.addEventListener('click', openModal);
        closeModalBtn.addEventListener('click', closeModal);
        
        window.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
        
        // Обработка формы
        const form = document.getElementById('modalForm');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.error || 'Ошибка сервера');
                    }
                    
                    alert('Спасибо! Ваша заявка #' + data.order_id + ' принята.');
                    form.reset();
                    closeModal();
                    
                } catch (error) {
                    console.error('Ошибка:', error);
                    alert('Ошибка: ' + error.message);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }
        
        // Обработка кнопок "Заказать" в карточках
        document.querySelectorAll('.btn-costume, .btn-program').forEach(btn => {
            btn.addEventListener('click', function() {
                openModal();
            });
        });
    });
    </script>
</body>
</html>