<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

function loadEnv($path = null) {
    $path = $path ?: __DIR__ . '/.env';
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key-value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    
    return true;
}

// Load environment variables
loadEnv();

// Define constants from environment variables
define('ENV_DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('ENV_DB_NAME', getenv('DB_NAME') ?: 'hr_management_system');
define('ENV_DB_USER', getenv('DB_USER') ?: 'root');
define('ENV_DB_PASS', getenv('DB_PASS') ?: '');

define('APP_NAME', getenv('APP_NAME') ?: 'HR Management System');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 3600);
define('PASSWORD_MIN_LENGTH', getenv('PASSWORD_MIN_LENGTH') ?: 8);
define('MAX_LOGIN_ATTEMPTS', getenv('MAX_LOGIN_ATTEMPTS') ?: 5);

define('MAX_FILE_SIZE', getenv('MAX_FILE_SIZE') ?: 5242880);
define('ALLOWED_FILE_TYPES', getenv('ALLOWED_FILE_TYPES') ?: 'pdf,doc,docx');

define('TIMEZONE', getenv('TIMEZONE') ?: 'UTC');

// Set timezone
date_default_timezone_set(TIMEZONE);
?>
