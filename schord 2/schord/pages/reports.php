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

// Get statistics for reports
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$total_records = $conn->query("SELECT COUNT(*) as count FROM health_records")->fetch_assoc()['count'];
$ongoing_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$completed_visits = $total_visits - $ongoing_visits;

// Top complaints
$top_complaints = $conn->query("
    SELECT complaint, COUNT(*) as count
    FROM clinic_visits
    GROUP BY complaint
    ORDER BY count DESC
    LIMIT 10
");

// Monthly visits
$monthly_visits = $conn->query("
    SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as count
    FROM clinic_visits
    GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Students by course
$students_by_course = $conn->query("
    SELECT course, COUNT(*) as count
    FROM students
    GROUP BY course
    ORDER BY count DESC
    LIMIT 8
");

// Total allergies count
$total_allergies = $conn->query("
    SELECT COUNT(*) as count FROM health_records WHERE allergies != '' AND allergies IS NOT NULL
")->fetch_assoc()['count'];

// Total conditions count
$total_conditions = $conn->query("
    SELECT COUNT(*) as count FROM health_records WHERE conditions != '' AND conditions IS NOT NULL
")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SCHoRD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .export-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 28px;
        }

        .page-header p {
            color: var(--text-light);
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--primary);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-card.success { border-top-color: var(--success); }
        .stat-card.warning { border-top-color: var(--warning); }
        .stat-card.info { border-top-color: #3b82f6; }

        .stat-value {
            font-size: 40px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-card.success .stat-value { color: var(--success); }
        .stat-card.warning .stat-value { color: var(--warning); }
        .stat-card.info .stat-value { color: #3b82f6; }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 600;
        }

        /* ===== CHARTS ===== */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .chart-card h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-dark);
            font-weight: 700;
            border-bottom: 2px solid var(--bg-light);
            padding-bottom: 15px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* ===== DATA TABLES ===== */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .data-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .data-card h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-dark);
            font-weight: 700;
        }

        .report-list {
            list-style: none;
        }

        .report-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--bg-light);
            font-size: 14px;
        }

        .report-list li:last-child {
            border-bottom: none;
        }

        .report-item-name {
            color: var(--text-dark);
            font-weight: 500;
        }

        .report-item-count {
            color: var(--primary);
            font-weight: 700;
            font-size: 16px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 32px;
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
            <li><a href="visits.php"><span>📝</span> Clinic Visits</a></li>
            <li><a href="health_records.php"><span>📋</span> Health Records</a></li>
            <li><a href="javascript:void(0)" class="active"><span>📊</span> Reports</a></li>
            <li><a href="javascript:void(0)"><span>⚙️</span> Settings</a></li>
            <li><a href="../auth/logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <h2 style="color: var(--text-dark);">📊 Reports Dashboard</h2>
            </div>
            <div class="header-right">
                <button class="export-btn" onclick="window.print()">📥 Export as PDF</button>
                <button class="theme-toggle" id="themeToggle">🌙</button>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1>📊 System Reports</h1>
                    <p>Comprehensive analytics and system statistics</p>
                </div>
            </div>

            <!-- KEY METRICS -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                    <div class="stat-label">Total Visits</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $total_records; ?></div>
                    <div class="stat-label">Health Records</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value"><?php echo $completed_visits; ?></div>
                    <div class="stat-label">Completed Visits</div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>📈 Visit Completion Rate</h3>
                    <div class="chart-container">
                        <canvas id="completionChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>🔝 Top Health Complaints</h3>
                    <div class="chart-container">
                        <canvas id="complaintsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- DATA TABLES -->
            <div class="data-grid">
                <div class="data-card">
                    <h3>🏫 Students by Course</h3>
                    <ul class="report-list">
                        <?php 
                        $students_by_course->data_seek(0);
                        while ($course = $students_by_course->fetch_assoc()): 
                        ?>
                            <li>
                                <span class="report-item-name"><?php echo htmlspecialchars(substr($course['course'], 0, 25)); ?></span>
                                <span class="report-item-count"><?php echo $course['count']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="data-card">
                    <h3>⚠️ Health Summary</h3>
                    <ul class="report-list">
                        <li>
                            <span class="report-item-name">Students with Allergies</span>
                            <span class="report-item-count"><?php echo $total_allergies; ?></span>
                        </li>
                        <li>
                            <span class="report-item-name">Students with Conditions</span>
                            <span class="report-item-count"><?php echo $total_conditions; ?></span>
                        </li>
                        <li>
                            <span class="report-item-name">Ongoing Visits</span>
                            <span class="report-item-count"><?php echo $ongoing_visits; ?></span>
                        </li>
                        <li>
                            <span class="report-item-name">Completion Rate</span>
                            <span class="report-item-count"><?php echo round((($completed_visits / max(1, $total_visits)) * 100)); ?>%</span>
                        </li>
                    </ul>
                </div>

                <div class="data-card">
                    <h3>🏆 Top Complaints</h3>
                    <ul class="report-list">
                        <?php 
                        $top_complaints->data_seek(0);
                        $rank = 1;
                        while ($complaint = $top_complaints->fetch_assoc()): 
                        ?>
                            <li>
                                <span class="report-item-name"><?php echo $rank . '. ' . htmlspecialchars(substr($complaint['complaint'], 0, 20)); ?></span>
                                <span class="report-item-count"><?php echo $complaint['count']; ?></span>
                            </li>
                        <?php 
                            $rank++;
                            endwhile; 
                        ?>
                    </ul>
                </div>
            </div>

            <!-- ADDITIONAL STATS -->
            <div class="stats-grid">
                <div class="stat-card" style="border-top-color: #8b5cf6;">
                    <div class="stat-value" style="color: #8b5cf6;"><?php echo $total_allergies; ?></div>
                    <div class="stat-label">Allergy Cases</div>
                </div>
                <div class="stat-card" style="border-top-color: #e91e63;">
                    <div class="stat-value" style="color: #e91e63;"><?php echo $total_conditions; ?></div>
                    <div class="stat-label">Condition Cases</div>
                </div>
                <div class="stat-card" style="border-top-color: #00bcd4;">
                    <div class="stat-value" style="color: #00bcd4;"><?php echo round((($total_records / max(1, $total_students)) * 100)); ?>%</div>
                    <div class="stat-label">Health Record Coverage</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        // Completion Rate Chart
        const completionCtx = document.getElementById('completionChart').getContext('2d');
        new Chart(completionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Ongoing'],
                datasets: [{
                    data: [<?php echo $completed_visits; ?>, <?php echo $ongoing_visits; ?>],
                    backgroundColor: ['#10b981', '#f59e0b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 15 } }
                }
            }
        });

        // Top Complaints Chart
        const complaintsCtx = document.getElementById('complaintsChart').getContext('2d');
        const complaintsMap = {
            labels: [<?php 
                $top_complaints->data_seek(0);
                $first = true;
                while ($c = $top_complaints->fetch_assoc()) {
                    if (!$first) echo ",";
                    echo "'" . htmlspecialchars(substr($c['complaint'], 0, 15)) . "'";
                    $first = false;
                }
            ?>],
            counts: [<?php 
                $top_complaints->data_seek(0);
                $first = true;
                while ($c = $top_complaints->fetch_assoc()) {
                    if (!$first) echo ",";
                    echo $c['count'];
                    $first = false;
                }
            ?>]
        };

        new Chart(complaintsCtx, {
            type: 'bar',
            data: {
                labels: complaintsMap.labels,
                datasets: [{
                    label: 'Count',
                    data: complaintsMap.counts,
                    backgroundColor: '#dc2626'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { font: { size: 11 } } }
                }
            }
        });
    </script>
</body>
</html>
