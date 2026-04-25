<?php
// admin_announcement.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_signup.php");
    exit;
}

require_once 'database/database.php';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $target_role = $_POST['target_role'] ?? 'global';
    $user_id = $_SESSION['user_id'];

    if ($title && $message) {
        $urgent = (stripos($title, 'typhoon') !== false || 
                   stripos($title, 'urgent') !== false || 
                   stripos($title, 'warning') !== false);

        $stmt = $conn->prepare("INSERT INTO announcements (title, message, target, role, urgent, created_by) VALUES (?, ?, 'all', ?, ?, ?)");
        $stmt->bind_param("sssii", $title, $message, $target_role, $urgent, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_announcement.php?success=1");
        exit;
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $target_role = $_POST['target_role'] ?? 'global';

    if ($id && $title && $message) {
        $urgent = (stripos($title, 'typhoon') !== false || 
                   stripos($title, 'urgent') !== false || 
                   stripos($title, 'warning') !== false);

        $stmt = $conn->prepare("UPDATE announcements SET title=?, message=?, role=?, urgent=? WHERE id=?");
        $stmt->bind_param("sssii", $title, $message, $target_role, $urgent, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_announcement.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_announcement.php");
    exit;
}

// Fetch all announcements for admin view
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as sender_name 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
");
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Manage Announcements</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .announcement-card { border-radius: 16px; transition: all 0.3s; }
        .announcement-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .scroll-list { max-height: 500px; overflow-y: auto; }
        .dropdown-toggle::after { display: none; }
    </style>
</head>
<body data-bs-theme="dark">

<!-- Navigation and Header (same as your original) -->
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


<div class="nxl-container">
    <div class="page-main-header">
        <h4 class="m-0 fw-bold page-title"><i class="fas fa-bullhorn me-2 text-success"></i>Manage Announcements</h4>
    </div>

    <div class="main-body">
        <div class="container-fluid">
            <div class="row g-4">
                <!-- Create Form -->
                <div class="col-lg-5">
                    <div class="card p-4 announcement-card">
                        <h4 class="mb-4"><i class="fas fa-plus me-2"></i>Create New Announcement</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label class="form-label">Target Group</label>
                                <select name="target_role" class="form-select" required>
                                    <option value="global">🌍 Global Users (Farmers + Technicians)</option>
                                    <option value="farmer">🌾 All Farmers</option>
                                    <option value="technician">🔧 All Technicians</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Typhoon Warning" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" rows="5" class="form-control" placeholder="Write message here..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Announcement
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of Announcements (Scrollable) -->
                <div class="col-lg-7">
                    <div class="card announcement-card">
                        <div class="card-header d-flex justify-content-between">
                            <h5>All Announcements (<?= count($announcements) ?>)</h5>
                            <small class="text-muted">Newest first • Scroll for more</small>
                        </div>
                        <div class="card-body scroll-list p-3">
                            <?php if (empty($announcements)): ?>
                                <p class="text-muted text-center">No announcements yet.</p>
                            <?php else: ?>
                                <?php foreach ($announcements as $ann): ?>
                                <div class="card mb-3 announcement-card <?= $ann['urgent'] ? 'border-danger' : 'border-success' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($ann['title']) ?></h6>
                                                <small class="text-muted">
                                                    <?= date('M j, Y g:i A', strtotime($ann['created_at'])) ?> • 
                                                    Target: <strong><?= ucfirst($ann['role']) ?></strong>
                                                </small>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-light dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item edit-btn" href="#" 
                                                           data-id="<?= $ann['id'] ?>"
                                                           data-title="<?= htmlspecialchars($ann['title']) ?>"
                                                           data-message="<?= htmlspecialchars($ann['message']) ?>"
                                                           data-role="<?= $ann['role'] ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a></li>
                                                    <li><a class="dropdown-item text-danger" href="?delete=<?= $ann['id'] ?>" 
                                                           onclick="return confirm('Delete this announcement?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                                        <?php if ($ann['urgent']): ?>
                                            <span class="badge bg-danger mt-2">URGENT</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header">
                <h5 class="modal-title">Edit Announcement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label>Target Group</label>
                        <select name="target_role" id="edit_role" class="form-select">
                            <option value="global">Global Users</option>
                            <option value="farmer">All Farmers</option>
                            <option value="technician">All Technicians</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Message</label>
                        <textarea name="message" id="edit_message" rows="5" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_title').value = this.dataset.title;
        document.getElementById('edit_message').value = this.dataset.message;
        document.getElementById('edit_role').value = this.dataset.role;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>
</body>
</html>