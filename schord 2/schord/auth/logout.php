<?php 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy session completely
session_unset();
session_destroy();

// Redirect to login using relative path
header("Location: login.php");
exit();
?>