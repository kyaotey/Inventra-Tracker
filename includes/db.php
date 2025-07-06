<?php
// Database configuration for production hosting
// Update these values with your hosting provider's database credentials



// For development, you can use these local settings:
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'missing_items_db';

// Set error reporting for production (hide errors from users)
error_reporting(0);
ini_set('display_errors', 0); // Hide errors in production

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please try again later.");
    }
    
    // Set charset to prevent SQL injection
    $conn->set_charset("utf8mb4");
    
    // Set timezone
    $conn->query("SET time_zone = '+00:00'");
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

function createNotification($conn, $user_id, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $stmt->close();
}
?>
