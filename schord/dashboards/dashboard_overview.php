<?php
session_start();
include '../config/db.php';

$is_logged_in = isset($_SESSION['user']);
$current_role = $is_logged_in ? $_SESSION['user']['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #f0fdf4 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 42px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            color: #6b7280;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 5px solid;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card.admin {
            border-top-color: #dc2626;
        }

        .dashboard-card.nurse {
            border-top-color: #0891b2;
        }

        .dashboard-card.staff {
            border-top-color: #6366f1;
        }

        .dashboard-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .dashboard-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .dashboard-desc {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-bottom: 20px;
        }

        .features-list li {
            padding: 8px 0;
            font-size: 13px;
            color: #4b5563;
            padding-left: 25px;
            position: relative;
        }

        .features-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            font-weight: bold;
            color: #10b981;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-admin {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
        }

        .btn-nurse {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: white;
        }

        .btn-staff {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin: 10px 0;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.15);
            color: #6b7280;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 14px;
            color: #1e40af;
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 40px 0;
        }

        .system-info {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .system-info h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>🎯 SCHoRD Dashboard System</h1>
            <p>Organized Role-Based Dashboards - No Conflicts</p>
        </div>

        <!-- STATUS INFO -->
        <?php if ($is_logged_in): ?>
            <div class="info-box">
                ✅ You are logged in as: <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong> 
                (Role: <strong><?php echo strtoupper($_SESSION['user']['role']); ?></strong>)
                <br><a href="auth/logout.php" style="color: #3b82f6;">Logout</a>
            </div>
        <?php else: ?>
            <div class="info-box">
                👤 Not logged in. <a href="auth/login.php" style="color: #3b82f6;">Login now</a> to access your dashboard.
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- DASHBOARD CARDS -->
        <div class="dashboard-grid">
            <!-- ADMIN DASHBOARD -->
            <div class="dashboard-card admin">
                <div class="dashboard-icon">🔴</div>
                <div class="dashboard-title">Admin Dashboard</div>
                <div class="dashboard-desc">System management and configuration panel for administrators</div>
                
                <?php if ($current_role === 'admin'): ?>
                    <span class="status-badge status-active">✓ Active (Your Role)</span>
                <?php else: ?>
                    <span class="status-badge status-inactive">Inactive</span>
                <?php endif; ?>

                <ul class="features-list">
                    <li>User management</li>
                    <li>System settings</li>
                    <li>Database backup</li>
                    <li>Advanced analytics</li>
                    <li>System configuration</li>
                </ul>

                <?php if ($current_role === 'admin'): ?>
                    <a href="dashboard_admin.php" class="btn btn-admin">Go to Admin Dashboard →</a>
                <?php else: ?>
                    <button class="btn btn-admin" disabled style="opacity: 0.5; cursor: not-allowed;">Admin Only</button>
                <?php endif; ?>
            </div>

            <!-- NURSE DASHBOARD -->
            <div class="dashboard-card nurse">
                <div class="dashboard-icon">👩‍⚕️</div>
                <div class="dashboard-title">Nurse Dashboard</div>
                <div class="dashboard-desc">Patient care operations and medical data management</div>

                <?php if ($current_role === 'nurse'): ?>
                    <span class="status-badge status-active">✓ Active (Your Role)</span>
                <?php else: ?>
                    <span class="status-badge status-inactive">Inactive</span>
                <?php endif; ?>

                <ul class="features-list">
                    <li>Patient vitals tracking</li>
                    <li>Appointment scheduling</li>
                    <li>Health alerts system</li>
                    <li>Clinic visit records</li>
                    <li>Performance charts</li>
                </ul>

                <?php if ($current_role === 'nurse'): ?>
                    <a href="nurse_dashboard.php" class="btn btn-nurse">Go to Nurse Dashboard →</a>
                <?php else: ?>
                    <button class="btn btn-nurse" disabled style="opacity: 0.5; cursor: not-allowed;">Nurse Only</button>
                <?php endif; ?>
            </div>

            <!-- STAFF DASHBOARD -->
            <div class="dashboard-card staff">
                <div class="dashboard-icon">👔</div>
                <div class="dashboard-title">Staff Dashboard</div>
                <div class="dashboard-desc">Student records and clinic operations management</div>

                <?php if ($current_role === 'staff'): ?>
                    <span class="status-badge status-active">✓ Active (Your Role)</span>
                <?php else: ?>
                    <span class="status-badge status-inactive">Inactive</span>
                <?php endif; ?>

                <ul class="features-list">
                    <li>Student management</li>
                    <li>Visit records</li>
                    <li>Health records</li>
                    <li>System statistics</li>
                    <li>Report generation</li>
                </ul>

                <?php if ($current_role === 'staff'): ?>
                    <a href="staff_dashboard.php" class="btn btn-staff">Go to Staff Dashboard →</a>
                <?php else: ?>
                    <button class="btn btn-staff" disabled style="opacity: 0.5; cursor: not-allowed;">Staff Only</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="divider"></div>

        <!-- SYSTEM INFO -->
        <div class="system-info">
            <h2>📊 System Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Active User</div>
                    <div class="info-value"><?php echo $is_logged_in ? htmlspecialchars($_SESSION['user']['name']) : 'Not logged in'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Role</div>
                    <div class="info-value"><?php echo $is_logged_in ? strtoupper($current_role) : '—'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Access Level</div>
                    <div class="info-value"><?php echo $is_logged_in ? '✓ Authorized' : '✗ Not Verified'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Dashboard Type</div>
                    <div class="info-value"><?php echo $is_logged_in ? ucfirst($current_role) : 'Guest'; ?></div>
                </div>
            </div>
        </div>

        <!-- HELP -->
        <div class="info-box" style="margin-top: 30px; background: rgba(34, 197, 94, 0.1); border-left-color: #22c55e; color: #166534;">
            <strong>✓ System Status: All Dashboards Operational</strong><br>
            • No conflicts between roles<br>
            • Each role has unique features<br>
            • Proper access control enabled<br>
            • Data organized and separated<br>
            <a href="DASHBOARD_ORGANIZATION.md" style="color: #16a34a; text-decoration: none;">📖 Read Full Documentation →</a>
        </div>
    </div>
</body>
</html>
