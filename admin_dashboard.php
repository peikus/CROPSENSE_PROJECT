    <?php
    // admin_dashboard.php - PRODIGY AI ANALYTICS DASHBOARD + ENHANCED PROFILE SYSTEM
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: admin_login.php");
        exit;
    }

    require_once 'database/database.php';

    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'] ?? 'Admin';
    $email = $_SESSION['email'] ?? '';

    $msg = '';

    // ====================== HANDLE PROFILE & PASSWORD UPDATES ======================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // ====================== UPDATE PROFILE ======================
        if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {

            $full_name_new = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            // Fallback name
            if ($full_name_new === '') {
                $full_name_new = $full_name;
            }

            $photo_path = '';

            // Upload Photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

                $upload_dir = 'uploads/profile/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

                // Allowed types (security)
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed)) {

                    $new_name = 'admin_' . $user_id . '_' . time() . '.' . $ext;
                    $target = $upload_dir . $new_name;

                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                        $photo_path = $target;
                    }
                }
            }

            // If no new photo, keep old one
            if (empty($photo_path)) {
                $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $photo_path = $row['photo'] ?? 'assets/images/avatar/1.png';
                $stmt->close();
            }

            // Update profile
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=?, photo=? WHERE id=?");
            $stmt->bind_param("ssssi", $full_name_new, $phone, $address, $photo_path, $user_id);

            if ($stmt->execute()) {
                $_SESSION['full_name'] = $full_name_new;

                $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
            } else {
                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>Failed to update profile.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';
            }

            $stmt->close();
        }

        // ====================== CHANGE PASSWORD ======================
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

                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>Current password is incorrect.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';

            } elseif ($new_pass !== $confirm) {

                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>Passwords do not match.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';

            } elseif (strlen($new_pass) < 8) {

                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>Password must be at least 8 characters.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>';

            } else {

                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);

                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-lock me-2"></i>Password changed successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>';
                } else {
                    $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>Failed to change password.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>';
                }

                $stmt->close();
            }
        }
    }
    // Load latest user data
    $stmt = $conn->prepare("SELECT full_name, email, phone, address, photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();

    // ====================== ENHANCED PRODIGY DASHBOARD STATS ======================
    $totalFarmers     = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetch_row()[0] ?? 0;
    $totalTechnicians = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'technician'")->fetch_row()[0] ?? 0;
    $totalProposals   = $conn->query("SELECT COUNT(*) FROM proposals")->fetch_row()[0] ?? 0;

    // NEW: Filtered total used in Recent Proposals (matches exactly what admin_proposal.php displays)
    $totalProposalsFiltered = $conn->query("SELECT COUNT(*) FROM proposals WHERE visibility != 'Internal'")->fetch_row()[0] ?? 0;

    $totalDetections  = $conn->query("SELECT COUNT(*) FROM user_detections")->fetch_row()[0] ?? 0;

    // Growth metrics (last 30 days vs previous)
    $growthFarmers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_row()[0] ?? 0;
    $growthProposals = $conn->query("SELECT COUNT(*) FROM proposals WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_row()[0] ?? 0;

    // Recent Proposals - NOW FILTERED TO MATCH admin_proposal.php (excludes Internal proposals)
    $stmt = $conn->prepare("SELECT p.id, p.subject, p.status, p.created_at, u.full_name 
                            FROM proposals p 
                            LEFT JOIN users u ON p.technician_id = u.id 
                            WHERE p.visibility != 'Internal'
                            ORDER BY p.created_at DESC LIMIT 5");
    $stmt->execute();
    $recentProposals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Detections Trend (Last 6 Months)
// ====================== DETECTIONS TREND - FIXED 6 MONTHS CHART ======================
$detectionData = [];

// 1. Get real detection counts
$monthQuery = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as ym,
        COUNT(*) as count 
    FROM user_detections 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
    GROUP BY ym 
    ORDER BY ym ASC
");

$countsMap = [];
while ($row = $monthQuery->fetch_assoc()) {
    $countsMap[$row['ym']] = (int)$row['count'];
}

// 2. Generate full 6 months (oldest → newest) with 0 for missing months
$now = new DateTime();
for ($i = 5; $i >= 0; $i--) {
    $date = clone $now;
    $date->modify("-{$i} months");
    
    $ym          = $date->format('Y-m');      // e.g. 2026-04
    $monthLabel  = $date->format('M Y');      // e.g. Apr 2026
    
    $detectionData[] = [
        'month' => $monthLabel,
        'count' => $countsMap[$ym] ?? 0
    ];
}

    // AI Insights - Top Diseases (Last 30 days)
    $topDiseasesQuery = "SELECT class_key, COUNT(*) as count 
                        FROM user_detections 
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY class_key 
                        ORDER BY count DESC LIMIT 5";
    $topDiseasesResult = $conn->query($topDiseasesQuery);
    $topDiseases = $topDiseasesResult ? $topDiseasesResult->fetch_all(MYSQLI_ASSOC) : [];

    // Notifications
    $notifications = [];

    // New Proposals
    $notifStmt = $conn->prepare("SELECT p.id, p.subject, p.created_at, 'proposal' as type 
                                FROM proposals p ORDER BY p.created_at DESC LIMIT 6");
    $notifStmt->execute();
    $notifs = $notifStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($notifs as $n) {
        $notifications[] = [
            'type' => 'proposal',
            'message' => "New Proposal: " . htmlspecialchars($n['subject']),
            'time' => $n['created_at'],
            'link' => "admin_view_proposal.php?id=" . $n['id']
        ];
    }

    // Recent Comments
    $commentStmt = $conn->prepare("SELECT c.comment, c.created_at, p.subject, p.id as proposal_id 
                                FROM proposal_comments c 
                                JOIN proposals p ON c.proposal_id = p.id 
                                ORDER BY c.created_at DESC LIMIT 5");
    $commentStmt->execute();
    $comments = $commentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($comments as $c) {
        $notifications[] = [
            'type' => 'comment',
            'message' => "Comment on '" . htmlspecialchars($c['subject']) . "': " . substr(htmlspecialchars($c['comment']), 0, 50) . "...",
            'time' => $c['created_at'],
            'link' => "admin_view_proposal.php?id=" . $c['proposal_id']
        ];
    }
    usort($notifications, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
    $unreadCount = count($notifications);

    // Avatar
    $avatar = !empty($user['photo']) ? htmlspecialchars($user['photo']) : 'assets/images/avatar/1.png';
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="x-ua-compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>RiceGuard AI • Prodigy Admin Dashboard</title>

        <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
        <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <style>

    body {
        overflow-x: hidden;
    }

            :root {
                --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
                --info-gradient: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            }

            /* FIXED Notification Dropdown - Proper Spacing */
            .notification-dropdown {
                top: 70px !important;
                right: 10px !important;
                width: 420px !important;
                max-height: 500px !important;
                overflow-y: auto;
                border: none;
                border-radius: 16px;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
                backdrop-filter: blur(20px);
                background: rgba(30, 41, 59, 0.98);
            }
            .notification-item {
                padding: 16px 20px !important;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                transition: all 0.3s ease;
                text-decoration: none;
            }
            .notification-item:hover {
                background: linear-gradient(90deg, rgba(16,185,129,0.15), transparent);
                transform: translateX(8px);
            }
            .notification-item:last-child { border-bottom: none; }

            /* FIXED Profile Dropdown - Proper Spacing */
            .profile-dropdown {
                top: 70px !important;
                right: 10px !important;
                width: 280px !important;
                border: none;
                border-radius: 16px;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
                backdrop-filter: blur(20px);
                background: rgba(30, 41, 59, 0.98);
            }

            /* Prodigy Glassmorphism Cards */
            .prodigy-card {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden;
                position: relative;
            }
            .prodigy-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: var(--primary-gradient);
                opacity: 0;
                transition: opacity 0.4s ease;
            }
            .prodigy-card:hover {
                transform: translateY(-12px) scale(1.02);
                box-shadow: 0 35px 80px -12px rgba(102, 126, 234, 0.3);
            }
            .prodigy-card:hover::before { opacity: 1; }

            /* Enhanced Stat Cards */
            .stat-metric {
                background: rgba(255, 255, 255, 0.08);
                border-radius: 16px;
                padding: 24px;
                position: relative;
                overflow: hidden;
            }
            .stat-metric::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: var(--success-gradient);
            }
            .stat-icon {
                width: 64px;
                height: 64px;
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                backdrop-filter: blur(10px);
            }

    .floating-panel {
        position: fixed;
        top: 0;
        right: -520px;
        width: 520px;
        height: 100vh;
        background: rgba(30, 41, 59, 0.98);
        backdrop-filter: blur(30px);
        transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1200;
        overflow-y: auto;
        padding: 32px;
        
        /* REMOVE shadow here */
        box-shadow: none;
    }

    .floating-panel.show {
        right: 0;
        box-shadow: -30px 0 80px rgba(0,0,0,0.6); /* ONLY when visible */
    }
            .profile-photo { 
                width: 180px; 
                height: 180px; 
                object-fit: cover; 
                border: 8px solid rgba(16,185,129,0.3);
                border-radius: 50%;
                box-shadow: 0 20px 40px rgba(16,185,129,0.3);
                transition: all 0.4s ease;
            }
            .profile-photo:hover { transform: scale(1.05); }

            /* Prodigy Page Header */
            .prodigy-header {
                background: var(--primary-gradient);
                border-radius: 24px;
                padding: 32px;
                position: relative;
                overflow: hidden;
            }
            .prodigy-header::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 100%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                animation: shimmer 6s infinite linear;
            }
            @keyframes shimmer {
                0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
                100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
            }

            /* AI Insight Cards */
            .ai-insight {
                background: rgba(102, 126, 234, 0.15);
                border: 1px solid rgba(102, 126, 234, 0.3);
                border-radius: 16px;
                padding: 24px;
            }

            /* Responsive improvements */
            @media (max-width: 768px) {
                .notification-dropdown { width: 100% !important; right: 0 !important; }
                .profile-dropdown { width: 100% !important; right: 0 !important; }
            }
            /* Professional ApexCharts Container */
/* ULTRA-PROFESSIONAL LINE CHART ENHANCEMENTS */
#detectionChart {
    background: linear-gradient(145deg, rgba(255,255,255,0.03), rgba(0,0,0,0.2)) !important;
    border-radius: 16px !important;
    padding: 24px !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important;
}

.apexcharts-tooltip {
    background: rgba(30,41,59,0.98) !important;
    border: 1px solid rgba(16,185,129,0.3) !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4) !important;
    backdrop-filter: blur(20px) !important;
}

.apexcharts-xaxis-invisible text,
.apexcharts-yaxis text {
    fill: #cbd5e1 !important;
    font-weight: 500 !important;
    font-size: 13px !important;
}

.apexcharts-gridline {
    stroke: rgba(255,255,255,0.08) !important;
    stroke-dasharray: 4 !important;
}        </style>
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
                        <div class="dropdown nxl-h-item">
                            <a href="javascript:void(0);" class="nxl-head-link" data-bs-toggle="dropdown">
                                <i class="feather-search"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-3" style="width:320px;">
                                <input type="text" id="globalSearch" class="form-control" placeholder="🔍 Search proposals, users..." 
                                    onkeypress="if(event.key==='Enter') performSearch(this.value)">
                            </div>
                        </div>

                        <!-- FIXED Notifications - Proper Top Positioning -->
                        <div class="dropdown nxl-h-item">
                            <a class="nxl-head-link position-relative" data-bs-toggle="dropdown">
                                <i class="feather-bell fs-5"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle p-1 border border-light rounded-circle" style="font-size: 0.7rem; min-width: 20px; height: 20px;">
                                        <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu notification-dropdown">
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
                                            <div class="notification-icon p-2 
                                                <?= $notif['type'] === 'proposal' ? 'text-primary' : 'text-warning' ?>">
                                                
                                                <i class="feather-<?= $notif['type'] === 'proposal' ? 'file-text' : 'message-square' ?> fs-4"></i>
                                            </div>                                    <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($notif['message']) ?></h6>
                                            <small class="text-muted"><?= date('M j, g:i A', strtotime($notif['time'])) ?></small>
                                        </div>
                                        <i class="feather-chevron-right text-muted mt-1"></i>
                                    </a>
                                <?php endforeach; endif; ?>
                                <div class="dropdown-divider"></div>
                                <a href="admin_proposal.php" class="dropdown-item text-center py-3 fw-semibold text-primary">
                                    <i class="feather-list me-2"></i>View All Activity
                                </a>
                            </div>
                        </div>



                         <!-- Theme Toggle -->
                        <div class="nxl-h-item dark-light-theme">
                            <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                                <i class="feather-moon"></i>
                            </a>
                            <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                                <i class="feather-sun"></i>
                            </a>
                        </div>

                        <!-- FIXED Profile - Proper Top Positioning -->
                        <div class="dropdown nxl-h-item">
                            <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                <img src="<?= $avatar ?>" alt="Admin" 
                                    class="img-fluid user-avtar me-0 rounded-circle border border-3 border-white shadow-sm" 
                                    style="width:48px;height:48px;object-fit:cover;">
                            </a>
                            <div class="dropdown-menu profile-dropdown">
                                <div class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= $avatar ?>" class="rounded-circle" style="width:50px;height:50px;object-fit:cover;" alt="Profile">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($user['full_name'] ?? $full_name) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($user['email'] ?? $email) ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item py-3" onclick="showProfilePanel(0)">
                                    <i class="feather-user me-3 text-primary"></i>Profile Details
                                </a>
                                <a class="dropdown-item py-3" onclick="showProfilePanel(1)">
                                    <i class="feather-settings me-3 text-info"></i>Account Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item py-3 text-danger fw-semibold" href="logout.php">
                                    <i class="feather-log-out me-3"></i>Sign Out
                                </a>
                            </div>
                        </div>

                       
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content - Professional Analytics Dashboard -->
        <main class="nxl-container">
            <div class="nxl-content">
                <div class="page-header">
                    <div class="page-header-title">
                        <h5 class="m-b-10">RiceGuard AI • Admin Analytics Dashboard</h5>
                        <p class="text-muted">Real-time overview • <?= date('F j, Y') ?></p>
                    </div>
                </div>

                <!-- COMPLETE MAIN CONTENT - PROFESSIONAL BOOTSTRAP DASHBOARD -->
                <div class="main-content">
                    <!-- KEY METRICS ROW -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-lg-6">
                            <div class="prodigy-card stat-metric">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Total Farmers</p>
                                        <h3 class="fw-bold mb-0 text-white"><?= number_format($totalFarmers) ?></h3>
                                        <small class="text-success fw-semibold">
                                            +<?= number_format($growthFarmers) ?> (30d)
                                        </small>
                                    </div>
                                    <div class="stat-icon bg-success text-white">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-success" style="width: <?= min(100, ($growthFarmers/max(1,$totalFarmers))*100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="prodigy-card stat-metric">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Active Proposals</p>
                                        <h3 class="fw-bold mb-0 text-white"><?= number_format($totalProposalsFiltered) ?></h3>
                                        <small class="text-info fw-semibold">
                                            +<?= number_format($totalProposalsFiltered) ?> (30d)
                                        </small>
                                    </div>
                                    <div class="stat-icon bg-info text-white">
                                        <i class="fas fa-file-contract"></i>
                                    </div>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-info" style="width: <?= min(100, ($growthProposals/max(1,$totalProposalsFiltered))*100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="prodigy-card stat-metric">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <p class="text-muted mb-1 small">AI Detections</p>
                                        <h3 class="fw-bold mb-0 text-white"><?= number_format($totalDetections) ?></h3>
                                        <small class="text-warning fw-semibold">
                                            <?php 
                                            $todayDetections = $conn->query("SELECT COUNT(*) FROM user_detections WHERE DATE(created_at) = CURDATE()")->fetch_row()[0] ?? 0;
                                            echo $todayDetections > 0 ? '+' . number_format($todayDetections) . ' today' : 'No detections today';
                                            ?>
                                        </small>
                                    </div>
                                    <div class="stat-icon bg-warning text-dark">
                                        <i class="fas fa-camera-retro"></i>
                                    </div>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-warning" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="prodigy-card stat-metric">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <p class="text-muted mb-1 small">Top Disease</p>
                                        <?php 
                                        $topDisease = $topDiseases[0] ?? ['class_key' => 'None', 'count' => 0];
                                        ?>
                                        <h5 class="fw-bold mb-0 text-white"><?= htmlspecialchars($topDisease['class_key']) ?></h5>
                                        <small class="text-danger fw-semibold"><?= number_format($topDisease['count']) ?> cases</small>
                                    </div>
                                    <div class="stat-icon bg-danger text-white">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-danger" style="width: 85%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CHARTS & RECENT ACTIVITY ROW -->
                    <div class="row g-4">
                        <!-- PROFESSIONAL LINE CHART - GUARANTEED TO WORK -->
                        <div class="col-xl-8">
                            <div class="card border-0 shadow-lg">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1 fw-bold text-white">Rice Disease & Pest Detections Trend</h5>
                                            <small class="text-success fw-semibold">
                                                <i class="fas fa-chart-line me-1"></i>Last 6 Months • Real-time Analytics
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-dark btn-sm " id="lineBtn">
                                                <i class="fas fa-chart-line me-1"></i>Line
                                            </button>
                                            <button type="button" class="btn btn-outline-dark btn-sm" id="areaBtn">
                                                <i class="fas fa-chart-area me-1"></i>Area
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div id="detectionChart" style="height: 420px; width: 100%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- RECENT PROPOSALS -->
                        <div class="col-xl-4">
                            <div class="card h-100 border-0 shadow-lg">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1 fw-bold">Recent Proposals</h5>
                                            <small class="text-muted">Latest <?= number_format($totalProposalsFiltered) ?> activities</small>
                                        </div>
                                        <a href="admin_proposal.php" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-list me-1"></i>View All
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recentProposals)): ?>
                                        <div class="p-5 text-center text-muted">
                                            <i class="fas fa-file-contract fa-3x mb-3 opacity-50"></i>
                                            <p class="mb-0">No proposals yet</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush list-group-hover">
                                            <?php foreach ($recentProposals as $p): ?>
                                            <a href="admin_view_proposal.php?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action border-0 py-3 px-4">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1 me-3">
                                                        <div class="fw-bold text-white"><?= htmlspecialchars($p['subject']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($p['full_name'] ?? 'System') ?></small>
                                                    </div>
                                                    <span class="badge bg-<?= strtolower($p['status'] ?? 'draft') === 'sent' ? 'success' : 'secondary' ?> rounded-pill">
                                                        <?= htmlspecialchars(ucfirst($p['status'] ?? 'Draft')) ?>
                                                    </span>
                                                </div>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QUICK ACTIONS -->
                    <div class="row g-4 mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-lg">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="card-title fw-bold mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="row g-3">
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <a href="admin_create_proposal.php" class="btn btn-success w-100 h-100 py-4 text-center text-white fw-bold shadow-lg border-0 rounded-3">
                                                <i class="feather-plus-square fa-2x mb-2 d-block"></i>New Proposal
                                            </a>
                                        </div>
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <a href="admin_announcement.php" class="btn btn-primary w-100 h-100 py-4 text-center text-white fw-bold shadow-lg border-0 rounded-3">
                                                <i class="feather-volume-2 fa-2x mb-2 d-block"></i>Send Announcement
                                            </a>
                                        </div>
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <a href="admin_farmerlog.php" class="btn btn-info w-100 h-100 py-4 text-center text-white fw-bold shadow-lg border-0 rounded-3">
                                                <i class="feather-users fa-2x mb-2 d-block"></i>Manage Farmers
                                            </a>
                                        </div>
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <a href="admin_all_user_history.php" class="btn btn-warning w-100 h-100 py-4 text-center text-dark fw-bold shadow-lg border-0 rounded-3">
                                                <i class="feather-clock fa-2x mb-2 d-block"></i>View History
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>








        <!-- Floating Profile Panel -->
        <div id="floatingPanel" class="floating-panel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 id="panelTitle">My Profile</h4>
                <button onclick="hidePanel()" class="btn btn-close btn-outline-light"></button>
            </div>

            <?= $msg ?>

            <ul class="nav nav-tabs mb-4" id="profileTabs">
                <li class="nav-item"><a class="nav-link active" onclick="switchTab(0)">Profile</a></li>
                <li class="nav-item"><a class="nav-link" onclick="switchTab(1)">Account Settings</a></li>
            </ul>

            <!-- Profile Tab -->
            <div id="tab-profile">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="text-center mb-4">
                        <img id="profile-pic" src="<?= $avatar ?>" class="profile-photo" alt="Profile Photo">
                        <label for="photo-upload" class="btn btn-success btn-sm mt-3">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <input type="file" id="photo-upload" name="photo" accept="image/*" class="d-none" onchange="previewPhoto(this)">
                    </div>

                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-3">Save Changes</button>
                </form>
            </div>

            <!-- Account Settings Tab -->
            <div id="tab-settings" style="display:none">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-3">Change Password</button>
                </form>
            </div>
        </div>

<script src="assets/vendors/js/vendors.min.js"></script>
<script src="assets/vendors/js/apexcharts.min.js"></script>
<script src="assets/js/common-init.min.js"></script>
<script src="assets/js/theme-customizer-init.min.js"></script>

<script>
let detectionChart = null;
let isLineMode = false; // Track current mode

// PROFESSIONAL APEXCHARTS - FIXED TOGGLE
window.addEventListener('load', function() {
    console.log('🟢 Initializing professional detections chart...');

    const months = <?= json_encode(array_column($detectionData, 'month')) ?>;
    const counts = <?= json_encode(array_column($detectionData, 'count')) ?>;

    console.log('📊 Final Chart Data:', { months, counts });

    const options = {
        series: [{
            name: "Detections",
            data: counts
        }],
        chart: {
            type: 'area',           // Default = Area
            height: 420,
            zoom: { enabled: false },
            toolbar: { show: true },
            background: 'transparent',
            fontFamily: 'system-ui, -apple-system, sans-serif'
        },
        stroke: {
            curve: 'smooth',
            width: 4,
            colors: ['#10b981']
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0.6,
                opacityFrom: 0.65,
                opacityTo: 0.15,
                stops: [0, 95]
            }
        },
        xaxis: {
            categories: months,
            labels: {
                style: {
                    colors: '#cbd5e1',
                    fontSize: '13px',
                    fontWeight: 500
                },
                rotate: -35
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: 0,
            labels: {
                style: {
                    colors: '#cbd5e1',
                    fontSize: '13px'
                },
                formatter: (val) => Math.round(val)
            }
        },
        grid: {
            borderColor: 'rgba(255,255,255,0.08)',
            strokeDashArray: 4
        },
        markers: {
            size: 5,
            colors: ['#10b981'],
            strokeColors: '#fff',
            strokeWidth: 3,
            hover: { size: 7 }
        },
        colors: ['#10b981'],
        tooltip: {
            theme: 'dark',
            x: { show: true },
            y: {
                formatter: (val) => `${val} detections`
            }
        },
        legend: {
            show: false
        }
    };

    // Destroy previous chart if exists
    if (detectionChart) {
        detectionChart.destroy();
    }

    detectionChart = new ApexCharts(document.querySelector("#detectionChart"), options);
    detectionChart.render();

    // Set initial button states
    document.getElementById('areaBtn').classList.add('active');
    document.getElementById('lineBtn').classList.remove('active');

    console.log('✅ Professional chart rendered successfully!');
});

// FIXED Toggle buttons - COMPLETE CHART RELOAD
document.getElementById('lineBtn')?.addEventListener('click', function() {
    if (detectionChart && !isLineMode) {
        console.log('🔄 Switching to LINE mode...');
        
        const months = <?= json_encode(array_column($detectionData, 'month')) ?>;
        const counts = <?= json_encode(array_column($detectionData, 'count')) ?>;

        const lineOptions = {
            ...detectionChart.options, // Preserve all other options
            chart: { type: 'line' },
            stroke: {
                curve: 'smooth',
                width: 5,  // THICKER line for visibility
                colors: ['#10b981']
            },
            fill: { type: 'solid', colors: ['transparent'] }, // NO FILL for line mode
            markers: {
                size: 6,
                colors: ['#10b981'],
                strokeColors: '#fff',
                strokeWidth: 4,
                hover: { size: 8 }
            }
        };

        detectionChart.updateOptions(lineOptions);
        isLineMode = true;
        
        // Update button states
        this.classList.add('active');
        document.getElementById('areaBtn').classList.remove('active');
        
        console.log('✅ Line mode activated');
    }
});

document.getElementById('areaBtn')?.addEventListener('click', function() {
    if (detectionChart && isLineMode) {
        console.log('🔄 Switching to AREA mode...');
        
        const months = <?= json_encode(array_column($detectionData, 'month')) ?>;
        const counts = <?= json_encode(array_column($detectionData, 'count')) ?>;

        const areaOptions = {
            ...detectionChart.options, // Preserve all other options
            chart: { type: 'area' },
            stroke: {
                curve: 'smooth',
                width: 4,
                colors: ['#10b981']
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.6,
                    opacityFrom: 0.65,
                    opacityTo: 0.15,
                    stops: [0, 95]
                }
            },
            markers: {
                size: 5,
                colors: ['#10b981'],
                strokeColors: '#fff',
                strokeWidth: 3,
                hover: { size: 7 }
            }
        };

        detectionChart.updateOptions(areaOptions);
        isLineMode = false;
        
        // Update button states
        this.classList.add('active');
        document.getElementById('lineBtn').classList.remove('active');
        
        console.log('✅ Area mode activated');
    }
});

// Profile panel functions (unchanged)
function showProfilePanel(tab = 0) { 
    document.getElementById('floatingPanel').classList.add('show'); 
    switchTab(tab); 
}
function hidePanel() { 
    document.getElementById('floatingPanel').classList.remove('show'); 
}
function switchTab(tab) {
    document.querySelectorAll('#profileTabs .nav-link').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('#tab-profile, #tab-settings').forEach(c => c.style.display = 'none');
    document.querySelector('#profileTabs .nav-link:nth-child(' + (tab+1) + ')').classList.add('active');
    document.getElementById(tab === 0 ? 'tab-profile' : 'tab-settings').style.display = 'block';
}
function previewPhoto(input) {
    if (input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('profile-pic').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
function performSearch(query) {
    if (query.trim()) window.location.href = `admin_proposal.php?search=${encodeURIComponent(query)}`;
}
</script>
</body>
</html>