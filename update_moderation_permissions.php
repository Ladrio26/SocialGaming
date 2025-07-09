<?php
require_once 'config.php';

echo "<h1>🔧 Mise à jour des permissions de modération</h1>";

try {
    // Récupérer les rôles existants
    $stmt = $pdo->query("SELECT * FROM user_roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Rôles trouvés :</h2>";
    
    foreach ($roles as $role) {
        echo "<h3>Rôle : " . htmlspecialchars($role['name']) . "</h3>";
        
        $permissions = json_decode($role['permissions'], true) ?: [];
        
        echo "<p>Permissions actuelles :</p>";
        echo "<pre>" . json_encode($permissions, JSON_PRETTY_PRINT) . "</pre>";
        
        // Ajouter la permission moderate_categories pour Modérateur et Admin
        if (in_array($role['name'], ['Modérateur', 'Admin'])) {
            $permissions['moderate_categories'] = true;
            
            // Mettre à jour les permissions
            $stmt = $pdo->prepare("UPDATE user_roles SET permissions = ? WHERE id = ?");
            $stmt->execute([json_encode($permissions), $role['id']]);
            
            echo "<p>✅ Permission 'moderate_categories' ajoutée pour le rôle " . htmlspecialchars($role['name']) . "</p>";
            echo "<p>Nouvelles permissions :</p>";
            echo "<pre>" . json_encode($permissions, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>⚠️ Pas de modification pour ce rôle</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2>✅ Mise à jour terminée !</h2>";
    echo "<p>Les modérateurs et admins peuvent maintenant accéder à la page de modération des catégories.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='moderation.php'>← Accéder à la page de modération</a></p>";
?> 