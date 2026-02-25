<?php
include_once 'common/header.php';

// Fetch Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_tournaments = $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$total_prize = $pdo->query("SELECT SUM(prize_pool) FROM tournaments WHERE status = 'Completed'")->fetchColumn() ?: 0;

// Revenue calculation (Commission from completed tournaments)
$total_revenue = $pdo->query("
    SELECT SUM(entry_fee * (SELECT COUNT(*) FROM participants WHERE tournament_id = t.id) * (commission_percentage/100)) 
    FROM tournaments t 
    WHERE status = 'Completed'
")->fetchColumn() ?: 0;

?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold">Dashboard Overview</h2>
        <a href="tournament.php" class="bg-red-500 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-lg shadow-red-500/20">
            <i class="fas fa-plus mr-1"></i> New Match
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 gap-4">
        <div class="glass rounded-2xl p-4 border-l-4 border-blue-500">
            <span class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Total Users</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black"><?php echo $total_users; ?></span>
                <i class="fas fa-users text-blue-500/30 text-xs"></i>
            </div>
        </div>
        <div class="glass rounded-2xl p-4 border-l-4 border-purple-500">
            <span class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Tournaments</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black"><?php echo $total_tournaments; ?></span>
                <i class="fas fa-trophy text-purple-500/30 text-xs"></i>
            </div>
        </div>
        <div class="glass rounded-2xl p-4 border-l-4 border-orange-500">
            <span class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Prize Paid</span>
            <div class="flex items-baseline gap-2">
                <span class="text-xl font-black"><?php echo formatCurrency($total_prize); ?></span>
            </div>
        </div>
        <div class="glass rounded-2xl p-4 border-l-4 border-red-500">
            <span class="text-[10px] text-slate-500 uppercase font-bold block mb-1">Revenue</span>
            <div class="flex items-baseline gap-2">
                <span class="text-xl font-black"><?php echo formatCurrency($total_revenue); ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Activity / Quick Links -->
    <div class="space-y-4">
        <h3 class="font-bold text-lg">Quick Management</h3>
        <div class="grid grid-cols-1 gap-3">
            <a href="tournament.php" class="glass rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold">Manage Tournaments</h4>
                        <p class="text-[10px] text-slate-500">Create, Edit, Delete matches</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-slate-600"></i>
            </a>
            <a href="user.php" class="glass rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-orange-500/10 rounded-xl flex items-center justify-center text-orange-500">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold">User Management</h4>
                        <p class="text-[10px] text-slate-500">View balances and history</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-slate-600"></i>
            </a>
            <a href="setting.php" class="glass rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-slate-500/10 rounded-xl flex items-center justify-center text-slate-400">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold">Admin Settings</h4>
                        <p class="text-[10px] text-slate-500">Website logo, name, and security</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-slate-600"></i>
            </a>
        </div>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
