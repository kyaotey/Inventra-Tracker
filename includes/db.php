<?php
// Load configuration if available, otherwise use defaults
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    $config = getDatabaseConfig();
    $host = $config['host'];
    $user = $config['user'];
    $pass = $config['pass'];
    $dbname = $config['name'];
} else {
    // Fallback to default configuration
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'missing_items_db';
}

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please try again later.");
    }
    
    // Set charset to prevent SQL injection
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Failed to set database charset: " . $conn->error);
    }
    
    // Set timezone
    if (!$conn->query("SET time_zone = '+00:00'")) {
        error_log("Failed to set database timezone: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

?>
