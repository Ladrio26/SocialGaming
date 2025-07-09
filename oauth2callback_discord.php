<?php
require_once 'config.php';
require_once 'config_discord.php';
require_once 'classes/Auth.php';

session_start();

// Vérifier si le code est présent
if (!isset($_GET['code'])) {
    header('Location: index.php?auth_error=Code d\'autorisation manquant');
    exit;
}

// Vérifier le state pour la sécurité CSRF
if (isset($_GET['state']) && isset($_SESSION['discord_state'])) {
    if ($_GET['state'] !== $_SESSION['discord_state']) {
        header('Location: index.php?auth_error=State invalide - tentative de sécurité détectée');
        exit;
    }
    // Nettoyer le state après vérification
    unset($_SESSION['discord_state']);
}

try {
    // 1. Échanger le code contre un token d'accès
    $token_url = DISCORD_TOKEN_URL;
    $data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => DISCORD_REDIRECT_URI,
        'scope' => DISCORD_SCOPES
    ];

    // Utiliser cURL au lieu de file_get_contents
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Erreur cURL lors de la récupération du token Discord : ' . $error);
    }

    if ($response === FALSE || $http_code >= 400) {
        throw new Exception('Erreur lors de la récupération du token Discord. Code HTTP : ' . $http_code);
    }

    $token = json_decode($response, true);

    if (isset($token['error'])) {
        throw new Exception('Erreur Discord : ' . ($token['error_description'] ?? $token['error']));
    }

    // 2. Récupérer les informations de l'utilisateur
    $user_url = DISCORD_USER_URL;

    // Utiliser cURL pour récupérer les infos utilisateur
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token['access_token']]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $user_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Erreur cURL lors de la récupération des informations utilisateur Discord : ' . $error);
    }

    if ($user_response === FALSE || $http_code >= 400) {
        throw new Exception('Erreur lors de la récupération des informations utilisateur Discord. Code HTTP : ' . $http_code);
    }

    $user_data = json_decode($user_response, true);

    if (isset($user_data['error'])) {
        throw new Exception('Erreur Discord : ' . $user_data['error']);
    }

    // 3. Préparer les données pour la méthode handleDiscordAuth
    // Gestion moderne des usernames Discord (sans discriminator)
    $username = $user_data['username'];
    
    // Utiliser le nom d'affichage global si disponible (nouveau système Discord)
    if (isset($user_data['global_name']) && !empty($user_data['global_name'])) {
        $display_name = $user_data['global_name'];
    } else {
        // Fallback pour les anciens comptes avec discriminator
        $display_name = $username;
        if (isset($user_data['discriminator']) && $user_data['discriminator'] !== '0') {
            $display_name .= '#' . $user_data['discriminator'];
        }
    }

    // URL d'avatar optimisée avec WebP
    $avatar_url = null;
    if (isset($user_data['avatar'])) {
        $avatar_url = 'https://cdn.discordapp.com/avatars/' . $user_data['id'] . '/' . $user_data['avatar'] . '.webp?size=128';
    }

    $discord_data = [
        'id' => $user_data['id'],
        'username' => $username,
        'display_name' => $display_name,
        'email' => $user_data['email'] ?? '',
        'avatar' => $avatar_url
    ];

    // 4. Authentifier ou inscrire l'utilisateur
    $auth = new Auth($pdo);
    $result = $auth->handleDiscordAuth($discord_data);

    if ($result['success']) {
        // Redirection vers la page principale avec un message de succès
        header('Location: index.php?auth_success=discord');
        exit;
    } else {
        // En cas d'erreur, rediriger avec le message d'erreur
        header('Location: index.php?auth_error=' . urlencode($result['message']));
        exit;
    }

} catch (Exception $e) {
    // Log de l'erreur pour le débogage
    error_log('Erreur authentification Discord: ' . $e->getMessage());
    
    // Redirection avec message d'erreur
    header('Location: index.php?auth_error=' . urlencode('Erreur lors de la connexion avec Discord : ' . $e->getMessage()));
    exit;
}
?> 