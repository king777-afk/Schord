<?php
// System Diagnostic Page
session_start();
include '../config/db.php';

$status = array(
    'database' => 'Unknown',
    'settings_table' => 'Unknown',
    'register_page' => 'Unknown',
    'uploads_dir' => 'Unknown',
);

// Check database connection
if ($conn && !$conn->connect_error) {
    $status['database'] = '✅ Connected';
} else {
    $status['database'] = '❌ Error: ' . $conn->connect_error;
}

// Check settings table
$result = $conn->query("SHOW TABLES LIKE 'settings'");
if ($result && $result->num_rows > 0) {
    $status['settings_table'] = '✅ Exists';
    
    // Try to fetch settings
    $settings_test = $conn->query("SELECT COUNT(*) as count FROM settings");
    if ($settings_test) {
        $row = $settings_test->fetch_assoc();
        $status['settings_table'] .= " ({$row['count']} rows)";
    }
} else {
    $status['settings_table'] = '❌ Does NOT exist - Run database.sql!';
}

// Check register page
if (file_exists('auth/register.php')) {
    $status['register_page'] = '✅ File exists';
} else {
    $status['register_page'] = '❌ File missing';
}

// Check uploads directories
$uploads_dir = 'uploads/';
$bg_dir = 'uploads/backgrounds/';
$logo_dir = 'uploads/logos/';

if (is_dir($uploads_dir)) {
    $status['uploads_dir'] = '✅ Uploads directory exists';
    
    if (is_dir($bg_dir)) {
        $status['uploads_dir'] .= ' | ✅ Background dir exists';
    } else {
        $status['uploads_dir'] .= ' | ⚠️ Background dir missing (will be created on upload)';
    }
    
    if (is_dir($logo_dir)) {
        $status['uploads_dir'] .= ' | ✅ Logo dir exists';
    } else {
        $status['uploads_dir'] .= ' | ⚠️ Logo dir missing (will be created on upload)';
    }
} else {
    $status['uploads_dir'] = '❌ Uploads directory not found';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostic - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .diagnostic-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        h1 {
            color: #e74c3c;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .status-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 4px solid #e74c3c;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .status-value {
            font-size: 0.95rem;
            color: #666;
            text-align: right;
        }

        .critical {
            background: #f8d7da;
            border-left-color: #c0392b;
        }

        .critical .status-label {
            color: #721c24;
        }

        .instructions {
            background: #ffe5e5;
            border: 2px solid #e74c3c;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .instructions h2 {
            color: #c0392b;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .instructions ol {
            color: #555;
            padding-left: 20px;
            line-height: 1.8;
        }

        .instructions li {
            margin-bottom: 10px;
        }

        .instructions strong {
            color: #2c3e50;
        }

        .links {
            text-align: center;
            margin-top: 30px;
        }

        .links a {
            display: inline-block;
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 0 5px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .success-note {
            background: #d4edda;
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="diagnostic-container">
        <h1>🔧 System Diagnostic</h1>

        <div class="status-item">
            <span class="status-label">Database Connection:</span>
            <span class="status-value"><?php echo $status['database']; ?></span>
        </div>

        <div class="status-item <?php echo (strpos($status['settings_table'], 'Does NOT') !== false) ? 'critical' : ''; ?>">
            <span class="status-label">Settings Table:</span>
            <span class="status-value"><?php echo $status['settings_table']; ?></span>
        </div>

        <div class="status-item">
            <span class="status-label">Register Page File:</span>
            <span class="status-value"><?php echo $status['register_page']; ?></span>
        </div>

        <div class="status-item">
            <span class="status-label">Upload Directories:</span>
            <span class="status-value"><?php echo $status['uploads_dir']; ?></span>
        </div>

        <?php if (strpos($status['settings_table'], 'Does NOT') !== false): ?>
            <div class="instructions">
                <h2>⚠️ IMPORTANT: Settings Table Missing!</h2>
                <p>Your database doesn't have the settings table. Follow these steps to fix it:</p>
                <ol>
                    <li>Open <strong>phpMyAdmin</strong>: <code>http://localhost/phpmyadmin/</code></li>
                    <li>Select the <strong>schord_db</strong> database</li>
                    <li>Click the <strong>SQL</strong> tab at the top</li>
                    <li>Open <strong>database.sql</strong> from your project folder</li>
                    <li>Copy all the content and paste it into the SQL editor</li>
                    <li>Click <strong>Go</strong> to execute</li>
                    <li>Refresh this page to verify</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="success-note">
                ✅ All system components are ready! Your SCHoRD system is operational.
            </div>
        <?php endif; ?>

        <div class="links">
            <a href="auth/login.php">🔐 Go to Login</a>
            <a href="auth/register.php">📝 Go to Register</a>
            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                <a href="admin_settings.php">⚙️ Admin Settings</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
