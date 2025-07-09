<?php
require_once 'config.php';

echo "<h1>🔧 Configuration de l'Intégration Discord</h1>";

// 1. Créer la table discord_accounts
echo "<h2>1. Création de la table discord_accounts</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS discord_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        discord_user_id VARCHAR(50) NOT NULL UNIQUE,
        discord_username VARCHAR(100) NOT NULL,
        discord_display_name VARCHAR(100) NOT NULL,
        discord_avatar_url VARCHAR(500),
        discord_access_token VARCHAR(500) NOT NULL,
        discord_refresh_token VARCHAR(500) NOT NULL,
        discord_token_expires_at TIMESTAMP NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_discord (user_id, discord_user_id)
    )";
    
    $pdo->exec($sql);
    echo "✅ Table discord_accounts créée avec succès<br>";
    
    // Créer les index
    $pdo->exec("CREATE INDEX idx_discord_accounts_user_id ON discord_accounts(user_id)");
    $pdo->exec("CREATE INDEX idx_discord_accounts_active ON discord_accounts(is_active)");
    echo "✅ Index créés avec succès<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la création de la table : " . $e->getMessage() . "<br>";
}

// 2. Vérifier la configuration Discord
echo "<h2>2. Vérification de la configuration Discord</h2>";

$config_ok = true;

if (!defined('DISCORD_CLIENT_ID') || empty(DISCORD_CLIENT_ID)) {
    echo "❌ DISCORD_CLIENT_ID non défini<br>";
    $config_ok = false;
} else {
    echo "✅ DISCORD_CLIENT_ID : " . DISCORD_CLIENT_ID . "<br>";
}

if (!defined('DISCORD_CLIENT_SECRET') || empty(DISCORD_CLIENT_SECRET)) {
    echo "❌ DISCORD_CLIENT_SECRET non défini<br>";
    $config_ok = false;
} else {
    echo "✅ DISCORD_CLIENT_SECRET : Configuré<br>";
}

if (!defined('DISCORD_REDIRECT_URI') || empty(DISCORD_REDIRECT_URI)) {
    echo "❌ DISCORD_REDIRECT_URI non défini<br>";
    $config_ok = false;
} else {
    echo "✅ DISCORD_REDIRECT_URI : " . DISCORD_REDIRECT_URI . "<br>";
}

// 3. Vérifier les fichiers requis
echo "<h2>3. Vérification des fichiers</h2>";

$required_files = [
    'classes/Discord.php',
    'api/discord.php',
    'oauth2callback_discord_link.php',
    'discord_callback.php',
    'assets/js/discord.js'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file<br>";
    } else {
        echo "❌ $file manquant<br>";
    }
}

// 4. Test de la classe Discord
echo "<h2>4. Test de la classe Discord</h2>";

try {
    require_once 'classes/Discord.php';
    $discord = new Discord($pdo);
    echo "✅ Classe Discord chargée avec succès<br>";
    
    // Test de génération d'URL d'authentification
    $auth_url = $discord->getAuthUrl('test_state');
    if (strpos($auth_url, 'discord.com') !== false) {
        echo "✅ URL d'authentification générée : " . substr($auth_url, 0, 100) . "...<br>";
    } else {
        echo "❌ Erreur dans la génération de l'URL d'authentification<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test de la classe Discord : " . $e->getMessage() . "<br>";
}

// 5. Instructions finales
echo "<h2>5. Instructions</h2>";

if ($config_ok) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🎉 Configuration terminée !</h3>";
    echo "<p>L'intégration Discord est maintenant configurée. Voici ce que vous devez faire :</p>";
    echo "<ol>";
    echo "<li><strong>Mettre à jour la console Discord Developer</strong> : Allez sur <a href='https://discord.com/developers/applications' target='_blank'>Discord Developer Portal</a></li>";
    echo "<li><strong>Modifiez votre application Discord</strong></li>";
    echo "<li><strong>Mettez à jour l'URI de redirection</strong> pour qu'elle soit exactement : <code>" . DISCORD_REDIRECT_URI . "</code></li>";
    echo "<li><strong>Testez la liaison</strong> en allant sur votre profil et en cliquant sur l'onglet Discord</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>⚠️ Configuration incomplète</h3>";
    echo "<p>Veuillez corriger les erreurs ci-dessus avant de continuer.</p>";
    echo "</div>";
}

echo "<p><a href='profile.php'>← Retour au profil</a></p>";
?> 