<?php
require_once '../config.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$currentUser = $auth->isLoggedIn();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $avatars = [];
    
    // Récupérer l'avatar Discord si disponible
    if ($currentUser['auth_provider'] === 'discord' && !empty($currentUser['discord_avatar'])) {
        $avatars[] = [
            'id' => 'discord',
            'name' => 'Avatar Discord',
            'url' => $currentUser['discord_avatar'],
            'provider' => 'discord',
            'provider_icon' => 'fab fa-discord'
        ];
    }
    
    // Récupérer l'avatar Steam si disponible
    $steamStmt = $pdo->prepare("SELECT steam_avatar FROM steam_accounts WHERE user_id = ?");
    $steamStmt->execute([$currentUser['id']]);
    $steamAccount = $steamStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($steamAccount && !empty($steamAccount['steam_avatar'])) {
        $avatars[] = [
            'id' => 'steam',
            'name' => 'Avatar Steam',
            'url' => $steamAccount['steam_avatar'],
            'provider' => 'steam',
            'provider_icon' => 'fab fa-steam'
        ];
    }
    
    // Ajouter l'avatar actuel s'il existe et n'est pas déjà dans la liste
    if (!empty($currentUser['avatar_url'])) {
        $currentAvatarExists = false;
        foreach ($avatars as $avatar) {
            if ($avatar['url'] === $currentUser['avatar_url']) {
                $currentAvatarExists = true;
                break;
            }
        }
        
        if (!$currentAvatarExists) {
            $avatars[] = [
                'id' => 'current',
                'name' => 'Avatar actuel',
                'url' => $currentUser['avatar_url'],
                'provider' => 'custom',
                'provider_icon' => 'fas fa-user'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'avatars' => $avatars,
        'current_avatar' => $currentUser['avatar_url']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?> 