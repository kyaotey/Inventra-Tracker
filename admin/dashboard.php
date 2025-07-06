<?php
require '../includes/security.php';
session_start();
require '../includes/db.php';


// Security check - ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle actions with proper validation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] === 'return' && $id > 0) {
        $stmt = $conn->prepare("UPDATE reports SET status='returned' WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_message = "Item marked as returned successfully!";
        } else {
            $error_message = "Failed to update item status.";
        }
    }
    
    if ($_GET['action'] === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM reports WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_message = "Item deleted successfully!";
        } else {
            $error_message = "Failed to delete item.";
        }
    }
}

// --- User Deletion Logic ---
if (isset($_GET['user_action']) && $_GET['user_action'] === 'delete_user' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    if ($user_id === intval($_SESSION['user_id'])) {
        $error_message = "You cannot delete your own admin account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Failed to delete user.";
        }
    }
}

// For line chart: reports per month (last 6 months)
$months = [];
$report_counts = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $label = date('M Y', strtotime($month . '-01'));
    $months[] = $label;
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
    $count = $conn->query("SELECT COUNT(*) as count FROM reports WHERE created_at BETWEEN '$start' AND '$end'")->fetch_assoc()['count'];
    $report_counts[] = $count;
}
// For doughnut chart: user distribution
$admin_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch_assoc()['count'];
$user_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Missing Items Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #0ea5e9;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --sidebar-width: 220px;
            --sidebar-bg: #232946;
            --sidebar-border: #232946;
            --sidebar-link: #b8c1ec;
            --sidebar-link-active: #fff;
            --sidebar-link-hover: #393e6e;
            --sidebar-accent: #6366f1;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .admin-header {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 24px rgba(99,102,241,0.10);
            border-bottom: 1px solid #e0e7ff;
        }
        .admin-header .navbar-brand {
            font-size: 1.7rem;
            letter-spacing: 1px;
            color: var(--primary-color) !important;
        }
        .admin-header .nav-link, .admin-header .navbar-text {
            color: var(--dark-color) !important;
            font-weight: 500;
        }
        .admin-header .nav-link.text-danger {
            color: var(--danger-color) !important;
        }
        .dashboard-section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }
        .dashboard-cards-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: rgba(255,255,255,0.98);
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(99,102,241,0.07);
            padding: 1.1rem 0.5rem 0.8rem 0.5rem;
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
            border: none;
            position: relative;
        }
        .stats-card:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 18px rgba(99,102,241,0.10);
        }
        .stats-icon {
            font-size: 1.6rem;
            margin-bottom: 0.3rem;
            opacity: 0.92;
        }
        .stats-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .stats-label {
            color: #6b7280;
            font-size: 0.98rem;
            margin-top: 0.1rem;
        }
        .admin-card {
            background: rgba(255,255,255,0.99);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(99,102,241,0.09);
            border: none;
            margin-bottom: 2.5rem;
            padding: 2.2rem 2rem 2rem 2rem;
        }
        .admin-card .card-header {
            background: transparent;
            border: none;
            padding: 0 0 1.2rem 0;
        }
        .search-box {
            background: rgba(255,255,255,0.96);
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(99,102,241,0.06);
            padding: 1.3rem 1rem 1.1rem 1rem;
            margin-bottom: 2.2rem;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e7ff;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.13rem rgba(99,102,241,0.15);
        }
        .table {
            background: rgba(255,255,255,0.99);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(99,102,241,0.07);
        }
        .table thead th {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
            font-size: 1.05rem;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f3f4f6;
        }
        .table tbody tr {
            transition: background-color 0.18s;
        }
        .table tbody tr:hover {
            background-color: #e0e7ff;
        }
        .btn-admin {
            border-radius: 8px;
            padding: 0.45rem 1.1rem;
            font-weight: 500;
            transition: all 0.18s;
            font-size: 1rem;
        }
        .btn-admin:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 2px 8px rgba(99,102,241,0.10);
        }
        .status-badge {
            font-size: 0.95rem;
            padding: 0.32rem 1.1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .badge {
            font-size: 0.95rem;
            padding: 0.32rem 1.1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        @media (max-width: 991px) {
            .dashboard-cards-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 600px) {
            .dashboard-cards-row {
                grid-template-columns: 1fr;
            }
            .admin-card {
                padding: 1.2rem 0.5rem 1.2rem 0.5rem;
            }
        }
        .admin-card .table {
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(99,102,241,0.10);
            margin-bottom: 0;
        }
        .admin-card .table thead th {
            background: #fff;
            color: #22223b;
            font-weight: 700;
            font-size: 1.08rem;
            border: none;
            padding: 1rem 0.7rem;
        }
        .admin-card .table thead tr {
            border-radius: 18px 18px 0 0;
        }
        .admin-card .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f8fafc;
        }
        .admin-card .table tbody tr {
            border-bottom: 1px solid #e0e7ff;
            transition: background 0.15s;
        }
        .admin-card .table tbody tr:hover {
            background: #e0e7ff;
        }
        .admin-card .table td, .admin-card .table th {
            vertical-align: middle;
            border: none;
            padding: 0.85rem 0.7rem;
        }
        .badge-pill {
            border-radius: 999px;
            padding: 0.45em 1.1em;
            font-size: 0.98rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
        }
        .badge-category {
            background: #e0e7ff;
            color: #2563eb;
        }
        .badge-type-lost {
            background: #fee2e2;
            color: #dc2626;
        }
        .badge-type-found {
            background: #cffafe;
            color: #0891b2;
        }
        .badge-status-pending {
            background: #fef9c3;
            color: #b45309;
        }
        .badge-status-returned {
            background: #d1fae5;
            color: #047857;
        }
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        .action-btn {
            background: #f3f4f6;
            border: none;
            border-radius: 50%;
            width: 2.2rem;
            height: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            font-size: 1.1rem;
            transition: background 0.15s, color 0.15s;
            box-shadow: none;
        }
        .action-btn:hover {
            background: #6366f1;
            color: #fff;
        }
        .action-btn.delete {
            color: #ef4444;
        }
        .action-btn.delete:hover {
            background: #ef4444;
            color: #fff;
        }
        .action-btn.return {
            color: #10b981;
        }
        .action-btn.return:hover {
            background: #10b981;
            color: #fff;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            box-shadow: 2px 0 16px rgba(35,41,70,0.10);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            padding: 1.5rem 1rem 1rem 1rem;
            transition: width 0.2s;
        }
        .sidebar-collapsed {
            width: 60px;
        }
        .sidebar .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--sidebar-link-active);
            margin-bottom: 2.5rem;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .sidebar-nav {
            flex: 1;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.7rem 1rem;
            color: var(--sidebar-link);
            text-decoration: none;
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 0.3rem;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-nav a.active, .sidebar-nav a:hover {
            background: var(--sidebar-link-hover);
            color: var(--sidebar-link-active);
        }
        .sidebar-nav a i {
            font-size: 1.2rem;
        }
        .sidebar-bottom {
            margin-top: 2rem;
            border-top: 1px solid #393e6e;
            padding-top: 1.2rem;
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 1rem;
            color: #b8c1ec;
            margin-bottom: 0.7rem;
        }
        .sidebar-logout {
            color: #ef4444 !important;
        }
        .sidebar-toggle {
            display: none;
            position: absolute;
            top: 1.2rem;
            right: -2.2rem;
            background: var(--sidebar-bg);
            border: none;
            color: #fff;
            font-size: 1.5rem;
            border-radius: 50%;
            width: 2.2rem;
            height: 2.2rem;
            align-items: center;
            justify-content: center;
            z-index: 1100;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            transition: margin-left 0.2s;
        }
        .main-content-collapsed {
            margin-left: 60px;
        }
        .admin-card, .stats-card {
            box-shadow: 0 4px 24px rgba(99,102,241,0.10);
            border-radius: 18px;
            background: #fff;
        }
        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                width: 60px;
                padding: 1rem 0.3rem;
            }
            .main-content {
                margin-left: 60px;
                padding: 1.2rem 0.5rem;
            }
            .sidebar-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div class="sidebar-logo">
            <i class="fas fa-shield-alt"></i> <span class="sidebar-logo-text">Admin</span>
        </div>
        <div class="sidebar-nav">
            <a href="#" class="active" data-section="overview"><i class="fas fa-tachometer-alt"></i> <span class="sidebar-link-text">Dashboard</span></a>
            <a href="#" data-section="reports"><i class="fas fa-list"></i> <span class="sidebar-link-text">Reports</span></a>
            <a href="#" data-section="users"><i class="fas fa-users"></i> <span class="sidebar-link-text">Users</span></a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <i class="fas fa-user-shield"></i> <span class="sidebar-user-text"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
            </div>
            <a class="sidebar-nav sidebar-logout" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span class="sidebar-link-text">Logout</span>
            </a>
        </div>
    </div>
    <div class="main-content" id="mainContent">
        <!-- Admin Header -->
        <nav class="admin-header navbar navbar-expand-lg navbar-light sticky-top">
            <div class="container">
                <a class="navbar-brand fw-bold" href="dashboard.php">
                    <i class="fas fa-shield-alt me-2"></i>
                    Admin Dashboard
                </a>
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user-shield me-1"></i>
                        Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>
                    </span>

                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-home me-1"></i>View Site
                    </a>
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </nav>

        <div class="container py-4">
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Overview and Category Statistics -->
            <div id="overview-section">
                <div class="dashboard-section-title">Overview</div>
                <div class="dashboard-cards-row">
                    <?php
                    $total_reports = $conn->query("SELECT COUNT(*) as count FROM reports")->fetch_assoc()['count'];
                    $pending_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status='pending'")->fetch_assoc()['count'];
                    $returned_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status='returned'")->fetch_assoc()['count'];
                    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                    
                    // Category-specific statistics
                    $item_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE category='item'")->fetch_assoc()['count'];
                    $person_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE category='person'")->fetch_assoc()['count'];
                    $pet_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE category='pet'")->fetch_assoc()['count'];
                    ?>
                    <div class="stats-card">
                        <i class="fas fa-clipboard-list stats-icon text-primary"></i>
                        <div class="stats-number text-primary"><?= $total_reports ?></div>
                        <div class="stats-label">Total Reports</div>
                    </div>
                    <div class="stats-card">
                        <i class="fas fa-clock stats-icon text-warning"></i>
                        <div class="stats-number text-warning"><?= $pending_reports ?></div>
                        <div class="stats-label">Pending Reports</div>
                    </div>
                    <div class="stats-card">
                        <i class="fas fa-check-circle stats-icon text-success"></i>
                        <div class="stats-number text-success"><?= $returned_reports ?></div>
                        <div class="stats-label">Resolved Reports</div>
                    </div>
                    <div class="stats-card">
                        <i class="fas fa-users stats-icon text-info"></i>
                        <div class="stats-number text-info"><?= $total_users ?></div>
                        <div class="stats-label">Registered Users</div>
                    </div>
                </div>
                <div class="dashboard-section-title">Category Statistics</div>
                <div class="dashboard-cards-row">
                    <div class="stats-card">
                        <i class="fas fa-box stats-icon text-primary"></i>
                        <div class="stats-number text-primary"><?= $item_reports ?></div>
                        <div class="stats-label">Missing Items</div>
                    </div>
                    <div class="stats-card">
                        <i class="fas fa-user stats-icon text-danger"></i>
                        <div class="stats-number text-danger"><?= $person_reports ?></div>
                        <div class="stats-label">Missing Persons</div>
                    </div>
                    <div class="stats-card">
                        <i class="fas fa-paw stats-icon text-warning"></i>
                        <div class="stats-number text-warning"><?= $pet_reports ?></div>
                        <div class="stats-label">Missing Pets</div>
                    </div>
                </div>
                <div class="dashboard-charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin-top: 2.5rem;">
                    <div class="admin-card">
                        <div style="font-weight:600; font-size:1.1rem; margin-bottom:0.5rem;">Report Status</div>
                        <canvas id="statusPieChart" height="200"></canvas>
                    </div>
                    <div class="admin-card">
                        <div style="font-weight:600; font-size:1.1rem; margin-bottom:0.5rem;">Category Statistics</div>
                        <canvas id="categoryBarChart" height="200"></canvas>
                    </div>
                    <div class="admin-card">
                        <div style="font-weight:600; font-size:1.1rem; margin-bottom:0.5rem;">User Distribution</div>
                        <canvas id="userDoughnutChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <!-- Reports Table -->
            <div id="reports-section" style="display:none;">
                <div class="dashboard-section-title">Manage Reports</div>
                <div class="admin-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">
                            <i class="fas fa-list me-2"></i> Reports
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle rounded-3 overflow-hidden">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Reporter</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $where = "WHERE 1=1";
                                    $params = [];
                                    $types = [];
                                    
                                    if (!empty($_GET['search'])) {
                                        $where .= " AND r.title LIKE ?";
                                        $params[] = '%' . $_GET['search'] . '%';
                                        $types[] = 's';
                                    }
                                    if (!empty($_GET['category'])) {
                                        $where .= " AND r.category = ?";
                                        $params[] = $_GET['category'];
                                        $types[] = 's';
                                    }
                                    if (!empty($_GET['type'])) {
                                        $where .= " AND r.type = ?";
                                        $params[] = $_GET['type'];
                                        $types[] = 's';
                                    }
                                    if (!empty($_GET['status'])) {
                                        $where .= " AND r.status = ?";
                                        $params[] = $_GET['status'];
                                        $types[] = 's';
                                    }

                                    $query = "SELECT r.*, u.name as reporter_name 
                                             FROM reports r 
                                             LEFT JOIN users u ON r.reported_by = u.id 
                                             $where 
                                             ORDER BY r.created_at DESC";
                                    
                                    if (!empty($params)) {
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param(implode('', $types), ...$params);
                                        $stmt->execute();
                                        $reports = $stmt->get_result();
                                    } else {
                                        $reports = $conn->query($query);
                                    }

                                    if ($reports->num_rows > 0) {
                                        while ($report = $reports->fetch_assoc()) {
                                            $status_color = $report['status'] === 'returned' ? 'success' : 'warning';
                                            $type_color = $report['type'] === 'lost' ? 'danger' : 'info';
                                            $type_icon = $report['type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-hand-holding-heart';
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
                                            $type_class = $report['type'] === 'lost' ? 'badge-type-lost' : 'badge-type-found';
                                            $status_class = $report['status'] === 'returned' ? 'badge-status-returned' : 'badge-status-pending';
                                            ?>
                                            <tr>
                                                <td class='fw-bold'>#<?php echo $report['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                                    <br><small class='text-muted'><?php echo htmlspecialchars(substr($report['description'], 0, 100)); echo (strlen($report['description']) > 100 ? '...' : ''); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pill badge-category">
                                                        <i class="fas <?php echo $category_icon; ?> me-1"></i><?php echo $category_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pill <?php echo $type_class; ?>">
                                                        <i class="fas <?php echo $type_icon; ?> me-1"></i><?php echo htmlspecialchars($report['type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                                    <?php echo htmlspecialchars($report['location']); ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user text-muted me-1"></i>
                                                    <?php echo htmlspecialchars($report['reporter_name']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-pill <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($report['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <a href="../view.php?id=<?php echo $report['id']; ?>" class="action-btn" title="View Details"><i class="fas fa-eye"></i></a>
                                                        <?php if ($report['status'] !== 'returned') { ?>
                                                            <a href="?action=return&id=<?php echo $report['id']; ?>" class="action-btn return" title="Mark as Returned" onclick="return confirm('Mark this report as returned?')"><i class="fas fa-check"></i></a>
                                                        <?php } ?>
                                                        <a href="?action=delete&id=<?php echo $report['id']; ?>" class="action-btn delete" title="Delete Report" onclick="return confirm('Are you sure you want to delete this report? This action cannot be undone.')"><i class="fas fa-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo "
                                        <tr>
                                            <td colspan='9' class='text-center py-5'>
                                                <i class='fas fa-search fa-2x text-muted mb-3'></i>
                                                <p class='text-muted'>No reports found matching your criteria.</p>
                                            </td>
                                        </tr>
                                        ";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- User Management Section -->
            <div id="users-section" style="display:none;">
                <div class="dashboard-section-title">User Management</div>
                <div class="admin-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">
                            <i class="fas fa-users-cog me-2"></i> Users
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle rounded-3 overflow-hidden">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $current_admin_id = intval($_SESSION['user_id']);
                                $users = $conn->query("SELECT id, name, email, created_at, is_admin FROM users ORDER BY created_at DESC");
                                if ($users && $users->num_rows > 0) {
                                    while ($user = $users->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td class='fw-bold'>#{$user['id']}</td>";
                                        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                        echo "<td><small class='text-muted'>" . date('M j, Y', strtotime($user['created_at'])) . "</small></td>";
                                        echo "<td>";
                                        if ($user['is_admin']) {
                                            echo "<span class='badge bg-primary'>Admin</span>";
                                        } else {
                                            echo "<span class='badge bg-secondary'>User</span>";
                                        }
                                        echo "</td>";
                                        echo "<td>";
                                        if ($user['id'] !== $current_admin_id) {
                                            echo "<a href='?user_action=delete_user&id={$user['id']}&csrf_token={$_SESSION['csrf_token']}' class='btn btn-sm btn-outline-danger btn-admin' title='Delete User' onclick='return confirm(\"Are you sure you want to delete this user? This action cannot be undone.\")'><i class='fas fa-trash'></i> Delete</a>";
                                        } else {
                                            echo "<span class='badge bg-info text-dark'>Current Admin</span>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-5'><i class='fas fa-users fa-2x text-muted mb-3'></i><p class='text-muted'>No users found.</p></td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sidebar collapse/expand for mobile
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('main-content-collapsed');
            // Hide/show text labels
            document.querySelectorAll('.sidebar-link-text, .sidebar-logo-text, .sidebar-user-text').forEach(el => {
                el.style.display = el.style.display === 'none' ? '' : 'none';
            });
        });

        // Section toggling logic
        const sectionLinks = document.querySelectorAll('.sidebar-nav a');
        const overviewSection = document.getElementById('overview-section');
        const reportsSection = document.getElementById('reports-section');
        const usersSection = document.getElementById('users-section');
        sectionLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                sectionLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                const section = this.getAttribute('data-section');
                overviewSection.style.display = (section === 'overview') ? '' : 'none';
                reportsSection.style.display = (section === 'reports') ? '' : 'none';
                usersSection.style.display = (section === 'users') ? '' : 'none';
                toggleCharts(section === 'overview');
            });
        });

        // Default to dashboard view
        overviewSection.style.display = '';
        reportsSection.style.display = 'none';
        usersSection.style.display = 'none';

        // Pie chart for Report Status
        const statusPieCtx = document.getElementById('statusPieChart').getContext('2d');
        const statusPieChart = new Chart(statusPieCtx, {
            type: 'pie',
            data: {
                labels: ['Pending Reports', 'Resolved Reports'],
                datasets: [{
                    data: [<?php echo $pending_reports; ?>, <?php echo $returned_reports; ?>],
                    backgroundColor: [
                        'rgba(251, 191, 36, 0.8)', // yellow
                        'rgba(16, 185, 129, 0.8)'  // green
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                }
            }
        });

        // Bar chart for Category Statistics
        const barCtx = document.getElementById('categoryBarChart').getContext('2d');
        const categoryBarChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Missing Items', 'Missing Persons', 'Missing Pets'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $item_reports; ?>, <?php echo $person_reports; ?>, <?php echo $pet_reports; ?>],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)', // blue
                        'rgba(239, 68, 68, 0.7)',  // red
                        'rgba(251, 191, 36, 0.7)'  // yellow
                    ],
                    borderRadius: 8,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // Doughnut chart for User Distribution
        const doughnutCtx = document.getElementById('userDoughnutChart').getContext('2d');
        const userDoughnutChart = new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Admins', 'Users'],
                datasets: [{
                    data: [<?php echo $admin_count; ?>, <?php echo $user_count; ?>],
                    backgroundColor: [
                        'rgba(99,102,241,0.8)', // blue
                        'rgba(59, 130, 246, 0.7)' // lighter blue
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                }
            }
        });

        // Hide all charts when not on dashboard
        function toggleCharts(show) {
            document.querySelector('.dashboard-charts-grid').style.display = show ? 'grid' : 'none';
        }
        toggleCharts(true);
    </script>
</body>
</html>
