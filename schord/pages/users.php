<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if admin
if ($user['role'] !== 'admin') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';
$edit_user = null;

// Add new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = '❌ All fields are required';
    } elseif (strlen($password) < 6) {
        $error = '❌ Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = '❌ Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Invalid email format';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = '❌ Email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')")) {
                $success = '✅ User added successfully!';
                $_POST = array();
            } else {
                $error = '❌ Error adding user';
            }
        }
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $edit_id = sanitize($_POST['edit_id']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);

    if (empty($name) || empty($email) || empty($role)) {
        $error = '❌ All fields are required';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id!='$edit_id'");
        if ($check->num_rows > 0) {
            $error = '❌ Email already exists';
        } else {
            if ($conn->query("UPDATE users SET name='$name', email='$email', role='$role' WHERE id='$edit_id'")) {
                $success = '✅ User updated successfully!';
                $_POST = array();
                $edit_user = null;
            } else {
                $error = '❌ Error updating user';
            }
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($id == $user['id']) {
        $error = '❌ You cannot delete your own account';
    } else {
        if ($conn->query("DELETE FROM users WHERE id='$id'")) {
            $success = '✅ User deleted successfully!';
        } else {
            $error = '❌ Error deleting user';
        }
    }
}

// Get user to edit
if (isset($_GET['edit'])) {
    $id = sanitize($_GET['edit']);
    $result = $conn->query("SELECT * FROM users WHERE id='$id'");
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';

$query = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $query .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($role_filter) {
    $query .= " AND role = '$role_filter'";
}
$query .= " ORDER BY created_at DESC";

$users = $conn->query($query);
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$nurses = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='nurse'")->fetch_assoc()['count'];
$staff = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='staff'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SCHoRD</title>
    <style>
        <?php
            // Role-based color scheme
            $primary = '#dc2626';  // Admin red
            $primary_dark = '#991b1b';
            if ($user['role'] === 'nurse') {
                $primary = '#0891b2';  // Nurse cyan
                $primary_dark = '#0e7490';
            } elseif ($user['role'] === 'staff') {
                $primary = '#6366f1';  // Staff indigo
                $primary_dark = '#4f46e5';
            }
        ?>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: <?php echo $primary; ?>;
            --primary-dark: <?php echo $primary_dark; ?>;
            --secondary: #2c3e50;
            --light: #ecf0f1;
            --dark: #1e293b;
            --text-dark: #0f172a;
            --text-light: #95a5a6;
            --success: #10b981;
            --warning: #f59e0b;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-dark);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary);
        }

        .header h1 {
            font-size: 28px;
            color: var(--text-dark);
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left: 4px solid #ef4444;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--primary);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .filters {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .table-container {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-light);
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--text-dark);
            font-size: 14px;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .role-admin {
            background: rgba(220, 38, 38, 0.1);
            color: var(--primary);
        }

        .role-nurse {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .role-staff {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-edit {
            background: #3b82f6;
            color: white;
        }

        .action-edit:hover {
            background: #2563eb;
        }

        .action-delete {
            background: #ef4444;
            color: white;
        }

        .action-delete:hover {
            background: #dc2626;
        }

        .action-btn:active {
            transform: scale(0.95);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            font-size: 22px;
            color: var(--text-dark);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .empty-state p {
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                flex-direction: column;
            }

            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select {
                min-width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>👥 User Management</h1>
            </div>
            <div class="header-actions">
                <?php
                    $dashboardFile = 'dashboard.php';
                    if ($user['role'] === 'admin') {
                        $dashboardFile = 'dashboard_admin.php';
                    } elseif ($user['role'] === 'nurse') {
                        $dashboardFile = 'nurse_dashboard.php';
                    } elseif ($user['role'] === 'staff') {
                        $dashboardFile = 'staff_dashboard.php';
                    }
                ?>
                <a href="../dashboards/<?php echo $dashboardFile; ?>" class="btn btn-secondary">← Back to Dashboard</a>
                <button class="btn btn-primary" onclick="openAddUserModal()" type="button">➕ Add New User</button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $admins; ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $nurses; ?></div>
                <div class="stat-label">Nurses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $staff; ?></div>
                <div class="stat-label">Staff Members</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%; align-items: center;">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="nurse" <?php echo $role_filter === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
                    <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
                <button type="submit" class="btn btn-secondary" style="min-width: auto;">🔍 Filter</button>
                <a href="users.php" class="btn btn-secondary" style="min-width: auto;">Clear</a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <?php if ($users->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $u['role']; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="action-btn action-edit" type="button" onclick="openEditUserModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo $u['role']; ?>')">✏️ Edit</button>
                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <button class="action-btn action-delete" type="button" onclick="if(confirm('Are you sure you want to delete this user?')) { window.location.href='users.php?delete=<?php echo $u['id']; ?>'; }">🗑️ Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <p>No users found. Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New User</h2>
                <button class="close-btn" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="staff">Staff</option>
                        <option value="nurse">Nurse</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">✅ Add User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit User</h2>
                <button class="close-btn" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="edit_role" required>
                        <option value="staff">Staff</option>
                        <option value="nurse">Nurse</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            const modal = document.getElementById('addUserModal');
            if (modal) {
                modal.classList.add('active');
            }
        }

        function openEditUserModal(id, name, email, role) {
            const idField = document.getElementById('edit_id');
            const nameField = document.getElementById('edit_name');
            const emailField = document.getElementById('edit_email');
            const roleField = document.getElementById('edit_role');
            
            if (idField && nameField && emailField && roleField) {
                idField.value = id;
                nameField.value = name;
                emailField.value = email;
                roleField.value = role;
                const modal = document.getElementById('editUserModal');
                if (modal) {
                    modal.classList.add('active');
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const addModal = document.getElementById('addUserModal');
            const editModal = document.getElementById('editUserModal');
            
            if (addModal && event.target === addModal) {
                addModal.classList.remove('active');
            }
            if (editModal && event.target === editModal) {
                editModal.classList.remove('active');
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const addModal = document.getElementById('addUserModal');
                const editModal = document.getElementById('editUserModal');
                if (addModal) addModal.classList.remove('active');
                if (editModal) editModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>
