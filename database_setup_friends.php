<?php
require_once 'config.php';

try {
    // Table des demandes d'amis
    $sql_friend_requests = "CREATE TABLE IF NOT EXISTS friend_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_friend_request (sender_id, receiver_id)
    )";
    
    $pdo->exec($sql_friend_requests);
    
    // Table des amis (pour les relations acceptées)
    $sql_friends = "CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        friend_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_friendship (user_id, friend_id)
    )";
    
    $pdo->exec($sql_friends);
    
    echo "Tables d'amis créées avec succès !\n";
    echo "- Table 'friend_requests' créée\n";
    echo "- Table 'friends' créée\n";
    
} catch (PDOException $e) {
    echo "Erreur lors de la création des tables d'amis : " . $e->getMessage();
}
?> 