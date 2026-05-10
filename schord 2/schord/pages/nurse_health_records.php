<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// NURSE ONLY
if (strtolower($user['role']) !== 'nurse') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';
$edit_record = null;
$selected_student = null;

if (isset($_GET['student'])) {
    $student_id = sanitize($_GET['student']);
    $result = $conn->query("SELECT * FROM students WHERE id='$student_id'");
    if ($result->num_rows > 0) {
        $selected_student = $result->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitize($_POST['student_id'] ?? '');
    $allergies = sanitize($_POST['allergies'] ?? '');
    $conditions = sanitize($_POST['conditions'] ?? '');
    $height = sanitize($_POST['height'] ?? '');
    $weight = sanitize($_POST['weight'] ?? '');
    $blood_type = sanitize($_POST['blood_type'] ?? '');
    $blood_pressure = sanitize($_POST['blood_pressure'] ?? '');
    $temperature = sanitize($_POST['temperature'] ?? '');

    if (empty($student_id)) {
        $error = '❌ Student is required';
    } else {
        // Check if record exists
        $check = $conn->query("SELECT id FROM health_records WHERE student_id='$student_id'");
        
        if ($check && $check->num_rows > 0) {
            // Update existing record
            $update_query = "UPDATE health_records SET 
                allergies='$allergies', 
                conditions='$conditions', 
                height='$height', 
                weight='$weight', 
                blood_type='$blood_type', 
                blood_pressure='$blood_pressure', 
                temperature='$temperature',
                updated_at=NOW()
                WHERE student_id='$student_id'";
            
            if ($conn->query($update_query)) {
                $success = '✅ Health record updated successfully!';
            } else {
                $error = '❌ Error updating record: ' . $conn->error;
            }
        } else {
            // Insert new record
            $insert_query = "INSERT INTO health_records (
                student_id, 
                allergies, 
                conditions, 
                height, 
                weight, 
                blood_type, 
                blood_pressure, 
                temperature,
                created_at
            ) VALUES (
                '$student_id', 
                '$allergies', 
                '$conditions', 
                '$height', 
                '$weight', 
                '$blood_type', 
                '$blood_pressure', 
                '$temperature',
                NOW()
            )";
            
            if ($conn->query($insert_query)) {
                $success = '✅ Health record created successfully!';
            } else {
                $error = '❌ Error creating record: ' . $conn->error;
            }
        }
    }
}

$records = $conn->query("
    SELECT hr.*, s.name, s.student_no 
    FROM health_records hr 
    RIGHT JOIN students s ON hr.student_id = s.id 
    ORDER BY s.name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Nurse - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0891b2;
            --primary-light: #06b6d4;
            --primary-dark: #0e7490;
            --accent: #00d9ff;
            --accent-light: #22d3ee;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #082f49;
            --darker: #051e2a;
            --text-dark: #0c2340;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg-light: #ecf9ff;
            --bg-lighter: #cff0f9;
            --bg-card: #ffffff;
            --bg-hover: #f0f9ff;
            --border-color: #e0f2fe;
            --shadow: 0 10px 40px rgba(8, 47, 73, 0.1);
            --shadow-lg: 0 20px 60px rgba(8, 47, 73, 0.15);
            --shadow-glow: 0 0 60px rgba(8, 145, 178, 0.15);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #ecf9ff 0%, #cff0f9 50%, #e0f9ff 100%);
            color: var(--text-dark);
            min-height: 100vh;
            letter-spacing: -0.3px;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #051e2a 0%, #082f49 50%, #0f3d52 100%);
            --bg-light: #164e63;
            --bg-lighter: #0f3d52;
            --bg-card: #082f49;
            --bg-hover: #0f4555;
            --border-color: #0e7490;
            --text-dark: #ecf9ff;
            --text-light: #cbd5e1;
            --text-lighter: #94a3b8;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: 1px solid rgba(6, 182, 212, 0.1);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.2);
            scrollbar-width: thin;
            scrollbar-color: rgba(6, 182, 212, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(6, 182, 212, 0.2);
            border-radius: 10px;
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(6, 182, 212, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(0, 217, 255, 0.05) 100%);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #06b6d4 0%, #00d9ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo {
            font-size: 28px;
            filter: drop-shadow(0 4px 10px rgba(6, 182, 212, 0.3));
        }

        .sidebar-nav {
            padding: 15px 0;
            list-style: none;
        }

        .sidebar-nav li {
            margin: 5px 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            text-align: left;
            width: 100%;
            font-size: 15px;
            position: relative;
            border-left: 3px solid transparent;
            pointer-events: auto;
            z-index: 10;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 0;
            background: rgba(6, 182, 212, 0.1);
            transition: width 0.3s ease;
            pointer-events: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(6, 182, 212, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a:hover::before,
        .sidebar-nav a.active::before {
            width: 20px;
        }

        .sidebar-nav a.active {
            border-left-color: var(--accent);
            text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
        }

        .sidebar-nav .icon {
            font-size: 18px;
            min-width: 20px;
            filter: drop-shadow(0 2px 4px rgba(6, 182, 212, 0.2));
        }

        /* ===== MAIN CONTENT ===== */
        .main-wrapper {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-wrapper.sidebar-collapsed {
            margin-left: 0;
        }

        /* ===== TOP HEADER ===== */
        .top-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 20px;
        }

        body.dark-mode .top-header {
            background: rgba(8, 47, 73, 0.5);
            border-bottom-color: rgba(6, 182, 212, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-dark);
        }

        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .theme-toggle {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* ===== MAIN CONTENT AREA ===== */
        .main-content {
            padding: 35px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        /* ===== FORM STYLES ===== */
        .form-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 12px 40px rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            margin-bottom: 35px;
        }

        body.dark-mode .form-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.1);
        }

        .form-card h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 13px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid rgba(6, 182, 212, 0.2);
            border-radius: 8px;
            color: var(--text-dark);
            font-family: inherit;
            background: rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group select,
        body.dark-mode .form-group textarea {
            background: rgba(8, 47, 73, 0.3);
            border-color: rgba(6, 182, 212, 0.2);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }

        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
        }

        /* ===== TABLE STYLES ===== */
        .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
        }

        body.dark-mode .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(8, 47, 73, 0.8) 100%);
            border-color: rgba(6, 182, 212, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(0, 217, 255, 0.05));
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode thead {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.15), rgba(0, 217, 255, 0.08));
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: var(--bg-hover);
        }

        body.dark-mode tbody tr:hover {
            background: rgba(6, 182, 212, 0.05);
        }

        @media (max-width: 1024px) {
            .main-content {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .menu-toggle {
                display: flex;
            }

            .main-content {
                padding: 20px;
            }

            .top-header {
                padding: 15px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .header-right {
                gap: 10px;
            }

            .top-header {
                flex-wrap: wrap;
            }

            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">👩‍⚕️</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
            <li><a class="sidebar-link" href="../dashboards/nurse_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a class="sidebar-link" href="nurse_patients.php"><span class="icon">👥</span> Patients</a></li>
            <li><a class="sidebar-link" href="nurse_visits.php"><span class="icon">📋</span> Visits</a></li>
            <li><a class="sidebar-link active" href="nurse_health_records.php"><span class="icon">❤️</span> Health Records</a></li>
            <li><a class="sidebar-link" href="nurse_reports.php"><span class="icon">📈</span> Reports</a></li>
            <li style="margin-top: 30px; border-top: 1px solid rgba(6, 182, 212, 0.15); padding-top: 20px;">
                <a class="sidebar-link" href="../auth/logout.php"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <div class="main-wrapper" id="mainWrapper">
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h2 class="header-title">❤️ Health Records</h2>
            </div>
            
            <div class="header-right">
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>
            </div>
        </header>

        <div class="main-content">
            <div class="page-header">
                <h1>Health Records Management</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <h2>➕ Add/Update Health Record</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Student *</label>
                        <select name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name");
                            while ($s = $students->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($selected_student && $selected_student['id'] == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']) . ' (' . $s['student_no'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>🩸 Blood Type</label>
                            <input type="text" name="blood_type" placeholder="e.g., O+">
                        </div>
                        <div class="form-group">
                            <label>📏 Height (cm)</label>
                            <input type="text" name="height" placeholder="e.g., 170">
                        </div>
                        <div class="form-group">
                            <label>⚖️ Weight (kg)</label>
                            <input type="text" name="weight" placeholder="e.g., 65">
                        </div>
                        <div class="form-group">
                            <label>❤️ Blood Pressure</label>
                            <input type="text" name="blood_pressure" placeholder="e.g., 120/80">
                        </div>
                        <div class="form-group">
                            <label>🌡️ Temperature (°C)</label>
                            <input type="text" name="temperature" placeholder="e.g., 37.5">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>🚫 Allergies</label>
                        <textarea name="allergies" placeholder="List any allergies..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>⚕️ Medical Conditions</label>
                        <textarea name="conditions" placeholder="List any medical conditions..."></textarea>
                    </div>

                    <button type="submit" class="btn">✅ Save Health Record</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>👤 Student</th>
                            <th>🩸 Blood Type</th>
                            <th>🚫 Allergies</th>
                            <th>⚕️ Conditions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['name']); ?></td>
                                <td><?php echo $record['blood_type'] ?? '-'; ?></td>
                                <td><?php echo $record['allergies'] ? htmlspecialchars(substr($record['allergies'], 0, 30)) . '...' : '-'; ?></td>
                                <td><?php echo $record['conditions'] ? htmlspecialchars(substr($record['conditions'], 0, 30)) . '...' : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Set active nav item based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.sidebar-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href').split('/').pop();
                if (linkHref === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });

        // Menu Toggle
        document.getElementById('menuToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed');
        });

        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            this.textContent = isDark ? '☀️' : '🌙';
        });

        // Load theme preference
        window.addEventListener('load', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('themeToggle').textContent = '☀️';
            }
        });
    </script>
</body>
</html>
