<?php
// knowledge_editor.php - UPDATED (Dropdown hides already saved items)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . '/database/database.php';

$success = '';
$error = '';

// Disease & Pest lists
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

// Get already saved keys
$savedKeys = [];
$result = $conn->query("SELECT disease FROM treatment_records WHERE user_id IS NULL");
while ($row = $result->fetch_assoc()) {
    $savedKeys[] = strtolower($row['disease']);
}

// ====================== HANDLE SAVE ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_knowledge'])) {
    $disease_key = strtolower(trim($_POST['disease_key'] ?? ''));
    $type        = $_POST['type'] ?? 'disease';

    if (empty($disease_key)) {
        $error = "Please select a disease or pest.";
    } else {
        $treatments = trim($_POST['treatments'] ?? '');
        $causes     = trim($_POST['causes'] ?? '');
        $prevention = trim($_POST['prevention'] ?? '');

        if ($type === 'disease') {
            $nutrient = trim($_POST['nutrient_deficiency'] ?? '');
            $grain    = trim($_POST['grain_damage'] ?? '');

            $stmt = $conn->prepare("INSERT INTO treatment_records 
                (disease, treatments, causes, nutrient_deficiency, grain_damage, prevention, type, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'disease', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                treatments=VALUES(treatments), causes=VALUES(causes), 
                nutrient_deficiency=VALUES(nutrient_deficiency), 
                grain_damage=VALUES(grain_damage), prevention=VALUES(prevention),
                updated_by=VALUES(updated_by), updated_at=NOW()");

            $admin_id = $_SESSION['user_id'];
            $stmt->bind_param("ssssssi", $disease_key, $treatments, $causes, $nutrient, $grain, $prevention, $admin_id);
        } else {
            $damage  = trim($_POST['damage_symptoms'] ?? '');
            $natural = trim($_POST['natural_enemies'] ?? '');

            $stmt = $conn->prepare("INSERT INTO treatment_records 
                (disease, treatments, causes, nutrient_deficiency, grain_damage, prevention, type, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pest', ?, NOW())
                ON DUPLICATE KEY UPDATE 
                treatments=VALUES(treatments), causes=VALUES(causes), 
                nutrient_deficiency=VALUES(nutrient_deficiency), 
                grain_damage=VALUES(grain_damage), prevention=VALUES(prevention),
                updated_by=VALUES(updated_by), updated_at=NOW()");

            $admin_id = $_SESSION['user_id'];
            $stmt->bind_param("ssssssi", $disease_key, $treatments, $causes, $natural, $damage, $prevention, $admin_id);
        }

        if ($stmt->execute()) {
            $success = "✅ Knowledge for <strong>" . htmlspecialchars($disease_key) . "</strong> has been successfully saved/updated.";
        } else {
            $error = "Database error: " . $stmt->error;
        }
        $stmt->close();

        // Refresh saved keys after save
        $savedKeys = [];
        $result = $conn->query("SELECT disease FROM treatment_records WHERE user_id IS NULL");
        while ($row = $result->fetch_assoc()) {
            $savedKeys[] = strtolower($row['disease']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Knowledge Editor</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            <div class="header-right ms-auto">
                <div class="d-flex align-items-center gap-3">
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
                    <h5 class="m-b-10">Knowledge Editor</h5>
                    <p class="text-muted">Add or update rice disease and pest knowledge</p>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Disease Form -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="fa-solid fa-seedling text-success"></i> Disease Knowledge</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="type" value="disease">
                                        <div class="mb-3">
                                            <label class="form-label">Select Disease</label>
                                            <select name="disease_key" class="form-control" required>
                                                <option value="">-- Choose Disease --</option>
                                                <?php foreach ($diseaseNames as $key => $name): 
                                                    if (!in_array($key, $savedKeys)): ?>
                                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                                                <?php endif; endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Recommended Treatments</label>
                                            <textarea name="treatments" rows="4" class="form-control" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Common Causes</label>
                                            <textarea name="causes" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Nutrient Deficiency</label>
                                            <textarea name="nutrient_deficiency" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Grain Damage</label>
                                            <textarea name="grain_damage" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Prevention Tips</label>
                                            <textarea name="prevention" rows="3" class="form-control"></textarea>
                                        </div>
                                        <button type="submit" name="save_knowledge" class="btn btn-success w-100">Save Disease Knowledge</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Pest Form -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="fa-solid fa-bug text-danger"></i> Pest Knowledge</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="type" value="pest">
                                        <div class="mb-3">
                                            <label class="form-label">Select Pest</label>
                                            <select name="disease_key" class="form-control" required>
                                                <option value="">-- Choose Pest --</option>
                                                <?php foreach ($pestNames as $key => $name): 
                                                    if (!in_array($key, $savedKeys)): ?>
                                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                                                <?php endif; endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Recommended Treatments</label>
                                            <textarea name="treatments" rows="4" class="form-control" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Common Causes</label>
                                            <textarea name="causes" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Damage Symptoms</label>
                                            <textarea name="damage_symptoms" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Prevention</label>
                                            <textarea name="prevention" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Natural Enemies</label>
                                            <textarea name="natural_enemies" rows="3" class="form-control"></textarea>
                                        </div>
                                        <button type="submit" name="save_knowledge" class="btn btn-danger w-100">Save Pest Knowledge</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>