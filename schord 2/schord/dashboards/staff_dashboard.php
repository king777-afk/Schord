<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if staff role - STAFF ONLY
if (strtolower($user['role']) !== 'staff') {
    if (strtolower($user['role']) === 'admin') {
        header("Location: dashboard_admin.php");
    } elseif (strtolower($user['role']) === 'nurse') {
        header("Location: nurse_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
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
                $success = '✅ Patient added successfully! The dashboard has been updated.';
                $_POST = [];
            } else {
                $error = '❌ Error adding patient';
                // Log DB error for troubleshooting
                @mkdir(__DIR__ . '/../logs', 0777, true);
                $logMessage = date('Y-m-d H:i:s') . " | staff_dashboard.php INSERT ERROR: " . $conn->error . "\n";
                @file_put_contents(__DIR__ . '/../logs/db_errors.txt', $logMessage, FILE_APPEND);
                error_log('staff_dashboard.php INSERT ERROR: ' . $conn->error);
            }
        }
    }
}

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$total_records = $conn->query("SELECT COUNT(*) as count FROM health_records")->fetch_assoc()['count'];
$this_month_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())")->fetch_assoc()['count'];

// Get recent students (new admissions)
$recent_students = $conn->query("
    SELECT s.id, s.name, s.student_no, s.course, s.created_at
    FROM students s
    ORDER BY s.created_at DESC
    LIMIT 8
");

// Get recent visits
$recent_visits = $conn->query("
    SELECT cv.id, cv.visit_date, cv.complaint, cv.status, s.name as student_name, s.student_no
    FROM clinic_visits cv
    JOIN students s ON cv.student_id = s.id
    ORDER BY cv.visit_date DESC
    LIMIT 8
");

// Get health records with critical info
$health_overview = $conn->query("
    SELECT COUNT(*) as total_records, 
           SUM(CASE WHEN blood_pressure IS NOT NULL THEN 1 ELSE 0 END) as records_with_bp
    FROM health_records
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - SCHoRD Clinical Health Records</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --shadow-glow: 0 0 60px rgba(16, 185, 129, 0.15);
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
            --text-dark: #e0f2fe;
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
            border-right: 1px solid rgba(16, 185, 129, 0.15);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.2);
            scrollbar-width: thin;
            scrollbar-color: rgba(16, 185, 129, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(16, 185, 129, 0.2);
            border-radius: 10px;
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.05) 100%);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
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
            background: rgba(16, 185, 129, 0.1);
            transition: width 0.3s ease;
            pointer-events: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(16, 185, 129, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a:hover::before,
        .sidebar-nav a.active::before {
            width: 20px;
        }

        .sidebar-nav a.active {
            border-left-color: var(--accent);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }

        .sidebar-nav .icon {
            font-size: 18px;
            min-width: 20px;
            filter: drop-shadow(0 2px 4px rgba(16, 185, 129, 0.2));
        }

        .sidebar-section {
            margin-top: 30px;
            border-top: 1px solid rgba(16, 185, 129, 0.15);
            padding-top: 15px;
        }

        .sidebar-section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            margin: 0 20px 10px 20px;
            letter-spacing: 1px;
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
            background: rgba(6, 78, 59, 0.5);
            border-bottom-color: rgba(16, 185, 129, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            min-width: 0;
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
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
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

        .search-box {
            display: flex;
            align-items: center;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            padding: 10px 16px;
            gap: 10px;
            min-width: 300px;
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.15);
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
            border-color: var(--primary);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: rgba(16, 185, 129, 0.08);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            background: rgba(16, 185, 129, 0.15);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        /* ===== MAIN CONTENT AREA ===== */
        .main-content {
            padding: 35px;
            background: linear-gradient(135deg, var(--bg-lighter) 0%, var(--bg-light) 100%);
        }

        body.dark-mode .main-content {
            background: linear-gradient(135deg, #022c1d 0%, #064e3b 100%);
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(16, 185, 129, 0.1);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.15);
        }

        .welcome-text {
            position: relative;
            z-index: 1;
        }

        .welcome-text h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .welcome-text p {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
        }

        .export-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25);
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.35);
        }

        .export-btn:active {
            transform: translateY(0);
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
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15), transparent);
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
            background: radial-gradient(circle, rgba(52, 211, 153, 0.1), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .stat-card.success { --stat-color: var(--success); }
        .stat-card.warning { --stat-color: var(--warning); }
        .stat-card.danger { --stat-color: var(--danger); }
        .stat-card.info { --stat-color: var(--info); }

        .stat-icon {
            font-size: 42px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--stat-color, var(--primary));
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
            letter-spacing: -1px;
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

        .stat-trend {
            font-size: 12px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--success);
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        body.dark-mode .stat-trend {
            border-top-color: rgba(255, 255, 255, 0.05);
        }

        /* ===== ACTION CARDS GRID ===== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }

        .action-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 22px 18px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .action-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.1);
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: inherit;
            z-index: 0;
        }

        .action-card:hover {
            border-color: transparent;
            color: white;
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.3);
        }

        .action-card:hover::before {
            opacity: 1;
        }

        .action-card:active {
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 34px;
            display: block;
            position: relative;
            z-index: 1;
        }

        .action-label {
            font-size: 14px;
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        /* ===== DATA SECTION ===== */
        .data-section {
            margin-bottom: 35px;
        }

        .section-header {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary);
        }

        .section-header::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 2px;
        }

        /* ===== TABLE STYLES ===== */
        .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
        }

        body.dark-mode .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.1);
        }

        .form-panel {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.72) 100%);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.8);
            margin-bottom: 35px;
        }

        body.dark-mode .form-panel {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.84) 100%);
            border-color: rgba(16, 185, 129, 0.1);
        }

        .form-panel-header {
            margin-bottom: 18px;
        }

        .form-panel-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.4px;
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
            border: 1px solid rgba(16, 185, 129, 0.18);
            padding: 13px 14px;
            background: rgba(255, 255, 255, 0.75);
            color: var(--text-dark);
            outline: none;
            transition: all 0.25s ease;
        }

        body.dark-mode .form-group input {
            background: rgba(2, 44, 29, 0.5);
            border-color: rgba(16, 185, 129, 0.22);
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
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
            box-shadow: 0 14px 30px rgba(16, 185, 129, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(16, 185, 129, 0.28);
        }

        .form-note {
            color: var(--text-light);
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.05));
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode thead {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(52, 211, 153, 0.08));
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
            background: rgba(16, 185, 129, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-ongoing {
            background: rgba(59, 130, 245, 0.1);
            color: #2563eb;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        /* ===== INFO CARDS ===== */
        .info-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.3) 100%);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateX(4px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15);
        }

        .info-card strong {
            color: var(--text-dark);
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-card p {
            font-size: 13px;
            color: var(--text-light);
            margin: 6px 0;
        }

        /* ===== DATA SECTION ===== */
        .data-section {
            margin-bottom: 35px;
        }

        .section-header {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary);
        }

        .section-header::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 2px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

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

            .welcome-section {
                padding: 20px;
                gap: 15px;
                border-radius: 12px;
                flex-direction: column;
            }

            .welcome-section::before {
                display: none;
            }

            .welcome-text h2 {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .search-box {
                max-width: 100%;
                min-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
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

            .stat-icon {
                font-size: 32px;
            }

            .main-content {
                padding: 15px;
            }

            .header-title {
                font-size: 18px;
            }

            .export-btn {
                padding: 10px 16px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">👨‍⚕️</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
            <div class="sidebar-section-label">Main</div>
            <li><a class="sidebar-link active" href="staff_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            
            <div class="sidebar-section-label">Operation</div>
            <li><a class="sidebar-link" href="../pages/staff_visits.php"><span class="icon">📋</span> Clinic Visits</a></li>
            <li><a class="sidebar-link" href="../pages/staff_patients.php"><span class="icon">👥</span> Patients</a></li>
            <li><a class="sidebar-link" href="../pages/staff_health_records.php"><span class="icon">📝</span> Health Records</a></li>
            
            <div class="sidebar-section-label">Reporting</div>
            <li><a class="sidebar-link" href="../pages/staff_reports.php"><span class="icon">📈</span> Reports</a></li>
            
            <li class="sidebar-section">
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
                <h2 class="header-title">Staff Dashboard</h2>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search students, records...">
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
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- WELCOME SECTION -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>! 🏥</h2>
                    <p>Manage clinic operations and patient records efficiently</p>
                </div>
                <button class="export-btn" onclick="exportData()" title="Export Report">📥 Export Report</button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- KEY METRICS -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Registered Patients</div>
                    <div class="stat-trend">📚 Total in system</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">📋</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                    <div class="stat-label">All Clinic Visits</div>
                    <div class="stat-trend">✓ Recorded</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?php echo $this_month_visits; ?></div>
                    <div class="stat-label">This Month</div>
                    <div class="stat-trend">📊 Current period</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-icon">📄</div>
                    <div class="stat-value"><?php echo $health_overview['total_records']; ?></div>
                    <div class="stat-label">Health Records</div>
                    <div class="stat-trend">📑 Complete</div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="section-header">🚀 Quick Actions</div>
            <div class="actions-grid">
                <a href="../pages/staff_visits.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <span class="action-label">Log Visit</span>
                </a>
                <a href="#addPatientForm" class="action-card">
                    <span class="action-icon">🩺</span>
                    <span class="action-label">Add Patient</span>
                </a>
                <a href="../pages/staff_health_records.php" class="action-card">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Update Record</span>
                </a>
                <a href="../pages/staff_patients.php" class="action-card">
                    <span class="action-icon">🏥</span>
                    <span class="action-label">Check Patient</span>
                </a>
                <a href="../pages/staff_reports.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <span class="action-label">View Reports</span>
                </a>
            </div>

            <div class="form-panel" id="addPatientForm">
                <div class="form-panel-header">
                    <div class="form-panel-title">Add Student / Patient</div>
                    <div class="form-panel-subtitle">Saved directly to the students table so the dashboard, patients list, and reports update automatically.</div>
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
                        <span class="form-note">New entries appear immediately in the recent patients table and total patient count.</span>
                    </div>
                </form>
            </div>

            <!-- RECENT VISITS -->
            <div class="data-section">
                <div class="section-header">🏥 Recent Clinic Visits</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>📅 Date/Time</th>
                                <th>👤 Patient Name</th>
                                <th>🆔 ID</th>
                                <th>📝 Complaint</th>
                                <th>📊 Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($recent_visits && $recent_visits->num_rows > 0):
                                while ($visit = $recent_visits->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?php echo date('M d, H:i', strtotime($visit['visit_date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($visit['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($visit['student_no']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['complaint'] ?? 'N/A', 0, 30)); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($visit['status'] ?? 'pending'); ?>"><?php echo ucfirst($visit['status'] ?? 'Pending'); ?></span></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 30px;">No recent visits</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RECENT PATIENTS -->
            <div class="data-section">
                <div class="section-header">👥 Recent Patient Registrations</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>👤 Patient Name</th>
                                <th>🆔 Registration ID</th>
                                <th>📚 Course</th>
                                <th>📅 Registered</th>
                                <th>📊 Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($recent_students && $recent_students->num_rows > 0):
                                while ($student = $recent_students->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['student_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course'] ?? '—'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'], 0)); ?></td>
                                    <td><span class="status-badge status-completed">Active</span></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 30px;">No recent registrations</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- HEALTH OVERVIEW -->
            <div class="data-section">
                <div class="section-header">⚕️ Clinical Data Overview</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 18px;">
                    <div class="info-card">
                        <strong>📄 Total Health Records</strong>
                        <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 10px;"><?php echo $health_overview['total_records']; ?></p>
                        <p>Complete health documentation on file</p>
                    </div>
                    <div class="info-card">
                        <strong>❤️ Vital Signs Recorded</strong>
                        <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 10px;"><?php echo $health_overview['records_with_bp']; ?></p>
                        <p>Patients with vital signs data</p>
                    </div>
                    <div class="info-card">
                        <strong>📋 Data Completeness</strong>
                        <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 10px;">
                            <?php 
                            $percentage = $health_overview['total_records'] > 0 
                                ? round(($health_overview['records_with_bp'] / $health_overview['total_records']) * 100) 
                                : 0;
                            echo $percentage . '%';
                            ?>
                        </p>
                        <p>Records with complete vital signs</p>
                    </div>
                </div>
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
                if (linkHref === currentPage || (currentPage === '' && linkHref === 'staff_dashboard.php')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Add search functionality
            const searchInput = document.querySelector('.search-box input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    performSearch(this.value);
                });
            }

            // Add data-searchable attribute to all tables and cards
            document.querySelectorAll('table, .stat-card, .action-card, .info-card').forEach(el => {
                el.setAttribute('data-searchable', 'true');
            });
        });

        // Menu Toggle with error handling
        const menuToggleBtn = document.getElementById('menuToggle');
        if (menuToggleBtn) {
            menuToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const sidebar = document.getElementById('sidebar');
                const mainWrapper = document.getElementById('mainWrapper');
                if (sidebar) sidebar.classList.toggle('collapsed');
                if (mainWrapper) mainWrapper.classList.toggle('sidebar-collapsed');
            });
        }

        // Theme Toggle with error handling
        const themeToggleBtn = document.getElementById('themeToggle');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                const isDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                this.textContent = isDark ? '☀️' : '🌙';
            });
        }

        // Load theme preference on page load
        window.addEventListener('load', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                const themeToggle = document.getElementById('themeToggle');
                if (themeToggle) themeToggle.textContent = '☀️';
            }
        });

        // Export Data Function
        function exportData() {
            try {
                const timestamp = new Date().toLocaleString();
                const csvContent = [
                    ['SCHoRD Staff Dashboard Report'],
                    ['Generated:', timestamp],
                    [''],
                    ['Metric', 'Value'],
                    ['Total Students', '<?php echo $total_students; ?>'],
                    ['Total Clinic Visits', '<?php echo $total_visits; ?>'],
                    ['This Month Visits', '<?php echo $this_month_visits; ?>'],
                    ['Health Records', '<?php echo $health_overview['total_records']; ?>']
                ].map(row => row.map(cell => '"' + String(cell).replace(/"/g, '""') + '"').join(',')).join('\n');

                const BOM = '\uFEFF';
                const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.setAttribute('href', URL.createObjectURL(blob));
                link.setAttribute('download', `SCHoRD-Staff-Report-${new Date().getTime()}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showNotification('✅ Report exported successfully!', 'success');
            } catch (error) {
                showNotification('❌ Error exporting report: ' + error.message, 'error');
                console.error('Export error:', error);
            }
        }

        // Search Function
        function performSearch(query) {
            const lowerQuery = query.toLowerCase();
            if (!query.trim()) {
                document.querySelectorAll('[data-searchable="true"]').forEach(el => {
                    el.style.display = '';
                    el.style.opacity = '1';
                    el.style.borderLeft = '';
                    el.style.boxShadow = '';
                });
                return;
            }

            let matchCount = 0;
            document.querySelectorAll('[data-searchable="true"]').forEach(el => {
                const text = el.innerText.toLowerCase();
                if (text.includes(lowerQuery)) {
                    el.style.display = '';
                    el.style.opacity = '1';
                    el.style.borderLeft = '4px solid #10b981';
                    el.style.boxShadow = '0 0 20px rgba(16, 185, 129, 0.3)';
                    matchCount++;
                } else {
                    el.style.opacity = '0.3';
                }
            });
            console.log(`Search: Found ${matchCount} matches for "${query}"`);
        }

        // Notification Helper with proper styling
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' :
                           type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' :
                           'linear-gradient(135deg, #3b82f6, #2563eb)';
            const shadow = type === 'error' ? '0 8px 24px rgba(239, 68, 68, 0.3)' :
                          'rgba(16, 185, 129, 0.3)';
                          
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 24px ${shadow};
                z-index: 9999;
                animation: slideInRight 0.3s ease;
                font-weight: 600;
                font-size: 14px;
                max-width: 350px;
                word-wrap: break-word;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
