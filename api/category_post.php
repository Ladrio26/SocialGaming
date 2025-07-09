<?php
require_once '../config.php';
require_once '../includes/date_utils.php';
require_once '../classes/Auth.php';
require_once '../includes/RoleManager.php';
header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$roleManager = new RoleManager($pdo);

// DELETE : suppression d'un post
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
    
    if (!$post_id) {
        echo json_encode(['success' => false, 'error' => 'ID de post manquant']);
        exit;
    }
    
    // Vérifier que l'utilisateur est l'auteur du post
    $stmt = $pdo->prepare('SELECT user_id, image_filename FROM category_posts WHERE id = ?');
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post introuvable']);
        exit;
    }
    
    // Vérifier que l'utilisateur est l'auteur du post OU qu'il a les permissions de modération
    if ($post['user_id'] !== $user['id'] && !$roleManager->hasPermission($user['id'], 'delete_posts')) {
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit;
    }
    
    // Supprimer l'image si elle existe
    if ($post['image_filename']) {
        $image_path = '../uploads/category_images/' . $post['image_filename'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Supprimer le post
    $stmt = $pdo->prepare('DELETE FROM category_posts WHERE id = ?');
    $stmt->execute([$post_id]);
    
    echo json_encode(['success' => true]);
    exit;
}

// POST : ajout d'un post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $content = trim($_POST['content'] ?? '');
    $image_url = null;
    $image_filename = null;
    $max_size = 5 * 1024 * 1024; // 5 Mo

    // Gestion de l'image (upload ou collage)
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        if (strpos($file['type'], 'image/') !== 0) {
            echo json_encode(['success' => false, 'error' => 'Type de fichier non supporté']);
            exit;
        }
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'error' => 'Image trop volumineuse (max 5Mo)']);
            exit;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('catimg_') . '.' . $ext;
        $dir = '../uploads/category_images/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $dest = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'error' => 'Erreur upload image']);
            exit;
        }
        $image_url = 'uploads/category_images/' . $filename;
        $image_filename = $filename;
    }
    // Insertion en base
    $stmt = $pdo->prepare('INSERT INTO category_posts (category_id, user_id, content, image_url, image_filename) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$category_id, $user['id'], $content, $image_url, $image_filename]);
    echo json_encode(['success' => true]);
    exit;
}

// GET : liste des posts d'une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['category_id'])) {
    $category_id = (int)$_GET['category_id'];
    
    // Vérifier si l'utilisateur est modérateur ou admin
    $is_moderator = $roleManager->hasPermission($user['id'], 'moderate_categories') || 
                   $roleManager->hasPermission($user['id'], 'access_admin_panel');
    
    // Vérifier si l'utilisateur a accès à cette catégorie (sauf pour les modérateurs)
    if (!$is_moderator) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(ucp.is_visible, TRUE) as is_visible
            FROM categories c
            LEFT JOIN user_category_preferences ucp ON c.id = ucp.category_id AND ucp.user_id = ?
            WHERE c.id = ?
        ");
        $stmt->execute([$user['id'], $category_id]);
        $category_access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category_access || !$category_access['is_visible']) {
            echo json_encode(['success' => false, 'error' => 'Accès à cette catégorie non autorisé']);
            exit;
        }
    }
    
    // Paramètre pour forcer l'affichage des amis uniquement (pour les modérateurs)
    $friends_only = isset($_GET['friends_only']) && $_GET['friends_only'] === 'true';
    
    try {
        if ($is_moderator && !$friends_only) {
            // Modérateurs et admins voient tous les posts (mode modération)
            $stmt = $pdo->prepare('
                SELECT p.*, u.username, u.avatar_url 
                FROM category_posts p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.category_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT 50
            ');
            $stmt->execute([$category_id]);
        } else {
            // Utilisateurs normaux ou modérateurs en mode "amis uniquement" 
            // Récupérer les posts de l'utilisateur ET de ses amis
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.*, u.username, u.avatar_url 
                FROM category_posts p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.category_id = ? 
                AND (
                    p.user_id = ? 
                    OR p.user_id IN (
                        SELECT DISTINCT 
                            CASE 
                                WHEN user_id = ? THEN friend_id 
                                WHEN friend_id = ? THEN user_id 
                            END as friend_id
                        FROM friends 
                        WHERE (user_id = ? OR friend_id = ?)
                    )
                )
                ORDER BY p.created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$category_id, $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
        }
        
        $posts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $author = $row['username'];
            $posts[] = [
                'id' => $row['id'],
                'author' => htmlspecialchars($author),
                'avatar_url' => $row['avatar_url'],
                'content' => htmlspecialchars($row['content']),
                'image_url' => $row['image_url'],
                'created_at' => formatDateTime($row['created_at']),
                'is_author' => ($row['user_id'] === $user['id']),
                'can_delete' => ($row['user_id'] === $user['id'] || $roleManager->hasPermission($user['id'], 'delete_posts')),
            ];
        }
        
        // Ajouter des informations sur le mode d'affichage pour les modérateurs
        $response = [
            'success' => true, 
            'posts' => $posts,
            'is_moderator' => $is_moderator,
            'friends_only' => $friends_only,
            'debug' => [
                'category_id' => $category_id,
                'user_id' => $user['id'],
                'posts_count' => count($posts)
            ]
        ];
        
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Erreur SQL: ' . $e->getMessage(),
            'debug' => [
                'category_id' => $category_id,
                'user_id' => $user['id'],
                'is_moderator' => $is_moderator,
                'friends_only' => $friends_only
            ]
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Requête invalide']); 