<?php
include_once __DIR__ . '/../../common/config.php';
$admin_id = $_SESSION['admin_id'] ?? null;

if (!$admin_id && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Panel - Headshot League BD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
        }
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes pulse-soft {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-pulse-soft {
            animation: pulse-soft 2s infinite;
        }
    </style>
</head>
<body class="select-none">
    <!-- Top Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 glass px-4 py-3 flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-white"></i>
            </div>
            <h1 class="font-bold text-lg tracking-tight">Admin <span class="text-red-500">Panel</span></h1>
        </div>
        
        <?php if ($admin_id): ?>
        <div class="flex items-center gap-4">
            <a href="setting.php" class="text-slate-400 hover:text-white"><i class="fas fa-cog"></i></a>
            <a href="login.php?logout=1" class="text-sm font-medium text-red-500">Logout</a>
        </div>
        <?php endif; ?>
    </header>

    <main class="pt-20 pb-24 px-4 min-h-screen">
<?php echo $security_script; ?>
