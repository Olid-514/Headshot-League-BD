<?php
include_once 'common/config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$tab = $_GET['tab'] ?? 'upcoming';

if ($tab == 'completed') {
    $stmt = $pdo->prepare("
        SELECT t.* FROM tournaments t 
        JOIN participants p ON t.id = p.tournament_id 
        WHERE p.user_id = ? AND t.status = 'Completed' 
        ORDER BY t.match_time DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT t.* FROM tournaments t 
        JOIN participants p ON t.id = p.tournament_id 
        WHERE p.user_id = ? AND (t.status = 'Upcoming' OR t.status = 'Live') 
        ORDER BY t.match_time ASC
    ");
}

$stmt->execute([$user_id]);
$matches = $stmt->fetchAll();

include_once 'common/header.php';
?>

<div class="space-y-6">
    <h2 class="text-xl font-bold">My Matches</h2>

    <!-- Tabs -->
    <div class="flex gap-2 p-1 bg-slate-800/50 rounded-2xl border border-white/5">
        <a href="?tab=upcoming" class="flex-1 text-center py-2.5 rounded-xl text-sm font-semibold transition-all <?php echo $tab == 'upcoming' ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'text-slate-400'; ?>">
            Upcoming/Live
        </a>
        <a href="?tab=completed" class="flex-1 text-center py-2.5 rounded-xl text-sm font-semibold transition-all <?php echo $tab == 'completed' ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' : 'text-slate-400'; ?>">
            Completed
        </a>
    </div>

    <!-- Match List -->
    <div class="space-y-4">
        <?php if (empty($matches)): ?>
            <div class="glass rounded-2xl p-12 text-center">
                <i class="fas fa-ghost text-slate-600 text-4xl mb-3"></i>
                <p class="text-slate-400 text-sm">No matches found in this category.</p>
            </div>
        <?php else: ?>
            <?php foreach ($matches as $m): ?>
                <div class="glass rounded-2xl overflow-hidden border border-white/5">
                    <div class="bg-slate-800/50 px-4 py-2 border-b border-white/5 flex justify-between items-center">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-orange-500"><?php echo $m['game_name']; ?></span>
                        <div class="flex items-center gap-2">
                            <?php if ($m['status'] == 'Live'): ?>
                                <span class="flex h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                                <span class="text-[10px] font-bold text-red-500 uppercase">Live</span>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-400 uppercase font-bold"><?php echo $m['status']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-lg mb-2"><?php echo $m['title']; ?></h4>
                        <p class="text-xs text-slate-400 mb-4"><i class="far fa-clock mr-1"></i> <?php echo date('d M, h:i A', strtotime($m['match_time'])); ?></p>

                        <?php if ($m['status'] == 'Live'): ?>
                            <div class="bg-orange-500/10 border border-orange-500/20 rounded-xl p-4 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-400">Room ID:</span>
                                    <span class="text-sm font-mono font-bold text-orange-500"><?php echo $m['room_id'] ?: 'Wait...'; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-400">Password:</span>
                                    <span class="text-sm font-mono font-bold text-orange-500"><?php echo $m['room_password'] ?: 'Wait...'; ?></span>
                                </div>
                            </div>
                        <?php elseif ($m['status'] == 'Completed'): ?>
                            <div class="flex items-center gap-2 text-sm font-semibold">
                                <i class="fas fa-medal text-yellow-500"></i>
                                <span class="text-slate-300">Match Finished</span>
                            </div>
                        <?php else: ?>
                            <div class="bg-slate-800/30 rounded-xl p-3 text-center">
                                <p class="text-xs text-slate-500">Room details will be available when match goes Live.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
