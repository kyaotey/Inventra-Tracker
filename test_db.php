<?php
require 'includes/db.php';

echo "<h2>Database Connection Test</h2>";

// Test database connection
if ($conn->ping()) {
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
    exit();
}

// Test if tables exist
$tables = ['users', 'reports'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
    }
}

// Test if admin user exists
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
$admin_count = $result->fetch_assoc()['count'];
echo "<p>Admin users: $admin_count</p>";

// Test if any users exist
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
echo "<p>Total users: $user_count</p>";

// Test if any reports exist
$result = $conn->query("SELECT COUNT(*) as count FROM reports");
$report_count = $result->fetch_assoc()['count'];
echo "<p>Total reports: $report_count</p>";

echo "<p><a href='index.php'>Go back to homepage</a></p>";
?> 