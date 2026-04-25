<?php
// farmer_history.php - FINAL VERSION (Three dots dropdowns added)
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: login_signup.php");
    exit;
}

require_once 'database/database.php';

$user_id = $_SESSION['user_id'];

// ====================== AJAX HANDLERS ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        if ($_POST['action'] === 'delete_image' && isset($_POST['image_path'])) {
            $image_path = trim($_POST['image_path']);
            if (empty($image_path) || strpos($image_path, '..') !== false || !file_exists($image_path)) {
                echo json_encode(['success' => false, 'error' => 'Invalid path']);
                exit;
            }
            $file_deleted = unlink($image_path);
            $stmt = $conn->prepare("DELETE FROM user_detections WHERE user_id = ? AND image_path = ?");
            $stmt->bind_param("is", $user_id, $image_path);
            $db_deleted = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $file_deleted || $db_deleted]);
            exit;
        }

        if ($_POST['action'] === 'delete_detection' && isset($_POST['class_key'])) {
            $class_key = strtolower(trim($_POST['class_key']));
            
            $stmt = $conn->prepare("SELECT image_path FROM user_detections WHERE user_id = ? AND class_key = ?");
            $stmt->bind_param("is", $user_id, $class_key);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if ($row['image_path'] && file_exists($row['image_path'])) {
                    @unlink($row['image_path']);
                }
            }
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM user_detections WHERE user_id = ? AND class_key = ?");
            $stmt->bind_param("is", $user_id, $class_key);
            $success = $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => $success]);
            exit;
        }

        if ($_POST['action'] === 'delete_plan' && isset($_POST['plan_id'])) {
            $plan_id = (int)$_POST['plan_id'];
            $stmt = $conn->prepare("DELETE FROM rice_plans WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $plan_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
            exit;
        }

        // New: Update plan notes
        if ($_POST['action'] === 'update_plan_notes' && isset($_POST['plan_id']) && isset($_POST['notes'])) {
            $plan_id = (int)$_POST['plan_id'];
            $notes = $_POST['notes'];
            $stmt = $conn->prepare("UPDATE rice_plans SET notes = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $notes, $plan_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Server error']);
        exit;
    }
}

// ====================== NAMES & KNOWLEDGE BASE ======================
$diseaseNames = [
    'healthy_rice_plant' => "Healthy Rice Plant",
    'bacterial_leaf_blight' => "Bacterial Leaf Blight",
    'leaf_blast' => "Leaf Blast",
    'rice_false_smut' => "Rice False Smut",
    'sheath_blight' => "Sheath Blight",
    'tungro_virus' => "Tungro Virus"
];

$pestNames = [
    'brown_planthopper' => "Brown Planthopper",
    'leaf_folders' => "Leaf Folders",
    'leafhopper' => "Leafhopper",
    'rice_bug' => "Rice Bug",
    'rice_gall_midge' => "Rice Gall Midge",
    'rice_leaf_roller' => "Rice Leaf Roller",
    'rice_stem_borer' => "Rice Stem Borer",
    'snail' => "Snail"
];

$knowledgeBase = [];
$query = "SELECT disease, treatments, causes, nutrient_deficiency, grain_damage, prevention 
          FROM treatment_records WHERE user_id IS NULL";
$resultKB = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($resultKB)) {
    $key = strtolower(trim($row['disease']));
    $knowledgeBase[$key] = $row;
}

// ====================== LOAD DETECTIONS ======================
$detectionData = [];
$stmt = $conn->prepare("SELECT class_key, confidence, image_path FROM user_detections 
                        WHERE user_id = ? ORDER BY class_key, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$currentKey = '';
$images = [];
$confidences = [];

while ($row = $result->fetch_assoc()) {
    $key = strtolower(trim($row['class_key']));
    
    if ($currentKey !== $key && $currentKey !== '') {
        $isPest = isset($pestNames[$currentKey]);
        $name = $isPest ? ($pestNames[$currentKey] ?? $currentKey) : ($diseaseNames[$currentKey] ?? $currentKey);
        
        $detectionData[] = [
            'class_key' => $currentKey,
            'class_name' => $name,
            'is_pest' => $isPest,
            'images' => $images,
            'confidence' => !empty($confidences) ? max($confidences) : 65
        ];
        $images = [];
        $confidences = [];
    }
    
    $currentKey = $key;
    if ($row['image_path']) $images[] = $row['image_path'];
    if (isset($row['confidence'])) $confidences[] = (int)$row['confidence'];
}

if ($currentKey !== '') {
    $isPest = isset($pestNames[$currentKey]);
    $name = $isPest ? ($pestNames[$currentKey] ?? $currentKey) : ($diseaseNames[$currentKey] ?? $currentKey);
    $detectionData[] = [
        'class_key' => $currentKey,
        'class_name' => $name,
        'is_pest' => $isPest,
        'images' => $images,
        'confidence' => !empty($confidences) ? max($confidences) : 65
    ];
}
$stmt->close();

// ====================== LOAD RICE PLANS ======================
$ricePlans = [];
$stmt = $conn->prepare("SELECT * FROM rice_plans WHERE user_id = ? ORDER BY planting_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultPlans = $stmt->get_result();
while ($row = $resultPlans->fetch_assoc()) {
    $ricePlans[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • History & Data</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .hidden { display: none !important; }
        .scrollable-section { max-height: 680px; overflow-y: auto; scrollbar-width: thin; }
        .detection-card, .plan-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .detection-card:hover, .plan-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2) !important; }
        .image-gallery img {
            width: 80px; height: 80px; object-fit: cover; border-radius: 8px;
            border: 2px solid #10b981; cursor: pointer; transition: all 0.2s;
        }
        .image-gallery img:hover { transform: scale(1.08); border-color: #34d399; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); align-items: center; justify-content: center; z-index: 9999; }
        .toast { position: fixed; top: 20px; right: 20px; padding: 16px 24px; border-radius: 12px; color: white; z-index: 10000; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); }
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
    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h5 class="m-b-10">Detection History &amp; Rice Plans</h5>
                    <p class="m-b-0 text-muted">All your past detections and saved rice plans</p>
                </div>
                <div class="page-header-content d-flex align-items-center justify-content-end gap-3">
                    <button onclick="window.print()" class="btn btn-outline-light">
                        <i class="fa-solid fa-print me-2"></i> PRINT ALL HISTORY
                    </button>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">

                    <!-- DETECTION HISTORY -->
                    <div class="row g-4">
                        <!-- DISEASES -->
                        <div class="col-xl-6">
                            <div class="card stretch stretch-full">
                                <div class="card-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-seedling fa-2x text-success"></i>
                                        <h5 class="card-title mb-0">Detected Diseases</h5>
                                    </div>
                                </div>
                                <div class="card-body scrollable-section p-4">
                                    <?php 
                                    $diseases = array_filter($detectionData, fn($d) => !$d['is_pest']);
                                    if (empty($diseases)): ?>
                                        <div class="text-center py-12 text-muted">
                                            <i class="fa-solid fa-seedling fa-4x mb-3 opacity-25"></i>
                                            <p>No diseases detected yet.</p>
                                        </div>
                                    <?php else: foreach ($diseases as $det):
                                        $kb = $knowledgeBase[$det['class_key']] ?? [];
                                        $confidence = $det['confidence'] ?? 65;
                                        
                                        if (stripos($det['class_key'], 'healthy') !== false) {
                                            $severityLabel = "HEALTHY"; $severityPercent = 0; $severityColor = "success";
                                        } elseif ($confidence >= 80) {
                                            $severityLabel = "SEVERE"; $severityPercent = $confidence; $severityColor = "danger";
                                        } elseif ($confidence >= 60) {
                                            $severityLabel = "MODERATE"; $severityPercent = $confidence; $severityColor = "warning";
                                        } else {
                                            $severityLabel = "LOW"; $severityPercent = $confidence; $severityColor = "info";
                                        }
                                    ?>
                                    <div class="detection-card card mb-4 border-0 shadow-sm">
                                        <div class="card-body position-relative">
                                            <!-- Three dots dropdown at top right -->
                                            <div class="dropdown position-absolute top-0 end-0 me-3 mt-3">
                                                <button class="btn btn-link text-muted p-1" data-bs-toggle="dropdown">
                                                    <i class="fa-solid fa-ellipsis-vertical fa-lg"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" onclick="printCard(this)"><i class="fa-solid fa-print me-2"></i>Print</a></li>
                                                    <li><a class="dropdown-item text-danger" onclick="deleteWholeDetection('<?= htmlspecialchars($det['class_key']) ?>', this)"><i class="fa-solid fa-trash me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                                                        <i class="fa-solid fa-seedling fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0"><?= htmlspecialchars($det['class_name']) ?></h5>
                                                        <small class="text-muted"><?= $confidence ?>% confidence</small>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?= $severityColor ?> fs-6 px-3 py-2"><?= $severityLabel ?></span>
                                                    <div class="text-<?= $severityColor ?> fw-bold mt-1"><?= $severityPercent ?>%</div>
                                                </div>
                                            </div>

                                            <!-- Rest of disease card content (unchanged) -->
                                            <div class="row g-3 text-sm">
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1 text-success"><i class="fa-solid fa-prescription-bottle-medical"></i> Treatments</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['treatments'] ?? 'No data available yet.')) ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1"><i class="fa-solid fa-question-circle"></i> Causes</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['causes'] ?? '—')) ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1 text-warning"><i class="fa-solid fa-seedling"></i> Nutrient Deficiency</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['nutrient_deficiency'] ?? '—')) ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1 text-amber"><i class="fa-solid fa-wheat-awn"></i> Grain Damage</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['grain_damage'] ?? '—')) ?></div>
                                                </div>
                                                <div class="col-12">
                                                    <strong class="d-block mb-1 text-success"><i class="fa-solid fa-shield-halved"></i> Prevention Tips</strong>
                                                    <div class="bg-success bg-opacity-10 border border-success border-opacity-25 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['prevention'] ?? '—')) ?></div>
                                                </div>
                                            </div>

                                            <?php if (!empty($det['images'])): ?>
                                            <div class="mt-4">
                                                <p class="text-muted small mb-2">Uploaded Photos (<?= count($det['images']) ?>)</p>
                                                <div class="image-gallery d-flex flex-wrap gap-3">
                                                    <?php foreach ($det['images'] as $img): ?>
                                                        <img src="<?= htmlspecialchars($img) ?>" onclick="showImageModal('<?= htmlspecialchars(addslashes($img)) ?>', '<?= htmlspecialchars($det['class_key']) ?>')" alt="">
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- PESTS (same three dots dropdown) -->
                        <div class="col-xl-6">
                            <div class="card stretch stretch-full">
                                <div class="card-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-bug fa-2x text-warning"></i>
                                        <h5 class="card-title mb-0">Detected Pests</h5>
                                    </div>
                                </div>
                                <div class="card-body scrollable-section p-4">
                                    <?php 
                                    $pests = array_filter($detectionData, fn($d) => $d['is_pest']);
                                    if (empty($pests)): ?>
                                        <div class="text-center py-12 text-muted">
                                            <i class="fa-solid fa-bug fa-4x mb-3 opacity-25"></i>
                                            <p>No pests detected yet.</p>
                                        </div>
                                    <?php else: foreach ($pests as $det):
                                        $kb = $knowledgeBase[$det['class_key']] ?? [];
                                        $confidence = $det['confidence'] ?? 65;
                                        
                                        if (stripos($det['class_key'], 'healthy') !== false) {
                                            $severityLabel = "HEALTHY"; $severityPercent = 0; $severityColor = "success";
                                        } elseif ($confidence >= 80) {
                                            $severityLabel = "SEVERE"; $severityPercent = $confidence; $severityColor = "danger";
                                        } elseif ($confidence >= 60) {
                                            $severityLabel = "MODERATE"; $severityPercent = $confidence; $severityColor = "warning";
                                        } else {
                                            $severityLabel = "LOW"; $severityPercent = $confidence; $severityColor = "info";
                                        }
                                    ?>
                                    <div class="detection-card card mb-4 border-0 shadow-sm">
                                        <div class="card-body position-relative">
                                            <!-- Three dots dropdown -->
                                            <div class="dropdown position-absolute top-0 end-0 me-3 mt-3">
                                                <button class="btn btn-link text-muted p-1" data-bs-toggle="dropdown">
                                                    <i class="fa-solid fa-ellipsis-vertical fa-lg"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" onclick="printCard(this)"><i class="fa-solid fa-print me-2"></i>Print</a></li>
                                                    <li><a class="dropdown-item text-danger" onclick="deleteWholeDetection('<?= htmlspecialchars($det['class_key']) ?>', this)"><i class="fa-solid fa-trash me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>

                                            <!-- Rest of pest card (unchanged) -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                                        <i class="fa-solid fa-bug fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0"><?= htmlspecialchars($det['class_name']) ?></h5>
                                                        <small class="text-muted"><?= $confidence ?>% confidence</small>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?= $severityColor ?> fs-6 px-3 py-2"><?= $severityLabel ?></span>
                                                    <div class="text-<?= $severityColor ?> fw-bold mt-1"><?= $severityPercent ?>%</div>
                                                </div>
                                            </div>

                                            <div class="row g-3 text-sm">
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1 text-success"><i class="fa-solid fa-prescription-bottle-medical"></i> Treatments</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['treatments'] ?? 'No data available yet.')) ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1"><i class="fa-solid fa-question-circle"></i> Causes</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['causes'] ?? '—')) ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1 text-orange"><i class="fa-solid fa-bug"></i> Damage Symptoms</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['grain_damage'] ?? '—')) ?></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <strong class="d-block mb-1 text-purple"><i class="fa-solid fa-spider"></i> Natural Enemies</strong>
                                                    <div class="bg-light bg-opacity-10 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['nutrient_deficiency'] ?? '—')) ?></div>
                                                </div>
                                                <div class="col-12">
                                                    <strong class="d-block mb-1 text-success"><i class="fa-solid fa-shield-halved"></i> Prevention Tips</strong>
                                                    <div class="bg-success bg-opacity-10 border border-success border-opacity-25 p-3 rounded-3"><?= nl2br(htmlspecialchars($kb['prevention'] ?? '—')) ?></div>
                                                </div>
                                            </div>

                                            <?php if (!empty($det['images'])): ?>
                                            <div class="mt-4">
                                                <p class="text-muted small mb-2">Uploaded Photos (<?= count($det['images']) ?>)</p>
                                                <div class="image-gallery d-flex flex-wrap gap-3">
                                                    <?php foreach ($det['images'] as $img): ?>
                                                        <img src="<?= htmlspecialchars($img) ?>" onclick="showImageModal('<?= htmlspecialchars(addslashes($img)) ?>', '<?= htmlspecialchars($det['class_key']) ?>')" alt="">
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== MY RICE PLANS - THREE DOTS DROPDOWN ==================== -->
                    <div class="mt-5">
                        <h5 class="mb-3"><i class="fa-solid fa-calendar-check me-2"></i> My Rice Plans</h5>
                        <div class="row g-4">
                            <?php if (empty($ricePlans)): ?>
                                <div class="col-12">
                                    <div class="card text-center p-5">
                                        <p class="text-muted">No rice plans created yet</p>
                                        <a href="farmer_yield_planner.php" class="btn btn-success mt-3">Create your first plan →</a>
                                    </div>
                                </div>
                            <?php else: foreach ($ricePlans as $plan): 
                                $riskColor = ($plan['risk'] === 'Low') ? 'success' : 
                                            (($plan['risk'] === 'Medium') ? 'warning' : 'danger');
                                $suggestions = [];
                                if (($plan['pest'] ?? '') === "High") $suggestions[] = "Apply pest control immediately.";
                                if (($plan['water'] ?? '') === "Lacking") $suggestions[] = "Improve irrigation schedule.";
                                if (($plan['water'] ?? '') === "Flooded") $suggestions[] = "Drain excess water to prevent root rot.";
                                if (($plan['health'] ?? '') === "Poor") $suggestions[] = "Apply balanced fertilizer.";
                                if (($plan['weather'] ?? '') === "Dry") $suggestions[] = "Increase watering frequency.";
                                if (($plan['weather'] ?? '') === "Rainy") $suggestions[] = "Monitor for fungal diseases and improve drainage.";
                                if (empty($suggestions)) $suggestions[] = "Maintain current good practices.";
                            ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body position-relative">
                                        <!-- Three dots dropdown at top right -->
                                        <div class="dropdown position-absolute top-0 end-0 me-3 mt-3">
                                            <button class="btn btn-link text-muted p-1" data-bs-toggle="dropdown">
                                                <i class="fa-solid fa-ellipsis-vertical fa-lg"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" onclick="printCard(this)"><i class="fa-solid fa-print me-2"></i>Print</a></li>
                                                <li><a class="dropdown-item" onclick="updatePlanNotes(<?= $plan['id'] ?>)"><i class="fa-solid fa-pen me-2"></i>Update Notes</a></li>
                                                <li><a class="dropdown-item text-danger" onclick="deletePlan(<?= $plan['id'] ?>)"><i class="fa-solid fa-trash me-2"></i>Delete</a></li>
                                            </ul>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="mb-0"><?= htmlspecialchars($plan['variety'] ?? 'Unnamed Variety') ?></h5>
                                            <span class="badge bg-<?= $riskColor ?>"><?= strtoupper($plan['risk'] ?? 'Low') ?> RISK</span>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            <?= htmlspecialchars($plan['growth_stage'] ?? '—') ?> • <?= htmlspecialchars($plan['planting_date']) ?>
                                        </p>

                                        <div class="row text-sm mb-4">
                                            <div class="col-6 mb-2"><strong>Field Size</strong><br><?= number_format($plan['size'], 1) ?> ha</div>
                                            <div class="col-6 mb-2"><strong>Expected Harvest</strong><br><?= htmlspecialchars($plan['harvest_date'] ?? '—') ?></div>
                                            <div class="col-6"><strong>Total Yield</strong><br><?= number_format($plan['total_yield'] ?? 0) ?> kg</div>
                                            <div class="col-6"><strong>Yield / ha</strong><br><?= number_format($plan['yield_per_hectare'] ?? 0) ?> kg</div>
                                        </div>

                                        <?php if (!empty($plan['notes'])): ?>
                                        <div class="mb-3">
                                            <strong class="text-muted">NOTES</strong>
                                            <p class="small"><?= nl2br(htmlspecialchars($plan['notes'])) ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <div>
                                            <strong class="text-muted">SMART SUGGESTIONS</strong>
                                            <ul class="list-unstyled small mt-2">
                                                <?php foreach ($suggestions as $s): ?>
                                                <li class="d-flex align-items-start gap-2 mb-1">
                                                    <i class="fa-solid fa-circle-check text-success mt-1"></i>
                                                    <?= htmlspecialchars($s) ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="bg-dark border border-secondary rounded-3 p-4 w-100" style="max-width: 600px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Photo Preview</h5>
                <button onclick="closeImageModal()" class="btn-close btn-close-white"></button>
            </div>
            <img id="modalImageBig" src="" class="img-fluid rounded-3 mb-4" style="max-height: 65vh; object-fit: contain;" alt="">
            <div class="d-flex gap-3">
                <button onclick="closeImageModal()" class="btn btn-secondary flex-fill">Close</button>
                <button onclick="deleteCurrentPhoto()" class="btn btn-danger flex-fill">
                    <i class="fa-solid fa-trash me-2"></i> Delete This Photo
                </button>
            </div>
        </div>
    </div>

    <!-- Update Notes Modal -->
    <div id="notesModal" class="modal">
        <div class="bg-dark border border-secondary rounded-3 p-4 w-100" style="max-width: 500px;">
            <h5 class="mb-3">Update Notes</h5>
            <textarea id="notesTextarea" class="form-control" rows="5"></textarea>
            <div class="d-flex gap-3 mt-4">
                <button onclick="closeNotesModal()" class="btn btn-secondary flex-fill">Cancel</button>
                <button onclick="saveNotes()" class="btn btn-success flex-fill">Save Notes</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/vendors/js/daterangepicker.min.js"></script>
    <script src="assets/vendors/js/apexcharts.min.js"></script>
    <script src="assets/vendors/js/circle-progress.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/dashboard-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
        let currentPhotoPath = '';
        let currentClassKeyForPhoto = '';
        let currentPlanId = null;

        function showImageModal(src, classKey) {
            currentPhotoPath = src;
            currentClassKeyForPhoto = classKey;
            document.getElementById('modalImageBig').src = src;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        function deleteCurrentPhoto() {
            if (!confirm("Delete this photo permanently?")) return;
            const formData = new FormData();
            formData.append('action', 'delete_image');
            formData.append('image_path', currentPhotoPath);
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeImageModal();
                    document.querySelectorAll(`img[src="${currentPhotoPath}"]`).forEach(img => img.remove());
                    showToast('✅ Photo deleted successfully', 'success');
                } else alert('Failed to delete photo');
            });
        }

        function deleteWholeDetection(classKey, btn) {
            if (!confirm(`Delete ALL photos and data for "${classKey}"?`)) return;
            btn.disabled = true;
            const formData = new FormData();
            formData.append('action', 'delete_detection');
            formData.append('class_key', classKey);
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) btn.closest('.card').remove();
                else alert('Delete failed');
            });
        }

        function printCard(btn) {
            const card = btn.closest('.card');
            const original = document.body.innerHTML;
            document.body.innerHTML = card.outerHTML;
            window.print();
            document.body.innerHTML = original;
            location.reload();
        }

        function deletePlan(planId) {
            if (!confirm("Delete this rice plan?")) return;
            const formData = new FormData();
            formData.append('action', 'delete_plan');
            formData.append('plan_id', planId);
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(() => location.reload());
        }

        // Update Notes
        function updatePlanNotes(planId) {
            currentPlanId = planId;
            document.getElementById('notesTextarea').value = '';
            document.getElementById('notesModal').style.display = 'flex';
        }

        function closeNotesModal() {
            document.getElementById('notesModal').style.display = 'none';
        }

        function saveNotes() {
            const notes = document.getElementById('notesTextarea').value.trim();
            if (!currentPlanId) return;

            const formData = new FormData();
            formData.append('action', 'update_plan_notes');
            formData.append('plan_id', currentPlanId);
            formData.append('notes', notes);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeNotesModal();
                    showToast('✅ Notes updated successfully', 'success');
                    setTimeout(() => location.reload(), 800);
                } else alert('Failed to update notes');
            });
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type === 'success' ? 'bg-success' : 'bg-danger'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3500);
        }
    </script>
</body>
</html>