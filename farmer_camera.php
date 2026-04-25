
<?php
// farmer_camera.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: login_signup.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <style>
#results-panel.hidden,
#results-panel.hidden * {
    display: none !important;
}
#no-result.hidden {
    display: none !important;
}
.hidden {
    display: none !important;
}
.cursor-pointer {
    cursor: pointer;
}
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

</style>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Rice Disease Detector</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- TensorFlow + Teachable Machine -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.21.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@0.8.4/dist/teachablemachine-image.min.js"></script>

</head>
<body>

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
    <!--! ================================================================ !-->
<!-- Main Content -->
 <!-- Main Content - Camera -->
            <div class="main-content">
                <div class="container-fluid">

                    <div class="page-header">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Live Camera • RiceGuard AI</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">

                            <div class="card stretch stretch-full">
                                <div class="card-header">
                                    <h5 class="card-title">Live Camera</h5>
                                </div>
                                <div class="card-body p-0">

                                    <!-- CAMERA VIEW -->
                                    <div id="camera-view" class="position-relative">

                                        <video id="video" autoplay playsinline class="w-100" style="height: 620px; object-fit: cover;"></video>

                                        <!-- Focus Frame -->
                                        <div class="position-absolute top-50 start-50 translate-middle pointer-events-none">
                                            <div class="border border-success border-3 rounded-4" style="width: 280px; height: 280px; box-shadow: 0 0 30px rgba(16,185,129,0.5);"></div>
                                        </div>

                                        <!-- Bottom Controls -->
                                        <div class="position-absolute bottom-0 start-0 end-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                                            <div class="d-flex justify-content-center">
                                                <button onclick="capturePhoto()" 
                                                    class="btn btn-light rounded-circle p-4 shadow-lg">
                                                    <div class="bg-white rounded-circle border border-4 border-dark" style="width: 60px; height: 60px;"></div>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Top Bar -->
                                        <div class="position-absolute top-0 start-0 end-0 d-flex justify-content-between align-items-center px-4 py-3 bg-black/60">
                                            <a href="farmer_index.php" class="text-white fs-5">← Back</a>
                                            <span class="text-white fw-semibold">RiceGuard Live Camera</span>
                                            <div></div>
                                        </div>
                                    </div>

                                    <!-- PREVIEW VIEW -->
                                    <div id="preview-view" class="hidden p-4">
                                        <div class="text-center mb-3">
                                            <h5>Preview</h5>
                                        </div>
                                        <div class="text-center">
                                            <img id="preview-image" class="img-fluid rounded-3 shadow" style="max-height: 500px;">
                                        </div>

                                        <div class="d-flex gap-3 mt-4">
                                            <button onclick="retake()" class="btn btn-secondary flex-fill">Retake</button>
                                            <button onclick="sendToIndex()" class="btn btn-success flex-fill">
                                                🌾 Classify This Image
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
               </div>
        <!-- [ Footer ] start -->
                <footer class="footer">
                    <p class="fs-11 text-muted fw-medium text-uppercase mb-0 copyright">
                        <span>Copyright ©</span>
                        <script>
                            document.write(new Date().getFullYear());
                        </script>
                    </p>
                    <p><span>By: <a target="_blank" href="https://wrapbootstrap.com/user/theme_ocean" target="_blank">theme_ocean</a></span> • <span>Distributed by: <a target="_blank" href="https://themewagon.com" target="_blank">ThemeWagon</a></span></p>
                    <div class="d-flex align-items-center gap-4">
                        <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Help</a>
                        <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Terms</a>
                        <a href="javascript:void(0);" class="fs-11 fw-semibold text-uppercase">Privacy</a>
                    </div>
                </footer>
                <!-- [ Footer ] end -->
            </main>  
    
 <!-- end main content -->
<script src="assets/vendors/js/vendors.min.js"></script>
    <!-- vendors.min.js {always must need to be top} -->
    <script src="assets/vendors/js/daterangepicker.min.js"></script>
    <script src="assets/vendors/js/apexcharts.min.js"></script>
    <script src="assets/vendors/js/circle-progress.min.js"></script>
    <!--! END: Vendors JS !-->
    <!--! BEGIN: Apps Init  !-->
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/dashboard-init.min.js"></script>
    <!--! END: Apps Init !-->
    <!--! BEGIN: Theme Customizer  !-->
    <script src="assets/js/theme-customizer-init.min.js"></script>
    <!--! END: Theme Customizer !-->

    <script>
// Camera Variables
let video = null;
let canvas = null;
let capturedImage = null;

function initCamera() {
    video = document.getElementById('video');
    canvas = document.createElement('canvas');

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: "environment" }
    })
    .then(stream => {
        video.srcObject = stream;
    })
    .catch(err => {
        alert("Cannot access camera. Please allow camera permission.");
        console.error(err);
    });
}

function capturePhoto() {
    if (!video) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    capturedImage = canvas.toDataURL('image/jpeg', 0.92);

    // Show preview
    document.getElementById('camera-view').classList.add('hidden');
    document.getElementById('preview-view').classList.remove('hidden');
    document.getElementById('preview-image').src = capturedImage;
}

function retake() {
    document.getElementById('preview-view').classList.add('hidden');
    document.getElementById('camera-view').classList.remove('hidden');
}

function sendToIndex() {
    if (!capturedImage) return;
    
    sessionStorage.setItem('capturedImage', capturedImage);
    window.location.href = "farmer_index.php";
}

// Initialize when page loads
window.onload = function() {
    initCamera();
};
</script>
</body>
</html>