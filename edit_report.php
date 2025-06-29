<?php
require 'includes/security.php';
session_start();
require 'includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=invalid_report');
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header('Location: index.php?error=not_found');
    exit();
}

$report = $result->fetch_assoc();
$success_message = '';
$error_message = '';

if (!((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $report['reported_by']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1))) {
    header('Location: view.php?id=' . $id . '&error=unauthorized');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $type = $_POST['type'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $contact_info = trim($_POST['contact_info'] ?? '');
    $photo_path = $report['photo'];

    // Handle file upload if provided
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
        $stmt = $conn->prepare("UPDATE reports SET title=?, type=?, description=?, location=?, contact_info=?, photo=? WHERE id=?");
        $stmt->bind_param("ssssssi", $title, $type, $description, $location, $contact_info, $photo_path, $id);
        if ($stmt->execute()) {
            header("Location: view.php?id=$id&updated=1");
            exit();
        } else {
            $error_message = "Failed to update report. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - Missing Items Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .edit-report-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            box-shadow: 0 8px 40px rgba(99,102,241,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            margin: 3rem auto 2rem auto;
            max-width: 600px;
        }
        .edit-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .edit-header h2 {
            font-weight: 800;
            font-size: 2rem;
            color: #6366f1;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.18);
        }
        .file-upload {
            border: 2px dashed rgba(0, 0, 0, 0.13);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8fafc;
        }
        .file-upload:hover {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }
        .preview-image {
            max-width: 180px;
            max-height: 180px;
            border-radius: 10px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="edit-report-card">
        <div class="edit-header">
            <h2><i class="fas fa-edit me-2"></i>Edit Report</h2>
            <p class="text-muted mb-0">Update the details of your report below.</p>
        </div>
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
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label fw-bold">Item Title *</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($report['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Item Type *</label>
                <select name="type" class="form-select" required>
                    <option value="lost" <?= $report['type'] === 'lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="found" <?= $report['type'] === 'found' ? 'selected' : '' ?>>Found</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label fw-bold">Location *</label>
                <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($report['location']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label fw-bold">Description *</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($report['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="contact_info" class="form-label fw-bold">Contact Information</label>
                <input type="text" class="form-control" id="contact_info" name="contact_info" value="<?= htmlspecialchars($report['contact_info']) ?>">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Item Photo (Optional)</label>
                <div class="file-upload" onclick="document.getElementById('photo').click()">
                    <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <h6>Upload a New Photo</h6>
                    <p class="text-muted mb-0">Click to select or drag and drop an image</p>
                    <small class="text-muted">Supports: JPEG, PNG, GIF (Max 5MB)</small>
                </div>
                <?php if (!empty($report['photo']) && file_exists($report['photo'])): ?>
                    <div id="preview-container">
                        <img id="preview-image" class="preview-image" src="<?= htmlspecialchars($report['photo']) ?>" alt="Preview">
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="view.php?id=<?= $report['id'] ?>" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload preview
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('preview-image');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'preview-image';
                        preview.className = 'preview-image';
                        document.getElementById('preview-container').appendChild(preview);
                    }
                    preview.src = e.target.result;
                    document.getElementById('preview-container').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 