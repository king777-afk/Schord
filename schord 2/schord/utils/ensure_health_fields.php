<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../config/db.php';

$queries = [
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS chronic_conditions TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL",
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS medications TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL",
    "ALTER TABLE health_records ADD COLUMN IF NOT EXISTS notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === false) {
        echo "FAILED: " . $sql . " -> " . $conn->error . "\n";
    } else {
        echo "OK: " . $sql . "\n";
    }
}

echo "Migration complete.\n";

?>
