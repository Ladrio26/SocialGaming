<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../includes/date_utils.php';
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

// Vérification du bannissement
require_once '../includes/RoleManager.php';
$roleManager = new RoleManager($pdo);
if ($roleManager->isBanned($user['id'])) {
    echo json_encode(['success' => false, 'message' => 'Votre compte a été suspendu']);
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
                $notification->createFriendRequest($receiver_id, $user['id'], $sender_name);
            }
            
            echo json_encode($result);
            break;
            
        case 'accept_request':
            $sender_id = $input['sender_id'] ?? 0;
            $request_id = $input['request_id'] ?? 0;

            // Log pour debug
            file_put_contents('debug_api_friends.log', getCurrentDateParis() . " | action: accept_request | sender_id: $sender_id | request_id: $request_id | user_id: {$user['id']}\n", FILE_APPEND);

            if (!$sender_id && !$request_id) {
                echo json_encode(['success' => false, 'message' => 'ID de demande ou ID expéditeur requis']);
                exit;
            }
            
            // Si on a un sender_id, trouver la demande correspondante
            if ($sender_id && !$request_id) {
                $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
                $stmt->execute([$sender_id, $user['id']]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($request) {
                    $request_id = $request['id'];
                } else {
                    echo json_encode(['success' => false, 'message' => 'Demande d\'ami non trouvée']);
                    exit;
                }
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
            $sender_id = $input['sender_id'] ?? 0;
            $request_id = $input['request_id'] ?? 0;

            // Log pour debug
            file_put_contents('debug_api_friends.log', getCurrentDateParis() . " | action: reject_request | sender_id: $sender_id | request_id: $request_id | user_id: {$user['id']}\n", FILE_APPEND);

            if (!$sender_id && !$request_id) {
                echo json_encode(['success' => false, 'message' => 'ID de demande ou ID expéditeur requis']);
                exit;
            }
            
            // Si on a un sender_id, trouver la demande correspondante
            if ($sender_id && !$request_id) {
                $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
                $stmt->execute([$sender_id, $user['id']]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($request) {
                    $request_id = $request['id'];
                } else {
                    echo json_encode(['success' => false, 'message' => 'Demande d\'ami non trouvée']);
                    exit;
                }
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
            
        case 'check_friend_status':
            $target_user_id = $input['user_id'] ?? 0;
            
            if (!$target_user_id) {
                echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
                exit;
            }
            
            // Vérifier si les utilisateurs sont déjà amis
            $stmt = $pdo->prepare("SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$user['id'], $target_user_id, $target_user_id, $user['id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => true, 'status' => 'friends']);
                exit;
            }
            
            // Vérifier si une demande a été envoyée par l'utilisateur connecté
            $stmt = $pdo->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
            $stmt->execute([$user['id'], $target_user_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => true, 'status' => 'pending_sent']);
                exit;
            }
            
            // Vérifier si une demande a été reçue par l'utilisateur connecté
            $stmt = $pdo->prepare("SELECT 1 FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
            $stmt->execute([$target_user_id, $user['id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => true, 'status' => 'pending_received']);
                exit;
            }
            
            // Aucune relation
            echo json_encode(['success' => true, 'status' => 'none']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 