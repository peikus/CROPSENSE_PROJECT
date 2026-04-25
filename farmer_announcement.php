<?php
// farmer_announcement.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_signup.php");
    exit;
}

require_once 'database/database.php';

// Create table with role column if needed
$conn->query("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target VARCHAR(50) DEFAULT 'all',
    role VARCHAR(20) DEFAULT 'global',
    urgent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    is_read BOOLEAN DEFAULT FALSE
)");

// Fetch only relevant announcements for farmers
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as sender_name 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    WHERE a.role IN ('global', 'farmer')
    ORDER BY a.created_at DESC, a.urgent DESC
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
    <title>RiceGuard AI • Announcements (<?= count($announcements) ?>)</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .announcement-urgent {
            border-left: 5px solid #ef4444 !important;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);
        }
        .announcement-normal {
            border-left: 4px solid #10b981;
        }
        .announcement-card {
            border-radius: 16px;
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
        }
        .announcement-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .unread-badge {
            background: #ef4444;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
        }
        .announcement-count {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body data-bs-theme="dark">

    <!-- Navigation Menu (unchanged) -->
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="farmer_dashboard.php" class="b-brand">
                    <img src="assets/images/logo-full.png" alt="" class="logo logo-lg" />
                    <img src="assets/images/logo-abbr.png" alt="" class="logo logo-sm" />
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item nxl-caption"><label>Navigation</label></li>
                    <li class="nxl-item"><a href="farmer_dashboard.php" class="nxl-link active"><span class="nxl-micon"><i class="feather-airplay"></i></span><span class="nxl-mtext">Dashboard</span></a></li>
                    <li class="nxl-item"><a href="farmer_index.php" class="nxl-link"><span class="nxl-micon"><i class="feather-upload"></i></span><span class="nxl-mtext">Upload</span></a></li>
                    <li class="nxl-item"><a href="farmer_camera.php" class="nxl-link"><span class="nxl-micon"><i class="feather-camera"></i></span><span class="nxl-mtext">Live Camera</span></a></li>
                    <li class="nxl-item"><a href="farmer_live_com.php" class="nxl-link"><span class="nxl-micon"><i class="feather-message-square"></i></span><span class="nxl-mtext">Messenger</span></a></li>
                    <li class="nxl-item"><a href="farmer_yield_planner.php" class="nxl-link"><span class="nxl-micon"><i class="feather-calendar"></i></span><span class="nxl-mtext">Rice Planner</span></a></li>
                    <li class="nxl-item"><a href="farmer_history.php" class="nxl-link"><span class="nxl-micon"><i class="feather-clock"></i></span><span class="nxl-mtext">History</span></a></li>
                    <li class="nxl-item"><a href="farmer_announcement.php" class="nxl-link"><span class="nxl-micon"><i class="feather-volume-2"></i></span><span class="nxl-mtext">Announcements</span></a></li>
                    <li class="nxl-item"><a href="weatherapi.php" class="nxl-link"><span class="nxl-micon"><i class="feather-cloud"></i></span><span class="nxl-mtext">Weather</span></a></li>
                </ul>              
            </div>
        </div>
    </nav>

    <!-- Header with Search + Notifications -->
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
                <div class="d-flex align-items-center">
                  
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
    <div class="container">
        <div class="page-main-header">
            <div class="page-main-header-inner">
                <h4 class="m-0 fw-bold page-title">
                    <i class="fas fa-bullhorn me-2 text-warning"></i>
                    Announcements (<?= count($announcements) ?>)
                </h4>
                <ul class="breadcome">
                    <li><a href="farmer_dashboard.php">Dashboard</a></li>
                    <li class="active">Announcements</li>
                </ul>
            </div>
        </div>

        <div class="main-body">
            <div class="container-fluid">
                <div class="row g-4">
                    <?php if (empty($announcements)): ?>
                        <div class="col-12">
                            <div class="card text-center p-5 border-0 shadow-lg">
                                <div class="text-warning mb-4">
                                    <i class="fas fa-bullhorn fa-4x"></i>
                                </div>
                                <h4 class="text-muted mb-3">No Announcements Yet</h4>
                                <p class="text-muted mb-0">Important messages from your cooperative will appear here when posted.</p>
                                <div class="mt-4">
                                    <button class="btn btn-outline-light" onclick="location.reload()">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                        <div class="col-xl-6 col-lg-8 col-md-12">
                            <div class="card announcement-card h-100 <?= $ann['urgent'] ? 'announcement-urgent' : 'announcement-normal' ?>">
                                <div class="card-body p-4">
                                    <?php if ($ann['urgent']): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>URGENT
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-2 fw-bold fs-5 lh-sm">
                                            <?= htmlspecialchars($ann['title']) ?>
                                        </h5>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('M j, Y g:i A', strtotime($ann['created_at'])) ?>
                                        </small>
                                    </div>

                                    <div class="card-text mb-3 lh-lg" style="line-height: 1.7;">
                                        <?= nl2br(htmlspecialchars($ann['message'])) ?>
                                    </div>

                                    <?php if ($ann['target'] && $ann['target'] !== 'all'): ?>
                                    <span class="badge bg-info fs-6 px-3 py-2">
                                        <i class="fas fa-target me-1"></i>
                                        <?= ucwords(str_replace('_', ' ', $ann['target'])) ?>
                                    </span>
                                    <?php endif; ?>

                                    <?php if ($ann['sender_name']): ?>
                                    <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            From: <?= htmlspecialchars($ann['sender_name']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
    // Auto-refresh every 10 seconds
    setTimeout(() => location.reload(), 100000);
    
    // Notification sound for urgent announcements
    <?php if (!empty($announcements) && $announcements[0]['urgent']): ?>
    const urgentAudio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjV');
    urgentAudio.play().catch(() => {});
    <?php endif; ?>
    </script>
</body>
</html>