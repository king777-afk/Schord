<?php
require 'config/db.php';

$correct_hash = '$2y$10$A6Xo4WBn2e0ov.O4u6DIC.fs8frSPRdm9IQMtSFysDWzwSPKBivvm';

$result = $conn->query("UPDATE users SET password='$correct_hash' WHERE email='admin@schord.com'");

if ($result) {
    echo "✅ Admin password FIXED with correct hash!\n";
    echo "Email: admin@schord.com\n";
    echo "Password: admin123\n";
    echo "\nYou can now login!\n";
} else {
    echo "❌ Error: " . $conn->error;
}

$conn->close();
?>
