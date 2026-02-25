<?php
/**
 * Headshot League BD - Configuration
 * Centralized Database Connection
 */

$host = '127.0.0.1';
$db   = 'headshot_league';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If database doesn't exist, we might be in install mode
     // throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

session_start();

// Helper function for currency formatting
function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

// Security: Disable right-click, selection, and zoom script
$security_script = "
<script>
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 85 || e.keyCode === 117)) {
            e.preventDefault();
        }
    });
    document.body.style.userSelect = 'none';
    document.body.style.webkitUserSelect = 'none';
    document.body.style.msUserSelect = 'none';
    document.body.style.mozUserSelect = 'none';
    
    // Disable zoom
    document.addEventListener('wheel', function(e) {
        if (e.ctrlKey) {
            e.preventDefault();
        }
    }, { passive: false });
</script>
";
?>
