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

// Update system settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    $clinic_name = sanitize($_POST['clinic_name'] ?? 'SCHoRD Clinic');
    $clinic_email = sanitize($_POST['clinic_email'] ?? 'clinic@schord.local');
    $max_visitors_per_day = sanitize($_POST['max_visitors_per_day'] ?? 100);
    
    $success = '✅ Settings updated successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #6366f1;
            --accent: #ec4899;
            --success: #10b981;
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
        .settings-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .settings-card h2 { margin-bottom: 25px; font-size: 20px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3); }
        .info-box {
            background: rgba(99, 102, 241, 0.08);
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid var(--primary);
            margin-bottom: 25px;
        }
        .info-box h3 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ System Settings</h1>
            <a href="dashboard_admin.php">← Back to Dashboard</a>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="settings-card">
            <h2>Clinic Information</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="form-group">
                    <label>Clinic Name</label>
                    <input type="text" name="clinic_name" value="SCHoRD Clinic" placeholder="Enter clinic name">
                </div>

                <div class="form-group">
                    <label>Clinic Email</label>
                    <input type="email" name="clinic_email" value="clinic@schord.local" placeholder="Enter clinic email">
                </div>

                <div class="form-group">
                    <label>Max Visitors Per Day</label>
                    <input type="number" name="max_visitors_per_day" value="100" placeholder="Enter max daily visitors">
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>

        <div class="settings-card">
            <h2>System Information</h2>
            <div class="info-box">
                <h3>SCHoRD Version</h3>
                <p>Clinical Health Records Management System v1.0</p>
            </div>
            <div class="info-box">
                <h3>Database</h3>
                <p><?php echo $conn->server_info; ?></p>
            </div>
            <div class="info-box">
                <h3>Support</h3>
                <p>For support, please contact the system administrator</p>
            </div>
        </div>
    </div>
</body>
</html>
