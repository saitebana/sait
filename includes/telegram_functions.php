<?php
require_once __DIR__.'/config.php';

/**
 * Отправка сообщения в Telegram
 */
function sendTelegramMessage($chatId, $text, $replyMarkup = null) {
    $url = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    
    if ($replyMarkup) {
        $data['reply_markup'] = $replyMarkup;
    }
    
    return sendTelegramRequest($url, $data);
}

/**
 * Ответ на callback-запрос (нажатие кнопки)
 */
function answerCallbackQuery($callbackId, $text = '', $showAlert = false) {
    $url = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/answerCallbackQuery";
    $data = [
        'callback_query_id' => $callbackId,
        'text' => $text,
        'show_alert' => $showAlert
    ];
    
    return sendTelegramRequest($url, $data);
}

/**
 * Редактирование существующего сообщения
 */
function editMessageText($chatId, $messageId, $newText) {
    $url = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/editMessageText";
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $newText,
        'parse_mode' => 'Markdown'
    ];
    
    return sendTelegramRequest($url, $data);
}

/**
 * Базовый запрос к Telegram API
 */
function sendTelegramRequest($url, $data) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data"]
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Telegram API error: ".$error);
        return ['ok' => false, 'error' => $error];
    }
    
    return json_decode($response, true) ?: ['ok' => false];
}

/**
 * Форматирование сообщения о заказе для Telegram
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
 * Создание клавиатуры для сообщения
 */
function createTelegramKeyboard($phone) {
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