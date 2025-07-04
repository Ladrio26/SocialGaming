<?php
require_once 'config_steam_oauth.php';

echo "<h2>Vérification de votre Steam ID</h2>";

echo "<h3>Comment trouver votre Steam ID :</h3>";
echo "<ol>";
echo "<li><strong>Méthode 1 - Via votre profil Steam :</strong></li>";
echo "<ul>";
echo "<li>Allez sur <a href='https://steamcommunity.com/my/profile' target='_blank'>votre profil Steam</a></li>";
echo "<li>Votre Steam ID est dans l'URL : https://steamcommunity.com/profiles/VOTRE_STEAM_ID</li>";
echo "</ul>";

echo "<li><strong>Méthode 2 - Via SteamDB :</strong></li>";
echo "<ul>";
echo "<li>Allez sur <a href='https://steamdb.info/' target='_blank'>SteamDB</a></li>";
echo "<li>Recherchez votre nom d'utilisateur Steam</li>";
echo "<li>Votre Steam ID sera affiché</li>";
echo "</ul>";

echo "<li><strong>Méthode 3 - Via l'API Steam :</strong></li>";
echo "<ul>";
echo "<li>Entrez votre nom d'utilisateur Steam ci-dessous</li>";
echo "</ul>";
echo "</ol>";

// Formulaire pour rechercher par nom d'utilisateur
echo "<h3>Rechercher par nom d'utilisateur Steam :</h3>";
echo "<form method='post'>";
echo "<input type='text' name='steam_username' placeholder='Votre nom d\'utilisateur Steam' required>";
echo "<button type='submit'>Rechercher</button>";
echo "</form>";

if (isset($_POST['steam_username'])) {
    $username = $_POST['steam_username'];
    echo "<h3>Résultats pour : " . htmlspecialchars($username) . "</h3>";
    
    // Rechercher via l'API Steam
    $search_url = "http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key={$steam_api_key}&vanityurl=" . urlencode($username);
    $search_data = file_get_contents($search_url);
    $search_json = json_decode($search_data, true);
    
    if (isset($search_json['response']['steamid'])) {
        $steam_id = $search_json['response']['steamid'];
        echo "<p>✅ Steam ID trouvé : <strong>$steam_id</strong></p>";
        
        // Récupérer les informations du profil
        $profile_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steam_api_key}&steamids={$steam_id}";
        $profile_data = file_get_contents($profile_url);
        $profile_json = json_decode($profile_data, true);
        
        if (isset($profile_json['response']['players'][0])) {
            $player = $profile_json['response']['players'][0];
            echo "<p>Nom d'affichage : " . htmlspecialchars($player['personaname']) . "</p>";
            echo "<p>Profil URL : <a href='" . htmlspecialchars($player['profileurl']) . "' target='_blank'>" . htmlspecialchars($player['profileurl']) . "</a></p>";
            echo "<p>Avatar : <img src='" . htmlspecialchars($player['avatar']) . "' style='width: 32px; height: 32px;'></p>";
            
            // Vérifier si le profil est public
            if ($player['communityvisibilitystate'] == 3) {
                echo "<p>✅ Profil public - L'API peut récupérer vos informations</p>";
            } else {
                echo "<p>⚠️ Profil privé - L'API ne peut pas récupérer vos informations</p>";
            }
        }
    } else {
        echo "<p>❌ Aucun Steam ID trouvé pour ce nom d'utilisateur</p>";
        echo "<p>Réponse API : " . htmlspecialchars(json_encode($search_json, JSON_PRETTY_PRINT)) . "</p>";
    }
}

echo "<h3>Test de l'URL de connexion Steam :</h3>";
echo "<p>Cliquez sur le lien ci-dessous pour tester la connexion Steam et voir quel Steam ID est récupéré :</p>";

$steam_auth_url = 'https://steamcommunity.com/openid/login';
$params = [
    'openid.ns' => 'http://specs.openid.net/auth/2.0',
    'openid.mode' => 'checkid_setup',
    'openid.return_to' => 'https://ladrio2.goodloss.fr/oauth2callback_steam.php',
    'openid.realm' => 'https://ladrio2.goodloss.fr',
    'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
    'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
];

echo "<p><a href='$steam_auth_url?" . http_build_query($params) . "' target='_blank'>🔗 Tester la connexion Steam</a></p>";

echo "<h3>Logs de connexion :</h3>";
echo "<p>Après avoir testé la connexion, regardez les logs dans le fichier de débogage pour voir quel Steam ID est récupéré.</p>";
?> 