<?php
require_once '../config.php';
require_once '../classes/Auth.php';
header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

try {
    if ($user) {
        // Récupérer les catégories avec les préférences de l'utilisateur et les nouveaux messages
        $stmt = $pdo->prepare("
            SELECT 
                c.id, 
                c.name, 
                c.description, 
                c.color, 
                c.icon,
                COALESCE(ucp.is_visible, TRUE) as is_visible,
                COALESCE(unread_count.count, 0) as unread_count
            FROM categories c
            LEFT JOIN user_category_preferences ucp ON c.id = ucp.category_id AND ucp.user_id = ?
            LEFT JOIN (
                SELECT 
                    cp.category_id,
                    COUNT(cp.id) as count
                FROM category_posts cp
                LEFT JOIN category_visits cv ON cp.category_id = cv.category_id AND cv.user_id = ?
                WHERE cp.created_at > COALESCE(cv.last_visit_at, '1970-01-01 00:00:00')
                GROUP BY cp.category_id
            ) unread_count ON c.id = unread_count.category_id
            WHERE COALESCE(ucp.is_visible, TRUE) = TRUE
            ORDER BY c.name ASC
        ");
        $stmt->execute([$user['id'], $user['id']]);
    } else {
        // Pour les utilisateurs non connectés, afficher toutes les catégories
        $stmt = $pdo->query("SELECT id, name, description, color, icon, 0 as unread_count FROM categories ORDER BY name ASC");
    }
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 