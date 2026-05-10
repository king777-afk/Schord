<?php
header('Content-Type: text/plain; charset=utf-8');
include __DIR__ . '/../config/db.php';

$res = $conn->query("SELECT COUNT(*) as c FROM students");
if ($res) {
    $count = $res->fetch_assoc()['c'];
    echo "students_count: $count\n";
} else {
    echo "count query failed: " . $conn->error . "\n";
}

$q = $conn->query("SELECT id, student_no, name, course, age, created_at FROM students ORDER BY created_at DESC LIMIT 5");
if ($q) {
    while ($r = $q->fetch_assoc()) {
        echo json_encode($r) . "\n";
    }
} else {
    echo "list query failed: " . $conn->error . "\n";
}
?>
