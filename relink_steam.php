<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Steam.php';
require_once 'config_steam_oauth.php';

echo "<h2>Reliaison de votre compte Steam</h2>";

// Votre Steam ID
$your_steam_id = '76561198047020612';

echo "<p>Steam ID à lier : <strong>$your_steam_id</strong></p>";

// Récupérer votre compte Discord (ID: 2)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 2");
$stmt->execute();
$discord_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$discord_user) {
    echo "<p>❌ Utilisateur Discord non trouvé</p>";
    exit;
}

echo "<p>Compte Discord trouvé : {$discord_user['username']} (ID: {$discord_user['id']})</p>";

// Lier le compte Steam
$steam = new Steam($pdo, $steam_api_key);
$result = $steam->linkSteamAccount($discord_user['id'], $your_steam_id);

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
        
        // Test de la méthode getSteamFriendCode
        $friend_code = $steam->getSteamFriendCode($steam_account['steam_id']);
        echo "<p>Code ami généré : <strong>$friend_code</strong></p>";
        
        // Récupérer les informations Steam
        $steam_info = $steam->getSteamInfo($discord_user['id']);
        if ($steam_info) {
            echo "<p>✅ Informations Steam récupérées avec succès</p>";
            echo "<p>Username : " . htmlspecialchars($steam_info['username']) . "</p>";
            
            // Récupérer les jeux
            $games = $steam->getSteamGames($discord_user['id']);
            if (!empty($games)) {
                echo "<p>✅ " . count($games) . " jeux trouvés</p>";
            } else {
                echo "<p>⚠️ Aucun jeu trouvé (profil privé ?)</p>";
            }
        } else {
            echo "<p>⚠️ Informations Steam non disponibles</p>";
        }
        
        echo "<h3>🎉 Reliaison terminée !</h3>";
        echo "<p>Votre compte Discord est maintenant relié à votre compte Steam.</p>";
        echo "<p><a href='https://ladrio2.goodloss.fr/profile.php' target='_blank'>Voir votre profil</a></p>";
        
    } else {
        echo "<p>❌ Aucun lien Steam trouvé après la liaison</p>";
    }
} else {
    echo "<p>❌ Échec de la liaison : {$result['message']}</p>";
}
?> 