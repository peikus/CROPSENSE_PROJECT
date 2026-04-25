<?php
// ========================================================
// FILE: farmer_dashboard.php
// FIXED: Unknown column 'is_read' error
// ENHANCED: Functional Search + Smart Notifications
// ========================================================

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: login_signup.php");
    exit;
}

require_once 'database/database.php';

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Farmer';
$email = $_SESSION['email'] ?? '';

$msg = '';

// ====================== HANDLE PROFILE & PASSWORD ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $full_name_new = trim($_POST['full_name'] ?? $full_name);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $farm_size = floatval($_POST['farm_size'] ?? 0);
        $rice_variety = trim($_POST['rice_variety'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $photo_path = '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $upload_dir = 'uploads/profile/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $new_name = $user_id . '_' . time() . '.' . $ext;
            $target = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $photo_path = $target;
            }
        }

        if (empty($photo_path)) {
            $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $photo_path = $row['photo'] ?? '';
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=?, farm_size=?, preferred_variety=?, bio=?, photo=? WHERE id=?");
        $stmt->bind_param("sssssssi", $full_name_new, $phone, $address, $farm_size, $rice_variety, $bio, $photo_path, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name_new;
            $msg = '<div class="alert alert-success">Profile updated successfully!</div>';
        } else {
            $msg = '<div class="alert alert-danger">Failed to update profile.</div>';
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

        if (password_verify($current, $row['password'])) {
            if ($new_pass === $confirm && strlen($new_pass) >= 12) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success">Password changed successfully!</div>';
                }
                $stmt->close();
            } else {
                $msg = '<div class="alert alert-danger">Passwords do not match or too weak.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">Current password is incorrect.</div>';
        }
    }
}

// Reload user data
$stmt = $conn->prepare("SELECT full_name, email, phone, address, farm_size, preferred_variety, bio, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ====================== DASHBOARD STATISTICS ======================
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM user_detections WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_detections = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT 
    COUNT(CASE WHEN class_key NOT LIKE '%healthy%' THEN 1 END) as affected,
    COUNT(CASE WHEN class_key LIKE '%healthy%' THEN 1 END) as healthy
    FROM user_detections WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$affected = $stats['affected'] ?? 0;
$healthy = $stats['healthy'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as plan_count FROM rice_plans WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$plan_count = $stmt->get_result()->fetch_assoc()['plan_count'] ?? 0;
$stmt->close();

// Recent detections
$stmt = $conn->prepare("SELECT class_key, confidence, created_at FROM user_detections 
                        WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ====================== SAFE NOTIFICATIONS ======================
// 1. New Messages (safe check - avoid error if is_read column doesn't exist yet)
$unread_messages = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages 
                            WHERE to_user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unread_messages = $stmt->get_result()->fetch_assoc()['unread'] ?? 0;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Column doesn't exist yet → fallback to total messages from others
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE to_user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unread_messages = $stmt->get_result()->fetch_assoc()['unread'] ?? 0;
    $stmt->close();
}

// 2. Upcoming Harvest (≤ 3 days)
$stmt = $conn->prepare("SELECT COUNT(*) as upcoming_harvest FROM rice_plans 
                        WHERE user_id = ? 
                        AND harvest_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_harvest = $stmt->get_result()->fetch_assoc()['upcoming_harvest'] ?? 0;
$stmt->close();

// 3. Recent Announcements
$stmt = $conn->prepare("SELECT COUNT(*) as new_ann FROM announcements 
                        WHERE role IN ('global', 'farmer') 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$new_announcements = $stmt->get_result()->fetch_assoc()['new_ann'] ?? 0;
$stmt->close();

$total_notifications = $unread_messages + $upcoming_harvest + $new_announcements;

// Weather
$apiKey = "67f431da7de34177a0d24928263103";
$city = "Sagay City";
$url = "http://api.weatherapi.com/v1/current.json?key=$apiKey&q=" . urlencode($city);
$response = @file_get_contents($url);
$weather = $response ? json_decode($response, true)['current'] ?? null : null;
?>

<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Dashboard</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .floating-panel {
            position: fixed; top: 0; right: -500px; width: 480px; height: 100vh;
            background: #1e293b; box-shadow: -15px 0 40px rgba(0,0,0,0.6);
            transition: right 0.4s ease; overflow-y: auto; z-index: 1100; padding: 25px;
        }
        .floating-panel.show { right: 0; }
        .profile-photo { width: 160px; height: 160px; object-fit: cover; border: 5px solid #10b981; border-radius: 50%; }
        .section-card { background: #0f172a; border: 1px solid #334155; }
        .notification-badge { background: #ef4444; color: white; font-size: 0.75rem; font-weight: 700; }
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
                    <!-- Search Bar -->
                    <div class="dropdown nxl-h-item nxl-header-search">
                        <a href="javascript:void(0);" class="nxl-head-link me-0" data-bs-toggle="dropdown">
                            <i class="feather-search"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-3" style="width: 320px;">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search detections..." onkeyup="filterDetections()">
                            <div id="searchResults" class="mt-3"></div>
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
                        <a class="nxl-head-link me-3" data-bs-toggle="dropdown" href="#" role="button" data-bs-auto-close="outside">
                            <i class="feather-bell"></i>
                            <?php if ($total_notifications > 0): ?>
                            <span class="badge bg-danger nxl-h-badge notification-badge"><?= $total_notifications ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown" style="width: 340px;">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <h6 class="fw-bold mb-0">Notifications</h6>
                            </div>
                            <div class="p-2">
                                <?php if ($unread_messages > 0): ?>
                                <a href="farmer_live_com.php" class="dropdown-item d-flex align-items-center gap-3 py-3">
                                    <i class="feather-message-square text-info"></i>
                                    <div>
                                        <strong>New Messages</strong><br>
                                        <small class="text-muted"><?= $unread_messages ?> unread message(s)</small>
                                    </div>
                                </a>
                                <?php endif; ?>

                                <?php if ($upcoming_harvest > 0): ?>
                                <a href="farmer_yield_planner.php" class="dropdown-item d-flex align-items-center gap-3 py-3">
                                    <i class="feather-calendar text-warning"></i>
                                    <div>
                                        <strong>Harvest Reminder</strong><br>
                                        <small class="text-muted"><?= $upcoming_harvest ?> plan(s) harvesting soon</small>
                                    </div>
                                </a>
                                <?php endif; ?>

                                <?php if ($new_announcements > 0): ?>
                                <a href="farmer_announcement.php" class="dropdown-item d-flex align-items-center gap-3 py-3">
                                    <i class="feather-volume-2 text-success"></i>
                                    <div>
                                        <strong>New Announcements</strong><br>
                                        <small class="text-muted"><?= $new_announcements ?> new announcement(s)</small>
                                    </div>
                                </a>
                                <?php endif; ?>

                                <?php if ($total_notifications === 0): ?>
                                <div class="text-center py-4 text-muted">No new notifications</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                    

                    <!-- User Profile -->
                    <div class="dropdown nxl-h-item">
                        <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside">
                            <img src="<?= $user['photo'] ? htmlspecialchars($user['photo']) : 'assets/images/avatar/1.png' ?>" 
                                 alt="user-image" class="img-fluid user-avtar me-0" />
                        </a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                            <div class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <img alt="user-image" class="img-fluid user-avtar" 
                                         src="<?= $user['photo'] ? htmlspecialchars($user['photo']) : 'assets/images/avatar/1.png' ?>" />
                                    <div>
                                        <h6 class="text-dark mb-0"><?= htmlspecialchars($full_name) ?> 
                                            <span class="badge bg-soft-success text-success ms-1">Farmer</span>
                                        </h6>
                                        <span class="fs-12 fw-medium text-muted"><?= htmlspecialchars($email) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0)" onclick="showProfilePanel(0)" class="dropdown-item">
                                <i class="feather-user"></i> Profile Details
                            </a>
                            <a href="javascript:void(0)" onclick="showProfilePanel(1)" class="dropdown-item">
                                <i class="feather-settings"></i> Account Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="feather-log-out"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content - Everything below this line is exactly as before -->
    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h5 class="m-b-10">🌾 RiceGuard AI Dashboard</h5>
                    <p class="m-b-0 text-muted">Welcome back, <?= htmlspecialchars($full_name) ?> • Real-time farm overview</p>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">

                    <!-- Statistics Row -->
                    <div class="row g-4">
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stretch stretch-full">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-camera-retro fa-2x text-primary mb-3"></i>
                                            <h6 class="text-muted mb-1">Total Detections</h6>
                                            <h2 class="fw-bold mb-0"><?= number_format($total_detections) ?></h2>
                                        </div>
                                        <span class="badge bg-primary">Season</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stretch stretch-full">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-bug fa-2x text-danger mb-3"></i>
                                            <h6 class="text-muted mb-1">Affected Plants</h6>
                                            <h2 class="fw-bold mb-0 text-danger"><?= number_format($affected) ?></h2>
                                        </div>
                                        <span class="badge bg-danger">Attention</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stretch stretch-full">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-seedling fa-2x text-success mb-3"></i>
                                            <h6 class="text-muted mb-1">Healthy Plants</h6>
                                            <h2 class="fw-bold mb-0 text-success"><?= number_format($healthy) ?></h2>
                                        </div>
                                        <span class="badge bg-success">Good</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card stretch stretch-full">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa-solid fa-calendar-check fa-2x text-warning mb-3"></i>
                                            <h6 class="text-muted mb-1">Rice Plans</h6>
                                            <h2 class="fw-bold mb-0"><?= number_format($plan_count) ?></h2>
                                        </div>
                                        <span class="badge bg-warning">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weather + Recent Detections -->
                    <div class="row g-4 mt-4">
                        <div class="col-lg-5">
                            <div class="card stretch stretch-full">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fa-solid fa-cloud-sun me-2"></i> Today's Weather • Sagay City</h5>
                                </div>
                                <div class="card-body text-center p-5">
                                    <?php if (isset($weather) && $weather): ?>
                                        <div class="display-1 fw-bold text-white mb-2"><?= round($weather['temp_c']) ?>°C</div>
                                        <h4 class="text-muted"><?= htmlspecialchars($weather['condition']['text'] ?? 'Clear') ?></h4>
                                        <div class="d-flex justify-content-center gap-5 mt-4 text-start">
                                            <div><i class="fa-solid fa-droplet text-info"></i> <?= $weather['humidity'] ?? 0 ?>% Humidity</div>
                                            <div><i class="fa-solid fa-wind text-info"></i> <?= round($weather['wind_kph'] ?? 0) ?> km/h</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="py-5 text-muted">
                                            <i class="fa-solid fa-cloud-sun fa-3x mb-3"></i>
                                            <p>Weather information unavailable at the moment.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card stretch stretch-full">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i> Recent Detections</h5>
                                    <a href="farmer_history.php" class="btn btn-sm btn-outline-light">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recent)): ?>
                                        <div class="text-center py-5">
                                            <i class="fa-solid fa-seedling fa-4x text-muted mb-3"></i>
                                            <p class="text-muted mb-2">No detections recorded yet.</p>
                                            <a href="farmer_index.php" class="btn btn-success">Upload First Image</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush" id="recentDetections">
                                            <?php foreach ($recent as $item): 
                                                $display_name = str_replace('_', ' ', ucwords($item['class_key']));
                                            ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 detection-item" 
                                                 data-name="<?= strtolower($display_name) ?>">
                                                <div class="d-flex align-items-center gap-3">
                                                    <i class="fa-solid fa-leaf text-success fa-lg"></i>
                                                    <div>
                                                        <strong><?= htmlspecialchars($display_name) ?></strong>
                                                        <small class="d-block text-muted"><?= date('M d, Y • h:i A', strtotime($item['created_at'])) ?></small>
                                                    </div>
                                                </div>
                                                <span class="badge bg-success px-3 py-2"><?= $item['confidence'] ?>% Confidence</span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row g-4 mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fa-solid fa-bolt me-2"></i> Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3 col-sm-6">
                                            <a href="farmer_index.php" class="btn btn-success w-100 py-4 d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-upload fa-2x"></i>
                                                <span>Upload Image</span>
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <a href="farmer_camera.php" class="btn btn-outline-light w-100 py-4 d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-camera fa-2x"></i>
                                                <span>Live Camera</span>
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <a href="farmer_yield_planner.php" class="btn btn-outline-light w-100 py-4 d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-calendar fa-2x"></i>
                                                <span>Rice Planner</span>
                                            </a>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <a href="farmer_history.php" class="btn btn-outline-light w-100 py-4 d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-history fa-2x"></i>
                                                <span>View History</span>
                                            </a>
                                        </div>
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
            <h5 id="panelTitle">My Profile</h5>
            <button onclick="hidePanel()" class="btn btn-outline-light btn-sm">✕</button>
        </div>

        <?= $msg ?>

        <ul class="nav nav-tabs mb-4" id="profileTabs">
            <li class="nav-item"><a class="nav-link active" onclick="switchTab(0)">Profile</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchTab(1)">Account Settings</a></li>
        </ul>

        <div id="tab-profile">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <div class="text-center mb-4">
                    <div class="position-relative d-inline-block">
                        <img id="profile-pic" 
                             src="<?= $user['photo'] ? htmlspecialchars($user['photo']) : 'assets/images/avatar/1.png' ?>" 
                             class="profile-photo" alt="Profile Photo">
                        <label for="photo-upload" class="btn btn-success btn-sm position-absolute bottom-0 end-0 rounded-circle p-2">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" id="photo-upload" name="photo" accept="image/*" class="d-none" onchange="previewPhoto(this)">
                    </div>
                </div>

                <div class="section-card card mb-4">
                    <div class="card-body">
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
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="section-card card mb-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Farm Size (hectares)</label>
                            <input type="number" step="0.1" name="farm_size" class="form-control" value="<?= $user['farm_size'] ?? '' ?>">
                        </div>
                        <div class="mb-3">
                            <label>Preferred Rice Variety</label>
                            <input type="text" name="rice_variety" class="form-control" value="<?= htmlspecialchars($user['preferred_variety'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label>About My Farm</label>
                            <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 py-3">Save Changes</button>
            </form>
        </div>

        <div id="tab-settings" style="display:none">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="section-card card">
                    <div class="card-body">
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
                        <button type="submit" class="btn btn-success w-100 py-3">Change Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
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
        document.querySelectorAll('#profileTabs .nav-link').forEach((el, i) => {
            el.classList.toggle('active', i === tab);
        });
        document.getElementById('tab-profile').style.display = tab === 0 ? 'block' : 'none';
        document.getElementById('tab-settings').style.display = tab === 1 ? 'block' : 'none';
        document.getElementById('panelTitle').textContent = tab === 0 ? 'My Profile' : 'Account Settings';
    }

    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-pic').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Search Functionality
    function filterDetections() {
        const term = document.getElementById('searchInput').value.toLowerCase().trim();
        const items = document.querySelectorAll('.detection-item');
        const resultsContainer = document.getElementById('searchResults');
        
        resultsContainer.innerHTML = '';

        if (!term) {
            resultsContainer.innerHTML = '<small class="text-muted">Type to search detections...</small>';
            return;
        }

        let found = false;
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            if (name.includes(term)) {
                found = true;
                const clone = item.cloneNode(true);
                clone.classList.add('bg-emerald-900', 'text-white');
                resultsContainer.appendChild(clone);
            }
        });

        if (!found) {
            resultsContainer.innerHTML = '<small class="text-muted">No matching detections found.</small>';
        }
    }
    </script>
</body>
</html>