<?php
include_once 'common/config.php';

$user_id = $_SESSION['user_id'] ?? null;

// Fetch Upcoming Tournaments
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE status = 'Upcoming' ORDER BY match_time ASC");
$stmt->execute();
$tournaments = $stmt->fetchAll();

include_once 'common/header.php';
?>

<div class="space-y-6">
    <!-- Hero Section -->
    <div class="glass rounded-3xl p-6 relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-2xl font-bold mb-2">Welcome to <span class="text-orange-500">Headshot League</span></h2>
            <p class="text-slate-400 text-sm">Join tournaments, win matches, and earn real rewards.</p>
        </div>
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-orange-500/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Tournament List -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="font-bold text-lg">Upcoming Matches</h3>
            <span class="text-xs text-slate-500"><?php echo count($tournaments); ?> Available</span>
        </div>

        <?php if (empty($tournaments)): ?>
            <div class="glass rounded-2xl p-8 text-center">
                <i class="fas fa-calendar-times text-slate-600 text-4xl mb-3"></i>
                <p class="text-slate-400 text-sm">No upcoming tournaments found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($tournaments as $t): ?>
                <div class="glass rounded-2xl overflow-hidden border border-white/5 hover:border-orange-500/30 transition-all">
                    <div class="bg-slate-800/50 px-4 py-2 border-b border-white/5 flex justify-between items-center">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-orange-500"><?php echo $t['game_name']; ?></span>
                        <span class="text-[10px] text-slate-400"><i class="far fa-clock mr-1"></i> <?php echo date('d M, h:i A', strtotime($t['match_time'])); ?></span>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-lg mb-4"><?php echo $t['title']; ?></h4>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-slate-800/30 p-3 rounded-xl border border-white/5">
                                <span class="block text-[10px] text-slate-500 uppercase mb-1">Prize Pool</span>
                                <span class="font-bold text-orange-500"><?php echo formatCurrency($t['prize_pool']); ?></span>
                            </div>
                            <div class="bg-slate-800/30 p-3 rounded-xl border border-white/5">
                                <span class="block text-[10px] text-slate-500 uppercase mb-1">Entry Fee</span>
                                <span class="font-bold"><?php echo formatCurrency($t['entry_fee']); ?></span>
                            </div>
                        </div>

                        <a href="join.php?id=<?php echo $t['id']; ?>" class="block w-full bg-orange-500 hover:bg-orange-600 text-white text-center font-bold py-3 rounded-xl transition-all shadow-lg shadow-orange-500/20 active:scale-95">
                            Join Now
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
