<?php
// weatherapi.php - FULLY UPDATED WITH NXL TEMPLATE + INNOVATIVE DESIGN
$apiKey = "67f431da7de34177a0d24928263103";
$city   = "Sagay City";

$url = "http://api.weatherapi.com/v1/forecast.json?key=$apiKey&q=" . urlencode($city) . "&days=1&aqi=no&alerts=yes";

$response = @file_get_contents($url);

if ($response === FALSE) {
    die("<div style='color:red; text-align:center; padding:50px;'>❌ Unable to fetch weather data.</div>");
}

$data = json_decode($response, true);

if (!$data || isset($data['error'])) {
    die("<div style='color:red; text-align:center; padding:50px;'>❌ API Error: " . ($data['error']['message'] ?? "Unknown error") . "</div>");
}

$current   = $data['current'];
$forecast  = $data['forecast']['forecastday'][0]['day'];

$temp      = round($current['temp_c']);
$condition = $current['condition']['text'];
$humidity  = $current['humidity'];
$wind      = round($current['wind_kph']);
$rain      = $forecast['daily_chance_of_rain'];

// Advanced Rice Farmer Risk Logic
$alerts = [];
$riskLevel = "Low";
$riskColor = "success";

if ($rain > 70 || $humidity > 88) {
    $alerts[] = "🔴 HIGH RISK: Sheath Blight & Rice Blast likely. Drain fields and apply fungicide if needed.";
    $riskLevel = "High";
    $riskColor = "danger";
} elseif ($rain > 50 || $humidity > 82) {
    $alerts[] = "🟡 MEDIUM RISK: Brown Spot & Fungal diseases possible. Increase scouting.";
    $riskLevel = "Medium";
    $riskColor = "warning";
}

if ($temp > 34) {
    $alerts[] = "🔥 Heat stress warning — Water early morning or late afternoon.";
}
if ($wind > 25) {
    $alerts[] = "💨 Strong winds — Check for lodging risk on tall varieties.";
}
if ($humidity > 85 && $rain < 30) {
    $alerts[] = "🦠 High humidity — Brown Planthopper risk rising. Scout fields today.";
}

if (empty($alerts)) {
    $alerts[] = "✅ Good weather conditions for rice today. Continue normal practices.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Weather for Farmers</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                    <h5 class="m-b-10">🌾 Weather for Rice Farmers</h5>
                    <p class="m-b-0 text-muted">Sagay City • Real-time insights for better decisions</p>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <div class="row g-4">
                        <!-- Current Weather Card -->
                        <div class="col-lg-5">
                            <div class="card stretch stretch-full">
                                <div class="card-body text-center p-5">
                                    <div class="display-1 fw-bold text-white mb-2"><?= $temp ?>°C</div>
                                    <h4 class="text-muted"><?= $condition ?></h4>
                                    <div class="d-flex justify-content-center gap-5 mt-4 text-start">
                                        <div><i class="fa-solid fa-droplet text-info"></i> <?= $humidity ?>% Humidity</div>
                                        <div><i class="fa-solid fa-wind text-info"></i> <?= $wind ?> km/h Wind</div>
                                        <div><i class="fa-solid fa-cloud-rain text-info"></i> <?= $rain ?>% Rain</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Risk Level -->
                        <div class="col-lg-7">
                            <div class="card stretch stretch-full border-<?= $riskColor ?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-<?= $riskColor ?> fs-1 px-4 py-3"><?= strtoupper($riskLevel) ?> RISK</span>
                                        </div>
                                        <div>
                                            <h5 class="mb-1">Today's Farming Risk Level</h5>
                                            <p class="text-muted">Based on temperature, rain chance, and humidity</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Farmer Alerts -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Farmer Alerts & Recommendations</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($alerts as $alert): ?>
                                <li class="list-group-item bg-transparent border-0 py-3">
                                    <?= $alert ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="text-center text-muted small mt-4">
                        💡 Tip: See symptoms in your field? Go to <strong>Upload</strong> or <strong>Live Camera</strong> for AI diagnosis.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/vendors/js/daterangepicker.min.js"></script>
    <script src="assets/vendors/js/apexcharts.min.js"></script>
    <script src="assets/vendors/js/circle-progress.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/dashboard-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>
</body>
</html>