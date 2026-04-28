<?php
include 'config/db.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Current User</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 20px; }
        .debug-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; }
        .role-admin { background: #dc2626; color: white; padding: 15px; border-radius: 5px; }
        .role-nurse { background: #0891b2; color: white; padding: 15px; border-radius: 5px; }
        .role-staff { background: #6366f1; color: white; padding: 15px; border-radius: 5px; }
        h1 { margin-top: 0; }
        .info { margin: 10px 0; font-size: 16px; }
        code { background: #f5f5f5; padding: 2px 8px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="debug-box">
        <h1>🔍 User Debug Information</h1>
        
        <div class="info">
            <strong>User ID:</strong> <?php echo $user['id']; ?>
        </div>
        
        <div class="info">
            <strong>Name:</strong> <?php echo $user['name']; ?>
        </div>
        
        <div class="info">
            <strong>Email:</strong> <?php echo $user['email']; ?>
        </div>
        
        <div class="info">
            <strong>Role:</strong>
            <div class="role-<?php echo $user['role']; ?>" style="margin-top: 5px; display: inline-block;">
                <?php echo strtoupper($user['role']); ?>
            </div>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <h2>Expected Behavior:</h2>
        <ul>
            <li><strong>If ADMIN:</strong> Click "Patients" should go to <code>students.php</code> (Red page)</li>
            <li><strong>If NURSE:</strong> Click "Patients" should go to <code>nurse_patients.php</code> (Cyan page)</li>
            <li><strong>If STAFF:</strong> Click "Patients" should go to <code>staff_patients.php</code> (Indigo page)</li>
        </ul>
        
        <hr style="margin: 20px 0;">
        
        <h2>Test Links:</h2>
        <p>
            <a href="dashboards/nurse_dashboard.php" style="display: inline-block; background: #0891b2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Go to Nurse Dashboard</a>
            <a href="dashboards/staff_dashboard.php" style="display: inline-block; background: #6366f1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Go to Staff Dashboard</a>
            <a href="dashboards/dashboard_admin.php" style="display: inline-block; background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">Go to Admin Dashboard</a>
        </p>
    </div>
</body>
</html>
