<?php
// technician_history_access.php - UPDATED (Full Knowledge Edit + History Tracking + Aligned Design)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'technician') {
    header("Location: technician_login.php");
    exit;
}

require_once 'database/database.php';

// ====================== HANDLE KNOWLEDGE UPDATE ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_knowledge'])) {
    $key        = strtolower(trim($_POST['disease_key']));
    $treatments = trim($_POST['treatments'] ?? '');
    $causes     = trim($_POST['causes'] ?? '');
    $nutrient   = trim($_POST['nutrient_deficiency'] ?? '');
    $grain      = trim($_POST['grain_damage'] ?? '');
    $prevention = trim($_POST['prevention'] ?? '');

    $technician_id = $_SESSION['user_id'];

    // Update the global knowledge record (user_id IS NULL)
    $stmt = $conn->prepare("UPDATE treatment_records SET 
        treatments = ?, 
        causes = ?, 
        nutrient_deficiency = ?, 
        grain_damage = ?, 
        prevention = ?, 
        updated_by = ?, 
        updated_at = NOW()
        WHERE disease = ? AND user_id IS NULL");

    $stmt->bind_param("sssssis", $treatments, $causes, $nutrient, $grain, $prevention, $technician_id, $key);
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: technician_history_access.php?updated=1");
        exit;
    } else {
        error_log("Knowledge update failed for key '$key': " . $stmt->error);
        $update_error = "Update failed. Please try again.";
    }
    $stmt->close();
}

// ====================== DATA ======================
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

// Load global knowledge (for display and editing)
$knowledgeBase = [];
$result = mysqli_query($conn, "SELECT * FROM treatment_records WHERE user_id IS NULL");
while ($row = mysqli_fetch_assoc($result)) {
    $key = strtolower(trim($row['disease']));
    $knowledgeBase[$key] = $row;
}

// Fetch all farmers and their detections
$allUsersData = [];
$userResult = mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role = 'farmer' ORDER BY full_name ASC");

while ($user = mysqli_fetch_assoc($userResult)) {
    $user_id = $user['id'];
    $user_name = $user['full_name'] ?: $user['email'];

    $detectionData = [];
    $stmt = $conn->prepare("SELECT class_key, image_path FROM user_detections WHERE user_id = ? ORDER BY class_key, created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $currentKey = '';
    $images = [];
    while ($row = $res->fetch_assoc()) {
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

    $allUsersData[] = [
        'user_id' => $user_id,
        'user_name' => $user_name,
        'email' => $user['email'],
        'detectionData' => $detectionData
    ];
}
?>

<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • All Farmers Records</title>
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .dropdown-menu { 
            display:none; 
            position:absolute; 
            right:0; 
            top:45px; 
            background:#1f2937; 
            border:1px solid #374151; 
            border-radius:12px; 
            box-shadow:0 10px 25px rgba(0,0,0,0.3); 
            z-index:99999; 
            width:180px; 
            padding:6px 0; 
        }
        .dropdown-menu.active { display:block; }
        .modal { 
            display:none; 
            position:fixed; 
            top:0; left:0; 
            width:100%; height:100%; 
            background:rgba(0,0,0,0.85); 
            align-items:center; 
            justify-content:center; 
            z-index:10000; 
        }
        .image-gallery img {
            transition: all 0.2s;
        }
        .image-gallery img:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3);
        }
        .knowledge-section {
            background: #1f2937;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
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
                
                   <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                            <i class="feather-moon"></i>
                        </a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                            <i class="feather-sun"></i>
                        </a>
                    </div>

                <!-- Notifications -->
                

               
            </div>
        </div>
    </div>
</header>

    <!-- Main Content -->
    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h5 class="m-b-10">Farmers Records & Knowledge</h5>
                    <p class="text-muted">View farmer detections and manage shared knowledge base</p>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <?php if (isset($update_error)): ?>
                        <div class="alert alert-danger mb-4"><?= htmlspecialchars($update_error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            ✅ Knowledge updated successfully! Changes are now reflected across the system.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($allUsersData as $userData): ?>
                        <div class="card mb-5 border-0 shadow">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-emerald-600 text-white" style="width:48px;height:48px;font-size:28px;">
                                        👤
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold"><?= htmlspecialchars($userData['user_name']) ?></h4>
                                        <small class="text-success"><?= htmlspecialchars($userData['email']) ?></small>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <?php foreach ($userData['detectionData'] as $det): 
                                        $kb = $knowledgeBase[$det['class_key']] ?? [];
                                        $isPest = $det['is_pest'];
                                        $displayName = htmlspecialchars($det['class_name']);
                                    ?>
                                    <div class="col-lg-6">
                                        <div class="card border-0 shadow hover-shadow h-100 position-relative" style="background:#111827;">
                                            <!-- Three Dot Menu -->
                                            <div class="position-absolute top-3 end-3" style="z-index:10;">
                                                <button onclick="toggleDropdown(this)" class="btn btn-link text-muted p-2" style="line-height:1;">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="#" onclick="openEditModal('<?= htmlspecialchars($det['class_key']) ?>', '<?= $displayName ?>')">
                                                        <i class="fas fa-pen me-2"></i> Edit Knowledge
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="printCard(this)">
                                                        <i class="fas fa-print me-2"></i> Print Card
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- Card Header -->
                                            <div class="card-body p-4">
                                                <div class="d-flex align-items-center gap-3 mb-4">
                                                    <div class="text-4xl"><?= $isPest ? '🐛' : '🌾' ?></div>
                                                    <div>
                                                        <h5 class="mb-1 fw-bold"><?= $displayName ?></h5>
                                                        <span class="badge <?= $isPest ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $isPest ? 'PEST' : 'DISEASE' ?></span>
                                                    </div>
                                                </div>

                                                <!-- Full Knowledge Display (Aligned with other pages) -->
                                                <div class="knowledge-section">
                                                    <strong class="text-success d-block mb-2">Recommended Treatments</strong>
                                                    <div class="small"><?= nl2br(htmlspecialchars($kb['treatments'] ?? 'No data available yet.')) ?></div>
                                                </div>

                                                <div class="knowledge-section">
                                                    <strong class="d-block mb-2">Common Causes / Biology</strong>
                                                    <div class="small"><?= nl2br(htmlspecialchars($kb['causes'] ?? '—')) ?></div>
                                                </div>

                                                <?php if (!$isPest): ?>
                                                <div class="knowledge-section">
                                                    <strong class="text-warning d-block mb-2">Nutrient Deficiency</strong>
                                                    <div class="small"><?= nl2br(htmlspecialchars($kb['nutrient_deficiency'] ?? '—')) ?></div>
                                                </div>
                                                <?php endif; ?>

                                                <div class="knowledge-section">
                                                    <strong class="d-block mb-2"><?= $isPest ? 'Damage Symptoms' : 'Grain / Paddy Damage' ?></strong>
                                                    <div class="small"><?= nl2br(htmlspecialchars($kb['grain_damage'] ?? '—')) ?></div>
                                                </div>

                                                <?php if ($isPest): ?>
                                                <div class="knowledge-section">
                                                    <strong class="text-purple d-block mb-2">Natural Enemies</strong>
                                                    <div class="small"><?= nl2br(htmlspecialchars($kb['nutrient_deficiency'] ?? '—')) ?></div>
                                                </div>
                                                <?php endif; ?>

                                                <div class="knowledge-section">
                                                    <strong class="text-success d-block mb-2">Prevention Tips</strong>
                                                    <div class="small"><?= nl2br(htmlspecialchars($kb['prevention'] ?? '—')) ?></div>
                                                </div>

                                                <!-- Clickable Images -->
                                                <?php if (!empty($det['images'])): ?>
                                                <div class="mt-4">
                                                    <p class="text-muted small mb-3">Uploaded Photos (<?= count($det['images']) ?>)</p>
                                                    <div class="image-gallery d-flex flex-wrap gap-2">
                                                        <?php foreach ($det['images'] as $img): ?>
                                                            <img src="<?= htmlspecialchars($img) ?>" 
                                                                 class="img-thumbnail rounded-3 cursor-pointer border-2 border-light"
                                                                 style="width: 100px; height: 100px; object-fit: cover;"
                                                                 onclick="showImageModal('<?= htmlspecialchars(addslashes($img)) ?>')"
                                                                 title="Click to view full size">
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- VIEW-ONLY IMAGE MODAL -->
    <div id="imageModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 d-flex align-items-center justify-content-center">
                    <img id="modalImageBig" src="" class="w-100" style="max-height: 85vh; object-fit: contain;" alt="Full size photo">
                </div>
            </div>
        </div>
    </div>

    <!-- FLOATING EDIT MODAL -->
    <div id="editModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark border-0 text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="modalTitle">Edit Knowledge Base</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeEditModal()"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editForm">
                        <input type="hidden" name="update_knowledge" value="1">
                        <input type="hidden" name="disease_key" id="modal_key">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Recommended Treatments</label>
                            <textarea name="treatments" id="edit_treatments" rows="4" class="form-control bg-dark text-light border-secondary"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Common Causes</label>
                            <textarea name="causes" id="edit_causes" rows="4" class="form-control bg-dark text-light border-secondary"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nutrient Deficiency / Natural Enemies</label>
                            <textarea name="nutrient_deficiency" id="edit_nutrient" rows="3" class="form-control bg-dark text-light border-secondary"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Grain Damage / Symptoms</label>
                            <textarea name="grain_damage" id="edit_grain" rows="3" class="form-control bg-dark text-light border-secondary"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Prevention Tips</label>
                            <textarea name="prevention" id="edit_prevention" rows="4" class="form-control bg-dark text-light border-secondary"></textarea>
                        </div>

                        <div class="d-flex gap-3 pt-3 border-top border-secondary">
                            <button type="button" onclick="closeEditModal()" class="btn btn-secondary flex-fill">Cancel</button>
                            <button type="submit" class="btn btn-success flex-fill">Save Changes to Knowledge Base</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    // Image Modal
    function showImageModal(src) {
        document.getElementById('modalImageBig').src = src;
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        modal.show();
    }

    // Dropdown (Bootstrap 5 compatible)
    function toggleDropdown(btn) {
        const menu = btn.parentElement.querySelector('.dropdown-menu');
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
        menu.classList.toggle('active');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.position-absolute')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('active'));
        }
    });

    // Edit Modal
    let currentEditKey = '';
    function openEditModal(key, name) {
        currentEditKey = key;
        document.getElementById('modal_key').value = key;
        document.getElementById('modalTitle').textContent = 'Edit Knowledge: ' + name;

        const kb = <?= json_encode($knowledgeBase) ?>;
        const data = kb[key] || {};

        document.getElementById('edit_treatments').value = data.treatments || '';
        document.getElementById('edit_causes').value = data.causes || '';
        document.getElementById('edit_nutrient').value = data.nutrient_deficiency || '';
        document.getElementById('edit_grain').value = data.grain_damage || '';
        document.getElementById('edit_prevention').value = data.prevention || '';

        const modal = new bootstrap.Modal(document.getElementById('editModal'));
        modal.show();
    }

    function closeEditModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
        if (modal) modal.hide();
    }

    // Print Card
    function printCard(btn) {
        const card = btn.closest('.card');
        if (!card) return;
        const originalContent = document.body.innerHTML;
        document.body.innerHTML = `
            <div style="padding:20px;font-family:Arial,sans-serif;">${card.outerHTML}</div>
        `;
        window.print();
        document.body.innerHTML = originalContent;
        location.reload(); // restore scripts and events
    }

    // Form submit confirmation (optional)
    document.getElementById('editForm').addEventListener('submit', function() {
        if (!confirm('Save these changes to the shared knowledge base? This will affect all users.')) {
            return false;
        }
    });

    // Close modals cleanly
    document.getElementById('imageModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalImageBig').src = '';
    });
    </script>
</body>
</html>