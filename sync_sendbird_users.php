<?php
require_once 'includes/db.php';
require_once 'includes/sendbird.php';

// Fetch all users from the database
$sql = "SELECT id, name, email FROM users";
$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $user_id = $row['id'];
    $nickname = $row['name'];
    // Optionally, you can use email as user_id or nickname if needed
    $response = sendbird_create_user($user_id, $nickname);
    if (isset($response['error'])) {
        echo "User ID: $user_id, Name: $nickname - Error: " . $response['error'] . "\n";
    } elseif ($response['http_code'] === 400 && isset($response['response']['code']) && $response['response']['code'] === 400201) {
        // User already exists in Sendbird
        echo "User ID: $user_id, Name: $nickname - Already exists in Sendbird.\n";
    } elseif ($response['http_code'] === 201 || $response['http_code'] === 200) {
        echo "User ID: $user_id, Name: $nickname - Synced successfully.\n";
    } else {
        echo "User ID: $user_id, Name: $nickname - Unexpected response: ";
        print_r($response);
        echo "\n";
    }
}

echo "\nSync complete.\n"; 