<?php
// knowledge_management.php - FINAL FIXED (No box around three dots)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_signup.php");
    exit;
}
require_once __DIR__ . '/database/database.php';

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

// Load saved knowledge
$savedData = [];
$result = mysqli_query($conn, "SELECT * FROM treatment_records WHERE user_id IS NULL ORDER BY type, disease");
while ($row = mysqli_fetch_assoc($result)) {
    $savedData[strtolower($row['disease'])] = $row;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_disease'])) {
    $key = mysqli_real_escape_string($conn, $_POST['delete_disease']);
    mysqli_query($conn, "DELETE FROM treatment_records WHERE disease = '$key' AND user_id IS NULL");
    header("Location: knowledge_management.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Knowledge Management</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .knowledge-card {
            border: none;
            border-radius: 12px;
        }
        .disease-header { color: #10b981; }
        .pest-header { color: #ef4444; }
        .scroll-container {
            max-height: 680px;
            overflow-y: auto;
        }
        .item-card {
            transition: all 0.3s ease;
            position: relative;
        }
        .item-card:hover {
            transform: translateY(-4px);
        }
        .dropdown-container {
            position: absolute;
            top: 18px;
            right: 18px;
            z-index: 10;
        }
        .dropdown-btn {
            background: transparent;
            border: none;
            color: inherit;
            font-size: 1.4rem;
            padding: 4px 8px;
            cursor: pointer;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 38px;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.4);
            z-index: 99999;
            width: 160px;
            padding: 6px 0;
        }
        .dropdown-menu.active { display: block; }
        .dropdown-item {
            padding: 10px 16px;
            color: #e2e8f0;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .dropdown-item:hover { 
            background: #374151; 
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
                                <li class="nxl-item"><a class="nxl-link" href="knowledge_modifier.php">Knowledge Modifiert</a></li>
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
                    <h5 class="m-b-10">Knowledge Management</h5>
                    <p class="text-muted">View, update and manage all rice disease & pest data</p>
                </div>
                <div class="page-header-content ms-auto">
                    <a href="knowledge_editor.php" class="btn btn-success">
                        <i class="fa-solid fa-plus me-2"></i> Add New Knowledge
                    </a>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <div class="row g-4">

                        <!-- DISEASES SECTION -->
                        <div class="col-xl-6">
                            <div class="card stretch stretch-full knowledge-card">
                                <div class="card-header d-flex align-items-center gap-3">
                                    <i class="fa-solid fa-seedling text-2xl disease-header"></i>
                                    <h5 class="card-title disease-header mb-0">Rice Diseases</h5>
                                </div>
                                <div class="card-body scroll-container p-0">
                                    <?php 
                                    $hasDisease = false;
                                    foreach ($diseaseNames as $key => $name): 
                                        if (isset($savedData[$key])):
                                            $d = $savedData[$key];
                                            $hasDisease = true;
                                    ?>
                                    <div class="item-card border-bottom p-6 hover:bg-light relative">
                                        <div class="dropdown-container">
                                            <button onclick="toggleDropdown(this)" class="dropdown-btn">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="knowledge_editor.php?edit=<?= urlencode($key) ?>" class="dropdown-item">
                                                    <i class="fa-solid fa-pen mr-2"></i> Update
                                                </a>
                                                <button onclick="deleteItem('<?= htmlspecialchars($key) ?>')" class="dropdown-item text-red-400">
                                                    <i class="fa-solid fa-trash mr-2"></i> Delete
                                                </button>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="fw-semibold text-success"><?= htmlspecialchars($name) ?></h6>
                                        </div>
                                        <div class="row g-3 text-sm">
                                            <div class="col-12">
                                                <strong class="text-success">Treatments:</strong><br>
                                                <?= nl2br(htmlspecialchars($d['treatments'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Causes:</strong><br>
                                                <?= nl2br(htmlspecialchars($d['causes'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Nutrient Deficiency:</strong><br>
                                                <?= nl2br(htmlspecialchars($d['nutrient_deficiency'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Grain Damage:</strong><br>
                                                <?= nl2br(htmlspecialchars($d['grain_damage'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Prevention:</strong><br>
                                                <?= nl2br(htmlspecialchars($d['prevention'] ?? '—')) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; endforeach; ?>

                                    <?php if (!$hasDisease): ?>
                                        <div class="text-center py-12 text-muted">
                                            No disease knowledge saved yet.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- PESTS SECTION -->
                        <div class="col-xl-6">
                            <div class="card stretch stretch-full knowledge-card">
                                <div class="card-header d-flex align-items-center gap-3">
                                    <i class="fa-solid fa-bug text-2xl pest-header"></i>
                                    <h5 class="card-title pest-header mb-0">Pests</h5>
                                </div>
                                <div class="card-body scroll-container p-0">
                                    <?php 
                                    $hasPest = false;
                                    foreach ($pestNames as $key => $name): 
                                        if (isset($savedData[$key])):
                                            $p = $savedData[$key];
                                            $hasPest = true;
                                    ?>
                                    <div class="item-card border-bottom p-6 hover:bg-light relative">
                                        <div class="dropdown-container">
                                            <button onclick="toggleDropdown(this)" class="dropdown-btn">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a href="knowledge_editor.php?edit=<?= urlencode($key) ?>" class="dropdown-item">
                                                    <i class="fa-solid fa-pen mr-2"></i> Update
                                                </a>
                                                <button onclick="deleteItem('<?= htmlspecialchars($key) ?>')" class="dropdown-item text-red-400">
                                                    <i class="fa-solid fa-trash mr-2"></i> Delete
                                                </button>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="fw-semibold text-danger"><?= htmlspecialchars($name) ?></h6>
                                        </div>
                                        <div class="row g-3 text-sm">
                                            <div class="col-12">
                                                <strong class="text-success">Treatments:</strong><br>
                                                <?= nl2br(htmlspecialchars($p['treatments'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Causes / Biology:</strong><br>
                                                <?= nl2br(htmlspecialchars($p['causes'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Damage Symptoms:</strong><br>
                                                <?= nl2br(htmlspecialchars($p['grain_damage'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Natural Enemies:</strong><br>
                                                <?= nl2br(htmlspecialchars($p['nutrient_deficiency'] ?? '—')) ?>
                                            </div>
                                            <div class="col-12">
                                                <strong>Prevention:</strong><br>
                                                <?= nl2br(htmlspecialchars($p['prevention'] ?? '—')) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; endforeach; ?>

                                    <?php if (!$hasPest): ?>
                                        <div class="text-center py-12 text-muted">
                                            No pest knowledge saved yet.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
    function toggleDropdown(btn) {
        const menu = btn.parentElement.querySelector('.dropdown-menu');
        const isActive = menu.classList.contains('active');

        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));

        if (!isActive) {
            menu.classList.add('active');
        }
    }

    function deleteItem(key) {
        if (confirm('Are you sure you want to delete this knowledge entry?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="delete_disease" value="${key}">`;
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
        }
    });
    </script>
</body>
</html>