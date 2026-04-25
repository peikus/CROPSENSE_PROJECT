<?php
// ========================================================
// FILE: logout.php
// SMART LOGOUT - Role-based redirect for Admin, Farmer & Technician
// ========================================================

ob_start();
session_start();

// Store role before destroying session (in case it's needed)
$role = $_SESSION['role'] ?? 'farmer';

// Destroy session completely
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

ob_end_clean();

// Role-based redirect
switch ($role) {
    case 'admin':
        header("Location: admin_login.php");
        break;
        
    case 'technician':
        header("Location: technician_login.php");
        break;
        
    case 'farmer':
    default:
        header("Location: login_signup.php");
        break;
}

exit;
?>