<?php
// admin_view_proposal.php - FINAL (Delete + Edit working for Notes & Comments)
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'technician' && $_SESSION['role'] !== 'admin')) {
    header("Location: technician_login.php"); exit;
}
require_once 'database/database.php';

$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM proposals WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: admin_proposal.php"); exit;
}

// Permission check
$canView = false;
if ($row['visibility'] === 'Public') $canView = true;
elseif ($row['visibility'] === 'Internal') $canView = ($row['technician_id'] == $user_id);
elseif ($row['visibility'] === 'Private') $canView = ($row['technician_id'] == $user_id || $row['to_email'] === ($_SESSION['email'] ?? ''));

if (!$canView) {
    header("Location: admin_proposal.php"); exit;
}

$tab = $_GET['tab'] ?? 'proposal';

// ====================== HANDLE DELETE & UPDATE ======================
if (isset($_GET['delete_comment'])) {
    $del_id = (int)$_GET['delete_comment'];
    $stmt = $conn->prepare("DELETE FROM proposal_comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_view_proposal.php?id=$id&tab=comments");
    exit;
}

if (isset($_GET['delete_note'])) {
    $del_id = (int)$_GET['delete_note'];
    $stmt = $conn->prepare("DELETE FROM proposal_notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_view_proposal.php?id=$id&tab=notes");
    exit;
}

// Update Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment']) && isset($_POST['edit_comment_id'])) {
    $comment_id = (int)$_POST['edit_comment_id'];
    $new_comment = trim($_POST['comment'] ?? '');
    if ($new_comment && $comment_id > 0) {
        $stmt = $conn->prepare("UPDATE proposal_comments SET comment = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_comment, $comment_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_view_proposal.php?id=$id&tab=comments");
    exit;
}

// Update Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note']) && isset($_POST['edit_note_id'])) {
    $note_id = (int)$_POST['edit_note_id'];
    $new_note = trim($_POST['note'] ?? '');
    if ($new_note && $note_id > 0) {
        $stmt = $conn->prepare("UPDATE proposal_notes SET note = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_note, $note_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_view_proposal.php?id=$id&tab=notes");
    exit;
}

// Add New Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && $row['allow_comments']) {
    $comment = trim($_POST['comment'] ?? '');
    if ($comment) {
        $stmt = $conn->prepare("INSERT INTO proposal_comments (proposal_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_view_proposal.php?id=$id&tab=comments");
    exit;
}

// Add New Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note']) && $tab === 'notes') {
    $note = trim($_POST['note'] ?? '');
    if ($note) {
        $stmt = $conn->prepare("INSERT INTO proposal_notes (proposal_id, user_id, note) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id, $user_id, $note);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_view_proposal.php?id=$id&tab=notes");
    exit;
}

// Load current user's notes
$notes = [];
$stmt = $conn->prepare("SELECT n.id, n.note, n.created_at FROM proposal_notes n 
                        WHERE n.proposal_id = ? AND n.user_id = ? 
                        ORDER BY n.created_at DESC");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Load comments
$comments = [];
if ($row['allow_comments']) {
    $stmt = $conn->prepare("SELECT c.id, c.comment, c.created_at, c.user_id, u.full_name, u.role 
                            FROM proposal_comments c 
                            LEFT JOIN users u ON c.user_id = u.id 
                            WHERE c.proposal_id = ? ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • View Proposal #<?= $id ?></title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .dropdown-container { position: relative; display: inline-block; }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 4px;
            z-index: 99999;
            width: 160px;
            background: #2c3034;
            border: 1px solid #3e4147;
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3);
            padding: 4px 0;
        }
        .dropdown-container:hover .dropdown-menu { display: block; }
        .dropdown-menu a, .dropdown-menu button {
            color: #e2e8f0;
        }
        .dropdown-menu a:hover, .dropdown-menu button:hover {
            background: #3e4147 !important;
        }
        /* .proposal-content {
            background: #2c3034;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        } */
        /* .tab-content {
            background: #2c3034;
            border-radius: 16px;
            padding: 2rem;
        } */
        /* .note-item, .comment-item {
            background: #3e4147;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        } */
        .nav-tabs .nav-link.active {
            background: #14b8a6;
            border-color: #14b8a6;
            color: white;
        }
        .nav-tabs .nav-link {
            color: #94a3b8;
            border-color: #3e4147;
        }
        .nav-tabs .nav-link:hover {
            color: #e2e8f0;
            border-color: #475569;
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
            
        </div>

        <div class="main-body">
            <div class="container-fluid">
                <!-- Proposal Tabs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs" id="proposalTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'proposal' ? 'active' : '' ?>" 
                                           href="?id=<?= $id ?>&tab=proposal" role="tab">
                                            <i class="fas fa-file-alt me-1"></i>Proposal
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'notes' ? 'active' : '' ?>" 
                                           href="?id=<?= $id ?>&tab=notes" role="tab">
                                            <i class="fas fa-sticky-note me-1"></i>Notes
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab === 'comments' ? 'active' : '' ?>" 
                                           href="?id=<?= $id ?>&tab=comments" role="tab">
                                            <i class="fas fa-comments me-1"></i>Comments
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-body tab-content">
                                <?php if ($tab === 'proposal'): ?>
                                    <!-- Proposal content -->
                                    <div class="proposal-content">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <h5 class="mb-2"><i class="fas fa-user me-2"></i>From:</h5>
                                                <h6 class="fw-semibold"><?= htmlspecialchars($row['lead'] ?? '—') ?></h6>
                                                <p class="text-muted mb-0">RiceGuard AI Team</p>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <h5 class="mb-2"><i class="fas fa-envelope me-2"></i>To:</h5>
                                                <h6 class="fw-semibold"><?= htmlspecialchars($row['to_email'] ?? $row['lead'] ?? '—') ?></h6>
                                                <p class="text-muted mb-0"><?= htmlspecialchars($row['to_email'] ?? '') ?></p>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <h5 class="mb-3"><i class="fas fa-file-contract me-2"></i>Proposal Content</h5>
                                            <div class="border border-secondary p-4 rounded-3">
                                                <?= nl2br(htmlspecialchars($row['content'] ?? 'No content')) ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($comments)): ?>
                                        <div class="mt-5">
                                            <h5 class="mb-4"><i class="fas fa-comments me-2"></i>Recent Comments</h5>
                                            <?php foreach ($comments as $c): ?>
                                            <div class="comment-item">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <span class="fw-semibold"><?= $c['role'] === 'admin' ? 'Admin' : htmlspecialchars($c['full_name'] ?? 'Unknown') ?></span>
                                                        <small class="text-muted ms-2"><?= date('M d, Y h:i A', strtotime($c['created_at'])) ?></small>
                                                    </div>
                                                    <?php if ($c['user_id'] == $user_id): ?>
                                                    <div class="dropdown-container">
                                                        <button class="btn btn-sm btn-link text-muted p-0">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a href="?id=<?= $id ?>&tab=comments&edit_comment=<?= $c['id'] ?>&edit_comment_text=<?= urlencode($c['comment']) ?>" 
                                                               class="dropdown-item">
                                                                <i class="fas fa-edit me-2"></i>Edit
                                                            </a>
                                                            <a href="#" onclick="deleteComment(<?= $c['id'] ?>)" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($tab === 'notes'): ?>
                                    <!-- Notes content -->
                                    <div class="tab-content">
                                        <h5 class="mb-4"><i class="fas fa-sticky-note me-2"></i>Your Private Notes</h5>

                                        <!-- Note input box (pre-filled when editing) -->
                                        <form method="POST" class="mb-5">
                                            <div class="mb-3">
                                                <textarea name="note" rows="4" class="form-control  text-light border-dark" 
                                                          placeholder="Write your private note here..."><?= htmlspecialchars($_GET['edit_note_text'] ?? '') ?></textarea>
                                            </div>
                                            <?php if (isset($_GET['edit_note'])): ?>
                                                <input type="hidden" name="update_note" value="1">
                                                <input type="hidden" name="edit_note_id" value="<?= (int)$_GET['edit_note'] ?>">
                                                <div class="d-flex gap-3">
                                                    <a href="?id=<?= $id ?>&tab=notes" class="btn btn-outline-secondary flex-fill">Cancel</a>
                                                    <button type="submit" class="btn btn-success flex-fill">Update Note</button>
                                                </div>
                                            <?php else: ?>
                                                <button type="submit" name="add_note" class="btn btn-success px-4">
                                                    <i class="fas fa-plus me-2"></i>Add Note
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Display current user's notes -->
                                        <?php if (!empty($notes)): ?>
                                            <div class="row">
                                                <?php foreach ($notes as $n): ?>
                                                <div class="col-12 mb-4">
                                                    <div class="note-item">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></small>
                                                            <div class="dropdown-container">
                                                                <button class="btn btn-sm btn-link text-muted p-0">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <div class="dropdown-menu">
                                                                    <a href="?id=<?= $id ?>&tab=notes&edit_note=<?= $n['id'] ?>&edit_note_text=<?= urlencode($n['note']) ?>" 
                                                                       class="dropdown-item">
                                                                        <i class="fas fa-edit me-2"></i>Edit
                                                                    </a>
                                                                    <a href="#" onclick="deleteNote(<?= $n['id'] ?>)" class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash me-2"></i>Delete
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($n['note'])) ?></p>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-sticky-note fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No notes yet. Add your first note above!</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($tab === 'comments'): ?>
                                    <!-- Comments content -->
                                    <div class="tab-content">
                                        <h5 class="mb-4"><i class="fas fa-comments me-2"></i>Comments</h5>

                                        <!-- Comment input box (pre-filled when editing) -->
                                        <?php if ($row['allow_comments']): ?>
                                        <form method="POST" class="mb-5">
                                            <div class="mb-3">
                                                <textarea name="comment" rows="4" class="form-control  text-light border-dark" 
                                                          placeholder="Write your comment here..."><?= htmlspecialchars($_GET['edit_comment_text'] ?? '') ?></textarea>
                                            </div>
                                            <?php if (isset($_GET['edit_comment'])): ?>
                                                <input type="hidden" name="update_comment" value="1">
                                                <input type="hidden" name="edit_comment_id" value="<?= (int)$_GET['edit_comment'] ?>">
                                                <div class="d-flex gap-3">
                                                    <a href="?id=<?= $id ?>&tab=comments" class="btn btn-outline-secondary flex-fill">Cancel</a>
                                                    <button type="submit" class="btn btn-success flex-fill">Update Comment</button>
                                                </div>
                                            <?php else: ?>
                                                <button type="submit" name="add_comment" class="btn btn-success px-4">
                                                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Comments are disabled for this proposal.
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($comments)): ?>
                                            <div class="row">
                                                <?php foreach ($comments as $c): ?>
                                                <div class="col-12 mb-4">
                                                    <div class="comment-item">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <span class="fw-semibold"><?= $c['role'] === 'admin' ? 'Admin' : htmlspecialchars($c['full_name'] ?? 'Unknown') ?></span>
                                                                <small class="text-muted ms-2"><?= date('M d, Y h:i A', strtotime($c['created_at'])) ?></small>
                                                            </div>
                                                            <?php if ($c['user_id'] == $user_id): ?>
                                                            <div class="dropdown-container">
                                                                <button class="btn btn-sm btn-link text-muted p-0">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <div class="dropdown-menu">
                                                                    <a href="?id=<?= $id ?>&tab=comments&edit_comment=<?= $c['id'] ?>&edit_comment_text=<?= urlencode($c['comment']) ?>" 
                                                                       class="dropdown-item">
                                                                        <i class="fas fa-edit me-2"></i>Edit
                                                                    </a>
                                                                    <a href="#" onclick="deleteComment(<?= $c['id'] ?>)" class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash me-2"></i>Delete
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No comments yet. Be the first to comment!</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
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
    function deleteNote(id) {
        if (confirm('Delete this note?')) {
            window.location.href = '?id=<?= $id ?>&tab=notes&delete_note=' + id;
        }
    }

    function deleteComment(id) {
        if (confirm('Delete this comment?')) {
            window.location.href = '?id=<?= $id ?>&tab=comments&delete_comment=' + id;
        }
    }

    // Tab switching functionality
    document.querySelectorAll('#proposalTabs .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            window.location.href = href;
        });
    });
    </script>
</body>
</html>