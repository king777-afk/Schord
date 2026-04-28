<?php
/**
 * Quick Test - Check if Login Page Shows Verification
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>SCHoRD - Quick 2FA Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .info { border-left: 4px solid #17a2b8; }
        code { background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; border-radius: 3px; }
        input, button { padding: 10px; margin: 10px 0; font-size: 16px; }
        button { background: #667eea; color: white; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background: #764ba2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 SCHoRD 2FA Quick Test</h1>

        <div class="box info">
            <h2>Test Verification System</h2>
            <p>This page helps you test the email verification system step by step.</p>
        </div>

        <?php
        // Check database table
        $tableCheck = $conn->query("SHOW TABLES LIKE 'verification_codes'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            echo "<div class='box success'><h3>✓ Verification Table Exists</h3></div>";
        } else {
            echo "<div class='box error'><h3>✗ Verification Table Does NOT Exist!</h3>";
            echo "<p><strong>ACTION REQUIRED:</strong> Visit <code>/utils/add_verification_table.php</code> to create the table.</p>";
            echo "</div>";
            exit;
        }

        // Check functions
        echo "<div class='box info'><h3>Checking Functions...</h3>";
        $functions = ['generateVerificationCode', 'sendVerificationEmail', 'createVerificationCode', 'verifyCode'];
        foreach ($functions as $func) {
            if (function_exists($func)) {
                echo "✓ $func()<br>";
            } else {
                echo "✗ $func() NOT FOUND<br>";
            }
        }
        echo "</div>";

        // Simulate login
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simulate_login'])) {
            $email = sanitize($_POST['email']);
            
            echo "<div class='box info'><h3>Step 1: Simulating Login</h3>";
            echo "Email: " . htmlspecialchars($email) . "<br>";
            
            // Check if user exists
            $userCheck = $conn->query("SELECT id FROM users WHERE email='$email'");
            if ($userCheck && $userCheck->num_rows > 0) {
                echo "✓ User found<br>";
                
                // Generate code
                $codeGen = createVerificationCode($email);
                if ($codeGen['success']) {
                    echo "✓ Code Generated: <code>" . $codeGen['code'] . "</code>";
                    echo "Store this code! You'll need it in Step 2.<br>";
                    
                    // Try to email
                    echo "Attempting to send email...";
                    $sent = sendVerificationEmail($email, $codeGen['code']);
                    if ($sent) {
                        echo "<br>✓ Email sent or queued<br>";
                        echo "Check your inbox (and spam folder) for the verification code.<br>";
                    } else {
                        echo "<br>✗ Email send failed<br>";
                        echo "This usually means mail() is not configured on the server.<br>";
                        echo "You can manually use the code above to test.<br>";
                    }
                    
                    // Set session for step 2
                    $_SESSION['test_email'] = $email;
                    $_SESSION['test_code'] = $codeGen['code'];
                } else {
                    echo "✗ Code generation failed";
                }
            } else {
                echo "✗ User not found<br>";
                echo "Available users: ";
                $users = $conn->query("SELECT email FROM users LIMIT 5");
                while ($user = $users->fetch_assoc()) {
                    echo htmlspecialchars($user['email']) . " ";
                }
            }
            echo "</div>";
        }

        // Verify code
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_test'])) {
            echo "<div class='box info'><h3>Step 2: Verifying Code</h3>";
            $email = $_SESSION['test_email'] ?? sanitize($_POST['email']);
            $code = sanitize($_POST['code']);
            
            echo "Email: " . htmlspecialchars($email) . "<br>";
            echo "Code: " . htmlspecialchars($code) . "<br>";
            
            $verify = verifyCode($email, $code);
            if ($verify['success']) {
                echo "<div style='color: green; font-weight: bold;'>✓ CODE VERIFIED!</div>";
                echo "This means the verification system is working correctly!<br>";
            } else {
                echo "<div style='color: red; font-weight: bold;'>✗ Verification Failed</div>";
                echo "Error: " . htmlspecialchars($verify['error']) . "<br>";
            }
            echo "</div>";
        }
        ?>

        <div class="box">
            <h3>Step 1: Generate Verification Code</h3>
            <form method="POST">
                <label>Email:
                    <input type="email" name="email" value="admin@schord.com" required>
                </label>
                <button type="submit" name="simulate_login">Generate Code</button>
            </form>
        </div>

        <?php if (isset($_SESSION['test_code'])): ?>
        <div class="box">
            <h3>Step 2: Verify the Code</h3>
            <p>Enter the code that was generated above (or check your email)</p>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['test_email']); ?>">
                <label>Code (6 digits):
                    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>
                </label>
                <button type="submit" name="verify_test">Verify Code</button>
            </form>
            <p style="color: orange;"><strong>Tip:</strong> The generated code is: <code><?php echo $_SESSION['test_code']; ?></code></p>
        </div>
        <?php endif; ?>

        <div class="box info">
            <h3>Next: Test Real Login</h3>
            <p><a href="../login.php" style="color: #667eea; text-decoration: none; font-weight: bold;">→ Go to Login Page →</a></p>
            <p>Now test the actual login page to see if the verification form appears.</p>
        </div>

    </div>
</body>
</html>
<?php $conn->close(); ?>
