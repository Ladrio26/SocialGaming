<?php
require_once 'config.php';

echo "<h2>Configuration du système de rôles</h2>";

try {
    // Lecture du fichier SQL
    $sql = file_get_contents('database_roles.sql');
    
    // Exécution des requêtes SQL
    $pdo->exec($sql);
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green; border-radius: 5px;'>";
    echo "✅ Système de rôles créé avec succès !<br>";
    echo "Les rôles suivants ont été créés :<br>";
    echo "- Admin (ID: 1)<br>";
    echo "- Modérateur (ID: 2)<br>";
    echo "- Gamer (ID: 3) - Rôle par défaut<br>";
    echo "- Banni (ID: 4)<br>";
    echo "</div>";
    
    // Vérification des rôles créés
    $stmt = $pdo->query("SELECT * FROM user_roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Rôles créés :</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Description</th><th>Permissions</th></tr>";
    foreach ($roles as $role) {
        echo "<tr>";
        echo "<td>" . $role['id'] . "</td>";
        echo "<td>" . htmlspecialchars($role['name']) . "</td>";
        echo "<td>" . htmlspecialchars($role['description']) . "</td>";
        echo "<td>" . htmlspecialchars($role['permissions']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérification des utilisateurs existants
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetchColumn();
    
    echo "<h3>Utilisateurs existants :</h3>";
    echo "<p>Nombre d'utilisateurs dans la base : " . $userCount . "</p>";
    echo "<p>Tous les utilisateurs existants ont été assignés au rôle 'Gamer' par défaut.</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px;'>";
    echo "❌ Erreur lors de la création du système de rôles : " . $e->getMessage();
    echo "</div>";
}
?> 