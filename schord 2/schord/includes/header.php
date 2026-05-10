<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Determine correct asset path for stylesheet when included from subfolders
$assetHref = 'assets/style.css';
if (strpos($_SERVER['PHP_SELF'], '/auth/') !== false || strpos($_SERVER['PHP_SELF'], '/dashboards/') !== false || strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $assetHref = '../assets/style.css';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCHoRD - School Health & Record Database</title>
    <link rel="stylesheet" href="<?php echo $assetHref; ?>">
</head>
<body>
    <?php if (isset($_SESSION['user'])): ?>
    <nav>
        <ul>
            <li class="logo">📋 SCHoRD</li>
            <?php
                // Determine correct paths based on current location
                $inDashboard = strpos($_SERVER['PHP_SELF'], '/dashboards/') !== false;
                $inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
                $dashboardPath = $inDashboard ? '' : 'dashboards/';
                $pagesPath = $inDashboard ? '../pages/' : 'pages/';
                $logoutPath = $inDashboard ? '../auth/logout.php' : 'auth/logout.php';
                
                // Determine which dashboard to link to
                $dashboardFile = 'dashboard.php';
                if ($_SESSION['user']['role'] === 'admin') {
                    $dashboardFile = 'dashboard_admin.php';
                } elseif ($_SESSION['user']['role'] === 'nurse') {
                    $dashboardFile = 'nurse_dashboard.php';
                } elseif ($_SESSION['user']['role'] === 'staff') {
                    $dashboardFile = 'staff_dashboard.php';
                }
            ?>
            <li><a href="<?php echo $dashboardPath . $dashboardFile; ?>" <?php echo (basename($_SERVER['PHP_SELF']) == $dashboardFile) ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <li><a href="<?php echo $pagesPath; ?>students.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'students.php') ? 'class="active"' : ''; ?>>Students</a></li>
            <li><a href="<?php echo $pagesPath; ?>visits.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'visits.php') ? 'class="active"' : ''; ?>>Visits</a></li>
            <li><a href="<?php echo $pagesPath; ?>health_records.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'health_records.php') ? 'class="active"' : ''; ?>>Health</a></li>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <li><a href="<?php echo $pagesPath; ?>users.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'class="active"' : ''; ?>>👥 Users</a></li>
            <?php endif; ?>
            <li style="margin-left: auto;">
                <span style="color: white; margin-right: 1rem;">👤 <?php echo htmlspecialchars($_SESSION['user']['name']); ?> (<?php echo ucfirst($_SESSION['user']['role']); ?>)</span>
                <a href="<?php echo $logoutPath; ?>">Logout</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
            }

            const themeBtn = document.getElementById('themeToggle');
            if (themeBtn) {
                themeBtn.addEventListener('click', function() {
                    document.body.classList.toggle('dark-mode');
                    const isDark = document.body.classList.contains('dark-mode');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            }
        });
    </script>
