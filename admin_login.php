<?php
// ========================================================
// FILE: admin_login.php
// UPDATED: Modern UI aligned with RiceGuard template
// ========================================================

session_start();
require_once 'database/database.php';

$defaultLang = 'tl';

$translations = [
    'tl' => [
        'title' => 'RiceGuard AI • Admin Login',
        'welcome' => 'Maligayang pagdating sa RiceGuard AI Admin Portal',
        'email' => 'Email',
        'password' => 'Password',
        'login_btn' => 'Mag-login',
        'error_empty' => 'Punan ang lahat ng field.',
        'error_invalid_credentials' => 'Mali ang email o password.',
        'forgot_password_link' => 'Nakalimutan ang Password?',
        'forgot_title' => 'Nakalimutan ang Password',
        'forgot_email_label' => 'Email Address',
        'forgot_submit_btn' => 'Ipadala ang Bagong Password',
        'forgot_note' => 'Isang secure na temporaryong password ang ipapadala sa iyong email.',
        'forgot_success' => 'Naipadala na ang bagong temporaryong password sa iyong email.',
        'forgot_email_not_found' => 'Walang admin account na nakarehistro sa email na ito.'
    ]
];

$msg = '';

// ====================== LOGIN PROCESSING ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['forgot_password'])) {
        // Forgot password logic
        $email = trim($_POST['forgot_email'] ?? '');
        if ($email) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $msg = $translations[$defaultLang]['forgot_success'];
            } else {
                $msg = $translations[$defaultLang]['forgot_email_not_found'];
            }
            $stmt->close();
        }
    } else {
        // Normal Login
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $msg = $translations[$defaultLang]['error_empty'];
        } else {
            $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ? AND role = 'admin'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {

                    // === CRITICAL SESSION RESET ===
                    session_unset();
                    session_destroy();
                    session_start();
                    session_regenerate_id(true);

                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = 'admin';
                    $_SESSION['email']     = $email;

                    header("Location: admin_dashboard.php");
                    exit;
                }
            }
            $msg = $translations[$defaultLang]['error_invalid_credentials'];
            $result->close();
            $stmt->close();
        }
    }
}

// If already logged in as admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations[$defaultLang]['title'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background: #0f172a; 
            color: white; 
            font-family: system-ui, sans-serif; 
        }
        .card { 
            background: #1e293b; 
            border-radius: 20px; 
            padding: 2.5rem; 
            max-width: 420px; 
            margin: auto; 
            margin-top: 100px; 
        }
        .password-container {
            position: relative;
        }
        .password-container input {
            width: 100%;
            padding-right: 50px;
        }
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            font-size: 1.1rem;
        }
        .toggle-password:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="text-center mb-8">
        <i class="fas fa-user-shield text-5xl text-emerald-400 mb-4"></i>
        <h1 class="text-2xl font-bold">Admin Login</h1>
        <p class="text-zinc-400 text-sm mt-1"><?= $translations[$defaultLang]['welcome'] ?></p>
    </div>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 <?= strpos($msg, 'Naipadala') !== false ? 'bg-emerald-700' : 'bg-red-700' ?> rounded-2xl text-center">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5" id="loginForm">
        <div>
            <input type="email" name="email" id="email_input"
                   placeholder="<?= $translations[$defaultLang]['email'] ?>" 
                   required 
                   class="w-full p-4 rounded-2xl bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-lg">
        </div>
        
        <div class="password-container">
            <input type="password" name="password" id="password_input"
                   placeholder="<?= $translations[$defaultLang]['password'] ?>" 
                   required 
                   class="w-full p-4 rounded-2xl bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-lg">
            <span class="toggle-password" id="togglePassword">
                <i class="fas fa-eye"></i>
            </span>
        </div>

        <!-- Forgot Password Link -->
        <div class="flex justify-end">
            <a href="#" onclick="showForgotModal(); return false;" 
               class="text-emerald-400 hover:text-emerald-300 text-sm font-medium">
                <?= $translations[$defaultLang]['forgot_password_link'] ?>
            </a>
        </div>

        <button type="submit"
                class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 rounded-3xl font-semibold text-lg transition-all">
            <?= $translations[$defaultLang]['login_btn'] ?>
        </button>
    </form>

    <p class="text-center text-xs text-zinc-500 mt-8">
        Restricted to Administrators only.
    </p>
</div>

<!-- Forgot Password Modal -->
<div id="forgotModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 px-4">
    <div class="card w-full max-w-md rounded-3xl p-8">
        <h2 class="text-2xl font-bold text-center mb-6"><?= $translations[$defaultLang]['forgot_title'] ?></h2>
        
        <form method="POST" id="forgotForm">
            <input type="hidden" name="forgot_password" value="1">
            
            <div class="mb-6">
                <label class="block text-zinc-400 text-sm mb-2"><?= $translations[$defaultLang]['forgot_email_label'] ?></label>
                <input type="email" name="forgot_email" id="forgot_email_input" 
                       class="w-full p-4 rounded-2xl bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-lg" 
                       required placeholder="your@email.com">
            </div>

            <button type="submit"
                    class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 rounded-3xl font-semibold text-lg transition-all">
                <?= $translations[$defaultLang]['forgot_submit_btn'] ?>
            </button>
        </form>

        <button onclick="hideForgotModal()" 
                class="w-full mt-4 py-4 bg-zinc-700 hover:bg-zinc-600 rounded-3xl text-zinc-400 font-medium">
            Cancel
        </button>

        <p class="text-center text-xs text-zinc-500 mt-6">
            <?= $translations[$defaultLang]['forgot_note'] ?>
        </p>
    </div>
</div>

<script>
// Password Toggle
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password_input');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
});

// Forgot Password Modal
function showForgotModal() {
    const modal = document.getElementById('forgotModal');
    const emailInput = document.getElementById('email_input');
    modal.classList.remove('hidden');
    
    if (emailInput && emailInput.value) {
        document.getElementById('forgot_email_input').value = emailInput.value;
    }
}

function hideForgotModal() {
    document.getElementById('forgotModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('forgotModal');
        if (modal && !modal.classList.contains('hidden')) hideForgotModal();
    }
});
</script>

</body>
</html>