import express from 'express';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3000;
const db = new Database('headshot.db');

// Initialize Database (SQLite version of the install.php logic)
db.exec(`
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
        bkash_number TEXT DEFAULT NULL,
        nagad_number TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS admin (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS tournaments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        game_name TEXT NOT NULL,
        entry_fee DECIMAL(10, 2) NOT NULL,
        prize_pool DECIMAL(10, 2) NOT NULL,
        match_time DATETIME NOT NULL,
        room_id TEXT DEFAULT NULL,
        room_password TEXT DEFAULT NULL,
        status TEXT DEFAULT 'Upcoming',
        commission_percentage INTEGER DEFAULT 10,
        rules TEXT,
        join_form_label_name TEXT DEFAULT 'Player Name',
        join_form_label_uid TEXT DEFAULT 'Game UID',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS participants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        tournament_id INTEGER NOT NULL,
        player_name TEXT,
        game_uid TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
    );

    CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        type TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        tournament_id INTEGER NOT NULL,
        title TEXT,
        message TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
    );

    CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        site_name TEXT NOT NULL,
        site_logo TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS payment_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bkash_number TEXT DEFAULT NULL,
        bkash_type TEXT DEFAULT NULL,
        nagad_number TEXT DEFAULT NULL,
        nagad_type TEXT DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS deposits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_id TEXT NOT NULL,
        payment_method TEXT NOT NULL, -- 'bkash' or 'nagad'
        status TEXT DEFAULT 'Pending', -- 'Pending', 'Approved', 'Rejected'
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS withdrawals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method TEXT NOT NULL, -- 'bkash' or 'nagad'
        status TEXT DEFAULT 'Pending', -- 'Pending', 'Completed', 'Rejected'
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
`);

// Default Settings
const settingsExists = db.prepare('SELECT id FROM settings LIMIT 1').get();
if (!settingsExists) {
    db.prepare('INSERT INTO settings (site_name, site_logo) VALUES (?, ?)').run('Headshot League BD', '/assets/logo.png');
}

// Default Payment Settings
const paymentSettingsExists = db.prepare('SELECT id FROM payment_settings LIMIT 1').get();
if (!paymentSettingsExists) {
    db.prepare('INSERT INTO payment_settings (bkash_number, bkash_type, nagad_number, nagad_type) VALUES (?, ?, ?, ?)').run('01700000000', 'Personal', '01800000000', 'Personal');
}

// Default Admin
const adminExists = db.prepare('SELECT id FROM admin WHERE username = ?').get('admin');
if (!adminExists) {
    db.prepare('INSERT INTO admin (username, password) VALUES (?, ?)').run('admin', 'admin123'); // In real app, use bcrypt
}

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// Simple Session Mock (since we don't have express-session installed)
const sessions: Record<string, any> = {};
app.use((req, res, next) => {
    const sessionId = req.headers.cookie?.split('; ').find(row => row.startsWith('session='))?.split('=')[1] || 'default';
    if (!sessions[sessionId]) sessions[sessionId] = {};
    (req as any).session = sessions[sessionId];
    next();
});

// Helper for currency
const formatCurrency = (amount: number) => '৳' + Number(amount).toFixed(2);

// UI Components (Mocking PHP includes)
const getHeader = (session: any) => {
    const userId = session.userId;
    let walletBalance = 0;
    let unreadCount = 0;
    if (userId) {
        const user = db.prepare('SELECT wallet_balance FROM users WHERE id = ?').get(userId) as any;
        walletBalance = user?.wallet_balance || 0;
        const count = db.prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0').get(userId) as any;
        unreadCount = count?.count || 0;
    }

    const settings = db.prepare('SELECT * FROM settings LIMIT 1').get() as any;
    const siteName = settings?.site_name || 'Headshot League BD';
    const siteLogo = settings?.site_logo || '/assets/logo.png';

    return `
    <!DOCTYPE html>
    <html lang="en" class="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>${siteName}</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
            body {
                font-family: 'Inter', sans-serif;
                background-color: #0f172a;
                color: #f8fafc;
                overflow-x: hidden;
                -webkit-tap-highlight-color: transparent;
            }
            .glass {
                background: rgba(30, 41, 59, 0.7);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            @keyframes pulse-soft {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            .animate-pulse-soft {
                animation: pulse-soft 2s infinite;
            }
        </style>
    </head>
    <body class="select-none">
        ${userId && unreadCount > 0 ? `
        <div class="fixed top-20 left-4 right-4 z-[60] bg-orange-500 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg flex justify-between items-center animate-bounce">
            <span><i class="fas fa-bell mr-2"></i> You have new notifications</span>
            <a href="/notifications" class="underline">View</a>
        </div>
        ` : ''}
        <header class="fixed top-0 left-0 right-0 z-50 glass px-4 py-3 flex justify-between items-center shadow-lg">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center overflow-hidden">
                    <img src="${siteLogo}" alt="Logo" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
                    <i class="fas fa-crosshairs text-white logo-fallback hidden"></i>
                </div>
                <h1 class="font-bold text-lg tracking-tight">${siteName.split(' ').map((word, i) => i === siteName.split(' ').length - 1 ? `<span class="text-orange-500">${word}</span>` : word).join(' ')}</h1>
            </div>
            ${userId ? `
            <div class="flex items-center gap-3">
                <a href="/notifications" class="relative w-10 h-10 bg-slate-800/50 rounded-full flex items-center justify-center border border-slate-700">
                    <i class="fas fa-bell text-slate-400"></i>
                    ${unreadCount > 0 ? `<span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center border-2 border-[#0f172a]">${unreadCount}</span>` : ''}
                </a>
                <div class="flex items-center gap-3 bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-700">
                    <i class="fas fa-wallet text-orange-500 text-sm"></i>
                    <span class="font-semibold text-sm">${formatCurrency(walletBalance)}</span>
                </div>
            </div>
            ` : `<a href="/login" class="text-sm font-medium text-orange-500">Login</a>`}
        </header>
        <main class="pt-20 pb-24 px-4 min-h-screen">
    `;
};

const getBottom = (active: string) => {
    return `
        </main>
        <a href="https://wa.me/8801308492029?text=Hello,%20HL%20BD%20Support,%20I%20need%20help%20🥺." 
           target="_blank"
           class="fixed bottom-24 right-6 z-50 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform animate-pulse-soft border-2 border-white/20">
            <i class="fab fa-whatsapp text-white text-3xl"></i>
        </a>
        <nav class="fixed bottom-0 left-0 right-0 z-50 glass border-t border-white/5 px-6 py-3 flex justify-between items-center shadow-[0_-4px_20px_rgba(0,0,0,0.3)]">
            <a href="/" class="flex flex-col items-center gap-1 ${active === 'home' ? 'text-orange-500' : 'text-slate-400'}">
                <i class="fas fa-home text-xl"></i>
                <span class="text-[10px] font-medium">Home</span>
            </a>
            <a href="/my-tournaments" class="flex flex-col items-center gap-1 ${active === 'matches' ? 'text-orange-500' : 'text-slate-400'}">
                <i class="fas fa-trophy text-xl"></i>
                <span class="text-[10px] font-medium">Matches</span>
            </a>
            <a href="/wallet" class="flex flex-col items-center gap-1 ${active === 'wallet' ? 'text-orange-500' : 'text-slate-400'}">
                <i class="fas fa-wallet text-xl"></i>
                <span class="text-[10px] font-medium">Wallet</span>
            </a>
            <a href="/profile" class="flex flex-col items-center gap-1 ${active === 'profile' ? 'text-orange-500' : 'text-slate-400'}">
                <i class="fas fa-user text-xl"></i>
                <span class="text-[10px] font-medium">Profile</span>
            </a>
        </nav>
        <script>
            document.addEventListener('contextmenu', event => event.preventDefault());
            document.body.style.userSelect = 'none';
        </script>
    </body>
    </html>
    `;
};

// Routes
app.get('/', (req, res) => {
    const tournaments = db.prepare("SELECT * FROM tournaments WHERE status = 'Upcoming' ORDER BY match_time ASC").all() as any[];
    let html = getHeader((req as any).session);
    
    html += `
    <div class="space-y-6">
        <div class="glass rounded-3xl p-6 relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-2xl font-bold mb-2">Welcome to <span class="text-orange-500">Headshot League BD</span></h2>
                <p class="text-slate-400 text-sm">Join tournaments, win matches, and earn real rewards.</p>
            </div>
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-orange-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="space-y-4">
            <h3 class="font-bold text-lg">Upcoming Matches</h3>
            ${tournaments.length === 0 ? `
                <div class="glass rounded-2xl p-8 text-center">
                    <p class="text-slate-400 text-sm">No upcoming tournaments found.</p>
                </div>
            ` : tournaments.map(t => `
                <div class="glass rounded-2xl overflow-hidden border border-white/5">
                    <div class="bg-slate-800/50 px-4 py-2 border-b border-white/5 flex justify-between items-center">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-orange-500">${t.game_name}</span>
                        <span class="text-[10px] text-slate-400"><i class="far fa-clock mr-1"></i> ${new Date(t.match_time).toLocaleString()}</span>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-lg mb-4">${t.title}</h4>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-slate-800/30 p-3 rounded-xl">
                                <span class="block text-[10px] text-slate-500 uppercase mb-1">Prize Pool</span>
                                <span class="font-bold text-orange-500">${formatCurrency(t.prize_pool)}</span>
                            </div>
                            <div class="bg-slate-800/30 p-3 rounded-xl">
                                <span class="block text-[10px] text-slate-500 uppercase mb-1">Entry Fee</span>
                                <span class="font-bold">${formatCurrency(t.entry_fee)}</span>
                            </div>
                        </div>
                        <a href="/join/${t.id}" class="w-full bg-orange-500 text-white text-center font-bold py-3 rounded-xl shadow-lg shadow-orange-500/20">Join Now</a>
                    </div>
                </div>
            `).join('')}
        </div>
    </div>
    `;
    
    html += getBottom('home');
    res.send(html);
});

app.get('/login', (req, res) => {
    let html = getHeader((req as any).session);
    html += `
    <div class="max-w-md mx-auto mt-10">
        <div class="glass rounded-2xl p-6 shadow-xl">
            <div class="flex gap-4 mb-8 border-b border-slate-700">
                <button onclick="switchTab('login')" id="loginTabBtn" class="pb-3 text-lg font-semibold border-b-2 border-orange-500 text-orange-500">Login</button>
                <button onclick="switchTab('signup')" id="signupTabBtn" class="pb-3 text-lg font-semibold border-b-2 border-transparent text-slate-400">Sign Up</button>
            </div>
            <form id="loginForm" action="/auth/login" method="POST" class="space-y-4">
                <input type="text" name="username" placeholder="Username" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm">
                <input type="password" name="password" placeholder="Password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm">
                <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 rounded-xl">Login</button>
            </form>
            <form id="signupForm" action="/auth/signup" method="POST" class="space-y-4 hidden">
                <input type="text" name="username" placeholder="Username" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm">
                <input type="email" name="email" placeholder="Email" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm">
                <input type="password" name="password" placeholder="Password" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm">
                <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 rounded-xl">Create Account</button>
            </form>
        </div>
    </div>
    <script>
        function switchTab(tab) {
            document.getElementById('loginForm').classList.toggle('hidden', tab === 'signup');
            document.getElementById('signupForm').classList.toggle('hidden', tab === 'login');
            document.getElementById('loginTabBtn').className = tab === 'login' ? 'pb-3 text-lg font-semibold border-b-2 border-orange-500 text-orange-500' : 'pb-3 text-lg font-semibold border-b-2 border-transparent text-slate-400';
            document.getElementById('signupTabBtn').className = tab === 'signup' ? 'pb-3 text-lg font-semibold border-b-2 border-orange-500 text-orange-500' : 'pb-3 text-lg font-semibold border-b-2 border-transparent text-slate-400';
        }
    </script>
    `;
    html += getBottom('profile');
    res.send(html);
});

app.post('/auth/signup', (req, res) => {
    const { username, email, password } = req.body;
    try {
        db.prepare('INSERT INTO users (username, email, password, wallet_balance) VALUES (?, ?, ?, ?)').run(username, email, password, 100); // Give 100 bonus for demo
        res.redirect('/login');
    } catch (e) {
        res.send('Signup failed. Username or Email exists.');
    }
});

app.post('/auth/login', (req, res) => {
    const { username, password } = req.body;
    const user = db.prepare('SELECT id FROM users WHERE username = ? AND password = ?').get(username, password) as any;
    if (user) {
        (req as any).session.userId = user.id;
        res.redirect('/');
    } else {
        res.send('Invalid credentials');
    }
});

app.get('/join/:id', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const t = db.prepare('SELECT * FROM tournaments WHERE id = ?').get(req.params.id) as any;
    if (!t) return res.redirect('/');
    
    const joined = db.prepare('SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?').get(userId, t.id);
    
    let html = getHeader((req as any).session);
    html += `
    <div class="space-y-6">
        <div class="glass rounded-3xl p-6 border-l-4 border-orange-500">
            <h3 class="font-bold text-lg">${t.title}</h3>
            <p class="text-xs text-slate-500">${t.game_name} | ${formatCurrency(t.entry_fee)}</p>
        </div>

        ${joined ? `
            <div class="bg-orange-500/20 border border-orange-500 text-orange-500 p-4 rounded-2xl text-sm">
                Already joined! See rules below.
            </div>
        ` : `
            <div class="glass rounded-3xl p-6">
                <h4 class="font-bold mb-4">Join Tournament</h4>
                <form action="/join" method="POST" class="space-y-4">
                    <input type="hidden" name="tournamentId" value="${t.id}">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">${t.join_form_label_name}</label>
                        <input type="text" name="playerName" required class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">${t.join_form_label_uid}</label>
                        <input type="text" name="gameUid" required class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                    <button type="submit" class="w-full bg-orange-500 py-4 rounded-xl font-bold shadow-lg">Confirm Join</button>
                </form>
            </div>
        `}

        <div class="glass rounded-3xl p-6">
            <h4 class="font-bold mb-4">📜 Game Rules</h4>
            <div class="text-xs text-slate-400 whitespace-pre-line leading-relaxed">
                ${t.rules || 'No specific rules.'}
            </div>
        </div>
    </div>
    `;
    html += getBottom('home');
    res.send(html);
});

app.post('/join', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const { tournamentId, playerName, gameUid } = req.body;
    const tournament = db.prepare('SELECT * FROM tournaments WHERE id = ?').get(tournamentId) as any;
    const user = db.prepare('SELECT wallet_balance FROM users WHERE id = ?').get(userId) as any;
    
    const alreadyJoined = db.prepare('SELECT id FROM participants WHERE user_id = ? AND tournament_id = ?').get(userId, tournamentId);
    if (alreadyJoined) return res.redirect('/join/' + tournamentId);

    if (user.wallet_balance >= tournament.entry_fee) {
        db.transaction(() => {
            db.prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?').run(tournament.entry_fee, userId);
            db.prepare('INSERT INTO participants (user_id, tournament_id, player_name, game_uid) VALUES (?, ?, ?, ?)').run(userId, tournamentId, playerName, gameUid);
            db.prepare('INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)').run(userId, tournament.entry_fee, 'Debit', 'Joined ' + tournament.title);
        })();
        res.redirect('/join/' + tournamentId);
    } else {
        res.send('Insufficient balance');
    }
});

app.get('/wallet', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const user = db.prepare('SELECT * FROM users WHERE id = ?').get(userId) as any;
    const transactions = db.prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC').all(userId) as any[];
    const paymentSettings = db.prepare('SELECT * FROM payment_settings LIMIT 1').get() as any;
    
    let html = getHeader((req as any).session);
    html += `
    <div class="space-y-6">
        <h2 class="text-xl font-bold">My Wallet</h2>
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-3xl p-8 shadow-2xl">
            <span class="text-orange-100/70 text-sm block mb-1">Available Balance</span>
            <h3 class="text-4xl font-black text-white">${formatCurrency(user.wallet_balance)}</h3>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <button onclick="openModal('depositModal')" class="glass rounded-2xl p-4 flex flex-col items-center gap-2">
                <i class="fas fa-plus text-orange-500"></i>
                <span class="text-sm font-bold">Add Money</span>
            </button>
            <button onclick="openModal('withdrawModal')" class="glass rounded-2xl p-4 flex flex-col items-center gap-2">
                <i class="fas fa-arrow-up text-red-500"></i>
                <span class="text-sm font-bold">Withdraw</span>
            </button>
        </div>
        <div class="space-y-3">
            <h3 class="font-bold text-lg">Transactions</h3>
            ${transactions.length === 0 ? '<p class="text-center text-slate-500 text-xs py-4">No transactions yet.</p>' : transactions.map(tx => `
                <div class="glass rounded-2xl p-4 flex justify-between items-center">
                    <div>
                        <h4 class="text-sm font-bold">${tx.description}</h4>
                        <span class="text-[10px] text-slate-500">${new Date(tx.created_at).toLocaleString()}</span>
                    </div>
                    <span class="font-bold ${tx.type === 'Credit' ? 'text-orange-500' : 'text-red-500'}">
                        ${tx.type === 'Credit' ? '+' : '-'}${formatCurrency(tx.amount)}
                    </span>
                </div>
            `).join('')}
        </div>
    </div>

    <!-- Deposit Modal -->
    <div id="depositModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-end sm:items-center justify-center p-4">
        <div class="bg-slate-900 w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 space-y-6 border-t sm:border border-white/10">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Add Money</h3>
                <button onclick="closeModal('depositModal')" class="text-slate-500"><i class="fas fa-times"></i></button>
            </div>
            
            <div id="depositStep1" class="space-y-4">
                <p class="text-sm text-slate-400">Select payment method:</p>
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="selectMethod('bkash')" class="bg-slate-800 p-6 rounded-2xl border border-white/5 flex flex-col items-center gap-3 hover:border-orange-500 transition-colors">
                        <i class="fas fa-mobile-alt text-3xl text-orange-500"></i>
                        <span class="font-bold">bKash</span>
                    </button>
                    <button onclick="selectMethod('nagad')" class="bg-slate-800 p-6 rounded-2xl border border-white/5 flex flex-col items-center gap-3 hover:border-red-500 transition-colors">
                        <i class="fas fa-mobile-alt text-3xl text-red-500"></i>
                        <span class="font-bold">Nagad</span>
                    </button>
                </div>
            </div>

            <div id="depositStep2" class="hidden space-y-4">
                <div class="bg-slate-800 p-4 rounded-2xl space-y-2">
                    <p class="text-[10px] text-slate-500 uppercase">Send Money to:</p>
                    <div class="flex justify-between items-center">
                        <span id="adminNumber" class="text-xl font-black text-white tracking-widest"></span>
                        <span id="adminType" class="text-[10px] bg-orange-500/20 text-orange-500 px-2 py-1 rounded font-bold"></span>
                    </div>
                    <p class="text-[10px] text-slate-400 italic">Send money to the number above. After successful payment, submit your Transaction ID below.</p>
                </div>

                <form action="/deposit" method="POST" class="space-y-4">
                    <input type="hidden" name="method" id="depositMethodInput">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Amount Sent (৳)</label>
                        <input type="number" name="amount" required placeholder="Min 10" class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Transaction ID</label>
                        <input type="text" name="transactionId" required placeholder="Enter TrxID" class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                    <button type="submit" class="w-full bg-orange-500 py-4 rounded-xl font-bold shadow-lg">Submit Request</button>
                </form>
                <button onclick="backToStep1()" class="w-full text-slate-500 text-sm">Back</button>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div id="withdrawModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-end sm:items-center justify-center p-4">
        <div class="bg-slate-900 w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 space-y-6 border-t sm:border border-white/10">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold">Withdraw Money</h3>
                <button onclick="closeModal('withdrawModal')" class="text-slate-500"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="/withdraw" method="POST" class="space-y-6">
                <div class="bg-slate-800 p-4 rounded-2xl">
                    <p class="text-[10px] text-slate-500 uppercase">Available Balance</p>
                    <p class="text-2xl font-black text-orange-500">${formatCurrency(user.wallet_balance)}</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Amount to Withdraw (৳)</label>
                        <input type="number" name="amount" required placeholder="Min 50" class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Select Method</label>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <label class="relative">
                                <input type="radio" name="method" value="bkash" required class="peer hidden">
                                <div class="bg-slate-800 p-3 rounded-xl border border-white/5 text-center peer-checked:border-orange-500 peer-checked:bg-orange-500/10 cursor-pointer">
                                    <span class="text-xs font-bold">bKash</span>
                                    <p class="text-[8px] text-slate-500">${user.bkash_number || 'Not set'}</p>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="method" value="nagad" required class="peer hidden">
                                <div class="bg-slate-800 p-3 rounded-xl border border-white/5 text-center peer-checked:border-red-500 peer-checked:bg-red-500/10 cursor-pointer">
                                    <span class="text-xs font-bold">Nagad</span>
                                    <p class="text-[8px] text-slate-500">${user.nagad_number || 'Not set'}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                ${(!user.bkash_number && !user.nagad_number) ? `
                    <div class="bg-red-500/10 text-red-500 p-3 rounded-xl text-[10px] text-center">
                        Please save your payment numbers in <a href="/profile" class="underline font-bold">Profile</a> first.
                    </div>
                ` : `
                    <button type="submit" class="w-full bg-orange-500 py-4 rounded-xl font-bold shadow-lg">Submit Withdrawal</button>
                `}
            </form>
        </div>
    </div>

    <script>
        const paymentData = {
            bkash: { number: '${paymentSettings.bkash_number}', type: '${paymentSettings.bkash_type}' },
            nagad: { number: '${paymentSettings.nagad_number}', type: '${paymentSettings.nagad_type}' }
        };

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
        function selectMethod(method) {
            document.getElementById('depositStep1').classList.add('hidden');
            document.getElementById('depositStep2').classList.remove('hidden');
            document.getElementById('adminNumber').innerText = paymentData[method].number;
            document.getElementById('adminType').innerText = paymentData[method].type;
            document.getElementById('depositMethodInput').value = method;
        }
        function backToStep1() {
            document.getElementById('depositStep1').classList.remove('hidden');
            document.getElementById('depositStep2').classList.add('hidden');
        }
    </script>
    `;
    html += getBottom('wallet');
    res.send(html);
});

app.post('/deposit', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const { amount, transactionId, method } = req.body;
    if (amount < 10) return res.send('Minimum deposit is 10 BDT');
    
    db.prepare('INSERT INTO deposits (user_id, amount, transaction_id, payment_method) VALUES (?, ?, ?, ?)').run(userId, amount, transactionId, method);
    res.redirect('/wallet');
});

app.post('/withdraw', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const { amount, method } = req.body;
    if (amount < 50) return res.send('Minimum withdrawal is 50 BDT');
    
    const user = db.prepare('SELECT wallet_balance, bkash_number, nagad_number FROM users WHERE id = ?').get(userId) as any;
    if (user.wallet_balance < amount) return res.send('Insufficient balance');
    
    const targetNumber = method === 'bkash' ? user.bkash_number : user.nagad_number;
    if (!targetNumber) return res.send('Payment number not set for ' + method);
    
    db.prepare('INSERT INTO withdrawals (user_id, amount, payment_method) VALUES (?, ?, ?)').run(userId, amount, method);
    res.redirect('/wallet');
});

app.get('/my-tournaments', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const matches = db.prepare(`
        SELECT t.* FROM tournaments t 
        JOIN participants p ON t.id = p.tournament_id 
        WHERE p.user_id = ?
    `).all(userId) as any[];
    
    let html = getHeader((req as any).session);
    html += `
    <div class="space-y-6">
        <h2 class="text-xl font-bold">My Matches</h2>
        <div class="space-y-4">
            ${matches.length === 0 ? '<p class="text-center text-slate-500">No matches joined yet.</p>' : matches.map(m => `
                <div class="glass rounded-2xl p-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-[10px] font-bold text-orange-500 uppercase">${m.game_name}</span>
                        <span class="text-[10px] uppercase font-bold text-slate-400">${m.status}</span>
                    </div>
                    <h4 class="font-bold text-lg mb-2">${m.title}</h4>
                    <p class="text-xs text-slate-400 mb-4">${new Date(m.match_time).toLocaleString()}</p>
                    ${m.status === 'Live' ? `
                        <div class="bg-orange-500/10 p-3 rounded-xl">
                            <p class="text-xs text-slate-400">Room ID: <span class="text-orange-500 font-bold">${m.room_id || 'Wait...'}</span></p>
                            <p class="text-xs text-slate-400">Pass: <span class="text-orange-500 font-bold">${m.room_password || 'Wait...'}</span></p>
                        </div>
                    ` : ''}
                </div>
            `).join('')}
        </div>
    </div>
    `;
    html += getBottom('matches');
    res.send(html);
});

app.get('/notifications', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    db.prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?').run(userId);
    const notifications = db.prepare(`
        SELECT n.*, t.room_id, t.room_password, t.status as tournament_status 
        FROM notifications n 
        JOIN tournaments t ON n.tournament_id = t.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC
    `).all(userId) as any[];
    
    let html = getHeader((req as any).session);
    html += `
    <div class="space-y-6">
        <h2 class="text-xl font-bold">Notifications</h2>
        <div class="space-y-4">
            ${notifications.length === 0 ? '<p class="text-center text-slate-500">No notifications yet.</p>' : notifications.map(n => `
                <div class="glass rounded-2xl p-5 border-l-4 ${n.is_read ? 'border-slate-700' : 'border-orange-500'}">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-sm">${n.title}</h3>
                        <span class="text-[9px] text-slate-500">${new Date(n.created_at).toLocaleString()}</span>
                    </div>
                    <p class="text-xs text-slate-400 mb-4">${n.message}</p>
                    ${n.tournament_status === 'Live' ? `
                        <div class="bg-orange-500/10 p-3 rounded-xl space-y-1">
                            <p class="text-[10px] text-slate-500">Room ID: <span class="text-orange-500 font-bold">${n.room_id}</span></p>
                            <p class="text-[10px] text-slate-500">Pass: <span class="text-orange-500 font-bold">${n.room_password}</span></p>
                        </div>
                    ` : ''}
                </div>
            `).join('')}
        </div>
    </div>
    `;
    html += getBottom('profile');
    res.send(html);
});

app.get('/profile', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const user = db.prepare('SELECT * FROM users WHERE id = ?').get(userId) as any;
    
    let html = getHeader((req as any).session);
    html += `
    <div class="space-y-6">
        <h2 class="text-xl font-bold">Profile</h2>
        <div class="glass rounded-3xl p-6 space-y-6">
            <div class="flex items-center gap-4 pb-6 border-b border-white/5">
                <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-lg">${user.username}</h3>
                    <p class="text-xs text-slate-500">${user.email}</p>
                </div>
            </div>
            
            <form action="/profile/update" method="POST" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">bKash Number</label>
                        <input type="text" name="bkash_number" value="${user.bkash_number || ''}" placeholder="017XXXXXXXX" class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Nagad Number</label>
                        <input type="text" name="nagad_number" value="${user.nagad_number || ''}" placeholder="01XXXXXXXXX" class="w-full bg-slate-800 p-3 rounded-xl mt-1">
                    </div>
                </div>
                <button type="submit" class="w-full bg-orange-500 py-4 rounded-xl font-bold">Save Numbers</button>
            </form>

            <a href="/logout" class="block w-full bg-red-500/10 text-red-500 font-bold py-4 rounded-3xl text-center">Logout</a>
        </div>
    </div>
    `;
    html += getBottom('profile');
    res.send(html);
});

app.post('/profile/update', (req, res) => {
    const userId = (req as any).session.userId;
    if (!userId) return res.redirect('/login');
    
    const { bkash_number, nagad_number } = req.body;
    db.prepare('UPDATE users SET bkash_number = ?, nagad_number = ? WHERE id = ?').run(bkash_number, nagad_number, userId);
    res.redirect('/profile');
});

app.get('/logout', (req, res) => {
    (req as any).session.userId = null;
    res.redirect('/login');
});

// Admin Routes
app.get('/admin', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.redirect('/admin/login');
    
    const stats = {
        users: db.prepare('SELECT COUNT(*) as count FROM users').get() as any,
        tournaments: db.prepare('SELECT COUNT(*) as count FROM tournaments').get() as any,
        prize: db.prepare("SELECT SUM(prize_pool) as sum FROM tournaments WHERE status = 'Completed'").get() as any
    };

    let html = `
    <!DOCTYPE html>
    <html lang="en" class="dark">
    <head>
        <meta charset="UTF-8"><title>Admin Panel</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-slate-900 text-slate-100 p-4">
        <div class="max-w-md mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">Admin Panel</h1>
                <div class="flex gap-4 items-center">
                    <a href="/admin/settings" class="text-slate-400 hover:text-white"><i class="fas fa-cog"></i></a>
                    <a href="/admin/logout" class="text-red-500">Logout</a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-800 p-4 rounded-2xl">
                    <p class="text-xs text-slate-500">Users</p>
                    <p class="text-2xl font-bold">${stats.users.count}</p>
                </div>
                <div class="bg-slate-800 p-4 rounded-2xl">
                    <p class="text-xs text-slate-500">Matches</p>
                    <p class="text-2xl font-bold">${stats.tournaments.count}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <a href="/admin/deposit-requests" class="bg-slate-800 p-4 rounded-2xl flex justify-between items-center hover:bg-slate-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-money-bill-wave text-orange-500"></i>
                        <span class="font-bold">Deposit Requests</span>
                    </div>
                    <i class="fas fa-chevron-right text-slate-500"></i>
                </a>
                <a href="/admin/withdrawal-requests" class="bg-slate-800 p-4 rounded-2xl flex justify-between items-center hover:bg-slate-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-hand-holding-usd text-red-500"></i>
                        <span class="font-bold">Withdrawal Requests</span>
                    </div>
                    <i class="fas fa-chevron-right text-slate-500"></i>
                </a>
                <a href="/admin/payment-settings" class="bg-slate-800 p-4 rounded-2xl flex justify-between items-center hover:bg-slate-700 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-university text-blue-500"></i>
                        <span class="font-bold">Payment Settings</span>
                    </div>
                    <i class="fas fa-chevron-right text-slate-500"></i>
                </a>
            </div>
            <div class="bg-slate-800 p-6 rounded-3xl">
                <h2 class="font-bold mb-4">Create Tournament</h2>
                <form action="/admin/tournament/create" method="POST" class="space-y-4">
                    <input type="text" name="title" placeholder="Title" required class="w-full bg-slate-700 p-3 rounded-xl">
                    <input type="text" name="game" placeholder="Game" required class="w-full bg-slate-700 p-3 rounded-xl">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="label_name" placeholder="Name Label" value="Player Name" class="w-full bg-slate-700 p-3 rounded-xl text-xs">
                        <input type="text" name="label_uid" placeholder="UID Label" value="Game UID" class="w-full bg-slate-700 p-3 rounded-xl text-xs">
                    </div>
                    <textarea name="rules" placeholder="Rules" class="w-full bg-slate-700 p-3 rounded-xl text-xs h-20"></textarea>
                    <input type="number" name="fee" placeholder="Entry Fee" required class="w-full bg-slate-700 p-3 rounded-xl">
                    <input type="number" name="prize" placeholder="Prize Pool" required class="w-full bg-slate-700 p-3 rounded-xl">
                    <input type="datetime-local" name="time" required class="w-full bg-slate-700 p-3 rounded-xl">
                    <button type="submit" class="w-full bg-red-500 py-3 rounded-xl font-bold">Launch</button>
                </form>
            </div>
            <div class="space-y-3">
                <h2 class="font-bold">Manage Matches</h2>
                ${(db.prepare('SELECT * FROM tournaments ORDER BY created_at DESC').all() as any[]).map(t => `
                    <div class="bg-slate-800 p-4 rounded-2xl flex justify-between items-center">
                        <div>
                            <p class="font-bold">${t.title}</p>
                            <p class="text-[10px] text-slate-500">${t.status}</p>
                        </div>
                        <a href="/admin/tournament/manage/${t.id}" class="text-blue-500 text-xs">Manage</a>
                    </div>
                `).join('')}
            </div>
        </div>
    </body>
    </html>
    `;
    res.send(html);
});

app.get('/admin/login', (req, res) => {
    res.send(`
    <body class="bg-slate-900 text-white flex items-center justify-center h-screen">
        <form action="/admin/auth/login" method="POST" class="bg-slate-800 p-8 rounded-3xl w-80 space-y-4">
            <h1 class="text-xl font-bold text-center">Admin Login</h1>
            <input type="text" name="username" placeholder="Admin Username" class="w-full bg-slate-700 p-3 rounded-xl">
            <input type="password" name="password" placeholder="Password" class="w-full bg-slate-700 p-3 rounded-xl">
            <button class="w-full bg-red-500 py-3 rounded-xl font-bold">Login</button>
        </form>
    </body>
    `);
});

app.post('/admin/auth/login', (req, res) => {
    const { username, password } = req.body;
    const admin = db.prepare('SELECT id FROM admin WHERE username = ? AND password = ?').get(username, password) as any;
    if (admin) {
        (req as any).session.adminId = admin.id;
        res.redirect('/admin');
    } else {
        res.send('Invalid admin credentials');
    }
});

app.post('/admin/settings/update', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.status(403).send('Unauthorized');
    
    const { site_name } = req.body;
    // Note: In this mock, we don't handle file uploads for the logo, 
    // but we update the site name.
    db.prepare('UPDATE settings SET site_name = ? WHERE id = 1').run(site_name);
    res.redirect('/admin/settings');
});

app.post('/admin/tournament/create', (req, res) => {
    const { title, game, fee, prize, time, rules, label_name, label_uid } = req.body;
    db.prepare('INSERT INTO tournaments (title, game_name, entry_fee, prize_pool, match_time, rules, join_form_label_name, join_form_label_uid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)').run(title, game, fee, prize, time, rules, label_name, label_uid);
    res.redirect('/admin');
});

app.get('/admin/tournament/manage/:id', (req, res) => {
    const t = db.prepare('SELECT * FROM tournaments WHERE id = ?').get(req.params.id) as any;
    const participants = db.prepare('SELECT u.username, p.player_name, p.game_uid FROM users u JOIN participants p ON u.id = p.user_id WHERE p.tournament_id = ?').all(req.params.id) as any[];
    
    res.send(`
    <body class="bg-slate-900 text-white p-4">
        <div class="max-w-md mx-auto space-y-6">
            <h1 class="text-xl font-bold">${t.title}</h1>
            <form action="/admin/tournament/update/${t.id}" method="POST" class="bg-slate-800 p-6 rounded-3xl space-y-4">
                <input type="text" name="room_id" placeholder="Room ID" value="${t.room_id || ''}" class="w-full bg-slate-700 p-3 rounded-xl">
                <input type="text" name="room_pass" placeholder="Room Pass" value="${t.room_password || ''}" class="w-full bg-slate-700 p-3 rounded-xl">
                
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="label_name" placeholder="Name Label" value="${t.join_form_label_name}" class="w-full bg-slate-700 p-3 rounded-xl text-xs">
                    <input type="text" name="label_uid" placeholder="UID Label" value="${t.join_form_label_uid}" class="w-full bg-slate-700 p-3 rounded-xl text-xs">
                </div>
                <textarea name="rules" placeholder="Rules" class="w-full bg-slate-700 p-3 rounded-xl text-xs h-24">${t.rules || ''}</textarea>

                <select name="status" class="w-full bg-slate-700 p-3 rounded-xl">
                    <option value="Upcoming" ${t.status === 'Upcoming' ? 'selected' : ''}>Upcoming</option>
                    <option value="Live" ${t.status === 'Live' ? 'selected' : ''}>Live</option>
                    <option value="Completed" ${t.status === 'Completed' ? 'selected' : ''}>Completed</option>
                </select>
                <button class="w-full bg-blue-500 py-3 rounded-xl font-bold">Update</button>
                <button name="send_notification" value="1" class="w-full bg-orange-500 py-3 rounded-xl font-bold">Send Live Notification</button>
            </form>
            <div class="space-y-2">
                <h2 class="font-bold">Participants (${participants.length})</h2>
                ${participants.map(p => `
                    <div class="bg-slate-800 p-3 rounded-xl">
                        <p class="font-bold">${p.username}</p>
                        <p class="text-[10px] text-slate-500">${p.player_name} | ${p.game_uid}</p>
                    </div>
                `).join('')}
            </div>
            <a href="/admin" class="block text-center text-slate-500">Back</a>
        </div>
    </body>
    `);
});

app.post('/admin/tournament/update/:id', (req, res) => {
    const { room_id, room_pass, status, send_notification, rules, label_name, label_uid } = req.body;
    db.prepare('UPDATE tournaments SET room_id = ?, room_password = ?, status = ?, rules = ?, join_form_label_name = ?, join_form_label_uid = ? WHERE id = ?').run(room_id, room_pass, status, rules, label_name, label_uid, req.params.id);
    
    if (send_notification) {
        const participants = db.prepare('SELECT user_id FROM participants WHERE tournament_id = ?').all(req.params.id) as any[];
        const tournament = db.prepare('SELECT title FROM tournaments WHERE id = ?').get(req.params.id) as any;
        const title = "Tournament is Live";
        const message = `Your tournament "${tournament.title}" is now Live. Room ID: ${room_id} Password: ${room_pass} Join Now and Good Luck!`;
        
        const insertNotify = db.prepare('INSERT INTO notifications (user_id, tournament_id, title, message) VALUES (?, ?, ?, ?)');
        db.transaction(() => {
            for (const p of participants) {
                insertNotify.run(p.user_id, req.params.id, title, message);
            }
        })();
    }
    
    res.redirect('/admin');
});

app.get('/admin/logout', (req, res) => {
    (req as any).session.adminId = null;
    res.redirect('/admin/login');
});

app.get('/admin/settings', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.redirect('/admin/login');
    
    const settings = db.prepare('SELECT * FROM settings LIMIT 1').get() as any;
    const admin = db.prepare('SELECT * FROM admin WHERE id = ?').get(adminId) as any;

    res.send(`
    <body class="bg-slate-900 text-white p-4">
        <div class="max-w-md mx-auto space-y-6">
            <h1 class="text-xl font-bold">Admin Settings</h1>
            
            <div class="bg-slate-800 p-6 rounded-3xl space-y-4">
                <h2 class="font-bold border-b border-white/5 pb-2 text-sm">Website Management</h2>
                <form action="/admin/settings/update" method="POST" class="space-y-4">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Website Name</label>
                        <input type="text" name="site_name" value="${settings.site_name}" class="w-full bg-slate-700 p-3 rounded-xl mt-1">
                    </div>
                    <button class="w-full bg-orange-500 py-3 rounded-xl font-bold">Save Settings</button>
                </form>
            </div>

            <div class="bg-slate-800 p-6 rounded-3xl space-y-4">
                <h2 class="font-bold border-b border-white/5 pb-2 text-sm">Profile Settings</h2>
                <form action="/admin/profile/update" method="POST" class="space-y-4">
                    <div>
                        <label class="text-[10px] text-slate-500 uppercase">Username</label>
                        <input type="text" name="username" value="${admin.username}" class="w-full bg-slate-700 p-3 rounded-xl mt-1">
                    </div>
                    <button class="w-full bg-slate-700 py-3 rounded-xl font-bold">Update Info</button>
                </form>
            </div>

            <a href="/admin" class="block text-center text-slate-500">Back to Dashboard</a>
        </div>
    </body>
    `);
});

app.get('/admin/payment-settings', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.redirect('/admin/login');
    
    const settings = db.prepare('SELECT * FROM payment_settings LIMIT 1').get() as any;

    res.send(`
    <body class="bg-slate-900 text-white p-4">
        <div class="max-w-md mx-auto space-y-6">
            <div class="flex items-center gap-3">
                <a href="/admin" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
                <h1 class="text-xl font-bold">Payment Settings</h1>
            </div>
            
            <div class="bg-slate-800 p-6 rounded-3xl space-y-6">
                <form action="/admin/payment-settings/update" method="POST" class="space-y-6">
                    <div class="space-y-4">
                        <h2 class="font-bold text-orange-500 flex items-center gap-2">
                            <i class="fas fa-mobile-alt"></i> bKash Settings
                        </h2>
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase">bKash Number</label>
                            <input type="text" name="bkash_number" value="${settings.bkash_number || ''}" class="w-full bg-slate-700 p-3 rounded-xl mt-1">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase">Account Type</label>
                            <select name="bkash_type" class="w-full bg-slate-700 p-3 rounded-xl mt-1">
                                <option value="Personal" ${settings.bkash_type === 'Personal' ? 'selected' : ''}>Personal</option>
                                <option value="Agent" ${settings.bkash_type === 'Agent' ? 'selected' : ''}>Agent</option>
                                <option value="Merchant" ${settings.bkash_type === 'Merchant' ? 'selected' : ''}>Merchant</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-white/5">
                        <h2 class="font-bold text-red-500 flex items-center gap-2">
                            <i class="fas fa-mobile-alt"></i> Nagad Settings
                        </h2>
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase">Nagad Number</label>
                            <input type="text" name="nagad_number" value="${settings.nagad_number || ''}" class="w-full bg-slate-700 p-3 rounded-xl mt-1">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase">Account Type</label>
                            <select name="nagad_type" class="w-full bg-slate-700 p-3 rounded-xl mt-1">
                                <option value="Personal" ${settings.nagad_type === 'Personal' ? 'selected' : ''}>Personal</option>
                                <option value="Agent" ${settings.nagad_type === 'Agent' ? 'selected' : ''}>Agent</option>
                                <option value="Merchant" ${settings.nagad_type === 'Merchant' ? 'selected' : ''}>Merchant</option>
                            </select>
                        </div>
                    </div>

                    <button class="w-full bg-orange-500 py-4 rounded-xl font-bold shadow-lg shadow-orange-500/20">Save Payment Settings</button>
                </form>
            </div>
        </div>
    </body>
    `);
});

app.post('/admin/payment-settings/update', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.status(403).send('Unauthorized');
    
    const { bkash_number, bkash_type, nagad_number, nagad_type } = req.body;
    db.prepare('UPDATE payment_settings SET bkash_number = ?, bkash_type = ?, nagad_number = ?, nagad_type = ? WHERE id = 1').run(bkash_number, bkash_type, nagad_number, nagad_type);
    res.redirect('/admin/payment-settings');
});

app.get('/admin/deposit-requests', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.redirect('/admin/login');
    
    const deposits = db.prepare(`
        SELECT d.*, u.username 
        FROM deposits d 
        JOIN users u ON d.user_id = u.id 
        ORDER BY d.created_at DESC
    `).all() as any[];

    res.send(`
    <body class="bg-slate-900 text-white p-4">
        <div class="max-w-md mx-auto space-y-6">
            <div class="flex items-center gap-3">
                <a href="/admin" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
                <h1 class="text-xl font-bold">Deposit Requests</h1>
            </div>
            
            <div class="space-y-4">
                ${deposits.length === 0 ? '<p class="text-center text-slate-500">No deposit requests.</p>' : deposits.map(d => `
                    <div class="bg-slate-800 p-5 rounded-3xl space-y-4 border border-white/5">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold">${d.username}</h3>
                                <p class="text-[10px] text-slate-500 uppercase">${d.payment_method} • ${new Date(d.created_at).toLocaleString()}</p>
                            </div>
                            <span class="px-2 py-1 rounded text-[10px] font-bold ${d.status === 'Approved' ? 'bg-green-500/20 text-green-500' : d.status === 'Rejected' ? 'bg-red-500/20 text-red-500' : 'bg-yellow-500/20 text-yellow-500'}">
                                ${d.status}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 bg-slate-900/50 p-3 rounded-2xl">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase">Amount</p>
                                <p class="font-bold text-orange-500">${formatCurrency(d.amount)}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase">TrxID</p>
                                <p class="font-bold text-xs">${d.transaction_id}</p>
                            </div>
                        </div>
                        ${d.status === 'Pending' ? `
                            <div class="flex gap-2">
                                <form action="/admin/deposit-requests/approve/${d.id}" method="POST" class="flex-1">
                                    <button class="w-full bg-green-500 py-2 rounded-xl text-xs font-bold">Approve</button>
                                </form>
                                <form action="/admin/deposit-requests/reject/${d.id}" method="POST" class="flex-1">
                                    <button class="w-full bg-red-500 py-2 rounded-xl text-xs font-bold">Reject</button>
                                </form>
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        </div>
    </body>
    `);
});

app.post('/admin/deposit-requests/approve/:id', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.status(403).send('Unauthorized');
    
    const deposit = db.prepare('SELECT * FROM deposits WHERE id = ?').get(req.params.id) as any;
    if (deposit && deposit.status === 'Pending') {
        db.transaction(() => {
            db.prepare('UPDATE deposits SET status = "Approved" WHERE id = ?').run(req.params.id);
            db.prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?').run(deposit.amount, deposit.user_id);
            db.prepare('INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, "Credit", ?)').run(deposit.user_id, deposit.amount, `Deposit Approved (${deposit.payment_method})`);
        })();
    }
    res.redirect('/admin/deposit-requests');
});

app.post('/admin/deposit-requests/reject/:id', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.status(403).send('Unauthorized');
    
    db.prepare('UPDATE deposits SET status = "Rejected" WHERE id = ?').run(req.params.id);
    res.redirect('/admin/deposit-requests');
});

app.get('/admin/withdrawal-requests', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.redirect('/admin/login');
    
    const withdrawals = db.prepare(`
        SELECT w.*, u.username, u.bkash_number as user_bkash, u.nagad_number as user_nagad 
        FROM withdrawals w 
        JOIN users u ON w.user_id = u.id 
        ORDER BY w.created_at DESC
    `).all() as any[];

    res.send(`
    <body class="bg-slate-900 text-white p-4">
        <div class="max-w-md mx-auto space-y-6">
            <div class="flex items-center gap-3">
                <a href="/admin" class="text-slate-500"><i class="fas fa-arrow-left"></i></a>
                <h1 class="text-xl font-bold">Withdrawal Requests</h1>
            </div>
            
            <div class="space-y-4">
                ${withdrawals.length === 0 ? '<p class="text-center text-slate-500">No withdrawal requests.</p>' : withdrawals.map(w => `
                    <div class="bg-slate-800 p-5 rounded-3xl space-y-4 border border-white/5">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold">${w.username}</h3>
                                <p class="text-[10px] text-slate-500 uppercase">${w.payment_method} • ${new Date(w.created_at).toLocaleString()}</p>
                            </div>
                            <span class="px-2 py-1 rounded text-[10px] font-bold ${w.status === 'Completed' ? 'bg-green-500/20 text-green-500' : w.status === 'Rejected' ? 'bg-red-500/20 text-red-500' : 'bg-yellow-500/20 text-yellow-500'}">
                                ${w.status}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 bg-slate-900/50 p-3 rounded-2xl">
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase">Amount</p>
                                <p class="font-bold text-red-500">${formatCurrency(w.amount)}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-500 uppercase">User Number</p>
                                <p class="font-bold text-xs">${w.payment_method === 'bkash' ? w.user_bkash : w.user_nagad}</p>
                            </div>
                        </div>
                        ${w.status === 'Pending' ? `
                            <div class="flex gap-2">
                                <form action="/admin/withdrawal-requests/complete/${w.id}" method="POST" class="flex-1">
                                    <button class="w-full bg-green-500 py-2 rounded-xl text-xs font-bold">Complete</button>
                                </form>
                                <form action="/admin/withdrawal-requests/reject/${w.id}" method="POST" class="flex-1">
                                    <button class="w-full bg-red-500 py-2 rounded-xl text-xs font-bold">Reject</button>
                                </form>
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        </div>
    </body>
    `);
});

app.post('/admin/withdrawal-requests/complete/:id', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.status(403).send('Unauthorized');
    
    const withdrawal = db.prepare('SELECT * FROM withdrawals WHERE id = ?').get(req.params.id) as any;
    if (withdrawal && withdrawal.status === 'Pending') {
        const user = db.prepare('SELECT wallet_balance FROM users WHERE id = ?').get(withdrawal.user_id) as any;
        if (user.wallet_balance >= withdrawal.amount) {
            db.transaction(() => {
                db.prepare('UPDATE withdrawals SET status = "Completed" WHERE id = ?').run(req.params.id);
                db.prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?').run(withdrawal.amount, withdrawal.user_id);
                db.prepare('INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, "Debit", ?)').run(withdrawal.user_id, withdrawal.amount, `Withdrawal Completed (${withdrawal.payment_method})`);
            })();
        }
    }
    res.redirect('/admin/withdrawal-requests');
});

app.post('/admin/withdrawal-requests/reject/:id', (req, res) => {
    const adminId = (req as any).session.adminId;
    if (!adminId) return res.status(403).send('Unauthorized');
    
    db.prepare('UPDATE withdrawals SET status = "Rejected" WHERE id = ?').run(req.params.id);
    res.redirect('/admin/withdrawal-requests');
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
