<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if nurse role - NURSE ONLY
if (strtolower($user['role']) !== 'nurse') {
    if (strtolower($user['role']) === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Get statistics with safe error handling
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$total_patients = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE DATE(visit_date) = CURDATE()");
$today_visits = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='pending'");
$pending_visits = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM health_records WHERE blood_pressure IS NOT NULL");
$critical_patients = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['count'] : 0;

// Get today's appointments
$today_appointments = $conn->query("
    SELECT cv.*, s.name as student_name, s.student_no as admission_number 
    FROM clinic_visits cv 
    JOIN students s ON cv.student_id = s.id 
    WHERE DATE(cv.visit_date) = CURDATE() 
    ORDER BY cv.visit_date DESC 
    LIMIT 8
");

// Get recent patients
$recent_patients = $conn->query("
    SELECT s.*, hr.blood_pressure, hr.temperature, hr.weight 
    FROM students s 
    LEFT JOIN health_records hr ON s.id = hr.student_id 
    ORDER BY s.id DESC 
    LIMIT 5
");

// Get health alerts
$health_alerts = $conn->query("
    SELECT s.name, s.student_no as admission_number, hr.allergies, hr.conditions as medical_conditions 
    FROM students s 
    LEFT JOIN health_records hr ON s.id = hr.student_id 
    WHERE hr.allergies IS NOT NULL OR hr.conditions IS NOT NULL 
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - SCHoRD Clinical Health Records</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: rgba(6, 182, 212, 0.08);
            border-radius: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        /* ===== MAIN CONTENT AREA ===== */
        .main-content {
            padding: 35px;
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
            border: 1px solid rgba(6, 182, 212, 0.1);
            box-shadow: var(--shadow);
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.15);
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
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
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
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.1);
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
            box-shadow: 0 12px 32px rgba(6, 182, 212, 0.3);
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
            box-shadow: 0 12px 40px rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
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

        /* ===== ALERT CARDS ===== */
        .alert-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.3) 100%);
            border-left: 4px solid var(--danger);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
        }

        .alert-card strong {
            color: var(--text-dark);
            display: block;
            margin-bottom: 8px;
        }

        .alert-card p {
            font-size: 13px;
            color: var(--text-light);
            margin: 6px 0;
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
            <li><a class="sidebar-link active" href="nurse_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a class="sidebar-link" href="../pages/nurse_patients.php"><span class="icon">👥</span> Patients</a></li>
            <li><a class="sidebar-link" href="../pages/nurse_visits.php"><span class="icon">📋</span> Visits</a></li>
            <li><a class="sidebar-link" href="../pages/nurse_schedule.php"><span class="icon">📅</span> Schedules</a></li>
            <li><a class="sidebar-link" href="../pages/nurse_health_records.php"><span class="icon">❤️</span> Health Records</a></li>
            <li><a class="sidebar-link" href="../pages/nurse_reports.php"><span class="icon">📈</span> Reports</a></li>
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
                <h2 class="header-title">Nurse Dashboard</h2>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search patients, records...">
                </div>

                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>

                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo substr(htmlspecialchars($user['name']), 0, 12); ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Nurse</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- WELCOME SECTION -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h2>
                    <p>Manage patient care and clinical operations efficiently</p>
                </div>
                <button class="export-btn" onclick="exportData()" title="Export Report">📥 Export Report</button>
            </div>

            <!-- KEY METRICS -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-trend">↑ Active monitoring</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?php echo $today_visits; ?></div>
                    <div class="stat-label">Today's Visits</div>
                    <div class="stat-trend">✓ Recorded today</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $pending_visits; ?></div>
                    <div class="stat-label">Pending Visits</div>
                    <div class="stat-trend">📌 Needs attention</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-value"><?php echo $critical_patients; ?></div>
                    <div class="stat-label">Under Care</div>
                    <div class="stat-trend">🔍 Active records</div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="section-header">🚀 Quick Actions</div>
            <div class="actions-grid">
                <a href="../pages/nurse_visits.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <span class="action-label">New Visit</span>
                </a>
                <a href="../pages/nurse_health_records.php" class="action-card">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Record Vitals</span>
                </a>
                <a href="../pages/nurse_patients.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <span class="action-label">Add Patient</span>
                </a>
                <a href="../pages/nurse_reports.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <span class="action-label">View Reports</span>
                </a>
            </div>

            <!-- TODAY'S SCHEDULE -->
            <div class="data-section">
                <div class="section-header">📅 Today's Schedule</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>⏱️ Time</th>
                                <th>👤 Patient Name</th>
                                <th>🆔 ID</th>
                                <th>📋 Complaint</th>
                                <th>💊 Treatment</th>
                                <th>📊 Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($today_appointments && $today_appointments->num_rows > 0):
                                while ($visit = $today_appointments->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?php echo date('H:i', strtotime($visit['visit_date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($visit['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($visit['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['complaint'] ?? 'N/A', 0, 25)); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['treatment'] ?? 'N/A', 0, 20)); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($visit['status'] ?? 'pending'); ?>"><?php echo ucfirst($visit['status'] ?? 'Pending'); ?></span></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">No appointments scheduled for today</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PATIENT ALERTS -->
            <div class="data-section">
                <div class="section-header">⚠️ Patient Alerts & Medical History</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <?php 
                    $alert_count = 0;
                    if ($health_alerts && $health_alerts->num_rows > 0):
                        while ($alert = $health_alerts->fetch_assoc()): 
                            if ($alert_count >= 6) break;
                            $alert_count++;
                    ?>
                        <div class="alert-card">
                            <strong>👤 <?php echo htmlspecialchars($alert['name'] ?? 'Unknown'); ?></strong>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($alert['admission_number'] ?? 'N/A'); ?></p>
                            <?php if(!empty($alert['allergies'])): ?>
                                <p><strong>🚫 Allergies:</strong> <?php echo htmlspecialchars($alert['allergies']); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($alert['medical_conditions'])): ?>
                                <p><strong>⚕️ Conditions:</strong> <?php echo htmlspecialchars($alert['medical_conditions']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: rgba(16, 185, 129, 0.05); border-radius: 12px;">
                            <p style="color: var(--text-light);">✓ No critical alerts - All patients stable</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RECENT PATIENTS -->
            <div class="data-section">
                <div class="section-header">👥 Recent Patients Under Care</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>👤 Patient Name</th>
                                <th>🆔 Admission ID</th>
                                <th>❤️ BP (Blood Pressure)</th>
                                <th>🌡️ Temperature</th>
                                <th>⚖️ Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($recent_patients && $recent_patients->num_rows > 0):
                                while ($patient = $recent_patients->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($patient['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($patient['student_no']); ?></td>
                                    <td><?php echo $patient['blood_pressure'] ? htmlspecialchars($patient['blood_pressure']) : '—'; ?></td>
                                    <td><?php echo $patient['temperature'] ? htmlspecialchars($patient['temperature']) . '°C' : '—'; ?></td>
                                    <td><?php echo $patient['weight'] ? htmlspecialchars($patient['weight']) . ' kg' : '—'; ?></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 30px;">No recent patients</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                if (linkHref === currentPage || (currentPage === '' && linkHref === 'nurse_dashboard.php')) {
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

        // Export Data Function
        function exportData() {
            const timestamp = new Date().toLocaleString();
            const csvContent = [
                ['SCHoRD Nurse Dashboard Report'],
                ['Generated:', timestamp],
                [''],
                ['Metric', 'Value'],
                ['Total Patients', '<?php echo $total_patients; ?>'],
                ['Today\'s Visits', '<?php echo $today_visits; ?>'],
                ['Pending Visits', '<?php echo $pending_visits; ?>'],
                ['Patients Under Care', '<?php echo $critical_patients; ?>']
            ].map(row => row.join(',')).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', `SCHoRD-Nurse-Report-${new Date().getTime()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification('📥 Report exported successfully!', 'success');
        }

        // Notification Helper
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #0891b2, #06b6d4);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
                z-index: 9999;
                animation: slideInRight 0.3s ease;
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
