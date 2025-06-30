<?php
require 'includes/security.php';
session_start();
require 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=login_required');
    exit();
}

$success_message = '';
$error_message = '';

// Fetch profile photo for navbar
$profile_photo = null;
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['profile_photo'])) {
        $profile_photo = $_SESSION['profile_photo'];
    } else {
        $stmt = $conn->prepare('SELECT profile_photo FROM users WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($profile_photo);
        $stmt->fetch();
        $stmt->close();
        $_SESSION['profile_photo'] = $profile_photo;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $contact_info = trim($_POST['contact_info'] ?? '');
    $age = $_POST['age'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $last_seen = $_POST['last_seen'] ?? null;
    $user_id = $_SESSION['user_id'];
    $category = 'person'; // Fixed category for persons

    // Validation
    if (empty($title) || empty($description) || empty($location)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!in_array($type, ['lost', 'found'])) {
        $error_message = "Invalid report type.";
    } else {
        // Handle file upload if provided
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= $max_size) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $file_extension;
                $photo_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    // File uploaded successfully
                } else {
                    $error_message = "Failed to upload photo.";
                }
            } else {
                $error_message = "Invalid photo format or size. Please use JPEG, PNG, or GIF under 5MB.";
            }
        }

        if (empty($error_message)) {
            $stmt = $conn->prepare("INSERT INTO reports (title, type, category, description, location, contact_info, photo, reported_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $title, $type, $category, $description, $location, $contact_info, $photo_path, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Person report submitted successfully! This person has been added to our database.";
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Failed to submit report. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fef2f2 0%, #dc2626 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .report-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-control, .form-select, .form-textarea {
            border-radius: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
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
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4);
        }
        
        .type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .type-option {
            flex: 1;
            padding: 1.5rem;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .type-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .type-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(239, 68, 68, 0.1));
            transform: translateY(-5px);
        }
        
        .type-option input[type="radio"] {
            display: none;
        }
        
        .type-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .file-upload {
            border: 2px dashed rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload:hover {
            border-color: var(--primary-color);
            background: rgba(220, 38, 38, 0.05);
        }
        
        .file-upload.dragover {
            border-color: var(--primary-color);
            background: rgba(220, 38, 38, 0.1);
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .urgency-badge {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .custom-navbar {
            background: linear-gradient(90deg, #f8fafc 60%, #fbbf24 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px #fbbf2440, 0 1.5px 0 #fbbf24;
            margin-top: 1.2rem;
        }
        .nav-pill-btn {
            border-radius: 999px !important;
            font-size: 1.08rem;
            box-shadow: 0 2px 12px #6366f133;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .nav-pill-btn:hover, .nav-pill-btn:focus {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 32px #6366f144;
            filter: brightness(1.08);
        }
        @media (max-width: 900px) {
            .custom-navbar .gap-3 { gap: 0.5rem !important; }
            .custom-navbar .navbar-brand { font-size: 1.2rem !important; }
            .nav-pill-btn { font-size: 0.98rem !important; padding: 0.5rem 1.2rem !important; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-search-location text-primary me-2"></i>
                Inventra
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="report_item.php">
                        <i class="fas fa-box text-primary me-1"></i>Report Item
                    </a>
                    <a class="nav-link" href="report_person.php">
                        <i class="fas fa-user text-danger me-1"></i>Report Person
                    </a>
                    <a class="nav-link" href="report_pet.php">
                        <i class="fas fa-paw text-warning me-1"></i>Report Pet
                    </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center me-2" style="width: 36px; height: 36px; font-size: 1.2rem; font-weight: 600; overflow: hidden;">
                                <?php if (!empty($profile_photo)): ?>
                                    <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;object-position:center;border-radius:50%;">
                                <?php else: ?>
                                    <?php echo strtoupper(substr(trim($_SESSION['name']), 0, 1)); ?>
                                <?php endif; ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="edit_profile.php"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                            <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="nav-link" href="register.php">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="report-card p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="urgency-badge d-inline-block mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            URGENT - Missing Person Report
                        </div>
                        <h2 class="fw-bold mb-3">
                            <i class="fas fa-user text-danger me-2"></i>
                            Report Missing Person
                        </h2>
                        <p class="text-muted">Help find missing persons or report someone you've found</p>
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

                    <!-- Report Form -->
                    <form method="post" enctype="multipart/form-data" id="reportForm">
                        <!-- Type Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Report Type *</label>
                            <div class="type-selector">
                                <div class="type-option" onclick="selectType('lost')">
                                    <input type="radio" name="type" value="lost" id="type-lost" required>
                                    <div class="type-icon text-danger">
                                        <i class="fas fa-user-times"></i>
                                    </div>
                                    <h5 class="mb-2">Missing Person</h5>
                                    <p class="text-muted mb-0">Someone is missing</p>
                                </div>
                                <div class="type-option" onclick="selectType('found')">
                                    <input type="radio" name="type" value="found" id="type-found" required>
                                    <div class="type-icon text-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <h5 class="mb-2">Found Person</h5>
                                    <p class="text-muted mb-0">I found someone</p>
                                </div>
                            </div>
                        </div>

                        <!-- Person Details -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-bold">Person's Name *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="Full name of the person" 
                                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="location" class="form-label fw-bold">Last Known Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="e.g., Downtown Mall, Central Park" 
                                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Person Information -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="age" class="form-label fw-bold">Age</label>
                                    <input type="number" class="form-control" id="age" name="age" 
                                           placeholder="Age" min="0" max="120" 
                                           value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="gender" class="form-label fw-bold">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select gender</option>
                                        <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= ($_POST['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="last_seen" class="form-label fw-bold">Last Seen</label>
                                    <input type="datetime-local" class="form-control" id="last_seen" name="last_seen" 
                                           value="<?= htmlspecialchars($_POST['last_seen'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Physical Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Detailed physical description including height, weight, hair color, eye color, clothing, distinctive features, tattoos, scars, etc." 
                                      required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="contact_info" class="form-label fw-bold">Contact Information</label>
                            <input type="text" class="form-control" id="contact_info" name="contact_info" 
                                   placeholder="Phone number, email, or preferred contact method" 
                                   value="<?= htmlspecialchars($_POST['contact_info'] ?? '') ?>">
                            <div class="form-text">This will be shown to help people contact you about the person</div>
                        </div>

                        <!-- Photo Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Person's Photo (Optional but Recommended)</label>
                            <div class="file-upload" onclick="document.getElementById('photo').click()">
                                <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-3"></i>
                                <h5>Upload a Photo</h5>
                                <p class="text-muted mb-0">Click to select or drag and drop an image</p>
                                <small class="text-muted">Supports: JPEG, PNG, GIF (Max 5MB)</small>
                            </div>
                            <div id="preview-container" style="display: none;">
                                <img id="preview-image" class="preview-image" src="" alt="Preview">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removePhoto()">
                                    <i class="fas fa-times me-1"></i>Remove Photo
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                Submit Report
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Home
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectType(type) {
            // Remove selected class from all options
            document.querySelectorAll('.type-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('type-' + type).checked = true;
        }

        // File upload preview
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('preview-container').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function removePhoto() {
            document.getElementById('photo').value = '';
            document.getElementById('preview-container').style.display = 'none';
        }

        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('photo').files = files;
                const event = new Event('change');
                document.getElementById('photo').dispatchEvent(event);
            }
        });

        // Form validation
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const type = document.querySelector('input[name="type"]:checked');
            if (!type) {
                e.preventDefault();
                alert('Please select a report type (Missing Person or Found Person)');
                return false;
            }
        });
    </script>
</body>
</html>
