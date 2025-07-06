<?php
require_once 'includes/db.php';

// Fetch all users from the database
$sql = "SELECT id, name, email FROM users";
$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $user_id = $row['id'];
    $nickname = $row['name'];
    echo "User ID: $user_id, Name: $nickname - Skipped (Sendbird integration disabled).\n";
}

echo "\nSync complete.\n"; 