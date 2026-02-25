<?php
/**
 * Headshot League BD - Installation Script
 * Auto-creates the database and tables
 */

$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$db   = 'headshot_league';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db` text");

    // Tables SQL
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
        bkash_number VARCHAR(50) NULL,
        nagad_number VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    );

    CREATE TABLE IF NOT EXISTS tournaments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        game_name VARCHAR(50) NOT NULL,
        entry_fee DECIMAL(10, 2) NOT NULL,
        prize_pool DECIMAL(10, 2) NOT NULL,
        match_time DATETIME NOT NULL,
        room_id VARCHAR(50) DEFAULT NULL,
        room_password VARCHAR(50) DEFAULT NULL,
        status ENUM('Upcoming', 'Live', 'Completed') DEFAULT 'Upcoming',
        commission_percentage INT DEFAULT 10,
        rules TEXT,
        join_form_label_name VARCHAR(255) DEFAULT 'Player Name',
        join_form_label_uid VARCHAR(255) DEFAULT 'Game UID',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tournament_id INT NOT NULL,
        player_name VARCHAR(255),
        game_uid VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
    );

    CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        type ENUM('Credit', 'Debit') NOT NULL,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tournament_id INT NOT NULL,
        title VARCHAR(255),
        message TEXT,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (tournament_id) REFERENCES tournaments(id)
    );

    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        site_name VARCHAR(255) NOT NULL,
        site_logo VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS payment_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bkash_number VARCHAR(50) NULL,
        bkash_type VARCHAR(50) NULL,
        nagad_number VARCHAR(50) NULL,
        nagad_type VARCHAR(50) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS deposits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_id VARCHAR(255) NOT NULL,
        payment_method ENUM('bkash','nagad') NOT NULL,
        status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('bkash','nagad') NOT NULL,
        status ENUM('Pending','Completed','Rejected') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $pdo->exec($sql);

    // Insert default settings if not exists
    $stmt = $pdo->prepare("SELECT id FROM settings LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO settings (site_name, site_logo) VALUES ('Headshot League BD', 'assets/logo.png')")->execute();
    }

    // Insert default payment settings if not exists
    $stmt = $pdo->prepare("SELECT id FROM payment_settings LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO payment_settings (bkash_number, bkash_type, nagad_number, nagad_type) VALUES ('01700000000', 'Personal', '01800000000', 'Personal')")->execute();
    }

    // Create default admin if not exists
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hashed_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin (username, password) VALUES ('admin', ?)")->execute([$hashed_pass]);
    }

    echo "Installation successful! Redirecting to login...";
    header("Refresh: 2; URL=login.php");

} catch (PDOException $e) {
    die("Installation failed: " . $e->getMessage());
}
?>
