<?php
/**
 * SCHoRD - Add Verification Codes Table
 * This script creates the verification_codes table for email 2FA
 * Run this once to set up the email verification system
 */

include '../config/db.php';

echo "🔧 Setting up Email Verification System...\n\n";

// Create verification_codes table
$sql = "CREATE TABLE IF NOT EXISTS verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempt_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY (email),
    KEY (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✅ Database table 'verification_codes' created successfully!\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
    exit(1);
}

echo "\n✨ Email Verification System Setup Complete!\n";
echo "📧 Users will now receive verification codes when logging in.\n";
echo "⏱️ Codes expire after 15 minutes.\n";
echo "🔐 Maximum 5 failed attempts per code.\n\n";

echo "Next steps:\n";
echo "1. Ensure your server has mail() enabled\n";
echo "2. Configure email settings in config/db.php if needed\n";
echo "3. Test by logging in at auth/login.php\n";

$conn->close();
?>
