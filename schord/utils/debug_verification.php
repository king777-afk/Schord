<?php
/**
 * SCHoRD - Email Verification System Debug Script
 * Helps identify issues with the 2FA system
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db.php';

echo "<style>
body { font-family: Arial; background: #f0f0f0; padding: 20px; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #667eea; }
.success { border-left-color: #28a745; }
.error { border-left-color: #dc3545; }
.warning { border-left-color: #ffc107; }
.info { border-left-color: #17a2b8; }
h2 { color: #667eea; margin-top: 20px; }
code { background: #f5f5f5; padding: 5px 10px; border-radius: 3px; display: block; margin: 10px 0; overflow-x: auto; }
.test { margin: 15px 0; }
</style>";

echo "<h1>🔧 SCHoRD Email Verification Debug Report</h1>";

// ===== TEST 1: Database Connection =====
echo "<h2>✅ Test 1: Database Connection</h2>";
if ($conn && $conn->connect_error == '') {
    echo "<div class='box success'><strong>✓ Connected to database:</strong> " . $conn->server_info . "</div>";
} else {
    echo "<div class='box error'><strong>✗ Database connection failed:</strong> " . $conn->connect_error . "</div>";
    exit;
}

// ===== TEST 2: Check if verification_codes table exists =====
echo "<h2>✅ Test 2: Verification Codes Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'verification_codes'");
if ($result && $result->num_rows > 0) {
    echo "<div class='box success'><strong>✓ Table exists:</strong> verification_codes</div>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE verification_codes");
    echo "<div class='box info'><strong>Table Structure:</strong><code>";
    while ($row = $structure->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - " . ($row['Null'] == 'YES' ? 'Nullable' : 'NOT NULL') . "\n";
    }
    echo "</code></div>";
} else {
    echo "<div class='box error'><strong>✗ Table does NOT exist.</strong></div>";
    echo "<div class='box warning'><strong>Action Required:</strong> Run the migration script at <code>/utils/add_verification_table.php</code></div>";
}

// ===== TEST 3: Check users table =====
echo "<h2>✅ Test 3: Users Table</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "<div class='box success'><strong>✓ Users in database:</strong> " . $row['count'] . "</div>";

// Show users for testing
$users = $conn->query("SELECT id, email, role FROM users");
if ($users && $users->num_rows > 0) {
    echo "<div class='box info'><strong>Available Users for Testing:</strong><code>";
    while ($user = $users->fetch_assoc()) {
        echo "Email: {$user['email']} (Role: {$user['role']})\n";
    }
    echo "</code></div>";
}

// ===== TEST 4: Check mail() function =====
echo "<h2>✅ Test 4: Mail Function</h2>";
if (function_exists('mail')) {
    echo "<div class='box success'><strong>✓ mail() function is available</strong></div>";
    
    // Try to send a test email
    $testEmail = "test-" . time() . "@schord.test";
    $testSubject = "SCHoRD Test Email";
    $testMessage = "This is a test email from SCHoRD verification system.";
    $testHeaders = "From: noreply@schord.bsu.edu.ph\r\nContent-Type: text/html; charset=UTF-8";
    
    echo "<div class='box warning'><strong>ⓘ Test Email Send:</strong>";
    echo "<br>Note: This may not actually send if the server doesn't have mail configured.";
    echo "<br>Check your server's mail logs for delivery status.";
    echo "</div>";
} else {
    echo "<div class='box error'><strong>✗ mail() function is NOT available</strong></div>";
    echo "<div class='box warning'><strong>⚠ This means emails cannot be sent!</strong> Contact your hosting provider.</div>";
}

// ===== TEST 5: Check Required PHP Functions =====
echo "<h2>✅ Test 5: Required PHP Functions</h2>";
$functions = ['date', 'strtotime', 'filter_var', 'password_verify', 'ctype_digit', 'random_int'];
$missing = [];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<div class='box success'>✓ " . $func . "()</div>";
    } else {
        echo "<div class='box error'>✗ " . $func . "() MISSING</div>";
        $missing[] = $func;
    }
}

// ===== TEST 6: Session Test =====
echo "<h2>✅ Test 6: Session Functionality</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='box success'><strong>✓ Session is active</strong></div>";
    $_SESSION['test_verification'] = 'test_' . time();
    echo "<div class='box info'><strong>✓ Session variables can be set</strong></div>";
} else {
    echo "<div class='box error'><strong>✗ Session is not active</strong></div>";
}

// ===== TEST 7: Check login.php changes =====
echo "<h2>✅ Test 7: Login.php Configuration</h2>";
$loginContent = file_get_contents('../login.php');

$checks = [
    'pending_verification' => 'Verification session variable',
    'createVerificationCode' => 'Code creation function call',
    'sendVerificationEmail' => 'Email sending function call',
    'verification-section' => 'Verification form HTML',
    'verify_code' => 'Code verification form name'
];

foreach ($checks as $keyword => $description) {
    if (strpos($loginContent, $keyword) !== false) {
        echo "<div class='box success'>✓ " . $description . "</div>";
    } else {
        echo "<div class='box error'>✗ " . $description . " - NOT FOUND</div>";
    }
}

// ===== TEST 8: Check db.php functions =====
echo "<h2>✅ Test 8: Email Functions in db.php</h2>";
$dbContent = file_get_contents('../config/db.php');

$functions_to_check = [
    'function generateVerificationCode' => 'generateVerificationCode()',
    'function sendVerificationEmail' => 'sendVerificationEmail()',
    'function createVerificationCode' => 'createVerificationCode()',
    'function verifyCode' => 'verifyCode()',
    'function cleanupExpiredCodes' => 'cleanupExpiredCodes()'
];

foreach ($functions_to_check as $keyword => $funcName) {
    if (strpos($dbContent, $keyword) !== false) {
        echo "<div class='box success'>✓ " . $funcName . " is defined</div>";
    } else {
        echo "<div class='box error'>✗ " . $funcName . " - NOT FOUND</div>";
    }
}

// ===== SUMMARY =====
echo "<h2>📋 Summary & Next Steps</h2>";

$issues = [];

if (!$conn->query("SHOW TABLES LIKE 'verification_codes'") || $conn->query("SHOW TABLES LIKE 'verification_codes'")->num_rows == 0) {
    $issues[] = "✗ Verification codes table does not exist - Run migration script";
}

if (!function_exists('mail')) {
    $issues[] = "✗ mail() function not available - Contact hosting provider";
}

if (!count($issues)) {
    echo "<div class='box success'><h3>✅ All Systems Go!</h3>";
    echo "<p>The email verification system appears to be properly configured.</p>";
    echo "<p><strong>To test:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <code>/auth/login.php</code></li>";
    echo "<li>Enter valid credentials (Example: admin@schord.com)</li>";
    echo "<li>Should see verification code email form</li>";
    echo "<li>Check inbox for 6-digit code</li>";
    echo "<li>Enter code to complete login</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='box error'><h3>⚠️ Issues Found:</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>" . $issue . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// ===== TEST PANEL =====
echo "<h2>🧪 Manual Test Panel</h2>";
echo "<div class='box info'>";
echo "<h3>Generate Test Code</h3>";
echo "<form method='POST'>";
echo "<label>Email: <input type='email' name='test_email' value='admin@schord.com' required></label>";
echo "<button name='test_generate' type='submit'>Generate & Email Code</button>";
echo "</form>";

if (isset($_POST['test_generate']) && isset($_POST['test_email'])) {
    $testEmail = sanitize($_POST['test_email']);
    echo "<hr>";
    echo "<strong>Testing with: " . $testEmail . "</strong><br>";
    
    // Check if table exists first
    if ($conn->query("SHOW TABLES LIKE 'verification_codes'")->num_rows > 0) {
        $codeResult = createVerificationCode($testEmail);
        if ($codeResult['success']) {
            echo "<div style='color: green;'><strong>✓ Code Generated:</strong> " . $codeResult['code'] . "</div>";
            echo "<div style='color: orange;'><strong>Sending email...</strong></div>";
            $sent = sendVerificationEmail($testEmail, $codeResult['code']);
            if ($sent) {
                echo "<div style='color: green;'><strong>✓ Email sent (or queued)</strong></div>";
                echo "<p>Check inbox for the email. It may take a minute to arrive.</p>";
            } else {
                echo "<div style='color: red;'><strong>✗ Email send failed</strong></div>";
                echo "<p>This usually means your hosting provider doesn't have mail() configured.</p>";
            }
        } else {
            echo "<div style='color: red;'>" . $codeResult['error'] . "</div>";
        }
    } else {
        echo "<div style='color: red;'><strong>✗ verification_codes table doesn't exist!</strong></div>";
        echo "<p>Run: <code>/utils/add_verification_table.php</code></p>";
    }
}

echo "</div>";

$conn->close();
?>
