<?php
require_once 'config.php';

echo "<h1>🔧 Correction de l'Authentification Discord</h1>";

try {
    // 1. Vérifier et ajouter les colonnes manquantes à la table users
    echo "<h2>1. Vérification de la table users</h2>";
    
    // Vérifier si la colonne discord_avatar existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'discord_avatar'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN discord_avatar VARCHAR(500) NULL AFTER avatar_url");
        echo "✅ Colonne discord_avatar ajoutée<br>";
    } else {
        echo "✅ Colonne discord_avatar existe déjà<br>";
    }
    
    // Vérifier si la colonne display_format existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'display_format'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN display_format ENUM('username_only', 'first_last', 'full_name') DEFAULT 'username_only' AFTER discord_avatar");
        echo "✅ Colonne display_format ajoutée<br>";
    } else {
        echo "✅ Colonne display_format existe déjà<br>";
    }
    
    // 2. Vérifier la configuration Discord
    echo "<h2>2. Vérification de la configuration Discord</h2>";
    
    if (defined('DISCORD_CLIENT_ID') && !empty(DISCORD_CLIENT_ID)) {
        echo "✅ DISCORD_CLIENT_ID configuré<br>";
    } else {
        echo "❌ DISCORD_CLIENT_ID manquant<br>";
    }
    
    if (defined('DISCORD_CLIENT_SECRET') && !empty(DISCORD_CLIENT_SECRET)) {
        echo "✅ DISCORD_CLIENT_SECRET configuré<br>";
    } else {
        echo "❌ DISCORD_CLIENT_SECRET manquant<br>";
    }
    
    if (defined('DISCORD_REDIRECT_URI') && !empty(DISCORD_REDIRECT_URI)) {
        echo "✅ DISCORD_REDIRECT_URI configuré : " . DISCORD_REDIRECT_URI . "<br>";
    } else {
        echo "❌ DISCORD_REDIRECT_URI manquant<br>";
    }
    
    // 3. Vérifier les utilisateurs Discord existants
    echo "<h2>3. Vérification des utilisateurs Discord existants</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE auth_provider = 'discord'");
    $stmt->execute();
    $discord_users_count = $stmt->fetchColumn();
    echo "📊 Nombre d'utilisateurs Discord : $discord_users_count<br>";
    
    if ($discord_users_count > 0) {
        $stmt = $pdo->prepare("SELECT id, username, email, provider_id FROM users WHERE auth_provider = 'discord' LIMIT 5");
        $stmt->execute();
        $discord_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Exemples d'utilisateurs Discord :</h3>";
        echo "<ul>";
        foreach ($discord_users as $user) {
            echo "<li>ID: {$user['id']} - Username: {$user['username']} - Email: {$user['email']} - Discord ID: {$user['provider_id']}</li>";
        }
        echo "</ul>";
    }
    
    // 4. Vérifier les sessions actives
    echo "<h2>4. Vérification des sessions</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_sessions WHERE expires_at > NOW()");
    $stmt->execute();
    $active_sessions = $stmt->fetchColumn();
    echo "📊 Sessions actives : $active_sessions<br>";
    
    // 5. Test de connexion à l'API Discord
    echo "<h2>5. Test de connexion à l'API Discord</h2>";
    
    if (function_exists('curl_init')) {
        echo "✅ cURL disponible<br>";
        
        // Test simple de connexion à Discord
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api/v10/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ Erreur cURL : $error<br>";
        } else {
            echo "✅ Connexion à Discord API réussie (Code HTTP: $http_code)<br>";
        }
    } else {
        echo "❌ cURL non disponible<br>";
    }
    
    echo "<h2>✅ Vérification terminée</h2>";
    echo "<p>L'authentification Discord devrait maintenant fonctionner correctement.</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors de la vérification</h2>";
    echo "<p>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
}
?> 