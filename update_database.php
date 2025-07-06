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
    
    // Check if the report_media table exists
    $tableExists = false;
    $result = $conn->query("SHOW TABLES LIKE 'report_media'");
    if ($result->num_rows > 0) {
        $tableExists = true;
    }

    if (!$tableExists) {
        // Create the report_media table
        $createTableSQL = "
        CREATE TABLE report_media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_id INT NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_type ENUM('image', 'video') NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
            INDEX idx_report_id (report_id),
            INDEX idx_file_type (file_type)
        )";
        
        if ($conn->query($createTableSQL) === TRUE) {
            echo "✅ Successfully created report_media table<br>";
        } else {
            echo "❌ Error creating report_media table: " . $conn->error . "<br>";
        }
    } else {
        echo "✅ report_media table already exists<br>";
    }

    // Check if there are any existing reports with photos that need to be migrated
    $result = $conn->query("SELECT id, photo FROM reports WHERE photo IS NOT NULL AND photo != ''");
    $migratedCount = 0;

    if ($result->num_rows > 0) {
        echo "<br>🔄 Migrating existing photos to new media system...<br>";
        
        while ($row = $result->fetch_assoc()) {
            $reportId = $row['id'];
            $photoPath = $row['photo'];
            
            // Check if the photo file exists
            if (file_exists($photoPath)) {
                // Get file info
                $fileSize = filesize($photoPath);
                $mimeType = mime_content_type($photoPath);
                $fileName = basename($photoPath);
                
                // Determine file type
                $fileType = 'image';
                if (strpos($mimeType, 'video/') === 0) {
                    $fileType = 'video';
                }
                
                // Insert into report_media table
                $stmt = $conn->prepare("INSERT INTO report_media (report_id, file_path, file_type, file_name, file_size, mime_type, is_primary) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("isssss", $reportId, $photoPath, $fileType, $fileName, $fileSize, $mimeType);
                
                if ($stmt->execute()) {
                    $migratedCount++;
                    echo "✅ Migrated photo for report #$reportId<br>";
                } else {
                    echo "❌ Failed to migrate photo for report #$reportId: " . $stmt->error . "<br>";
                }
            } else {
                echo "⚠️ Photo file not found for report #$reportId: $photoPath<br>";
            }
        }
        
        echo "<br>📊 Migration complete: $migratedCount photos migrated<br>";
    } else {
        echo "<br>✅ No existing photos to migrate<br>";
    }

    // Add profile_photo column to users table if it doesn't exist
    $checkProfilePhotoColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    if ($checkProfilePhotoColumn->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL AFTER email");
        echo "<p style='color: green;'>✓ Profile photo column added successfully</p>";
    } else {
        echo "<p style='color: blue;'>ℹ Profile photo column already exists</p>";
    }

    echo "<p style='color: green;'><strong>Database update completed successfully!</strong></p>";
    echo "<p><a href='index.php'>← Back to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 