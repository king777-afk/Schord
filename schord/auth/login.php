<?php
/**
 * SCHoRD - Login Page with Email Verification
 * Professional Centered Portal Design
 */
header('Content-Type: text/html; charset=utf-8');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Try to include config, but don't fail if it doesn't exist
if (file_exists('../config/db.php')) {
    include '../config/db.php';
}

// Quick logout if requested
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $user_role = $_SESSION['user']['role'];
    switch($user_role) {
        case 'admin':
            header("Location: ../dashboards/dashboard_admin.php");
            break;
        case 'nurse':
            header("Location: ../dashboards/nurse_dashboard.php");
            break;
        case 'staff':
            header("Location: ../dashboards/staff_dashboard.php");
            break;
        default:
            header("Location: ../dashboards/dashboard.php");
            break;
    }
    exit();
}

$error = '';
$success = '';

// ===== LOGIN WITH EMAIL & PASSWORD (NO VERIFICATION) =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            $result = $conn->query("SELECT * FROM users WHERE email='$email'");
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Credentials correct - Login directly (no verification)
                    $_SESSION['user'] = $user;
                    
                    // Redirect based on role
                    switch($user['role']) {
                        case 'admin':
                            header("Location: ../dashboards/dashboard_admin.php");
                            break;
                        case 'nurse':
                            header("Location: ../dashboards/nurse_dashboard.php");
                            break;
                        case 'staff':
                            header("Location: ../dashboards/staff_dashboard.php");
                            break;
                        default:
                            header("Location: ../dashboards/dashboard.php");
                            break;
                    }
                    exit();
                } else {
                    $error = 'Invalid credentials';
                }
            } else {
                $error = 'User not found';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCHoRD | Batangas State University</title>
    <style>
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        /* ===== BACKGROUND ===== */
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            position: relative;
            background-image: url('../assets/backgrounds/645452722_1246690147647555_3499458509804140275_n.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Animated background with floating particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at top, rgba(220, 38, 38, 0.2) 0%, transparent 40%),
                        radial-gradient(ellipse at bottom, rgba(153, 27, 27, 0.2) 0%, transparent 50%),
                        linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.3) 100%);
            animation: gradientShift 15s ease infinite;
            z-index: 0;
        }

        @keyframes gradientShift {
            0%, 100% {
                opacity: 0.8;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
        }

        /* Animated background patterns */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            animation: float 20s infinite ease-in-out;
            top: var(--y, 50%);
            left: var(--x, 50%);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            50% { transform: translateY(-30px) translateX(20px); }
        }

        /* ===== MAIN CONTAINER ===== */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100vh;
            position: relative;
            z-index: 1;
            padding: 20px;
        }

        /* ===== WELCOME SECTION ===== */
        .welcome-section {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            background: linear-gradient(135deg, #1a0033 0%, #330066 50%, #0f172a 100%);
            backdrop-filter: blur(10px);
            opacity: 1;
            visibility: visible;
            overflow: auto;
        }

        .welcome-section.hide {
            animation: fadeOutWelcome 0.8s ease-out forwards;
            pointer-events: none;
        }

        @keyframes fadeInWelcome {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeOutWelcome {
            from {
                opacity: 1;
                backdrop-filter: blur(5px);
            }
            to {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
        }

        .welcome-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            opacity: 1;
            visibility: visible;
        }

        @keyframes slideUpContent {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            box-shadow: 0 20px 60px rgba(220, 38, 38, 0.5);
            margin-bottom: 20px;
            opacity: 1;
            visibility: visible;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.5) rotateZ(-10deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotateZ(0);
            }
        }

        .welcome-text {
            max-width: 600px;
        }

        .welcome-text h2 {
            font-size: 52px;
            font-weight: 900;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            letter-spacing: -1px;
            color: white;
            opacity: 1;
            visibility: visible;
        }

        .welcome-text p {
            font-size: 22px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.8;
            margin-bottom: 15px;
            font-weight: 500;
            opacity: 1;
            visibility: visible;
        }

        .typing-text {
            display: inline;
            color: #667eea;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .bsu-text {
            font-size: 20px;
            color: #dc2626;
            font-weight: 700;
            margin: 0 5px;
            letter-spacing: 0.5px;
            display: inline;
            opacity: 1;
            visibility: visible;
        }

        .welcome-subtext {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.75);
            margin-top: 25px;
            font-weight: 500;
            opacity: 1;
            visibility: visible;
        }

        .skip-button {
            margin-top: 50px;
            padding: 14px 35px;
            background: rgba(220, 38, 38, 0.25);
            border: 2px solid #dc2626;
            color: #dc2626;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            letter-spacing: 0.5px;
            opacity: 1;
            visibility: visible;
        }

        .skip-button:hover {
            background: rgba(220, 38, 38, 0.3);
            transform: translateY(-2px);
            border-color: rgba(220, 38, 38, 0.6);
        }

        /* ===== LOGIN CARD ===== */
        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            padding: 50px;
            opacity: 1;
            animation: slideUp 0.8s ease-out;
        }

        .login-container.show {
            animation: slideUp 0.8s ease-out forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== LOGO SECTION ===== */
        .logo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 35px;
            opacity: 1;
            visibility: visible;
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(153, 27, 27, 0.2));
            border: 2px solid rgba(220, 38, 38, 0.4);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 15px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
            animation: floatLogo 3s ease-in-out infinite;
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        .logo-section h1 {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1px;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-section p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            letter-spacing: 0.5px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 13px;
            animation: slideDown 0.45s ease;
            border-left: 4px solid;
            letter-spacing: 0.2px;
            backdrop-filter: blur(10px);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.15);
            color: #fecaca;
            border-left-color: #dc2626;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border-left-color: #22c55e;
        }

        /* ===== FORM GROUPS ===== */
        .form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            font-size: 18px;
            z-index: 2;
            opacity: 0.8;
        }

        .form-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(220, 38, 38, 0.2);
            color: white;
            padding: 13px 16px 13px 50px;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-weight: 500;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-group input:focus {
            outline: none;
            border-color: #dc2626;
            background: rgba(220, 38, 38, 0.12);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
            transform: translateY(-2px);
        }

        /* ===== SUBMIT BUTTON ===== */
        button[type="submit"] {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
            border: 2px solid transparent;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 25px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        button[type="submit"]:hover::before {
            left: 100%;
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(220, 38, 38, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(-1px);
        }

        /* ===== FORM LINKS ===== */
        .form-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-links p {
            color: rgba(255, 255, 255, 0.75);
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .form-links a {
            color: #dc2626;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .form-links a:hover {
            color: #991b1b;
            text-decoration: underline;
        }

        /* ===== VERIFICATION SECTION ===== */
        .verification-section {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .verify-title {
            font-size: 24px;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
            text-align: center;
        }

        .verify-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.5;
        }

        .verify-subtitle strong {
            color: #dc2626;
            font-weight: 700;
        }

        .form-hint {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
        }

        /* Number input styling */
        input[type="text"][pattern="[0-9]*"],
        input[inputmode="numeric"] {
            text-align: center;
            font-size: 24px;
            letter-spacing: 8px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .link-button {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            font-size: 13px;
            padding: 0;
            font-family: inherit;
        }

        .link-button:hover {
            color: #991b1b;
            text-decoration: underline;
        }

        .btn-verify {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .btn-verify::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-verify:hover::before {
            left: 100%;
        }

        .btn-verify:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(220, 38, 38, 0.4);
        }

        .btn-verify:active {
            transform: translateY(-1px);
        }

        /* ===== RESPONSIVE: TABLET ===== */
        @media (max-width: 768px) {
            .login-container {
                width: 90%;
                max-width: 380px;
                padding: 40px 30px;
            }

            .logo-circle {
                width: 60px;
                height: 60px;
                font-size: 32px;
            }

            .logo-section h1 {
                font-size: 28px;
            }

            .form-group input {
                padding: 11px 14px 11px 40px;
                font-size: 14px;
            }

            button[type="submit"] {
                padding: 12px 18px;
                font-size: 14px;
            }
        }

        /* ===== RESPONSIVE: MOBILE ===== */
        @media (max-width: 480px) {
            body::before {
                filter: brightness(0.6) contrast(1.1);
            }

            .login-wrapper {
                padding: 15px;
            }

            .login-container {
                width: 100%;
                max-width: 100%;
                padding: 30px 20px;
                border-radius: 14px;
            }

            .logo-section {
                margin-bottom: 25px;
            }

            .logo-circle {
                width: 55px;
                height: 55px;
                font-size: 28px;
            }

            .logo-section h1 {
                font-size: 24px;
            }

            .logo-section p {
                font-size: 12px;
            }

            .form-group label {
                font-size: 13px;
            }

            .form-group input {
                padding: 10px 13px 10px 38px;
                font-size: 13px;
            }

            button[type="submit"] {
                padding: 11px 16px;
                font-size: 13px;
            }

            .form-links p {
                font-size: 12px;
            }
        }

        /* ===== ACCESSIBILITY ===== */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <!-- LOGO SECTION -->
            <div class="logo-section">
                <div class="logo-circle">🏥</div>
                <h1>SCHoRD</h1>
                <p>School Health & Record Database</p>
            </div>

            <!-- ERROR/SUCCESS ALERTS -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- ===== LOGIN FORM ===== -->
            <form method="POST" action="">
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">✉️</span>
                        <input type="email" id="email" name="email" required placeholder="your.email@g.bsu.edu.ph">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔐</span>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="login">Login</button>

                <!-- Register Link -->
                <div class="form-links">
                    <p>Don't have an account? <a href="register.php">Register here →</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== FORM INITIALIZATION SCRIPT ===== -->
    <script>
        // Focus on email field for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.focus();
            }
        });
        document.addEventListener('keydown', function(e) {
            if ((e.code === 'Space' || e.code === 'Enter') && !document.activeElement.matches('input')) {
                skipWelcome();
            }
        });
    </script>
</body>
</html>
