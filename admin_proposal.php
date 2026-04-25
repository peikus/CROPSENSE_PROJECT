<?php
// admin_proposal.php - FINAL (Internal proposals hidden from admin)
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit;
}
require_once 'database/database.php';

// Real delete from database
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM proposals WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_proposal.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Admin Proposals</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
<style>
    /* FIXED: Dark/Light theme compatible colors */
    :root {
        --text-primary: #ffffff;
        --text-secondary: #a1a1aa;
        --text-muted: #71717a;
        --bg-hover: rgba(255,255,255,0.1);
        --border-color: rgba(255,255,255,0.1);
    }
    
    [data-bs-theme="dark"] {
        --text-primary: #ffffff;
        --text-secondary: #a1a1aa;
        --text-muted: #71717a;
        --bg-hover: rgba(255,255,255,0.1);
        --border-color: rgba(255,255,255,0.2);
    }
    
    [data-bs-theme="light"] {
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --text-muted: #9ca3af;
        --bg-hover: rgba(0,0,0,0.05);
        --border-color: rgba(0,0,0,0.1);
    }
    
    /* Table hover */
    .table-row:hover { 
        background-color: var(--bg-hover) !important; 
    }
    
    .table-container { overflow: visible !important; }
    
    /* FIXED: Clean action buttons - NO BORDER */
    .action-btn {
        border: none !important;
        background: transparent !important;
        color: var(--text-secondary) !important;
        padding: 8px !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
        box-shadow: none !important;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .action-btn:hover {
        background-color: var(--bg-hover) !important;
        color: #22c55e !important;
        transform: scale(1.05);
    }
    
    /* FIXED DROPDOWN - Perfect positioning */
    .dropdown-container {
        position: relative;
        display: inline-block;
        z-index: 10000;
    }
    
    .dropdown-toggle {
        border: none !important;
        background: transparent !important;
        cursor: pointer;
        padding: 8px !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
        color: var(--text-secondary) !important;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dropdown-toggle:hover {
        background-color: var(--bg-hover) !important;
        color: #22c55e !important;
        transform: scale(1.05);
    }
    
    /* ✅ FIXED: Remove ALL Bootstrap dropdown arrows/triangles */
    .dropdown-toggle::after {
        display: none !important;
        border: none !important;
        content: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    
    /* Extra safety for all possible arrow selectors */
    .dropdown-toggle.dropdown-toggle::after,
    .btn-group .dropdown-toggle::after,
    .dropup .dropdown-toggle::after,
    .dropend .dropdown-toggle::after,
    .dropstart .dropdown-toggle::after {
        display: none !important;
    }
    
    .dropdown-menu {
        position: fixed !important;
        min-width: 200px !important;
        background: var(--bs-body-bg) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 12px !important;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3) !important;
        z-index: 99999 !important;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px) scale(0.95);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        list-style: none;
        padding: 8px 0 !important;
        margin: 0 !important;
        font-size: 14px;
    }
    
    .dropdown-menu.show {
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) scale(1) !important;
    }
    
    .dropdown-item {
        display: flex !important;
        align-items: center;
        width: 100% !important;
        padding: 12px 16px !important;
        color: var(--bs-body-color) !important;
        text-decoration: none !important;
        font-weight: 500 !important;
        border: none !important;
        background: none !important;
        text-align: left !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        white-space: nowrap;
        border-radius: 8px !important;
        margin: 2px 8px !important;
    }
    
    .dropdown-item:hover {
        background-color: var(--bg-hover) !important;
        color: var(--text-primary) !important;
    }
    
    .dropdown-item.danger {
        color: #f87171 !important;
    }
    
    .dropdown-item.danger:hover {
        background-color: rgba(248, 113, 113, 0.1) !important;
        color: #f87171 !important;
    }
    
    .dropdown-item i {
        width: 18px;
        margin-right: 12px;
        font-size: 14px;
    }
    
    /* Status badges - FIXED for both themes */
    .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
    }
    
    .status-sent { 
        background-color: rgba(34,197,94,.2); 
        color: #22c55e; 
        border: 1px solid rgba(34,197,94,.3);
    }
    .status-draft { 
        background-color: rgba(156,163,175,.2); 
        color: #9ca3af; 
        border: 1px solid rgba(156,163,175,.3);
    }
    .status-open { 
        background-color: rgba(59,130,246,.2); 
        color: #3b82f6; 
        border: 1px solid rgba(59,130,246,.3);
    }
    .status-revise { 
        background-color: rgba(251,191,36,.2); 
        color: #fbbf24; 
        border: 1px solid rgba(251,191,36,.3);
    }
    
    /* Proposal ID badge */
    .proposal-id {
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 6px;
    }
    
    /* Responsive text */
    .subject-text {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style></head>
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
                                <li class="nxl-item"><a class="nxl-link" href="create_technician.php">Create Technicians</a></li>
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
    <div class="nxl-container">
        <div class="page-main-header">
            <div class="d-flex justify-content-between">
                <div class="page-title-section">
                    <h4 class="m-0 text-white"><i class="fas fa-file-invoice me-2"></i>Admin Proposals</h4>
                </div>
                <div class="d-flex align-items-center">
                    <a href="admin_create_proposal.php" class="btn btn-primary btn-rounded">
                        <i class="fas fa-plus me-2"></i>New Proposal
                    </a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body table-container">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="">
                                        <tr>
                                            <th>PROPOSAL</th>
                                            <th>LEAD</th>
                                            <th>SUBJECT</th>
                                            <th>DATE</th>
                                            <th>STATUS</th>
                                            <th class="text-center">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="proposalsBody">
    <?php
    // IMPORTANT: Hide Internal proposals from admin
$sql = "SELECT p.*, u.full_name 
        FROM proposals p 
        LEFT JOIN users u ON p.technician_id = u.id 
        WHERE p.visibility != 'Internal' 
           OR (p.visibility = 'Internal' AND p.technician_id = ?)
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$hasProposals = false;
while ($row = $result->fetch_assoc()):
    $hasProposals = true;
        
        // ✅ FIXED: Handle NULL/empty status and exact matches
       $statusRaw = $row['status'] ?? 'Draft';
$statusClean = strtoupper(trim($statusRaw));

// Normalize common variations
switch ($statusClean) {
    case 'SENT':
        $statusClass = 'status-sent';
        $statusLabel = 'Sent';
        break;

    case 'DRAFT':
        $statusClass = 'status-draft';
        $statusLabel = 'Draft';
        break;

    case 'OPEN':
        $statusClass = 'status-open';
        $statusLabel = 'Open';
        break;

    case 'REVISE':
    case 'REVISION':
        $statusClass = 'status-revise';
        $statusLabel = 'Revise';
        break;

    default:
        $statusClass = 'status-draft';
        $statusLabel = ucfirst(strtolower($statusRaw));
}
    ?>
    <tr class="table-row" data-id="<?= $row['id'] ?>">
        <td>
            <span class="proposal-id bg-primary text-white">#<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></span>
        </td>
        <td>
            <span style="color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($row['lead'] ?? '—') ?></span>
        </td>
        <td>
            <span class="subject-text" style="color: var(--text-primary);"><?= htmlspecialchars($row['subject'] ?? '') ?></span>
        </td>
        <td>
            <span style="color: var(--text-muted); font-size: 0.875rem;"><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></span>
        </td>
<td>
    <span class="status-badge <?= $statusClass ?>">
        <?= htmlspecialchars($statusLabel) ?>
    </span>
</td>        <td class="text-center">
            <div class="d-flex justify-content-center align-items-center gap-2">
                <!-- View button -->
                <a href="admin_view_proposal.php?id=<?= $row['id'] ?>" 
                   class="action-btn" title="View proposal">
                    <i class="fas fa-eye fs-5"></i>
                </a>
                
                <!-- Three dots -->
                <div class="dropdown-container" data-proposal-id="<?= $row['id'] ?>">
                    <button class="dropdown-toggle action-btn" title="More actions">
                        <i class="fas fa-ellipsis-v fs-5"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="admin_edit_proposal.php?id=<?= $row['id'] ?>" class="dropdown-item">
                                <i class="fas fa-edit"></i>Edit Proposal
                            </a>
                        </li>
                        <li>
                            <button class="dropdown-item print-btn" data-id="<?= $row['id'] ?>">
                                <i class="fas fa-print"></i>Print
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item danger delete-btn" data-id="<?= $row['id'] ?>">
                                <i class="fas fa-trash"></i>Delete
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>

    <?php if (!$hasProposals): ?>
    <tr>
        <td colspan="6" class="text-center py-5">
            <div class="py-5">
                <i class="fas fa-file-invoice text-muted mb-4" style="font-size: 4rem; opacity: 0.5;"></i>
                <h5 style="color: var(--text-primary);">No proposals yet</h5>
                <p class="mb-0" style="color: var(--text-muted);">Create your first proposal to get started</p>
            </div>
        </td>
    </tr>
    <?php endif; ?>
</tbody>




                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    // FIXED: Bulletproof dropdown functionality
    let currentDropdown = null;

    document.addEventListener('DOMContentLoaded', function() {
        function positionDropdown(container) {
            const toggleBtn = container.querySelector('.dropdown-toggle');
            const menu = container.querySelector('.dropdown-menu');
            if (!toggleBtn || !menu) return;
            
            const rect = toggleBtn.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            
            // Position below button
            menu.style.position = 'fixed';
            menu.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            menu.style.left = (rect.left + window.scrollX) + 'px';
            menu.style.width = '200px';
        }
        
        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            currentDropdown = null;
        }
        
        function toggleDropdown(container) {
            const menu = container.querySelector('.dropdown-menu');
            const isActive = menu.classList.contains('show');
            
            closeAllDropdowns();
            
            if (!isActive) {
                positionDropdown(container);
                menu.classList.add('show');
                currentDropdown = container;
            }
        }
        
        // FIXED: Click handler - WORKS PERFECTLY
        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('.dropdown-toggle');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                const container = toggleBtn.closest('.dropdown-container');
                if (container) {
                    toggleDropdown(container);
                }
                return;
            }
            
            // Close if clicking outside
            if (!e.target.closest('.dropdown-container') && !e.target.closest('.dropdown-menu')) {
                closeAllDropdowns();
            }
        });
        
        // Print & Delete handlers
        document.addEventListener('click', function(e) {
            if (e.target.matches('.print-btn, .print-btn *')) {
                e.stopPropagation();
                const id = e.target.closest('.print-btn').dataset.id;
                if (confirm(`Print proposal #${id}?`)) {
                    window.open(`print_proposal.php?id=${id}`, '_blank');
                }
                closeAllDropdowns();
            }
            
            if (e.target.matches('.delete-btn, .delete-btn *')) {
                e.stopPropagation();
                const id = e.target.closest('.delete-btn').dataset.id;
                if (confirm(`Delete proposal #${id} permanently?\nThis action cannot be undone.`)) {
                    window.location.href = `admin_proposal.php?delete=${id}`;
                }
                closeAllDropdowns();
            }
        });
        
        // Close on scroll/resize/Escape
        window.addEventListener('scroll', closeAllDropdowns);
        window.addEventListener('resize', closeAllDropdowns);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });
    });
    </script>
</body>
</html>