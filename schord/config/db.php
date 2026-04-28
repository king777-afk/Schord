<?php
// Return query errors as false results instead of uncaught exceptions.
mysqli_report(MYSQLI_REPORT_OFF);

// Database Configuration - prefer MYSQL_PUBLIC_URL, then Railway private vars, then local
if ($url = getenv('MYSQL_PUBLIC_URL')) {
    // MYSQL_PUBLIC_URL contains full connection string: mysql://user:pass@host:port/db
    $parts = parse_url($url);
    if ($parts !== false && isset($parts['host'])) {
        $db_host = $parts['host'];
        $db_user = isset($parts['user']) ? rawurldecode($parts['user']) : 'root';
        $db_password = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
        $db_name = isset($parts['path']) ? ltrim(rawurldecode($parts['path']), '/') : 'schord_db';
        $db_port = $parts['port'] ?? 3306;
    } else {
        // Fall back to Railway private vars if the public URL is malformed
        $db_host = getenv('MYSQLHOST') ?: 'localhost';
        $db_user = getenv('MYSQLUSER') ?: 'root';
        $db_password = getenv('MYSQLPASSWORD') ?: '';
        $db_name = getenv('MYSQLDATABASE') ?: 'schord_db';
        $db_port = getenv('MYSQLPORT') ?: 3306;
    }
} elseif (getenv('MYSQLHOST')) {
    // Railway private environment variables
    $db_host = getenv('MYSQLHOST');
    $db_user = getenv('MYSQLUSER');
    $db_password = getenv('MYSQLPASSWORD');
    $db_name = getenv('MYSQLDATABASE');
    $db_port = getenv('MYSQLPORT') ?: 3306;
} else {
    // Local Development Environment
    $db_host = "localhost";
    $db_user = "root";
    $db_password = "";
    $db_name = "schord_db";
    $db_port = 3306;
}

$conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 (full unicode support)
$conn->set_charset("utf8mb4");
if (!$conn->query("SET NAMES utf8mb4")) {
    error_log("Error setting charset: " . $conn->error);
}

// Ensure nurse_schedules table exists (independent of ensureCoreSchema)
$tableCheck = $conn->query("SHOW TABLES LIKE 'nurse_schedules'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    $createScheduleTable = "CREATE TABLE IF NOT EXISTS nurse_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        schedule_date DATETIME NOT NULL,
        reason VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        priority ENUM('low','normal','high') DEFAULT 'normal',
        notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        status ENUM('pending','completed','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        KEY (schedule_date),
        KEY (status)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if (!$conn->query($createScheduleTable)) {
        error_log("Error creating nurse_schedules table: " . $conn->error);
    }
}

/**
 * Ensure required core tables exist in the currently selected database.
 * This allows first-run Railway environments to self-initialize.
 */
function ensureCoreSchema($conn) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$tableCheck || $tableCheck->num_rows > 0) {
        return;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','nurse','staff') DEFAULT 'staff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_no VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci UNIQUE NOT NULL,
            name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            class VARCHAR(50),
            course VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            age INT,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS health_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            allergies TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            conditions TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            blood_type VARCHAR(5),
            height DECIMAL(5,2),
            weight DECIMAL(6,2),
            blood_pressure VARCHAR(20),
            temperature DECIMAL(5,2),
            check_date DATETIME,
            last_check_date DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS clinic_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            visit_date DATETIME NOT NULL,
            complaint TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            treatment TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            status ENUM('ongoing','completed') DEFAULT 'ongoing',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS verification_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            attempt_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY (email),
            KEY (expires_at)
        )",

        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS nurse_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            schedule_date DATETIME NOT NULL,
            reason VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            priority ENUM('low','normal','high') DEFAULT 'normal',
            notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            status ENUM('pending','completed','cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            KEY (schedule_date),
            KEY (status)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            error_log("Schema init error: " . $conn->error);
            return;
        }
    }

    // Backward/forward compatible schema adjustments for redesigned pages.
    $columnChecks = [
        "ALTER TABLE health_records ADD COLUMN chronic_conditions TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL",
        "ALTER TABLE health_records ADD COLUMN medications TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL",
        "ALTER TABLE health_records ADD COLUMN notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL"
    ];

    foreach ($columnChecks as $alterSql) {
        // Ignore duplicate column errors and keep moving.
        $conn->query($alterSql);
    }

    // Ensure clinic status supports all values used by dashboards/pages.
    $conn->query("ALTER TABLE clinic_visits MODIFY COLUMN status ENUM('pending','ongoing','completed') DEFAULT 'ongoing'");

    // Default admin account: admin@schord.com / admin123
    $adminPassword = '$2y$10$slYQmyNdGzIn9tdVyNo3Be63DlH.qVETVSMt.eoHxjAuxsQta7u.m';
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('System Admin', 'admin@schord.com', '$adminPassword', 'admin') ON DUPLICATE KEY UPDATE name=name");
}

ensureCoreSchema($conn);

/**
 * Sanitize input to prevent SQL injection
 */
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

/**
 * Check if user is logged in, redirect to login if not
 */
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
    
    if (!isset($_SESSION['user'])) {
        // Use an absolute path so redirects work from any subfolder (e.g. /dashboards, /pages).
        if (!headers_sent()) {
            header("Location: /auth/login.php");
        } else {
            echo '<script>window.location.href="/auth/login.php";</script>';
        }
        exit();
    }
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
    
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Generate a random 6-digit verification code
 */
function generateVerificationCode() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send verification code to email
 * Optimized for XAMPP with logging and error handling
 */
function sendVerificationEmail($email, $code) {
    $subject = "SCHoRD Login Verification Code";
    
    $message = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
                .container { max-width: 500px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 20px; text-align: center; }
                .code-box { background-color: #f0f0f0; border: 2px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 5px; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #667eea; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
                .warning { color: #d9534f; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 SCHoRD - Login Verification</h1>
                </div>
                <div class='content'>
                    <h2>Your Verification Code</h2>
                    <p>Hello! Your Batangas State University School Health Record login verification code is:</p>
                    <div class='code-box'>$code</div>
                    <p>This code will expire in 15 minutes.</p>
                    <p><span class='warning'>⚠️ Never share this code with anyone.</span></p>
                    <p>If you didn't request this code, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>SCHoRD - School Health & Record Database<br>Batangas State University</p>
                </div>
            </div>
        </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@schord.bsu.edu.ph" . "\r\n";
    $headers .= "Reply-To: noreply@schord.bsu.edu.ph" . "\r\n";
    
    // Log the email attempt
    $logFile = __DIR__ . '/../emails_log.txt';
    $logEntry = date('Y-m-d H:i:s') . " | TO: $email | CODE: $code | SUBJECT: $subject\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Try to send email
    $result = @mail($email, $subject, $message, $headers);
    
    // Log result
    $resultLog = $result ? "SUCCESS" : "FAILED";
    $resultEntry = date('Y-m-d H:i:s') . " | RESULT: $resultLog for $email\n";
    @file_put_contents($logFile, $resultEntry, FILE_APPEND);
    
    return $result;
}

/**
 * Get last 10 emails sent (for debugging on XAMPP)
 */
function getEmailLog() {
    $logFile = __DIR__ . '/../emails_log.txt';
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        // Return last 10 lines
        $lines = array_slice(explode("\n", $content), -10);
        return implode("\n", $lines);
    }
    return "No email log found yet.";
}

/**
 * Create a verification code in the database
 */
function createVerificationCode($email) {
    global $conn;
    
    // Generate code
    $code = generateVerificationCode();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete old codes for this email
    $conn->query("DELETE FROM verification_codes WHERE email='".sanitize($email)."' AND expires_at < NOW()");
    
    // Insert new code
    $result = $conn->query("INSERT INTO verification_codes (email, code, expires_at) VALUES ('".sanitize($email)."', '$code', '$expiresAt')");
    
    if ($result) {
        return ['success' => true, 'code' => $code];
    } else {
        return ['success' => false, 'error' => 'Failed to create verification code'];
    }
}

/**
 * Verify a code entered by user
 */
function verifyCode($email, $code) {
    global $conn;
    
    $email = sanitize($email);
    $code = sanitize($code);
    
    // Check if code exists and is not expired
    $result = $conn->query("SELECT id FROM verification_codes WHERE email='$email' AND code='$code' AND expires_at > NOW() AND attempt_count < 5");
    
    if ($result && $result->num_rows > 0) {
        // Delete the used code
        $conn->query("DELETE FROM verification_codes WHERE email='$email' AND code='$code'");
        return ['success' => true];
    } else {
        // Increment attempt count
        $conn->query("UPDATE verification_codes SET attempt_count = attempt_count + 1 WHERE email='$email' AND code='$code'");
        return ['success' => false, 'error' => 'Invalid verification code'];
    }
}

/**
 * Clear expired verification codes
 */
function cleanupExpiredCodes() {
    global $conn;
    $conn->query("DELETE FROM verification_codes WHERE expires_at < NOW()");
}
?>