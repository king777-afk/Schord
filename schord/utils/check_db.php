<?php
/**
 * Database bootstrap / repair script.
 *
 * This creates the required tables if they do not already exist,
 * then seeds the default admin user.
 */

require '../config/db.php';

if (!isset($conn) || $conn->connect_error) {
    die(isset($conn) ? "DB connect failed: " . $conn->connect_error : "DB connection not created");
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
    )"
];

$messages = [];
$messages[] = "Connected to database successfully.";

foreach ($queries as $query) {
    if ($conn->query($query)) {
        $messages[] = "✓ Table checked/created successfully.";
    } else {
        $messages[] = "✗ Error: " . $conn->error;
    }
}

$admin_password = '$2y$10$slYQmyNdGzIn9tdVyNo3Be63DlH.qVETVSMt.eoHxjAuxsQta7u.m';
$conn->query("INSERT INTO users (name, email, password, role) VALUES ('System Admin', 'admin@schord.com', '$admin_password', 'admin') ON DUPLICATE KEY UPDATE name=name");

$result = $conn->query("SHOW TABLES");
if ($result) {
    $messages[] = "Tables currently in database: " . $result->num_rows;
}

foreach ($messages as $message) {
    echo htmlspecialchars($message) . "<br>";
}

echo "<br><strong>Database bootstrap completed.</strong>";
echo "<br><a href='../'>Go to Login</a>";
?>