<?php
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

// Get comprehensive statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$ongoing_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$completed_visits = $total_visits - $ongoing_visits;
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_staff = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN('staff', 'nurse')")->fetch_assoc()['count'];
$total_admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];

// Get visits this month
$visits_today = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE DATE(visit_date) = CURDATE()")->fetch_assoc()['count'];
$visits_this_month = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())")->fetch_assoc()['count'];

// Get user role distribution for pie chart
$role_distribution = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles = [];
$role_counts = [];
while ($row = $role_distribution->fetch_assoc()) {
    $roles[] = ucfirst($row['role']);
    $role_counts[] = $row['count'];
}

// Get visits by day for line chart
$visits_by_day = $conn->query("
    SELECT DATE(visit_date) as day, COUNT(*) as count
    FROM clinic_visits
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(visit_date)
    ORDER BY day ASC
");

$chart_days = [];
$chart_counts = [];
while ($row = $visits_by_day->fetch_assoc()) {
    $chart_days[] = date('M d', strtotime($row['day']));
    $chart_counts[] = $row['count'];
}

// Get complaint statistics
$complaint_stats = $conn->query("
    SELECT complaint, COUNT(*) as count
    FROM clinic_visits
    GROUP BY complaint
    ORDER BY count DESC
    LIMIT 6
");

$complaint_names = [];
$complaint_counts = [];
while ($row = $complaint_stats->fetch_assoc()) {
    $complaint_names[] = substr($row['complaint'], 0, 20);
    $complaint_counts[] = $row['count'];
}

// Recent activities
$recent_activities = $conn->query("
    SELECT 'New Visit' as type, CONCAT(s.name, ' - ', cv.complaint) as subject, cv.visit_date as date, '📝' as icon
    FROM clinic_visits cv JOIN students s ON cv.student_id = s.id
    UNION ALL
    SELECT 'New Student' as type, s.name as subject, s.created_at as date, '📚' as icon
    FROM students s
    ORDER BY date DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SCHoRD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --accent: #ec4899;
            --accent-light: #f472b6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #0f172a;
            --darker: #06111f;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg-light: #f8fafc;
            --bg-lighter: #f1f5f9;
            --bg-card: #ffffff;
            --bg-hover: #f3f4f6;
            --border-color: #e2e8f0;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.12);
            --shadow-glow: 0 0 60px rgba(99, 102, 241, 0.2);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3f2 100%);
            color: var(--text-dark);
            min-height: 100vh;
            letter-spacing: -0.3px;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #06111f 0%, #0f172a 50%, #1a1f3a 100%);
            --bg-light: #1e293b;
            --bg-lighter: #162e4a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --border-color: #334155;
            --text-dark: #f1f5f9;
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
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.2);
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(236, 72, 153, 0.05) 100%);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo {
            font-size: 28px;
            filter: drop-shadow(0 4px 10px rgba(99, 102, 241, 0.3));
        }

        .sidebar-nav {
            padding: 15px 0;
            list-style: none;
        }

        .sidebar-nav li {
            margin: 5px 0;
        }

        .sidebar-nav a,
        .sidebar-btn {
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

        .sidebar-nav a::before,
        .sidebar-btn::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 0;
            background: rgba(99, 102, 241, 0.1);
            transition: width 0.3s ease;
            pointer-events: none;
        }

        .sidebar-nav a:hover,
        .sidebar-btn:hover {
            color: white;
            background: rgba(99, 102, 241, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a:hover::before,
        .sidebar-btn:hover::before {
            width: 20px;
        }

        .sidebar-nav a.active,
        .sidebar-btn.active {
            color: var(--primary-light);
            background: rgba(99, 102, 241, 0.15);
            border-left-color: var(--primary-light);
            text-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
        }

        .sidebar-nav .icon {
            font-size: 18px;
            min-width: 20px;
            filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.2));
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
            background: rgba(30, 41, 59, 0.5);
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
            min-width: 0;
        }

        .menu-toggle {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: white;
            display: none;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .search-box {
            flex: 1;
            max-width: 420px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 1) 100%);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 11px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        body.dark-mode .search-box {
            background: linear-gradient(135deg, rgba(30, 41, 59, 1) 0%, rgba(15, 23, 42, 1) 100%);
            border-color: var(--border-color);
        }

        .search-box:focus-within {
            border-color: var(--primary);
            background: var(--bg-card);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
            transform: translateY(-2px);
        }

        .search-box input {
            flex: 1;
            background: none;
            border: none;
            outline: none;
            color: var(--text-dark);
            font-size: 14px;
            width: 100%;
        }

        .search-box input::placeholder {
            color: var(--text-lighter);
        }

        /* Search Results Styling */
        .data-card.search-highlight,
        .stat-card.search-highlight,
        .chart-card.search-highlight {
            border-color: var(--primary) !important;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3) !important;
        }

        body.dark-mode .data-card.search-highlight,
        body.dark-mode .stat-card.search-highlight,
        body.dark-mode .chart-card.search-highlight {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.5) !important;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-center {
            position: relative;
        }

        .notification-btn {
            background: linear-gradient(135deg, var(--bg-light), var(--bg-lighter));
            border: 2px solid var(--border-color);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            position: relative;
            transition: all 0.3s ease;
        }

        body.dark-mode .notification-btn {
            background: linear-gradient(135deg, var(--bg-light), var(--bg-lighter));
            border-color: var(--border-color);
        }

        .notification-btn:hover {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border-color: transparent;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, var(--accent), #f97316);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
        }

        .theme-toggle {
            background: linear-gradient(135deg, var(--bg-light), var(--bg-lighter));
            border: 2px solid var(--border-color);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        body.dark-mode .theme-toggle {
            background: linear-gradient(135deg, var(--bg-light), var(--bg-lighter));
            border-color: var(--border-color);
        }

        .theme-toggle:hover {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border-color: transparent;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 14px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(236, 72, 153, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-profile:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            padding: 35px;
            background: linear-gradient(135deg, var(--bg-lighter) 0%, var(--bg-light) 100%);
        }

        body.dark-mode .main-content {
            background: linear-gradient(135deg, #06111f 0%, #0f172a 100%);
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 35px;
            margin-bottom: 35px;
            border: 1px solid rgba(99, 102, 241, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.1);
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 41, 59, 0.8) 100%);
            border-color: rgba(99, 102, 241, 0.15);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .welcome-text {
            position: relative;
            z-index: 1;
        }

        .welcome-text h2 {
            font-size: 32px;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .welcome-text p {
            color: var(--text-light);
            font-size: 15px;
        }

        .export-btn {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 13px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
            position: relative;
            z-index: 1;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .export-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
        }

        .export-btn:active {
            transform: translateY(-2px);
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 41, 59, 0.8) 100%);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15), transparent);
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
            background: radial-gradient(circle, rgba(236, 72, 153, 0.1), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .stat-card.primary { --stat-color: var(--primary); }
        .stat-card.success { --stat-color: var(--success); }
        .stat-card.warning { --stat-color: var(--warning); }
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
            color: var(--text-dark);
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

        /* ===== CHARTS GRID ===== */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 22px;
            margin-bottom: 35px;
        }

        .chart-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        body.dark-mode .chart-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 41, 59, 0.8) 100%);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.1);
        }

        .chart-card h3 {
            font-size: 18px;
            margin-bottom: 22px;
            color: var(--text-dark);
            font-weight: 700;
            letter-spacing: -0.3px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 16px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 10px;
        }

        /* ===== DATA GRID ===== */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 22px;
            margin-bottom: 35px;
        }

        .data-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        body.dark-mode .data-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 41, 59, 0.8) 100%);
            border-color: rgba(99, 102, 241, 0.1);
        }

        .data-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.1);
        }

        .data-card h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-dark);
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .activity-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: var(--bg-hover);
            padding: 14px 12px;
            border-radius: 8px;
            margin: 0 -12px;
            padding: 14px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            font-size: 24px;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-subject {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
            font-size: 13px;
            word-break: break-word;
        }

        .activity-time {
            font-size: 12px;
            color: var(--text-lighter);
        }

        .stat-list {
            list-style: none;
        }

        .stat-list li {
            display: flex;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .stat-list li:hover {
            background: var(--bg-hover);
            padding: 13px 12px;
            border-radius: 6px;
            margin: 0 -12px;
            padding: 13px;
        }

        .stat-list li:last-child {
            border-bottom: none;
        }

        .stat-name {
            color: var(--text-dark);
            font-weight: 500;
        }

        .stat-count {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 14px;
        }

        /* ===== ACTIONS BAR ===== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
            margin-bottom: 0;
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
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 41, 59, 0.8) 100%);
            border-color: rgba(99, 102, 241, 0.1);
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
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.3);
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
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .action-card span:not(.action-icon) {
            position: relative;
            z-index: 1;
            font-size: 14px;
        }

        /* ===== DROPDOWN MENU ===== */
        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 255, 255, 0.95));
            backdrop-filter: blur(10px);
            border-radius: 14px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
            min-width: 320px;
            max-height: 450px;
            overflow-y: auto;
            display: none;
            z-index: 1001;
            border: 1px solid rgba(99, 102, 241, 0.1);
        }

        body.dark-mode .dropdown-menu {
            background: linear-gradient(135deg, var(--bg-card), rgba(30, 41, 59, 0.95));
        }

        .dropdown-menu.active {
            display: block;
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 12px;
            align-items: flex-start;
            transition: all 0.3s ease;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: var(--bg-hover);
        }

        .dropdown-item span {
            font-size: 20px;
            flex-shrink: 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }

            .main-wrapper {
                margin-left: 240px;
            }

            .welcome-section {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
            }

            .sidebar.collapsed {
                transform: translateX(0);
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
            }

            .welcome-text h2 {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .data-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .search-box {
                max-width: 100%;
            }

            .actions-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }

            .stat-value {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .header-left {
                gap: 10px;
            }

            .search-box {
                display: none;
            }

            .top-header {
                flex-wrap: wrap;
            }

            .stat-value {
                font-size: 24px;
            }

            .stat-icon {
                font-size: 32px;
            }

            .main-content {
                padding: 15px;
            }
        }
    </style>
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">🏥</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
            <li><a class="sidebar-btn active" href="dashboard_admin.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a class="sidebar-btn" href="admin_users.php"><span class="icon">👥</span> Users</a></li>
            <li><a class="sidebar-btn" href="admin_students.php"><span class="icon">👨‍🎓</span> Students</a></li>
            <li><a class="sidebar-btn" href="admin_visits.php"><span class="icon">📝</span> Clinic Visits</a></li>
            <li><a class="sidebar-btn" href="admin_records.php"><span class="icon">📋</span> Records</a></li>
            <li><a class="sidebar-btn" href="admin_reports.php"><span class="icon">📊</span> Reports</a></li>
            <li><a class="sidebar-btn" href="admin_settings.php"><span class="icon">⚙️</span> Settings</a></li>
            <li><a class="sidebar-btn" href="../auth/logout.php"><span class="icon">🚪</span> Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search students, visits, records..." id="searchInput">
                </div>
            </div>
            
            <div class="header-right">
                <div class="notification-center">
                    <button class="notification-btn" id="notifBtn" title="Notifications">
                        🔔
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="dropdown-menu" id="notifDropdown">
                        <div class="dropdown-item">
                            <span>✅</span>
                            <div>
                                <div class="activity-subject">New visit recorded</div>
                                <div class="activity-time">2 minutes ago</div>
                            </div>
                        </div>
                        <div class="dropdown-item">
                            <span>👤</span>
                            <div>
                                <div class="activity-subject">New student registered</div>
                                <div class="activity-time">15 minutes ago</div>
                            </div>
                        </div>
                        <div class="dropdown-item">
                            <span>⚠️</span>
                            <div>
                                <div class="activity-subject">System backup completed</div>
                                <div class="activity-time">1 hour ago</div>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>

                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo substr(htmlspecialchars($user['name']), 0, 12); ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Admin</div>
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
                    <p>Here's your system overview and real-time analytics dashboard</p>
                </div>
                <button class="export-btn" onclick="exportData()" title="Export Report">📥 Export Report</button>
            </div>

            <!-- KEY METRICS -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-trend">↑ 12% from last month</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon">📝</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                    <div class="stat-label">Total Visits</div>
                    <div class="stat-trend">↑ 8% from last month</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $ongoing_visits; ?></div>
                    <div class="stat-label">Ongoing Visits</div>
                    <div class="stat-trend">✓ <?php echo $completed_visits; ?> completed</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon">👨‍💼</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">System Users</div>
                    <div class="stat-trend">↑ <?php echo $total_staff; ?> active staff</div>
                </div>
            </div>

            <!-- CHARTS SECTION -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>📈 Monthly Visit Trends</h3>
                    <div class="chart-container">
                        <canvas id="visitsChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>👥 User Role Distribution</h3>
                    <div class="chart-container">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- SECONDARY CHARTS -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>🏥 Top Health Complaints</h3>
                    <div class="chart-container">
                        <canvas id="complaintsChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>📊 Visit Status Overview</h3>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- DATA SECTION -->
            <div class="data-grid">
                <div class="data-card">
                    <h3>⏰ Recent Activities</h3>
                    <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><?php echo $activity['icon']; ?></div>
                            <div class="activity-content">
                                <div class="activity-subject"><?php echo htmlspecialchars(substr($activity['subject'], 0, 35)); ?></div>
                                <div class="activity-time"><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="data-card">
                    <h3>📊 System Performance</h3>
                    <ul class="stat-list">
                        <li>
                            <span class="stat-name">Database Health</span>
                            <span class="stat-count">✓ Optimal</span>
                        </li>
                        <li>
                            <span class="stat-name">System Uptime</span>
                            <span class="stat-count">99.9%</span>
                        </li>
                        <li>
                            <span class="stat-name">Response Time</span>
                            <span class="stat-count">127ms</span>
                        </li>
                        <li>
                            <span class="stat-name">Data Backup</span>
                            <span class="stat-count">✓ Recent</span>
                        </li>
                        <li>
                            <span class="stat-name">Active Sessions</span>
                            <span class="stat-count"><?php echo $total_users; ?></span>
                        </li>
                    </ul>
                </div>

                <div class="data-card">
                    <h3>📈 Quick Statistics</h3>
                    <ul class="stat-list">
                        <li>
                            <span class="stat-name">Visits Today</span>
                            <span class="stat-count"><?php echo $visits_today; ?></span>
                        </li>
                        <li>
                            <span class="stat-name">Visits This Month</span>
                            <span class="stat-count"><?php echo $visits_this_month; ?></span>
                        </li>
                        <li>
                            <span class="stat-name">Total Staff Members</span>
                            <span class="stat-count"><?php echo $total_staff; ?></span>
                        </li>
                        <li>
                            <span class="stat-name">Admin Users</span>
                            <span class="stat-count"><?php echo $total_admins; ?></span>
                        </li>
                        <li>
                            <span class="stat-name">Completion Rate</span>
                            <span class="stat-count"><?php echo round((($completed_visits / max(1, $total_visits)) * 100)); ?>%</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="actions-grid">
                <a class="action-card" href="admin_students.php" title="Manage Students">
                    <span class="action-icon">👨‍🎓</span>
                    <span>Manage Students</span>
                </a>
                <a class="action-card" href="admin_visits.php" title="Record Visit">
                    <span class="action-icon">📝</span>
                    <span>Record Visit</span>
                </a>
                <a class="action-card" href="admin_records.php" title="View Records">
                    <span class="action-icon">📋</span>
                    <span>View Records</span>
                </a>
                <a class="action-card" href="admin_reports.php" title="View Reports">
                    <span class="action-icon">📊</span>
                    <span>View Reports</span>
                </a>
                <a class="action-card" href="admin_settings.php" title="Settings">
                    <span class="action-icon">⚙️</span>
                    <span>Settings</span>
                </a>
                <a class="action-card" href="admin_users.php" title="Manage Users">
                    <span class="action-icon">👥</span>
                    <span>Manage Users</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Set active nav item based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href').split('/').pop();
                if (linkHref === currentPage || (currentPage === '' && linkHref === 'dashboard_admin.php')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });

        // Navigation Helper (kept for compatibility)
        function navigateTo(page) {
            window.location.href = page;
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const notifDropdown = document.getElementById('notifDropdown');
            const notifBtn = document.getElementById('notifBtn');
            
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('active');
            }
        });

        // Menu Toggle
        document.getElementById('menuToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed');
        });

        // Notification Toggle
        document.getElementById('notifBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notifDropdown').classList.toggle('active');
        });

        // Theme Toggle with smooth transition
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            // Update button icon
            this.textContent = isDark ? '☀️' : '🌙';
        });

        // Load theme preference on page load
        window.addEventListener('load', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('themeToggle').textContent = '☀️';
            }
        });

        // Search functionality with results display
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            
            if (query.length === 0) {
                // Reset - show all content
                document.querySelectorAll('.data-card, .stat-card, .chart-card').forEach(card => {
                    card.style.display = '';
                    card.style.opacity = '1';
                });
                return;
            }

            // Search through all content
            let matchCount = 0;
            const searchResults = [];
            
            // Search in data cards
            document.querySelectorAll('.data-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
                
                if (text.includes(query) || title.includes(query)) {
                    card.style.display = '';
                    card.style.opacity = '1';
                    matchCount++;
                    searchResults.push(title);
                } else {
                    card.style.opacity = '0.3';
                }
            });

            // Search in stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                
                if (text.includes(query)) {
                    card.style.opacity = '1';
                    matchCount++;
                } else {
                    card.style.opacity = '0.3';
                }
            });

            // Search in chart cards
            document.querySelectorAll('.chart-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                
                if (text.includes(query)) {
                    card.style.opacity = '1';
                    matchCount++;
                } else {
                    card.style.opacity = '0.3';
                }
            });

            // Show feedback
            if (matchCount === 0) {
                console.log('❌ No results found for: ' + query);
            } else {
                console.log('✅ Found ' + matchCount + ' matching results');
            }
        });

        // Smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Line Chart - Visits
        const visitsCtx = document.getElementById('visitsChart');
        if (visitsCtx) {
            new Chart(visitsCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_days); ?>,
                    datasets: [{
                        label: 'Daily Visits',
                        data: <?php echo json_encode($chart_counts); ?>,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: { size: 12, weight: '600' },
                                usePointStyle: true,
                                padding: 15,
                                color: document.body.classList.contains('dark-mode') ? '#f1f5f9' : '#1e293b'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { font: { size: 11 }, color: document.body.classList.contains('dark-mode') ? '#94a3b8' : '#64748b' },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            ticks: { font: { size: 11 }, color: document.body.classList.contains('dark-mode') ? '#94a3b8' : '#64748b' },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Pie Chart - Roles
        const roleCtx = document.getElementById('roleChart');
        if (roleCtx) {
            new Chart(roleCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($roles); ?>,
                    datasets: [{
                        data: <?php echo json_encode($role_counts); ?>,
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(59, 130, 245, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            '#6366f1',
                            '#10b981',
                            '#3b82f6',
                            '#f59e0b'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 12 },
                                padding: 15,
                                color: document.body.classList.contains('dark-mode') ? '#f1f5f9' : '#1e293b'
                            }
                        }
                    }
                }
            });
        }

        // Bar Chart - Complaints
        const complaintsCtx = document.getElementById('complaintsChart');
        if (complaintsCtx) {
            new Chart(complaintsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($complaint_names); ?>,
                    datasets: [{
                        label: 'Count',
                        data: <?php echo json_encode($complaint_counts); ?>,
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: '#6366f1',
                        borderRadius: 8,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // Status Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx.getContext('2d'), {
                type: 'polarArea',
                data: {
                    labels: ['Completed', 'Ongoing'],
                    datasets: [{
                        data: [<?php echo $completed_visits; ?>, <?php echo $ongoing_visits; ?>],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(245, 158, 11, 0.7)'
                        ],
                        borderColor: [
                            '#10b981',
                            '#f59e0b'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.body.classList.contains('dark-mode') ? '#f1f5f9' : '#1e293b'
                            }
                        }
                    }
                }
            });
        }

        // Export Data
        function exportData() {
            // Create a simple CSV export
            const timestamp = new Date().toLocaleString();
            const csvContent = [
                ['SCHoRD Dashboard Report'],
                ['Generated:', timestamp],
                [''],
                ['Metric', 'Value'],
                ['Total Students', '<?php echo $total_students; ?>'],
                ['Total Visits', '<?php echo $total_visits; ?>'],
                ['Ongoing Visits', '<?php echo $ongoing_visits; ?>'],
                ['Completed Visits', '<?php echo $completed_visits; ?>'],
                ['Total Users', '<?php echo $total_users; ?>'],
                ['Active Staff', '<?php echo $total_staff; ?>'],
                ['Admin Users', '<?php echo $total_admins; ?>'],
                ['Visits Today', '<?php echo $visits_today; ?>'],
                ['Visits This Month', '<?php echo $visits_this_month; ?>']
            ].map(row => row.join(',')).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', `SCHoRD-Report-${new Date().getTime()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Show notification
            showNotification('📥 Report exported successfully!', 'success');
        }

        // Notification helper
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #6366f1, #ec4899);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
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

        // Add CSS animations
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
