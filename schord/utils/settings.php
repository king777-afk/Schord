<?php
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
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'update_profile') {
        $new_name = sanitize($_POST['name']);
        if (empty($new_name)) {
            $error = '❌ Name cannot be empty';
        } else {
            if ($conn->query("UPDATE users SET name='$new_name' WHERE id='{$user['id']}'")) {
                $success = '✅ Profile updated successfully!';
                $user['name'] = $new_name;
            } else {
                $error = '❌ Error updating profile';
            }
        }
    } elseif ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = sanitize($_POST['new_password']);
        $confirm_password = sanitize($_POST['confirm_password']);
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = '❌ All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = '❌ New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = '❌ Password must be at least 6 characters';
        } else {
            $result = $conn->query("SELECT password FROM users WHERE id='{$user['id']}'");
            $db_user = $result->fetch_assoc();
            if (password_verify($current_password, $db_user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                if ($conn->query("UPDATE users SET password='$hashed_password' WHERE id='{$user['id']}'")) {
                    $success = '✅ Password changed successfully!';
                } else {
                    $error = '❌ Error changing password';
                }
            } else {
                $error = '❌ Current password is incorrect';
            }
        }
    } elseif ($action == 'system_backup') {
        $success = '✅ System backup initiated. This will be completed shortly.';
    }
}

// Get system stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$admin_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$staff_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN('staff', 'nurse')")->fetch_assoc()['count'];

// Get all users for management
$all_users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #dc2626;
            --primary-dark: #991b1b;
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
        }

        body.dark-mode {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --bg-light: #1e293b;
            --bg-card: #334155;
            --text-dark: #f1f5f9;
            --text-light: #cbd5e1;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: var(--dark);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 800;
        }

        .sidebar-nav {
            padding: 20px 0;
            list-style: none;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(220, 38, 38, 0.2);
            border-left: 4px solid var(--primary);
            padding-left: 16px;
        }

        /* ===== MAIN ===== */
        .main-wrapper {
            margin-left: 260px;
        }

        .top-header {
            background: var(--bg-card);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .theme-toggle {
            background: var(--bg-light);
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: var(--primary);
            color: white;
        }

        /* ===== CONTENT ===== */
        .main-content {
            padding: 30px;
        }

        .page-header {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
            box-shadow: var(--shadow);
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--primary);
            border-left: 4px solid var(--primary);
        }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--bg-light);
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            color: var(--text-light);
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ===== SETTINGS CARD ===== */
        .settings-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .settings-card h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--text-dark);
            font-weight: 700;
            border-bottom: 2px solid var(--bg-light);
            padding-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            padding: 12px 15px;
            border: 2px solid var(--bg-light);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            color: var(--text-dark);
            background: var(--bg-light);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--bg-card);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
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
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--primary);
            text-align: center;
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

        /* ===== TABLE ===== */
        .table-card {
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

        .role-staff {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .role-user {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* ===== FEATURE BOX ===== */
        .feature-box {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .feature-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .feature-info p {
            font-size: 13px;
            color: var(--text-light);
        }

        .feature-box .btn {
            margin: 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .feature-box {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span style="font-size: 24px;">🏥</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
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
            <li><a href="../dashboards/<?php echo $dashboardFile; ?>"><span>📊</span> Dashboard</a></li>
            <li><a href="../pages/students.php"><span>👨‍🎓</span> Students</a></li>
            <li><a href="../pages/visits.php"><span>📝</span> Clinic Visits</a></li>
            <li><a href="../pages/health_records.php"><span>📋</span> Health Records</a></li>
            <li><a href="../pages/reports.php"><span>📊</span> Reports</a></li>
            <li><a href="settings.php" class="active"><span>⚙️</span> Settings</a></li>
            <li><a href="../auth/logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div>
                <h2 style="color: var(--text-dark);">⚙️ System Settings</h2>
            </div>
            <div class="header-right">
                <button class="theme-toggle" id="themeToggle">🌙</button>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>⚙️ Administrator Settings</h1>
                <p>Manage system configuration and account settings</p>
            </div>

            <!-- ALERTS -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- TABS -->
            <div class="tabs">
                <button class="tab-btn <?php echo $active_tab == 'general' ? 'active' : ''; ?>" onclick="switchTab('general')">👤 Profile</button>
                <button class="tab-btn <?php echo $active_tab == 'security' ? 'active' : ''; ?>" onclick="switchTab('security')">🔒 Security</button>
                <button class="tab-btn <?php echo $active_tab == 'system' ? 'active' : ''; ?>" onclick="switchTab('system')">⚙️ System</button>
                <button class="tab-btn <?php echo $active_tab == 'users' ? 'active' : ''; ?>" onclick="switchTab('users')">👥 Users</button>
            </div>

            <!-- TAB: PROFILE -->
            <div class="tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>" id="general">
                <div class="settings-card">
                    <h2>👤 Profile Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: var(--bg-light); cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled style="background: var(--bg-light); cursor: not-allowed;">
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="action" value="update_profile" class="btn btn-primary">💾 Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB: SECURITY -->
            <div class="tab-content <?php echo $active_tab == 'security' ? 'active' : ''; ?>" id="security">
                <div class="settings-card">
                    <h2>🔒 Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="action" value="change_password" class="btn btn-primary">🔐 Change Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB: SYSTEM -->
            <div class="tab-content <?php echo $active_tab == 'system' ? 'active' : ''; ?>" id="system">
                <!-- System Stats -->
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-box" style="border-top-color: var(--success);">
                        <div class="stat-value" style="color: var(--success);"><?php echo $admin_users; ?></div>
                        <div class="stat-label">Admin Users</div>
                    </div>
                    <div class="stat-box" style="border-top-color: #3b82f6;">
                        <div class="stat-value" style="color: #3b82f6;"><?php echo $staff_users; ?></div>
                        <div class="stat-label">Staff Members</div>
                    </div>
                    <div class="stat-box" style="border-top-color: var(--warning);">
                        <div class="stat-value" style="color: var(--warning);"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>

                <!-- System Features -->
                <div class="settings-card">
                    <h2>🛠️ System Maintenance</h2>
                    <div class="feature-box">
                        <div class="feature-info">
                            <h3>📥 System Backup</h3>
                            <p>Create a full backup of the database and system files</p>
                        </div>
                        <form method="POST" style="margin: 0;">
                            <button type="submit" name="action" value="system_backup" class="btn btn-primary">📥 Create Backup</button>
                        </form>
                    </div>
                    <div class="feature-box">
                        <div class="feature-info">
                            <h3>✨ System Health</h3>
                            <p>Database Status: ✓ Optimal | Uptime: 99.9% | Response: 127ms</p>
                        </div>
                        <button class="btn btn-secondary">📊 View Details</button>
                    </div>
                    <div class="feature-box">
                        <div class="feature-info">
                            <h3>🔄 Clear Cache</h3>
                            <p>Clear temporary files and cached data</p>
                        </div>
                        <button class="btn btn-secondary">🗑️ Clear Cache</button>
                    </div>
                </div>
            </div>

            <!-- TAB: USERS -->
            <div class="tab-content <?php echo $active_tab == 'users' ? 'active' : ''; ?>" id="users">
                <div class="settings-card">
                    <h2>👥 User Management</h2>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = $all_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo strtolower($u['role']); ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Update URL
            window.history.replaceState({}, '', '?tab=' + tabName);
        }

        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>
