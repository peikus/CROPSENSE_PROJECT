<?php
// farmer_account_settings.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: login_signup.php");
    exit;
}

require_once 'database/database.php';

$user_id = $_SESSION['user_id'];
$msg = '';

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (password_verify($current, $row['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 12 && preg_match('/[A-Z]/', $new) && preg_match('/[a-z]/', $new) && preg_match('/[0-9]/', $new) && preg_match('/[!@#$%^&*]/', $new)) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed, $user_id);
                    if ($stmt->execute()) {
                        $msg = '<div class="alert alert-success">Password updated successfully!</div>';
                    }
                    $stmt->close();
                } else {
                    $msg = '<div class="alert alert-danger">Password must be at least 12 characters with uppercase, lowercase, number, and special character.</div>';
                }
            } else {
                $msg = '<div class="alert alert-danger">New passwords do not match.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">Current password is incorrect.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Account Settings</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body data-bs-theme="dark">

    <!-- Navigation & Header (same as dashboard) -->
    <!-- Copy the nav and header from farmer_dashboard.php here or include as partial -->

    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <h5 class="m-b-10">Account Settings</h5>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <?= $msg ?>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label>Current Password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>New Password</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                        <div class="mb-4">
                                            <label>Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">Update Password</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-body text-center">
                                    <p class="text-muted">Forgot your password? Use the login page to request a temporary password via email.</p>
                                    <a href="login_signup.php" class="btn btn-outline-light">Go to Login Page</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>