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
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$content_type = $_GET['type'] ?? '';
$category_id = (int)($_GET['category_id'] ?? 0);
$last_check = (int)($_GET['last_check'] ?? 0);

try {
    switch ($content_type) {
        case 'recent_posts':
            // Récupérer les nouveaux posts récents
            $stmt = $pdo->prepare("
                SELECT 
                    cp.id,
                    cp.content,
                    cp.image_url,
                    cp.created_at,
                    cp.category_id,
                    c.name as category_name,
                    u.username as author,
                    u.avatar_url as author_avatar
                FROM category_posts cp
                JOIN categories c ON cp.category_id = c.id
                JOIN users u ON cp.user_id = u.id
                WHERE cp.created_at > FROM_UNIXTIME(?)
                ORDER BY cp.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$last_check]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les posts pour l'affichage
            $formatted_posts = [];
            foreach ($posts as $post) {
                $formatted_posts[] = [
                    'id' => $post['id'],
                    'content' => $post['content'],
                    'image_url' => $post['image_url'],
                    'created_at' => date('d/m/Y H:i', strtotime($post['created_at'])),
                    'category_id' => $post['category_id'],
                    'category_name' => $post['category_name'],
                    'author' => [
                        'username' => $post['author'],
                        'avatar_url' => $post['author_avatar']
                    ]
                ];
            }
            
            echo json_encode([
                'success' => true,
                'type' => 'recent_posts',
                'posts' => $formatted_posts,
                'timestamp' => time()
            ]);
            break;
            
        case 'category_posts':
            // Récupérer les nouveaux posts d'une catégorie
            if (!$category_id) {
                throw new Exception('ID de catégorie manquant');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    cp.id,
                    cp.content,
                    cp.image_url,
                    cp.created_at,
                    cp.user_id,
                    u.username as author,
                    u.avatar_url as author_avatar
                FROM category_posts cp
                JOIN users u ON cp.user_id = u.id
                WHERE cp.category_id = ? AND cp.created_at > FROM_UNIXTIME(?)
                ORDER BY cp.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$category_id, $last_check]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les posts pour l'affichage
            $formatted_posts = [];
            foreach ($posts as $post) {
                $formatted_posts[] = [
                    'id' => $post['id'],
                    'content' => $post['content'],
                    'image_url' => $post['image_url'],
                    'created_at' => date('d/m/Y H:i', strtotime($post['created_at'])),
                    'author' => $post['author'],
                    'author_avatar' => $post['author_avatar'],
                    'can_delete' => ($post['user_id'] == $user['id'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'type' => 'category_posts',
                'category_id' => $category_id,
                'posts' => $formatted_posts,
                'timestamp' => time()
            ]);
            break;
            
        case 'categories_list':
            // Récupérer la liste mise à jour des catégories avec badges
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.description,
                    COALESCE(unread_count.count, 0) as unread_count
                FROM categories c
                LEFT JOIN (
                    SELECT 
                        cp.category_id,
                        COUNT(cp.id) as count
                    FROM category_posts cp
                    LEFT JOIN category_visits cv ON cp.category_id = cv.category_id AND cv.user_id = ?
                    WHERE cp.created_at > COALESCE(cv.last_visit_at, '1970-01-01 00:00:00')
                    GROUP BY cp.category_id
                ) unread_count ON c.id = unread_count.category_id
                ORDER BY c.name ASC
            ");
            $stmt->execute([$user['id']]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'type' => 'categories_list',
                'categories' => $categories,
                'timestamp' => time()
            ]);
            break;
            
        default:
            throw new Exception('Type de contenu invalide');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur lors de la récupération du contenu: ' . $e->getMessage()
    ]);
}
?> 