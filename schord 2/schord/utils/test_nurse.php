<?php
session_start();
include '../config/db.php';

// Test user session
$_SESSION['user'] = array(
    'id' => 1,
    'name' => 'Test Nurse',
    'email' => 'nurse@test.com',
    'role' => 'nurse'
);

// Test database connection
echo "Testing Nurse Dashboard...<br><br>";

// Test queries
$test_queries = array(
    "Total Students" => "SELECT COUNT(*) as count FROM students",
    "Today's Visits" => "SELECT COUNT(*) as count FROM clinic_visits WHERE DATE(visit_date) = CURDATE()",
    "Pending Visits" => "SELECT COUNT(*) as count FROM clinic_visits WHERE status='pending'",
    "Health Records" => "SELECT COUNT(*) as count FROM health_records WHERE blood_pressure IS NOT NULL"
);

foreach ($test_queries as $name => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ $name: " . $row['count'] . "<br>";
    } else {
        echo "✗ $name: ERROR - " . $conn->error . "<br>";
    }
}

echo "<br><a href='nurse_dashboard.php'>Go to Nurse Dashboard</a>";
?>
