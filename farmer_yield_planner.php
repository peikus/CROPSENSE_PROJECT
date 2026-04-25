<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_signup.php");
    exit;
}

require_once 'database/database.php';

$user_id = $_SESSION['user_id'];
$result = null;

// ====================== HANDLE SAVE PLAN ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'save_plan') {
    $variety          = $_POST['variety'] ?? '';
    $size             = floatval($_POST['size'] ?? 0);
    $planting_date    = $_POST['planting_date'] ?? '';
    $growth_stage     = $_POST['growth_stage'] ?? '';
    $health           = $_POST['health'] ?? '';
    $pest             = $_POST['pest'] ?? '';
    $water            = $_POST['water'] ?? '';
    $weather          = $_POST['weather'] ?? '';
    $notes            = $_POST['notes'] ?? '';
    $yield_per_hectare = !empty($_POST['yield']) ? floatval($_POST['yield']) : 4000;

    $harvest_date = !empty($planting_date) 
                    ? date('Y-m-d', strtotime($planting_date . ' +120 days')) 
                    : '0000-00-00';

    $total_yield = $size * $yield_per_hectare;

    $risk_score = 0;
    if ($health == "Poor") $risk_score += 2;
    if ($pest == "High") $risk_score += 2;
    if ($water != "Enough") $risk_score += 1;
    if ($weather != "Normal") $risk_score += 1;

    if ($risk_score <= 2) $risk = "Low"; 
    elseif ($risk_score <= 4) $risk = "Medium"; 
    else $risk = "High";

    $stmt = $conn->prepare("INSERT INTO rice_plans 
        (user_id, variety, size, planting_date, growth_stage, harvest_date, 
         total_yield, risk, yield_per_hectare, health, pest, water, weather, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("isdsssdssissss", 
        $user_id, $variety, $size, $planting_date, $growth_stage, $harvest_date,
        $total_yield, $risk, $yield_per_hectare, $health, $pest, $water, $weather, $notes);

    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success]);
    exit;
}

// ====================== CALCULATE PLAN ======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $variety = $_POST['variety'] ?? '';
    $size = floatval($_POST['size'] ?? 0);
    $planting_date = $_POST['planting_date'] ?? '';
    $growth_stage = $_POST['growth_stage'] ?? '';
    $health = $_POST['health'] ?? '';
    $pest = $_POST['pest'] ?? '';
    $water = $_POST['water'] ?? '';
    $weather = $_POST['weather'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $yield_per_hectare = !empty($_POST['yield']) ? floatval($_POST['yield']) : 4000;

    $harvest_date = !empty($planting_date) 
                    ? date('Y-m-d', strtotime($planting_date . ' +120 days')) 
                    : '0000-00-00';

    $total_yield = $size * $yield_per_hectare;

    $risk_score = 0;
    if ($health == "Poor") $risk_score += 2;
    if ($pest == "High") $risk_score += 2;
    if ($water != "Enough") $risk_score += 1;
    if ($weather != "Normal") $risk_score += 1;

    if ($risk_score <= 2) {
        $risk = "Low"; $color = "success";
    } elseif ($risk_score <= 4) {
        $risk = "Medium"; $color = "warning";
    } else {
        $risk = "High"; $color = "danger";
    }

    $suggestions = [];
    if ($pest == "High") $suggestions[] = "Apply pest control immediately.";
    if ($water == "Lacking") $suggestions[] = "Improve irrigation schedule.";
    if ($water == "Flooded") $suggestions[] = "Drain excess water to prevent root rot.";
    if ($health == "Poor") $suggestions[] = "Apply balanced fertilizer.";
    if ($weather == "Dry") $suggestions[] = "Increase watering frequency.";
    if ($weather == "Rainy") $suggestions[] = "Monitor for fungal diseases and improve drainage.";
    if (empty($suggestions)) $suggestions[] = "Maintain current good practices.";

    $result = compact("variety","size","planting_date","growth_stage","health","pest","water","weather","harvest_date","total_yield","risk","color","suggestions","notes","yield_per_hectare");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Rice Planner</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .card {
            border-radius: 15px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 16px;
            
        }
        .result-stat {
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            /* background-color: #0d0d0d !important; */
        }
        .risk-badge {
            font-size: 1.35rem;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 12px;
        }
        .harvest-alert {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* FIX: Make TOTAL EXPECTED YIELD number clearly visible */
       h3 .yield-amount {
            color: #0d0d0d !important;   /* Strong blue - visible on both light and dark backgrounds */
            font-weight: 700;
        }
        
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
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Rice Planner</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="farmer_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Rice Planner</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Input Form -->
                        <div class="col-lg-5">
                            <div class="card">
                                <div class="card-header py-3">
                                    <h5 class="card-title mb-0"><i class="feather-calendar me-2"></i>Plan Your Rice Field</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="plannerForm">
                                        <div class="mb-3">
                                            <label class="form-label">Rice Variety</label>
                                            <input type="text" name="variety" class="form-control" placeholder="e.g. NSIC Rc 222, IR64" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Field Size (hectares)</label>
                                                <input type="number" name="size" step="0.1" class="form-control" placeholder="2.5" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Planting Date</label>
                                                <input type="date" name="planting_date" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Growth Stage</label>
                                                <select name="growth_stage" class="form-select">
                                                    <option value="Seedling">Seedling</option>
                                                    <option value="Vegetative">Vegetative</option>
                                                    <option value="Flowering">Flowering</option>
                                                    <option value="Harvesting">Harvesting</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Plant Health</label>
                                                <select name="health" class="form-select">
                                                    <option value="Good">Good</option>
                                                    <option value="Average">Average</option>
                                                    <option value="Poor">Poor</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Pest Pressure</label>
                                                <select name="pest" class="form-select">
                                                    <option value="None">None</option>
                                                    <option value="Low">Low</option>
                                                    <option value="High">High</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Water Condition</label>
                                                <select name="water" class="form-select">
                                                    <option value="Enough">Enough</option>
                                                    <option value="Lacking">Lacking</option>
                                                    <option value="Flooded">Flooded</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                
                                                <label class="form-label">Weather Condition</label>
                                                <select name="weather" class="form-select">
                                                    <option value="Normal">Normal</option>
                                                    <option value="Dry">Dry</option>
                                                    <option value="Rainy">Rainy</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Yield per hectare (kg) - Optional</label>
                                                <input type="number" name="yield" class="form-control" placeholder="4000">
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label">Notes / Observations</label>
                                            <textarea name="notes" class="form-control" rows="3" placeholder="Any additional observations..."></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold">
                                            <i class="feather-calculator me-2"></i> CALCULATE PLAN
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Result Panel -->
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Prediction Result</h5>
                                    <?php if($result): ?>
                                    <button onclick="saveCurrentPlan()" class="btn btn-primary btn-sm">
                                        <i class="feather-save me-1"></i> Save to History
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">

                                    <?php if(!$result): ?>
                                    <div class="text-center py-5">
                                        <i class="feather-calendar" style="font-size: 4rem; color: #6c757d;"></i>
                                        <h5 class="mt-4 text-muted">No calculation yet</h5>
                                        <p class="text-muted">Fill the form on the left and click Calculate Plan</p>
                                    </div>
                                    <?php else: ?>

                                        <?php 
                                        $harvest_ts = strtotime($result['harvest_date']);
                                        $today_ts = strtotime(date('Y-m-d'));
                                        $days_left = floor(($harvest_ts - $today_ts) / 86400);
                                        ?>

                                        <?php if ($days_left <= 3 && $days_left >= 0): ?>
                                        <div class="alert alert-warning harvest-alert mb-4">
                                            <strong>⚠️ Harvest Time Approaching!</strong><br>
                                            Your rice will be ready on <strong><?= htmlspecialchars($result['harvest_date']) ?></strong> 
                                            (Only <strong><?= $days_left ?></strong> day(s) left)
                                        </div>
                                        <?php endif; ?>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="result-stat file-text">
                                                    <p class="text-muted mb-1">ESTIMATED HARVEST DATE</p>
                                                    <h3 class="fw-bold text-success"><?= htmlspecialchars($result['harvest_date']) ?></h3>
                                                </div>
                                            </div>

                               

                                            <div class="col-md-6">
                                                <div class="result-stat file-text ">
                                                    <p class="text-muted mb-1">TOTAL EXPECTED YIELD</p>
                                                    <h3 class="yield-amount"><?= number_format($result['total_yield']) ?> kg</h3>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 p-4 rounded-3 text-center border <?= $result['color'] === 'success' ? 'bg-success-subtle text-success' : ($result['color']==='warning' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') ?>">
                                            <h4 class="mb-1 fw-bold"><?= strtoupper($result['risk']) ?> RISK</h4>
                                            <p class="mb-0">Based on current field conditions</p>
                                        </div>

                                        <div class="mt-4">
                                            <h5 class="mb-3"><i class="feather-lightbulb text-warning"></i> Smart Suggestions</h5>
                                            <ul class="list-group">
                                                <?php foreach($result['suggestions'] as $s): ?>
                                                <li class="list-group-item d-flex align-items-start gap-2">
                                                    <i class="feather-check-circle text-success mt-1"></i>
                                                    <?= htmlspecialchars($s) ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <?php if(!empty($result['notes'])): ?>
                                        <div class="mt-4">
                                            <h5 class="mb-3"><i class="feather-file-text"></i> Your Notes</h5>
                                            <div class="p-3 border rounded-3">
                                                <?= nl2br(htmlspecialchars($result['notes'])) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/vendors/js/daterangepicker.min.js"></script>
    <script src="assets/vendors/js/apexcharts.min.js"></script>
    <script src="assets/vendors/js/circle-progress.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/dashboard-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    const planData = <?= $result ? json_encode($result) : 'null' ?>;

    function saveCurrentPlan() {
        if (!planData) {
            alert("Please calculate a plan first.");
            return;
        }

        const formData = new FormData();
        formData.append('action', 'save_plan');
        formData.append('variety', planData.variety);
        formData.append('size', planData.size);
        formData.append('planting_date', planData.planting_date);
        formData.append('growth_stage', planData.growth_stage);
        formData.append('health', planData.health);
        formData.append('pest', planData.pest);
        formData.append('water', planData.water);
        formData.append('weather', planData.weather);
        formData.append('notes', planData.notes || '');
        formData.append('yield', planData.yield_per_hectare);

        fetch('farmer_yield_planner.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("✅ Plan saved to history successfully!");
            } else {
                alert("Failed to save plan.");
            }
        })
        .catch(() => alert("Failed to save plan."));
    }
    </script>
</body>
</html>