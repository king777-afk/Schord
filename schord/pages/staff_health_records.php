<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// STAFF ONLY
if (strtolower($user['role']) !== 'staff') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';
$edit_record = null;

// Add or Update Health Record
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitize($_POST['student_id']);
    $blood_type = sanitize($_POST['blood_type']);
    $allergies = sanitize($_POST['allergies']);
    $chronic_conditions = sanitize($_POST['chronic_conditions']);
    $medications = sanitize($_POST['medications']);
    $notes = sanitize($_POST['notes']);

    if (empty($student_id)) {
        $error = 'âŒ Student is required';
    } else {
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = sanitize($_POST['edit_id']);
            if ($conn->query("UPDATE health_records SET blood_type='$blood_type', allergies='$allergies', chronic_conditions='$chronic_conditions', medications='$medications', notes='$notes' WHERE id='$edit_id'")) {
                $success = '✅ Health record updated successfully!';
                $_POST = array();
                $edit_record = null;
            } else {
                $error = 'âŒ Error updating record';
            }
        } else {
            if ($conn->query("INSERT INTO health_records (student_id, blood_type, allergies, chronic_conditions, medications, notes) VALUES ('$student_id', '$blood_type', '$allergies', '$chronic_conditions', '$medications', '$notes')")) {
                $success = '✅ Health record added successfully!';
                $_POST = array();
            } else {
                $error = 'âŒ Error adding record';
            }
        }
    }
}

// Delete Health Record
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM health_records WHERE id='$id'")) {
        $success = '✅ Record deleted successfully!';
    } else {
        $error = 'âŒ Error deleting record';
    }
}

// Get record to edit
if (isset($_GET['edit'])) {
    $id = sanitize($_GET['edit']);
    $result = $conn->query("SELECT * FROM health_records WHERE id='$id'");
    if ($result->num_rows > 0) {
        $edit_record = $result->fetch_assoc();
    }
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT hr.*, s.name, s.student_no FROM health_records hr JOIN students s ON hr.student_id = s.id WHERE 1=1";
if ($search) {
    $query .= " AND (s.name LIKE '%$search%' OR s.student_no LIKE '%$search%' OR hr.blood_type LIKE '%$search%')";
}
$query .= " ORDER BY s.name ASC";

$records = $conn->query($query);
$total_records = $conn->query("SELECT COUNT(*) as count FROM health_records")->fetch_assoc()['count'];

$students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Staff - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #10b981;
            --primary-light: #34d399;
            --primary-dark: #059669;
            --accent: #6ee7b7;
            --accent-light: #a7f3d0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #064e3b;
            --darker: #022c1d;
            --text-dark: #1f2937;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg-light: #f0fdf4;
            --bg-lighter: #dcfce7;
            --bg-card: #ffffff;
            --bg-hover: #ecfdf5;
            --border-color: #bbf7d0;
            --shadow: 0 10px 40px rgba(16, 185, 129, 0.1);
            --shadow-lg: 0 20px 60px rgba(16, 185, 129, 0.15);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #e0fce8 100%);
            color: var(--text-dark);
            min-height: 100vh;
            letter-spacing: -0.3px;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #022c1d 0%, #064e3b 50%, #0f5c47 100%);
            --bg-light: #1b4d3f;
            --bg-lighter: #0f5c47;
            --bg-card: #064e3b;
            --bg-hover: #0d5d4a;
            --border-color: #047857;
            --text-dark: #ecfdf5;
            --text-light: #cbd5e1;
            --text-lighter: #94a3b8;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.2);
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(167, 139, 250, 0.05) 100%);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #34d399 0%, #6ee7b7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo {
            font-size: 28px;
            filter: drop-shadow(0 4px 10px rgba(16, 185, 129, 0.3));
        }

        .sidebar-nav {
            padding: 15px 0;
            list-style: none;
        }

        .sidebar-nav li {
            margin: 5px 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            border: none;
            background: none;
            text-align: left;
            width: 100%;
            font-size: 15px;
            position: relative;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(16, 185, 129, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a.active {
            border-left-color: var(--accent);
        }

        .sidebar-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            margin: 0 20px 10px 20px;
            letter-spacing: 1px;
            margin-top: 30px;
            border-top: 1px solid rgba(16, 185, 129, 0.15);
            padding-top: 15px;
        }

        .sidebar-section-label:first-of-type {
            margin-top: 0;
            border-top: none;
            padding-top: 0;
        }

        /* ===== MAIN CONTENT ===== */
        .main-wrapper {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-wrapper.sidebar-collapsed {
            margin-left: 0;
        }

        /* ===== TOP HEADER ===== */
        .top-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 20px;
        }

        body.dark-mode .top-header {
            background: rgba(30, 27, 75, 0.5);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .theme-toggle {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: var(--primary);
            color: white;
        }

        /* ===== MAIN CONTENT AREA ===== */
        .main-content {
            padding: 35px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 22px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 27, 75, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(16, 185, 129, 0.2);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--primary);
            margin: 12px 0 0 0;
        }

        /* ===== FORM STYLES ===== */
        .form-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
        }

        body.dark-mode .form-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 27, 75, 0.8) 100%);
        }

        .form-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 18px;
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
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-dark);
            font-family: inherit;
            transition: all 0.3s ease;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group select,
        body.dark-mode .form-group textarea {
            background: rgba(16, 185, 129, 0.05);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* ===== TABLE STYLES ===== */
        .table-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            margin-top: 20px;
        }

        body.dark-mode .table-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 27, 75, 0.8) 100%);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(167, 139, 250, 0.05));
            border-bottom: 2px solid var(--border-color);
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: var(--bg-hover);
        }

        body.dark-mode tbody tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }

        /* ===== BUTTONS ===== */
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .action-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-left-color: #10b981;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left-color: #ef4444;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

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
            border-top: 5px solid var(--primary);
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
            font-weight: 700;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .form-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
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
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--bg-light);
            border-radius: 8px;
            color: var(--text-dark);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .table-card {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--bg-light);
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .action-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .main-wrapper + footer {
            margin-left: 280px;
            text-align: center;
            padding: 14px 20px;
        }

        .main-wrapper.sidebar-collapsed + footer {
            margin-left: 0;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .main-wrapper + footer { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">👨‍⚕️</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
            <div class="sidebar-section-label">Main</div>
            <li><a href="../dashboards/staff_dashboard.php" class="nav-link">📊 Dashboard</a></li>
            
            <div class="sidebar-section-label">Operation</div>
            <li><a href="staff_visits.php" class="nav-link">📋 Clinic Visits</a></li>
            <li><a href="staff_patients.php" class="nav-link">👥 Patients</a></li>
            <li><a href="staff_health_records.php" class="nav-link active">📝 Health Records</a></li>
            
            <div class="sidebar-section-label">Reporting</div>
            <li><a href="staff_reports.php" class="nav-link">📈 Reports</a></li>
            
            <li><a href="../auth/logout.php" class="nav-link">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h1 class="header-title">📝 Health Records</h1>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search records...">
                </div>

                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>

                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo substr(htmlspecialchars($user['name']), 0, 12); ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Staff</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="page-header">
                <h1>Health Records Management</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- STATS GRID -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Records</div>
                    <div class="stat-value"><?php echo $total_records; ?></div>
                </div>
            </div>

            <!-- FORM -->
            <div class="form-card">
                <h2><?php echo $edit_record ? 'Edit Health Record' : 'Add New Health Record'; ?></h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php 
                                    $students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name ASC");
                                    while ($student = $students->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                        <?php echo ($edit_record && $edit_record['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name']) . ' (' . $student['student_no'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Blood Type</label>
                            <input type="text" name="blood_type" value="<?php echo $edit_record ? htmlspecialchars($edit_record['blood_type']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Allergies</label>
                        <textarea name="allergies"><?php echo $edit_record ? htmlspecialchars($edit_record['allergies']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Chronic Conditions</label>
                        <textarea name="chronic_conditions"><?php echo $edit_record ? htmlspecialchars($edit_record['chronic_conditions'] ?? ($edit_record['conditions'] ?? '')) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Medications</label>
                        <textarea name="medications"><?php echo $edit_record ? htmlspecialchars($edit_record['medications']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes</label>
                        <textarea name="notes"><?php echo $edit_record ? htmlspecialchars($edit_record['notes']) : ''; ?></textarea>
                    </div>
                    <?php if ($edit_record): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_record['id']; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn">✅ <?php echo $edit_record ? 'Update' : 'Add'; ?> Record</button>
                </form>
            </div>

            <!-- TABLE -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Blood Type</th>
                            <th>Allergies</th>
                            <th>Chronic Conditions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($records && $records->num_rows > 0): ?>
                            <?php while ($record = $records->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['blood_type']); ?></td>
                                    <td><?php echo htmlspecialchars(substr((string)($record['allergies'] ?? ''), 0, 30)); ?></td>
                                    <td><?php echo htmlspecialchars(substr((string)($record['chronic_conditions'] ?? ($record['conditions'] ?? '')), 0, 30)); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="?edit=<?php echo $record['id']; ?>" class="action-btn action-edit">Edit</a>
                                            <a href="?delete=<?php echo $record['id']; ?>" class="action-btn action-delete" onclick="return confirm('Delete?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-light);">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const sidebar = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const menuToggle = document.getElementById('menuToggle');

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            themeToggle.textContent = '☀️';
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            themeToggle.textContent = isDark ? '☀️' : '🌙';
        });

        // Menu toggle for mobile
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('sidebar-collapsed');
        });

        // Add active navigation indicator
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            if (link.getAttribute('href') === 'staff_health_records.php') {
                link.classList.add('active');
            }
        });
    </script>
    <?php include('../includes/footer.php'); ?>

