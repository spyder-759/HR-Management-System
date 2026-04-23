<?php
/**
 * Database Configuration
 * Centralized database settings for the HR Management System
 */

// Load environment configuration
require_once __DIR__ . '/env.php';

// Database connection settings
class DatabaseConfig {
    private static $instance = null;
    private $connection;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . ENV_DB_HOST . ";dbname=" . ENV_DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, ENV_DB_USER, ENV_DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact administrator.");
            }
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Database helper functions
function getDB() {
    return DatabaseConfig::getInstance()->getConnection();
}

// Backup configuration
define('BACKUP_ENABLED', true);
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_SCHEDULE', 'daily'); // daily, weekly, monthly
define('BACKUP_RETENTION_DAYS', 30);

// Database optimization settings
define('DB_OPTIMIZE_ENABLED', true);
define('DB_OPTIMIZE_SCHEDULE', 'weekly');
?>
