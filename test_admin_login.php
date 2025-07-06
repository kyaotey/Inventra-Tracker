<?php
require "includes/db.php";

echo "<h2>Admin Login Troubleshooting</h2>";

// Check database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Users table does not exist. Please run the database setup.</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Users table exists</p>";
}

// Check for admin users
$result = $conn->query("SELECT id, name, email, is_admin FROM users WHERE is_admin = 1");
if ($result->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ No admin users found in database</p>";
    echo "<p>You need to create an admin user. You can:</p>";
    echo "<ol>";
    echo "<li>Use the <code>create_admin.php</code> file (update the email and password first)</li>";
    echo "<li>Or manually insert an admin user into the database</li>";
    echo "</ol>";
} else {
    echo "<p style='color: green;'>✅ Found " . $result->num_rows . " admin user(s):</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: " . $row['id'] . " - Name: " . $row['name'] . " - Email: " . $row['email'] . "</li>";
    }
    echo "</ul>";
}

// Check for regular users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
$regular_users = $result->fetch_assoc()['count'];
echo "<p>📊 Total regular users: " . $regular_users . "</p>";

// Show login instructions
echo "<h3>How to Login as Admin:</h3>";
echo "<ol>";
echo "<li>Go to the main login page: <a href='login.php'>login.php</a></li>";
echo "<li>Use an admin email and password</li>";
echo "<li>You will be automatically redirected to the admin dashboard</li>";
echo "</ol>";

// Check if admin dashboard is accessible
if (file_exists('admin/dashboard.php')) {
    echo "<p style='color: green;'>✅ Admin dashboard file exists</p>";
} else {
    echo "<p style='color: red;'>❌ Admin dashboard file missing</p>";
}

// Check if auth.php is accessible
if (file_exists('admin/auth.php')) {
    echo "<p style='color: green;'>✅ Admin auth file exists</p>";
} else {
    echo "<p style='color: red;'>❌ Admin auth file missing</p>";
}

echo "<h3>Quick Admin Creation:</h3>";
echo "<p>If you need to create an admin user quickly, you can run this SQL:</p>";
echo "<pre>";
echo "INSERT INTO users (name, email, password, is_admin) VALUES ";
echo "('Admin User', 'admin@example.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 1);";
echo "</pre>";
echo "<p>This will create an admin user with:</p>";
echo "<ul>";
echo "<li>Email: admin@example.com</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";
?> 