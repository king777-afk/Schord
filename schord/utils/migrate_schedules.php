<?php
// Database Migration Script for nurse_schedules table
header('Content-Type: text/html; charset=utf-8');
include 'config/db.php';

echo "<h1>🔄 Database Migration - Nurse Schedules</h1>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'nurse_schedules'");

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ nurse_schedules table already exists.</p>";
} else {
    echo "<p>Creating nurse_schedules table...</p>";
    
    $sql = "CREATE TABLE IF NOT EXISTS nurse_schedules (
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
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✅ nurse_schedules table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

echo "<p><a href='dashboards/nurse_dashboard.php'>← Go back to Dashboard</a></p>";
?>
