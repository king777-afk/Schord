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

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$ongoing_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$completed_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='completed'")->fetch_assoc()['count'];

// Get monthly visit data
$monthly_visits = $conn->query("SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as count FROM clinic_visits GROUP BY DATE_FORMAT(visit_date, '%Y-%m') ORDER BY month DESC LIMIT 12");
$months = [];
$visit_counts = [];
while ($row = $monthly_visits->fetch_assoc()) {
    array_unshift($months, $row['month']);
    array_unshift($visit_counts, $row['count']);
}

// Top students by visits
$top_students = $conn->query("SELECT s.name, COUNT(cv.id) as visit_count FROM students s LEFT JOIN clinic_visits cv ON s.id = cv.student_id GROUP BY s.id ORDER BY visit_count DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Staff - SCHoRD</title>
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
            background: radial-gradient(circle, rgba(167, 139, 250, 0.1), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.2);
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
        }

        /* ===== CHART STYLES ===== */
        .chart-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        body.dark-mode .chart-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(30, 27, 75, 0.8) 100%);
            border-color: rgba(16, 185, 129, 0.1);
        }

        .chart-card h2 {
            margin-bottom: 20px;
            color: var(--text-dark);
            font-size: 18px;
            font-weight: 700;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 300px;
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

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: flex; }
            .main-content { padding: 20px; }
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 20px;
        }

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

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
            <li><a href="staff_health_records.php" class="nav-link">📝 Health Records</a></li>
            
            <div class="sidebar-section-label">Reporting</div>
            <li><a href="staff_reports.php" class="nav-link active">📈 Reports</a></li>
            
            <li><a href="../auth/logout.php" class="nav-link">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h1 class="header-title">📈 Reports</h1>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search reports...">
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
                <h1>Staff Analytics & Reports</h1>
            </div>

            <!-- STATS GRID -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Visits</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ongoing Visits</div>
                    <div class="stat-value"><?php echo $ongoing_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed Visits</div>
                    <div class="stat-value"><?php echo $completed_visits; ?></div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="chart-card">
                <h2>📈 Monthly Visits Trend</h2>
                <div class="chart-container">
                    <canvas id="visitChart"></canvas>
                </div>
            </div>

            <!-- TOP PATIENTS -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Top Patients by Visits</th>
                            <th>Number of Visits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $top_students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><strong><?php echo $student['visit_count']; ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
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
            if (link.getAttribute('href') === 'staff_reports.php') {
                link.classList.add('active');
            }
        });

        // Visit Chart
        const ctx = document.getElementById('visitChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Monthly Visits',
                    data: <?php echo json_encode($visit_counts); ?>,
                    fill: true,
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderColor: '#10b981',
                    borderWidth: 3,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            color: '#1f2937'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#64748b' },
                        grid: { color: 'rgba(16, 185, 129, 0.1)' }
                    },
                    x: {
                        ticks: { color: '#64748b' },
                        grid: { color: 'rgba(16, 185, 129, 0.1)' }
                    }
                }
            }
        });

        // Dark mode support for chart
        if (body.classList.contains('dark-mode')) {
            console.log('Dark mode active');
        }
    </script>
    <?php include('../includes/footer.php'); ?>

