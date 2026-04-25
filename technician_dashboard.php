<?php
// technician_dashboard.php - FINAL FIXED VERSION (Dropdowns Fully Visible)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'technician') {
    header("Location: technician_login.php");
    exit;
}

require_once 'database/database.php';

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Technician';

$msg = '';

// ====================== PROFILE & PASSWORD ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $full_name_new = trim($_POST['full_name'] ?? $full_name);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $upload_dir = 'uploads/profile/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $new_name = 'tech_' . $user_id . '_' . time() . '.' . $ext;
                $target = $upload_dir . $new_name;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) $photo_path = $target;
            }
        }

        if (empty($photo_path)) {
            $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $photo_path = $row['photo'] ?? 'assets/images/avatar/1.png';
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=?, photo=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name_new, $phone, $address, $photo_path, $user_id);
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name_new;
            $msg = '<div class="alert alert-success alert-dismissible fade show">✅ Profile updated!</div>';
        }
        $stmt->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current, $row['password'] ?? '')) {
            $msg = '<div class="alert alert-danger">Current password incorrect.</div>';
        } elseif ($new_pass !== $confirm) {
            $msg = '<div class="alert alert-danger">Passwords do not match.</div>';
        } elseif (strlen($new_pass) < 12) {
            $msg = '<div class="alert alert-danger">Password must be at least 12 characters.</div>';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            if ($stmt->execute()) $msg = '<div class="alert alert-success">Password changed successfully!</div>';
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare("SELECT full_name, email, phone, address, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();

$avatar = !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'assets/images/avatar/1.png';

// Stats
$totalFarmers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetch_row()[0] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM proposals WHERE technician_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalProposals = $stmt->get_result()->fetch_row()[0] ?? 0;
$stmt->close();

$totalDetections = $conn->query("SELECT COUNT(*) FROM user_detections")->fetch_row()[0] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM proposals WHERE technician_id = ? AND status IN ('Draft','Open','Revise')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendingProposals = $stmt->get_result()->fetch_row()[0] ?? 0;
$stmt->close();

// Notifications
$notifications = [];
$stmt = $conn->prepare("SELECT id, subject, created_at FROM proposals WHERE technician_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$props = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($props as $p) {
    $notifications[] = ['type'=>'proposal', 'message'=>"New Proposal: ".htmlspecialchars($p['subject']), 'time'=>$p['created_at'], 'link'=>"technician_view_proposal.php?id=".$p['id']];
}
$stmt->close();

$stmt = $conn->prepare("SELECT id, title, created_at FROM announcements WHERE role IN ('global','technician') ORDER BY created_at DESC LIMIT 4");
$stmt->execute();
$anns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($anns as $a) {
    $notifications[] = ['type'=>'announcement', 'message'=>"Announcement: ".htmlspecialchars($a['title']), 'time'=>$a['created_at'], 'link'=>"technician_announcement.php"];
}
$stmt->close();

$unreadCount = count($notifications);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Technician Dashboard</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="assets/js/bootstrap.bundle.min.js"></script>

<style>
    body { 
        overflow-x: hidden; 
    }

    /* ✅ FIX: Allow dropdowns to escape containers */
    .nxl-header,
    .header-wrapper,
    .nxl-container,
    .nxl-content {
        overflow: visible !important;
    }

    /* ✅ FIXED DROPDOWN (no forced top/right anymore) */
    .dropdown-menu {
        border: none !important;
        border-radius: 16px !important;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5) !important;
        backdrop-filter: blur(20px) !important;
        background: rgba(30, 41, 59, 0.98) !important;
        z-index: 99999 !important; /* stay above everything */
    }

    /* Search Dropdown */
    .dropdown-menu:has(#globalSearch) {
        width: 320px !important;
        padding: 12px !important;
    }

    /* Notification Dropdown */
    .notification-dropdown {
        width: 420px !important;
        max-height: 520px !important;
        overflow-y: auto !important;
    }

    /* Profile Dropdown */
    .profile-dropdown {
        width: 300px !important;
    }

    .notification-item {
        transition: background 0.2s ease;
    }
    .notification-item:hover {
        background: rgba(255,255,255,0.08) !important;
    }

    .prodigy-card {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        transition: all 0.4s ease;
    }
    .prodigy-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px -12px rgba(16,185,129,0.3);
    }

    .floating-panel {
        position: fixed;
        top: 0;
        right: -520px;
        width: 520px;
        height: 100vh;
        background: rgba(30,41,59,0.98);
        backdrop-filter: blur(30px);
        transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1200;
        overflow-y: auto;
        padding: 32px;
    }
    .floating-panel.show { 
        right: 0; 
        box-shadow: -30px 0 80px rgba(0,0,0,0.6);
    }

    /* ✅ MOBILE FIX */
    @media (max-width: 768px) {
        .dropdown-menu,
        .notification-dropdown,
        .profile-dropdown {
            width: calc(100vw - 40px) !important;
            right: 20px !important;
            left: auto !important;
        }
    }
</style>
</head>
<body data-bs-theme="dark">

        <!-- Navigation (unchanged) -->
        <nav class="nxl-navigation">
            <div class="navbar-wrapper">
                
                <div class="navbar-content">
                    <ul class="nxl-navbar">
                        <li class="nxl-item nxl-caption"><label>Navigation</label></li>
                     
                        <li class="nxl-item active">
                            <a href="technician_dashboard.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-airplay"></i></span>
                                <span class="nxl-mtext">Dashboard</span>
                            </a>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Proposal</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="technician_proposal.php">All Proposals</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="technician_create_proposal.php">Create Proposal</a></li>
                            </ul>
                        </li>
                    
                        <li class="nxl-item">
                            <a href="technician_history_access.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-clock"></i></span>
                                <span class="nxl-mtext">Farmers Record</span>
                            </a>
                        </li>

                        <li class="nxl-item">
                            <a href="technician_live_com.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-message-square"></i></span>
                                <span class="nxl-mtext">Messenger</span>
                            </a>
                        </li>

                        <li class="nxl-item">
                            <a href="technician_announcement.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-volume-2"></i></span>
                                <span class="nxl-mtext">Announcements</span>
                            </a>
                        </li>
                    </ul>              
                </div>
            </div>
        </nav>





<!-- Header - FIXED Notifications & Profile (Using your improved version) -->
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
                <div class="dropdown nxl-h-item">
                <a href="javascript:void(0);" class="nxl-head-link" data-bs-toggle="dropdown" data-bs-display="static">
                            <i class="feather-search"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-3" style="width:320px;">
                        <input type="text" id="globalSearch" class="form-control" placeholder="🔍 Search proposals, users..." 
                            onkeypress="if(event.key==='Enter') performSearch(this.value)">
                    </div>
                </div>
                   <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                            <i class="feather-moon"></i>
                        </a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                            <i class="feather-sun"></i>
                        </a>
                    </div>

                <!-- Notifications -->
                <div class="dropdown nxl-h-item">
                        <a href="javascript:void(0);" class="nxl-head-link" data-bs-toggle="dropdown" data-bs-display="static">
                            <i class="feather-bell fs-5"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle p-1 border border-light rounded-circle" style="font-size: 0.7rem; min-width: 20px; height: 20px;">
                                <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-3" style="width:320px;">
                        <div class="p-4 border-bottom" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-bell me-2"></i>Notifications</h6>
                                <span class="badge bg-light text-dark"><?= $unreadCount ?> New</span>
                            </div>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="feather-bell-off fs-1 mb-3 opacity-50"></i>
                                <p class="mb-0">No new notifications</p>
                            </div>
                        <?php else: foreach ($notifications as $notif): ?>
                            <a href="<?= htmlspecialchars($notif['link']) ?>" class="dropdown-item notification-item d-flex gap-3 text-decoration-none">
                                <div class="notification-icon p-2 <?= $notif['type'] === 'proposal' ? 'text-primary' : 'text-warning' ?>">
                                    <i class="feather-<?= $notif['type'] === 'proposal' ? 'file-text' : 'message-square' ?> fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($notif['message']) ?></h6>
                                    <small class="text-muted"><?= date('M j, g:i A', strtotime($notif['time'])) ?></small>
                                </div>
                                <i class="feather-chevron-right text-muted mt-1"></i>
                            </a>
                        <?php endforeach; endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="technician_proposal.php" class="dropdown-item text-center py-3 fw-semibold text-primary">
                            <i class="feather-list me-2"></i>View All Activity
                        </a>
                    </div>
                </div>

                <!-- Profile -->
                <div class="dropdown nxl-h-item">
                        <a href="javascript:void(0);" class="nxl-head-link" data-bs-toggle="dropdown" data-bs-display="static">
                            <img src="<?= $avatar ?>" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-3" style="width:320px;">
                        <div class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= $avatar ?>" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($full_name) ?></h6>
                                    <small class="text-muted">Technician</small>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" onclick="showProfilePanel(0)"><i class="feather-user me-2"></i>Profile Details</a>
                        <a class="dropdown-item" onclick="showProfilePanel(1)"><i class="feather-settings me-2"></i>Account Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="logout.php"><i class="feather-log-out me-2"></i>Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <h5>Welcome back, <?= htmlspecialchars($full_name) ?>!</h5>
        </div>
        <?= $msg ?>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-lg-6"><div class="prodigy-card p-4">Farmers Supported<br><h3><?= number_format($totalFarmers) ?></h3></div></div>
            <div class="col-xl-3 col-lg-6"><div class="prodigy-card p-4">My Proposals<br><h3><?= number_format($totalProposals) ?></h3></div></div>
            <div class="col-xl-3 col-lg-6"><div class="prodigy-card p-4">Detections<br><h3><?= number_format($totalDetections) ?></h3></div></div>
            <div class="col-xl-3 col-lg-6"><div class="prodigy-card p-4">Pending<br><h3><?= number_format($pendingProposals) ?></h3></div></div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3">
            <div class="col-md-3"><a href="technician_create_proposal.php" class="btn btn-success w-100 py-4">+ New Proposal</a></div>
            <div class="col-md-3"><a href="technician_history_access.php" class="btn btn-info w-100 py-4">Farmers Records</a></div>
            <div class="col-md-3"><a href="technician_live_com.php" class="btn btn-primary w-100 py-4">Live Chat</a></div>
            <div class="col-md-3"><a href="technician_announcement.php" class="btn btn-warning w-100 py-4">Announcements</a></div>
        </div>
    </div>
</div>

<!-- Floating Profile Panel -->
<div id="floatingPanel" class="floating-panel">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 id="panelTitle">My Profile</h4>
        <button onclick="hidePanel()" class="btn btn-close"></button>
    </div>
    <?= $msg ?>

    <ul class="nav nav-tabs mb-4" id="profileTabs">
        <li class="nav-item"><a class="nav-link active" onclick="switchTab(0)">Profile</a></li>
        <li class="nav-item"><a class="nav-link" onclick="switchTab(1)">Account Settings</a></li>
    </ul>

<div id="tab-profile">  
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">

        <div class="text-center mb-4 position-relative d-inline-block">
            <img 
                id="profile-pic" 
                src="<?= $avatar ?>" 
                class="rounded-circle border border-5 border-success" 
                style="width:160px;height:160px;object-fit:cover;"
            >

            <!-- Camera Icon Right Bottom with same style -->
            <label 
                for="photo-upload"
                class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow-sm"
                style="cursor:pointer;"
            >
                <i class="fa-solid fa-camera-retro fa-2x text-primary mb-0"></i>
            </label>

            <input 
                type="file" 
                id="photo-upload" 
                name="photo" 
                accept="image/*" 
                class="d-none" 
                onchange="previewPhoto(this)"
            >
        </div>

        <div class="mb-3">
            <label>Full Name</label>
            <input 
                type="text" 
                name="full_name" 
                class="form-control" 
                value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
                required
            >
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input 
                type="text" 
                name="phone" 
                class="form-control" 
                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
            >
        </div>

        <div class="mb-3">
            <label>Address</label>
            <textarea 
                name="address" 
                class="form-control" 
                rows="3"
            ><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-success w-100 py-3">
            Save Changes
        </button>
    </form>
</div>

    <div id="tab-settings" style="display:none">
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control" required></div>
            <div class="mb-3"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="btn btn-success w-100 py-3">Change Password</button>
        </form>
    </div>
</div>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>

<script>
function showProfilePanel(tab = 0) {
    document.getElementById('floatingPanel').classList.add('show');
    switchTab(tab);
}
function hidePanel() {
    document.getElementById('floatingPanel').classList.remove('show');
}
function switchTab(tab) {
    document.querySelectorAll('#profileTabs .nav-link').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-profile').style.display = tab === 0 ? 'block' : 'none';
    document.getElementById('tab-settings').style.display = tab === 1 ? 'block' : 'none';
    document.querySelectorAll('#profileTabs .nav-link')[tab].classList.add('active');
}
function previewPhoto(input) {
    if (input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('profile-pic').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
function performSearch(query) {
    if (query.trim()) {
        window.location.href = `technician_proposal.php?search=${encodeURIComponent(query)}`;
    }
}
</script>
</body>
</html>