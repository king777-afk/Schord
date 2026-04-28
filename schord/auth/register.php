<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include '../config/db.php';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email already exists
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            if ($conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')")) {
                $success = 'Registration successful! You can now login.';
                // Clear form
                $_POST = array();
            } else {
                $error = 'Registration failed: ' . $conn->error;
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
    <title>Register - SCHoRD</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../assets/backgrounds/645452722_1246690147647555_3499458509804140275_n.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            z-index: 0;
            filter: brightness(0.7) contrast(1.1);
        }

        /* ===== MAIN CONTAINER ===== */
        .register-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
            position: relative;
            z-index: 2;
            padding: 20px;
        }

        /* ===== REGISTER CARD ===== */
        .register-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            padding: 45px;
            animation: slideUp 0.6s ease-out;
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
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .logo-section h1 {
            font-size: 32px;
            color: white;
            margin-bottom: 8px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .logo-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
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
            background: rgba(220, 38, 38, 0.2);
            color: #fecaca;
            border-left-color: #dc2626;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
            border-left-color: #22c55e;
        }

        .alert-success a {
            color: #86efac;
            text-decoration: underline;
            font-weight: 700;
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
            color: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.3px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            font-size: 18px;
            z-index: 2;
            opacity: 0.8;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 16px 12px 45px;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-family: inherit;
        }

        .form-group select {
            padding: 12px 16px;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(220, 38, 38, 0.8);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
            transform: translateY(-2px);
        }

        .form-group select option {
            background: #2c3e50;
            color: white;
        }

        /* ===== SUBMIT BUTTON ===== */
        button[type="submit"] {
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
            border: 2px solid transparent;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(220, 38, 38, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        /* ===== FORM LINKS ===== */
        .form-links {
            text-align: center;
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .form-links p {
            color: rgba(255, 255, 255, 0.85);
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .form-links a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .form-links a:hover {
            color: #fcd34d;
            text-decoration: underline;
        }

        /* ===== RESPONSIVE: TABLET ===== */
        @media (max-width: 768px) {
            .register-container {
                width: 90%;
                max-width: 400px;
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

            .form-group input,
            .form-group select {
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

            .register-wrapper {
                padding: 15px;
            }

            .register-container {
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

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                font-size: 13px;
            }

            .form-group input,
            .form-group select {
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
    <div class="register-wrapper">
        <div class="register-container">
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
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                    <br><a href="login.php">Go to Login →</a>
                </div>
            <?php endif; ?>

            <!-- REGISTER FORM -->
            <form method="POST">
                <!-- Full Name Field -->
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">✉️</span>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔐</span>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">✔️</span>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select a role</option>
                        <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="nurse" <?php echo (isset($_POST['role']) && $_POST['role'] == 'nurse') ? 'selected' : ''; ?>>Nurse</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="register">Register</button>

                <!-- Login Link -->
                <div class="form-links">
                    <p>Already have an account? <a href="login.php">Login here →</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>