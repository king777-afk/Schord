<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Redirect admin to admin dashboard
if (strtolower($user['role']) === 'admin') {
    header("Location: ../dashboards/dashboard_admin.php");
    exit();
}

$success = '';
$error = '';
$edit_visit = null;

// Add or Update Visit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitize($_POST['student_id']);
    $complaint = sanitize($_POST['complaint']);
    $treatment = sanitize($_POST['treatment']);
    $status = sanitize($_POST['status']);

    if (empty($student_id) || empty($complaint) || empty($treatment)) {
        $error = '❌ All fields are required';
    } else {
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = sanitize($_POST['edit_id']);
            if ($conn->query("UPDATE clinic_visits SET complaint='$complaint', treatment='$treatment', status='$status' WHERE id='$edit_id'")) {
                $success = '✅ Visit updated successfully!';
                $_POST = array();
                $edit_visit = null;
            } else {
                $error = '❌ Error updating visit';
            }
        } else {
            if ($conn->query("INSERT INTO clinic_visits (student_id, visit_date, complaint, treatment, status) VALUES ('$student_id', NOW(), '$complaint', '$treatment', '$status')")) {
                $success = '✅ Visit recorded successfully!';
                $_POST = array();
            } else {
                $error = '❌ Error recording visit';
            }
        }
    }
}

// Delete Visit
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM clinic_visits WHERE id='$id'")) {
        $success = '✅ Visit deleted successfully!';
    } else {
        $error = '❌ Error deleting visit';
    }
}

// Get visit to edit
if (isset($_GET['edit'])) {
    $id = sanitize($_GET['edit']);
    $result = $conn->query("SELECT * FROM clinic_visits WHERE id='$id'");
    if ($result->num_rows > 0) {
        $edit_visit = $result->fetch_assoc();
    }
}

// Search and Filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT cv.*, s.name, s.student_no FROM clinic_visits cv JOIN students s ON cv.student_id = s.id WHERE 1=1";
if ($search) {
    $query .= " AND (s.name LIKE '%$search%' OR s.student_no LIKE '%$search%' OR cv.complaint LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND cv.status = '$status_filter'";
}
$query .= " ORDER BY cv.visit_date DESC";

$visits = $conn->query($query);
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$ongoing = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='completed'")->fetch_assoc()['count'];

$students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Visits - SCHoRD</title>
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
            background: linear-gradient(180deg, var(--dark) 0%, var(--primary-dark) 100%);
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
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--primary);
            padding-left: 16px;
        }

        /* ===== MAIN ===== */
        .main-wrapper {
            margin-left: 260px;
        }

        .top-header {
            background: var(--bg-card);
            border-bottom: 2px solid var(--primary);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
            background: var(--bg-light);
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            border-color: var(--primary);
        }

        .search-box input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            color: var(--text-dark);
            font-size: 14px;
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

        .page-header p {
            color: var(--text-light);
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

        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--primary);
            text-align: center;
        }

        .stat-card.success {
            border-top-color: var(--success);
        }

        .stat-card.warning {
            border-top-color: var(--warning);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-card.success .stat-value {
            color: var(--success);
        }

        .stat-card.warning .stat-value {
            color: var(--warning);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
        }

        /* ===== FORM ===== */
        .form-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .form-card h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--bg-light);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            color: var(--text-dark);
            background: var(--bg-light);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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
            border: 2px solid var(--bg-light);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* ===== TABLE ===== */
        .table-card {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table-header {
            background: var(--bg-light);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            font-size: 18px;
        }

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 15px;
            border: 2px solid var(--bg-light);
            border-radius: 6px;
            background: var(--bg-card);
            color: var(--text-dark);
            cursor: pointer;
            font-size: 13px;
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
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-ongoing {
            background: rgba(250, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* ===== ACTIONS ===== */
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
            text-decoration: none;
            display: inline-block;
        }

        .action-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-edit:hover {
            background: #3b82f6;
            color: white;
        }

        .action-delete {
            background: rgba(220, 38, 38, 0.1);
            color: var(--primary);
        }

        .action-delete:hover {
            background: var(--primary);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filters {
                flex-direction: column;
            }

            .actions {
                flex-direction: column;
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
                $role = strtolower($user['role']);
                if ($role === 'admin') {
                    $dashboardFile = 'dashboard_admin.php';
                } elseif ($role === 'nurse') {
                    $dashboardFile = 'nurse_dashboard.php';
                } elseif ($role === 'staff') {
                    $dashboardFile = 'staff_dashboard.php';
                }
                
                $patientsFile = 'students.php';
                if ($role === 'nurse') {
                    $patientsFile = 'nurse_patients.php';
                } elseif ($role === 'staff') {
                    $patientsFile = 'staff_patients.php';
                }
            ?>
            <li><a href="../dashboards/<?php echo $dashboardFile; ?>"><span>📊</span> Dashboard</a></li>
            <li><a href="<?php echo $patientsFile; ?>"><span>👥</span> Patients</a></li>
            <li><a href="visits.php" class="active"><span>📝</span> Clinic Visits</a></li>
            <li><a href="health_records.php"><span>📋</span> Health Records</a></li>
            <li><a href="reports.php"><span>📊</span> Reports</a></li>
            <li><a href="javascript:void(0)"><span>⚙️</span> Settings</a></li>
            <li><a href="../auth/logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <div class="search-box">
                    <span>🔍</span>
                    <form method="GET" style="display: flex; flex: 1; gap: 10px;">
                        <input type="text" name="search" placeholder="Search visits..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--primary);">🔎</button>
                    </form>
                </div>
            </div>
            <div class="header-right">
                <button class="theme-toggle" id="themeToggle">🌙</button>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>📝 Clinic Visits Management</h1>
                <p>Record and manage all clinic visit records</p>
            </div>

            <!-- ALERTS -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                    <div class="stat-label">Total Visits</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $ongoing; ?></div>
                    <div class="stat-label">Ongoing</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completed; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>

            <!-- ADD/EDIT FORM -->
            <div class="form-card">
                <h2><?php echo $edit_visit ? '✏️ Edit Visit' : '➕ Record New Visit'; ?></h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Student *</label>
                            <select name="student_id" required>
                                <option value="">-- Choose Student --</option>
                                <?php 
                                $students->data_seek(0);
                                while ($student = $students->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                        <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) || (isset($edit_visit) && $edit_visit['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['student_no']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <option value="ongoing" <?php echo (!isset($edit_visit) || $edit_visit['status'] == 'ongoing') ? 'selected' : ''; ?>>⏳ Ongoing</option>
                                <option value="completed" <?php echo (isset($edit_visit) && $edit_visit['status'] == 'completed') ? 'selected' : ''; ?>>✅ Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Complaint *</label>
                        <textarea name="complaint" required placeholder="Describe the student's complaint..."><?php echo isset($_POST['complaint']) ? htmlspecialchars($_POST['complaint']) : (isset($edit_visit) ? htmlspecialchars($edit_visit['complaint']) : ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Treatment *</label>
                        <textarea name="treatment" required placeholder="Describe the treatment provided..."><?php echo isset($_POST['treatment']) ? htmlspecialchars($_POST['treatment']) : (isset($edit_visit) ? htmlspecialchars($edit_visit['treatment']) : ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_visit ? '💾 Update Visit' : '➕ Record Visit'; ?>
                        </button>
                        <?php if ($edit_visit): ?>
                            <a href="visits.php" class="btn btn-secondary">❌ Cancel</a>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_visit['id']; ?>">
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- VISITS TABLE -->
            <div class="table-card">
                <div class="table-header">
                    <h2>📋 All Visits</h2>
                    <form method="GET" class="filters">
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="">-- All Status --</option>
                            <option value="ongoing" <?php echo $status_filter == 'ongoing' ? 'selected' : ''; ?>>⏳ Ongoing</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                        </select>
                    </form>
                </div>
                <?php if ($visits->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Complaint</th>
                                <th>Treatment</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($visit = $visits->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($visit['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($visit['complaint'], 0, 30)); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['treatment'], 0, 30)); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($visit['visit_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $visit['status']; ?>">
                                            <?php echo ucfirst($visit['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="visits.php?edit=<?php echo $visit['id']; ?>" class="action-btn action-edit">✏️ Edit</a>
                                            <a href="visits.php?delete=<?php echo $visit['id']; ?>" class="action-btn action-delete" onclick="return confirm('Delete this visit?');">🗑️ Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>📭 No Visits Found</h3>
                        <p>Record your first clinic visit using the form above or adjust your search</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
