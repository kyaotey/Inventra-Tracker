<?php
require 'includes/db.php';

echo "Updating database schema to add coordinate columns...\n";

try {
    // Add latitude and longitude columns if they don't exist
    $sql = "ALTER TABLE reports 
            ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL";
    
    if ($conn->query($sql)) {
        echo "✓ Successfully added latitude and longitude columns\n";
    } else {
        echo "✗ Error adding columns: " . $conn->error . "\n";
    }
    
    // Add index for better performance on coordinate-based queries
    $indexSql = "CREATE INDEX IF NOT EXISTS idx_coordinates ON reports(latitude, longitude)";
    
    if ($conn->query($indexSql)) {
        echo "✓ Successfully added coordinate index\n";
    } else {
        echo "✗ Error adding index: " . $conn->error . "\n";
    }
    
    echo "\nDatabase update completed successfully!\n";
    echo "You can now use the map functionality in your reports.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?> 