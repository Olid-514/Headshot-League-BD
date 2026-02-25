<?php
/**
 * Headshot League BD - Database Update Script
 * Run this once to update your database schema.
 */

// Mock config.php content since we are in a Node environment
// In a real PHP app, this would be: include 'config.php';
$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$db   = 'headshot_league';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create payment_settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bkash_number VARCHAR(50) NULL,
        bkash_type VARCHAR(50) NULL,
        nagad_number VARCHAR(50) NULL,
        nagad_type VARCHAR(50) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insert default row if not exists
    $stmt = $pdo->query("SELECT id FROM payment_settings LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO payment_settings (bkash_number, bkash_type, nagad_number, nagad_type) VALUES ('01700000000', 'Personal', '01800000000', 'Personal')");
    }

    // 2. Create deposits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS deposits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_id VARCHAR(255) NOT NULL,
        payment_method ENUM('bkash','nagad') NOT NULL,
        status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Create withdrawals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('bkash','nagad') NOT NULL,
        status ENUM('Pending','Completed','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Update users table
    // Check if columns exist before adding
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'bkash_number'")->fetch();
    if (!$columns) {
        $pdo->exec("ALTER TABLE users ADD COLUMN bkash_number VARCHAR(50) NULL");
    }
    
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'nagad_number'")->fetch();
    if (!$columns) {
        $pdo->exec("ALTER TABLE users ADD COLUMN nagad_number VARCHAR(50) NULL");
    }

    echo "<div style='font-family: sans-serif; padding: 20px; background: #dcfce7; color: #166534; border-radius: 10px; margin: 20px;'>
            <h2 style='margin-top: 0;'>✅ Database updated successfully.</h2>
            <p>All payment tables and columns have been created.</p>
            <p><strong>You can now delete this file.</strong></p>
          </div>";

} catch (PDOException $e) {
    die("<div style='color: red; padding: 20px;'>Installation failed: " . $e->getMessage() . "</div>");
}
?>
