<?php
/**
 * Database Schema Migration - Add Vital Signs Columns
 * Access this file once to update your database
 */

include '../config/db.php';

echo "<h1>🔨 Database Migration - SCHoRD</h1>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.result { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { border-left: 4px solid #10b981; color: #10b981; }
.error { border-left: 4px solid #ef4444; color: #ef4444; }
</style>";

$queries = array(
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS blood_pressure VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS temperature DECIMAL(5,2) DEFAULT NULL",
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS weight DECIMAL(6,2) DEFAULT NULL",
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS check_date DATETIME DEFAULT NULL",
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS last_check_date DATETIME DEFAULT NULL",
    "ALTER TABLE students MODIFY COLUMN course VARCHAR(100)",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS class VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL"
);

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "<div class='result success'>✓ " . htmlspecialchars(substr($query, 0, 80)) . "...</div>";
    } else {
        echo "<div class='result error'>✗ Error: " . htmlspecialchars($conn->error) . "</div>";
    }
}

echo "<div class='result success'><strong>✓ Database migration completed!</strong><br>
Your database now has all the required columns for the Health Records and Reports pages.</div>";

echo "<br><a href='../dashboards/dashboard_admin.php' style='padding: 10px 20px; background: #0891b2; color: white; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard →</a>";
?>
