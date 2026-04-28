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

$success = '';
$error = '';

// Delete visit
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM clinic_visits WHERE id='$id'")) {
        $success = '✅ Visit deleted successfully!';
    } else {
        $error = '❌ Error deleting visit';
    }
}

// Get visits data
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];

$visits = $conn->query("
    SELECT cv.*, s.name as student_name, s.student_no
    FROM clinic_visits cv
    JOIN students s ON cv.student_id = s.id
    ORDER BY cv.visit_date DESC
    LIMIT 50
");

if (!$visits) {
    echo "<div style='padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'><strong>Database Error:</strong> " . htmlspecialchars($conn->error) . "<br><a href='../utils/migrate_database.php' style='color: #dc2626; font-weight: bold;'>Run database migration →</a></div>";
    $visits = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Visits - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #6366f1;
            --accent: #ec4899;
            --success: #10b981;
            --danger: #ef4444;
            --text-dark: #1e293b;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
        }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3f2 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid;
            font-weight: 600;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); border-color: var(--success); color: var(--success); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); border-color: var(--danger); color: var(--danger); }
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 12px;
        }
        .btn-danger { background: var(--danger); color: white; }
        table { width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        th { background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.05)); padding: 16px 20px; text-align: left; font-weight: 700; border-bottom: 2px solid var(--border-color); }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border-color); }
        tbody tr:hover { background: #f9fafb; }
        .stat-card { display: inline-block; background: var(--bg-card); padding: 20px 30px; border-radius: 12px; margin-right: 20px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stat-card strong { color: var(--primary); font-size: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Clinic Visits</h1>
            <a href="dashboard_admin.php">← Back to Dashboard</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="stat-card">
            <div>Total Visits: <strong><?php echo $total_visits; ?></strong></div>
        </div>

        <h2>Recent Clinic Visits</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Student No.</th>
                    <th>Complaint</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($visits && $visits->num_rows > 0): ?>
                    <?php while ($v = $visits->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($v['visit_date'])); ?></td>
                            <td><?php echo htmlspecialchars($v['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($v['student_no']); ?></td>
                            <td><?php echo htmlspecialchars(substr($v['complaint'], 0, 30) . '...'); ?></td>
                            <td><span style="background: <?php echo $v['status'] === 'completed' ? '#d1fae5' : '#fef3c7'; ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;"><?php echo ucfirst($v['status'] ?? 'pending'); ?></span></td>
                            <td>
                                <a href="?delete=<?php echo $v['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this visit?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 30px;">No visits found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
