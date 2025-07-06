<?php
/**
 * Application Configuration
 * 
 * This file contains all configuration settings for the application.
 * Update these values according to your environment.
 */

// Prevent multiple inclusions
if (defined('APP_CONFIG_LOADED')) {
    return;
}
define('APP_CONFIG_LOADED', true);

// Environment setting
define('ENVIRONMENT', 'development'); // Change to 'production' for live deployment

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'missing_items_db');

// Production database settings (uncomment and update for production)
// define('DB_HOST', 'your_production_host');
// define('DB_USER', 'your_production_username');
// define('DB_PASS', 'your_production_password');
// define('DB_NAME', 'your_production_database');

// Application Settings
define('APP_NAME', 'Inventra - Missing Items Tracker');
define('APP_URL', 'http://localhost/Inventra-Tracker'); // Update for production
define('APP_VERSION', '1.0.0');

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR', 'uploads/');
define('PROFILE_PHOTOS_DIR', 'uploads/profile_photos/');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
}

// Timezone
date_default_timezone_set('UTC');

// Session Configuration - These will be set in security.php before session starts
// Note: Session settings must be set before session_start() is called

// Helper function to get database configuration
function getDatabaseConfig() {
    return [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'name' => DB_NAME
    ];
}

// Helper function to check if in development mode
function isDevelopment() {
    return ENVIRONMENT === 'development';
}

// Helper function to check if in production mode
function isProduction() {
    return ENVIRONMENT === 'production';
}

function getEmailConfig() {
    return [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'your-email@gmail.com',
        'smtp_password' => 'your-app-password',
        'from_email' => 'noreply@inventra.com',
        'from_name' => 'Inventra Team'
    ];
}
?> 