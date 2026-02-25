    </main>

    <!-- WhatsApp Support Button -->
    <a href="https://wa.me/8801308492029?text=Hello,%20HL%20BD%20Support,%20I%20need%20help%20🥺." 
       target="_blank"
       class="fixed bottom-24 right-6 z-50 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform animate-pulse-soft border-2 border-white/20">
        <i class="fab fa-whatsapp text-white text-3xl"></i>
    </a>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 glass border-t border-white/5 px-6 py-3 flex justify-between items-center shadow-[0_-4px_20px_rgba(0,0,0,0.3)]">
        <a href="index.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-orange-500' : 'text-slate-400'; ?>">
            <i class="fas fa-home text-xl"></i>
            <span class="text-[10px] font-medium">Home</span>
        </a>
        <a href="my_tournaments.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'my_tournaments.php' ? 'text-orange-500' : 'text-slate-400'; ?>">
            <i class="fas fa-trophy text-xl"></i>
            <span class="text-[10px] font-medium">Matches</span>
        </a>
        <a href="wallet.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'wallet.php' ? 'text-orange-500' : 'text-slate-400'; ?>">
            <i class="fas fa-wallet text-xl"></i>
            <span class="text-[10px] font-medium">Wallet</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center gap-1 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-orange-500' : 'text-slate-400'; ?>">
            <i class="fas fa-user text-xl"></i>
            <span class="text-[10px] font-medium">Profile</span>
        </a>
    </nav>
</body>
</html>
