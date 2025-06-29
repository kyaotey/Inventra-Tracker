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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Missing Items Tracker</title>
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
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .table {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        .btn-admin {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <nav class="admin-header navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shield-alt text-primary me-2"></i>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
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
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <i class="fas fa-clipboard-list fa-2x text-primary mb-3"></i>
                    <div class="stats-number"><?= $total_reports ?></div>
                    <div class="text-muted">Total Reports</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                    <div class="stats-number text-warning"><?= $pending_reports ?></div>
                    <div class="text-muted">Pending Reports</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                    <div class="stats-number text-success"><?= $returned_reports ?></div>
                    <div class="text-muted">Resolved Reports</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <i class="fas fa-users fa-2x text-info mb-3"></i>
                    <div class="stats-number text-info"><?= $total_users ?></div>
                    <div class="text-muted">Registered Users</div>
                </div>
            </div>
        </div>

        <!-- Category Statistics -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-4">
                <div class="stats-card">
                    <i class="fas fa-box fa-2x text-primary mb-3"></i>
                    <div class="stats-number text-primary"><?= $item_reports ?></div>
                    <div class="text-muted">Missing Items</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="stats-card">
                    <i class="fas fa-user fa-2x text-danger mb-3"></i>
                    <div class="stats-number text-danger"><?= $person_reports ?></div>
                    <div class="text-muted">Missing Persons</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="stats-card">
                    <i class="fas fa-paw fa-2x text-warning mb-3"></i>
                    <div class="stats-number text-warning"><?= $pet_reports ?></div>
                    <div class="text-muted">Missing Pets</div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-box">
            <form method="get" class="row g-3">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by title..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="item" <?= ($_GET['category'] ?? '') === 'item' ? 'selected' : '' ?>>Items</option>
                        <option value="person" <?= ($_GET['category'] ?? '') === 'person' ? 'selected' : '' ?>>Persons</option>
                        <option value="pet" <?= ($_GET['category'] ?? '') === 'pet' ? 'selected' : '' ?>>Pets</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="lost" <?= ($_GET['type'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= ($_GET['type'] ?? '') === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="returned" <?= ($_GET['status'] ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-admin w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-admin w-100">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Reports Table -->
        <div class="admin-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h4 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Manage Reports
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
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
                                    
                                    echo "
                                    <tr>
                                        <td class='fw-bold'>#{$report['id']}</td>
                                        <td>
                                            <strong>{$report['title']}</strong>
                                            <br><small class='text-muted'>" . substr($report['description'], 0, 100) . (strlen($report['description']) > 100 ? '...' : '') . "</small>
                                        </td>
                                        <td>
                                            <span class='status-badge bg-{$category_color} text-white'>
                                                <i class='fas {$category_icon} me-1'></i>{$category_text}
                                            </span>
                                        </td>
                                        <td>
                                            <span class='status-badge bg-{$type_color} text-white'>
                                                <i class='fas {$type_icon} me-1'></i>{$report['type']}
                                            </span>
                                        </td>
                                        <td>
                                            <i class='fas fa-map-marker-alt text-muted me-1'></i>
                                            {$report['location']}
                                        </td>
                                        <td>
                                            <i class='fas fa-user text-muted me-1'></i>
                                            {$report['reporter_name']}
                                        </td>
                                        <td>
                                            <span class='status-badge bg-{$status_color} text-white'>
                                                {$report['status']}
                                            </span>
                                        </td>
                                        <td>
                                            <small class='text-muted'>
                                                " . date('M j, Y', strtotime($report['created_at'])) . "
                                            </small>
                                        </td>
                                        <td>
                                            <div class='btn-group' role='group'>
                                                <a href='../view.php?id={$report['id']}' 
                                                   class='btn btn-sm btn-outline-primary btn-admin' 
                                                   title='View Details'>
                                                    <i class='fas fa-eye'></i>
                                                </a>
                                                " . ($report['status'] !== 'returned' ? "
                                                <a href='?action=return&id={$report['id']}' 
                                                   class='btn btn-sm btn-outline-success btn-admin' 
                                                   title='Mark as Returned'
                                                   onclick='return confirm(\"Mark this report as returned?\")'>
                                                    <i class='fas fa-check'></i>
                                                </a>" : "") . "
                                                <a href='?action=delete&id={$report['id']}' 
                                                   class='btn btn-sm btn-outline-danger btn-admin' 
                                                   title='Delete Report'
                                                   onclick='return confirm(\"Are you sure you want to delete this report? This action cannot be undone.\")'>
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    ";
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
