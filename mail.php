<?php
// Включаем строгий режим ошибок
declare(strict_types=1);

// Устанавливаем заголовки
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Настройки времени выполнения
set_time_limit(10);

// Конфигурация (вынести в отдельный файл в продакшене)
define('TELEGRAM_TOKEN', '7052457745:AAHhW7BpeA6dQPiA4CsN5yRWpWwV3h0qHG0');
define('TELEGRAM_CHAT_ID', '-1002031108194');
define('ADMIN_EMAIL', 'orders@prazdniksytkami.ru');
define('MAIL_FROM', 'noreply@prazdniksytkami.ru');
define('LOG_FILE', __DIR__.'/mail_errors.log');

// Инициализация логов
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_FILE);

// Функция для логирования
function logError(string $message): void {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s]').' '.$message.PHP_EOL, FILE_APPEND);
}

// Функция для JSON ответа
function sendJsonResponse(bool $success, string $message = '', array $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ];
    
    // Очистка буфера вывода
    while (ob_get_level() > 0) ob_end_clean();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Основная обработка
try {
    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Метод не разрешен', [], 405);
    }

    // Проверка Content-Type (два варианта для совместимости)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJsonContent = str_starts_with($contentType, 'application/json') 
                   || str_starts_with($contentType, 'text/json');
    
    if (!$isJsonContent) {
        // Пробуем получить данные из form-data если JSON не пришел
        $formData = $_POST;
        if (!empty($formData)) {
            $data = $formData;
        } else {
            sendJsonResponse(false, 'Неверный Content-Type. Отправьте JSON или form-data', [], 415);
        }
    } else {
        // Получаем и декодируем JSON
        $jsonInput = file_get_contents('php://input');
        if ($jsonInput === false || $jsonInput === '') {
            sendJsonResponse(false, 'Пустое тело запроса', [], 400);
        }
        
        $data = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logError('JSON decode error: '.json_last_error_msg().' Input: '.substr($jsonInput, 0, 500));
            sendJsonResponse(false, 'Неверный JSON формат', [], 400);
        }
    }

    // Валидация данных
    $requiredFields = [
        'name' => 'Имя',
        'phone' => 'Телефон', 
        'event' => 'Тип мероприятия'
    ];
    
    $errors = [];
    foreach ($requiredFields as $field => $name) {
        if (empty($data[$field])) {
            $errors[] = "Поле '$name' обязательно для заполнения";
        }
    }
    
    if (!empty($errors)) {
        sendJsonResponse(false, implode("\n", $errors), ['fields' => array_keys($requiredFields)], 422);
    }

    // Очистка и нормализация данных
    $cleanData = [
        'name' => mb_substr(trim(preg_replace('/[^\p{L}\s\-]/u', '', $data['name'] ?? '')), 0, 100),
        'phone' => preg_replace('/[^0-9+]/', '', $data['phone'] ?? ''),
        'event' => mb_substr(trim(strip_tags($data['event'] ?? '')), 0, 50),
        'date' => !empty($data['date']) ? date('Y-m-d', strtotime($data['date'])) : null,
        'guests' => isset($data['guests']) ? (int)$data['guests'] : null,
        'message' => !empty($data['message']) ? mb_substr(trim(strip_tags($data['message'])), 0, 1000) : null
    ];

    // Формирование сообщения
    $messageText = "🎉 Новая заявка!\n\n"
        . "👤 Имя: {$cleanData['name']}\n"
        . "📞 Телефон: {$cleanData['phone']}\n"
        . "🎂 Мероприятие: {$cleanData['event']}\n";
    
    if ($cleanData['date']) $messageText .= "📅 Дата: {$cleanData['date']}\n";
    if ($cleanData['guests']) $messageText .= "👥 Гостей: {$cleanData['guests']}\n";
    if ($cleanData['message']) $messageText .= "✉️ Сообщение: {$cleanData['message']}\n";

    // Отправка в Telegram
    $telegramSent = false;
    try {
        $url = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/sendMessage";
        $params = [
            'chat_id' => TELEGRAM_CHAT_ID,
            'text' => $messageText,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode === 200) {
            $respData = json_decode($response, true);
            $telegramSent = $respData['ok'] ?? false;
        }
        
        if (!$telegramSent) {
            logError("Telegram send failed. Code: $httpCode, Response: ".substr($response, 0, 500));
        }
        
        curl_close($ch);
    } catch (Throwable $e) {
        logError("Telegram error: ".$e->getMessage());
    }

    // Отправка email
    $emailSent = false;
    try {
        $subject = "Новая заявка: {$cleanData['event']}";
        $headers = [
            'From' => MAIL_FROM,
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        
        $headersStr = '';
        foreach ($headers as $key => $value) {
            $headersStr .= "$key: $value\r\n";
        }
        
        $emailSent = mail(
            ADMIN_EMAIL,
            '=?UTF-8?B?'.base64_encode($subject).'?=',
            $messageText,
            $headersStr
        );
        
        if (!$emailSent) {
            logError("Email sending failed");
        }
    } catch (Throwable $e) {
        logError("Email error: ".$e->getMessage());
    }

    // Формирование ответа
    if ($telegramSent || $emailSent) {
        sendJsonResponse(true, 'Заявка успешно отправлена', [
            'channels' => [
                'telegram' => $telegramSent,
                'email' => $emailSent
            ],
            'order_id' => 'ORD-'.date('Ymd-His').'-'.bin2hex(random_bytes(3))
        ]);
    } else {
        sendJsonResponse(false, 'Не удалось отправить заявку', [], 500);
    }

} catch (Throwable $e) {
    logError("System error: ".$e->getMessage()."\n".$e->getTraceAsString());
    sendJsonResponse(false, 'Внутренняя ошибка сервера', [], 500);
}