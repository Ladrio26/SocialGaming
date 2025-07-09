<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../classes/Notification.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$currentUser = $auth->isLoggedIn();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Vérification du bannissement
require_once '../includes/RoleManager.php';
$roleManager = new RoleManager($pdo);
if ($roleManager->isBanned($currentUser['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Votre compte a été suspendu']);
    exit;
}

$notification = new Notification($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $notifications = $notification->getForUser($currentUser['id'], $limit, $offset, $unread_only);
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        case 'count':
            $unread_count = $notification->getUnreadCount($currentUser['id']);
            echo json_encode([
                'success' => true,
                'count' => $unread_count
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $notification_id = (int)($input['notification_id'] ?? 0);
            
            if (!$notification_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de notification manquant']);
                exit;
            }
            
            $result = $notification->markAsRead($notification_id, $currentUser['id']);
            echo json_encode($result);
            break;
            
        case 'mark_all_read':
            $result = $notification->markAllAsRead($currentUser['id']);
            echo json_encode($result);
            break;
            
        case 'delete':
            $notification_id = (int)($input['notification_id'] ?? 0);
            
            if (!$notification_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de notification manquant']);
                exit;
            }
            
            $result = $notification->delete($notification_id, $currentUser['id']);
            echo json_encode($result);
            break;
            
        case 'delete_all':
            $result = $notification->deleteAll($currentUser['id']);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?> 