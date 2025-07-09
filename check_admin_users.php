<?php
require_once 'config.php';
require_once 'includes/RoleManager.php';

echo "<h1>Vérification des Utilisateurs Admin</h1>";

try {
    $roleManager = new RoleManager($pdo);
    
    // Récupérer tous les utilisateurs avec leurs rôles
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.created_at, r.name as role_name, r.id as role_id
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY r.id, u.username
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Utilisateurs dans la base :</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Email</th><th>Rôle</th><th>Date création</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $roleColor = '';
        switch ($user['role_id']) {
            case 1: $roleColor = 'background: #dc3545; color: white;'; break; // Admin
            case 2: $roleColor = 'background: #ffc107; color: #212529;'; break; // Modérateur
            case 3: $roleColor = 'background: #28a745; color: white;'; break; // Gamer
            case 4: $roleColor = 'background: #6c757d; color: white;'; break; // Banni
        }
        
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td style='padding: 4px 8px; border-radius: 4px; " . $roleColor . "'>" . htmlspecialchars($user['role_name']) . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Compter par rôle
    $roleCounts = [];
    foreach ($users as $user) {
        $roleName = $user['role_name'];
        if (!isset($roleCounts[$roleName])) {
            $roleCounts[$roleName] = 0;
        }
        $roleCounts[$roleName]++;
    }
    
    echo "<h2>Répartition par rôle :</h2>";
    echo "<ul>";
    foreach ($roleCounts as $role => $count) {
        echo "<li><strong>" . htmlspecialchars($role) . "</strong> : " . $count . " utilisateur(s)</li>";
    }
    echo "</ul>";
    
    // Vérifier s'il y a des admins
    $admins = array_filter($users, function($user) {
        return $user['role_id'] == 1; // Admin
    });
    
    if (empty($admins)) {
        echo "<h2 style='color: red;'>⚠️ Aucun utilisateur admin trouvé !</h2>";
        echo "<p>Vous devez créer un utilisateur admin pour accéder aux pages Admin et Modération.</p>";
        
        echo "<h3>Options pour créer un admin :</h3>";
        echo "<ol>";
        echo "<li><strong>Option 1 :</strong> Créer un compte normal puis le promouvoir admin via la base de données</li>";
        echo "<li><strong>Option 2 :</strong> Modifier directement un utilisateur existant en admin</li>";
        echo "<li><strong>Option 3 :</strong> Créer un script de création d'admin</li>";
        echo "</ol>";
        
        if (!empty($users)) {
            echo "<h3>Promouvoir un utilisateur existant en admin :</h3>";
            echo "<form method='POST' action='promote_to_admin.php'>";
            echo "<select name='user_id' required>";
            echo "<option value=''>Choisir un utilisateur...</option>";
            foreach ($users as $user) {
                if ($user['role_id'] != 1) { // Pas déjà admin
                    echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['role_name']) . ")</option>";
                }
            }
            echo "</select>";
            echo "<button type='submit' style='background: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 4px; margin-left: 10px;'>Promouvoir Admin</button>";
            echo "</form>";
        }
    } else {
        echo "<h2 style='color: green;'>✅ Utilisateurs admin trouvés :</h2>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li><strong>" . htmlspecialchars($admin['username']) . "</strong> (" . htmlspecialchars($admin['email']) . ")</li>";
        }
        echo "</ul>";
        echo "<p>Vous pouvez vous connecter avec l'un de ces comptes pour accéder aux pages Admin et Modération.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 