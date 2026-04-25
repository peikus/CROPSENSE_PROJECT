<?php
// technician_announcement.php - FULLY FIXED & CLEANED
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: login_signup.php");
    exit;
}

require_once __DIR__ . '/database/database.php';

// Create table if not exists (with role column)
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

// Fetch only relevant announcements for technicians
$stmt = $conn->prepare("
    SELECT a.*, u.full_name as sender_name 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    WHERE a.role IN ('global', 'technician')
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
            background: linear-gradient(135deg, #1e1b4b 0%, #2d1b69 100%); 
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3); 
        }
        .announcement-normal { 
            border-left: 4px solid #10b981; 
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%); 
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

    <!-- Navigation -->
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="technician_dashboard.php" class="b-brand">
                    <img src="assets/images/logo-full.png" alt="" class="logo logo-lg" />
                    <img src="assets/images/logo-abbr.png" alt="" class="logo logo-sm" />
                </a>
            </div>
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
                    <li class="nxl-item active">
                        <a href="technician_announcement.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-volume-2"></i></span>
                            <span class="nxl-mtext">Announcements</span>
                        </a>
                    </li>
                </ul>              
            </div>
        </div>
    </nav>

    <!-- Header -->
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
    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h5 class="m-b-10">
                        <i class="fas fa-bullhorn me-2 text-warning"></i>
                        Announcements (<?= count($announcements) ?>)
                    </h5>
                    <p class="text-muted">Important updates and alerts for technicians</p>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <div class="row g-4">
                        <?php if (empty($announcements)): ?>
                            <div class="col-12">
                                <div class="card text-center p-5">
                                    <i class="fas fa-bullhorn fa-4x text-warning mb-4"></i>
                                    <h4>No Announcements Yet</h4>
                                    <p class="text-muted">Important messages from admin will appear here.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $ann): ?>
                            <div class="col-xl-6 col-lg-8 col-md-12">
                                <div class="card announcement-card h-100 <?= $ann['urgent'] ? 'announcement-urgent' : 'announcement-normal' ?>">
                                    <div class="card-body p-4">
                                        <?php if ($ann['urgent']): ?>
                                            <span class="badge bg-danger fs-6 px-3 py-2 mb-3">
                                                <i class="fas fa-exclamation-triangle"></i> URGENT
                                            </span>
                                        <?php endif; ?>
                                        
                                        <h5 class="card-title"><?= htmlspecialchars($ann['title']) ?></h5>
                                        <small class="text-muted d-block mb-3">
                                            <?= date('M j, Y • g:i A', strtotime($ann['created_at'])) ?>
                                        </small>
                                        
                                        <div class="mb-3"><?= nl2br(htmlspecialchars($ann['message'])) ?></div>
                                        
                                        <?php if (!empty($ann['sender_name'])): ?>
                                        <div class="mt-3 pt-3 border-top">
                                            <small class="text-muted">From: <strong><?= htmlspecialchars($ann['sender_name']) ?></strong></small>
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
    </main>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
        // Auto refresh announcements every 60 seconds (optional)
        // setTimeout(() => location.reload(), 60000);
    </script>
</body>
</html>