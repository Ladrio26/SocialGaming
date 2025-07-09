<?php
require_once 'config.php';

echo "<h1>🔧 Configuration des propositions de catégories</h1>";

try {
    // Créer la table category_proposals
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS category_proposals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            status ENUM('pending', 'approved', 'rejected', 'modified') DEFAULT 'pending',
            moderator_id INT NULL,
            moderator_notes TEXT,
            original_name VARCHAR(100) NULL,
            original_description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            moderated_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    echo "<p>✅ Table 'category_proposals' créée avec succès</p>";
    
    // Créer les index pour optimiser les requêtes
    $pdo->exec("CREATE INDEX idx_category_proposals_status ON category_proposals(status)");
    $pdo->exec("CREATE INDEX idx_category_proposals_user_id ON category_proposals(user_id)");
    $pdo->exec("CREATE INDEX idx_category_proposals_created_at ON category_proposals(created_at)");
    
    echo "<p>✅ Index créés avec succès</p>";
    
    // Vérifier la structure de la table
    $stmt = $pdo->query("DESCRIBE category_proposals");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Structure de la table :</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Configuration terminée !</h2>";
    echo "<p>La table des propositions de catégories est maintenant prête.</p>";
    
    echo "<h3>Prochaines étapes :</h3>";
    echo "<ul>";
    echo "<li><a href='update_moderation_permissions.php'>Mettre à jour les permissions de modération</a></li>";
    echo "<li><a href='propose_category.php'>Tester la page de proposition</a></li>";
    echo "<li><a href='moderation.php'>Tester la page de modération</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 