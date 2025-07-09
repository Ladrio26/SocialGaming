<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Discord.php';

session_start();

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    header('Location: index.php?auth_error=Vous devez être connecté pour lier votre compte Discord');
    exit;
}

$discord = new Discord($pdo);

try {
    // Vérifier si nous avons un code d'autorisation
    if (!isset($_GET['code'])) {
        throw new Exception('Code d\'autorisation manquant');
    }
    
    // Vérifier le state pour la sécurité
    if (isset($_GET['state']) && isset($_SESSION['discord_state']) && $_GET['state'] !== $_SESSION['discord_state']) {
        throw new Exception('State invalide');
    }
    
    $code = $_GET['code'];
    
    // Échanger le code contre un token d'accès
    $token_response = $discord->exchangeCodeForToken($code, 'https://ladrio2.goodloss.fr/oauth2callback_discord_link.php');
    
    // Récupérer les informations de l'utilisateur Discord
    $discord_user_info = $discord->getUserInfo($token_response['access_token']);
    
    // Préparer l'URL de l'avatar Discord
    $avatar_url = null;
    if (isset($discord_user_info['avatar'])) {
        // Utiliser le format WebP par défaut, avec fallback PNG
        $avatar_url = 'https://cdn.discordapp.com/avatars/' . $discord_user_info['id'] . '/' . $discord_user_info['avatar'] . '.webp?size=128';
    }
    
    // Préparer le nom d'affichage
    $display_name = $discord_user_info['username'];
    // Discord a supprimé les discriminators pour les nouveaux comptes
    // On utilise le nom d'affichage global si disponible
    if (isset($discord_user_info['global_name']) && !empty($discord_user_info['global_name'])) {
        $display_name = $discord_user_info['global_name'];
    } elseif (isset($discord_user_info['discriminator']) && $discord_user_info['discriminator'] !== '0') {
        $display_name .= '#' . $discord_user_info['discriminator'];
    }
    
    // Lier le compte Discord à l'utilisateur
    $success = $discord->linkAccount(
        $user['id'],
        $discord_user_info['id'],
        $discord_user_info['username'],
        $display_name,
        $avatar_url,
        $token_response['access_token'],
        $token_response['refresh_token'],
        $token_response['expires_in']
    );
    
    if ($success) {
        // Nettoyer le state de session
        unset($_SESSION['discord_state']);
        
        // Rediriger vers la page de profil avec un message de succès
        header('Location: profile.php?discord_success=1');
        exit;
    } else {
        throw new Exception('Erreur lors de la liaison du compte Discord');
    }
    
} catch (Exception $e) {
    // Nettoyer le state de session
    unset($_SESSION['discord_state']);
    
    // Rediriger vers la page de profil avec un message d'erreur
    header('Location: profile.php?discord_error=' . urlencode($e->getMessage()));
    exit;
}
?> 