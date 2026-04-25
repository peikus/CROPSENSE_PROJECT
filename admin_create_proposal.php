<?php
// admin_create_proposal.php - FIXED
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); 
    exit;
}
require_once 'database/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject     = trim($_POST['subject'] ?? '');
    $related     = trim($_POST['related'] ?? '');
    $lead_email  = trim($_POST['lead'] ?? '');
    $to_email    = trim($_POST['to'] ?? '');
    $visibility  = $_POST['visibility'] ?? 'Public';
    $start_date  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $status      = $_POST['status'] ?? 'Draft';
    $content     = trim($_POST['proposal_content'] ?? '');
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;

    if ($visibility === 'Private' && empty($to_email)) {
        $error = "For Private visibility, 'To' email is required.";
    } elseif ($visibility === 'Private' && !empty($to_email)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $to_email);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $error = "The email you entered does not exist in the system.";
        }
        $check->close();
    }

    if (empty($error)) {
        // FIXED: Correct INSERT query
        $stmt = $conn->prepare("INSERT INTO proposals 
            (technician_id, lead, to_email, subject, related, visibility, 
             start_date, due_date, status, content, allow_comments, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param("isssssssssi", 
            $_SESSION['user_id'],   // technician_id = current admin user_id
            $lead_email, 
            $to_email, 
            $subject, 
            $related, 
            $visibility, 
            $start_date, 
            $due_date, 
            $status, 
            $content, 
            $allow_comments
        );

        if ($stmt->execute()) {
            header("Location: admin_proposal.php?success=1");
            exit;
        } else {
            $error = "Failed to create proposal. Database Error: " . $conn->error;
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
    <title>RiceGuard AI • Admin Create Proposal</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        <!-- Header - FIXED Notifications & Profile -->
        <header class="nxl-header">
            <div class="header-wrapper">
                <div class="header-left d-flex align-items-center gap-4">
                    <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                        <div class="hamburger hamburger--arrowturn">
                            <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                        </div>
                    </a>
                    <div class="nxl-navigation-toggle">
                        <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                        <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
                    </div>
                </div>

                <div class="header-right ms-auto">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Search -->
                      


                         <!-- Theme Toggle -->
                        <div class="nxl-h-item dark-light-theme">
                            <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                                <i class="feather-moon"></i>
                            </a>
                            <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                                <i class="feather-sun"></i>
                            </a>
                        </div>

                       

                       
                    </div>
                </div>
            </div>
        </header>

    <!-- Main Content -->
    <div class="nxl-container">
        <div class="page-main-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="page-leftheader">
                    <h4 class="page-title"><i class="feather-plus-square me-2"></i>Create New Proposal</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Proposal Details</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="feather-alert-triangle me-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Proposal created successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="mb-4">
                                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                                        <input type="text" name="subject" required class="form-control" placeholder="Enter proposal subject">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Related <span class="text-danger">*</span></label>
                                        <select name="related" class="form-select" required>
                                            <option value="Technician">Technician</option>
                                            <option value="Farmer">Farmer</option>
                                            <option value="Feature">Feature</option>
                                            <option value="System">System</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Lead <span class="text-danger">*</span></label>
                                        <select name="lead" class="form-select" required>
                                            <option value="">Select Admin / Technician</option>
                                            <?php 
                                            $users = $conn->query("SELECT full_name, email, role FROM users WHERE role IN ('admin', 'technician') ORDER BY role DESC, full_name");
                                            while ($u = $users->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($u['email']) ?>" 
                                                    <?= ($u['email'] === ($_SESSION['email'] ?? '')) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($u['full_name']) ?> 
                                                (<?= htmlspecialchars($u['email']) ?>) 
                                                - <?= ucfirst($u['role']) ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Visibility</label>
                                        <select name="visibility" id="visibility" class="form-select" onchange="toggleToField()">
                                            <option value="Public">Public (All can see)</option>
                                            <option value="Private">Private (Specific email)</option>
                                            <option value="Internal">Internal (Only me)</option>
                                        </select>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="start_date" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Due Date</label>
                                            <input type="date" name="due_date" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div id="toField" style="display:none;" class="mb-4">
                                        <label class="form-label">To <span class="text-danger">*</span></label>
                                        <input type="email" name="to" id="to_email" class="form-control" placeholder="recipient@email.com">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="Sent">Sent</option>
                                            <option value="Draft" selected>Draft</option>
                                            <option value="Open">Open</option>
                                            <option value="Revise">Revise</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="allow_comments" id="allow_comments" value="1" class="form-check-input">
                                            <label for="allow_comments" class="form-check-label">Allow Comments</label>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Proposal Content <span class="text-danger">*</span></label>
                                        <textarea name="proposal_content" rows="12" required class="form-control" placeholder="Enter proposal content..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-4">
                                <a href="admin_proposal.php" class="btn btn-secondary">
                                    <i class="feather-x me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="feather-save me-2"></i>Save Proposal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
    <script>
    function toggleToField() {
        const vis = document.getElementById('visibility').value;
        document.getElementById('toField').style.display = (vis === 'Private') ? 'block' : 'none';
    }
    </script>
</body>
</html>