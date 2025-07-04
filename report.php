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
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $contact_info = trim($_POST['contact_info'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Handle category-specific description
    if ($category === 'person') {
        $description = trim($_POST['person_description']);
    } elseif ($category === 'pet') {
        $description = trim($_POST['pet_description']);
    }

    // Validation
    if (empty($title) || empty($description) || empty($location)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!in_array($type, ['lost', 'found'])) {
        $error_message = "Invalid item type.";
    } elseif (!in_array($category, ['item', 'person', 'pet'])) {
        $error_message = "Invalid category.";
    } else {
        // Handle multiple media uploads
        $uploadedMedia = [];
        $photo_path = null; // Keep for backward compatibility
        
        if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
            $uploadResult = uploadMediaFiles($_FILES['media']);
            
            if ($uploadResult['success']) {
                $uploadedMedia = $uploadResult['files'];
                // Set primary photo for backward compatibility
                if (!empty($uploadedMedia)) {
                    $photo_path = $uploadedMedia[0]['file_path'];
                }
                
                if (!empty($uploadResult['errors'])) {
                    $error_message = "Some files failed to upload: " . implode(', ', $uploadResult['errors']);
                }
            } else {
                $error_message = $uploadResult['error'];
            }
        }

        if (empty($error_message)) {
            // Insert report
            $stmt = $conn->prepare("INSERT INTO reports (title, type, category, description, location, contact_info, photo, reported_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $title, $type, $category, $description, $location, $contact_info, $photo_path, $user_id);
            
            if ($stmt->execute()) {
                $reportId = $conn->insert_id;
                
                // Insert media files
                if (!empty($uploadedMedia)) {
                    $mediaStmt = $conn->prepare("INSERT INTO report_media (report_id, file_path, file_type, file_name, file_size, mime_type, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    foreach ($uploadedMedia as $index => $media) {
                        $isPrimary = $index === 0 ? 1 : 0;
                        $mediaStmt->bind_param("isssssi", $reportId, $media['file_path'], $media['file_type'], $media['file_name'], $media['file_size'], $media['mime_type'], $isPrimary);
                        $mediaStmt->execute();
                    }
                }
                
                $success_message = "Report submitted successfully! Your report has been added to our database.";
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
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .type-selector, .category-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .type-option, .category-option {
            flex: 1;
            padding: 1.5rem;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .type-option:hover, .category-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .type-option.selected, .category-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            transform: translateY(-5px);
        }
        
        .type-option input[type="radio"], .category-option input[type="radio"] {
            display: none;
        }
        
        .type-icon, .category-icon {
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
            background: rgba(99, 102, 241, 0.05);
        }
        
        .file-upload.dragover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .category-fields {
            display: none;
        }
        
        .category-fields.active {
            display: block;
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
                        <h2 class="fw-bold mb-3">
                            <i class="fas fa-plus-circle text-primary me-2"></i>
                            Report Missing Item/Person/Pet
                        </h2>
                        <p class="text-muted">Help others find their lost items, persons, or pets or report what you've found</p>
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
                        <!-- Category Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Category *</label>
                            <div class="category-selector">
                                <div class="category-option" onclick="selectCategory('item')">
                                    <input type="radio" name="category" value="item" id="category-item" required>
                                    <div class="category-icon text-primary">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <h5 class="mb-2">Item</h5>
                                    <p class="text-muted mb-0">Lost or found item</p>
                                </div>
                                <div class="category-option" onclick="selectCategory('person')">
                                    <input type="radio" name="category" value="person" id="category-person" required>
                                    <div class="category-icon text-danger">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h5 class="mb-2">Person</h5>
                                    <p class="text-muted mb-0">Missing person</p>
                                </div>
                                <div class="category-option" onclick="selectCategory('pet')">
                                    <input type="radio" name="category" value="pet" id="category-pet" required>
                                    <div class="category-icon text-warning">
                                        <i class="fas fa-paw"></i>
                                    </div>
                                    <h5 class="mb-2">Pet</h5>
                                    <p class="text-muted mb-0">Lost or found pet</p>
                                </div>
                            </div>
                        </div>

                        <!-- Type Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Type *</label>
                            <div class="type-selector">
                                <div class="type-option" onclick="selectType('lost')">
                                    <input type="radio" name="type" value="lost" id="type-lost" required>
                                    <div class="type-icon text-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <h5 class="mb-2">Lost</h5>
                                    <p class="text-muted mb-0">I lost something/someone</p>
                                </div>
                                <div class="type-option" onclick="selectType('found')">
                                    <input type="radio" name="type" value="found" id="type-found" required>
                                    <div class="type-icon text-info">
                                        <i class="fas fa-hand-holding-heart"></i>
                                    </div>
                                    <h5 class="mb-2">Found</h5>
                                    <p class="text-muted mb-0">I found something/someone</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item Details -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-bold">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="Enter title..." 
                                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="location" class="form-label fw-bold">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="e.g., Library, Park, Mall" 
                                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Category-specific fields -->
                        <div id="item-fields" class="category-fields active">
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Item Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Provide detailed description of the item (color, brand, size, distinctive features, etc.)" 
                                          required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div id="person-fields" class="category-fields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="person-age" class="form-label fw-bold">Age</label>
                                        <input type="number" class="form-control" id="person-age" name="person_age" 
                                               placeholder="Age" min="0" max="120">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="person-gender" class="form-label fw-bold">Gender</label>
                                        <select class="form-select" id="person-gender" name="person_gender">
                                            <option value="">Select gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="person-description" class="form-label fw-bold">Person Description *</label>
                                <textarea class="form-control" id="person-description" name="person_description" rows="4" 
                                          placeholder="Physical description, clothing, height, hair color, eye color, distinctive features, etc." 
                                          required><?= htmlspecialchars($_POST['person_description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="person-last-seen" class="form-label fw-bold">Last Seen</label>
                                <input type="datetime-local" class="form-control" id="person-last-seen" name="person_last_seen">
                            </div>
                        </div>

                        <div id="pet-fields" class="category-fields">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pet-type" class="form-label fw-bold">Pet Type</label>
                                        <select class="form-select" id="pet-type" name="pet_type">
                                            <option value="">Select pet type</option>
                                            <option value="dog">Dog</option>
                                            <option value="cat">Cat</option>
                                            <option value="bird">Bird</option>
                                            <option value="rabbit">Rabbit</option>
                                            <option value="hamster">Hamster</option>
                                            <option value="fish">Fish</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pet-breed" class="form-label fw-bold">Breed</label>
                                        <input type="text" class="form-control" id="pet-breed" name="pet_breed" 
                                               placeholder="e.g., Golden Retriever, Persian Cat">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pet-color" class="form-label fw-bold">Color</label>
                                        <input type="text" class="form-control" id="pet-color" name="pet_color" 
                                               placeholder="e.g., Brown, White, Black">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pet-size" class="form-label fw-bold">Size</label>
                                        <select class="form-select" id="pet-size" name="pet_size">
                                            <option value="">Select size</option>
                                            <option value="small">Small</option>
                                            <option value="medium">Medium</option>
                                            <option value="large">Large</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="pet-description" class="form-label fw-bold">Pet Description *</label>
                                <textarea class="form-control" id="pet-description" name="pet_description" rows="4" 
                                          placeholder="Detailed description including distinctive features, markings, collar details, microchip info, etc." 
                                          required><?= htmlspecialchars($_POST['pet_description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="pet-last-seen" class="form-label fw-bold">Last Seen</label>
                                <input type="datetime-local" class="form-control" id="pet-last-seen" name="pet_last_seen">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="contact_info" class="form-label fw-bold">Contact Information</label>
                            <input type="text" class="form-control" id="contact_info" name="contact_info" 
                                   placeholder="Phone number, email, or preferred contact method" 
                                   value="<?= htmlspecialchars($_POST['contact_info'] ?? '') ?>">
                            <div class="form-text">This will be shown to help people contact you</div>
                        </div>

                        <!-- Media Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Photos & Videos (Optional)</label>
                            <div class="file-upload" onclick="document.getElementById('media').click()">
                                <input type="file" id="media" name="media[]" accept="image/*,video/*" multiple style="display: none;">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-3"></i>
                                <h5>Upload Photos & Videos</h5>
                                <p class="text-muted mb-0">Click to select or drag and drop multiple files</p>
                                <small class="text-muted">Supports: JPEG, PNG, GIF, WebP, MP4, AVI, MOV, WMV, FLV, WebM, MKV (Max 10MB each)</small>
                            </div>
                            <div id="media-preview-container" class="mt-3">
                                <!-- Media previews will be displayed here -->
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
        function selectCategory(category) {
            // Remove selected class from all options
            document.querySelectorAll('.category-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById('category-' + category).checked = true;
            
            // Show/hide category-specific fields
            document.querySelectorAll('.category-fields').forEach(field => {
                field.classList.remove('active');
            });
            document.getElementById(category + '-fields').classList.add('active');
            
            // Update description field requirements
            updateDescriptionField();
        }

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

        function updateDescriptionField() {
            const category = document.querySelector('input[name="category"]:checked')?.value;
            const descriptionField = document.getElementById('description');
            const personDescriptionField = document.getElementById('person-description');
            const petDescriptionField = document.getElementById('pet-description');
            
            if (category === 'item') {
                descriptionField.required = true;
                personDescriptionField.required = false;
                petDescriptionField.required = false;
            } else if (category === 'person') {
                descriptionField.required = false;
                personDescriptionField.required = true;
                petDescriptionField.required = false;
            } else if (category === 'pet') {
                descriptionField.required = false;
                personDescriptionField.required = false;
                petDescriptionField.required = true;
            }
        }

        // Multiple media upload preview
        document.getElementById('media').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            const container = document.getElementById('media-preview-container');
            container.innerHTML = '';
            
            files.forEach((file, index) => {
                const reader = new FileReader();
                const previewDiv = document.createElement('div');
                previewDiv.className = 'media-preview-item d-inline-block me-3 mb-3 position-relative';
                previewDiv.style.maxWidth = '150px';
                
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Preview" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0" 
                                    onclick="removeMediaFile(${index})" style="transform: translate(50%, -50%);">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="mt-1">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        `;
                    } else if (file.type.startsWith('video/')) {
                        previewDiv.innerHTML = `
                            <video src="${e.target.result}" class="img-fluid rounded" style="max-height: 120px; object-fit: cover;" controls></video>
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0" 
                                    onclick="removeMediaFile(${index})" style="transform: translate(50%, -50%);">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="mt-1">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        `;
                    }
                };
                
                reader.readAsDataURL(file);
                container.appendChild(previewDiv);
            });
        });

        function removeMediaFile(index) {
            const input = document.getElementById('media');
            const dt = new DataTransfer();
            const files = Array.from(input.files);
            
            files.forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            
            input.files = dt.files;
            
            // Trigger change event to update preview
            const event = new Event('change');
            input.dispatchEvent(event);
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
                const input = document.getElementById('media');
                const dt = new DataTransfer();
                
                // Add existing files
                Array.from(input.files).forEach(file => dt.items.add(file));
                
                // Add new files
                Array.from(files).forEach(file => dt.items.add(file));
                
                input.files = dt.files;
                const event = new Event('change');
                input.dispatchEvent(event);
            }
        });

        // Form validation
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const category = document.querySelector('input[name="category"]:checked');
            const type = document.querySelector('input[name="type"]:checked');
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category (Item, Person, or Pet)');
                return false;
            }
            
            if (!type) {
                e.preventDefault();
                alert('Please select a type (Lost or Found)');
                return false;
            }
        });
    </script>
</body>
</html>
