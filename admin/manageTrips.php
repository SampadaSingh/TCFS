<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update':
            $id = intval($_POST['trip_id']);
            $status = $conn->real_escape_string($_POST['status']);

            $sql = "UPDATE trips SET status = '$status' WHERE id = $id";
            
            if($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'Trip updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update trip: ' . $conn->error]);
            }
            exit;

        case 'delete':
            $id = intval($_POST['id']);
            
            if($conn->query("DELETE FROM trips WHERE id = $id")) {
                echo json_encode(['success' => true, 'message' => 'Trip deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete trip: ' . $conn->error]);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get') {
        $id = intval($_GET['id']);
        $trip = $conn->query("SELECT t.*, u.name as host_name, u.email as host_email FROM trips t JOIN users u ON t.host_id = u.id WHERE t.id = $id")->fetch_assoc();
        if($trip) {
            echo json_encode(['success' => true, 'trip' => $trip]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Trip not found']);
        }
        exit;
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = $search ? "WHERE t.trip_name LIKE '%$search%' OR t.destination LIKE '%$search%'" : "";

$trips = $conn->query("
    SELECT 
        t.id,
        t.trip_name,
        t.destination,
        t.start_date,
        t.end_date,
        t.budget_min,
        t.budget_max,
        t.status,
        t.group_size_max,
        u.name as host_name,
        (SELECT COUNT(*) FROM trip_applications ta WHERE ta.trip_id = t.id AND ta.status = 'accepted') AS accepted_count
    FROM trips t
    JOIN users u ON t.host_id = u.id
    $search_query
    ORDER BY t.created_at DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trips - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-airplane"></i> Manage Trips</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <form method="GET" action="" id="searchForm">
                            <input type="text" name="search" placeholder="Search trips..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="bi bi-search"></i></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <div class="card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Trip Name</th>
                                    <th>Destination</th>
                                    <th>Host</th>
                                    <th>Count</th>
                                    <th>Dates</th>
                                    <th>Budget</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($trip = $trips->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $trip['id']; ?></td>
                                    <td><?php echo htmlspecialchars($trip['trip_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['destination']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['host_name']); ?></td>
                                    <td><?php echo $trip['accepted_count'] . '/' . $trip['group_size_max']; ?></td>
                                    <td><?php echo date('M d', strtotime($trip['start_date'])) . ' - ' . date('M d, Y', strtotime($trip['end_date'])); ?></td>
                                    <td>Rs.<?php echo number_format($trip['budget_min']); ?> - Rs.<?php echo number_format($trip['budget_max']); ?></td>
                                    <td><span class="status-badge status-<?php echo $trip['status']; ?>"><?php echo ucfirst($trip['status']); ?></span></td>
                                    <td class="action-buttons">
                                        <button class="btn-view" onclick="viewTrip(<?php echo $trip['id']; ?>)" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn-edit" onclick="editTrip(<?php echo $trip['id']; ?>)" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-delete" onclick="deleteTrip(<?php echo $trip['id']; ?>, '<?php echo htmlspecialchars($trip['trip_name']); ?>')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-info-circle"></i> Trip Details</h2>
                <button class="close-btn" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
            </div>
        </div>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2><i class="bi bi-pencil-square"></i> Edit Trip</h2>
                <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editTripForm">
                    <input type="hidden" id="edit_trip_id" name="trip_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Trip Name <span class="required">*</span></label>
                            <input type="text" id="edit_name" name="name" required readonly>
                            <span class="error-message" id="error_name"></span>
                        </div>
                        <div class="form-group">
                            <label>Destination <span class="required">*</span></label>
                            <input type="text" id="edit_destination" name="destination" required readonly>
                            <span class="error-message" id="error_destination"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description <span class="required">*</span></label>
                        <textarea id="edit_description" name="description" rows="3" required readonly></textarea>
                        <span class="error-message" id="error_description"></span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date <span class="required">*</span></label>
                            <input type="date" id="edit_start_date" name="start_date" required readonly>
                            <span class="error-message" id="error_start_date"></span>
                        </div>
                        <div class="form-group">
                            <label>End Date <span class="required">*</span></label>
                            <input type="date" id="edit_end_date" name="end_date" required readonly>
                            <span class="error-message" id="error_end_date"></span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Min Budget (Rs.) <span class="required">*</span></label>
                            <input type="number" id="edit_budget_min" name="budget_min" required readonly>
                            <span class="error-message" id="error_budget_min"></span>
                        </div>
                        <div class="form-group">
                            <label>Max Budget (Rs.) <span class="required">*</span></label>
                            <input type="number" id="edit_budget_max" name="budget_max" required readonly>
                            <span class="error-message" id="error_budget_max"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status <span class="required">*</span></label>
                        <select id="edit_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Update Trip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin-trips.js?v=2"></script>
</body>
</html>
