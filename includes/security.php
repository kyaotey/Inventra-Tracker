<?php
// Security configuration and headers

// Set secure session configuration (only if session hasn't started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src \'self\' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\';');

// Prevent caching of sensitive pages
function noCacheHeaders() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

// CSRF token generation and validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Rate limiting function
function checkRateLimit($action, $limit = 5, $timeWindow = 300) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
    }
    
    if (time() > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
    }
    
    if ($_SESSION[$key]['count'] >= $limit) {
        return false; // Rate limit exceeded
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// Log security events
function logSecurityEvent($event, $details = '') {
    $logEntry = date('Y-m-d H:i:s') . " - " . $event . " - " . $_SERVER['REMOTE_ADDR'] . " - " . $details . "\n";
    error_log($logEntry, 3, 'security.log');
}

// Validate file upload
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    if (!in_array($mimeType, $allowedMimes)) {
        return false;
    }
    
    return true;
}

// Secure password validation
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
    return preg_match($pattern, $password);
}

// Secure email validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && 
           preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
}

// Validate multiple media uploads (photos and videos)
function validateMediaUpload($file, $maxSize = 10485760) { // 10MB default
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File size exceeds limit (' . formatBytes($maxSize) . ')'];
    }
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    // Allowed MIME types for images and videos
    $allowedMimes = [
        // Images
        'image/jpeg' => 'image',
        'image/jpg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'image/webp' => 'image',
        // Videos
        'video/mp4' => 'video',
        'video/avi' => 'video',
        'video/mov' => 'video',
        'video/wmv' => 'video',
        'video/flv' => 'video',
        'video/webm' => 'video',
        'video/mkv' => 'video'
    ];
    
    if (!array_key_exists($mimeType, $allowedMimes)) {
        return ['valid' => false, 'error' => 'File type not allowed. Supported: JPEG, PNG, GIF, WebP, MP4, AVI, MOV, WMV, FLV, WebM, MKV'];
    }
    
    return [
        'valid' => true, 
        'file_type' => $allowedMimes[$mimeType],
        'mime_type' => $mimeType
    ];
}

// Upload multiple media files
function uploadMediaFiles($files, $uploadDir = 'uploads/', $reportId = null) {
    $uploadedFiles = [];
    $errors = [];
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }
    }
    
    // Handle multiple files
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue; // Skip empty file inputs
        }
        
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        // Validate file
        $validation = validateMediaUpload($file);
        if (!$validation['valid']) {
            $errors[] = $file['name'] . ': ' . $validation['error'];
            continue;
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $uploadedFiles[] = [
                'file_path' => $filePath,
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'file_type' => $validation['file_type'],
                'mime_type' => $validation['mime_type'],
                'is_primary' => count($uploadedFiles) === 0 // First file is primary
            ];
        } else {
            $errors[] = $file['name'] . ': Failed to move uploaded file';
        }
    }
    
    return [
        'success' => count($uploadedFiles) > 0,
        'files' => $uploadedFiles,
        'errors' => $errors
    ];
}

// Format bytes to human readable format
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Get media files for a report
function getReportMedia($reportId, $conn) {
    $stmt = $conn->prepare("SELECT * FROM report_media WHERE report_id = ? ORDER BY is_primary DESC, created_at ASC");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $media = [];
    while ($row = $result->fetch_assoc()) {
        $media[] = $row;
    }
    
    return $media;
}

// Delete media file from database and filesystem
function deleteMediaFile($mediaId, $conn) {
    // Get file info before deletion
    $stmt = $conn->prepare("SELECT file_path FROM report_media WHERE id = ?");
    $stmt->bind_param("i", $mediaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filePath = $row['file_path'];
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM report_media WHERE id = ?");
        $stmt->bind_param("i", $mediaId);
        $success = $stmt->execute();
        
        // Delete from filesystem
        if ($success && file_exists($filePath)) {
            unlink($filePath);
        }
        
        return $success;
    }
    
    return false;
}
?> 