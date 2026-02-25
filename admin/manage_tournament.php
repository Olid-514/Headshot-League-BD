<?php
include_once 'common/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: tournament.php");
    exit;
}

$error = '';
$success = '';

// Fetch Tournament
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$id]);
$tournament = $stmt->fetch();

// Fetch Participants
$stmt = $pdo->prepare("SELECT u.id, u.username FROM users u JOIN participants p ON u.id = p.user_id WHERE p.tournament_id = ?");
$stmt->execute([$id]);
$participants = $stmt->fetchAll();

// Handle Room Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_room'])) {
    $room_id = $_POST['room_id'];
    $room_pass = $_POST['room_password'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE tournaments SET room_id = ?, room_password = ?, status = ? WHERE id = ?");
    $stmt->execute([$room_id, $room_pass, $status, $id]);
    $success = "Room details updated!";
    $tournament['room_id'] = $room_id;
    $tournament['room_password'] = $room_pass;
    $tournament['status'] = $status;
}

// Handle Live Notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_notification'])) {
    $room_id = $_POST['room_id'];
    $room_pass = $_POST['room_password'];
    
    try {
        $pdo->beginTransaction();
        
        // Update tournament status to Live
        $stmt = $pdo->prepare("UPDATE tournaments SET room_id = ?, room_password = ?, status = 'Live' WHERE id = ?");
        $stmt->execute([$room_id, $room_pass, $id]);
        
        // Fetch all participants
        $stmt = $pdo->prepare("SELECT user_id FROM participants WHERE tournament_id = ?");
        $stmt->execute([$id]);
        $participants_list = $stmt->fetchAll();
        
        $title = "Tournament is Live";
        $message = "Your tournament is now Live. Room ID: $room_id Password: $room_pass Join Now and Good Luck!";
        
        // Insert notifications
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, tournament_id, title, message) VALUES (?, ?, ?, ?)");
        foreach ($participants_list as $p) {
            $stmt->execute([$p['user_id'], $id, $title, $message]);
        }
        
        $pdo->commit();
        $success = "Live notifications sent to all participants!";
        $tournament['status'] = 'Live';
        $tournament['room_id'] = $room_id;
        $tournament['room_password'] = $room_pass;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to send notifications.";
    }
}

// Handle Winner Declaration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['declare_winner'])) {
    $winner_id = $_POST['winner_id'];
    $prize = $tournament['prize_pool'];
    
    try {
        $pdo->beginTransaction();
        
        // Add prize to winner
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$prize, $winner_id]);
        
        // Add transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Credit', ?)");
        $stmt->execute([$winner_id, $prize, "Won Tournament: " . $tournament['title']]);
        
        // Update tournament status
        $stmt = $pdo->prepare("UPDATE tournaments SET status = 'Completed' WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $success = "Winner declared and prize distributed!";
        $tournament['status'] = 'Completed';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to distribute prize.";
    }
}

?>

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="tournament.php" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
        <h2 class="text-xl font-bold">Manage Match</h2>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-3 rounded-xl text-sm">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-orange-500/20 border border-orange-500 text-orange-500 px-4 py-3 rounded-xl text-sm">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Tournament Info -->
    <div class="glass rounded-3xl p-6 border-l-4 border-red-500">
        <h3 class="font-bold text-lg mb-1"><?php echo $tournament['title']; ?></h3>
        <p class="text-xs text-slate-500 mb-4 uppercase font-bold"><?php echo $tournament['game_name']; ?> • <?php echo $tournament['status']; ?></p>
        
        <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="bg-slate-800/50 p-3 rounded-xl">
                <span class="text-slate-500 block mb-1">Participants</span>
                <span class="font-bold"><?php echo count($participants); ?> Joined</span>
            </div>
            <div class="bg-slate-800/50 p-3 rounded-xl">
                <span class="text-slate-500 block mb-1">Prize Pool</span>
                <span class="font-bold text-orange-500"><?php echo formatCurrency($tournament['prize_pool']); ?></span>
            </div>
        </div>
    </div>

    <?php if ($tournament['status'] != 'Completed'): ?>
        <!-- Room Details Form -->
        <div class="glass rounded-3xl p-6">
            <h3 class="font-bold mb-4">Room Details & Status</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_room" value="1">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Room ID</label>
                        <input type="text" name="room_id" value="<?php echo $tournament['room_id']; ?>" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Password</label>
                        <input type="text" name="room_password" value="<?php echo $tournament['room_password']; ?>" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase mb-1">Match Status</label>
                    <select name="status" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
                        <option value="Upcoming" <?php echo $tournament['status'] == 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="Live" <?php echo $tournament['status'] == 'Live' ? 'selected' : ''; ?>>Live</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition-all text-sm">
                    Update Details
                </button>
                <button type="submit" name="send_notification" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-all text-sm shadow-lg shadow-orange-500/20">
                    <i class="fas fa-paper-plane mr-2"></i> Send Live Notification
                </button>
            </form>
        </div>

        <!-- Winner Declaration -->
        <div class="glass rounded-3xl p-6">
            <h3 class="font-bold mb-4">Declare Winner</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="declare_winner" value="1">
                <div>
                    <label class="block text-[10px] text-slate-500 uppercase mb-1">Select Winner</label>
                    <select name="winner_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
                        <option value="">-- Choose Participant --</option>
                        <?php foreach ($participants as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['username']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" onclick="return confirm('Are you sure? This will distribute the prize and end the match.')" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-orange-500/20">
                    Declare Winner & Pay
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="glass rounded-3xl p-8 text-center border-orange-500/20">
            <i class="fas fa-check-circle text-orange-500 text-4xl mb-3"></i>
            <h3 class="font-bold text-lg">Match Completed</h3>
            <p class="text-slate-500 text-sm">Winner has been declared and prizes distributed.</p>
        </div>
    <?php endif; ?>

    <!-- Participant List -->
    <div class="space-y-4">
        <h3 class="font-bold text-lg">Participants (<?php echo count($participants); ?>)</h3>
        <div class="space-y-2">
            <?php foreach ($participants as $p): ?>
                <div class="glass rounded-xl p-3 flex justify-between items-center text-sm border-white/5">
                    <span class="font-medium"><?php echo $p['username']; ?></span>
                    <span class="text-[10px] text-slate-500">ID: #<?php echo $p['id']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
