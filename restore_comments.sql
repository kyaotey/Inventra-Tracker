-- Restore Comments System for Inventra-Tracker
-- This script recreates the comments table that was accidentally removed

USE missing_items_db;

-- Recreate the comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    parent_id INT DEFAULT NULL,
    reply_to VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id)
);

-- Add some sample comments if the table is empty (optional)
-- INSERT INTO comments (report_id, user_id, comment, created_at) VALUES 
-- (1, 1, 'This is a sample comment', NOW()); 