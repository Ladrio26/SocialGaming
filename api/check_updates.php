<?php
require_once '../config.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    echo json_encode([
        'success' => false, 
        'error' => 'Non autorisé - Utilisateur non connecté',
        'debug' => [
            'session_id' => session_id(),
            'session_status' => session_status()
        ]
    ]);
    exit;
}

// Récupérer les paramètres
$last_check = (int)($_GET['last_check'] ?? 0);
$category_id = (int)($_GET['category_id'] ?? 0);

try {
    $updates = [];
    
    // 1. Vérifier les nouveaux posts dans la catégorie actuelle
    if ($category_id > 0) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM category_posts 
            WHERE category_id = ? AND created_at > FROM_UNIXTIME(?)
        ");
        $stmt->execute([$category_id, $last_check]);
        $new_posts = $stmt->fetch()['count'];
        
        if ($new_posts > 0) {
            $updates['category_posts'] = $new_posts;
        }
    }
    
    // 2. Vérifier les nouveaux messages non lus dans toutes les catégories
    // Version simplifiée et plus robuste
    $stmt = $pdo->prepare("
        SELECT 
            c.id as category_id,
            c.name as category_name,
            COUNT(cp.id) as unread_count
        FROM categories c
        LEFT JOIN category_posts cp ON c.id = cp.category_id
        LEFT JOIN category_visits cv ON c.id = cv.category_id AND cv.user_id = ?
        WHERE cp.created_at > COALESCE(cv.last_visit_at, '1970-01-01')
        GROUP BY c.id, c.name
        HAVING unread_count > 0
    ");
    $stmt->execute([$user['id']]);
    $unread_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($unread_categories)) {
        $updates['unread_categories'] = $unread_categories;
    }
    
    // 3. Vérifier les nouveaux posts récents (pour la page d'accueil)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM category_posts 
        WHERE created_at > FROM_UNIXTIME(?)
    ");
    $stmt->execute([$last_check]);
    $recent_posts = $stmt->fetch()['count'];
    
    if ($recent_posts > 0) {
        $updates['recent_posts'] = $recent_posts;
    }
    
    // 4. Vérifier les nouvelles demandes d'amis
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM friend_requests 
        WHERE receiver_id = ? AND status = 'pending' AND created_at > FROM_UNIXTIME(?)
    ");
    $stmt->execute([$user['id'], $last_check]);
    $new_friend_requests = $stmt->fetch()['count'];
    
    if ($new_friend_requests > 0) {
        $updates['friend_requests'] = $new_friend_requests;
    }
    
    // 5. Vérifier les nouvelles notifications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 AND created_at > FROM_UNIXTIME(?)
    ");
    $stmt->execute([$user['id'], $last_check]);
    $new_notifications = $stmt->fetch()['count'];
    
    if ($new_notifications > 0) {
        $updates['notifications'] = $new_notifications;
    }
    
    echo json_encode([
        'success' => true,
        'has_updates' => !empty($updates),
        'updates' => $updates,
        'timestamp' => time(),
        'debug' => [
            'last_check' => $last_check,
            'category_id' => $category_id,
            'user_id' => $user['id']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur lors de la vérification des mises à jour: ' . $e->getMessage()
    ]);
}
?> 