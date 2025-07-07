-- Add coordinate columns to existing reports table
USE missing_items_db;

-- Add latitude and longitude columns if they don't exist
ALTER TABLE reports 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL;

-- Add index for better performance on coordinate-based queries
CREATE INDEX IF NOT EXISTS idx_coordinates ON reports(latitude, longitude); 