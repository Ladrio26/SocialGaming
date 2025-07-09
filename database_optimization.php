<?php
require_once 'config.php';

echo "🚀 Début de l'optimisation de la base de données SocialGaming...\n\n";

try {
    // 1. Créer la table notifications si elle n'existe pas
    echo "📝 1. Création de la table notifications...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data JSON,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_user_created (user_id, created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table notifications créée/optimisée\n\n";

    // 2. Ajouter les index de performance
    echo "⚡ 2. Ajout des index de performance...\n";
    
    $indexes = [
        "ALTER TABLE friends ADD INDEX IF NOT EXISTS idx_user_friend (user_id, friend_id)",
        "ALTER TABLE steam_games ADD INDEX IF NOT EXISTS idx_steam_app (steam_id, app_id)",
        "ALTER TABLE users ADD INDEX IF NOT EXISTS idx_username_email (username, email)",
        "ALTER TABLE steam_profiles ADD INDEX IF NOT EXISTS idx_username (username)",
        "ALTER TABLE user_sessions ADD INDEX IF NOT EXISTS idx_token (session_token)",
        "ALTER TABLE user_sessions ADD INDEX IF NOT EXISTS idx_expires (expires_at)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "⚠️  Erreur avec l'index: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "✅ Index ajoutés\n\n";

    // 3. Créer la table user_stats
    echo "📊 3. Création de la table user_stats...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_stats (
            user_id INT PRIMARY KEY,
            total_friends INT DEFAULT 0,
            total_games INT DEFAULT 0,
            profile_views INT DEFAULT 0,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table user_stats créée\n\n";

    // 4. Créer la table common_games_cache
    echo "🎮 4. Création de la table common_games_cache...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS common_games_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user1_id INT NOT NULL,
            user2_id INT NOT NULL,
            game_id INT NOT NULL,
            game_name VARCHAR(200),
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_common_game (user1_id, user2_id, game_id),
            FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_users (user1_id, user2_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table common_games_cache créée\n\n";

    // 5. Créer la table activity_logs
    echo "📝 5. Création de la table activity_logs...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(50) NOT NULL,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_action (user_id, action),
            INDEX idx_created (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table activity_logs créée\n\n";

    // 6. Créer les vues optimisées
    echo "👁️ 6. Création des vues optimisées...\n";
    
    // Supprimer les vues existantes si elles existent
    $pdo->exec("DROP VIEW IF EXISTS user_profiles");
    $pdo->exec("DROP VIEW IF EXISTS friends_with_common_games");
    
    // Créer la vue user_profiles
    $pdo->exec("
        CREATE VIEW user_profiles AS
        SELECT 
            u.id, u.username, u.email, u.avatar_url, u.created_at,
            COALESCE(us.total_friends, 0) as total_friends,
            COALESCE(us.total_games, 0) as total_games,
            COALESCE(us.profile_views, 0) as profile_views,
            sa.steam_id, sp.username as steam_username
        FROM users u
        LEFT JOIN user_stats us ON u.id = us.user_id
        LEFT JOIN steam_accounts sa ON u.id = sa.user_id
        LEFT JOIN steam_profiles sp ON sa.steam_id = sp.steam_id
    ");
    
    // Créer la vue friends_with_common_games
    $pdo->exec("
        CREATE VIEW friends_with_common_games AS
        SELECT 
            f.user_id, f.friend_id,
            COUNT(DISTINCT sg1.app_id) as common_games_count,
            GROUP_CONCAT(DISTINCT sg1.name SEPARATOR ', ') as common_games
        FROM friends f
        JOIN steam_accounts sa1 ON f.user_id = sa1.user_id
        JOIN steam_games sg1 ON sa1.steam_id = sg1.steam_id
        JOIN steam_accounts sa2 ON f.friend_id = sa2.user_id
        JOIN steam_games sg2 ON sa2.steam_id = sg2.steam_id AND sg1.app_id = sg2.app_id
        GROUP BY f.user_id, f.friend_id
    ");
    echo "✅ Vues créées\n\n";

    // 7. Initialiser les statistiques existantes
    echo "📈 7. Initialisation des statistiques...\n";
    
    // Compter les amis pour chaque utilisateur
    $pdo->exec("
        INSERT INTO user_stats (user_id, total_friends)
        SELECT user_id, COUNT(*) as total_friends
        FROM friends
        GROUP BY user_id
        ON DUPLICATE KEY UPDATE total_friends = VALUES(total_friends)
    ");
    
    // Compter les jeux pour chaque utilisateur
    $pdo->exec("
        INSERT INTO user_stats (user_id, total_games)
        SELECT sa.user_id, COUNT(*) as total_games
        FROM steam_games sg
        JOIN steam_accounts sa ON sg.steam_id = sa.steam_id
        GROUP BY sa.user_id
        ON DUPLICATE KEY UPDATE total_games = VALUES(total_games)
    ");
    echo "✅ Statistiques initialisées\n\n";

    // 8. Nettoyer les anciennes données
    echo "🧹 8. Nettoyage des anciennes données...\n";
    
    // Supprimer les sessions expirées
    $pdo->exec("DELETE FROM user_sessions WHERE expires_at < NOW()");
    
    // Supprimer les codes de vérification expirés
    $pdo->exec("DELETE FROM steam_verification_codes WHERE expires_at < NOW()");
    
    // Supprimer les anciennes notifications (plus de 90 jours)
    $pdo->exec("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    echo "✅ Nettoyage terminé\n\n";

    echo "🎉 Optimisation terminée avec succès !\n\n";
    echo "📋 Résumé des améliorations :\n";
    echo "- ✅ Table notifications créée avec index optimisés\n";
    echo "- ✅ Index de performance ajoutés sur toutes les tables\n";
    echo "- ✅ Table user_stats pour les statistiques\n";
    echo "- ✅ Table common_games_cache pour les jeux en commun\n";
    echo "- ✅ Table activity_logs pour le suivi d'activité\n";
    echo "- ✅ Vues optimisées pour les requêtes complexes\n";
    echo "- ✅ Statistiques initialisées\n";
    echo "- ✅ Nettoyage des données obsolètes\n\n";
    
    echo "🚀 Votre base de données est maintenant optimisée pour de meilleures performances !\n";

} catch (PDOException $e) {
    echo "❌ Erreur lors de l'optimisation : " . $e->getMessage() . "\n";
}
?> 