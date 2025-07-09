<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../classes/Discord.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$discord = new Discord($pdo);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'link':
            // Générer l'URL d'autorisation Discord pour lier un compte
            $state = bin2hex(random_bytes(16));
            $_SESSION['discord_state'] = $state;
            
            // Utiliser l'URL de redirection spécifique pour lier un compte
            $auth_url = $discord->getAuthUrl($state, 'https://ladrio2.goodloss.fr/oauth2callback_discord_link.php');
            echo json_encode(['auth_url' => $auth_url]);
            break;
            
        case 'unlink':
            // Délier le compte Discord
            $success = $discord->unlinkAccount($user['id']);
            echo json_encode(['success' => $success]);
            break;
            
        case 'status':
            // Récupérer le statut du compte Discord lié
            try {
                $linked_account = $discord->getLinkedAccount($user['id']);
                
                // Si l'utilisateur s'est inscrit directement avec Discord, créer un compte lié virtuel
                if (empty($linked_account) && $user['auth_provider'] === 'discord' && !empty($user['provider_id'])) {
                    // Créer un compte Discord lié basé sur les informations du compte principal
                    $virtual_account = [
                        'discord_user_id' => $user['provider_id'],
                        'discord_username' => $user['username'],
                        'discord_display_name' => $user['username'],
                        'discord_avatar_url' => $user['discord_avatar'] ?? null,
                        'is_active' => true
                    ];
                    
                    echo json_encode([
                        'linked' => true,
                        'account' => $virtual_account,
                        'is_primary_account' => true // Indique que c'est le compte principal
                    ]);
                } else {
                    echo json_encode([
                        'linked' => !empty($linked_account),
                        'account' => $linked_account,
                        'is_primary_account' => false
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'linked' => false,
                    'account' => null,
                    'is_primary_account' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action invalide']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 