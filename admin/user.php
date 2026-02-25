<?php
include_once 'common/header.php';

$error = '';
$success = '';

// Fetch Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

?>

<div class="space-y-6">
    <h2 class="text-xl font-bold">User Management</h2>

    <div class="space-y-4">
        <?php foreach ($users as $u): ?>
            <div class="glass rounded-2xl p-4 border border-white/5">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center text-slate-500">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm"><?php echo $u['username']; ?></h4>
                            <p class="text-[10px] text-slate-500"><?php echo $u['email']; ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-[10px] text-slate-500 uppercase">Balance</span>
                        <span class="font-bold text-orange-500 text-sm"><?php echo formatCurrency($u['wallet_balance']); ?></span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button class="flex-1 bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-bold py-2 rounded-lg transition-all">
                        View History
                    </button>
                    <button class="flex-1 bg-red-500/10 border border-red-500/20 text-red-500 text-[10px] font-bold py-2 rounded-lg transition-all">
                        Block User
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
