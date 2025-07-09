<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../classes/Friends.php';
require_once '../classes/UserDisplay.php';

$auth = new Auth($pdo);
$friends = new Friends($pdo);

// Vérifier si l'utilisateur est connecté
$user = $auth->isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer une recherche']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'search_users':
            $query = trim($input['query'] ?? '');
            
            // Si pas de requête, afficher des utilisateurs récents
            if (empty($query)) {
                try {
                                    $stmt = $pdo->prepare("
                    SELECT id, username, email, auth_provider, avatar_url, created_at, display_format 
                    FROM users 
                    WHERE is_active = 1
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                    $stmt->execute();
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Masquer les emails pour la sécurité (sauf pour l'utilisateur connecté)
                    foreach ($users as &$user_data) {
                        if ($user_data['id'] !== $user['id']) {
                            $user_data['email'] = substr($user_data['email'], 0, 3) . '***' . substr($user_data['email'], strpos($user_data['email'], '@'));
                        }
                        
                        // Ajouter le statut de relation d'amis
                        $user_data['relationship_status'] = $friends->getRelationshipStatus($user['id'], $user_data['id']);
                        
                        // Déterminer le type de correspondance pour la recherche
                        $user_data['match_type'] = 'other';
                        if (stripos($user_data['username'], $query) === 0) {
                            $user_data['match_type'] = 'username_start';
                        } elseif (stripos($user_data['username'], $query) !== false) {
                            $user_data['match_type'] = 'username_contains';
                        }
                    }
                    
                    echo json_encode([
                        'success' => true, 
                        'users' => $users,
                        'count' => count($users),
                        'type' => 'recent'
                    ]);
                    exit;
                    
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche : ' . $e->getMessage()]);
                    exit;
                }
            }
            
            if (strlen($query) < 1) {
                echo json_encode(['success' => false, 'message' => 'La recherche doit contenir au moins 1 caractère']);
                exit;
            }
            
            try {
                // Recherche d'utilisateurs par pseudo (LIKE pour une recherche partielle)
                $stmt = $pdo->prepare("
                    SELECT id, username, email, auth_provider, avatar_url, created_at, display_format 
                    FROM users 
                    WHERE username LIKE ? 
                    AND is_active = 1
                    ORDER BY 
                        CASE 
                            WHEN username LIKE ? THEN 1
                            ELSE 2
                        END,
                        username ASC
                    LIMIT 20
                ");
                $search_term = '%' . $query . '%';
                $exact_start = $query . '%';
                $stmt->execute([$search_term, $exact_start]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Masquer les emails pour la sécurité (sauf pour l'utilisateur connecté)
                foreach ($users as &$user_data) {
                    if ($user_data['id'] !== $user['id']) {
                        $user_data['email'] = substr($user_data['email'], 0, 3) . '***' . substr($user_data['email'], strpos($user_data['email'], '@'));
                    }
                    
                    // Ajouter le statut de relation d'amis
                    $user_data['relationship_status'] = $friends->getRelationshipStatus($user['id'], $user_data['id']);
                }
                
                echo json_encode([
                    'success' => true, 
                    'users' => $users,
                    'count' => count($users),
                    'type' => 'search'
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche : ' . $e->getMessage()]);
            }
            break;
            
        case 'get_user_details':
            $user_id = $input['user_id'] ?? 0;
            
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("
                    SELECT id, username, email, auth_provider, avatar_url, created_at, display_format 
                    FROM users 
                    WHERE id = ? AND is_active = 1
                ");
                $stmt->execute([$user_id]);
                $user_details = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_details) {
                    // Masquer l'email si ce n'est pas l'utilisateur connecté
                    if ($user_details['id'] !== $user['id']) {
                        $user_details['email'] = substr($user_details['email'], 0, 3) . '***' . substr($user_details['email'], strpos($user_details['email'], '@'));
                    }
                    
                    echo json_encode(['success' => true, 'user' => $user_details]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                }
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails : ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 