<?php
require 'includes/db.php';

$where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($_POST['keyword'])) {
    $where .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = '%' . $_POST['keyword'] . '%';
    $params[] = '%' . $_POST['keyword'] . '%';
    $types .= 'ss';
}
if (!empty($_POST['category'])) {
    $where .= " AND r.category = ?";
    $params[] = $_POST['category'];
    $types .= 's';
}
if (!empty($_POST['location'])) {
    $where .= " AND location LIKE ?";
    $params[] = '%' . $_POST['location'] . '%';
    $types .= 's';
}
if (!empty($_POST['type'])) {
    $where .= " AND type = ?";
    $params[] = $_POST['type'];
    $types .= 's';
}
if (!empty($_POST['status'])) {
    $where .= " AND status = ?";
    $params[] = $_POST['status'];
    $types .= 's';
}
if (!empty($_POST['date_from'])) {
    $where .= " AND created_at >= ?";
    $params[] = $_POST['date_from'] . ' 00:00:00';
    $types .= 's';
}
if (!empty($_POST['date_to'])) {
    $where .= " AND created_at <= ?";
    $params[] = $_POST['date_to'] . ' 23:59:59';
    $types .= 's';
}

$query = "SELECT r.*, u.name as reporter_name FROM reports r LEFT JOIN users u ON r.reported_by = u.id $where ORDER BY r.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
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
    echo "<div class='col-12 text-center py-5'><div class='text-muted'><i class='fas fa-search fa-3x mb-3 opacity-50'></i><h4>No reports found</h4><p class='lead'>Try adjusting your search criteria or be the first to report a missing item, person, or pet!</p></div></div>";
} 