-- =====================================================
-- INVENTRA-TRACKER COMPLETE DATABASE SCHEMA
-- =====================================================
-- This file contains the complete database structure for the Inventra-Tracker project
-- Version: 1.0.0
-- Last Updated: 2024
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS missing_items_db;
USE missing_items_db;

-- =====================================================
-- USERS TABLE
-- =====================================================
-- Stores user account information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for better performance
    INDEX idx_email (email),
    INDEX idx_is_admin (is_admin),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- REPORTS TABLE
-- =====================================================
-- Stores all reports (items, persons, pets)
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    category ENUM('item', 'person', 'pet') NOT NULL DEFAULT 'item',
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    contact_info TEXT,
    photo VARCHAR(255) DEFAULT NULL, -- Legacy field for backward compatibility
    status ENUM('pending', 'returned') DEFAULT 'pending',
    reported_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_reported_by (reported_by),
    INDEX idx_created_at (created_at),
    INDEX idx_coordinates (latitude, longitude),
    INDEX idx_location (location),
    FULLTEXT idx_search (title, description, location)
);

-- =====================================================
-- REPORT_MEDIA TABLE
-- =====================================================
-- Stores multiple media files (photos and videos) for reports
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
    
    -- Foreign key constraints
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_report_id (report_id),
    INDEX idx_file_type (file_type),
    INDEX idx_is_primary (is_primary),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- COMMENTS TABLE
-- =====================================================
-- Stores comments and replies for reports
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    parent_id INT DEFAULT NULL,
    reply_to VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_report_id (report_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
-- Stores user notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    notification_type ENUM('report_update', 'comment', 'system', 'match') DEFAULT 'system',
    related_id INT DEFAULT NULL, -- ID of related report, comment, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_notification_type (notification_type),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- SESSIONS TABLE (Optional - for session management)
-- =====================================================
-- Stores user sessions for better security
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);

-- =====================================================
-- AUDIT_LOG TABLE (Optional - for security tracking)
-- =====================================================
-- Logs important actions for security and debugging
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, is_admin) VALUES 
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insert sample regular user (password: user123)
INSERT INTO users (name, email, password, is_admin) VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- Insert sample reports
INSERT INTO reports (title, type, category, description, location, latitude, longitude, contact_info, reported_by) VALUES
('Lost Golden Retriever', 'lost', 'pet', 'Friendly golden retriever named Max, wearing a blue collar. Last seen near Central Park.', 'Central Park, New York', 40.7829, -73.9654, 'Contact: 555-0123', 2),
('Found iPhone 13', 'found', 'item', 'Found a black iPhone 13 with a cracked screen protector. Located near Starbucks on 5th Avenue.', 'Starbucks, 5th Avenue', 40.7589, -73.9851, 'Contact: 555-0456', 2),
('Missing Elderly Person', 'lost', 'person', 'Elderly woman, approximately 70 years old, wearing a red sweater and black pants. May be confused.', 'Times Square Area', 40.7580, -73.9855, 'Contact: 555-0789', 2),
('Found Black Cat', 'found', 'pet', 'Found a friendly black cat with white paws. No collar but very affectionate.', 'Brooklyn Bridge Park', 40.7021, -73.9969, 'Contact: 555-0321', 2);

-- Insert sample comments
INSERT INTO comments (report_id, user_id, comment) VALUES
(1, 2, 'I think I saw a dog matching this description near the fountain yesterday. Will keep an eye out.'),
(2, 1, 'This phone has been claimed by its owner. Thank you for your help!'),
(3, 2, 'Update: She has been found safe and reunited with her family. Thank you everyone!');

-- Insert sample notifications
INSERT INTO notifications (user_id, message, notification_type, related_id) VALUES
(2, 'Your report "Lost Golden Retriever" has received a new comment.', 'comment', 1),
(2, 'Your report "Found iPhone 13" has been marked as returned.', 'report_update', 2);

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for active reports (not returned)
CREATE VIEW active_reports AS
SELECT 
    r.*,
    u.name as reporter_name,
    u.email as reporter_email,
    COUNT(rm.id) as media_count,
    COUNT(c.id) as comment_count
FROM reports r
LEFT JOIN users u ON r.reported_by = u.id
LEFT JOIN report_media rm ON r.id = rm.report_id
LEFT JOIN comments c ON r.id = c.report_id AND c.parent_id IS NULL
WHERE r.status = 'pending'
GROUP BY r.id
ORDER BY r.created_at DESC;

-- View for recent activity
CREATE VIEW recent_activity AS
SELECT 
    'report' as type,
    r.id,
    r.title,
    r.category,
    r.type,
    r.created_at,
    u.name as user_name
FROM reports r
JOIN users u ON r.reported_by = u.id
UNION ALL
SELECT 
    'comment' as type,
    c.report_id as id,
    CONCAT('Comment on: ', r.title) as title,
    r.category,
    r.type,
    c.created_at,
    u.name as user_name
FROM comments c
JOIN reports r ON c.report_id = r.id
JOIN users u ON c.user_id = u.id
WHERE c.parent_id IS NULL
ORDER BY created_at DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure to get reports by category
DELIMITER //
CREATE PROCEDURE GetReportsByCategory(IN category_param ENUM('item', 'person', 'pet'))
BEGIN
    SELECT 
        r.*,
        u.name as reporter_name,
        COUNT(rm.id) as media_count,
        COUNT(c.id) as comment_count
    FROM reports r
    LEFT JOIN users u ON r.reported_by = u.id
    LEFT JOIN report_media rm ON r.id = rm.report_id
    LEFT JOIN comments c ON r.id = c.report_id AND c.parent_id IS NULL
    WHERE r.category = category_param
    GROUP BY r.id
    ORDER BY r.created_at DESC;
END //
DELIMITER ;

-- Procedure to search reports
DELIMITER //
CREATE PROCEDURE SearchReports(IN search_term VARCHAR(255))
BEGIN
    SELECT 
        r.*,
        u.name as reporter_name,
        COUNT(rm.id) as media_count,
        COUNT(c.id) as comment_count
    FROM reports r
    LEFT JOIN users u ON r.reported_by = u.id
    LEFT JOIN report_media rm ON r.id = rm.report_id
    LEFT JOIN comments c ON r.id = c.report_id AND c.parent_id IS NULL
    WHERE MATCH(r.title, r.description, r.location) AGAINST(search_term IN BOOLEAN MODE)
       OR r.title LIKE CONCAT('%', search_term, '%')
       OR r.description LIKE CONCAT('%', search_term, '%')
       OR r.location LIKE CONCAT('%', search_term, '%')
    GROUP BY r.id
    ORDER BY r.created_at DESC;
END //
DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger to update report status when marked as returned
DELIMITER //
CREATE TRIGGER update_report_timestamp
BEFORE UPDATE ON reports
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- Trigger to log report status changes
DELIMITER //
CREATE TRIGGER log_report_status_change
AFTER UPDATE ON reports
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values)
        VALUES (
            NEW.reported_by,
            'status_change',
            'reports',
            NEW.id,
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status)
        );
    END IF;
END //
DELIMITER ;

-- =====================================================
-- FINAL COMMENTS
-- =====================================================
-- 
-- This database schema includes:
-- 1. All core tables for the Inventra-Tracker application
-- 2. Proper foreign key constraints and indexes
-- 3. Sample data for testing
-- 4. Views for common queries
-- 5. Stored procedures for complex operations
-- 6. Triggers for automatic updates and logging
-- 7. Support for multiple media files per report
-- 8. Comment system with replies
-- 9. Notification system
-- 10. Session management
-- 11. Audit logging for security
--
-- To use this schema:
-- 1. Run this SQL file in your MySQL/MariaDB server
-- 2. Update the database connection settings in config.php
-- 3. The default admin credentials are:
--    Email: admin@example.com
--    Password: admin123
--
-- ===================================================== 