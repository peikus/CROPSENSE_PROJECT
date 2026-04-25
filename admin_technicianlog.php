<?php
// admin_technicianlog.php - Technicians Management (Same as farmerlog)
ob_start();
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . '/database/database.php';

$msg = '';

// ====================== HANDLE ACTIONS ======================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = ($_GET['action'] === 'approve') ? 'approved' : 'declined';
    $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=? AND role='technician'");
    $stmt->bind_param("si", $st, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_technicianlog.php?msg=" . urlencode($st === 'approved' ? 'Technician approved successfully!' : 'Technician request declined.'));
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='technician'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_technicianlog.php?msg=Technician deleted successfully.");
    exit;
}

if (isset($_POST['update_user'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $st = $_POST['status'] ?? 'pending';
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, status=? WHERE id=? AND role='technician'");
    $stmt->bind_param("sssi", $name, $email, $st, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_technicianlog.php?msg=Technician updated successfully.");
    exit;
}

if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Load only Technicians
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'technician' ORDER BY full_name");
$stmt->execute();
$technicians = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">  <!-- Keep dark as default, or remove if you have a toggle -->
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Manage Technicians</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .actions-column {
            width: 80px;
            text-align: right;
            position: relative;
        }
        
        /* Custom Dropdown - Improved for Light/Dark mode */
        .custom-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-toggle-custom {
            background: none;
            border: none;
            padding: 8px;
            color: #adb5bd;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .dropdown-toggle-custom:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .dropdown-menu-custom {
            position: absolute !important;
            top: 100% !important;
            right: 0 !important;
            z-index: 9999 !important;
            border-radius: 8px !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3) !important;
            min-width: 180px !important;
            padding: 8px 0 !important;
            margin: 0 !important;
        }
        
        /* Automatic Light/Dark support using Bootstrap variables */
        [data-bs-theme="dark"] .dropdown-menu-custom {
            background: #1f2937 !important;
            border: 1px solid #374151 !important;
        }
        
        [data-bs-theme="light"] .dropdown-menu-custom {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }
        
        .dropdown-item-custom {
            padding: 12px 16px !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            text-decoration: none !important;
            border: none !important;
            background: none !important;
            width: 100% !important;
            text-align: left !important;
            font-size: 14px !important;
            transition: background 0.2s ease !important;
        }
        
        [data-bs-theme="dark"] .dropdown-item-custom {
            color: #e2e8f0 !important;
        }
        
        [data-bs-theme="light"] .dropdown-item-custom {
            color: #212529 !important;
        }
        
        .dropdown-item-custom:hover {
            background: var(--bs-primary-bg-subtle) !important;
        }
        
        .dropdown-item-custom.danger {
            color: #f87171 !important;
        }
        
        .dropdown-item-custom.success {
            color: #10b981 !important;
        }
        
        .table td, .table th {
            vertical-align: middle;
            position: relative;
            overflow: visible !important;
        }
        
        .table-responsive {
            overflow: visible !important;
        }
        
        /* Hide default Bootstrap dropdowns to prevent conflicts */
        .dropdown-menu {
            display: none !important;
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
                    <h5 class="m-b-10">Manage Technicians</h5>
                    <p class="text-muted mb-0">Approve, edit, and manage technician accounts</p>
                </div>
            </div>

            <div class="main-content">
                <?php if ($msg): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th class="text-end" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($technicians)): ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">No technicians found.</td></tr>
                                    <?php else: foreach ($technicians as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <span class="badge <?= $row['status']=='approved' ? 'bg-success' : ($row['status']=='declined' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= ucfirst($row['status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        <td class="text-end">
                                            <div class="custom-dropdown" data-user-id="<?= $row['id'] ?>">
                                                <button class="dropdown-toggle-custom" onclick="toggleDropdown(event, '<?= $row['id'] ?>')">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu-custom" id="dropdown-<?= $row['id'] ?>" style="display: none;">
                                                    <a class="dropdown-item-custom text-primary" href="javascript:void(0)" 
                                                       onclick="editUser(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['full_name'])) ?>', '<?= addslashes(htmlspecialchars($row['email'])) ?>', '<?= $row['status'] ?? 'pending' ?>')">
                                                        <i class="fa-solid fa-pen"></i> Edit
                                                    </a>
                                                    <?php if ($row['status'] == 'pending'): ?>
                                                    <a class="dropdown-item-custom success" href="?action=approve&id=<?= $row['id'] ?>">
                                                        <i class="fa-solid fa-check-circle"></i> Approve
                                                    </a>
                                                    <a class="dropdown-item-custom danger" href="?action=decline&id=<?= $row['id'] ?>" 
                                                       onclick="return confirm('Decline this farmer request?')">
                                                        <i class="fa-solid fa-xmark-circle"></i> Decline
                                                    </a>
                                                    <?php endif; ?>
                                                    <a class="dropdown-item-custom danger" href="?delete=<?= $row['id'] ?>" 
                                                       onclick="return confirm('Delete this farmer permanently?')">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit User Modal (same as farmer) -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Technician</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="eid">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" id="ename" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="eemail" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" id="estatus" class="form-select">
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="declined">Declined</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts (same) -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    // Same JavaScript as in admin_farmerlog.php
    let openDropdownId = null;
    
    function toggleDropdown(event, userId) {
        event.stopPropagation();
        
        if (openDropdownId) {
            const prevDropdown = document.getElementById('dropdown-' + openDropdownId);
            if (prevDropdown) prevDropdown.style.display = 'none';
        }
        
        const dropdown = document.getElementById('dropdown-' + userId);
        if (dropdown) {
            if (openDropdownId === userId) {
                dropdown.style.display = 'none';
                openDropdownId = null;
            } else {
                dropdown.style.display = 'block';
                openDropdownId = userId;
            }
        }
    }
    
    function editUser(id, name, email, status) {
        document.getElementById('eid').value = id;
        document.getElementById('ename').value = name;
        document.getElementById('eemail').value = email;
        document.getElementById('estatus').value = status;
        new bootstrap.Modal(document.getElementById('editModal')).show();
        
        if (openDropdownId) {
            const dropdown = document.getElementById('dropdown-' + openDropdownId);
            if (dropdown) dropdown.style.display = 'none';
            openDropdownId = null;
        }
    }
    
    document.addEventListener('click', function(event) {
        if (openDropdownId) {
            const dropdown = document.getElementById('dropdown-' + openDropdownId);
            const customDropdown = event.target.closest('.custom-dropdown');
            if (!customDropdown || !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
                openDropdownId = null;
            }
        }
    });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>