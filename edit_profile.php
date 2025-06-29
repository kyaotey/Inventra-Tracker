<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/auth.php';
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die('<div style="padding:2rem;text-align:center;color:#fff;background:#ef4444;font-size:1.3rem;">User session not found. Please <a href="login.php" style="color:#fff;text-decoration:underline;">login</a> again.</div>');
}
if (!isset($conn) || !$conn) {
    die('<div style="padding:2rem;text-align:center;color:#fff;background:#ef4444;font-size:1.3rem;">Database connection failed. Please try again later.</div>');
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT name, email, created_at, profile_photo FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $created_at, $profile_photo);
if (!$stmt->fetch()) {
    $name = $email = $created_at = $profile_photo = '';
}
$stmt->close();

$success_message = '';
$error_message = '';

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Invalid CSRF token.';
    } else {
        $new_name = trim($_POST['name']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $new_profile_photo = $_FILES['profile_photo'] ?? null;
        $profile_photo_path = $profile_photo;

        // Handle profile photo upload
        if ($new_profile_photo && $new_profile_photo['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            if (in_array($new_profile_photo['type'], $allowed_types) && $new_profile_photo['size'] <= $max_size) {
                $ext = pathinfo($new_profile_photo['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = 'uploads/profile_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($new_profile_photo['tmp_name'], $target_path)) {
                    $profile_photo_path = $target_path;
                    $stmt = $conn->prepare('UPDATE users SET profile_photo = ? WHERE id = ?');
                    $stmt->bind_param('si', $profile_photo_path, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    header('Location: index.php?profile_updated=1');
                    exit();
                } else {
                    $error_message = 'Failed to upload profile photo.';
                }
            } else {
                $error_message = 'Invalid photo format or size. Only JPEG/PNG under 2MB.';
            }
        }

        // Fetch current user data for password check
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        // Update name if changed
        if ($new_name !== $name && strlen($new_name) >= 2 && strlen($new_name) <= 100) {
            $stmt = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $new_name, $user_id);
            if ($stmt->execute()) {
                $_SESSION['name'] = $new_name;
                header('Location: index.php?profile_updated=1');
                exit();
            } else {
                $error_message = 'Failed to update name.';
            }
            $stmt->close();
        }

        // Update password if provided
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = 'To change your password, fill in all password fields.';
            } elseif (!password_verify($current_password, $hashed_password)) {
                $error_message = 'Current password is incorrect.';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'New passwords do not match.';
            } elseif (!validatePassword($new_password)) {
                $error_message = 'Password must be at least 8 characters, include uppercase, lowercase, and a number.';
            } else {
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->bind_param('si', $new_hashed, $user_id);
                if ($stmt->execute()) {
                    $success_message = 'Password changed successfully.';
                } else {
                    $error_message = 'Failed to update password.';
                }
                $stmt->close();
            }
        }
        // Refresh profile photo after upload
        $profile_photo = $profile_photo_path;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .background-shapes {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: 0;
            pointer-events: none;
        }
        .background-shapes .shape1 {
            position: absolute; left: 5vw; top: 10vh; width: 180px; height: 180px;
            background: linear-gradient(135deg, #6366f1 60%, #8b5cf6 100%);
            opacity: 0.13; border-radius: 50%; filter: blur(8px);
        }
        .background-shapes .shape2 {
            position: absolute; right: 8vw; bottom: 8vh; width: 120px; height: 120px;
            background: linear-gradient(135deg, #10b981 60%, #34d399 100%);
            opacity: 0.10; border-radius: 50%; filter: blur(10px);
        }
        .profile-card {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(18px);
            border-radius: 32px;
            border: 1.5px solid rgba(99,102,241,0.10);
            box-shadow: 0 8px 40px rgba(99,102,241,0.10);
            margin: 3.5rem 0;
            max-width: 430px;
            width: 100%;
            position: relative;
            z-index: 2;
            padding: 2.5rem 2.2rem 2.2rem 2.2rem;
        }
        .avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1 60%, #8b5cf6 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.2rem;
            font-weight: 800;
            margin: 0 auto 1.2rem auto;
            box-shadow: 0 4px 24px rgba(99,102,241,0.13);
            border: 5px solid #fff;
            outline: 3px solid #6366f1;
            outline-offset: 2px;
            transition: box-shadow 0.2s;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .profile-header h2 {
            font-weight: 800;
            font-size: 1.6rem;
            margin-bottom: 0.2rem;
        }
        .profile-header .profile-info {
            color: #6366f1;
            font-size: 1.08rem;
            margin-bottom: 0.2rem;
        }
        .profile-header .profile-date {
            color: #888;
            font-size: 0.98rem;
            margin-bottom: 0.7rem;
        }
        .divider {
            border-top: 1.5px solid #e5e7eb;
            margin: 2.2rem 0 2.2rem 0;
        }
        .profile-section-title {
            font-size: 1.13rem;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 1.2rem;
            letter-spacing: 0.5px;
        }
        .edit-section {
            margin-top: 0;
        }
        .form-floating > .form-control, .form-floating > .form-select {
            height: 3.1rem;
            border-radius: 14px;
            border: 2px solid #e5e7eb;
            background: #f8fafc;
            font-size: 1.08rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-floating > .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.13rem rgba(99,102,241,0.13);
        }
        .form-floating > label {
            color: #6366f1;
            font-weight: 500;
            font-size: 1.02rem;
            left: 1.1rem;
        }
        .profile-card .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 14px;
            padding: 0.8rem 2.2rem;
            font-weight: 700;
            font-size: 1.08rem;
            transition: all 0.2s;
            box-shadow: 0 2px 12px rgba(99,102,241,0.10);
        }
        .profile-card .btn-primary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 32px rgba(99,102,241,0.18);
        }
        .profile-card .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.08rem;
            box-shadow: 0 2px 12px rgba(16,185,129,0.10);
        }
        .profile-card .btn-success .fa-check {
            animation: popCheck 0.5s cubic-bezier(.4,0,.2,1);
        }
        @keyframes popCheck {
            0% { transform: scale(0.2); opacity: 0; }
            60% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .alert {
            font-size: 1.01rem;
        }
        @media (max-width: 600px) {
            .profile-card {
                padding: 1.2rem 0.5rem 1.2rem 0.5rem;
            }
            .avatar {
                width: 80px; height: 80px; font-size: 2.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape1"></div>
        <div class="shape2"></div>
    </div>
    <div class="profile-card">
        <div class="profile-header">
            <div class="avatar" id="avatarPreview">
                <?php if (!empty($profile_photo) && file_exists($profile_photo)): ?>
                    <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <?php echo strtoupper(substr(trim($name), 0, 1)); ?>
                <?php endif; ?>
            </div>
            <h2><?php echo htmlspecialchars($name); ?></h2>
            <div class="profile-info"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($email); ?></div>
            <div class="profile-date"><i class="fas fa-calendar-alt me-2"></i>Joined <?php echo date('F j, Y', strtotime($created_at)); ?></div>
        </div>
        <div class="divider"></div>
        <div class="profile-section-title">Edit Profile</div>
        <div class="edit-section">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2 fa-lg text-success"></i>
                    <span><?= htmlspecialchars($success_message) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2 fa-lg text-danger"></i>
                    <span><?= htmlspecialchars($error_message) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <form method="post" autocomplete="off" enctype="multipart/form-data">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required minlength="2" maxlength="100" placeholder="Full Name">
                    <label for="name">Full Name</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($email) ?>" disabled placeholder="Email Address">
                    <label for="email">Email Address</label>
                </div>
                <div class="profile-section-title mb-2">Change Password</div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" placeholder="Current Password">
                    <label for="current_password">Current Password</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" placeholder="New Password">
                    <label for="new_password">New Password</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" placeholder="Confirm New Password">
                    <label for="confirm_password">Confirm New Password</label>
                </div>
                <div class="mb-4 text-center">
                    <label for="profile_photo" class="form-label fw-bold" style="color:#6366f1;cursor:pointer;">
                        <i class="fas fa-camera fa-lg me-2"></i>Change Profile Photo
                    </label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png" style="display:none;" onchange="previewProfilePhoto(event)">
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
                <div class="text-center">
                    <a href="index.php" class="text-decoration-none fw-bold">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function previewProfilePhoto(event) {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatar = document.getElementById('avatarPreview');
                avatar.innerHTML = '<img src="' + e.target.result + '" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html> 