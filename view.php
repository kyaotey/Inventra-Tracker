<?php
require 'includes/security.php';
session_start();
require 'includes/db.php'; 

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
        
        .detail-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .info-item {
            padding: 1rem;
            border-radius: 12px;
            background: rgba(99, 102, 241, 0.05);
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            color: #6b7280;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        .item-image {
            border-radius: 15px;
            max-width: 100%;
            height: auto;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .contact-info {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--primary-color);
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
        <?php
        if (!isset($_GET['id'])) {
            echo "
            <div class='row justify-content-center'>
                <div class='col-lg-8'>
                    <div class='detail-card p-5 text-center'>
                        <i class='fas fa-exclamation-triangle fa-3x text-warning mb-3'></i>
                        <h3>Invalid Item ID</h3>
                        <p class='text-muted'>The item you're looking for doesn't exist or has been removed.</p>
                        <a href='index.php' class='btn btn-primary'>
                            <i class='fas fa-arrow-left me-2'></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
            ";
            exit;
        }

        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT r.*, u.name as reporter_name, u.email as reporter_email 
                               FROM reports r 
                               LEFT JOIN users u ON r.reported_by = u.id 
                               WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $report = $result->fetch_assoc();
            $status_color = $report['status'] === 'returned' ? 'success' : 'warning';
            $type_color = $report['type'] === 'lost' ? 'danger' : 'info';
            $type_icon = $report['type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-hand-holding-heart';
            $status_icon = $report['status'] === 'returned' ? 'fa-check-circle' : 'fa-clock';
            
            // Category-specific icons and colors
            $category_icon = 'fa-box';
            $category_color = 'primary';
            $category_text = 'Item';
            
            if ($report['category'] === 'person') {
                $category_icon = 'fa-user';
                $category_color = 'danger';
                $category_text = 'Person';
            } elseif ($report['category'] === 'pet') {
                $category_icon = 'fa-paw';
                $category_color = 'warning';
                $category_text = 'Pet';
            }
            ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="detail-card p-5">
                        <!-- Header -->
                        <div class="row align-items-center mb-4">
                            <div class="col-md-8">
                                <h1 class="fw-bold mb-2"><?= htmlspecialchars($report['title']) ?></h1>
                                <div class="d-flex gap-2 mb-3">
                                    <span class="status-badge bg-<?= $category_color ?> text-white">
                                        <i class="fas <?= $category_icon ?> me-1"></i><?= $category_text ?>
                                    </span>
                                    <span class="status-badge bg-<?= $type_color ?> text-white">
                                        <i class="fas <?= $type_icon ?> me-1"></i><?= ucfirst($report['type']) ?>
                                    </span>
                                    <span class="status-badge bg-<?= $status_color ?> text-white">
                                        <i class="fas <?= $status_icon ?> me-1"></i><?= ucfirst($report['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to List
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Main Content -->
                            <div class="col-lg-8">
                                <!-- Item Image -->
                                <?php if (!empty($report['photo'])): ?>
                                    <div class="mb-4">
                                        <img src="<?= htmlspecialchars($report['photo']) ?>" 
                                             alt="<?= $category_text ?> Photo" class="item-image w-100">
                                    </div>
                                <?php endif; ?>

                                <!-- Description -->
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-info-circle me-2"></i><?= $category_text ?> Description
                                    </div>
                                    <div class="info-value">
                                        <?= nl2br(htmlspecialchars($report['description'])) ?>
                                    </div>
                                </div>

                                <!-- Location -->
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Location
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($report['location']) ?>
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div class="timeline mt-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-history me-2"></i><?= $category_text ?> Timeline
                                    </h5>
                                    
                                    <div class="timeline-item">
                                        <div class="fw-bold">Reported</div>
                                        <div class="text-muted">
                                            <?= date('F j, Y \a\t g:i A', strtotime($report['created_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($report['status'] === 'returned'): ?>
                                        <div class="timeline-item">
                                            <div class="fw-bold text-success"><?= $category_text ?> Returned</div>
                                            <div class="text-muted">Successfully reunited with owner</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="timeline-item">
                                            <div class="fw-bold text-warning">Currently Pending</div>
                                            <div class="text-muted">Waiting to be found or claimed</div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Comments Section -->
                                <div class="mt-5">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-comments me-2"></i>Comments
                                    </h5>
                                    <?php
                                    // Handle new comment submission
                                    if (isset($_POST['add_comment']) && isset($_SESSION['user_id']) && !empty(trim($_POST['comment']))) {
                                        $comment = trim($_POST['comment']);
                                        $user_id = $_SESSION['user_id'];
                                        $stmt = $conn->prepare("INSERT INTO comments (report_id, user_id, comment) VALUES (?, ?, ?)");
                                        $stmt->bind_param("iis", $id, $user_id, $comment);
                                        $stmt->execute();
                                    }
                                    // Handle comment deletion (admin only)
                                    if (isset($_POST['delete_comment']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
                                        $del_id = intval($_POST['delete_comment']);
                                        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
                                        $stmt->bind_param("i", $del_id);
                                        $stmt->execute();
                                    }
                                    // Fetch comments
                                    $stmt = $conn->prepare("SELECT c.*, u.name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.report_id = ? ORDER BY c.created_at DESC");
                                    $stmt->bind_param("i", $id);
                                    $stmt->execute();
                                    $comments = $stmt->get_result();
                                    ?>
                                    <div class="mb-3">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                        <form method="post" class="d-flex align-items-start gap-2">
                                            <textarea name="comment" class="form-control" rows="2" placeholder="Add a comment..." required></textarea>
                                            <button type="submit" name="add_comment" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                                        </form>
                                        <?php else: ?>
                                        <div class="alert alert-info py-2">Please <a href="login.php">login</a> to comment.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($comments->num_rows > 0): ?>
                                            <?php while ($c = $comments->fetch_assoc()): ?>
                                                <div class="border rounded-3 p-3 mb-2 bg-light position-relative">
                                                    <div class="fw-bold mb-1"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($c['name']) ?> <span class="text-muted small ms-2"><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?></span></div>
                                                    <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                                        <form method="post" class="position-absolute top-0 end-0 m-2">
                                                            <input type="hidden" name="delete_comment" value="<?= $c['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <div class="text-muted">No comments yet. Be the first to comment!</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="col-lg-4">
                                <!-- Reporter Information -->
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-user me-2"></i>Reported By
                                    </div>
                                    <div class="info-value">
                                        <?= htmlspecialchars($report['reporter_name']) ?>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <?php if (!empty($report['contact_info'])): ?>
                                    <div class="contact-info mb-4">
                                        <h6 class="fw-bold mb-3">
                                            <i class="fas fa-phone me-2"></i>Contact Information
                                        </h6>
                                        <p class="mb-0"><?= htmlspecialchars($report['contact_info']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="d-grid gap-2">
                                    <?php if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] == $report['reported_by']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1)): ?>
                                    <a href="edit_report.php?id=<?= $report['id'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-2"></i>Edit Report
                                    </a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                        <a href="admin/dashboard.php" class="btn btn-outline-warning">
                                            <i class="fas fa-shield-alt me-2"></i>Admin Panel
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-info" onclick="shareReport()">
                                        <i class="fas fa-share me-2"></i>Share Report
                                    </button>
                                </div>

                                <!-- Similar Items -->
                                <div class="mt-4">
                                    <h6 class="fw-bold mb-3">
                                        <i class="fas fa-search me-2"></i>Similar Items
                                    </h6>
                                    <?php
                                    $similar_query = "SELECT id, title, type, status FROM reports 
                                                    WHERE type = ? AND id != ? AND status = 'pending' 
                                                    ORDER BY created_at DESC LIMIT 3";
                                    $stmt = $conn->prepare($similar_query);
                                    $stmt->bind_param("si", $report['type'], $id);
                                    $stmt->execute();
                                    $similar_result = $stmt->get_result();
                                    
                                    if ($similar_result->num_rows > 0):
                                        while ($similar = $similar_result->fetch_assoc()):
                                            $similar_type_color = $similar['type'] === 'lost' ? 'danger' : 'info';
                                    ?>
                                        <div class="card mb-2">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1">
                                                    <a href="view.php?id=<?= $similar['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($similar['title']) ?>
                                                    </a>
                                                </h6>
                                                <span class="badge bg-<?= $similar_type_color ?>">
                                                    <?= ucfirst($similar['type']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php 
                                        endwhile;
                                    else:
                                        echo "<p class='text-muted small'>No similar items found.</p>";
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            echo "
            <div class='row justify-content-center'>
                <div class='col-lg-8'>
                    <div class='detail-card p-5 text-center'>
                        <i class='fas fa-search fa-3x text-muted mb-3'></i>
                        <h3>Item Not Found</h3>
                        <p class='text-muted'>The item you're looking for doesn't exist or has been removed.</p>
                        <a href='index.php' class='btn btn-primary'>
                            <i class='fas fa-arrow-left me-2'></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
            ";
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function shareReport() {
            if (navigator.share) {
                navigator.share({
                    title: 'Missing Items Tracker - <?= htmlspecialchars($report['title'] ?? 'Item') ?>',
                    text: 'Check out this <?= $report['type'] ?? 'item' ?> report',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(function() {
                    alert('Link copied to clipboard!');
                });
            }
        }

        function editReport() {
            // This would redirect to an edit page (to be implemented)
            alert('Edit functionality coming soon!');
        }
    </script>
</body>
</html>
