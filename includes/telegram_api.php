<?php
function sendTelegramNotification(array $orderData): array {
    $message = formatTelegramMessage($orderData);
    $keyboard = createTelegramKeyboard($orderData['phone']);

    return sendTelegramRequest('sendMessage', [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'reply_markup' => $keyboard,
        'disable_web_page_preview' => true
    ]);
}

function formatTelegramMessage(array $data): string {
    return sprintf(
        "🦆 *Новая заявка на праздник!* #%s\n\n".
        "👤 *Имя:* %s\n".
        "📞 *Телефон:* `%s`\n".
        "📅 *Дата:* %s\n".
        "🎉 *Услуга:* %s\n\n".
        "📝 *Сообщение:*\n%s\n\n".
        "🌐 *Источник:* %s\n".
        "⏱ *Получено:* %s",
        $data['id'],
        $data['name'],
        $data['phone'],
        $data['date'] ?? 'Не указана',
        $data['service'] ?? 'Не указано',
        $data['message'] ?? 'Нет сообщения',
        $data['source'] ?? 'Прямая заявка',
        date('d.m.Y H:i:s')
    );
}

function createTelegramKeyboard(string $phone): string {
    $buttons = [
        [
            ['text' => '☎ Позвонить', 'url' => "tel:$phone"],
            ['text' => '✉ Написать', 'url' => "https://t.me/+$phone"]
        ],
        [
            ['text' => '✅ Принято в работу', 'callback_data' => 'accept_'.$phone]
        ]
    ];
    
    return json_encode(['inline_keyboard' => $buttons]);
}

function sendTelegramRequest(string $method, array $params): array {
    $url = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/$method";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data"]
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Telegram API error: ".$error);
    }
    
    return json_decode($response, true) ?? [];
}