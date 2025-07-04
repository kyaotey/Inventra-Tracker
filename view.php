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

// Fetch report info as early as possible so modals have access
$report = null;
$category_icon = $category_text = $type_icon = $status_icon = null;
$reportMedia = []; // Array to store media files
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT r.*, u.name as reporter_name, u.email as reporter_email FROM reports r LEFT JOIN users u ON r.reported_by = u.id WHERE r.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $report = $result->fetch_assoc();
        $status_color = $report['status'] === 'returned' ? 'success' : 'warning';
        $type_color = $report['type'] === 'lost' ? 'danger' : 'info';
        $type_icon = $report['type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-hand-holding-heart';
        $status_icon = $report['status'] === 'returned' ? 'fa-check-circle' : 'fa-clock';
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
        
        // Fetch media files for this report
        $reportMedia = getReportMedia($id, $conn);
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
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
    </style>
</head>
<body>
    <!-- Modals at the top of the page -->
    <div class="modal fade" id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-scroll="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="descModalLabel"><i class="fas fa-info-circle me-2"></i><?= $category_text ?> Description</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= nl2br(htmlspecialchars($report['description'])) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-scroll="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="locationModalLabel"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= htmlspecialchars($report['location']) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="timelineModalAll" tabindex="-1" aria-labelledby="timelineModalAllLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-scroll="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="timelineModalAllLabel">
              <i class="fas fa-stream text-primary me-2"></i>Timeline Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item py-3">
                <div class="d-flex align-items-center gap-3 mb-1">
                  <span class="badge bg-dark"><i class="fas fa-flag"></i></span>
                  <span class="fw-bold">Reported</span>
                  <span class="text-muted ms-auto small"><i class="fas fa-clock me-1"></i><?= date('F j, Y \a\t g:i A', strtotime($report['created_at'])) ?></span>
                </div>
                <div class="mb-1"><b>Who:</b> <?= htmlspecialchars($report['reporter_name']) ?></div>
                <div><b>Details:</b> Report created</div>
              </li>
              <?php if ($report['status'] !== 'returned'): ?>
              <li class="list-group-item py-3">
                <div class="d-flex align-items-center gap-3 mb-1">
                  <span class="badge bg-dark"><i class="fas fa-hourglass-half"></i></span>
                  <span class="fw-bold">In Progress</span>
                  <span class="text-muted ms-auto small">Active</span>
                </div>
                <div class="mb-1"><b>Who:</b> -</div>
                <div><b>Details:</b> Waiting to be found or claimed</div>
              </li>
              <?php endif; ?>
              <?php if ($report['status'] === 'returned'): ?>
              <li class="list-group-item py-3">
                <div class="d-flex align-items-center gap-3 mb-1">
                  <span class="badge bg-dark"><i class="fas fa-check"></i></span>
                  <span class="fw-bold"><?php if ($report['category'] === 'person') { echo 'Reunited'; } else { echo 'Returned'; } ?></span>
                  <span class="text-muted ms-auto small"><i class="fas fa-clock me-1"></i><?= date('F j, Y \a\t g:i A', strtotime($report['updated_at'] ?? $report['created_at'])) ?></span>
                </div>
                <div class="mb-1"><b>Who:</b> <?= htmlspecialchars($report['reporter_name']) ?></div>
                <div><b>Details:</b> Reunited with family, friends, or caregivers</div>
              </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="reporterModal" tabindex="-1" aria-labelledby="reporterModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-scroll="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="reporterModalLabel"><i class="fas fa-user me-2"></i>Reported By</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= htmlspecialchars($report['reporter_name']) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-scroll="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="contactModalLabel"><i class="fas fa-envelope me-2"></i>Contact Information</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?= htmlspecialchars($report['contact_info'] ?? $report['reporter_email']) ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Media Gallery Modal -->
    <div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaModalLabel">
                        <span id="mediaCounter">1 of 1</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <div class="media-gallery-container position-relative">
                        <div id="mediaContent" class="media-content">
                            <!-- Media content will be loaded here -->
                        </div>
                        
                        <!-- Navigation arrows -->
                        <button class="btn btn-light btn-sm position-absolute top-50 start-0 translate-middle-y ms-2" 
                                id="prevBtn" onclick="changeMedia(-1)" style="z-index: 10;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="btn btn-light btn-sm position-absolute top-50 end-0 translate-middle-y me-2" 
                                id="nextBtn" onclick="changeMedia(1)" style="z-index: 10;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <!-- Thumbnail navigation -->
                        <div class="media-thumbnails mt-3 p-3" id="mediaThumbnails">
                            <!-- Thumbnails will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

    <div class="container py-5">
        <?php
        if (!isset($_GET['id'])) {
            echo "
            <div class='row justify-content-center'>
                <div class='col-lg-8'>
                    <div class='glass-card p-5 text-center'>
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
        $stmt = $conn->prepare("SELECT r.*, u.name as reporter_name, u.email as reporter_email FROM reports r LEFT JOIN users u ON r.reported_by = u.id WHERE r.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $report = $result->fetch_assoc();
            $status_color = $report['status'] === 'returned' ? 'success' : 'warning';
            $type_color = $report['type'] === 'lost' ? 'danger' : 'info';
            $type_icon = $report['type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-hand-holding-heart';
            $status_icon = $report['status'] === 'returned' ? 'fa-check-circle' : 'fa-clock';
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
            
            // Fetch media files for this report
            $reportMedia = getReportMedia($id, $conn);
        ?>
        <style>
        .glass-card {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(18px);
            border-radius: 32px;
            box-shadow: 0 8px 40px rgba(99,102,241,0.13);
            border: 1.5px solid rgba(99,102,241,0.10);
            margin-bottom: 2.5rem;
        }
        .big-title {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 0.5rem;
            color: #22223b;
        }
        .badge-modern {
            border-radius: 999px;
            font-size: 1.02rem;
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            margin-right: 0.3rem;
            background: rgba(99,102,241,0.08);
            color: #6366f1;
            border: 1.5px solid #e0e7ff;
        }
        .badge-modern.filled { background: linear-gradient(90deg, #6366f1, #8b5cf6); color: #fff; border: none; }
        .info-card {
            background: rgba(255,255,255,0.85);
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(99,102,241,0.07);
            padding: 1.5rem 1.5rem 1.2rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1.5px solid #f3f4f6;
        }
        .info-label {
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 0.3rem;
            font-size: 1.08rem;
        }
        .info-value {
            color: #22223b;
            font-size: 1.08rem;
        }
        .horizontal-timeline {
            display: flex;
            align-items: flex-start;
            gap: 2.5rem;
            margin: 2.2rem 0 1.2rem 0;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 160px;
            position: relative;
        }
        .timeline-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1 60%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 2px 8px #6366f122;
            margin-bottom: 0.5rem;
            z-index: 2;
            animation: popIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        .timeline-step.active .timeline-dot {
            background: linear-gradient(135deg, #10b981 60%, #34d399 100%);
        }
        .timeline-label {
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 0.2rem;
            text-align: center;
        }
        .timeline-desc {
            color: #6b7280;
            font-size: 0.98rem;
            text-align: center;
        }
        .timeline-date {
            color: #8b5cf6;
            font-size: 0.93rem;
            margin-top: 0.2rem;
            text-align: center;
        }
        .timeline-connector {
            position: absolute;
            top: 16px;
            left: 100%;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #6366f1 60%, #8b5cf6 100%);
            z-index: 1;
        }
        .timeline-step:last-child .timeline-connector { display: none; }
        @keyframes popIn {
            0% { transform: scale(0.2); opacity: 0; }
            60% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        @media (max-width: 900px) {
            .glass-card { padding: 1.2rem 0.5rem; }
            .big-title { font-size: 1.3rem; }
            .horizontal-timeline { gap: 1.2rem; }
            .timeline-step { min-width: 120px; }
        }
        </style>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="glass-card p-4 p-md-5">
                    <div class="big-title mb-2"><?= htmlspecialchars($report['title']) ?></div>
                    <div class="d-flex flex-wrap justify-content-center mb-4 gap-2">
                        <span class="badge-modern filled"><i class="fas <?= $category_icon ?> me-1"></i><?= $category_text ?></span>
                        <span class="badge-modern"><i class="fas <?= $type_icon ?> me-1"></i><?= ucfirst($report['type']) ?></span>
                        <span class="badge-modern"><i class="fas <?= $status_icon ?> me-1"></i><?= ucfirst($report['status']) ?></span>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-7">
                            <?php if (!empty($reportMedia)): ?>
                                <div class="mb-4">
                                    <h5 class="mb-3"><i class="fas fa-images me-2"></i>Media</h5>
                                    <div class="media-preview">
                                        <?php 
                                        $firstMedia = $reportMedia[0]; // Get the first media file
                                        $totalMedia = count($reportMedia);
                                        ?>
                                        <div class="position-relative" style="cursor: pointer;" onclick="openMediaGallery(0)">
                                            <?php if ($firstMedia['file_type'] === 'image'): ?>
                                                <img src="<?= htmlspecialchars($firstMedia['file_path']) ?>" 
                                                     alt="<?= $category_text ?> Photo" 
                                                     class="img-fluid rounded-4 shadow-sm" 
                                                     style="max-height:320px;object-fit:cover;">
                                            <?php elseif ($firstMedia['file_type'] === 'video'): ?>
                                                <video src="<?= htmlspecialchars($firstMedia['file_path']) ?>" 
                                                       class="img-fluid rounded-4 shadow-sm" 
                                                       style="max-height:320px;object-fit:cover;"
                                                       controls>
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php endif; ?>
                                            
                                            <?php if ($totalMedia > 1): ?>
                                                <div class="media-overlay position-absolute top-0 end-0 m-2">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-images me-1"></i><?= $totalMedia ?> files
                                                    </span>
                                                </div>
                                                <div class="media-overlay position-absolute bottom-0 start-0 m-2">
                                                    <span class="badge bg-dark bg-opacity-75">
                                                        <i class="fas fa-expand me-1"></i>Click to view all
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (!empty($report['photo'])): ?>
                                <!-- Fallback to old single photo display -->
                                <div class="mb-4 text-center">
                                    <img src="<?= htmlspecialchars($report['photo']) ?>" alt="<?= $category_text ?> Photo" class="img-fluid rounded-4 shadow-sm" style="max-height:320px;object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <!-- Map Placeholder Section -->
                            <div class="mb-4">
                                <div class="info-card d-flex flex-column align-items-center justify-content-center" style="min-height:220px; background: #f8f9fa; border: 1px dashed #b0b0b0;">
                                    <div class="info-label mb-2" style="font-size:1.15rem;"><i class="fas fa-map-marked-alt me-2"></i>Map <span class="badge bg-secondary">Coming Soon</span></div>
                                    <div id="map" style="width:100%;max-width:500px;height:180px;background:#e9ecef;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#888;font-size:1.1rem;">
                                        Map will appear here
                                    </div>
                                </div>
                            </div>
                            <div class="info-card mb-3 clickable-info" data-bs-toggle="modal" data-bs-target="#descModal" style="cursor:pointer;">
                                <div class="info-label"><i class="fas fa-info-circle me-2"></i><?= $category_text ?> Description</div>
                                <div class="info-value"><?= nl2br(htmlspecialchars($report['description'])) ?></div>
                            </div>
                            <div class="info-card mb-3 clickable-info" data-bs-toggle="modal" data-bs-target="#locationModal" style="cursor:pointer;">
                                <div class="info-label"><i class="fas fa-map-marker-alt me-2"></i>Location</div>
                                <div class="info-value"><?= htmlspecialchars($report['location']) ?></div>
                            </div>
                            <div class="info-card mb-3 clickable-info" data-bs-toggle="modal" data-bs-target="#timelineModalAll" style="cursor:pointer;">
                                <div class="info-label mb-2"><i class="fas fa-stream me-2"></i><?= $category_text ?> Timeline</div>
                                <div class="horizontal-timeline">
                                    <div class="timeline-step active">
                                        <div class="timeline-dot"><i class="fas fa-flag"></i></div>
                                        <div class="timeline-label">Reported</div>
                                        <div class="timeline-desc">Report created</div>
                                        <div class="timeline-date"><?= date('M j, Y', strtotime($report['created_at'])) ?></div>
                                        <div class="timeline-connector"></div>
                                    </div>
                                    <?php if ($report['status'] !== 'returned'): ?>
                                    <div class="timeline-step">
                                        <div class="timeline-dot"><i class="fas fa-hourglass-half"></i></div>
                                        <div class="timeline-label">In Progress</div>
                                        <div class="timeline-desc">Waiting to be found or claimed</div>
                                        <div class="timeline-date">Active</div>
                                        <div class="timeline-connector"></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($report['status'] === 'returned'): ?>
                                    <div class="timeline-step active">
                                        <div class="timeline-dot"><i class="fas fa-check"></i></div>
                                        <div class="timeline-label"><?php if ($report['category'] === 'person') { echo 'Reunited'; } else { echo 'Returned'; } ?></div>
                                        <div class="timeline-desc">Reunited with family, friends, or caregivers</div>
                                        <div class="timeline-date"><?= date('M j, Y', strtotime($report['updated_at'] ?? $report['created_at'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="info-card mb-3 clickable-info" data-bs-toggle="modal" data-bs-target="#reporterModal" style="cursor:pointer;">
                                <div class="info-label"><i class="fas fa-user me-2"></i>Reported By</div>
                                <div class="info-value"><?= htmlspecialchars($report['reporter_name']) ?></div>
                            </div>
                            <div class="info-card mb-3 clickable-info" data-bs-toggle="modal" data-bs-target="#contactModal" style="cursor:pointer;">
                                <div class="info-label"><i class="fas fa-envelope me-2"></i>Contact Information</div>
                                <div class="info-value"><?= htmlspecialchars($report['contact_info'] ?? $report['reporter_email']) ?></div>
                            </div>
                            <div class="info-card mb-3 text-center d-flex gap-2">
                                <button class="btn btn-outline-primary w-100" onclick="navigator.share ? navigator.share({title:document.title,url:window.location.href}) : window.alert('Copy the link to share!')">
                                    <i class="fas fa-share me-2"></i>Share Report
                                </button>
                                <button class="btn btn-outline-success w-100" onclick="printReport()">
                                    <i class="fas fa-file-pdf me-2"></i>Download PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="info-card mt-4">
                        <div class="info-label mb-2"><i class="fas fa-comments me-2"></i>Comments</div>
                        <?php
                        // Handle new comment submission
                        if (isset($_POST['add_comment']) && isset($_SESSION['user_id']) && !empty(trim($_POST['comment']))) {
                            $comment = trim($_POST['comment']);
                            $user_id = $_SESSION['user_id'];
                            $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
                            $reply_to = isset($_POST['reply_to']) ? trim($_POST['reply_to']) : null;
                            
                            if ($parent_id) {
                                // This is a reply
                                $stmt = $conn->prepare("INSERT INTO comments (report_id, user_id, comment, parent_id, reply_to) VALUES (?, ?, ?, ?, ?)");
                                $stmt->bind_param("iisis", $id, $user_id, $comment, $parent_id, $reply_to);
                            } else {
                                // This is a top-level comment
                                $stmt = $conn->prepare("INSERT INTO comments (report_id, user_id, comment) VALUES (?, ?, ?)");
                                $stmt->bind_param("iis", $id, $user_id, $comment);
                            }
                            $stmt->execute();
                        }
                        // Handle comment deletion (admin only)
                        if (isset($_POST['delete_comment']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
                            $del_id = intval($_POST['delete_comment']);
                            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
                            $stmt->bind_param("i", $del_id);
                            $stmt->execute();
                        }
                        // Fetch all comments for total count (top-level only)
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM comments WHERE report_id = ? AND parent_id IS NULL");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $totalResult = $stmt->get_result();
                        $totalComments = $totalResult->fetch_assoc()['total'];
                        
                        // Fetch first 2 top-level comments with their replies for display
                        $stmt = $conn->prepare("
                            SELECT c.*, u.name, 
                                   (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) as reply_count
                            FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.report_id = ? AND c.parent_id IS NULL 
                            ORDER BY c.created_at DESC 
                            LIMIT 2
                        ");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $comments = $stmt->get_result();
                        
                        // Function to get replies for a comment
                        function getCommentReplies($comment_id, $conn) {
                            $stmt = $conn->prepare("
                                SELECT c.*, u.name 
                                FROM comments c 
                                JOIN users u ON c.user_id = u.id 
                                WHERE c.parent_id = ? 
                                ORDER BY c.created_at ASC
                            ");
                            $stmt->bind_param("i", $comment_id);
                            $stmt->execute();
                            return $stmt->get_result();
                        }
                        ?>
                        <div class="mb-3">
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="post" class="d-flex align-items-start gap-2">
                                <textarea name="comment" class="form-control rounded-3 shadow-sm" rows="2" placeholder="Add a comment..." required></textarea>
                                <button type="submit" name="add_comment" class="btn btn-primary rounded-3 shadow-sm"><i class="fas fa-paper-plane"></i></button>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info py-2">Please <a href="login.php">login</a> to comment.</div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($comments->num_rows > 0): ?>
                                <?php while ($c = $comments->fetch_assoc()): ?>
                                    <div class="border rounded-3 p-3 mb-3 bg-light position-relative shadow-sm">
                                        <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                            <i class="fas fa-user-circle me-1 text-primary"></i><?= htmlspecialchars($c['name']) ?> 
                                            <span class="text-muted small ms-2">
                                                <i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="text-muted mb-2" style="font-size:0.98rem;">"<?= htmlspecialchars($c['comment']) ?>"</div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex gap-2">
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary reply-btn" 
                                                            data-comment-id="<?= $c['id'] ?>" 
                                                            data-comment-author="<?= htmlspecialchars($c['name']) ?>">
                                                        <i class="fas fa-reply me-1"></i>Reply
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($c['reply_count'] > 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary toggle-replies-btn" 
                                                            data-comment-id="<?= $c['id'] ?>">
                                                        <i class="fas fa-comments me-1"></i><?= $c['reply_count'] ?> replies
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                                <form method="post" class="d-inline">
                                                    <button type="submit" name="delete_comment" value="<?= $c['id'] ?>" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Reply form (hidden by default) -->
                                        <div class="reply-form mt-3" id="reply-form-<?= $c['id'] ?>" style="display: none;">
                                            <form method="post" class="d-flex align-items-start gap-2">
                                                <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                                                <input type="hidden" name="reply_to" value="<?= htmlspecialchars($c['name']) ?>">
                                                <textarea name="comment" class="form-control rounded-3 shadow-sm" rows="2" 
                                                          placeholder="Reply to <?= htmlspecialchars($c['name']) ?>..." required></textarea>
                                                <button type="submit" name="add_comment" class="btn btn-primary rounded-3 shadow-sm">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <!-- Replies container -->
                                        <div class="replies-container mt-3" id="replies-<?= $c['id'] ?>" style="display: none;">
                                            <?php 
                                            $replies = getCommentReplies($c['id'], $conn);
                                            while ($reply = $replies->fetch_assoc()): 
                                            ?>
                                                <div class="border rounded-3 p-2 mb-2 bg-white ms-4 position-relative" style="border-left: 3px solid #6366f1 !important;">
                                                    <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                                        <i class="fas fa-reply me-1 text-primary"></i><?= htmlspecialchars($reply['name']) ?>
                                                        <span class="text-muted small ms-2">
                                                            <i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?>
                                                        </span>
                                                        <?php if ($reply['reply_to']): ?>
                                                            <span class="text-primary small">→ @<?= htmlspecialchars($reply['reply_to']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size:0.95rem;">"<?= htmlspecialchars($reply['comment']) ?>"</div>
                                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                                        <form method="post" class="position-absolute top-0 end-0 m-2">
                                                            <button type="submit" name="delete_comment" value="<?= $reply['id'] ?>" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                
                                <?php if ($totalComments > 2): ?>
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#commentsModal">
                                            <i class="fas fa-comments me-2"></i>Read More Comments (<?= $totalComments - 2 ?> more)
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-muted">No comments yet. Be the first to comment!</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Glassmorphism printable report section -->
        <div id="printable-report" style="display:none;">
            <div class="pdf-glass-card">
                <div class="pdf-header-row">
                    <div class="pdf-title-group">
                        <h1 class="pdf-big-title"><i class="fas fa-search-location text-primary me-2"></i>Inventra</h1>
                        <div class="pdf-badges">
                            <span class="pdf-badge filled"><i class="fas <?= $category_icon ?> me-1"></i><?= $category_text ?></span>
                            <span class="pdf-badge"><i class="fas <?= $type_icon ?> me-1"></i><?= ucfirst($report['type']) ?></span>
                            <span class="pdf-badge"><i class="fas <?= $status_icon ?> me-1"></i><?= ucfirst($report['status']) ?></span>
                        </div>
                    </div>
                    <div class="pdf-meta">
                        <span><b>ID:</b> #<?= htmlspecialchars($report['id']) ?></span>
                        <span><b>Date:</b> <?= date('F j, Y', strtotime($report['created_at'])) ?></span>
                    </div>
                </div>
                <div class="pdf-main-grid">
                    <div class="pdf-details">
                        <div class="pdf-section-title">Title</div>
                        <div class="pdf-detail-value fw-bold mb-2" style="font-size:1.3rem;"> <?= htmlspecialchars($report['title']) ?> </div>
                        <div class="pdf-section-title">Location</div>
                        <div class="pdf-detail-value mb-2"> <?= htmlspecialchars($report['location']) ?> </div>
                        <div class="pdf-section-title">Reported By</div>
                        <div class="pdf-detail-value mb-2"> <?= htmlspecialchars($report['reporter_name']) ?> </div>
                        <div class="pdf-section-title">Contact</div>
                        <div class="pdf-detail-value mb-2"> <?= htmlspecialchars($report['contact_info'] ?? $report['reporter_email']) ?> </div>
                        <div class="pdf-section-title">Description</div>
                        <div class="pdf-detail-value mb-2"> <?= nl2br(htmlspecialchars($report['description'])) ?> </div>
                    </div>
                    <?php if (!empty($report['photo'])): ?>
                    <div class="pdf-image-col">
                        <img src="<?= htmlspecialchars($report['photo']) ?>" alt="Report Photo" class="pdf-photo">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="pdf-timeline-section">
                    <div class="pdf-section-title">Timeline</div>
                    <div class="pdf-vertical-timeline">
                        <div class="pdf-timeline-step active">
                            <div class="pdf-timeline-dot"><i class="fas fa-flag"></i></div>
                            <div>
                                <div class="pdf-timeline-label">Reported</div>
                                <div class="pdf-timeline-desc">Report created</div>
                                <div class="pdf-timeline-date"> <?= date('M j, Y \a\t g:i A', strtotime($report['created_at'])) ?> by <?= htmlspecialchars($report['reporter_name']) ?> </div>
                            </div>
                        </div>
                        <?php if ($report['status'] !== 'returned'): ?>
                        <div class="pdf-timeline-step">
                            <div class="pdf-timeline-dot"><i class="fas fa-hourglass-half"></i></div>
                            <div>
                                <div class="pdf-timeline-label">In Progress</div>
                                <div class="pdf-timeline-desc">Waiting to be found or claimed</div>
                                <div class="pdf-timeline-date">Active</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($report['status'] === 'returned'): ?>
                        <div class="pdf-timeline-step active">
                            <div class="pdf-timeline-dot"><i class="fas fa-check"></i></div>
                            <div>
                                <div class="pdf-timeline-label"><?php if ($report['category'] === 'person') { echo 'Reunited'; } else { echo 'Returned'; } ?></div>
                                <div class="pdf-timeline-desc">Reunited with family, friends, or caregivers</div>
                                <div class="pdf-timeline-date"> <?= date('M j, Y \a\t g:i A', strtotime($report['updated_at'] ?? $report['created_at'])) ?> </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pdf-footer">
                    <span>Generated by Inventra | <?= date('F j, Y, g:i A') ?></span>
                </div>
            </div>
        </div>
        <style>
        @media print {
            body * { visibility: hidden !important; }
            #printable-report, #printable-report * { visibility: visible !important; }
            #printable-report { position: absolute; left: 0; top: 0; width: 100vw; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #222; padding: 2rem 1.5rem; min-height: 100vh; z-index: 9999; }
            .pdf-glass-card {
                max-width: 900px;
                margin: 0 auto;
                background: rgba(255,255,255,0.85);
                border-radius: 32px;
                box-shadow: 0 8px 40px rgba(99,102,241,0.13);
                border: 1.5px solid rgba(99,102,241,0.10);
                padding: 2.5rem 2rem 1.5rem 2rem;
                backdrop-filter: blur(18px);
            }
            .pdf-header-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 1.2rem;
            }
            .pdf-title-group { display: flex; flex-direction: column; gap: 0.5rem; }
            .pdf-big-title { font-size: 2.2rem; font-weight: 900; letter-spacing: 1px; color: #22223b; margin-bottom: 0.2rem; }
            .pdf-badges { display: flex; gap: 0.5rem; margin-top: 0.2rem; }
            .pdf-badge {
                border-radius: 999px;
                font-size: 1.02rem;
                font-weight: 600;
                padding: 0.5rem 1.2rem;
                background: rgba(99,102,241,0.08);
                color: #6366f1;
                border: 1.5px solid #e0e7ff;
                display: flex;
                align-items: center;
                gap: 0.3rem;
            }
            .pdf-badge.filled { background: linear-gradient(90deg, #6366f1, #8b5cf6); color: #fff; border: none; }
            .pdf-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 0.2rem; font-size: 1.08rem; color: #6366f1; }
            .pdf-main-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem; align-items: flex-start; margin-bottom: 2rem; }
            .pdf-details { font-size: 1.08rem; }
            .pdf-section-title { color: #6366f1; font-size: 1.15rem; font-weight: 800; margin-bottom: 0.2rem; letter-spacing: 0.5px; margin-top: 0.7rem; }
            .pdf-detail-value { color: #22223b; font-size: 1.08rem; }
            .pdf-image-col { display: flex; align-items: center; justify-content: center; }
            .pdf-photo { max-width: 260px; max-height: 260px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: 2px solid #e0e7ff; background: #f8fafc; }
            .pdf-timeline-section { margin-top: 1.5rem; }
            .pdf-vertical-timeline { position: relative; padding-left: 2rem; }
            .pdf-vertical-timeline::before {
                content: '';
                position: absolute;
                left: 0.5rem;
                top: 0;
                bottom: 0;
                width: 2px;
                background: linear-gradient(to bottom, #6366f1, #8b5cf6);
            }
            .pdf-timeline-step {
                position: relative;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: flex-start;
                gap: 1.2rem;
            }
            .pdf-timeline-dot {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: linear-gradient(135deg, #6366f1 60%, #8b5cf6 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 1.3rem;
                box-shadow: 0 2px 8px #6366f122;
                margin-bottom: 0.5rem;
                z-index: 2;
            }
            .pdf-timeline-step.active .pdf-timeline-dot {
                background: linear-gradient(135deg, #10b981 60%, #34d399 100%);
            }
            .pdf-timeline-label { font-weight: 700; color: #6366f1; margin-bottom: 0.2rem; }
            .pdf-timeline-desc { color: #6b7280; font-size: 0.98rem; }
            .pdf-timeline-date { color: #8b5cf6; font-size: 0.93rem; margin-top: 0.2rem; }
            .pdf-footer { text-align: right; color: #bbb; font-size: 0.98rem; margin-top: 2.5rem; border-top: 1px solid #e0e7ff; padding-top: 0.7rem; }
        }
        </style>
        <script>
        function printReport() {
            // Show printable section, print, then hide again
            const printable = document.getElementById('printable-report');
            printable.style.display = 'block';
            window.print();
            setTimeout(() => { printable.style.display = 'none'; }, 500);
        }
        </script>
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
        // Global variables for media gallery
        let currentMediaIndex = 0;
        let mediaData = [];
        
        // Initialize media data from PHP
        <?php if (!empty($reportMedia)): ?>
        mediaData = <?= json_encode($reportMedia) ?>;
        <?php endif; ?>
        
        function openMediaGallery(startIndex = 0) {
            currentMediaIndex = startIndex;
            const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
            
            // Load media content
            loadMediaContent();
            
            // Load thumbnails
            loadThumbnails();
            
            // Update counter
            updateMediaCounter();
            
            // Show/hide navigation arrows
            updateNavigationArrows();
            
            modal.show();
        }
        
        function loadMediaContent() {
            const mediaContent = document.getElementById('mediaContent');
            mediaContent.innerHTML = '';
            
            if (mediaData.length === 0) return;
            
            const currentMedia = mediaData[currentMediaIndex];
            
            if (currentMedia.file_type === 'image') {
                const img = document.createElement('img');
                img.src = currentMedia.file_path;
                img.className = 'img-fluid';
                img.alt = currentMedia.file_name;
                mediaContent.appendChild(img);
            } else if (currentMedia.file_type === 'video') {
                const video = document.createElement('video');
                video.src = currentMedia.file_path;
                video.className = 'img-fluid';
                video.controls = true;
                video.autoplay = true;
                video.alt = currentMedia.file_name;
                mediaContent.appendChild(video);
            }
        }
        
        function loadThumbnails() {
            const thumbnailsContainer = document.getElementById('mediaThumbnails');
            thumbnailsContainer.innerHTML = '';
            
            mediaData.forEach((media, index) => {
                const thumbnail = document.createElement('div');
                thumbnail.className = `media-thumbnail position-relative ${index === currentMediaIndex ? 'active' : ''}`;
                thumbnail.onclick = () => goToMedia(index);
                
                if (media.file_type === 'image') {
                    const img = document.createElement('img');
                    img.src = media.file_path;
                    img.alt = media.file_name;
                    thumbnail.appendChild(img);
                } else if (media.file_type === 'video') {
                    const video = document.createElement('video');
                    video.src = media.file_path;
                    video.muted = true;
                    thumbnail.appendChild(video);
                    
                    const indicator = document.createElement('div');
                    indicator.className = 'video-indicator';
                    indicator.innerHTML = '<i class="fas fa-play"></i>';
                    thumbnail.appendChild(indicator);
                }
                
                thumbnailsContainer.appendChild(thumbnail);
            });
        }
        
        function changeMedia(direction) {
            const newIndex = currentMediaIndex + direction;
            
            if (newIndex >= 0 && newIndex < mediaData.length) {
                currentMediaIndex = newIndex;
                loadMediaContent();
                loadThumbnails();
                updateMediaCounter();
                updateNavigationArrows();
            }
        }
        
        function goToMedia(index) {
            if (index >= 0 && index < mediaData.length) {
                currentMediaIndex = index;
                loadMediaContent();
                loadThumbnails();
                updateMediaCounter();
                updateNavigationArrows();
            }
        }
        
        function updateMediaCounter() {
            const counter = document.getElementById('mediaCounter');
            counter.textContent = `${currentMediaIndex + 1} of ${mediaData.length}`;
        }
        
        function updateNavigationArrows() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            prevBtn.style.display = currentMediaIndex > 0 ? 'block' : 'none';
            nextBtn.style.display = currentMediaIndex < mediaData.length - 1 ? 'block' : 'none';
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('mediaModal');
            if (modal.classList.contains('show')) {
                if (e.key === 'ArrowLeft') {
                    changeMedia(-1);
                } else if (e.key === 'ArrowRight') {
                    changeMedia(1);
                } else if (e.key === 'Escape') {
                    bootstrap.Modal.getInstance(modal).hide();
                }
            }
        });
        
        // Touch/swipe support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.getElementById('mediaContent').addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.getElementById('mediaContent').addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next
                    changeMedia(1);
                } else {
                    // Swipe right - previous
                    changeMedia(-1);
                }
            }
        }

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
        
        // Comment reply functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Main page reply buttons
            document.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const replyForm = document.getElementById('reply-form-' + commentId);
                    replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
                    
                    // Focus on textarea when showing
                    if (replyForm.style.display === 'block') {
                        replyForm.querySelector('textarea').focus();
                    }
                });
            });
            
            // Main page toggle replies buttons
            document.querySelectorAll('.toggle-replies-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const repliesContainer = document.getElementById('replies-' + commentId);
                    repliesContainer.style.display = repliesContainer.style.display === 'none' ? 'block' : 'none';
                    
                    // Update button text
                    const icon = this.querySelector('i');
                    if (repliesContainer.style.display === 'block') {
                        icon.className = 'fas fa-chevron-up me-1';
                        this.innerHTML = icon.outerHTML + 'Hide replies';
                    } else {
                        icon.className = 'fas fa-comments me-1';
                        this.innerHTML = icon.outerHTML + this.textContent.split(' ')[1] + ' replies';
                    }
                });
            });
            
            // Modal reply buttons
            document.querySelectorAll('.modal-reply-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const replyForm = document.getElementById('modal-reply-form-' + commentId);
                    replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
                    
                    // Focus on textarea when showing
                    if (replyForm.style.display === 'block') {
                        replyForm.querySelector('textarea').focus();
                    }
                });
            });
            
            // Modal toggle replies buttons
            document.querySelectorAll('.modal-toggle-replies-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.getAttribute('data-comment-id');
                    const repliesContainer = document.getElementById('modal-replies-' + commentId);
                    repliesContainer.style.display = repliesContainer.style.display === 'none' ? 'block' : 'none';
                    
                    // Update button text
                    const icon = this.querySelector('i');
                    if (repliesContainer.style.display === 'block') {
                        icon.className = 'fas fa-chevron-up me-1';
                        this.innerHTML = icon.outerHTML + 'Hide replies';
                    } else {
                        icon.className = 'fas fa-comments me-1';
                        this.innerHTML = icon.outerHTML + this.textContent.split(' ')[1] + ' replies';
                    }
                });
            });
        });
    </script>
    
    <!-- Media Gallery Styles -->
    <style>
    .media-preview {
        position: relative;
    }
    
    .media-overlay {
        z-index: 5;
    }
    
    .media-gallery-container {
        min-height: 400px;
    }
    
    .media-content {
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
    }
    
    .media-content img,
    .media-content video {
        max-width: 100%;
        max-height: 70vh;
        object-fit: contain;
    }
    
    .media-thumbnails {
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        overflow-x: auto;
        white-space: nowrap;
    }
    
    .media-thumbnail {
        display: inline-block;
        width: 80px;
        height: 60px;
        margin: 0 5px;
        border: 2px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .media-thumbnail.active {
        border-color: #6366f1;
        transform: scale(1.05);
    }
    
    .media-thumbnail:hover {
        border-color: #6366f1;
        transform: scale(1.05);
    }
    
    .media-thumbnail img,
    .media-thumbnail video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .media-thumbnail .video-indicator {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        background: rgba(0,0,0,0.7);
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .media-thumbnail {
            width: 60px;
            height: 45px;
        }
    }
    </style>
    
    <!-- Comments Modal -->
    <div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commentsModalLabel">
                        <i class="fas fa-comments me-2"></i>All Comments (<?= $totalComments ?>)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // Fetch all top-level comments for the modal
                    $stmt = $conn->prepare("
                        SELECT c.*, u.name, 
                               (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) as reply_count
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.report_id = ? AND c.parent_id IS NULL 
                        ORDER BY c.created_at DESC
                    ");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $allComments = $stmt->get_result();
                    ?>
                    
                    <?php if ($allComments->num_rows > 0): ?>
                        <div class="comments-container">
                            <?php while ($c = $allComments->fetch_assoc()): ?>
                                <div class="border rounded-3 p-3 mb-3 bg-light position-relative shadow-sm">
                                    <div class="fw-bold mb-2 d-flex align-items-center gap-2">
                                        <i class="fas fa-user-circle me-1 text-primary"></i><?= htmlspecialchars($c['name']) ?> 
                                        <span class="text-muted small ms-2">
                                            <i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($c['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div class="text-muted mb-2" style="font-size:0.98rem;">"<?= htmlspecialchars($c['comment']) ?>"</div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary modal-reply-btn" 
                                                        data-comment-id="<?= $c['id'] ?>" 
                                                        data-comment-author="<?= htmlspecialchars($c['name']) ?>">
                                                    <i class="fas fa-reply me-1"></i>Reply
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($c['reply_count'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary modal-toggle-replies-btn" 
                                                        data-comment-id="<?= $c['id'] ?>">
                                                    <i class="fas fa-comments me-1"></i><?= $c['reply_count'] ?> replies
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                            <form method="post" class="d-inline">
                                                <button type="submit" name="delete_comment" value="<?= $c['id'] ?>" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Modal reply form (hidden by default) -->
                                    <div class="modal-reply-form mt-3" id="modal-reply-form-<?= $c['id'] ?>" style="display: none;">
                                        <form method="post" class="d-flex align-items-start gap-2">
                                            <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="reply_to" value="<?= htmlspecialchars($c['name']) ?>">
                                            <textarea name="comment" class="form-control rounded-3 shadow-sm" rows="2" 
                                                      placeholder="Reply to <?= htmlspecialchars($c['name']) ?>..." required></textarea>
                                            <button type="submit" name="add_comment" class="btn btn-primary rounded-3 shadow-sm">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Modal replies container -->
                                    <div class="modal-replies-container mt-3" id="modal-replies-<?= $c['id'] ?>" style="display: none;">
                                        <?php 
                                        $replies = getCommentReplies($c['id'], $conn);
                                        while ($reply = $replies->fetch_assoc()): 
                                        ?>
                                            <div class="border rounded-3 p-2 mb-2 bg-white ms-4 position-relative" style="border-left: 3px solid #6366f1 !important;">
                                                <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                                                    <i class="fas fa-reply me-1 text-primary"></i><?= htmlspecialchars($reply['name']) ?>
                                                    <span class="text-muted small ms-2">
                                                        <i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?>
                                                    </span>
                                                    <?php if ($reply['reply_to']): ?>
                                                        <span class="text-primary small">→ @<?= htmlspecialchars($reply['reply_to']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted" style="font-size:0.95rem;">"<?= htmlspecialchars($reply['comment']) ?>"</div>
                                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                                                    <form method="post" class="position-absolute top-0 end-0 m-2">
                                                        <button type="submit" name="delete_comment" value="<?= $reply['id'] ?>" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
                            <h5>No comments yet</h5>
                            <p>Be the first to comment on this report!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

