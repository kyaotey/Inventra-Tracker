<?php
require_once 'config.php';
require_once 'includes/db.php';

echo "<h2>Database Setup and Verification</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
</style>";

// Test database connection
echo "<div class='section'>";
echo "<h3>1. Database Connection Test</h3>";
if ($conn->ping()) {
    echo "<p class='success'>✅ Database connection successful!</p>";
} else {
    echo "<p class='error'>❌ Database connection failed!</p>";
    exit();
}
echo "</div>";

// Check if all required tables exist
echo "<div class='section'>";
echo "<h3>2. Table Structure Verification</h3>";
$required_tables = [
    'users' => [
        'id', 'name', 'email', 'profile_photo', 'password', 'is_admin', 'created_at'
    ],
    'reports' => [
        'id', 'title', 'type', 'category', 'description', 'location', 'contact_info', 
        'photo', 'status', 'reported_by', 'created_at'
    ],
    'report_media' => [
        'id', 'report_id', 'file_path', 'file_type', 'file_name', 'file_size', 
        'mime_type', 'is_primary', 'created_at'
    ],
    'notifications' => [
        'id', 'user_id', 'message', 'is_read', 'created_at'
    ],
    'comments' => [
        'id', 'report_id', 'user_id', 'comment', 'parent_id', 'created_at'
    ]
];

foreach ($required_tables as $table => $columns) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ Table '$table' exists</p>";
        
        // Check columns
        $result = $conn->query("DESCRIBE $table");
        $existing_columns = [];
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        $missing_columns = array_diff($columns, $existing_columns);
        if (!empty($missing_columns)) {
            echo "<p class='warning'>⚠️ Missing columns in $table: " . implode(', ', $missing_columns) . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Table '$table' does not exist</p>";
    }
}
echo "</div>";

// Check for admin user
echo "<div class='section'>";
echo "<h3>3. Admin User Verification</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
$admin_count = $result->fetch_assoc()['count'];

if ($admin_count > 0) {
    echo "<p class='success'>✅ Admin user exists ($admin_count admin(s))</p>";
} else {
    echo "<p class='error'>❌ No admin user found!</p>";
    echo "<p class='info'>Creating default admin user...</p>";
    
    // Create default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, is_admin) VALUES ('Admin User', 'admin@example.com', '$admin_password', 1)";
    
    if ($conn->query($sql)) {
        echo "<p class='success'>✅ Default admin user created!</p>";
        echo "<p class='info'>Email: admin@example.com | Password: admin123</p>";
    } else {
        echo "<p class='error'>❌ Failed to create admin user: " . $conn->error . "</p>";
    }
}
echo "</div>";

// Check data integrity
echo "<div class='section'>";
echo "<h3>4. Data Integrity Check</h3>";

// Check users count
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
echo "<p class='info'>Total users: $user_count</p>";

// Check reports count
$result = $conn->query("SELECT COUNT(*) as count FROM reports");
$report_count = $result->fetch_assoc()['count'];
echo "<p class='info'>Total reports: $report_count</p>";

// Check for orphaned reports (reports without valid users)
$result = $conn->query("SELECT COUNT(*) as count FROM reports r LEFT JOIN users u ON r.reported_by = u.id WHERE r.reported_by IS NOT NULL AND u.id IS NULL");
$orphaned_reports = $result->fetch_assoc()['count'];
if ($orphaned_reports > 0) {
    echo "<p class='warning'>⚠️ Found $orphaned_reports orphaned reports (reports from deleted users)</p>";
} else {
    echo "<p class='success'>✅ No orphaned reports found</p>";
}

echo "</div>";

// Check database permissions
echo "<div class='section'>";
echo "<h3>5. Database Permissions Check</h3>";
$permissions = [
    'SELECT' => false,
    'INSERT' => false,
    'UPDATE' => false,
    'DELETE' => false,
    'CREATE' => false,
    'ALTER' => false
];

foreach ($permissions as $permission => &$has_permission) {
    $result = $conn->query("SHOW GRANTS FOR CURRENT_USER()");
    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Grants for ' . DB_USER . '@' . DB_HOST], $permission) !== false) {
            $has_permission = true;
            break;
        }
    }
}

foreach ($permissions as $permission => $has_permission) {
    if ($has_permission) {
        echo "<p class='success'>✅ $permission permission available</p>";
    } else {
        echo "<p class='warning'>⚠️ $permission permission not available</p>";
    }
}
echo "</div>";

// Check for common issues
echo "<div class='section'>";
echo "<h3>6. Common Issues Check</h3>";

// Check if timezone is set
$result = $conn->query("SELECT @@time_zone as timezone");
$timezone = $result->fetch_assoc()['timezone'];
echo "<p class='info'>Database timezone: $timezone</p>";

// Check character set
$result = $conn->query("SELECT @@character_set_database as charset");
$charset = $result->fetch_assoc()['charset'];
echo "<p class='info'>Database character set: $charset</p>";

// Check for any errors in error log
if (file_exists('error.log')) {
    $error_log_size = filesize('error.log');
    if ($error_log_size > 0) {
        echo "<p class='warning'>⚠️ Error log file exists and has content ($error_log_size bytes)</p>";
    } else {
        echo "<p class='success'>✅ Error log is empty</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No error log file found</p>";
}

echo "</div>";

// Summary and recommendations
echo "<div class='section'>";
echo "<h3>7. Summary and Recommendations</h3>";

if ($admin_count == 0) {
    echo "<p class='error'>❌ CRITICAL: No admin user found. Please create an admin user.</p>";
}

if ($user_count == 0) {
    echo "<p class='warning'>⚠️ No users found. Consider creating some test users.</p>";
}

echo "<p class='info'>If you're still experiencing issues:</p>";
echo "<ul>";
echo "<li>Check that XAMPP MySQL service is running</li>";
echo "<li>Verify database credentials in config.php</li>";
echo "<li>Ensure all SQL files have been imported</li>";
echo "<li>Check file permissions for uploads directory</li>";
echo "<li>Review error logs for specific error messages</li>";
echo "</ul>";

echo "<p><a href='index.php'>← Go back to homepage</a></p>";
echo "</div>";

$conn->close();
?> 