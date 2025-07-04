<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../classes/Notification.php';
require_once '../classes/Friends.php';
require_once '../classes/UserDisplay.php';

$auth = new Auth($pdo);
$friends = new Friends($pdo);

// Vérifier si l'utilisateur est connecté
$user = $auth->isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'send_request':
            $receiver_id = $input['receiver_id'] ?? 0;
            
            if (!$receiver_id) {
                echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
                exit;
            }
            
            $result = $friends->sendFriendRequest($user['id'], $receiver_id);
            
            // Si la demande a été envoyée avec succès, créer une notification
            if ($result['success']) {
                $notification = new Notification($pdo);
                $sender_name = UserDisplay::formatDisplayName($user);
                $notification->createFriendRequest($receiver_id, $sender_name);
            }
            
            echo json_encode($result);
            break;
            
        case 'accept_request':
            $request_id = $input['request_id'] ?? 0;
            
            if (!$request_id) {
                echo json_encode(['success' => false, 'message' => 'ID de demande requis']);
                exit;
            }
            
            $result = $friends->acceptFriendRequest($request_id, $user['id']);
            
            // Si la demande a été acceptée avec succès, créer une notification
            if ($result['success'] && isset($result['sender_id'])) {
                $notification = new Notification($pdo);
                $accepter_name = UserDisplay::formatDisplayName($user);
                $notification->createFriendAccepted($result['sender_id'], $accepter_name);
            }
            
            echo json_encode($result);
            break;
            
        case 'reject_request':
            $request_id = $input['request_id'] ?? 0;
            
            if (!$request_id) {
                echo json_encode(['success' => false, 'message' => 'ID de demande requis']);
                exit;
            }
            
            $result = $friends->rejectFriendRequest($request_id, $user['id']);
            echo json_encode($result);
            break;
            
        case 'remove_friend':
            $friend_id = $input['friend_id'] ?? 0;
            
            if (!$friend_id) {
                echo json_encode(['success' => false, 'message' => 'ID ami requis']);
                exit;
            }
            
            $result = $friends->removeFriend($user['id'], $friend_id);
            echo json_encode($result);
            break;
            
        case 'get_friends':
            $target_user_id = $input['user_id'] ?? $user['id'];
            
            // Vérifier que l'utilisateur demandé existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$target_user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                exit;
            }
            
            $friends_list = $friends->getFriends($target_user_id);
            echo json_encode(['success' => true, 'friends' => $friends_list]);
            break;
            
        case 'get_received_requests':
            $requests = $friends->getReceivedFriendRequests($user['id']);
            echo json_encode(['success' => true, 'requests' => $requests]);
            break;
            
        case 'get_sent_requests':
            $requests = $friends->getSentFriendRequests($user['id']);
            echo json_encode(['success' => true, 'requests' => $requests]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 