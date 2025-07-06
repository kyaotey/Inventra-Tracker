<?php 
require 'includes/auth.php'; 
require 'includes/db.php'; 
require 'includes/security.php';

// Generate CSRF token for any forms
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-container {
            background: rgba(255,255,255,0.93);
            backdrop-filter: blur(14px);
            border-radius: 28px;
            box-shadow: 0 8px 40px rgba(99,102,241,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            margin: 3rem auto 2rem auto;
            max-width: 1100px;
        }
        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2.2rem;
        }
        .dashboard-header h2 {
            font-weight: 800;
            font-size: 2rem;
            color: #6366f1;
            margin-bottom: 0;
        }
        .dashboard-header .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.08rem;
            padding: 0.7rem 2rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.10);
            transition: all 0.2s;
        }
        .dashboard-header .btn-primary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 32px rgba(99,102,241,0.18);
        }
        .item-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: 0 8px 32px rgba(99,102,241,0.07);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(99,102,241,0.13);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
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
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 25px rgba(16,185,129,0.13);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(16,185,129,0.18);
        }
        @media (max-width: 900px) {
            .dashboard-container {
                padding: 1.2rem 0.3rem 1.2rem 0.3rem;
            }
            .dashboard-header h2 {
                font-size: 1.3rem;
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
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2><i class="fas fa-clipboard-list me-2"></i>Your Reported Items</h2>
            <a href="report.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Report New Item</a>
        </div>
        <div class="row">
            <?php
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT * FROM reports WHERE reported_by = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status_color = $row['status'] === 'returned' ? 'success' : 'warning';
                    $type_color = $row['type'] === 'lost' ? 'danger' : 'info';
                    $type_icon = $row['type'] === 'lost' ? 'fa-exclamation-triangle' : 'fa-hand-holding-heart';
                    echo "
                    <div class='col-lg-4 col-md-6 mb-4'>
                        <div class='item-card h-100'>
                            <div class='card-body p-4'>
                                <div class='d-flex justify-content-between align-items-start mb-3'>
                                    <h5 class='card-title fw-bold mb-0'>{$row['title']}</h5>
                                    <div class='d-flex gap-2'>
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
                                    <p class='card-text'>{$row['description']}</p>
                                </div>
                                <div class='d-flex justify-content-between align-items-center'>
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
                echo "<div class='col-12 text-center'><p class='text-muted'>No reports yet.</p></div>";
            }
            ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
