<?php
/**
 * Application Configuration
 * General application settings and constants
 */

// Load environment configuration
require_once __DIR__ . '/env.php';

// Application paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('BACKUPS_PATH', ROOT_PATH . 'backups/');
define('LOGS_PATH', ROOT_PATH . 'logs/');
define('CACHE_PATH', ROOT_PATH . 'cache/');

// Create necessary directories if they don't exist
$required_dirs = [UPLOADS_PATH, BACKUPS_PATH, LOGS_PATH, CACHE_PATH];
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Application settings
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', APP_ENV === 'development');
define('APP_MAINTENANCE', false);

// Security settings
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'your-secret-encryption-key-here');
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-jwt-secret-key-here');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', APP_ENV === 'production');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Email configuration
define('EMAIL_FROM_NAME', APP_NAME);
define('EMAIL_FROM_ADDRESS', getenv('EMAIL_FROM') ?: 'noreply@hrms.com');
define('EMAIL_REPLY_TO', getenv('EMAIL_REPLY_TO') ?: 'support@hrms.com');

// SMTP settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

// File upload settings
define('UPLOAD_MAX_SIZE', MAX_FILE_SIZE);
define('UPLOAD_ALLOWED_TYPES', explode(',', ALLOWED_FILE_TYPES));

// Pagination settings
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Date/time formats
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M j, Y');
define('DISPLAY_DATETIME_FORMAT', 'M j, Y H:i');

// Currency settings
define('CURRENCY_CODE', 'USD');
define('CURRENCY_SYMBOL', '$');

// Company settings
define('COMPANY_NAME', getenv('COMPANY_NAME') ?: 'Your Company');
define('COMPANY_ADDRESS', getenv('COMPANY_ADDRESS') ?: '');
define('COMPANY_PHONE', getenv('COMPANY_PHONE') ?: '');
define('COMPANY_EMAIL', getenv('COMPANY_EMAIL') ?: '');

// Leave settings
define('ANNUAL_LEAVE_DAYS', 21);
define('SICK_LEAVE_DAYS', 10);
define('PERSONAL_LEAVE_DAYS', 5);

// Working hours
define('WORK_START_TIME', '09:00');
define('WORK_END_TIME', '17:00');
define('LUNCH_BREAK_DURATION', 60); // minutes

// Performance review settings
define('PERFORMANCE_REVIEW_FREQUENCY', 'quarterly'); // monthly, quarterly, semi-annual, annual
define('PERFORMANCE_RATINGS', [1, 2, 3, 4, 5]); // 1-5 scale

// Notification settings
define('NOTIFICATION_EMAIL_ENABLED', true);
define('NOTIFICATION_SMS_ENABLED', false);
define('NOTIFICATION_BROWSER_ENABLED', true);

// API settings
define('API_RATE_LIMIT', 100); // requests per hour
define('API_TOKEN_LIFETIME', 86400); // 24 hours

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Logging settings
define('LOG_ENABLED', true);
define('LOG_LEVEL', APP_ENV === 'development' ? 'DEBUG' : 'INFO');
define('LOG_MAX_FILES', 30);
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . 'error.log');
}
?>
