<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../includes/RoleManager.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
// Mode debug : permettre de forcer l'utilisateur via ?debug_user_id=ID
if (php_sapi_name() === 'cli' && isset($_GET['debug_user_id'])) {
    $user = $pdo->query('SELECT * FROM users WHERE id = ' . (int)$_GET['debug_user_id'])->fetch(PDO::FETCH_ASSOC);
} else {
    $user = $auth->isLoggedIn();
}

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

// Initialisation du gestionnaire de rôles
$roleManager = new RoleManager($pdo);

// Vérification du bannissement
if ($roleManager->isBanned($user['id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur banni']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Récupérer les amis de l'utilisateur (relations bidirectionnelles)
        // La table friends n'a pas de colonne status, les amitiés sont automatiquement acceptées
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN user_id = ? THEN friend_id 
                    WHEN friend_id = ? THEN user_id 
                END as friend_id
            FROM friends 
            WHERE (user_id = ? OR friend_id = ?)
        ");
        $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
        $friends = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($friends)) {
            echo json_encode([
                'success' => true, 
                'posts' => [],
                'message' => 'Aucun ami trouvé'
            ]);
            exit;
        }
        
        // Récupérer les derniers posts avec images des amis
        // On récupère tous les posts des utilisateurs qui sont actuellement amis,
        // peu importe quand l'amitié a été établie
        // ET on filtre selon les préférences de catégories de l'utilisateur
        $placeholders = str_repeat('?,', count($friends) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.content,
                p.image_url,
                p.created_at,
                p.category_id,
                u.id as user_id,
                u.username,
                u.avatar_url,
                c.name as category_name
            FROM category_posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.user_id IN ($placeholders)
            AND p.image_url IS NOT NULL
            AND p.image_url != ''
            AND (
                p.category_id IS NULL 
                OR p.category_id IN (
                    SELECT c2.id 
                    FROM categories c2
                    LEFT JOIN user_category_preferences ucp ON c2.id = ucp.category_id AND ucp.user_id = ?
                    WHERE COALESCE(ucp.is_visible, TRUE) = TRUE
                )
            )
            ORDER BY p.created_at DESC
            LIMIT 20
        ");
        
        // Ajouter l'ID de l'utilisateur à la fin du tableau de paramètres
        $params = array_merge($friends, [$user['id']]);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les posts
        $formatted_posts = [];
        $post_ids = [];
        foreach ($posts as $post) {
            $formatted_posts[] = [
                'id' => $post['id'],
                'content' => htmlspecialchars($post['content']),
                'image_url' => $post['image_url'],
                'created_at' => formatDateTime($post['created_at']),
                'category_name' => $post['category_name'] ? htmlspecialchars($post['category_name']) : 'Général',
                'category_id' => $post['category_id'],
                'author' => [
                    'id' => $post['user_id'],
                    'username' => htmlspecialchars($post['username']),
                    'avatar_url' => $post['avatar_url']
                ]
            ];
            $post_ids[] = $post['id'];
        }
        
        echo json_encode([
            'success' => true,
            'posts' => $formatted_posts,
            'count' => count($formatted_posts),
            'debug' => [
                'user_id' => $user['id'],
                'friends_count' => count($friends),
                'friends_ids' => $friends,
                'post_ids' => $post_ids
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Erreur lors de la récupération des posts: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}

// function formatDateTime($datetime) {
//     $date = new DateTime($datetime);
//     $now = new DateTime();
//     $diff = $now->diff($date);
    
//     if ($diff->days > 0) {
//         return $date->format('d/m/Y à H:i');
//     } elseif ($diff->h > 0) {
//         return "Il y a {$diff->h}h";
//     } elseif ($diff->i > 0) {
//         return "Il y a {$diff->i}min";
//     } else {
//         return "À l'instant";
//     }
// }
?> 