<?php
include_once __DIR__ . '/../common/config.php';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_id']);
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, password FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid admin credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Headshot League BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-red-500/20">
                <i class="fas fa-user-shield text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold">Admin <span class="text-red-500">Access</span></h1>
            <p class="text-slate-400 text-sm mt-1">Authorized personnel only</p>
        </div>

        <div class="glass rounded-3xl p-8 shadow-2xl">
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-2 rounded-xl mb-6 text-sm">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2 uppercase tracking-wider">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="text" name="username" required class="w-full bg-slate-800/50 border border-slate-700 rounded-xl pl-12 pr-4 py-3.5 text-sm focus:outline-none focus:border-red-500 transition-colors">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="password" name="password" required class="w-full bg-slate-800/50 border border-slate-700 rounded-xl pl-12 pr-4 py-3.5 text-sm focus:outline-none focus:border-red-500 transition-colors">
                    </div>
                </div>
                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-red-500/20 active:scale-[0.98]">
                    Secure Login
                </button>
            </form>
        </div>
        
        <div class="text-center mt-8">
            <a href="../index.php" class="text-slate-500 text-sm hover:text-slate-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to User Panel
            </a>
        </div>
    </div>
</body>
</html>
