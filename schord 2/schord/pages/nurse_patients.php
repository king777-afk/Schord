<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Only nurses can access this page
if (strtolower($user['role']) !== 'nurse') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $student_no = sanitize($_POST['student_no'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $course = sanitize($_POST['course'] ?? '');
    $age = sanitize($_POST['age'] ?? '');

    if ($student_no === '' || $name === '' || $course === '' || $age === '') {
        $error = '❌ All fields are required';
    } elseif (!is_numeric($age) || (int)$age < 1 || (int)$age > 100) {
        $error = '❌ Age must be between 1 and 100';
    } else {
        $check = $conn->query("SELECT id FROM students WHERE student_no='$student_no'");
        if ($check && $check->num_rows > 0) {
            $error = '❌ Student number already exists';
        } else {
            $ageValue = (int)$age;
            if ($conn->query("INSERT INTO students (student_no, name, course, age) VALUES ('$student_no', '$name', '$course', $ageValue)")) {
                $success = '✅ Patient added successfully!';
                $_POST = [];
                header("Location: nurse_patients.php?added=1");
                exit();
            } else {
                $error = '❌ Error adding patient';
                // Log detailed DB error for troubleshooting on local XAMPP
                @mkdir(__DIR__ . '/../logs', 0777, true);
                $logMessage = date('Y-m-d H:i:s') . " | nurse_patients.php INSERT ERROR: " . $conn->error . "\n";
                @file_put_contents(__DIR__ . '/../logs/db_errors.txt', $logMessage, FILE_APPEND);
                error_log('nurse_patients.php INSERT ERROR: ' . $conn->error);
            }
        }
    }
}

if (isset($_GET['added'])) {
    $success = '✅ Patient added successfully!';
}
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get all patients with their latest health records
if ($search) {
    $patients = $conn->query("
         SELECT s.*, 
             hr.blood_pressure, hr.temperature, hr.weight, hr.allergies,
               COUNT(cv.id) as total_visits
        FROM students s 
        LEFT JOIN health_records hr ON s.id = hr.student_id 
        LEFT JOIN clinic_visits cv ON s.id = cv.student_id
        WHERE s.name LIKE '%$search%' OR s.student_no LIKE '%$search%' OR s.course LIKE '%$search%'
        GROUP BY s.id
        ORDER BY s.name ASC
    ");
} else {
    $patients = $conn->query("
        SELECT s.*, 
               hr.blood_pressure, hr.temperature, hr.weight, hr.allergies,
               COUNT(cv.id) as total_visits
        FROM students s 
        LEFT JOIN health_records hr ON s.id = hr.student_id 
        LEFT JOIN clinic_visits cv ON s.id = cv.student_id
        GROUP BY s.id
        ORDER BY s.name ASC
    ");
}

if ($patients === false) {
    $error = 'Failed to load patient records.';
    error_log('nurse_patients.php query error: ' . $conn->error);
}

$total_patients = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Management - Nurse - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0891b2;
            --primary-light: #06b6d4;
            --primary-dark: #0e7490;
            --accent: #00d9ff;
            --accent-light: #22d3ee;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #082f49;
            --darker: #051e2a;
            --text-dark: #0c2340;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg-light: #ecf9ff;
            --bg-lighter: #cff0f9;
            --bg-card: #ffffff;
            --bg-hover: #f0f9ff;
            --border-color: #e0f2fe;
            --shadow: 0 10px 40px rgba(8, 47, 73, 0.1);
            --shadow-lg: 0 20px 60px rgba(8, 47, 73, 0.15);
            --shadow-glow: 0 0 60px rgba(8, 145, 178, 0.15);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #ecf9ff 0%, #cff0f9 50%, #e0f9ff 100%);
            color: var(--text-dark);
            min-height: 100vh;
            letter-spacing: -0.3px;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #051e2a 0%, #082f49 50%, #0f3d52 100%);
            --bg-light: #164e63;
            --bg-lighter: #0f3d52;
            --bg-card: #082f49;
            --bg-hover: #0f4555;
            --border-color: #0e7490;
            --text-dark: #ecf9ff;
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
            border-right: 1px solid rgba(6, 182, 212, 0.1);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.2);
            scrollbar-width: thin;
            scrollbar-color: rgba(6, 182, 212, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(6, 182, 212, 0.2);
            border-radius: 10px;
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(6, 182, 212, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(0, 217, 255, 0.05) 100%);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #06b6d4 0%, #00d9ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo {
            font-size: 28px;
            filter: drop-shadow(0 4px 10px rgba(6, 182, 212, 0.3));
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
            cursor: pointer;
            border: none;
            background: none;
            text-align: left;
            width: 100%;
            font-size: 15px;
            position: relative;
            border-left: 3px solid transparent;
            pointer-events: auto;
            z-index: 10;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 0;
            background: rgba(6, 182, 212, 0.1);
            transition: width 0.3s ease;
            pointer-events: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(6, 182, 212, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a:hover::before,
        .sidebar-nav a.active::before {
            width: 20px;
        }

        .sidebar-nav a.active {
            border-left-color: var(--accent);
            text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
        }

        .sidebar-nav .icon {
            font-size: 18px;
            min-width: 20px;
            filter: drop-shadow(0 2px 4px rgba(6, 182, 212, 0.2));
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
            -webkit-backdrop-filter: blur(10px);
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
            background: rgba(8, 47, 73, 0.5);
            border-bottom-color: rgba(6, 182, 212, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-dark);
        }

        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 10px;
            padding: 10px 16px;
            gap: 10px;
            min-width: 300px;
        }

        .search-box input {
            border: none;
            background: none;
            outline: none;
            flex: 1;
            color: var(--text-dark);
            font-size: 14px;
        }

        .search-box input::placeholder {
            color: var(--text-light);
        }

        .theme-toggle {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
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
            border-color: var(--primary);
        }

        .alert {
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 22px;
            font-weight: 600;
            border: 1px solid transparent;
            box-shadow: var(--shadow);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.25);
            color: var(--primary-dark);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.10);
            border-color: rgba(239, 68, 68, 0.20);
            color: #b91c1c;
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
            letter-spacing: -0.5px;
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
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.15), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(0, 217, 255, 0.1), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(6, 182, 212, 0.2);
            border-color: rgba(6, 182, 212, 0.2);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--primary);
            margin: 12px 0 0 0;
            position: relative;
            z-index: 1;
            letter-spacing: -1px;
        }

        /* ===== TABLE STYLES ===== */
        .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            margin-top: 20px;
        }

        .form-panel {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.72) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 24px;
            margin: 20px 0 35px;
        }

        body.dark-mode .form-panel {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.1);
        }

        .form-panel-header {
            margin-bottom: 18px;
        }

        .form-panel-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .form-panel-subtitle {
            margin-top: 6px;
            color: var(--text-light);
            font-size: 14px;
        }

        .patient-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: 0.2px;
        }

        .form-group input {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(6, 182, 212, 0.18);
            padding: 13px 14px;
            background: rgba(255, 255, 255, 0.75);
            color: var(--text-dark);
            outline: none;
            transition: all 0.25s ease;
        }

        body.dark-mode .form-group input {
            background: rgba(8, 47, 73, 0.5);
            border-color: rgba(6, 182, 212, 0.22);
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.12);
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .submit-btn {
            border: none;
            border-radius: 12px;
            padding: 13px 18px;
            font-weight: 800;
            cursor: pointer;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            box-shadow: 0 14px 30px rgba(6, 182, 212, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(6, 182, 212, 0.28);
        }

        .form-note {
            color: var(--text-light);
            font-size: 13px;
        }

        body.dark-mode .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(0, 217, 255, 0.05));
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode thead {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(0, 217, 255, 0.08));
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            letter-spacing: 0.5px;
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
            background: rgba(6, 182, 212, 0.05);
        }

        .btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
        }

        .vital-badge {
            display: inline-block;
            background: rgba(8, 145, 178, 0.1);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .alert-badge {
            display: inline-block;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 1024px) {
            .main-content {
                padding: 25px;
            }

            .search-box {
                min-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .menu-toggle {
                display: flex;
            }

            .main-content {
                padding: 20px;
            }

            .top-header {
                padding: 15px 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .search-box {
                max-width: 100%;
                min-width: 200px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header-right {
                gap: 10px;
            }

            .search-box {
                display: none;
            }

            .top-header {
                flex-wrap: wrap;
            }

            .stat-value {
                font-size: 28px;
            }

            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">👩‍⚕️</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
            <li><a class="sidebar-link" href="../dashboards/nurse_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a class="sidebar-link active" href="nurse_patients.php"><span class="icon">👥</span> Patients</a></li>
            <li><a class="sidebar-link" href="nurse_visits.php"><span class="icon">📋</span> Visits</a></li>
            <li><a class="sidebar-link" href="nurse_health_records.php"><span class="icon">❤️</span> Health Records</a></li>
            <li><a class="sidebar-link" href="nurse_reports.php"><span class="icon">📈</span> Reports</a></li>
            <li style="margin-top: 30px; border-top: 1px solid rgba(6, 182, 212, 0.15); padding-top: 20px;">
                <a class="sidebar-link" href="../auth/logout.php"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h2 class="header-title">👥 Patients Management</h2>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <span>🔍</span>
                    <form method="GET" style="display: flex; flex: 1; gap: 0;">
                        <input type="text" name="search" placeholder="Search patients..." value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </div>

                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>Patient Records</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="form-panel" id="addPatientForm">
                <div class="form-panel-header">
                    <div class="form-panel-title">Add New Patient</div>
                    <div class="form-panel-subtitle">Saved directly to the shared students table and reflected across the dashboard, visits, and health records.</div>
                </div>

                <form method="POST" class="patient-form" autocomplete="off">
                    <input type="hidden" name="add_patient" value="1">

                    <div class="form-group">
                        <label for="student_no">Student Number</label>
                        <input type="text" id="student_no" name="student_no" value="<?php echo htmlspecialchars($_POST['student_no'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="course">Course</label>
                        <input type="text" id="course" name="course" value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="1" max="100" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Save Patient</button>
                        <span class="form-note">New patients will immediately appear in the table below.</span>
                    </div>
                </form>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                </div>
            </div>

            <!-- PATIENTS TABLE -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>👤 Patient Name</th>
                            <th>📋 ID</th>
                            <th>📚 Course</th>
                            <th>🎂 Age</th>
                            <th>💓 BP</th>
                            <th>🌡️ Temp</th>
                            <th>⚠️ Allergies</th>
                            <th>📊 Visits</th>
                            <th>🔧 Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($patients && $patients->num_rows > 0): ?>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($patient['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($patient['student_no']); ?></td>
                                <td><?php echo htmlspecialchars($patient['course']); ?></td>
                                <td><?php echo $patient['age']; ?></td>
                                <td>
                                    <?php if ($patient['blood_pressure']): ?>
                                        <span class="vital-badge"><?php echo htmlspecialchars($patient['blood_pressure']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patient['temperature']): ?>
                                        <span class="vital-badge"><?php echo htmlspecialchars($patient['temperature']); ?>°</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patient['allergies']): ?>
                                        <span class="alert-badge">⚠️ Yes</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $patient['total_visits'] ?? 0; ?></strong></td>
                                <td>
                                    <a href="nurse_health_records.php?student=<?php echo $patient['id']; ?>" class="btn">View Records</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: var(--text-light);">
                                    <?php echo htmlspecialchars($error ?: 'No patients found.'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Set active nav item based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.sidebar-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href').split('/').pop();
                if (linkHref === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });

        // Menu Toggle
        document.getElementById('menuToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed');
        });

        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            this.textContent = isDark ? '☀️' : '🌙';
        });

        // Load theme preference
        window.addEventListener('load', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('themeToggle').textContent = '☀️';
            }
        });
    </script>
</body>
</html>
