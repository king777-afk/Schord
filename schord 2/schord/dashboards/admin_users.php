<?php
// Prevent caching of this page
header('Cache-Control: no-cache, no-store, must-revalidate, private, max-age=0');
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
header('X-UA-Compatible: IE=edge');
header('Content-Type: text/html; charset=utf-8');

include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if admin - redirect based on role
if (strtolower($user['role']) !== 'admin') {
    if (strtolower($user['role']) === 'nurse') {
        header("Location: nurse_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
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

// Get user statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$nurses = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='nurse'")->fetch_assoc()['count'];
$staff = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='staff'")->fetch_assoc()['count'];

// Search and filter
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <style>
        /* Hide any stray CSS text that might appear */
        body::before,
        body::after,
        .container::before,
        .container::after {
            display: none !important;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3f2 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
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
        }

        .header h1 {
            font-size: 28px;
            font-weight: 800;
        }

        .header-actions {
            display: flex;
            gap: 12px;
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 600;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
            border-bottom: 2px solid var(--border-color);
        }

        th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-size: 14px;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .role-admin {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary);
        }

        .role-nurse {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .role-staff {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
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
            font-weight: 600;
        }

        .action-edit {
            background: var(--secondary);
            color: white;
        }

        .action-edit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .action-delete {
            background: var(--danger);
            color: white;
        }

        .action-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
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
            width: 90%;
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
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>👥 Manage Users</h1>
            <div class="header-actions">
                <button class="btn btn-primary" id="addUserBtn" type="button">➕ Add User</button>
                <a href="dashboard_admin.php" class="btn btn-secondary">← Back</a>
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
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $nurses; ?></div>
                <div class="stat-label">Nurses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $staff; ?></div>
                <div class="stat-label">Staff</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%; align-items: center;">
                <input type="text" name="search" placeholder="🔍 Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="nurse" <?php echo $role_filter === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
                    <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
                <button type="submit" class="btn btn-secondary" style="min-width: auto;">🔍 Filter</button>
                <a href="admin_users.php" class="btn btn-secondary" style="min-width: auto;">Clear</a>
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
                            <th>Joined Date</th>
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
                                        <button class="action-btn action-edit" type="button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)">✏️ Edit</button>
                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <button class="action-btn action-delete" type="button" onclick="if(confirm('Are you sure you want to delete this user?')) { window.location.href='admin_users.php?delete=<?php echo $u['id']; ?>'; }">🗑️ Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: var(--text-light);">
                    <div style="font-size: 48px; margin-bottom: 10px;">👥</div>
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
                <button class="close-btn" type="button" onclick="closeModal('addUserModal')">✕</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" minlength="6" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" minlength="6" required>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="nurse">Nurse</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Add User</button>
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
                <button class="close-btn" type="button" onclick="closeModal('editUserModal')">✕</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="edit_id" id="editUserId">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="editUserName" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="editUserEmail" required>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="editUserRole" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="nurse">Nurse</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Update User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add User button
        const addUserBtn = document.getElementById('addUserBtn');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', function() {
                openModal('addUserModal');
            });
        }

        // Open modal
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
            }
        }

        // Close modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Open edit modal with user data
        function openEditModal(userData) {
            if (userData && userData.id) {
                document.getElementById('editUserId').value = userData.id;
                document.getElementById('editUserName').value = userData.name || '';
                document.getElementById('editUserEmail').value = userData.email || '';
                document.getElementById('editUserRole').value = userData.role || '';
                openModal('editUserModal');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal.active');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.active');
                modals.forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>
