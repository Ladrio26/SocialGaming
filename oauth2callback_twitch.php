<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Twitch.php';

session_start();

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    header('Location: index.php?auth_error=Vous devez être connecté pour lier votre compte Twitch');
    exit;
}

$twitch = new Twitch($pdo);

try {
    // Vérifier si nous avons un code d'autorisation
    if (!isset($_GET['code'])) {
        throw new Exception('Code d\'autorisation manquant');
    }
    
    // Vérifier le state pour la sécurité
    if (isset($_GET['state']) && isset($_SESSION['twitch_state']) && $_GET['state'] !== $_SESSION['twitch_state']) {
        throw new Exception('State invalide');
    }
    
    $code = $_GET['code'];
    
    // Échanger le code contre un token d'accès
    $token_response = $twitch->exchangeCodeForToken($code);
    
    // Récupérer les informations de l'utilisateur Twitch
    $twitch_user_info = $twitch->getUserInfo($token_response['access_token']);
    
    // Lier le compte Twitch à l'utilisateur
    $success = $twitch->linkAccount(
        $user['id'],
        $twitch_user_info['id'],
        $twitch_user_info['login'],
        $twitch_user_info['display_name'],
        $twitch_user_info['profile_image_url'] ?? null,
        $token_response['access_token'],
        $token_response['refresh_token'],
        $token_response['expires_in']
    );
    
    if ($success) {
        // Nettoyer le state de session
        unset($_SESSION['twitch_state']);
        
        // Rediriger vers la page de profil avec un message de succès
        header('Location: profile.php?twitch_success=1');
        exit;
    } else {
        throw new Exception('Erreur lors de la liaison du compte Twitch');
    }
    
} catch (Exception $e) {
    // Nettoyer le state de session
    unset($_SESSION['twitch_state']);
    
    // Rediriger vers la page de profil avec un message d'erreur
    header('Location: profile.php?twitch_error=' . urlencode($e->getMessage()));
    exit;
}
?> 