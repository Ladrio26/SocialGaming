<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../includes/RoleManager.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

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
    // Récupérer les préférences de l'utilisateur
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.name,
                c.description,
                COALESCE(ucp.is_visible, TRUE) as is_visible
            FROM categories c
            LEFT JOIN user_category_preferences ucp ON c.id = ucp.category_id AND ucp.user_id = ?
            ORDER BY c.name ASC
        ");
        $stmt->execute([$user['id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Erreur lors de la récupération des préférences: ' . $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mettre à jour les préférences
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['preferences']) || !is_array($input['preferences'])) {
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Supprimer toutes les préférences existantes de l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM user_category_preferences WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // Insérer les nouvelles préférences
        $stmt = $pdo->prepare("
            INSERT INTO user_category_preferences (user_id, category_id, is_visible) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($input['preferences'] as $pref) {
            if (isset($pref['category_id']) && isset($pref['is_visible'])) {
                // Conversion robuste en booléen
                $is_visible = filter_var($pref['is_visible'], FILTER_VALIDATE_BOOLEAN);
                $stmt->execute([
                    $user['id'],
                    (int)$pref['category_id'],
                    $is_visible ? 1 : 0
                ]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Préférences mises à jour avec succès'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
?> 