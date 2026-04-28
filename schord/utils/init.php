<?php
/**
 * Database initialization script
 * Creates the schord_db database and all required tables
 */

// Suppress error reporting initially
mysqli_report(MYSQLI_REPORT_OFF);

// Database credentials
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'schord_db';

// Step 1: Connect to MySQL without selecting a database
$conn = new mysqli($db_host, $db_user, $db_password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Create the database if it doesn't exist
$create_db = "CREATE DATABASE IF NOT EXISTS " . $db_name;
if (!$conn->query($create_db)) {
    die("Error creating database: " . $conn->error);
}

// Step 3: Select the database
if (!$conn->select_db($db_name)) {
    die("Error selecting database: " . $conn->error);
}

// Step 4: Create tables
$tables = [
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
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS verification_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempt_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY (email),
        KEY (expires_at)
    )"
];

// Execute table creation queries
foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        die("Error creating table: " . $conn->error);
    }
}

// Step 5: Insert demo admin user
$admin_password = password_hash('admin123', PASSWORD_BCRYPT);
$insert_admin = "INSERT INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@schord.com', '$admin_password', 'admin')
ON DUPLICATE KEY UPDATE name=name";

if (!$conn->query($insert_admin)) {
    die("Error inserting admin user: " . $conn->error);
}

$conn->close();

// Display success message
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Initialization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Database Initialized</h1>
        <div class="success">
            <strong>Success!</strong> The database and tables have been created.
        </div>
        <p><strong>Demo Admin Credentials:</strong></p>
        <p>
            Email: <strong>admin@schord.com</strong><br>
            Password: <strong>admin123</strong>
        </p>
        <p>Click the button below to access the login page:</p>
        <a href="../auth/login.php">Go to Login</a>
    </div>
</body>
</html>
