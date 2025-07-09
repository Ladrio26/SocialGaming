<?php
require_once 'config.php';

try {
    // Création de la table des catégories
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#667eea',
        icon VARCHAR(50) DEFAULT 'fas fa-folder',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "✅ Table 'categories' créée avec succès\n";

    // Création de la table des posts
    $pdo->exec("CREATE TABLE IF NOT EXISTS category_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        user_id INT NOT NULL,
        title VARCHAR(200),
        content TEXT,
        image_url VARCHAR(500),
        image_filename VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'category_posts' créée avec succès\n";

    // Création de la table des commentaires
    $pdo->exec("CREATE TABLE IF NOT EXISTS category_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES category_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ Table 'category_comments' créée avec succès\n";

    // Création de la table des likes
    $pdo->exec("CREATE TABLE IF NOT EXISTS category_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES category_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_like (post_id, user_id)
    )");
    echo "✅ Table 'category_likes' créée avec succès\n";

    // Insertion des catégories de test
    $categories = [
        ['Gaming Memes', 'Partagez vos meilleurs memes de gaming !', '#ff6b6b', 'fas fa-laugh-squint'],
        ['Screenshots', 'Vos plus beaux moments de jeu en capture d\'écran', '#4ecdc4', 'fas fa-camera'],
        ['Astuces & Guides', 'Partagez vos conseils et tutoriels de jeu', '#45b7d1', 'fas fa-lightbulb'],
        ['Setup Gaming', 'Montrez votre setup de gaming', '#96ceb4', 'fas fa-desktop']
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (name, description, color, icon, created_by) VALUES (?, ?, ?, ?, 2)");
    
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "✅ Catégories de test insérées avec succès\n";

    echo "\n🎉 Installation terminée avec succès !\n";

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?> 