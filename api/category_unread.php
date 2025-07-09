<?php
require_once '../config.php';
require_once '../classes/Auth.php';
header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'count':
            // Compter les nouveaux messages pour toutes les catégories
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as category_id,
                    c.name as category_name,
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
            $unread_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'unread_counts' => $unread_counts
            ]);
            break;
            
        case 'mark_visited':
            // Marquer une catégorie comme visitée
            $category_id = (int)($_POST['category_id'] ?? 0);
            
            if (!$category_id) {
                throw new Exception('ID de catégorie manquant');
            }
            
            // Vérifier que la catégorie existe
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Catégorie introuvable');
            }
            
            // Insérer ou mettre à jour la visite
            $stmt = $pdo->prepare("
                INSERT INTO category_visits (user_id, category_id, last_visit_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE last_visit_at = NOW()
            ");
            $stmt->execute([$user['id'], $category_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Visite enregistrée'
            ]);
            break;
            
        case 'mark_all_visited':
            // Marquer toutes les catégories comme visitées
            $stmt = $pdo->prepare("
                INSERT INTO category_visits (user_id, category_id, last_visit_at)
                SELECT ?, id, NOW()
                FROM categories
                ON DUPLICATE KEY UPDATE last_visit_at = NOW()
            ");
            $stmt->execute([$user['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Toutes les catégories marquées comme visitées'
            ]);
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