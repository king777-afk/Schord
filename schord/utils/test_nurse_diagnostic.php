<?php
session_start();
include '../config/db.php';

// Set test session
$_SESSION['user'] = array(
    'id' => 1,
    'name' => 'Test Nurse',
    'email' => 'nurse@test.com',
    'role' => 'nurse'
);

echo "<h1>🔍 Nurse Dashboard Diagnostic Test</h1>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0891b2; }
.pass { border-left-color: #10b981; color: #10b981; }
.fail { border-left-color: #ef4444; color: #ef4444; }
</style>";

// Test 1: Database connection
echo "<div class='test pass'>✓ Database connected</div>";

// Test 2: Check tables exist
$tables = array('students', 'clinic_visits', 'health_records', 'users');
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='test pass'>✓ Table '$table' exists</div>";
    } else {
        echo "<div class='test fail'>✗ Table '$table' NOT FOUND</div>";
    }
}

// Test 3: Test each query separately
echo "<h2>Testing Queries:</h2>";

// Query 1: Total students
$result = $conn->query("SELECT COUNT(*) as count FROM students");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<div class='test pass'>✓ Total Students: " . $row['count'] . "</div>";
} else {
    echo "<div class='test fail'>✗ Total Students Query Error: " . $conn->error . "</div>";
}

// Query 2: Today's visits
$result = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE DATE(visit_date) = CURDATE()");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<div class='test pass'>✓ Today's Visits: " . $row['count'] . "</div>";
} else {
    echo "<div class='test fail'>✗ Today's Visits Error: " . $conn->error . "</div>";
}

// Query 3: Pending visits
$result = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='pending'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<div class='test pass'>✓ Pending Visits: " . $row['count'] . "</div>";
} else {
    echo "<div class='test fail'>✗ Pending Visits Error: " . $conn->error . "</div>";
}

// Query 4: Health records with BP
$result = $conn->query("SELECT COUNT(*) as count FROM health_records WHERE blood_pressure IS NOT NULL");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<div class='test pass'>✓ Health Records with BP: " . $row['count'] . "</div>";
} else {
    echo "<div class='test fail'>✗ Health Records Error: " . $conn->error . "</div>";
}

// Query 5: Today's appointments
$result = $conn->query("
    SELECT cv.*, s.name as student_name, s.admission_number 
    FROM clinic_visits cv 
    JOIN students s ON cv.student_id = s.id 
    WHERE DATE(cv.visit_date) = CURDATE() 
    ORDER BY cv.visit_date DESC 
    LIMIT 8
");
if ($result) {
    echo "<div class='test pass'>✓ Today's Appointments: " . $result->num_rows . " records</div>";
} else {
    echo "<div class='test fail'>✗ Today's Appointments Error: " . $conn->error . "</div>";
}

echo "<br><br><a href='nurse_dashboard.php' style='padding: 10px 20px; background: #0891b2; color: white; text-decoration: none; border-radius: 5px;'>Go to Nurse Dashboard →</a>";
?>
