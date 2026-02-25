<?php
include_once 'common/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'login') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } elseif ($action == 'signup') {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, wallet_balance) VALUES (?, ?, ?, 0)");
                $stmt->execute([$username, $email, $password]);
                $success = "Account created successfully! Please login.";
            } catch (PDOException $e) {
                $error = "Username or Email already exists";
            }
        }
    }
}

include_once 'common/header.php';
?>

<div class="max-w-md mx-auto mt-10">
    <div class="glass rounded-2xl p-6 shadow-xl">
        <div class="flex gap-4 mb-8 border-b border-slate-700">
            <button onclick="switchTab('login')" id="loginTabBtn" class="pb-3 text-lg font-semibold border-b-2 border-orange-500 text-orange-500 transition-all">Login</button>
            <button onclick="switchTab('signup')" id="signupTabBtn" class="pb-3 text-lg font-semibold border-b-2 border-transparent text-slate-400 transition-all">Sign Up</button>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-2 rounded-lg mb-4 text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-orange-500/20 border border-orange-500 text-orange-500 px-4 py-2 rounded-lg mb-4 text-sm">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="loginForm" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Username</label>
                <input type="text" name="username" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-orange-500/20">
                Login
            </button>
        </form>

        <!-- Signup Form -->
        <form id="signupForm" method="POST" class="space-y-4 hidden">
            <input type="hidden" name="action" value="signup">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Username</label>
                <input type="text" name="username" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Email</label>
                <input type="email" name="email" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500 transition-colors">
            </div>
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-colors shadow-lg shadow-orange-500/20">
                Create Account
            </button>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const loginTabBtn = document.getElementById('loginTabBtn');
    const signupTabBtn = document.getElementById('signupTabBtn');

    if (tab === 'login') {
        loginForm.classList.remove('hidden');
        signupForm.classList.add('hidden');
        loginTabBtn.classList.add('border-orange-500', 'text-orange-500');
        loginTabBtn.classList.remove('border-transparent', 'text-slate-400');
        signupTabBtn.classList.add('border-transparent', 'text-slate-400');
        signupTabBtn.classList.remove('border-orange-500', 'text-orange-500');
    } else {
        loginForm.classList.add('hidden');
        signupForm.classList.remove('hidden');
        signupTabBtn.classList.add('border-orange-500', 'text-orange-500');
        signupTabBtn.classList.remove('border-transparent', 'text-slate-400');
        loginTabBtn.classList.add('border-transparent', 'text-slate-400');
        loginTabBtn.classList.remove('border-orange-500', 'text-orange-500');
    }
}
</script>

<?php include_once 'common/bottom.php'; ?>
