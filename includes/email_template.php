<?php
/**
 * Шаблон письма для уведомления о новом заказе
 * Используется в mail.php для отправки администратору
 */

// Проверка, что файл вызывается из mail.php
if (!isset($orderData) {
    die('Прямой доступ запрещен');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый заказ #<?= $orderData['id'] ?> - Праздник сУтками!</title>
    <style>
        body {
            font-family: 'Comic Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 100%);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        .header:after {
            content: "";
            background: url('https://prazdniksytkami.ru/images/ducks-pattern.png');
            opacity: 0.1;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            position: relative;
            z-index: 1;
            font-family: 'Chewy', cursive;
        }
        .header p {
            margin: 10px 0 0;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        .content {
            padding: 25px;
        }
        .order-details {
            background: #fffde7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            min-width: 150px;
            color: #ff6b6b;
        }
        .detail-value {
            flex: 1;
        }
        .urgent {
            color: #d32f2f;
            font-weight: bold;
        }
        .message-block {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .footer {
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #777;
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦆 Новый заказ #<?= $orderData['id'] ?></h1>
            <p>Тип: <?= $orderData['event_type'] ?? 'Не указан' ?></p>
        </div>
        
        <div class="content">
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Имя клиента:</span>
                    <span class="detail-value"><?= $orderData['name'] ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Телефон:</span>
                    <span class="detail-value">
                        <a href="tel:<?= $orderData['phone'] ?>"><?= $orderData['phone'] ?></a>
                        <?php if (!empty($orderData['email'])): ?>
                            <br>Email: <a href="mailto:<?= $orderData['email'] ?>"><?= $orderData['email'] ?></a>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Дата праздника:</span>
                    <span class="detail-value <?= (isset($orderData['event_date']) && strtotime($orderData['event_date']) < strtotime('+3 days')) ? 'urgent' : '' ?>">
                        <?= $orderData['event_date'] ?? 'Не указана' ?>
                        <?php if (isset($orderData['event_date']) && strtotime($orderData['event_date']) < strtotime('+3 days')): ?>
                            <span class="badge">СРОЧНО</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Количество гостей:</span>
                    <span class="detail-value"><?= $orderData['guests'] ?? 'Не указано' ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Источник:</span>
                    <span class="detail-value"><?= $orderData['source'] ?? 'Прямая заявка' ?></span>
                </div>
            </div>
            
            <?php if (!empty($orderData['message'])): ?>
                <div class="message-block">
                    <h3 style="margin-top: 0; color: #ff6b6b;">✏ Дополнительная информация:</h3>
                    <p style="white-space: pre-line;"><?= $orderData['message'] ?></p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px; font-size: 14px; color: #666;">
                <p><strong>IP-адрес:</strong> <?= $orderData['ip'] ?? 'Неизвестен' ?></p>
                <p><strong>Дата заявки:</strong> <?= $orderData['received_at'] ?? date('Y-m-d H:i:s') ?></p>
            </div>
        </div>
        
        <div class="footer">
            <p>Это письмо отправлено автоматически. Пожалуйста, ответьте клиенту в течение 24 часов.</p>
            <p>© <?= date('Y') ?> <a href="https://prazdniksytkami.ru" style="color: #ff6b6b; text-decoration: none;">Праздник сУтками!</a></p>
        </div>
    </div>
</body>
</html>