<?php
// admin_edit_proposal.php (UPDATED WITH TEMPLATE)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit;
}
require_once 'database/database.php';

$id = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $conn->prepare("SELECT * FROM proposals WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: admin_proposal.php"); exit;
}

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
            $error = "The email does not exist.";
        }
        $check->close();
    }

    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE proposals SET 
            lead = ?, to_email = ?, subject = ?, related = ?, visibility = ?, 
            start_date = ?, due_date = ?, status = ?, content = ?, allow_comments = ?
            WHERE id = ?");

        $stmt->bind_param("ssssssssssi",
            $lead_email, $to_email, $subject, $related, $visibility,
            $start_date, $due_date, $status, $content, $allow_comments, $id
        );

        if ($stmt->execute()) {
            header("Location: admin_proposal.php?success=1");
            exit;
        } else {
            $error = "Failed to update proposal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Edit Proposal</title>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css" />
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

<!-- Main Content -->
<div class="nxl-container">
    <div class="page-main-header">
        <h4 class="page-title">
            <i class="feather-edit me-2"></i>Edit Proposal #<?= str_pad($id,6,'0',STR_PAD_LEFT) ?>
        </h4>
    </div>

    <div class="card">
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">

                    <!-- LEFT -->
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control"
                                   value="<?= htmlspecialchars($row['subject']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Related</label>
                            <select name="related" class="form-select">
                                <option <?= $row['related']=='Technician'?'selected':'' ?>>Technician</option>
                                <option <?= $row['related']=='Farmer'?'selected':'' ?>>Farmer</option>
                                <option <?= $row['related']=='Feature'?'selected':'' ?>>Feature</option>
                                <option <?= $row['related']=='System'?'selected':'' ?>>System</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Lead</label>
                            <select name="lead" class="form-select">
                                <?php
                                $users = $conn->query("SELECT full_name,email,role FROM users WHERE role IN ('admin','technician')");
                                while ($u = $users->fetch_assoc()):
                                ?>
                                <option value="<?= $u['email'] ?>"
                                    <?= ($u['email']==$row['lead'])?'selected':'' ?>>
                                    <?= $u['full_name'] ?> (<?= $u['role'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Visibility</label>
                            <select name="visibility" id="visibility" class="form-select" onchange="toggleToField()">
                                <option value="Public" <?= $row['visibility']=='Public'?'selected':'' ?>>Public</option>
                                <option value="Private" <?= $row['visibility']=='Private'?'selected':'' ?>>Private</option>
                                <option value="Internal" <?= $row['visibility']=='Internal'?'selected':'' ?>>Internal</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" name="start_date" class="form-control"
                                       value="<?= $row['start_date'] ?>">
                            </div>
                            <div class="col-md-6">
                                <input type="date" name="due_date" class="form-control"
                                       value="<?= $row['due_date'] ?>">
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT -->
                    <div class="col-lg-6">

                        <div id="toField" style="display: <?= $row['visibility']=='Private'?'block':'none' ?>">
                            <label class="form-label">To</label>
                            <input type="email" name="to" class="form-control"
                                   value="<?= htmlspecialchars($row['to_email']) ?>">
                        </div>

                        <div class="mb-4 mt-3">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option <?= $row['status']=='Sent'?'selected':'' ?>>Sent</option>
                                <option <?= $row['status']=='Draft'?'selected':'' ?>>Draft</option>
                                <option <?= $row['status']=='Open'?'selected':'' ?>>Open</option>
                                <option <?= $row['status']=='Revise'?'selected':'' ?>>Revise</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <input type="checkbox" name="allow_comments" value="1"
                                <?= $row['allow_comments']?'checked':'' ?>>
                            Allow Comments
                        </div>

                        <div class="mb-4">
                            <textarea name="proposal_content" rows="10" class="form-control"><?= htmlspecialchars($row['content']) ?></textarea>
                        </div>

                    </div>
                </div>

                <div class="mt-4 d-flex gap-3">
                    <a href="admin_proposals.php" class="btn btn-secondary">Cancel</a>
                    <button class="btn btn-primary">Update Proposal</button>
                </div>

            </form>
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