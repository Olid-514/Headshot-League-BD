<?php
include_once 'common/config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Mark all as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$user_id]);

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT n.*, t.room_id, t.room_password, t.status as tournament_status 
    FROM notifications n 
    JOIN tournaments t ON n.tournament_id = t.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

include_once 'common/header.php';
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold">Notifications</h2>
        <span class="text-[10px] text-slate-500 uppercase font-bold"><?php echo count($notifications); ?> Total</span>
    </div>

    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="glass rounded-3xl p-12 text-center">
                <div class="w-16 h-16 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bell-slash text-slate-600 text-2xl"></i>
                </div>
                <p class="text-slate-500 text-sm">No notifications yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="glass rounded-2xl p-5 border-l-4 <?php echo $n['is_read'] ? 'border-slate-700' : 'border-orange-500'; ?> relative overflow-hidden">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-sm"><?php echo $n['title']; ?></h3>
                        <span class="text-[9px] text-slate-500"><?php echo date('d M, h:i A', strtotime($n['created_at'])); ?></span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4"><?php echo $n['message']; ?></p>
                    
                    <?php if ($n['tournament_status'] == 'Live'): ?>
                        <div class="bg-orange-500/10 border border-orange-500/20 rounded-xl p-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-slate-500 uppercase">Room ID</span>
                                <span class="text-xs font-mono font-bold text-orange-500"><?php echo $n['room_id']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-slate-500 uppercase">Password</span>
                                <span class="text-xs font-mono font-bold text-orange-500"><?php echo $n['room_password']; ?></span>
                            </div>
                        </div>
                        <a href="my_tournaments.php" class="mt-4 block w-full bg-orange-500 text-white text-center py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-orange-500/20">
                            Join Match Now
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
