<?php
// all_user_history.php - NXL Template + Full Rich Display + Clickable Photo Modal
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    session_start();
    header("Location: admin_login.php");
    exit;
}

if (!isset($_SESSION['last_regenerated']) || time() - $_SESSION['last_regenerated'] > 180) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
}

require_once __DIR__ . '/database/database.php';

// ====================== DATA PREPARATION ======================
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

// Load global knowledge base
$knowledgeBase = [];
$query = "SELECT disease, type, treatments, causes, nutrient_deficiency, grain_damage, prevention 
          FROM treatment_records WHERE user_id IS NULL";
$resultKB = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($resultKB)) {
    $key = strtolower(trim($row['disease']));
    $knowledgeBase[$key] = $row;
}

// Fetch all farmers
$allUsersData = [];
$userQuery = "SELECT id, full_name, email FROM users WHERE role = 'farmer' ORDER BY full_name ASC";
$userResult = mysqli_query($conn, $userQuery);

while ($user = mysqli_fetch_assoc($userResult)) {
    $user_id = $user['id'];
    $user_name = $user['full_name'] ?: $user['email'];

    // Detections
    $detectionData = [];
    $stmt = $conn->prepare("SELECT class_key, image_path FROM user_detections WHERE user_id = ? ORDER BY class_key, created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $currentKey = '';
    $images = [];
    while ($row = $result->fetch_assoc()) {
        if ($currentKey !== $row['class_key']) {
            if ($currentKey !== '') {
                $isPest = isset($pestNames[$currentKey]);
                $name = $isPest ? ($pestNames[$currentKey] ?? $currentKey) : ($diseaseNames[$currentKey] ?? $currentKey);
                $detectionData[] = [
                    'class_key' => $currentKey,
                    'class_name' => $name,
                    'is_pest' => $isPest ? 1 : 0,
                    'images' => $images
                ];
            }
            $currentKey = $row['class_key'];
            $images = [];
        }
        if ($row['image_path']) $images[] = $row['image_path'];
    }
    if ($currentKey !== '') {
        $isPest = isset($pestNames[$currentKey]);
        $name = $isPest ? ($pestNames[$currentKey] ?? $currentKey) : ($diseaseNames[$currentKey] ?? $currentKey);
        $detectionData[] = [
            'class_key' => $currentKey,
            'class_name' => $name,
            'is_pest' => $isPest ? 1 : 0,
            'images' => $images
        ];
    }
    $stmt->close();

    // Rice Plans
    $ricePlans = [];
    $planStmt = $conn->prepare("SELECT * FROM rice_plans WHERE user_id = ? ORDER BY planting_date DESC");
    $planStmt->bind_param("i", $user_id);
    $planStmt->execute();
    $planResult = $planStmt->get_result();
    while ($row = $planResult->fetch_assoc()) {
        $ricePlans[] = $row;
    }
    $planStmt->close();

    $allUsersData[] = [
        'user_id' => $user_id,
        'user_name' => $user_name,
        'email' => $user['email'],
        'detectionData' => $detectionData,
        'ricePlans' => $ricePlans
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • All Users History</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .section-header {
            font-size: 1.75rem;
            font-weight: 700;
        }
        .detection-card {
            transition: all 0.3s ease;
        }
        .detection-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15);
        }
        .image-gallery img { 
            width: 85px; 
            height: 85px; 
            object-fit: cover; 
            border-radius: 12px; 
            border: 2px solid #10b981; 
            cursor: pointer;
            transition: all 0.2s;
        }
        .image-gallery img:hover {
            transform: scale(1.08);
            border-color: #34d399;
        }
        .modal-img {
            max-height: 85vh;
            width: auto;
            max-width: 100%;
            border-radius: 12px;
        }
        .scrollable-section { 
            max-height: 720px; 
            overflow-y: auto; 
            padding-right: 12px; 
        }
        .scrollable-section::-webkit-scrollbar { 
            width: 6px; 
        }
        .scrollable-section::-webkit-scrollbar-thumb { 
            background: #10b981; 
            border-radius: 20px; 
        }
        .rice-plans-scroll {
            max-height: 520px;
            overflow-y: auto;
            padding-right: 8px;
        }
        .rice-plans-scroll::-webkit-scrollbar { 
            width: 6px; 
        }
        .rice-plans-scroll::-webkit-scrollbar-thumb { 
            background: #10b981; 
            border-radius: 20px; 
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
    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h5 class="m-b-10">All Farmers History</h5>
                    <p class="text-muted mb-0">Complete detection and rice plan history for all farmers</p>
                </div>
            </div>

            <div class="main-content">
                <?php if (empty($allUsersData)): ?>
                    <div class="card">
                        <div class="card-body text-center py-20 text-muted">
                            No farmer data found yet.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($allUsersData as $userData): ?>
                        <div class="card mb-5">
                            <div class="card-header d-flex align-items-center gap-4">
                                <div class="w-16 h-16 bg-emerald-600 rounded-3xl flex items-center justify-center   text-4xl">👤</div>
                                <div>
                                    <h4 class="mb-1"><?= htmlspecialchars($userData['user_name']) ?></h4>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($userData['email']) ?></p>
                                </div>
                            </div>

                            <div class="card-body">

                                <!-- Detection History -->
                                <h5 class="mb-4 section-header"><i class="fa-solid fa-magnifying-glass text-emerald-400"></i> Detection History</h5>
                                <div class="row g-4">

                                    <!-- DISEASES -->
                                    <div class="col-lg-6">
                                        <div class="card detection-card h-100">
                                            <div class="card-header">
                                                <h6 class="mb-0"><span class="text-3xl">🌾</span> Detected Diseases</h6>
                                            </div>
                                            <div class="card-body scrollable-section">
                                                <?php 
                                                $diseases = array_filter($userData['detectionData'], fn($d) => $d['is_pest'] == 0);
                                                if (empty($diseases)): ?>
                                                    <p class="text-muted text-center py-12">No diseases detected yet.</p>
                                                <?php else: foreach ($diseases as $det):
                                                    $kb = $knowledgeBase[$det['class_key']] ?? [];
                                                ?>
                                                <div class="mb-8 p-6 bg-zinc-900 border border-zinc-700 rounded-3xl">
                                                    <div class="flex items-center gap-4 mb-6">
                                                        <div class="w-14 h-14 bg-emerald-500/20 text-emerald-400 rounded-3xl flex items-center justify-center text-4xl">🌾</div>
                                                        <div>
                                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($det['class_name']) ?></h5>
                                                            <span class="badge bg-emerald-500">DISEASE</span>
                                                        </div>
                                                    </div>

                                                    <!-- Severity Box -->
                                                    <div class="mb-6 p-5 bg-gradient-to-r from-emerald-500/10 to-green-500/10 border border-emerald-400/30 rounded-2xl d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="text-3xl fw-bold text-emerald-400">MODERATE</div>
                                                            <small class="text-emerald-300">Damage Assessment</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-muted">Severity</small>
                                                            <div class="text-4xl fw-bold text-emerald-400">65%</div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-3 text-sm">
                                                        <div class="col-md-6">
                                                            <strong class="text-emerald-400">Recommended Treatments</strong>
                                                            <div class="bg-emerald-500/10 border border-emerald-400/30 p-4 rounded mt-2"><?= nl2br(htmlspecialchars($kb['treatments'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong class="text-blue-400">Common Causes</strong>
                                                            <div class="bg-emerald-500/10 border border-emerald-400/30 p-4 rounded mt-2"><?= nl2br(htmlspecialchars($kb['causes'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong class="text-yellow-400">Nutrient Deficiency</strong>
                                                            <div class="bg-emerald-500/10 border border-emerald-400/30 p-4 rounded mt-2"><?= nl2br(htmlspecialchars($kb['nutrient_deficiency'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong class="text-amber-400">Grain Damage</strong>
                                                            <div class="bg-emerald-500/10 border border-emerald-400/30 p-4 rounded mt-2"><?= nl2br(htmlspecialchars($kb['grain_damage'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-12">
                                                            <strong class="text-emerald-400">Prevention Tips</strong>
                                                            <div class="bg-emerald-500/10 border border-emerald-400/30 p-4 rounded mt-2"><?= nl2br(htmlspecialchars($kb['prevention'] ?? '—')) ?></div>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($det['images'])): ?>
                                                    <div class="mt-6">
                                                        <p class="text-xs text-muted mb-3">Uploaded Photos</p>
                                                        <div class="image-gallery d-flex flex-wrap gap-3">
                                                            <?php foreach ($det['images'] as $img): ?>
                                                                <img src="<?= htmlspecialchars($img) ?>" alt="Photo" onclick="showImageModal(this.src)">
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PESTS -->
                                    <div class="col-lg-6">
                                        <div class="card detection-card h-100">
                                            <div class="card-header">
                                                <h6 class="mb-0"><span class="text-3xl">🐛</span> Detected Pests</h6>
                                            </div>
                                            <div class="card-body scrollable-section">
                                                <?php 
                                                $pests = array_filter($userData['detectionData'], fn($d) => $d['is_pest'] == 1);
                                                if (empty($pests)): ?>
                                                    <p class="text-muted text-center py-12">No pests detected yet.</p>
                                                <?php else: foreach ($pests as $det):
                                                    $kb = $knowledgeBase[$det['class_key']] ?? [];
                                                ?>
                                                <div class="mb-8 p-6 bg-zinc-900 border border-zinc-700 rounded-3xl">
                                                    <div class="flex items-center gap-4 mb-6">
                                                        <div class="w-14 h-14 bg-orange-500/20 text-orange-400 rounded-3xl flex items-center justify-center text-4xl">🐛</div>
                                                        <div>
                                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($det['class_name']) ?></h5>
                                                            <span class="badge bg-orange-500">PEST</span>
                                                        </div>
                                                    </div>

                                                    <div class="mb-6 p-5 bg-gradient-to-r from-orange-500/10 to-red-500/10 border border-orange-400/30 rounded-2xl d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="text-3xl fw-bold text-orange-400">MODERATE</div>
                                                            <small class="text-orange-300">Damage Assessment</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-muted">Severity</small>
                                                            <div class="text-4xl fw-bold text-orange-400">65%</div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-3 text-sm">
                                                        <div class="col-md-6">
                                                            <strong class="text-emerald-400">Recommended Treatments</strong>
                                                            <div class=" p-3 rounded mt-2"><?= nl2br(htmlspecialchars($kb['treatments'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong class="text-blue-400">Common Causes</strong>
                                                            <div class=" p-3 rounded mt-2"><?= nl2br(htmlspecialchars($kb['causes'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong class="text-orange-400">Damage Symptoms</strong>
                                                            <div class=" p-3 rounded mt-2"><?= nl2br(htmlspecialchars($kb['grain_damage'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <strong class="text-purple-400">Natural Enemies</strong>
                                                            <div class=" p-3 rounded mt-2"><?= nl2br(htmlspecialchars($kb['nutrient_deficiency'] ?? '—')) ?></div>
                                                        </div>
                                                        <div class="col-12">
                                                            <strong class="text-emerald-400">Prevention Tips</strong>
                                                            <div class="bg-emerald-500/10 border border-emerald-400/30 p-4 rounded mt-2"><?= nl2br(htmlspecialchars($kb['prevention'] ?? '—')) ?></div>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($det['images'])): ?>
                                                    <div class="mt-6">
                                                        <p class="text-xs text-muted mb-3">Uploaded Photos</p>
                                                        <div class="image-gallery d-flex flex-wrap gap-3">
                                                            <?php foreach ($det['images'] as $img): ?>
                                                                <img src="<?= htmlspecialchars($img) ?>" alt="Photo" onclick="showImageModal(this.src)">
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rice Plans -->
                                <h5 class="mt-5 mb-4 section-header"><i class="fa-solid fa-calendar-check text-amber-400"></i> Rice Plans</h5>
                                <?php if (empty($userData['ricePlans'])): ?>
                                    <div class=" border border-zinc-700 rounded-3xl p-12 text-center">
                                        <p class="text-muted">No rice plans created yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="rice-plans-scroll row g-4">
                                        <?php foreach ($userData['ricePlans'] as $plan): 
                                            $riskColor = match($plan['risk'] ?? 'Medium') {
                                                'Low' => 'success', 'Medium' => 'warning', default => 'danger'
                                            };
                                        ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                                        <div>
                                                            <h6 class="fw-bold"><?= htmlspecialchars($plan['variety']) ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($plan['growth_stage'] ?? '') ?> • <?= htmlspecialchars($plan['planting_date']) ?></small>
                                                        </div>
                                                        <span class="badge bg-<?= $riskColor ?>"><?= strtoupper($plan['risk'] ?? 'Medium') ?> RISK</span>
                                                    </div>

                                                    <div class="row text-sm mb-4">
                                                        <div class="col-6">
                                                            <strong>Field Size</strong><br>
                                                            <span class="fs-5"><?= number_format($plan['size'], 1) ?> ha</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Expected Harvest</strong><br>
                                                            <span class="fs-5"><?= htmlspecialchars($plan['harvest_date']) ?></span>
                                                        </div>
                                                        <div class="col-6 mt-3">
                                                            <strong>Total Yield</strong><br>
                                                            <span class="fs-5"><?= number_format($plan['total_yield']) ?> kg</span>
                                                        </div>
                                                        <div class="col-6 mt-3">
                                                            <strong>Yield / ha</strong><br>
                                                            <span class="fs-5"><?= number_format($plan['yield_per_hectare']) ?> kg</span>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($plan['notes'])): ?>
                                                    <div class="mb-4 p-4  border border-zinc-700 rounded-3xl">
                                                        <strong class="text-xs text-muted">NOTES</strong>
                                                        <p class="small mt-2"><?= nl2br(htmlspecialchars($plan['notes'])) ?></p>
                                                    </div>
                                                    <?php endif; ?>

                                                    <div>
                                                        <strong class="text-xs text-amber-400 d-block mb-2">SMART SUGGESTIONS</strong>
                                                        <ul class="list-unstyled small">
                                                            <?php
                                                            $suggestions = [];
                                                            if (($plan['pest'] ?? '') === "High") $suggestions[] = "Apply pest control immediately.";
                                                            if (($plan['water'] ?? '') === "Lacking") $suggestions[] = "Improve irrigation schedule.";
                                                            if (($plan['water'] ?? '') === "Flooded") $suggestions[] = "Drain excess water to prevent root rot.";
                                                            if (($plan['health'] ?? '') === "Poor") $suggestions[] = "Apply balanced fertilizer.";
                                                            if (($plan['weather'] ?? '') === "Dry") $suggestions[] = "Increase watering frequency.";
                                                            if (($plan['weather'] ?? '') === "Rainy") $suggestions[] = "Monitor for fungal diseases and improve drainage.";
                                                            if (empty($suggestions)) $suggestions[] = "Maintain current good practices.";
                                                            foreach ($suggestions as $s): ?>
                                                            <li class="mb-2"><i class="fa-solid fa-circle-check text-emerald-400 me-2"></i><?= htmlspecialchars($s) ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Image Viewer Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="modalImage" src="" class="modal-img" alt="Full Size Photo">
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    function showImageModal(src) {
        document.getElementById('modalImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
    </script>
</body>
</html>