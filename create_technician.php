<?php
// create_technician.php - Updated for consistency
session_start();
require_once __DIR__ . '/database/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_signup.php");
    exit;
}

$msg = '';
$msg_type = 'success';

if (isset($_POST['create_tech'])) {
    $tech_name  = trim($_POST['tech_name'] ?? '');
    $tech_email = trim($_POST['tech_email'] ?? '');
    $tech_pass  = $_POST['tech_pass'] ?? '';

    if (empty($tech_name) || empty($tech_email) || empty($tech_pass)) {
        $msg = 'Please fill in all fields.';
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $tech_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = 'This email is already registered.';
            $msg_type = 'error';
        } else {
            $hashed = password_hash($tech_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users 
                (full_name, email, password, role, status, created_at) 
                VALUES (?, ?, ?, 'technician', 'approved', NOW())");
            $stmt->bind_param("sss", $tech_name, $tech_email, $hashed);
            
            if ($stmt->execute()) {
                $msg = 'Technician account created successfully!';
            } else {
                $msg = 'Database error: ' . $conn->error;
                $msg_type = 'error';
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Technician • RiceGuard AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #0f172a; color: white; }
        .card { background: #1e293b; border: 1px solid #334155; }
    </style>
</head>
<body class="min-h-screen p-8 flex items-center justify-center">

<div class="max-w-lg w-full">
    <a href="admin_dashboard.php" class="inline-flex items-center gap-2 text-emerald-400 hover:text-emerald-300 mb-8">
        ← Back to Dashboard
    </a>

    <div class="card rounded-3xl p-10">
        <div class="text-center mb-8">
            <i class="fas fa-user-plus text-6xl text-cyan-400 mb-4"></i>
            <h1 class="text-3xl font-bold">Create New Technician</h1>
            <p class="text-zinc-400 mt-2">Add a new technician account</p>
        </div>

        <?php if ($msg): ?>
            <div class="mb-8 p-4 rounded-2xl <?= $msg_type === 'success' ? 'bg-green-700' : 'bg-red-700' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <div>
                <label class="block text-zinc-400 text-sm mb-2">Full Name</label>
                <input type="text" name="tech_name" required 
                       class="w-full p-5 rounded-2xl bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-lg">
            </div>

            <div>
                <label class="block text-zinc-400 text-sm mb-2">Email Address</label>
                <input type="email" name="tech_email" required 
                       class="w-full p-5 rounded-2xl bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-lg">
            </div>

            <div>
                <label class="block text-zinc-400 text-sm mb-2">Password</label>
                <input type="password" name="tech_pass" required 
                       class="w-full p-5 rounded-2xl bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-lg">
            </div>

            <button name="create_tech" 
                    class="w-full py-6 bg-cyan-600 hover:bg-cyan-700 rounded-3xl text-xl font-semibold transition">
                Create Technician Account
            </button>
        </form>
    </div>
</div>

</body>
</html>