<?php
require_once 'config.php';

echo "<h2>Réinitialisation des catégories</h2>";

try {
    // Afficher les catégories actuelles
    echo "<h3>Catégories actuelles :</h3>";
    $stmt = $pdo->query("SELECT id, name, description, icon, color FROM categories ORDER BY id");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Description</th><th>Icône</th><th>Couleur</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>" . $cat['id'] . "</td>";
            echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['description']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['icon']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['color']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucune catégorie trouvée.</p>";
    }
    
    // Supprimer toutes les catégories existantes
    echo "<h3>Suppression des catégories existantes...</h3>";
    $stmt = $pdo->prepare("DELETE FROM categories");
    $stmt->execute();
    echo "<p>✅ " . $stmt->rowCount() . " catégorie(s) supprimée(s)</p>";
    
    // Réinitialiser l'auto-increment
    $pdo->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    echo "<p>✅ Auto-increment réinitialisé</p>";
    
    // Créer la nouvelle catégorie "test"
    echo "<h3>Création de la catégorie 'test'...</h3>";
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon, color) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Test', 'Catégorie de test pour les messages', 'fas fa-flask', '#6c757d']);
    
    $new_id = $pdo->lastInsertId();
    echo "<p>✅ Nouvelle catégorie créée avec l'ID : " . $new_id . "</p>";
    
    // Vérifier le résultat
    echo "<h3>Catégories après réinitialisation :</h3>";
    $stmt = $pdo->query("SELECT id, name, description, icon, color FROM categories ORDER BY id");
    $new_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($new_categories) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Description</th><th>Icône</th><th>Couleur</th></tr>";
        foreach ($new_categories as $cat) {
            echo "<tr>";
            echo "<td>" . $cat['id'] . "</td>";
            echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['description']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['icon']) . "</td>";
            echo "<td>" . htmlspecialchars($cat['color']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>✅ Réinitialisation terminée avec succès !</h3>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 