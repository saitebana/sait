<?php
// Настройки почты
define('ADMIN_EMAIL', 'orders@prazdniksytkami.ru');
define('SITE_NAME', 'Праздник сУтками!');
define('MAIL_FROM', 'noreply@prazdniksytkami.ru');

// Настройки Telegram
define('TELEGRAM_TOKEN', '7052457745:AAHhW7BpeA6dQPiA4CsN5yRWpWwV3h0qHG0');
define('TELEGRAM_CHAT_ID', '1002031108194');
define('TELEGRAM_WEBHOOK_URL', 'https://prazdniksytkami.ru/telegram_webhook.php');

// Настройки безопасности
define('ALLOWED_DOMAINS', ['prazdniksytkami.ru', 'www.prazdniksytkami.ru']);

// Пути к логам
define('LOG_DIR', __DIR__.'/../logs/');
define('ORDER_LOG', LOG_DIR.'orders.log');
define('ERROR_LOG', LOG_DIR.'errors.log');

// Настройки reCAPTCHA
define('RECAPTCHA_SECRET', '6LdJ...');
define('RECAPTCHA_SITEKEY', '6LdJ...');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../logs/php-errors.log');