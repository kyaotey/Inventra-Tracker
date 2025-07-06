<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require "includes/security.php";
session_start();
require "includes/db.php";

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$error_message = "";
$success_message = "";

// Get current user data first
try {
    $stmt = $conn->prepare("SELECT name, email, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} catch (Exception $e) {
    $error_message = "Failed to load user data.";
    $user = ['name' => '', 'email' => '', 'profile_photo' => ''];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed. Please try again.";
        logSecurityEvent('CSRF token validation failed', 'Profile edit attempt');
    } else {
        $name = sanitizeInput($_POST["name"]);
        $email = sanitizeInput($_POST["email"]);
        $current_password = $_POST["current_password"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];
        
        // Handle profile photo upload
        $profile_photo_path = $user['profile_photo']; // Keep existing photo by default
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadProfilePhoto($_FILES['profile_photo']);
            if ($uploadResult['success']) {
                $profile_photo_path = $uploadResult['file_path'];
            } else {
                $error_message = $uploadResult['error'];
            }
        }

        // Basic validation
        if (empty($name) || empty($email)) {
            $error_message = "Name and email are required.";
        } elseif (strlen($name) < 2 || strlen($name) > 100) {
            $error_message = "Name must be between 2 and 100 characters long.";
        } elseif (!validateEmail($email)) {
            $error_message = "Please enter a valid email address.";
        } else {
            try {
                // Check if email is already taken by another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $_SESSION["user_id"]);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "An account with this email already exists.";
                } else {
                    // Update basic info
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_photo = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $profile_photo_path, $_SESSION["user_id"]);
                    
                    if ($stmt->execute()) {
                        // Update session data
                        $_SESSION["name"] = $name;
                        $_SESSION["email"] = $email;
                        $_SESSION["profile_photo"] = $profile_photo_path;
                        
                        // Handle password change if provided
                        $password_updated = false;
                        if (!empty($current_password) && !empty($new_password)) {
                            // Verify current password
                            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                            $stmt->bind_param("i", $_SESSION["user_id"]);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $user = $result->fetch_assoc();
                            
                            if (password_verify($current_password, $user['password'])) {
                                if (!validatePassword($new_password)) {
                                    $error_message = "New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.";
                                } elseif ($new_password !== $confirm_password) {
                                    $error_message = "New passwords do not match.";
                                } else {
                                    // Update password
                                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                                    $stmt->bind_param("si", $hashed_password, $_SESSION["user_id"]);
                                    
                                    if ($stmt->execute()) {
                                        logSecurityEvent('Password changed', "User ID: " . $_SESSION["user_id"]);
                                        $password_updated = true;
                                    } else {
                                        $error_message = "Failed to update password. Please try again.";
                                    }
                                }
                            } else {
                                $error_message = "Current password is incorrect.";
                            }
                        }
                        
                        // Set success message and redirect after successful profile update
                        if (empty($error_message)) {
                            // Debug: Log the redirect attempt
                            error_log("Profile update successful, redirecting to index.php");
                            
                            // Create specific success message based on what was updated
                            $success_message = "Profile information updated successfully!";
                            if ($password_updated) {
                                $success_message = "Profile and password updated successfully!";
                            } elseif (!empty($_FILES['profile_photo']['name'])) {
                                $success_message = "Profile and photo updated successfully!";
                            }
                            
                            $_SESSION['profile_update_success'] = $success_message;
                            header("Location: index.php");
                            exit;
                        } else {
                            // Debug: Log any error messages
                            error_log("Profile update error: " . $error_message);
                        }
                    } else {
                        $error_message = "Failed to update profile. Please try again.";
                    }
                }
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $error_message = "Database error. Please try again later.";
            }
        }
    }
    
    // Fallback redirect if we reach here without errors
    if (empty($error_message)) {
        $_SESSION['profile_update_success'] = "Profile updated successfully!";
        header("Location: index.php");
        exit;
    }
}



// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Missing Items Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: "Inter", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-right: none;
            border-radius: 12px 0 0 12px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .password-section {
            background: rgba(99, 102, 241, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="profile-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary mb-2">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </h2>
                        <p class="text-muted">Update your account information</p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="profileForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="Enter your full name" 
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email address" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Profile Photo Section -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-camera me-2"></i>Profile Photo
                            </label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="profile-photo-preview">
                                    <?php if (!empty($user['profile_photo'])): ?>
                                        <img src="<?= htmlspecialchars($user['profile_photo']) ?>" 
                                             alt="Current Profile Photo" 
                                             class="rounded-circle border" 
                                             style="width: 80px; height: 80px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle border d-flex align-items-center justify-content-center bg-light" 
                                             style="width: 80px; height: 80px;">
                                            <i class="fas fa-user text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                                           accept="image/jpeg,image/jpg,image/png,image/gif">
                                    <div class="form-text">
                                        Upload a JPG, PNG, or GIF image (max 5MB). Leave empty to keep current photo.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Change Section -->
                        <div class="password-section">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </h5>
                            <p class="text-muted small mb-3">Leave blank if you don't want to change your password</p>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label fw-bold">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="current_password" name="current_password" 
                                           placeholder="Enter your current password">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-bold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           placeholder="Enter new password">
                                </div>
                                <div class="form-text">
                                    Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label fw-bold">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="index.php" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile photo preview (always clear previous content)
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.querySelector('.profile-photo-preview');
            preview.innerHTML = ""; // Always clear previous content

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.className = 'rounded-circle border';
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            } else {
                // Show icon if no file selected
                const icon = document.createElement('i');
                icon.className = 'fas fa-user text-muted';
                icon.style.fontSize = '2rem';
                preview.appendChild(icon);
            }
        });
    </script>
</body>
</html>