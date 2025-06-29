<?php
require 'includes/db.php';

echo "<h2>Database Update Script</h2>";

try {
    // Add category column if it doesn't exist
    $checkColumn = $conn->query("SHOW COLUMNS FROM reports LIKE 'category'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE reports ADD COLUMN category ENUM('item', 'person', 'pet') NOT NULL DEFAULT 'item' AFTER type");
        echo "<p style='color: green;'>✓ Category column added successfully</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Category column already exists</p>";
    }
    
    // Update existing records to have 'item' as default category
    $result = $conn->query("UPDATE reports SET category = 'item' WHERE category IS NULL OR category = ''");
    echo "<p style='color: green;'>✓ Updated existing records with default category</p>";
    
    echo "<p style='color: green;'><strong>Database update completed successfully!</strong></p>";
    echo "<p><a href='index.php'>← Back to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 