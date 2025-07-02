<?php
/**
 * Система защиты от спама и ботов
 */

// Время блокировки при превышении лимита (в секундах)
define('ANTISPAM_BLOCK_TIME', 300); // 5 минут

// Максимальное количество запросов с одного IP
define('ANTISPAM_MAX_REQUESTS', 5);

// Временной интервал для проверки (в секундах)
define('ANTISPAM_TIME_WINDOW', 60); // 1 минута

// Список запрещенных email-доменов
define('ANTISPAM_BAD_DOMAINS', ['mail.ru', 'yandex.ru', 'rambler.ru']); // Пример

class AntiSpam {
    private static $instance = null;
    private $ip;
    
    private function __construct() {
        $this->ip = $this->getClientIp();
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new AntiSpam();
        }
        return self::$instance;
    }
    
    /**
     * Основная функция проверки
     */
    public function validateRequest() {
        // Проверка IP в черном списке
        if ($this->isIpBlocked()) {
            return false;
        }
        
        // Проверка частоты запросов
        if ($this->checkRequestRate()) {
            $this->blockIp();
            return false;
        }
        
        // Проверка скрытых полей (honeypot)
        if (!empty($_POST['website']) || !empty($_POST['url'])) {
            return false;
        }
        
        // Проверка времени заполнения формы
        if (isset($_POST['form_time']) && (time() - (int)$_POST['form_time'] < 3) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Проверка IP в черном списке
     */
    private function isIpBlocked() {
        $blockedIps = file(__DIR__.'/../logs/blocked_ips.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array($this->ip, $blockedIps ?: []);
    }
    
    /**
     * Проверка частоты запросов
     */
    private function checkRequestRate() {
        $logFile = __DIR__.'/../logs/requests.log';
        $now = time();
        $windowStart = $now - ANTISPAM_TIME_WINDOW;
        
        // Читаем логи запросов
        $requests = [];
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($time, $ip) = explode('|', $line);
                if ($time >= $windowStart) {
                    $requests[] = $ip;
                }
            }
        }
        
        // Фильтруем запросы текущего IP
        $ipRequests = array_filter($requests, function($item) {
            return $item === $this->ip;
        });
        
        // Записываем текущий запрос
        file_put_contents($logFile, "$now|$this->ip".PHP_EOL, FILE_APPEND);
        
        return count($ipRequests) >= ANTISPAM_MAX_REQUESTS;
    }
    
    /**
     * Блокировка IP
     */
    private function blockIp() {
        $blockFile = __DIR__.'/../logs/blocked_ips.log';
        file_put_contents($blockFile, $this->ip.PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Проверка email на спам-домены
     */
    public function validateEmail($email) {
        if (empty($email)) return true;
        
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return !in_array($domain, ANTISPAM_BAD_DOMAINS);
    }
    
    /**
     * Получение реального IP клиента
     */
    private function getClientIp() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        // Для случая, когда IP содержит несколько адресов
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Генерация скрытого поля для honeypot
     */
    public function generateHoneypotField() {
        return '<div style="position: absolute; left: -9999px;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
            <input type="hidden" name="form_time" value="'.time().'">
        </div>';
    }
}

// Функция для удобного вызова из других файлов
function validateRequest() {
    $antispam = AntiSpam::getInstance();
    return $antispam->validateRequest();
}

// Функция для проверки email
function validateEmail($email) {
    $antispam = AntiSpam::getInstance();
    return $antispam->validateEmail($email);
}

// Функция для получения honeypot-поля
function getHoneypotField() {
    $antispam = AntiSpam::getInstance();
    return $antispam->generateHoneypotField();
}