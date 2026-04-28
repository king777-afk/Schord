<?php
session_start();

// If user is logged in, redirect to appropriate dashboard based on role
if (isset($_SESSION['user'])) {
    switch($_SESSION['user']['role']) {
        case 'admin':
            header("Location: dashboards/dashboard_admin.php");
            break;
        case 'nurse':
            header("Location: dashboards/nurse_dashboard.php");
            break;
        case 'staff':
            header("Location: dashboards/staff_dashboard.php");
            break;
        default:
            header("Location: dashboards/dashboard.php");
            break;
    }
    exit();
}

// Otherwise redirect to login
header("Location: auth/login.php");
exit();
?>