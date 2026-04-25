<?php
// farmer_index.php - FULLY FIXED (Model loading robust + clean UI + no missing lines)
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: login_signup.php");
    exit;
}

require_once __DIR__ . '/database/database.php';
require __DIR__ . '/vendor/autoload.php';

// ENV Loader
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// ====================== POST HANDLERS ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_detection') {
        $user_id = $_SESSION['user_id'];
        $class_key = strtolower(trim($_POST['class_key'] ?? ''));
        $confidence = (int)($_POST['confidence'] ?? 65);
        $base64 = $_POST['image'] ?? '';

        if ($user_id && !empty($class_key) && !empty($base64)) {
            $upload_dir = 'uploads/detections/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $filename = $user_id . '_' . $class_key . '_' . time() . '.jpg';
            $filepath = $upload_dir . $filename;

            $base64 = str_replace('data:image/jpeg;base64,', '', $base64);
            $base64 = str_replace(' ', '+', $base64);
            file_put_contents($filepath, base64_decode($base64));

            $stmt = $conn->prepare("INSERT INTO user_detections (user_id, class_key, confidence, image_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $user_id, $class_key, $confidence, $filepath);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['success' => true]);
        exit;
    } elseif ($_POST['action'] === 'chat_query') {
        header('Content-Type: application/json; charset=utf-8');
        $result = tryGetChatResponse($_POST['query'] ?? '', $_POST['language'] ?? 'en');
        echo json_encode($result);
        exit;
    }
}

// Chatbot function
function tryGetChatResponse($query, $language = 'en') {
    $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
    if (empty($apiKey) || $apiKey === 'YOUR_HARDCODE_KEY_HERE') {
        return ['response' => 'API key not configured. Please add your Groq API key in the .env file.'];
    }

    $langName = ($language === 'en') ? 'English' : (($language === 'tl') ? 'Filipino' : (($language === 'hil') ? 'Hiligaynon' : 'Cebuano'));
    $prompt = "You are a friendly rice farming expert from the Philippines. User asked: \"$query\". Reply in $langName. Keep answer short, practical and helpful for Filipino farmers.";

    $url = "https://api.groq.com/openai/v1/chat/completions";
    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'max_tokens' => 600
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) return ['response' => 'Connection error: ' . $curlError];

    $json = json_decode($raw, true);
    if ($httpCode === 200 && isset($json['choices'][0]['message']['content'])) {
        return ['response' => trim($json['choices'][0]['message']['content'])];
    }
    return ['response' => 'Sorry, I couldn\'t get a response right now.'];
}

// ====================== KNOWLEDGE BASE ======================
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

$knowledgeBase = [];
$query = "SELECT disease, treatments, causes, nutrient_deficiency, grain_damage, prevention 
          FROM treatment_records WHERE user_id IS NULL";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $key = strtolower(trim($row['disease']));
    $knowledgeBase[$key] = [
        'treatment'           => $row['treatments'] ?? 'No data available yet.',
        'causes'              => $row['causes'] ?? '—',
        'nutrient_deficiency' => $row['nutrient_deficiency'] ?? '—',
        'grain_damage'        => $row['grain_damage'] ?? '—',
        'prevention'          => $row['prevention'] ?? '—'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RiceGuard AI • Rice Disease Detector</title>

    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3.21.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@0.8.4/dist/teachablemachine-image.min.js"></script>

    <style>
        .hidden { display: none !important; }

        #chat-toggle {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 28px;
            box-shadow: 0 8px 25px rgba(16,185,129,0.4);
            z-index: 1070;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #chat-window {
            position: fixed;
            bottom: 95px;
            right: 30px;
            width: 380px;
            height: 520px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            display: none;
            flex-direction: column;
            z-index: 1060;
            border: 1px solid #e5e7eb;
        }

        #chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
            scrollbar-width: thin;
        }

        .chat-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 20px;
            margin-bottom: 12px;
            word-break: break-word;
            line-height: 1.4;
        }

        .bot-bubble {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        .user-bubble {
            background: #10b981;
            color: white;
            border-bottom-right-radius: 4px;
            align-self: flex-end;
        }
    </style>
</head>
<body data-bs-theme="dark">

    <!-- Navigation -->
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
                    <li class="nxl-item nxl-hasmenu">
                        <a href="farmer_dashboard.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboards</span>
                        </a>
                    </li>
                    <li class="nxl-item active">
                        <a href="farmer_index.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-upload"></i></span>
                            <span class="nxl-mtext">Upload</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="farmer_camera.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-camera"></i></span>
                            <span class="nxl-mtext">Live Camera</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="farmer_live_com.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-message-square"></i></span>
                            <span class="nxl-mtext">Messenger</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="farmer_yield_planner.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-calendar"></i></span>
                            <span class="nxl-mtext">Rice Planner</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="farmer_history.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-clock"></i></span>
                            <span class="nxl-mtext">History</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="farmer_announcement.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-volume-2"></i></span>
                            <span class="nxl-mtext">Announcements</span>
                        </a>
                    </li>
                    <li class="nxl-item">
                        <a href="weatherapi.php" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-cloud"></i></span>
                            <span class="nxl-mtext">Weather</span>
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
                <div class="nxl-navigation-toggle">
                    <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                    <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
                </div>
            </div>
            <div class="header-right ms-auto">
                <div class="d-flex align-items-center gap-3">
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
                    <h5 class="m-b-10">Rice Disease & Pest Detector</h5>
                </div>
            </div>

            <div class="main-content">
                <div class="container-fluid">
                    <div class="row g-4">

                        <!-- LEFT UPLOAD -->
                        <div class="col-lg-5">
                            <div class="card stretch stretch-full">
                                <div class="card-header">
                                    <h5 class="card-title">Upload Rice Plant Image</h5>
                                </div>
                                <div class="card-body">

                                    <div id="drop-zone" class="border border-dashed border-primary rounded-3 p-8 text-center cursor-pointer mb-4" style="min-height: 280px;">
                                        <input type="file" id="file-input" accept="image/*" class="hidden">
                                        <i class="fa-solid fa-cloud-arrow-up fa-4x text-primary mb-3"></i>
                                        <h5 class="mb-2">Drop image here or</h5>
                                        <button onclick="browsePhoto()" class="btn btn-primary px-5 py-2">
                                            <i class="fa-solid fa-folder-open me-2"></i> BROWSE PHOTO
                                        </button>
                                        <p class="text-muted mt-4 small">JPG or PNG • Max 10MB • Clear rice leaf/plant photo</p>
                                    </div>

                                    <div id="preview-container" class="hidden mb-4">
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="fw-medium">Selected Image</span>
                                            <button onclick="clearPreview()" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i> Clear
                                            </button>
                                        </div>
                                        <div class="border rounded-3 overflow-hidden">
                                            <img id="preview-image" class="img-fluid w-100" alt="Preview" style="max-height: 380px; object-fit: contain;">
                                        </div>
                                    </div>

                                    <button onclick="classifyCurrentImage()" id="classify-btn"
                                            class="btn btn-lg w-100 py-3 fw-bold text-white"
                                            style="background: linear-gradient(135deg, #10b981, #059669);" disabled>
                                        <i class="fa-solid fa-magnifying-glass me-2"></i> CLASSIFY IMAGE
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT RESULTS -->
                        <div class="col-lg-7">
                            <div class="card stretch stretch-full">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title">Classification Results</h5>
                                    <div id="status" class="badge bg-success">Model loading...</div>
                                </div>
                                <div class="card-body">

                                    <div id="no-result">
                                        <i class="fa-solid fa-seedling fa-5x text-muted mb-4"></i>
                                        <h5>No image classified yet</h5>
                                        <p class="text-muted">Upload a clear rice plant photo to start detection</p>
                                    </div>

                                    <div id="results-panel" class="hidden">

                                        <div class="d-flex justify-content-end mb-4">
                                            <button onclick="saveCurrentDetection()" class="btn btn-success">
                                                <i class="fa-solid fa-floppy-disk me-2"></i> SAVE TO HISTORY
                                            </button>
                                        </div>

                                        <div class="mb-5">
                                            <div class="d-flex align-items-baseline gap-3">
                                                <div id="top-label" class="h4 mb-0 text-success"></div>
                                                <div id="top-confidence" class="display-6 fw-bold"></div>
                                            </div>
                                        </div>

                                        <div id="predictions-list" class="mb-4 p-3 border rounded"></div>

                                        <!-- Severity -->
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col">
                                                        <div id="severity-label" class="h4 mb-1"></div>
                                                        <p id="severity-message" class="mb-0 text-muted"></p>
                                                    </div>
                                                    <div class="col-auto text-end">
                                                        <small class="text-muted">Damage Level</small>
                                                        <div id="severity-percent" class="h3 fw-bold"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Knowledge -->
                                        <div class="row g-3">
                                            <div class="col-12"><div id="treatment" class="p-3 border rounded"></div></div>
                                            <div class="col-12"><div id="causes" class="p-3 border rounded"></div></div>
                                            <div id="nutrient-section" class="col-12"><div id="nutrient" class="p-3 border rounded"></div></div>
                                            <div id="damage-section" class="col-12 hidden"><div id="damage" class="p-3 border rounded"></div></div>
                                            <div id="grain-section" class="col-12"><div id="grain" class="p-3 border rounded"></div></div>
                                            <div class="col-12"><div id="prevention" class="p-3 border rounded"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- FLOATING CHATBOT -->
    <button id="chat-toggle"><i class="fa-solid fa-comment-dots"></i></button>

    <div id="chat-window">
        <div class="chat-header d-flex justify-content-between align-items-center p-3 bg-success text-white">
            <h5 class="mb-0">RiceGuard AI Assistant 🌾</h5>
            <button id="chat-close" class="btn-close btn-close-white"></button>
        </div>
        <div id="chat-messages" class="flex-grow-1 bg-white"></div>
        <div class="p-3 border-top bg-white">
            <div class="input-group">
                <input id="chat-input" type="text" class="form-control" placeholder="Ask about rice farming...">
                <button id="chat-send" class="btn btn-success"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

<script>
// ==================== DATA ====================
const diseaseNames = <?= json_encode($diseaseNames); ?>;
const pestNames = <?= json_encode($pestNames); ?>;
const knowledgeBase = <?= json_encode($knowledgeBase); ?>;

let model = null;
let currentImage = null;
let currentObjectURL = null;
let lastClassKey = null;
let lastConfidence = 65;

const modelURL = 'model/model.json';
const metadataURL = 'model/metadata.json';

async function loadModel() {
    const statusEl = document.getElementById('status');
    statusEl.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i> Loading model...`;
    statusEl.className = "badge bg-warning";

    try {
        console.log("Attempting to load model from:", modelURL);
        model = await tmImage.load(modelURL, metadataURL);
        console.log("✅ Model loaded successfully! Classes:", model.getTotalClasses());
        statusEl.innerHTML = `<i class="fa-solid fa-circle-check"></i> Model ready`;
        statusEl.className = "badge bg-success";
    } catch (e) {
        console.error("❌ Model load failed:", e);
        statusEl.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> Model failed`;
        statusEl.className = "badge bg-danger";
        alert("Failed to load the AI model.\n\nPlease check:\n1. The 'model/' folder exists in the same directory.\n2. model.json and weights files are present.\n\nCheck browser console (F12) for details.");
    }
}

function browsePhoto() { document.getElementById('file-input').click(); }

function setupUpload() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');

    fileInput.addEventListener('change', e => {
        if (e.target.files.length) handleFile(e.target.files[0]);
    });

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#10b981'; });
    dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = ''; });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.style.borderColor = '';
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });
}

function handleFile(file) {
    if (!file || !file.type.startsWith('image/')) return alert('Please select a valid image file');

    if (currentObjectURL) URL.revokeObjectURL(currentObjectURL);
    currentObjectURL = URL.createObjectURL(file);

    document.getElementById('preview-image').src = currentObjectURL;
    document.getElementById('preview-container').classList.remove('hidden');

    currentImage = new Image();
    currentImage.src = currentObjectURL;
    currentImage.onload = () => document.getElementById('classify-btn').disabled = false;
}

function clearPreview() {
    document.getElementById('preview-container').classList.add('hidden');
    document.getElementById('classify-btn').disabled = true;
    if (currentObjectURL) URL.revokeObjectURL(currentObjectURL);
    currentObjectURL = null;
    currentImage = null;
}

async function classifyCurrentImage() {
    if (!model || !currentImage) {
        alert("Model not loaded or no image selected.");
        return;
    }

    const btn = document.getElementById('classify-btn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i> CLASSIFYING...`;

    try {
        const predictions = await model.predict(currentImage);
        displayResults(predictions);
    } catch (err) {
        console.error("Classification error:", err);
        alert("Classification failed. Please try again.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
}

function displayResults(predictions) {
    document.getElementById('no-result').classList.add('hidden');
    document.getElementById('results-panel').classList.remove('hidden');

    let filtered = predictions.filter(p => p.probability >= 0.01);
    filtered.sort((a, b) => b.probability - a.probability);
    const top = filtered[0];

    const className = top.className.trim().toLowerCase().replace(/\s+/g, '_');
    lastClassKey = className;
    lastConfidence = Math.round(top.probability * 100);

    const isPest = Object.keys(pestNames).includes(className);
    const nameMap = isPest ? pestNames : diseaseNames;

    document.getElementById('top-label').textContent = nameMap[className] || top.className;
    document.getElementById('top-confidence').innerHTML = `${lastConfidence}%`;

    let html = '';
    filtered.forEach(pred => {
        const perc = (pred.probability * 100).toFixed(1);
        html += `<div class="d-flex justify-content-between mb-2"><span>${nameMap[pred.className] || pred.className}</span><span class="text-muted">${perc}%</span></div>`;
    });
    document.getElementById('predictions-list').innerHTML = html;

    // Severity logic
    let severityLabel = "LOW", severityMessage = "Monitor plant.", severityPercent = 30, color = "text-warning";
    if (className.includes("healthy")) {
        severityLabel = "HEALTHY"; severityMessage = "No action needed."; severityPercent = 0; color = "text-success";
    } else if (top.probability >= 0.80) {
        severityLabel = "SEVERE"; severityMessage = "Immediate action!"; severityPercent = 95; color = "text-danger";
    } else if (top.probability >= 0.50) {
        severityLabel = "MODERATE"; severityMessage = "Apply treatment."; severityPercent = 65; color = "text-warning";
    }

    document.getElementById('severity-label').textContent = severityLabel;
    document.getElementById('severity-label').className = `h4 mb-1 ${color}`;
    document.getElementById('severity-percent').textContent = severityPercent + "%";
    document.getElementById('severity-message').textContent = severityMessage;

    const kb = knowledgeBase[className] || {};

    document.getElementById('treatment').innerHTML = `<p>${kb.treatment || 'No data available yet.'}</p>`;
    document.getElementById('causes').innerHTML = `<p>${kb.causes || '—'}</p>`;
    document.getElementById('prevention').innerHTML = `<p>${kb.prevention || '—'}</p>`;

    if (isPest) {
        document.getElementById('nutrient-section').classList.add('hidden');
        document.getElementById('grain-section').classList.add('hidden');
        document.getElementById('damage-section').classList.remove('hidden');
        document.getElementById('damage').innerHTML = `<p>${kb.grain_damage || '—'}</p>`;
    } else {
        document.getElementById('nutrient-section').classList.remove('hidden');
        document.getElementById('grain-section').classList.remove('hidden');
        document.getElementById('damage-section').classList.add('hidden');
        document.getElementById('nutrient').innerHTML = `<p>${kb.nutrient_deficiency || '—'}</p>`;
        document.getElementById('grain').innerHTML = `<p>${kb.grain_damage || '—'}</p>`;
    }
}

async function saveCurrentDetection() {
    if (!lastClassKey || !currentImage) {
        alert("No detection or image to save.");
        return;
    }

    const btn = document.querySelector('button[onclick="saveCurrentDetection()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner animate-spin"></i> SAVING...`;

    try {
        const canvas = document.createElement('canvas');
        canvas.width = currentImage.width;
        canvas.height = currentImage.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(currentImage, 0, 0);
        const base64 = canvas.toDataURL('image/jpeg', 0.85);

        const formData = new FormData();
        formData.append('action', 'save_detection');
        formData.append('class_key', lastClassKey);
        formData.append('confidence', lastConfidence);
        formData.append('image', base64);

        const res = await fetch('', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            alert("✅ Detection + Photo saved to history successfully!");
        }
    } catch (e) {
        alert("Failed to save detection.");
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ==================== CHATBOT ====================
function initChat() {
    const toggleBtn = document.getElementById('chat-toggle');
    const chatWindow = document.getElementById('chat-window');
    const closeBtn = document.getElementById('chat-close');
    const sendBtn = document.getElementById('chat-send');
    const input = document.getElementById('chat-input');

    toggleBtn.addEventListener('click', () => {
        chatWindow.style.display = (chatWindow.style.display === 'flex') ? 'none' : 'flex';
        if (chatWindow.style.display === 'flex' && document.getElementById('chat-messages').children.length === 0) {
            setTimeout(() => addChatMessage("Hello! 🌾 Ask me anything about rice farming.", false), 300);
        }
    });

    closeBtn.addEventListener('click', () => chatWindow.style.display = 'none');

    sendBtn.addEventListener('click', sendChatQuery);
    input.addEventListener('keypress', e => { if (e.key === 'Enter') sendChatQuery(); });
}

function addChatMessage(text, isUser = false) {
    const container = document.getElementById('chat-messages');
    const msg = document.createElement('div');
    msg.className = `d-flex ${isUser ? 'justify-content-end' : 'justify-content-start'}`;
    msg.innerHTML = `<div class="chat-bubble ${isUser ? 'user-bubble' : 'bot-bubble'}">${text}</div>`;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;
}

async function sendChatQuery() {
    const input = document.getElementById('chat-input');
    const query = input.value.trim();
    if (!query) return;

    addChatMessage(query, true);
    input.value = '';

    const typing = document.createElement('div');
    typing.id = 'typing-indicator';
    typing.className = 'd-flex justify-content-start';
    typing.innerHTML = `<div class="chat-bubble bot-bubble"><i class="fa-solid fa-spinner fa-spin me-1"></i> Thinking...</div>`;
    document.getElementById('chat-messages').appendChild(typing);
    document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;

    try {
        const res = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=chat_query&query=${encodeURIComponent(query)}&language=en`
        });
        const data = await res.json();

        document.getElementById('typing-indicator').remove();
        addChatMessage(data.response || "Sorry, I couldn't answer right now.", false);
    } catch (e) {
        if (document.getElementById('typing-indicator')) document.getElementById('typing-indicator').remove();
        addChatMessage("Connection error. Please try again.", false);
    }
}

// ONLOAD
window.onload = async () => {
    await loadModel();
    setupUpload();
    initChat();

    const camImage = sessionStorage.getItem('capturedImage');
    if (camImage) {
        const img = new Image();
        img.src = camImage;
        currentImage = img;
        document.getElementById('preview-image').src = camImage;
        document.getElementById('preview-container').classList.remove('hidden');
        document.getElementById('classify-btn').disabled = false;
        sessionStorage.removeItem('capturedImage');
    }
};
</script>

</body>
</html>