<?php
include_once 'common/config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user_id]);
            $success = "Profile updated successfully!";
            // Refresh user data
            $user['username'] = $username;
            $user['email'] = $email;
        } catch (PDOException $e) {
            $error = "Username or Email already taken.";
        }
    } elseif (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        
        if (password_verify($old_pass, $user['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $success = "Password changed successfully!";
        } else {
            $error = "Old password is incorrect.";
        }
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

include_once 'common/header.php';
?>

<div class="space-y-6">
    <h2 class="text-xl font-bold">My Profile</h2>

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

    <!-- Profile Info -->
    <div class="glass rounded-3xl p-6 space-y-6">
        <div class="flex items-center gap-4 pb-6 border-b border-white/5">
            <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/20">
                <i class="fas fa-user text-white text-2xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg"><?php echo $user['username']; ?></h3>
                <p class="text-xs text-slate-500"><?php echo $user['email']; ?></p>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Username</label>
                <input type="text" name="username" value="<?php echo $user['username']; ?>" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Email</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <button type="submit" name="update_profile" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition-all text-sm">
                Update Profile
            </button>
        </form>
    </div>

    <!-- Password Section -->
    <div class="glass rounded-3xl p-6">
        <h3 class="font-bold mb-4">Change Password</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Old Password</label>
                <input type="password" name="old_password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">New Password</label>
                <input type="password" name="new_password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <button type="submit" name="change_password" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition-all text-sm">
                Change Password
            </button>
        </form>
    </div>

    <!-- Logout -->
    <form method="POST">
        <button type="submit" name="logout" class="w-full bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 text-red-500 font-bold py-4 rounded-3xl transition-all flex items-center justify-center gap-2">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </button>
    </form>
</div>

<?php include_once 'common/bottom.php'; ?>
