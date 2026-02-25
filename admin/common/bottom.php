    </main>

    <!-- WhatsApp Support Button (Admin) -->
    <a href="https://wa.me/8801308492029?text=Hello,%20HL%20BD%20Support,%20I%20need%20help%20🥺." 
       target="_blank"
       class="fixed bottom-24 right-6 z-50 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform animate-pulse-soft border-2 border-white/20">
        <i class="fab fa-whatsapp text-white text-3xl"></i>
    </a>

    <!-- Admin Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 glass border-t border-white/5 px-4 py-3 flex justify-between items-center shadow-[0_-4px_20px_rgba(0,0,0,0.3)]">
        <a href="index.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-red-500' : 'text-slate-400'; ?>">
            <i class="fas fa-chart-line text-xl"></i>
            <span class="text-[10px] font-medium">Stats</span>
        </a>
        <a href="tournament.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'tournament.php' ? 'text-red-500' : 'text-slate-400'; ?>">
            <i class="fas fa-plus-circle text-xl"></i>
            <span class="text-[10px] font-medium">Create</span>
        </a>
        <a href="user.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'user.php' ? 'text-red-500' : 'text-slate-400'; ?>">
            <i class="fas fa-users text-xl"></i>
            <span class="text-[10px] font-medium">Users</span>
        </a>
        <a href="setting.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'setting.php' ? 'text-red-500' : 'text-slate-400'; ?>">
            <i class="fas fa-cog text-xl"></i>
            <span class="text-[10px] font-medium">Settings</span>
        </a>
    </nav>
</body>
</html>
