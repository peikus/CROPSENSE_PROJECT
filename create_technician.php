<?php
// ========================================================
// FILE: create_technician.php
// UPDATED: Full NXL Template Alignment (Navigation + Header)
// ========================================================

session_start();
require_once __DIR__ . '/database/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_signup.php");
    exit;
}

$msg = '';
$msg_type = 'success';

if (isset($_POST['create_tech'])) {
    $tech_name  = trim($_POST['tech_name'] ?? '');
    $tech_email = trim($_POST['tech_email'] ?? '');
    $tech_pass  = $_POST['tech_pass'] ?? '';

    if (empty($tech_name) || empty($tech_email) || empty($tech_pass)) {
        $msg = 'Please fill in all fields.';
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $tech_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = 'This email is already registered.';
            $msg_type = 'error';
        } else {
            $hashed = password_hash($tech_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users 
                (full_name, email, password, role, status, created_at) 
                VALUES (?, ?, ?, 'technician', 'approved', NOW())");
            $stmt->bind_param("sss", $tech_name, $tech_email, $hashed);
            
            if ($stmt->execute()) {
                $msg = 'Technician account created successfully!';
            } else {
                $msg = 'Database error: ' . $conn->error;
                $msg_type = 'error';
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Technician • RiceGuard AI</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .card {
            border-radius: 16px;
        }
        .password-container {
            position: relative;
        }
        .password-container input {
            width: 100%;
            padding-right: 50px;
        }
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            font-size: 1.1rem;
        }
        .toggle-password:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body data-bs-theme="dark">

      <!-- Navigation (unchanged) -->
        <nav class="nxl-navigation">
            <div class="navbar-wrapper">
                <div class="m-header">
                    <a href="admin_dashboard.php" class="b-brand">
                        <img src="assets/images/logo-full.png" alt="" class="logo logo-lg" />
                        <img src="assets/images/logo-abbr.png" alt="" class="logo logo-sm" />
                    </a>
                </div>
                <div class="navbar-content">
                    <ul class="nxl-navbar">
                        <li class="nxl-item nxl-caption"><label>Navigation</label></li>
                        <li class="nxl-item active">
                            <a href="admin_dashboard.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-airplay"></i></span>
                                <span class="nxl-mtext">Dashboard</span>
                            </a>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-cast"></i></span>
                                <span class="nxl-mtext">Knowledge</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="knowledge_editor.php">Knowledge Editor</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="knowledge_management.php">Knowledge Management</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="knowledge_modifier.php">Knowledge Modifier</a></li>
                            </ul>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Proposal</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="admin_proposal.php">All Proposals</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="admin_create_proposal.php">Create Proposal</a></li>
                            </ul>
                        </li>
                        
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-users"></i></span>
                                <span class="nxl-mtext">User Logs</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="admin_farmerlog.php">Farmers</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="admin_technicianlog.php">Technicians</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="create_technician.php">Create Technicians</a></li>
                            </ul>
                        </li>

                        <li class="nxl-item">
                            <a href="admin_all_user_history.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-clock"></i></span>
                                <span class="nxl-mtext">Diagnoses History</span>
                            </a>
                        </li>

                        <li class="nxl-item">
                            <a href="admin_announcement.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-volume-2"></i></span>
                                <span class="nxl-mtext">Announcements</span>
                            </a>
                        </li>
                    </ul>              
                </div>
            </div>
        </nav>
<!-- ... (keep your existing nav and header code here) ... -->

        <header class="nxl-header">
                <div class="header-wrapper">
                    <div class="header-left d-flex align-items-center gap-4">
                        <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                            <div class="hamburger hamburger--arrowturn">
                                <div class="hamburger-box">
                                    <div class="hamburger-inner"></div>
                                </div>
                            </div>
                        </a>
                        <div class="nxl-navigation-toggle">
                            <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                            <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="header-right ms-auto">
                        <div class="d-flex align-items-center">
                        
                            <div class="nxl-h-item dark-light-theme">
                                <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button"><i class="feather-moon"></i></a>
                                <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none"><i class="feather-sun"></i></a>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </header>

    <!-- ==================== MAIN CONTENT ==================== -->
    <div class="nxl-container">
        <div class="main-body">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-8">
                        <div class="card mt-5">
                            <div class="card-body p-5">
                                <div class="text-center mb-8">
                                    <i class="fas fa-user-plus text-6xl text-cyan-400 mb-4"></i>
                                    <h1 class="h3 fw-bold">Create New Technician</h1>
                                    <p class="text-muted">Add a new technician account to the RiceGuard AI system</p>
                                </div>

                                <?php if ($msg): ?>
                                    <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-danger' ?> text-center">
                                        <?= htmlspecialchars($msg) ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="space-y-6">
                                    <div class="mb-4">
                                        <label class="form-label text-muted">Full Name</label>
                                        <input type="text" name="tech_name" required 
                                               class="form-control  text-light border-secondary">
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label text-muted">Email Address</label>
                                        <input type="email" name="tech_email" required 
                                               class="form-control text-light border-secondary">
                                    </div>

                                    <div class="mb-4 password-container">
                                        <label class="form-label text-muted">Password</label>
                                        <input type="password" name="tech_pass" id="tech_pass" required 
                                               class="form-control text-light border-secondary">
                                        <span class="toggle-password" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>

                                    <button name="create_tech" type="submit" 
                                            class="btn btn-primary w-100 py-3 fw-semibold">
                                        Create Technician Account
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <p class="text-muted small">
                                The new technician can log in immediately using the credentials above.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/vendors/js/daterangepicker.min.js"></script>
    <script src="assets/vendors/js/apexcharts.min.js"></script>
    <script src="assets/vendors/js/circle-progress.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/dashboard-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    // Password Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('tech_pass');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    });
    </script>

</body>
</html>