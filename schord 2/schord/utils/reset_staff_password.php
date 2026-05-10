<?php
require __DIR__ . '/../config/db.php';

$email = $argv[1] ?? 'p@gmail.com';
$newPassword = $argv[2] ?? 'admin123';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

$stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
if (!$stmt) {
    die('Prepare failed: ' . $conn->error . PHP_EOL);
}

$stmt->bind_param('ss', $hash, $email);
if (!$stmt->execute()) {
    die('Execute failed: ' . $stmt->error . PHP_EOL);
}

echo 'Updated password hash for ' . $email . PHP_EOL;
echo 'Hash: ' . $hash . PHP_EOL;
echo 'New password: ' . $newPassword . PHP_EOL;
