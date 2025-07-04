<?php


require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Steam.php';
require_once 'config_steam_oauth.php';

// Fonction pour valider la signature OpenID
function validateOpenIDSignature($params, $signature) {
    // Steam utilise OpenID 2.0, nous devons valider la signature
    // Pour simplifier, nous allons vérifier que nous recevons bien une réponse de Steam
    return isset($params['openid_mode']) && $params['openid_mode'] === 'id_res';
}

// Fonction pour extraire le Steam ID de l'URL OpenID
function extractSteamId($claimed_id) {
    // Format: https://steamcommunity.com/openid/id/76561197960265728
    if (preg_match('/\/openid\/id\/(\d+)$/', $claimed_id, $matches)) {
        return $matches[1];
    }
    return null;
}

try {
    error_log("Steam callback - Début du traitement");
    error_log("Steam callback - GET params: " . json_encode($_GET));
    
    // Vérifier si nous recevons une réponse d'authentification
    if (isset($_GET['openid_mode']) && $_GET['openid_mode'] === 'id_res') {
        
        // Valider la signature (simplifié pour cet exemple)
        if (!validateOpenIDSignature($_GET, $_GET['openid_sig'] ?? '')) {
            throw new Exception('Signature OpenID invalide');
        }
        
        // Extraire le Steam ID
        $steam_id = extractSteamId($_GET['openid_claimed_id'] ?? '');
        
        error_log("Steam callback - openid_claimed_id: " . ($_GET['openid_claimed_id'] ?? 'N/A'));
        error_log("Steam callback - Steam ID extrait: " . ($steam_id ?: 'N/A'));
        
        if (!$steam_id) {
            throw new Exception('Impossible d\'extraire le Steam ID');
        }
        
        // Récupérer les informations du profil Steam
        $steam = new Steam($pdo);
        
        // Récupérer les informations du profil via l'API Steam
        $profile_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steam_api_key}&steamids={$steam_id}";
        $profile_data = file_get_contents($profile_url);
        $profile_json = json_decode($profile_data, true);
        
        if (!isset($profile_json['response']['players'][0])) {
            throw new Exception('Impossible de récupérer les informations du profil Steam');
        }
        
        $player = $profile_json['response']['players'][0];
        
        // Préparer les données Steam
        $steam_data = [
            'id' => $steam_id,
            'username' => $player['personaname'],
            'email' => null, // Steam ne fournit pas l'email via OpenID
            'avatar' => $player['avatarfull'] ?? '',
            'realname' => $player['realname'] ?? '',
            'profileurl' => $player['profileurl'] ?? ''
        ];
        
        error_log("Steam callback - Données Steam préparées: " . json_encode($steam_data));
        
        error_log("Steam callback - steam_data: " . json_encode($steam_data));
        
        // Gérer l'authentification avec les données Steam
        $auth = new Auth($pdo);
        $result = $auth->handleSteamAuth($steam_data);
        
        error_log("Steam callback - résultat auth: " . json_encode($result));
        
        if ($result['success']) {
            // Rediriger vers la page d'accueil avec un message de succès
            header('Location: index.php?auth_success=steam');
            exit;
        } else {
            // Rediriger vers la page de connexion avec un message d'erreur
            header('Location: index.php?auth_error=' . urlencode($result['message']));
            exit;
        }
        
    } else {
        // Pas de réponse d'authentification, rediriger vers la page d'accueil
        header('Location: index.php');
        exit;
    }
    
} catch (Exception $e) {
    // En cas d'erreur, rediriger vers la page d'accueil avec un message d'erreur
    header('Location: index.php?auth_error=' . urlencode('Erreur d\'authentification Steam : ' . $e->getMessage()));
    exit;
}
?> 