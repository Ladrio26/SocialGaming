<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    try {
        // Vérifier que l'utilisateur existe
        $stmt = $pdo->prepare("SELECT username, role_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "<p style='color: red;'>❌ Utilisateur non trouvé</p>";
        } elseif ($user['role_id'] == 1) {
            echo "<p style='color: orange;'>⚠️ Cet utilisateur est déjà admin</p>";
        } else {
            // Promouvoir en admin
            $stmt = $pdo->prepare("UPDATE users SET role_id = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            
            echo "<p style='color: green;'>✅ Utilisateur <strong>" . htmlspecialchars($user['username']) . "</strong> promu admin avec succès !</p>";
            echo "<p>Vous pouvez maintenant vous connecter avec ce compte pour accéder aux pages Admin et Modération.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Aucun utilisateur sélectionné</p>";
}

echo "<p><a href='check_admin_users.php'>← Retour à la vérification des utilisateurs</a></p>";
?> 