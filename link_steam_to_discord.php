<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Steam.php';
require_once 'config_steam_oauth.php';

echo "<h2>Liaison Steam à Discord</h2>";

// Récupérer votre compte Discord (ID: 2)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 2");
$stmt->execute();
$discord_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$discord_user) {
    echo "<p>❌ Utilisateur Discord non trouvé</p>";
    exit;
}

echo "<p>Compte Discord trouvé : {$discord_user['username']} (ID: {$discord_user['id']})</p>";

// Récupérer l'utilisateur Steam existant
$stmt = $pdo->prepare("SELECT * FROM users WHERE auth_provider = 'steam'");
$stmt->execute();
$steam_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($steam_user) {
    echo "<p>Utilisateur Steam trouvé : {$steam_user['username']} (ID: {$steam_user['id']}, Steam ID: {$steam_user['provider_id']})</p>";
    
    // Lier le compte Steam à votre compte Discord
    $steam = new Steam($pdo, $steam_api_key);
    $result = $steam->linkSteamAccount($discord_user['id'], $steam_user['provider_id']);
    
    echo "<p>Résultat de la liaison : " . json_encode($result) . "</p>";
    
    if ($result['success']) {
        echo "<p>✅ Compte Steam lié avec succès !</p>";
        
        // Vérifier la liaison
        $stmt = $pdo->prepare("SELECT * FROM steam_accounts WHERE user_id = ?");
        $stmt->execute([$discord_user['id']]);
        $steam_account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($steam_account) {
            echo "<p>✅ Lien Steam confirmé dans la base de données</p>";
            echo "<p>Steam ID : {$steam_account['steam_id']}</p>";
            echo "<p>Date de liaison : {$steam_account['linked_at']}</p>";
        }
        
        // Supprimer l'utilisateur Steam séparé
        echo "<h3>Nettoyage :</h3>";
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$steam_user['id']]);
        echo "<p>Utilisateur Steam séparé supprimé</p>";
        
    } else {
        echo "<p>❌ Échec de la liaison : {$result['message']}</p>";
    }
} else {
    echo "<p>Aucun utilisateur Steam trouvé dans la base</p>";
}

// Afficher l'état final
echo "<h3>État final :</h3>";
$stmt = $pdo->prepare("SELECT * FROM steam_accounts WHERE user_id = ?");
$stmt->execute([$discord_user['id']]);
$final_account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($final_account) {
    echo "<p>✅ Votre compte Discord est maintenant lié à Steam !</p>";
    echo "<p>Steam ID : {$final_account['steam_id']}</p>";
    
    // Test de récupération des informations Steam
    $steam_info = $steam->getSteamInfo($discord_user['id']);
    if ($steam_info) {
        echo "<p>✅ Informations Steam récupérées avec succès</p>";
    } else {
        echo "<p>⚠️ Informations Steam non disponibles (profil privé ?)</p>";
    }
} else {
    echo "<p>❌ Aucun lien Steam trouvé</p>";
}
?> 