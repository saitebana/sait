<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db_connect.php';
require_once __DIR__.'/../includes/telegram_functions.php';

// Логирование входящего запроса
file_put_contents(__DIR__.'/../logs/telegram_webhook.log', date('[Y-m-d H:i:s]')." Request: ".file_get_contents('php://input').PHP_EOL, FILE_APPEND);

// Получаем входящее сообщение
$input = json_decode(file_get_contents('php://input'), true);

try {
    // Проверяем токен бота
    if (!isset($input['token']) || $input['token'] !== TELEGRAM_TOKEN) {
        throw new Exception('Invalid token', 403);
    }

    // Обрабатываем разные типы сообщений
    if (isset($input['message'])) {
        handleMessage($input['message']);
    } elseif (isset($input['callback_query'])) {
        handleCallback($input['callback_query']);
    } elseif (isset($input['my_chat_member'])) {
        handleChatMemberUpdate($input['my_chat_member']);
    }

    // Успешный ответ
    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    // Логирование ошибки
    error_log("Telegram webhook error: ".$e->getMessage());
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Обработка текстовых сообщений
 */
function handleMessage($message) {
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $messageId = $message['message_id'];
    
    // Логируем сообщение
    file_put_contents(__DIR__.'/../logs/telegram_messages.log', 
        date('[Y-m-d H:i:s]')." ChatID: $chatId | Text: $text".PHP_EOL, 
        FILE_APPEND);
    
    // Обработка команд
    switch ($text) {
        case '/start':
            $response = "🦆 Привет! Я бот для уведомлений о новых заказах.\n\n";
            $response .= "Я буду присылать сюда новые заявки с сайта prazdniksytkami.ru";
            sendTelegramMessage($chatId, $response);
            break;
            
        case '/orders':
            $orders = getRecentOrders(5);
            if (empty($orders)) {
                sendTelegramMessage($chatId, "⏳ Нет последних заказов");
            } else {
                foreach ($orders as $order) {
                    sendOrderToTelegram($chatId, $order);
                }
            }
            break;
            
        default:
            if (strpos($text, '/order_') === 0) {
                $orderId = str_replace('/order_', '', $text);
                $order = getOrderById($orderId);
                if ($order) {
                    sendOrderToTelegram($chatId, $order);
                } else {
                    sendTelegramMessage($chatId, "❌ Заказ #$orderId не найден");
                }
            } else {
                sendTelegramMessage($chatId, "🤖 Я понимаю только команды /start и /orders");
            }
    }
}

/**
 * Обработка callback-запросов (нажатие кнопок)
 */
function handleCallback($callback) {
    $chatId = $callback['message']['chat']['id'];
    $data = $callback['data'];
    $messageId = $callback['message']['message_id'];
    $callbackId = $callback['id'];
    
    // Обработка действий с заказом
    if (strpos($data, 'accept_') === 0) {
        $phone = str_replace('accept_', '', $data);
        
        // Помечаем заказ как обработанный в БД
        $database = Database::getInstance();
        $database->updateOrderStatusByPhone($phone, 'processed');
        
        // Отправляем подтверждение
        answerCallbackQuery($callbackId, "✅ Заказ принят в работу");
        
        // Редактируем оригинальное сообщение
        $originalText = $callback['message']['text'];
        $newText = $originalText."\n\n🏷 Статус: В работе";
        
        editMessageText($chatId, $messageId, $newText);
    }
}

/**
 * Получение последних заказов из БД
 */
function getRecentOrders($limit = 5) {
    try {
        $database = Database::getInstance();
        return $database->getOrders($limit);
    } catch (Exception $e) {
        error_log("Error getting orders: ".$e->getMessage());
        return [];
    }
}

/**
 * Получение заказа по ID
 */
function getOrderById($orderId) {
    try {
        $database = Database::getInstance();
        return $database->getOrder($orderId);
    } catch (Exception $e) {
        error_log("Error getting order: ".$e->getMessage());
        return null;
    }
}

/**
 * Отправка информации о заказе в Telegram
 */
function sendOrderToTelegram($chatId, $order) {
    $message = formatOrderMessage($order);
    $keyboard = createOrderKeyboard($order['phone']);
    
    sendTelegramMessage($chatId, $message, $keyboard);
}

/**
 * Форматирование сообщения о заказе
 */
function formatOrderMessage($order) {
    return sprintf(
        "🦆 *Новый заказ* #%s\n".
        "┌──────────────────\n".
        "│ *Имя:* %s\n".
        "│ *Телефон:* `%s`\n".
        "│ *Тип:* %s\n".
        "│ *Дата:* %s\n".
        "│ *Гостей:* %s\n".
        "└──────────────────\n".
        "📝 *Сообщение:*\n%s\n\n".
        "🌐 *Источник:* %s\n".
        "⏱ *Получено:* %s",
        $order['id'],
        $order['name'],
        $order['phone'],
        $order['event_type'] ?? 'Не указано',
        $order['event_date'] ?? 'Не указана',
        $order['guests'] ?? 'Не указано',
        $order['message'] ?? 'Нет сообщения',
        $order['source'] ?? 'Прямая заявка',
        $order['received_at'] ?? date('d.m.Y H:i:s')
    );
}

/**
 * Создание клавиатуры для заказа
 */
function createOrderKeyboard($phone) {
    $buttons = [
        [
            ['text' => '☎ Позвонить', 'url' => "tel:$phone"],
            ['text' => '✉ Написать', 'url' => "https://t.me/+$phone"]
        ],
        [
            ['text' => '✅ Принято в работу', 'callback_data' => "accept_$phone"]
        ]
    ];
    
    return json_encode(['inline_keyboard' => $buttons]);
}