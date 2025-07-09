<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Discord.php';

echo "<h1>🔄 Migration des Comptes Discord</h1>";

// Récupérer tous les utilisateurs qui se sont inscrits avec Discord
$stmt = $pdo->prepare("SELECT id, username, provider_id, discord_avatar FROM users WHERE auth_provider = 'discord' AND provider_id IS NOT NULL");
$stmt->execute();
$discord_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Utilisateurs Discord trouvés : " . count($discord_users) . "</h2>";

if (empty($discord_users)) {
    echo "<p>Aucun utilisateur Discord à migrer.</p>";
    echo "<p><a href='profile.php'>← Retour au profil</a></p>";
    exit;
}

$migrated = 0;
$errors = 0;

foreach ($discord_users as $user) {
    echo "<h3>Migration de l'utilisateur : " . htmlspecialchars($user['username']) . " (ID: " . $user['id'] . ")</h3>";
    
    // Vérifier si un compte Discord lié existe déjà
    $stmt = $pdo->prepare("SELECT id FROM discord_accounts WHERE user_id = ? OR discord_user_id = ?");
    $stmt->execute([$user['id'], $user['provider_id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "<p>⚠️ Compte Discord déjà migré pour cet utilisateur.</p>";
        continue;
    }
    
    try {
        // Créer un compte Discord lié avec les informations disponibles
        $discord = new Discord($pdo);
        
        // Préparer l'URL de l'avatar Discord
        $avatar_url = null;
        if (!empty($user['discord_avatar'])) {
            $avatar_url = $user['discord_avatar'];
        }
        
        // Créer un token temporaire (sera mis à jour lors de la prochaine connexion)
        $temp_access_token = 'temp_' . bin2hex(random_bytes(16));
        $temp_refresh_token = 'temp_' . bin2hex(random_bytes(16));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $success = $discord->linkAccount(
            $user['id'],
            $user['provider_id'],
            $user['username'],
            $user['username'], // display_name = username pour l'instant
            $avatar_url,
            $temp_access_token,
            $temp_refresh_token,
            3600 // 1 heure
        );
        
        if ($success) {
            echo "<p>✅ Compte Discord migré avec succès !</p>";
            $migrated++;
        } else {
            echo "<p>❌ Erreur lors de la migration.</p>";
            $errors++;
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors++;
    }
}

echo "<h2>Résumé de la migration</h2>";
echo "<ul>";
echo "<li>✅ Comptes migrés avec succès : $migrated</li>";
echo "<li>❌ Erreurs : $errors</li>";
echo "</ul>";

if ($migrated > 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🎉 Migration terminée !</h3>";
    echo "<p>Les comptes Discord ont été migrés vers la nouvelle table. Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li>Aller sur votre profil et voir l'onglet Discord</li>";
    echo "<li>Vos informations Discord devraient s'afficher automatiquement</li>";
    echo "<li>Les tokens seront mis à jour lors de votre prochaine connexion Discord</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<p><a href='profile.php'>← Retour au profil</a></p>";
?> 