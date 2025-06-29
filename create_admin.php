<?php
require "includes/db.php";

// This script creates a new admin user
// WARNING: Delete this file after use for security

$admin_email = "your-email@example.com"; // Change this to your email
$admin_password = "your-password"; // Change this to your desired password
$admin_name = "Your Name"; // Change this to your name

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing user to admin
    $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
    $stmt->bind_param("s", $admin_email);
    if ($stmt->execute()) {
        echo "User updated to admin successfully!<br>";
        echo "Email: " . $admin_email . "<br>";
        echo "Password: " . $admin_password . "<br>";
    } else {
        echo "Failed to update user to admin.";
    }
} else {
    // Create new admin user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $admin_name, $admin_email, $hashed_password);
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: " . $admin_email . "<br>";
        echo "Password: " . $admin_password . "<br>";
        echo "Name: " . $admin_name . "<br>";
    } else {
        echo "Failed to create admin user.";
    }
}

echo "<br><strong>IMPORTANT:</strong> Delete this file (create_admin.php) after use for security!";
?> 