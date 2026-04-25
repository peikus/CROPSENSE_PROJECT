<?php
// knowledge_modifier.php - UPDATED (Added Type label)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

require_once 'database/database.php';

// Load original snapshot
$originalFile = 'original_knowledge.json';
$originalData = file_exists($originalFile) ? json_decode(file_get_contents($originalFile), true) : [];

// Fetch current records with history
$sql = "SELECT t.*, 
           COALESCE(u.email, 'System') as editor_email,
           COALESCE(u.role, 'Unknown') as editor_role 
    FROM treatment_records t
    LEFT JOIN users u ON t.updated_by = u.id
    WHERE t.user_id IS NULL  
    ORDER BY t.type, t.disease, t.updated_at DESC";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $dbKey = strtolower($row['disease']);
    $type = $row['type'] ?? (in_array($dbKey, ['healthy_rice_plant','bacterial_leaf_blight','leaf_blast','rice_false_smut','sheath_blight','tungro_virus']) ? 'disease' : 'pest');
    
    if (!isset($data[$type])) $data[$type] = [];
    if (!isset($data[$type][$dbKey])) $data[$type][$dbKey] = [];
    
    $data[$type][$dbKey][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Knowledge Modifier History</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .version-card {
            transition: all 0.2s;
        }
        .version-card:hover {
            transform: translateY(-2px);
        }
        .type-badge {
            font-size: 0.85rem;
            padding: 4px 10px;
        }
    </style>
</head>
<body data-bs-theme="dark">

    <!-- Navigation -->
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

    <!-- Header -->
    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left d-flex align-items-center gap-4">
                <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h5 class="m-b-10">Knowledge Update History</h5>
                    <p class="text-muted">Track every change made to the shared knowledge base</p>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <?php if (empty($data)): ?>
                        <div class="card">
                            <div class="card-body text-center py-12 text-muted">
                                No knowledge records found in the database.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($data as $type => $items): ?>
                            <div class="card mb-5">
                                <div class="card-header">
                                    <h5 class="mb-0"><?= htmlspecialchars(ucfirst($type)) ?>s</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($items as $jsonKey => $versions): 
                                        $original = $originalData[$jsonKey] ?? [];
                                    ?>
                                    <div class="border border-secondary rounded-3 p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($jsonKey) ?></h6>
                                            <span class="badge <?= $type === 'disease' ? 'bg-success' : 'bg-danger' ?> type-badge">
                                                <?= strtoupper($type) ?>
                                            </span>
                                        </div>
                                        <div class="row g-4">
                                            <!-- Original -->
                                            <div class="col-md-5">
                                                <div class="border border-secondary bg-opacity-10 p-4 rounded-3 h-100">
                                                    <h6 class="text-warning mb-3"><i class="fa-solid fa-history"></i> Original Version</h6>
                                                    <div class="small text-muted">
                                                        <strong>Treatments:</strong> <?= nl2br(htmlspecialchars($original['treatments'] ?? '—')) ?><br><br>
                                                        <strong>Causes:</strong> <?= nl2br(htmlspecialchars($original['causes'] ?? '—')) ?><br><br>
                                                        <strong>Nutrient Deficiency:</strong> <?= nl2br(htmlspecialchars($original['nutrient_deficiency'] ?? '—')) ?><br><br>
                                                        <strong>Grain Damage:</strong> <?= nl2br(htmlspecialchars($original['grain_damage'] ?? '—')) ?><br><br>
                                                        <strong>Prevention:</strong> <?= nl2br(htmlspecialchars($original['prevention'] ?? '—')) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- History -->
                                            <div class="col-md-7">
                                                <?php foreach ($versions as $v): ?>
                                                <div class="version-card border border-success p-4 rounded-3 mb-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <h6 class="text-success mb-0">
                                                            Updated • <?= htmlspecialchars($v['updated_at'] ?? 'Unknown') ?>
                                                        </h6>
                                                        <span class="badge bg-soft-primary">
                                                            <?= htmlspecialchars($v['editor_email'] ?? 'Unknown') ?> 
                                                            (<?= htmlspecialchars($v['editor_role'] ?? 'Unknown') ?>)
                                                        </span>
                                                    </div>
                                                    <div class="small">
                                                        <strong>Treatments:</strong> <?= nl2br(htmlspecialchars($v['treatments'] ?? '—')) ?><br><br>
                                                        <strong>Causes:</strong> <?= nl2br(htmlspecialchars($v['causes'] ?? '—')) ?><br><br>
                                                        <strong>Nutrient Deficiency:</strong> <?= nl2br(htmlspecialchars($v['nutrient_deficiency'] ?? '—')) ?><br><br>
                                                        <strong>Grain Damage:</strong> <?= nl2br(htmlspecialchars($v['grain_damage'] ?? '—')) ?><br><br>
                                                        <strong>Prevention:</strong> <?= nl2br(htmlspecialchars($v['prevention'] ?? '—')) ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>