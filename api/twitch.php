<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../classes/Twitch.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$twitch = new Twitch($pdo);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'link':
            // Générer l'URL d'autorisation Twitch
            $state = bin2hex(random_bytes(16));
            $_SESSION['twitch_state'] = $state;
            
            $auth_url = $twitch->getAuthUrl($state);
            echo json_encode(['auth_url' => $auth_url]);
            break;
            
        case 'unlink':
            // Délier le compte Twitch
            $success = $twitch->unlinkAccount($user['id']);
            echo json_encode(['success' => $success]);
            break;
            
        case 'status':
            // Récupérer le statut du compte Twitch lié
            $linked_account = $twitch->getLinkedAccount($user['id']);
            echo json_encode([
                'linked' => !empty($linked_account),
                'account' => $linked_account
            ]);
            break;
            
        case 'streams':
            // Récupérer les streams en direct
            $limit = min(20, intval($_GET['limit'] ?? 10));
            $streams = $twitch->getLiveStreamsFromCache($limit);
            
            echo json_encode([
                'streams' => $streams,
                'count' => count($streams)
            ]);
            break;
            
        case 'update_streams':
            // Mettre à jour le cache des streams (appelé par un cron job)
            if (!isset($_GET['secret']) || $_GET['secret'] !== TWITCH_UPDATE_SECRET) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès refusé']);
                exit;
            }
            
            // Récupérer tous les comptes Twitch actifs
            $stmt = $pdo->prepare("
                SELECT twitch_username FROM twitch_accounts 
                WHERE is_active = TRUE
            ");
            $stmt->execute();
            $usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($usernames)) {
                // Récupérer les streams en direct
                $streams = $twitch->getStreamsByUsernames($usernames);
                
                // Mettre à jour le cache
                $twitch->updateStreamsCache($streams);
                
                echo json_encode([
                    'success' => true,
                    'updated_streams' => count($streams),
                    'total_accounts' => count($usernames)
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'updated_streams' => 0,
                    'total_accounts' => 0
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