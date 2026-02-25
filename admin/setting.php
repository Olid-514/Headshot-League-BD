<?php
include_once 'common/header.php';

$error = '';
$success = '';

// Fetch Admin
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Fetch Settings
$stmt = $pdo->prepare("SELECT * FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_admin'])) {
        $username = $_POST['username'];
        $stmt = $pdo->prepare("UPDATE admin SET username = ? WHERE id = ?");
        $stmt->execute([$username, $admin_id]);
        $success = "Admin info updated!";
        $admin['username'] = $username;
    } elseif (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        
        if (password_verify($old_pass, $admin['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $admin_id]);
            $success = "Password changed!";
        } else {
            $error = "Old password incorrect.";
        }
    } elseif (isset($_POST['update_settings'])) {
        $site_name = $_POST['site_name'];
        $logo_path = $settings['site_logo'];

        // Handle Logo Upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'svg'];
            $filename = $_FILES['site_logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if (!is_dir('../assets')) {
                    mkdir('../assets', 0777, true);
                }
                $new_filename = 'logo_' . time() . '.' . $ext;
                $target = '../assets/' . $new_filename;
                
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target)) {
                    $logo_path = 'assets/' . $new_filename;
                } else {
                    $error = "Failed to upload logo.";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, and SVG allowed.";
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, site_logo = ? WHERE id = ?");
            $stmt->execute([$site_name, $logo_path, $settings['id']]);
            $success = "Settings updated!";
            $settings['site_name'] = $site_name;
            $settings['site_logo'] = $logo_path;
        }
    }
}
?>

<div class="space-y-6">
    <h2 class="text-xl font-bold">Admin Settings</h2>

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

    <div class="glass rounded-3xl p-6 space-y-6">
        <h3 class="font-bold border-b border-white/5 pb-4">Website Management</h3>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="update_settings" value="1">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Website Name</label>
                <input type="text" name="site_name" value="<?php echo $settings['site_name']; ?>" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Website Logo (JPG/PNG/SVG)</label>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-slate-800 rounded-lg flex items-center justify-center overflow-hidden border border-slate-700">
                        <img src="../<?php echo $settings['site_logo']; ?>" alt="Current Logo" class="w-full h-full object-cover">
                    </div>
                    <input type="file" name="site_logo" class="text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                </div>
            </div>
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-all text-sm shadow-lg shadow-orange-500/20">
                Save Website Settings
            </button>
        </form>
    </div>

    <div class="glass rounded-3xl p-6 space-y-6">
        <h3 class="font-bold border-b border-white/5 pb-4">Profile Settings</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="update_admin" value="1">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Admin Username</label>
                <input type="text" name="username" value="<?php echo $admin['username']; ?>" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
            </div>
            <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition-all text-sm">
                Update Admin Info
            </button>
        </form>
    </div>

    <div class="glass rounded-3xl p-6 space-y-6">
        <h3 class="font-bold border-b border-white/5 pb-4">Security</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="change_password" value="1">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Old Password</label>
                <input type="password" name="old_password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">New Password</label>
                <input type="password" name="new_password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500">
            </div>
            <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 rounded-xl transition-all text-sm">
                Change Admin Password
            </button>
        </form>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>
