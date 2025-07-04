<?php
require_once 'config.php';

try {
    // Table des utilisateurs
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255),
        auth_provider ENUM('manual', 'gmail', 'discord') DEFAULT 'manual',
        provider_id VARCHAR(255),
        avatar_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    )";
    
    $pdo->exec($sql_users);
    
    // Table des sessions
    $sql_sessions = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql_sessions);
    
    echo "Base de données initialisée avec succès !\n";
    echo "- Table 'users' créée\n";
    echo "- Table 'user_sessions' créée\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de l'initialisation de la base de données : " . $e->getMessage();
}
?> 