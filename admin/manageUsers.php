<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update':
            $id = intval($_POST['user_id']);
            $name = $conn->real_escape_string($_POST['name']);
            $email = $conn->real_escape_string($_POST['email']);
            $dob = $conn->real_escape_string($_POST['dob']);
            $gender = $conn->real_escape_string($_POST['gender']);
            $bio = $conn->real_escape_string($_POST['bio'] ?? '');

            $check = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $id");
            if($check->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            $sql = "UPDATE users SET name = '$name', email = '$email', dob = '$dob', gender = '$gender', bio = '$bio' WHERE id = $id";
            
            if($conn->query($sql)) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $conn->error]);
            }
            exit;

        case 'delete':
            $id = intval($_POST['id']);
            
            if($conn->query("DELETE FROM users WHERE id = $id")) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $conn->error]);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Handle GET API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get') {
        $id = intval($_GET['id']);
        $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();

        if($user) {
            // Pull human-readable interests list for the admin modal
            $interests = [];
            $interest_sql = "SELECT i.interest_name FROM user_interests ui JOIN interests i ON ui.interest_id = i.id WHERE ui.user_id = $id";
            $interest_result = $conn->query($interest_sql);
            if($interest_result) {
                while($row = $interest_result->fetch_assoc()) {
                    $interests[] = $row['interest_name'];
                }
            }
            $user['interests'] = !empty($interests) ? implode(', ', $interests) : '';

            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        exit;
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : "";

$users = $conn->query("SELECT id, name, email, dob, gender, created_at FROM users WHERE role='User' $search_query ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content-header">
                <h1><i class="bi bi-people-fill"></i> Manage Users</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <form method="GET" action="" id="searchForm">
                            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
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
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>DOB</th>
                                    <th>Gender</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['dob'])); ?></td>
                                    <td><?php echo $user['gender']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn-view" onclick="viewUser(<?php echo $user['id']; ?>)" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" title="Delete">
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
                <h2><i class="bi bi-person-circle"></i> User Details</h2>
                <button class="close-btn" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
            </div>
        </div>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-pencil-square"></i> Edit User</h2>
                <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" id="edit_name" name="name" required>
                        <span class="error-message" id="error_name"></span>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" id="edit_email" name="email" required>
                        <span class="error-message" id="error_email"></span>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" id="edit_dob" name="dob" required>
                        <span class="error-message" id="error_dob"></span>
                    </div>
                    <div class="form-group">
                        <label>Gender <span class="required">*</span></label>
                        <select id="edit_gender" name="gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea id="edit_bio" name="bio" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin-users.js"></script>
</body>
</html>
