<?php
include_once 'common/header.php';

$error = '';
$success = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_tournament'])) {
    $title = $_POST['title'];
    $game_name = $_POST['game_name'];
    $entry_fee = $_POST['entry_fee'];
    $prize_pool = $_POST['prize_pool'];
    $match_time = $_POST['match_time'];
    $commission = $_POST['commission_percentage'];
    $rules = $_POST['rules'];
    $label_name = $_POST['join_form_label_name'];
    $label_uid = $_POST['join_form_label_uid'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tournaments (title, game_name, entry_fee, prize_pool, match_time, commission_percentage, rules, join_form_label_name, join_form_label_uid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $game_name, $entry_fee, $prize_pool, $match_time, $commission, $rules, $label_name, $label_uid]);
        $success = "Tournament created successfully!";
    } catch (PDOException $e) {
        $error = "Error creating tournament.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$id]);
        $success = "Tournament deleted!";
    } catch (Exception $e) {
        $error = "Cannot delete tournament with participants.";
    }
}

// Fetch All
$stmt = $pdo->query("SELECT * FROM tournaments ORDER BY created_at DESC");
$tournaments = $stmt->fetchAll();

?>

<div class="space-y-6">
    <h2 class="text-xl font-bold">Manage Tournaments</h2>

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

    <!-- Create Form -->
    <div class="glass rounded-3xl p-6">
        <h3 class="font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-plus-circle text-red-500"></i> Create New Match
        </h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="create_tournament" value="1">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Match Title</label>
                <input type="text" name="title" required placeholder="e.g. Pro League S1" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Game Name</label>
                    <select name="game_name" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
                        <option value="Free Fire">Free Fire</option>
                        <option value="PUBG Mobile">PUBG Mobile</option>
                        <option value="Call of Duty">Call of Duty</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Commission %</label>
                    <input type="number" name="commission_percentage" value="10" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Entry Fee (৳)</label>
                    <input type="number" name="entry_fee" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Prize Pool (৳)</label>
                    <input type="number" name="prize_pool" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Match Time</label>
                <input type="datetime-local" name="match_time" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Player Name Label</label>
                    <input type="text" name="join_form_label_name" value="Player Name" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Game UID Label</label>
                    <input type="text" name="join_form_label_uid" value="Game UID" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Game Rules</label>
                <textarea name="rules" rows="4" required placeholder="Enter full game rules here..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 transition-colors"></textarea>
            </div>
            <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-red-500/20">
                Launch Tournament
            </button>
        </form>
    </div>

    <!-- Tournament List -->
    <div class="space-y-4">
        <h3 class="font-bold text-lg">Existing Matches</h3>
        <?php foreach ($tournaments as $t): ?>
            <div class="glass rounded-2xl p-4 border border-white/5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-bold text-sm"><?php echo $t['title']; ?></h4>
                        <span class="text-[10px] text-slate-500 uppercase"><?php echo $t['game_name']; ?> • <?php echo $t['status']; ?></span>
                    </div>
                    <div class="flex gap-2">
                        <a href="manage_tournament.php?id=<?php echo $t['id']; ?>" class="w-8 h-8 bg-blue-500/10 text-blue-500 rounded-lg flex items-center justify-center text-xs">
                            <i class="fas fa-tasks"></i>
                        </a>
                        <a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure?')" class="w-8 h-8 bg-red-500/10 text-red-500 rounded-lg flex items-center justify-center text-xs">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400">
                    <span>Entry: <?php echo formatCurrency($t['entry_fee']); ?></span>
                    <span>Prize: <?php echo formatCurrency($t['prize_pool']); ?></span>
                    <span>Time: <?php echo date('d M, h:i A', strtotime($t['match_time'])); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
