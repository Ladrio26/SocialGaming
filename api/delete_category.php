<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../includes/RoleManager.php';

header('Content-Type: application/json');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

$roleManager = new RoleManager($pdo);

// Vérifier les permissions de modération
if (!$roleManager->hasPermission($user['id'], 'moderate_categories')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les permissions pour supprimer des catégories']);
    exit;
}

// Récupérer l'ID de la catégorie
$category_id = (int)($_POST['category_id'] ?? 0);

if (!$category_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de catégorie manquant']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Vérifier que la catégorie existe et ne contient pas de posts
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(p.id) as posts_count 
        FROM categories c 
        LEFT JOIN category_posts p ON c.id = p.category_id 
        WHERE c.id = ? 
        GROUP BY c.id
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception('Catégorie non trouvée');
    }
    
    if ($category['posts_count'] > 0) {
        throw new Exception('Impossible de supprimer une catégorie contenant des posts');
    }
    
    // Supprimer la catégorie
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Erreur lors de la suppression de la catégorie');
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Catégorie supprimée avec succès',
        'category_name' => $category['name']
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
    ]);
}
?> 