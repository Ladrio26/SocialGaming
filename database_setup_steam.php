<?php
require_once 'config.php';

try {
    // Table pour les comptes Steam liés
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS steam_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            steam_id VARCHAR(20) NOT NULL,
            linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_steam (user_id, steam_id),
            UNIQUE KEY unique_steam_id (steam_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table pour les profils Steam
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS steam_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            steam_id VARCHAR(20) NOT NULL,
            username VARCHAR(255),
            realname VARCHAR(255),
            avatar TEXT,
            profile_url TEXT,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_steam_id (steam_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table pour les jeux Steam
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS steam_games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            steam_id VARCHAR(20) NOT NULL,
            app_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            playtime_forever INT DEFAULT 0,
            playtime_2weeks INT DEFAULT 0,
            img_icon_url VARCHAR(255),
            img_logo_url VARCHAR(255),
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_steam_game (steam_id, app_id),
            FOREIGN KEY (steam_id) REFERENCES steam_accounts(steam_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table pour les codes de vérification Steam
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS steam_verification_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            steam_id VARCHAR(20) NOT NULL,
            verification_code VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            UNIQUE KEY unique_steam_code (steam_id, verification_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "Tables Steam créées avec succès !\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la création des tables : " . $e->getMessage() . "\n";
}
?> 