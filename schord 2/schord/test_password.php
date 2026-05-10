<?php
echo "Testing password verification...\n\n";

$hash = '$2y$10$slYQmyNdGzin7olVN3/p2OPST9/PgBkqquzi.Ge3SEUgVF3GA9H4m';
$password = 'admin123';

echo "Hash: $hash\n";
echo "Password: $password\n\n";

if (password_verify($password, $hash)) {
    echo "✅ Password is CORRECT - hash matches!\n";
} else {
    echo "❌ Password is WRONG - hash does NOT match!\n";
}

// Generate a new hash for admin123 to use
echo "\n\nGenerating new hash for admin123...\n";
$new_hash = password_hash('admin123', PASSWORD_BCRYPT);
echo "New Hash: $new_hash\n";

// Test the new hash
if (password_verify('admin123', $new_hash)) {
    echo "✅ New hash works!\n";
    echo "\nUse this hash in the database:\n";
    echo $new_hash . "\n";
}
?>
