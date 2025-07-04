<?php
require_once 'config.php';
require_once 'config_steam.php';
require_once 'classes/Steam.php';

// Créer une instance Steam avec la bonne clé API
$steam = new Steam($pdo, $steam_api_key);

// ID de l'utilisateur Discord (vous)
$user_id = 2;
$steam_id = '76561198047020612';

echo "<h1>Mise à jour des informations Steam</h1>";

// Mettre à jour les informations Steam
echo "<h2>Mise à jour du profil Steam...</h2>";
$result = $steam->updateSteamInfo($user_id, $steam_id);

if ($result) {
    echo "<p style='color: green;'>✓ Informations Steam mises à jour avec succès</p>";
} else {
    echo "<p style='color: red;'>✗ Erreur lors de la mise à jour</p>";
}

// Récupérer et afficher les informations
echo "<h2>Informations Steam actuelles</h2>";
$steam_info = $steam->getSteamInfo($user_id);

if ($steam_info) {
    echo "<p><strong>Steam ID:</strong> " . ($steam_info['steam_id'] ?? 'Non trouvé') . "</p>";
    echo "<p><strong>Nom d'utilisateur:</strong> " . ($steam_info['username'] ?? 'Non trouvé') . "</p>";
    echo "<p><strong>Nom réel:</strong> " . ($steam_info['realname'] ?? 'Non trouvé') . "</p>";
    echo "<p><strong>Avatar:</strong> " . ($steam_info['avatar'] ?? 'Non trouvé') . "</p>";
    echo "<p><strong>Profil URL:</strong> " . ($steam_info['profile_url'] ?? 'Non trouvé') . "</p>";
    
    // Afficher le code ami/Steam ID
    $friend_code = $steam->getSteamFriendCode($steam_info['steam_id']);
    echo "<p><strong>Code ami/Steam ID:</strong> $friend_code</p>";
    
    // Afficher les jeux
    if (isset($steam_info['games']) && !empty($steam_info['games'])) {
        echo "<h3>Jeux Steam (" . count($steam_info['games']) . ")</h3>";
        echo "<ul>";
        foreach (array_slice($steam_info['games'], 0, 5) as $game) {
            $hours = round($game['playtime_forever'] / 60, 1);
            echo "<li><strong>{$game['name']}</strong> - {$hours}h</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun jeu trouvé</p>";
    }
} else {
    echo "<p style='color: red;'>Aucune information Steam trouvée</p>";
}

echo "<p><a href='profile.php'>Retour au profil</a></p>";
?> 