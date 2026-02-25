<?php
include_once 'config.php';
$user_id = $_SESSION['user_id'] ?? null;
$wallet_balance = 0;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $wallet_balance = $user['wallet_balance'] ?? 0;

    // Fetch unread notifications count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetchColumn();
}

// Fetch Settings
$stmt = $pdo->prepare("SELECT * FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch();
$site_name = $settings['site_name'] ?? 'Headshot League BD';
$site_logo = $settings['site_logo'] ?? 'assets/logo.png';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
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
    <?php if ($user_id && $unread_count > 0): ?>
    <div class="fixed top-20 left-4 right-4 z-[60] bg-orange-500 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg flex justify-between items-center animate-bounce">
        <span><i class="fas fa-bell mr-2"></i> You have new notifications</span>
        <a href="notifications.php" class="underline">View</a>
    </div>
    <?php endif; ?>

    <!-- Top Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 glass px-4 py-3 flex justify-between items-center shadow-lg">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center overflow-hidden">
                <img src="<?php echo $site_logo; ?>" alt="Logo" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                <i class="fas fa-crosshairs text-white hidden"></i>
            </div>
            <h1 class="font-bold text-lg tracking-tight">
                <?php 
                $words = explode(' ', $site_name);
                foreach($words as $index => $word) {
                    if($index == count($words) - 1) {
                        echo "<span class='text-orange-500'>$word</span>";
                    } else {
                        echo "$word ";
                    }
                }
                ?>
            </h1>
        </div>
        
        <?php if ($user_id): ?>
        <div class="flex items-center gap-3">
            <a href="notifications.php" class="relative w-10 h-10 bg-slate-800/50 rounded-full flex items-center justify-center border border-slate-700">
                <i class="fas fa-bell text-slate-400"></i>
                <?php if ($unread_count > 0): ?>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center border-2 border-[#0f172a]"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="flex items-center gap-3 bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-700">
                <i class="fas fa-wallet text-orange-500 text-sm"></i>
                <span class="font-semibold text-sm"><?php echo formatCurrency($wallet_balance); ?></span>
            </div>
        </div>
        <?php else: ?>
        <a href="login.php" class="text-sm font-medium text-orange-500">Login</a>
        <?php endif; ?>
    </header>

    <main class="pt-20 pb-24 px-4 min-h-screen">
<?php echo $security_script; ?>
