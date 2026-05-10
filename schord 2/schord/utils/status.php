<?php
// System Diagnostics Page
?>
<!DOCTYPE html>
<html>
<head>
    <title>SCHoRD - System Status</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #2c3e50; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .section { margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 SCHoRD System Status Check</h1>
        
        <?php
        $errors = [];
        $warnings = [];
        $success = [];

        // 1. Check PHP
        $success[] = "✅ PHP is running (v" . phpversion() . ")";

        // 2. Check MySQL Connection
        $conn = new mysqli("localhost", "root", "", "schord_db");
        if ($conn->connect_error) {
            $errors[] = "❌ MySQL Connection Failed: " . $conn->connect_error;
        } else {
            $success[] = "✅ MySQL Connected";
            
            // Check tables
            $tables = ['users', 'students', 'health_records', 'clinic_visits'];
            $result = $conn->query("SHOW TABLES FROM schord_db");
            $existing_tables = [];
            
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $existing_tables[] = $row[0];
                }
            }
            
            $missing_tables = array_diff($tables, $existing_tables);
            
            if (empty($missing_tables)) {
                $success[] = "✅ All 4 required tables found";
            } else {
                $errors[] = "❌ Missing tables: " . implode(", ", $missing_tables);
            }
            
            // Check admin user
            $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE email='admin@schord.com'");
            if ($admin_check) {
                $row = $admin_check->fetch_assoc();
                if ($row['count'] > 0) {
                    $success[] = "✅ Admin user exists (admin@schord.com)";
                } else {
                    $warnings[] = "⚠️ Admin user not found - creating now...";
                    
                    // Create admin user
                    $admin_password = '$2y$10$slYQmyNdGzIn9tdVyNo3Be63DlH.qVETVSMt.eoHxjAuxsQta7u.m'; // admin123
                    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('System Admin', 'admin@schord.com', '$admin_password', 'admin')");
                    $success[] = "✅ Admin user created";
                }
            }
            
            $conn->close();
        }

        // 3. Check files
        $required_files = [
            '../index.php',
            '../auth/login.php',
            '../auth/register.php',
            '../dashboard.php',
            '../students.php',
            '../visits.php',
            '../health_records.php',
            '../config/db.php',
            '../assets/style.css'
        ];
        
        $missing_files = [];
        foreach ($required_files as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missing_files[] = $file;
            }
        }
        
        if (empty($missing_files)) {
            $success[] = "✅ All required PHP and CSS files found";
        } else {
            $errors[] = "❌ Missing files: " . implode(", ", $missing_files);
        }

        // Display results
        echo "<div class='section'>";
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<div class='status error'>" . htmlspecialchars($error) . "</div>";
            }
        }
        
        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                echo "<div class='status info'>" . htmlspecialchars($warning) . "</div>";
            }
        }
        
        if (!empty($success)) {
            foreach ($success as $msg) {
                echo "<div class='status ok'>" . htmlspecialchars($msg) . "</div>";
            }
        }
        
        echo "</div>";

        if (empty($errors)) {
            echo "<div class='section' style='background: #d4edda; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
            echo "<h2 style='color: #155724; margin-top: 0;'>✅ System is Ready!</h2>";
            echo "<p>Your SCHoRD system is fully functional and ready to use.</p>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li><a href='../'>Go to Login</a></li>";
            echo "<li>Login with: <code>admin@schord.com</code> / <code>admin123</code></li>";
            echo "<li>Start adding students and recording visits</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='section' style='background: #f8d7da; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
            echo "<h2 style='color: #721c24; margin-top: 0;'>❌ Issues Found</h2>";
            echo "<p>Please fix the errors above before proceeding.</p>";
            echo "</div>";
        }
        ?>
        
        <div class="section" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>📊 System Information</h3>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        </div>
    </div>
</body>
</html>
