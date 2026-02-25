<?php
include_once 'common/config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetch()['wallet_balance'];

// Fetch transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

include_once 'common/header.php';
?>

<div class="space-y-6">
    <h2 class="text-xl font-bold">My Wallet</h2>

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-3xl p-8 shadow-2xl shadow-orange-500/20 relative overflow-hidden">
        <div class="relative z-10">
            <span class="text-orange-100/70 text-sm font-medium mb-1 block">Available Balance</span>
            <h3 class="text-4xl font-black text-white"><?php echo formatCurrency($balance); ?></h3>
        </div>
        
        <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl"></div>
        <div class="absolute left-0 bottom-0 w-24 h-24 bg-black/10 rounded-full -ml-10 -mb-10 blur-xl"></div>
    </div>

    <!-- Actions -->
    <div class="grid grid-cols-2 gap-4">
        <button class="glass rounded-2xl p-4 flex flex-col items-center gap-2 border-orange-500/20 hover:bg-orange-500/5 transition-all">
            <div class="w-10 h-10 bg-orange-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-plus text-orange-500"></i>
            </div>
            <span class="text-sm font-bold">Add Money</span>
        </button>
        <button class="glass rounded-2xl p-4 flex flex-col items-center gap-2 border-red-500/20 hover:bg-red-500/5 transition-all">
            <div class="w-10 h-10 bg-red-500/20 rounded-full flex items-center justify-center">
                <i class="fas fa-arrow-up text-red-500"></i>
            </div>
            <span class="text-sm font-bold">Withdraw</span>
        </button>
    </div>

    <!-- Transactions -->
    <div class="space-y-4">
        <h3 class="font-bold text-lg">Recent Transactions</h3>
        
        <?php if (empty($transactions)): ?>
            <div class="glass rounded-2xl p-8 text-center">
                <p class="text-slate-500 text-sm">No transactions yet.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($transactions as $tx): ?>
                    <div class="glass rounded-2xl p-4 flex justify-between items-center border-white/5">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center <?php echo $tx['type'] == 'Credit' ? 'bg-orange-500/10 text-orange-500' : 'bg-red-500/10 text-red-500'; ?>">
                                <i class="fas <?php echo $tx['type'] == 'Credit' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold"><?php echo $tx['description']; ?></h4>
                                <span class="text-[10px] text-slate-500"><?php echo date('d M, h:i A', strtotime($tx['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-bold <?php echo $tx['type'] == 'Credit' ? 'text-orange-500' : 'text-red-500'; ?>">
                                <?php echo ($tx['type'] == 'Credit' ? '+' : '-') . formatCurrency($tx['amount']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
