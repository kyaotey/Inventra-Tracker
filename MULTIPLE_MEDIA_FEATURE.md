# Multiple Media Upload Feature

## Overview
The Inventra-Tracker system now supports uploading multiple photos and videos for each report. This enhancement allows users to provide more comprehensive visual information about lost or found items, persons, and pets.

## Features

### Supported File Types
- **Images**: JPEG, PNG, GIF, WebP
- **Videos**: MP4, AVI, MOV, WMV, FLV, WebM, MKV
- **Maximum file size**: 10MB per file
- **Multiple files**: Upload several files at once

### Key Features
1. **Multiple File Selection**: Users can select multiple files at once
2. **Drag & Drop**: Support for drag and drop file uploads
3. **Preview**: Real-time preview of selected files before upload
4. **Remove Files**: Ability to remove individual files before submission
5. **Media Gallery**: Display all uploaded media in a gallery format
6. **Full-Screen View**: Click on media to view in full size
7. **Primary Media**: First uploaded file is marked as primary
8. **Backward Compatibility**: Existing single photos continue to work

## Database Changes

### New Table: `report_media`
```sql
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
);
```

## Implementation Details

### Backend Functions (includes/security.php)
- `validateMediaUpload()`: Validates individual media files
- `uploadMediaFiles()`: Handles multiple file uploads
- `getReportMedia()`: Retrieves media files for a report
- `deleteMediaFile()`: Deletes media files from database and filesystem
- `formatBytes()`: Formats file sizes for display

### Frontend Features
- **Multiple File Input**: HTML5 multiple file input with drag & drop
- **Preview System**: JavaScript-based preview with remove functionality
- **Media Gallery**: Bootstrap-based responsive gallery
- **Modal Viewer**: Full-screen media viewing modal

## Usage

### For Users
1. **Uploading Media**: 
   - Click the upload area or drag files directly
   - Select multiple files (images and/or videos)
   - Preview files before submission
   - Remove unwanted files if needed

2. **Viewing Media**:
   - Media gallery displays all uploaded files
   - Click on any media to view in full size
   - Videos have built-in controls
   - Primary media is marked with a badge

### For Developers
1. **Database Migration**: Run `update_database.php` to create the new table
2. **File Uploads**: Use `uploadMediaFiles()` function for handling uploads
3. **Display Media**: Use `getReportMedia()` to retrieve media for display

## Security Features
- File type validation using MIME types
- File size limits (10MB per file)
- Secure file naming with unique identifiers
- Input sanitization and validation
- Rate limiting for uploads

## Browser Compatibility
- Modern browsers with HTML5 File API support
- Drag & drop support for desktop browsers
- Fallback to click-to-select for mobile devices
- Video playback support varies by browser

## Performance Considerations
- Files are stored in the `uploads/` directory
- Database indexes for efficient queries
- Lazy loading for media galleries
- Optimized file size validation

## Future Enhancements
- Image compression and resizing
- Video thumbnail generation
- Cloud storage integration
- Advanced media editing tools
- Bulk media management for admins 