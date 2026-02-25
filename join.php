<?php
include_once 'common/config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$joined = false;

// Fetch tournament details
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch();

if (!$tournament) {
    header("Location: index.php");
    exit;
}

// Check if already joined
$stmt = $pdo->prepare("SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?");
$stmt->execute([$user_id, $tournament_id]);
if ($stmt->fetch()) {
    $joined = true;
    $success = "You have already joined this tournament!";
}

// Handle Join Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_join'])) {
    $player_name = $_POST['player_name'];
    $game_uid = $_POST['game_uid'];
    
    if (empty($player_name) || empty($game_uid)) {
        $error = "All fields are required!";
    } else {
        // Get user balance
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_balance = $stmt->fetch()['wallet_balance'];
        
        if ($user_balance >= $tournament['entry_fee']) {
            try {
                $pdo->beginTransaction();
                
                // Deduct balance
                $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt->execute([$tournament['entry_fee'], $user_id]);
                
                // Add participant
                $stmt = $pdo->prepare("INSERT INTO participants (user_id, tournament_id, player_name, game_uid) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $tournament_id, $player_name, $game_uid]);
                
                // Add transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Debit', ?)");
                $stmt->execute([$user_id, $tournament['entry_fee'], "Joined Tournament: " . $tournament['title']]);
                
                $pdo->commit();
                $success = "Successfully joined the tournament!";
                $joined = true;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Something went wrong. Please try again.";
            }
        } else {
            $error = "Insufficient wallet balance!";
        }
    }
}

include_once 'common/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="index.php" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
        <h2 class="text-xl font-bold">Join Tournament</h2>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-3 rounded-xl text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-orange-500/20 border border-orange-500 text-orange-500 px-4 py-3 rounded-xl text-sm">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Tournament Summary -->
    <div class="glass rounded-3xl p-6 border-l-4 border-orange-500">
        <h3 class="font-bold text-lg mb-1"><?php echo $tournament['title']; ?></h3>
        <p class="text-xs text-slate-500 mb-4 uppercase font-bold"><?php echo $tournament['game_name']; ?> • <?php echo date('d M, h:i A', strtotime($tournament['match_time'])); ?></p>
        
        <div class="flex justify-between items-center text-sm">
            <span class="text-slate-400">Entry Fee:</span>
            <span class="font-bold"><?php echo formatCurrency($tournament['entry_fee']); ?></span>
        </div>
    </div>

    <?php if (!$joined): ?>
        <!-- Join Form -->
        <div class="glass rounded-3xl p-6 shadow-xl">
            <h3 class="font-bold mb-6 flex items-center gap-2">
                <i class="fas fa-edit text-orange-500"></i> Player Details
            </h3>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2 uppercase tracking-wider"><?php echo $tournament['join_form_label_name']; ?></label>
                    <input type="text" name="player_name" required placeholder="Enter your in-game name" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-orange-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-2 uppercase tracking-wider"><?php echo $tournament['join_form_label_uid']; ?></label>
                    <input type="text" name="game_uid" required placeholder="Enter your game UID" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-orange-500 transition-colors">
                </div>
                
                <div class="pt-2">
                    <button type="submit" name="confirm_join" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-orange-500/20 active:scale-95">
                        Confirm & Join
                    </button>
                    <p class="text-[10px] text-slate-500 text-center mt-4">By clicking Join, you agree to the game rules below.</p>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Game Rules -->
    <div class="glass rounded-3xl p-6">
        <h3 class="font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-scroll text-yellow-500"></i> 📜 Game Rules
        </h3>
        <div class="bg-slate-800/30 rounded-2xl p-5 border border-white/5">
            <div class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">
                <?php echo !empty($tournament['rules']) ? $tournament['rules'] : "No specific rules provided for this tournament."; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
