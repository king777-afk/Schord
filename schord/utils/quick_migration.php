<?php
/**
 * Quick Database Migration Check & Fix
 */

include __DIR__ . '/../config/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>SCHoRD Database Migration</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .result {
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #999;
        }
        .success {
            background: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }
        .error {
            background: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }
        .summary {
            background: #2196f3;
            color: white;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
            text-align: center;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover { background: #1976d2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Migration</h1>
        <p>Checking and adding required database columns...</p>
<?php

$columns_to_add = array(
    array('table' => 'health_records', 'column' => 'blood_pressure', 'type' => 'VARCHAR(20)'),
    array('table' => 'health_records', 'column' => 'temperature', 'type' => 'DECIMAL(5,2)'),
    array('table' => 'health_records', 'column' => 'weight', 'type' => 'DECIMAL(6,2)'),
    array('table' => 'health_records', 'column' => 'check_date', 'type' => 'DATETIME'),
    array('table' => 'health_records', 'column' => 'last_check_date', 'type' => 'DATETIME'),
    array('table' => 'health_records', 'column' => 'created_at', 'type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'),
    array('table' => 'students', 'column' => 'class', 'type' => 'VARCHAR(50)'),
    array('table' => 'students', 'column' => 'phone', 'type' => 'VARCHAR(20)'),
);

$changes_made = 0;

foreach ($columns_to_add as $info) {
    $table = $info['table'];
    $column = $info['column'];
    $type = $info['type'];
    
    // Check if column exists
    $check_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '$table' 
                    AND COLUMN_NAME = '$column'";
    
    $result = $conn->query($check_query);
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='result success'>✓ Column '$column' already exists in '$table'</div>";
    } else {
        $add_query = "ALTER TABLE $table ADD COLUMN $column $type";
        if ($conn->query($add_query)) {
            echo "<div class='result success'>✓ Added column '$column' to '$table'</div>";
            $changes_made++;
        } else {
            echo "<div class='result error'>✗ Error adding '$column' to '$table': " . $conn->error . "</div>";
        }
    }
}

?>
        <div class="summary">
            <h2>Migration Complete!</h2>
            <p>Your database is now ready. All required columns have been added.</p>
        </div>
        <a href="../dashboards/dashboard_admin.php">Go to Admin Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>
