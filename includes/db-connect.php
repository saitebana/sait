 <?php
/**
 * Подключение к базе данных с улучшенной обработкой ошибок
 */

// Настройки подключения (замените на актуальные для вашего хостинга)
define('DB_HOST', 'localhost');         // Сервер БД (возможно pma.yourhosting.ru)
define('DB_NAME', 'danik1d5_main');     // Имя базы данных
define('DB_USER', 'danik1d5_main');     // Пользователь БД
define('DB_PASS', '!SuUvP7t6O5H');      // Пароль (убедитесь в его корректности)
define('DB_CHARSET', 'utf8mb4');        // Кодировка
define('DB_PORT', '3306');              // Порт MySQL

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Дополнительные настройки соединения
            $this->connection->exec("SET time_zone = '+03:00'");
            
        } catch (PDOException $e) {
            // Детальное логирование ошибки
            $errorMsg = "Database connection failed:\n"
                      . "Error: ".$e->getMessage()."\n"
                      . "DSN: ".$dsn."\n"
                      . "Time: ".date('Y-m-d H:i:s')."\n";
            
            error_log($errorMsg, 3, __DIR__.'/../logs/db_errors.log');
            
            // Пользовательское сообщение без деталей ошибки
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'Системная ошибка. Пожалуйста, попробуйте позже.'
            ]));
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Вставка нового заказа
     */
    public function insertOrder($orderData) {
        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO orders 
                (order_id, name, phone, email, event_type, event_date, guests_count, message, source, ip, user_agent, status) 
                VALUES 
                (:order_id, :name, :phone, :email, :event_type, :event_date, :guests_count, :message, :source, :ip, :user_agent, 'new')"
            );
            
            $result = $stmt->execute([
                ':order_id'     => $orderData['id'],
                ':name'         => $orderData['name'],
                ':phone'        => $orderData['phone'],
                ':email'        => $orderData['email'] ?? null,
                ':event_type'   => $orderData['event_type'],
                ':event_date'   => $orderData['event_date'] ?? null,
                ':guests_count' => $orderData['guests'] ?? null,
                ':message'      => $orderData['message'] ?? null,
                ':source'       => $orderData['source'],
                ':ip'           => $orderData['ip'],
                ':user_agent'   => $orderData['user_agent']
            ]);
            
            return $result ? $this->connection->lastInsertId() : false;
            
        } catch (PDOException $e) {
            error_log("Order insert error: ".$e->getMessage()."\nData: ".print_r($orderData, true), 
                     3, __DIR__.'/../logs/db_errors.log');
            return false;
        }
    }
    
    /**
     * Получение списка заказов
     */
    public function getOrders($limit = 10, $status = null) {
        try {
            $sql = "SELECT * FROM orders";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit";
            $params[':limit'] = (int)$limit;
            
            $stmt = $this->connection->prepare($sql);
            
            foreach ($params as $key => $value) {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($key, $value, $paramType);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get orders error: ".$e->getMessage(), 3, __DIR__.'/../logs/db_errors.log');
            return [];
        }
    }
    
    /**
     * Получение заказа по ID
     */
    public function getOrder($orderId) {
        try {
            $stmt = $this->connection->prepare(
                "SELECT * FROM orders WHERE order_id = :order_id LIMIT 1"
            );
            
            $stmt->execute([':order_id' => $orderId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get order error: ".$e->getMessage()." | OrderID: $orderId", 
                     3, __DIR__.'/../logs/db_errors.log');
            return null;
        }
    }
    
    /**
     * Обновление статуса заказа
     */
    public function updateOrderStatus($orderId, $status) {
        try {
            $stmt = $this->connection->prepare(
                "UPDATE orders SET status = :status WHERE order_id = :order_id"
            );
            
            return $stmt->execute([
                ':order_id' => $orderId,
                ':status'   => $status
            ]);
            
        } catch (PDOException $e) {
            error_log("Update order status error: ".$e->getMessage(), 
                     3, __DIR__.'/../logs/db_errors.log');
            return false;
        }
    }
}

// Создаем экземпляр подключения
try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Проверка соединения
    $db->query("SELECT 1");
    
} catch (PDOException $e) {
    error_log("Critical DB connection error: ".$e->getMessage(), 
             3, __DIR__.'/../logs/db_errors.log');
    die(json_encode([
        'success' => false,
        'message' => 'Системная ошибка базы данных'
    ]));
}