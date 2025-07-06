<?php
require __DIR__ . '/includes/security.php';
session_start();
require __DIR__ . '/includes/db.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_comment') {
        $report_id = $_POST['report_id'] ?? null;
        $comment_text = trim($_POST['comment'] ?? '');
        $parent_id = $_POST['parent_id'] ?? null;
        
        if ($report_id && !empty($comment_text)) {
            // Insert comment
            $stmt = $conn->prepare("
                INSERT INTO comments (report_id, user_id, comment, parent_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iisi", $report_id, $user_id, $comment_text, $parent_id);
            $stmt->execute();
            $comment_id = $conn->insert_id;
            $stmt->close();
            
            if ($comment_id) {
                // Comment added successfully
                $_SESSION['comment_success'] = 'Comment added successfully!';
            } else {
                $_SESSION['comment_error'] = 'Failed to add comment.';
            }
        } else {
            $_SESSION['comment_error'] = 'Please enter a comment.';
        }
        
        // Redirect back to the report
        header('Location: view.php?id=' . $report_id . '#comments');
        exit;
    }
}

// Handle comment deletion (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    if ($_SESSION['is_admin']) {
        $comment_id = $_POST['comment_id'] ?? null;
        $report_id = $_POST['report_id'] ?? null;
        
        if ($comment_id) {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->bind_param("i", $comment_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['comment_success'] = 'Comment deleted successfully!';
        }
        
        header('Location: view.php?id=' . $report_id . '#comments');
        exit;
    }
}

// Get comments for a report
function getComments($conn, $report_id) {
    $stmt = $conn->prepare("
        SELECT c.*, u.name as user_name, u.profile_photo 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.report_id = ? 
        ORDER BY c.parent_id ASC, c.created_at ASC
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();
    
    return $comments;
}

// Format comments into a hierarchical structure
function formatComments($comments) {
    $formatted = [];
    $replies = [];
    
    foreach ($comments as $comment) {
        if ($comment['parent_id'] === null) {
            $formatted[] = $comment;
        } else {
            if (!isset($replies[$comment['parent_id']])) {
                $replies[$comment['parent_id']] = [];
            }
            $replies[$comment['parent_id']][] = $comment;
        }
    }
    
    return ['comments' => $formatted, 'replies' => $replies];
}
?> 