<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if admin
if (strtolower($user['role']) !== 'admin') {
    if (strtolower($user['role']) === 'nurse') {
        header("Location: nurse_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Get statistics for reports
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$total_records = $conn->query("SELECT COUNT(*) as count FROM health_records")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Top complaints
$complaints = $conn->query("SELECT complaint, COUNT(*) as count FROM clinic_visits GROUP BY complaint ORDER BY count DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #6366f1;
            --accent: #ec4899;
            --success: #10b981;
            --text-dark: #1e293b;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --page-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3f2 100%);
            --table-hover: #f9fafb;
        }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: var(--page-bg);
            color: var(--text-dark);
            min-height: 100vh;
        }
        body.dark-mode {
            --text-dark: #e2e8f0;
            --bg-card: #0f172a;
            --border-color: #334155;
            --page-bg: linear-gradient(135deg, #020617 0%, #0f172a 50%, #111827 100%);
            --table-hover: #1e293b;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 255, 255, 0.5));
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary);
        }
        body.dark-mode .stat-card,
        body.dark-mode .section {
            box-shadow: 0 12px 24px rgba(0,0,0,0.25);
        }
        .stat-card h3 { font-size: 12px; color: var(--text-dark); text-transform: uppercase; margin-bottom: 10px; opacity: 0.8; }
        .stat-card .value { font-size: 36px; font-weight: 800; color: var(--primary); }
        .section { background: var(--bg-card); padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .section h2 { margin-bottom: 20px; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.05)); padding: 12px; text-align: left; font-weight: 700; border-bottom: 2px solid var(--border-color); }
        td { padding: 12px; border-bottom: 1px solid var(--border-color); }
        tbody tr:hover { background: var(--table-hover); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Reports & Analytics</h1>
            <a href="dashboard_admin.php">← Back to Dashboard</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="value"><?php echo $total_students; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Clinic Visits</h3>
                <div class="value"><?php echo $total_visits; ?></div>
            </div>
            <div class="stat-card">
                <h3>Health Records</h3>
                <div class="value"><?php echo $total_records; ?></div>
            </div>
            <div class="stat-card">
                <h3>System Users</h3>
                <div class="value"><?php echo $total_users; ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Top Health Complaints</h2>
            <table>
                <thead>
                    <tr>
                        <th>Complaint</th>
                        <th>Frequency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $comp_count = 0;
                        if ($complaints && $complaints->num_rows > 0): 
                            while ($comp_count < 10 && ($c = $complaints->fetch_assoc())):
                            $comp_count++;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['complaint'] ?? 'N/A'); ?></td>
                            <td><strong><?php echo $c['count']; ?></strong></td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr><td colspan="2">No complaint data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>
