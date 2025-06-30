<?php 
require 'includes/security.php';
session_start();
require 'includes/db.php'; 
// Show welcome popup if just logged in
$show_welcome = false;
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] && isset($_SESSION['name'])) {
    $show_welcome = true;
    unset($_SESSION['show_welcome']); // Only show once
}
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
            --surface: #fff;
            --surface-glass: rgba(255,255,255,0.92);
            --text-main: #22223b;
            --text-muted: #6b7280;
            --navbar-bg: linear-gradient(90deg, #f8fafc 60%, #e0e7ff 100%);
            --shadow: 0 8px 40px rgba(99,102,241,0.10);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #6366f1 100%);
            min-height: 100vh;
            color: var(--text-main);
            transition: background 0.3s, color 0.3s;
        }
        .navbar {
            background: var(--navbar-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1.5px solid rgba(99, 102, 241, 0.13);
            box-shadow: var(--shadow);
            border-radius: 0 0 22px 22px;
            padding-top: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1050;
            animation: navbarFadeIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        @keyframes navbarFadeIn {
            from { opacity: 0; transform: translateY(-16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .navbar .navbar-brand {
            font-size: 2rem;
            letter-spacing: 1px;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            font-weight: 800;
            transition: color 0.2s, text-shadow 0.2s;
            text-shadow: 0 2px 8px rgba(99,102,241,0.07);
        }
        .navbar .navbar-brand:hover {
            color: var(--secondary-color) !important;
            text-shadow: 0 4px 16px rgba(139,92,246,0.13);
        }
        .navbar .navbar-brand i {
            font-size: 2.2rem;
            margin-right: 0.5rem;
        }
        .navbar-nav {
            align-items: center;
        }
        .navbar-nav .nav-link {
            color: var(--text-main) !important;
            font-weight: 600;
            border-radius: 12px;
            margin-left: 0.7rem;
            margin-right: 0.7rem;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            padding: 0.6rem 1.2rem;
            font-size: 1.08rem;
        }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff !important;
            box-shadow: 0 2px 12px var(--primary-color), 0 0 8px var(--primary-color);
        }
        .navbar .rounded-circle {
            border: 2.5px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(99, 102, 241, 0.13);
            width: 40px !important;
            height: 40px !important;
            font-size: 1.25rem !important;
            font-weight: 700;
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        .hero-section {
            background: var(--surface-glass);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            margin-bottom: 2.5rem;
            padding: 3rem 1.5rem 2.5rem 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            border: 1.5px solid rgba(99,102,241,0.10);
        }
        .hero-section h1 {
            color: var(--primary-color);
            font-weight: 800;
            font-size: 2.5rem;
            text-shadow: 0 2px 16px rgba(99,102,241,0.13);
        }
        .hero-section p {
            color: var(--text-muted);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 2rem auto;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 12px var(--primary-color), 0 0 8px var(--primary-color);
        }
        .btn-primary:hover, .btn-primary:focus {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 25px var(--primary-color), 0 0 16px var(--primary-color);
            outline: none;
        }
        .search-card {
            background: var(--surface-glass);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(99,102,241,0.10);
            box-shadow: 0 8px 32px var(--primary-color, rgba(99,102,241,0.10));
            transition: background 0.3s, border 0.3s;
        }
        .item-card {
            background: var(--surface-glass);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(99,102,241,0.10);
            box-shadow: 0 8px 32px var(--primary-color, rgba(99,102,241,0.10));
            transition: background 0.3s, border 0.3s;
            overflow: hidden;
        }
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px var(--primary-color, rgba(99,102,241,0.18));
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid rgba(99,102,241,0.10);
            padding: 0.75rem 1rem;
            background: var(--surface);
            color: var(--text-main);
            transition: background 0.3s, color 0.3s, border 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            background: var(--surface-glass);
            color: var(--text-main);
        }
        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }
        .floating-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4);
        }
        .welcome-popup {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 2000;
            background: linear-gradient(135deg, #6366f1 60%, #8b5cf6 100%);
            color: #fff;
            padding: 1.25rem 2.2rem 1.25rem 1.5rem;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(99,102,241,0.22), 0 2px 8px rgba(0,0,0,0.10);
            font-size: 1.15rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 1rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s, transform 0.5s;
            transform: translateY(-30px) scale(0.98);
            min-width: 320px;
            max-width: 90vw;
            border: 1.5px solid #fff2;
            overflow: hidden;
        }
        .welcome-popup.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }
        .welcome-popup .close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.3rem;
            margin-left: 1rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .welcome-popup .close-btn:hover {
            opacity: 1;
        }
        .welcome-popup .icon-anim {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            margin-right: 0.5rem;
            animation: popIn 0.7s cubic-bezier(.4,0,.2,1);
        }
        @keyframes popIn {
            0% { transform: scale(0.5) rotate(-20deg); opacity: 0; }
            60% { transform: scale(1.2) rotate(8deg); opacity: 1; }
            100% { transform: scale(1) rotate(0); opacity: 1; }
        }
        .welcome-popup .progress-bar {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 4px;
            background: linear-gradient(90deg, #a5b4fc 0%, #6366f1 100%);
            border-radius: 0 0 18px 18px;
            animation: progressBarAnim 5s linear forwards;
        }
        @keyframes progressBarAnim {
            from { width: 100%; }
            to { width: 0%; }
        }
        @media (max-width: 600px) {
            .welcome-popup {
                right: 0.5rem;
                left: 0.5rem;
                top: 1rem;
                padding: 1rem 1.2rem 1rem 1rem;
                font-size: 1rem;
                min-width: unset;
            }
        }
    </style>
</head>
<body>
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
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="display-4 fw-bold mb-3">
                <i class="fas fa-search-location me-3"></i>
                Find Your Missing Items, Persons & Pets
            </h1>
            <p class="lead mb-4">Connect lost items, missing persons, and pets with their owners. Report found items and search for your lost belongings, loved ones, or pets. Our platform makes it easy, secure, and fast.</p>
            
            <!-- Action Buttons -->
            <div class="row g-3 justify-content-center">
                <div class="col-md-4">
                    <a href="report_item.php" class="btn btn-primary btn-lg w-100 shadow-sm">
                        <i class="fas fa-box me-2"></i>Report Missing Item
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="report_person.php" class="btn btn-danger btn-lg w-100 shadow-sm">
                        <i class="fas fa-user me-2"></i>Report Missing Person
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="report_pet.php" class="btn btn-warning btn-lg w-100 shadow-sm">
                        <i class="fas fa-paw me-2"></i>Report Missing Pet
                    </a>
                </div>
            </div>
        </div>
        <!-- Filter/Search Bar -->
        <form id="filterForm" class="row g-3 align-items-end mb-4 bg-white rounded-4 shadow-sm p-3" style="backdrop-filter:blur(8px);">
            <div class="col-md-2">
                <label class="form-label fw-bold text-primary">Keyword</label>
                <input type="text" class="form-control" name="keyword" placeholder="Search...">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-primary">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="item">Items</option>
                    <option value="person">Persons</option>
                    <option value="pet">Pets</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-primary">Location</label>
                <input type="text" class="form-control" name="location" placeholder="Location">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-primary">Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="lost">Lost</option>
                    <option value="found">Found</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold text-primary">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="returned">Returned</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Search
                </button>
            </div>
        </form>
        <!-- Items Grid -->
        <div class="row" id="itemsGrid">
        <?php
        $where = "WHERE 1=1";
        $params = [];
        $types = [];
        
        if (!empty($_GET['keyword'])) {
            $where .= " AND (r.title LIKE ? OR r.description LIKE ?)";
            $params[] = '%' . $_GET['keyword'] . '%';
            $params[] = '%' . $_GET['keyword'] . '%';
            $types[] = 's';
            $types[] = 's';
        }
        if (!empty($_GET['category'])) {
            $where .= " AND r.category = ?";
            $params[] = $_GET['category'];
            $types[] = 's';
        }
        if (!empty($_GET['location'])) {
            $where .= " AND location LIKE ?";
            $params[] = '%' . $_GET['location'] . '%';
            $types[] = 's';
        }
        if (!empty($_GET['type'])) {
            $where .= " AND type = ?";
            $params[] = $_GET['type'];
            $types[] = 's';
        }
        if (!empty($_GET['status'])) {
            $where .= " AND status = ?";
            $params[] = $_GET['status'];
            $types[] = 's';
        }

        $query = "SELECT r.*, u.name as reporter_name FROM reports r 
                 LEFT JOIN users u ON r.reported_by = u.id 
                 $where ORDER BY r.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param(implode('', $types), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $status_color = $row['status'] === 'returned' ? 'success' : 'warning';
                $type_color = $row['type'] === 'lost' ? 'danger' : 'info';
                $type_icon = $row['type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-hand-holding-heart';
                
                // Category-specific icons and colors
                $category_icon = 'fa-box';
                $category_color = 'primary';
                $category_text = 'Item';
                
                if ($row['category'] === 'person') {
                    $category_icon = 'fa-user';
                    $category_color = 'danger';
                    $category_text = 'Person';
                } elseif ($row['category'] === 'pet') {
                    $category_icon = 'fa-paw';
                    $category_color = 'warning';
                    $category_text = 'Pet';
                }
                
                echo "
                <div class='col-lg-4 col-md-6 mb-4'>
                    <div class='item-card h-100'>
                        <div class='card-body p-4'>
                            <div class='d-flex justify-content-between align-items-start mb-3'>
                                <h5 class='card-title fw-bold mb-0'>{$row['title']}</h5>
                                <div class='d-flex gap-2'>
                                    <span class='status-badge bg-{$category_color} text-white'>
                                        <i class='fas {$category_icon} me-1'></i>{$category_text}
                                    </span>
                                    <span class='status-badge bg-{$type_color} text-white'>
                                        <i class='fas {$type_icon} me-1'></i>{$row['type']}
                                    </span>
                                    <span class='status-badge bg-{$status_color} text-white'>
                                        {$row['status']}
                                    </span>
                                </div>
                            </div>
                            
                            <div class='mb-3'>
                                <p class='text-muted mb-2'>
                                    <i class='fas fa-map-marker-alt me-2'></i>
                                    <strong>Location:</strong> {$row['location']}
                                </p>
                                <p class='card-text'>" . substr($row['description'], 0, 150) . (strlen($row['description']) > 150 ? '...' : '') . "</p>
                            </div>
                            
                            <div class='d-flex justify-content-between align-items-center'>
                                <small class='text-muted'>
                                    <i class='fas fa-user me-1'></i>
                                    {$row['reporter_name']}
                                </small>
                                <small class='text-muted'>
                                    <i class='fas fa-calendar me-1'></i>
                                    " . date('M j, Y', strtotime($row['created_at'])) . "
                                </small>
                            </div>
                            
                            <div class='mt-3'>
                                <a href='view.php?id={$row['id']}' class='btn btn-outline-primary btn-sm w-100'>
                                    <i class='fas fa-eye me-1'></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                ";
            }
        } else {
            echo "
            <div class='col-12 text-center py-5'>
                <div class='text-white'>
                    <i class='fas fa-search fa-3x mb-3 opacity-50'></i>
                    <h4>No reports found</h4>
                    <p class='lead'>Try adjusting your search criteria or be the first to report a missing item, person, or pet!</p>
                    " . (isset($_SESSION['user_id']) ? "
                    <div class='row g-3 justify-content-center mt-4'>
                        <div class='col-md-4'>
                            <a href='report_item.php' class='btn btn-primary btn-lg w-100'>
                                <i class='fas fa-box me-2'></i>Report Missing Item
                            </a>
                        </div>
                        <div class='col-md-4'>
                            <a href='report_person.php' class='btn btn-danger btn-lg w-100'>
                                <i class='fas fa-user me-2'></i>Report Missing Person
                            </a>
                        </div>
                        <div class='col-md-4'>
                            <a href='report_pet.php' class='btn btn-warning btn-lg w-100'>
                                <i class='fas fa-paw me-2'></i>Report Missing Pet
                            </a>
                        </div>
                    </div>" : "") . "
                </div>
            </div>
            ";
        }
        ?>
        </div>
        <script>
        document.getElementById('filterForm').addEventListener('input', function() {
            const form = this;
            const formData = new FormData(form);
            fetch('search_items.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('itemsGrid').innerHTML = html;
            });
        });
        </script>
    </div>
    <footer class="text-center py-4 mt-5" style="color:#6366f1;font-weight:600;opacity:0.8;">
        &copy; <?= date('Y') ?> Inventra. All rights reserved.
    </footer>

    <!-- Floating Action Button -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="floating-action">
            <a href="report.php" class="floating-btn d-flex align-items-center justify-content-center text-decoration-none">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    <?php endif; ?>

    <?php if ($show_welcome): ?>
    <div class="welcome-popup" id="welcomePopup">
        <span class="icon-anim"><i class="fas fa-sparkles"></i></span>
        Welcome, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>! Glad to see you back.
        <button class="close-btn" onclick="closeWelcomePopup()" aria-label="Close">&times;</button>
        <div class="progress-bar"></div>
    </div>
    <script>
        function closeWelcomePopup() {
            document.getElementById('welcomePopup').classList.remove('show');
        }
        window.addEventListener('DOMContentLoaded', function() {
            var popup = document.getElementById('welcomePopup');
            if (popup) {
                setTimeout(function() {
                    popup.classList.add('show');
                }, 300); // Fade in
                setTimeout(function() {
                    popup.classList.remove('show');
                }, 5300); // Auto-dismiss after 5s
            }
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
