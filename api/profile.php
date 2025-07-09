<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);

// Vérifier si l'utilisateur est connecté
$user = $auth->isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'get_profile':
            // Récupérer les informations du profil
            try {
                $stmt = $pdo->prepare("
                    SELECT id, username, email, auth_provider, avatar_url, created_at, display_format 
                    FROM users 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($profile) {
                    echo json_encode(['success' => true, 'profile' => $profile]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Profil non trouvé']);
                }
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du profil : ' . $e->getMessage()]);
            }
            break;
            
        case 'update_profile':
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $profile_visibility = $input['profile_visibility'] ?? 'private';
            
            // Validation du pseudo obligatoire
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Le pseudo est obligatoire']);
                exit;
            }
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'L\'email est obligatoire']);
                exit;
            }
            
            // Validation de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
                exit;
            }
            
            // Validation du pseudo si fourni
            if ($has_username) {
                if (strlen($username) < 3) {
                    echo json_encode(['success' => false, 'message' => 'Le pseudo doit contenir au moins 3 caractères']);
                    exit;
                }
                
                if (strlen($username) > 20) {
                    echo json_encode(['success' => false, 'message' => 'Le pseudo ne peut pas dépasser 20 caractères']);
                    exit;
                }
                
                // Vérifier que le pseudo ne contient que des caractères autorisés
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                    echo json_encode(['success' => false, 'message' => 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores']);
                    exit;
                }
            }
            

            

            
            try {
                // Vérifier si l'email ou le pseudo sont déjà utilisés par un autre utilisateur
                $stmt = $pdo->prepare("
                    SELECT id FROM users 
                    WHERE (email = ? OR username = ?) AND id != ?
                ");
                $stmt->execute([$email, $username, $user['id']]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cet email ou pseudo est déjà utilisé par un autre utilisateur']);
                    exit;
                }
                
                // Mettre à jour le profil
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, profile_visibility = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $profile_visibility, $user['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
            }
            break;
            
        case 'remove_avatar':
            try {
                // Supprimer l'ancien avatar s'il existe
                if ($user['avatar_url'] && !strpos($user['avatar_url'], 'discordapp.com')) {
                    $old_avatar_path = '/home/ladrio/Websites/src/ladrio2.goodloss.fr/' . ltrim($user['avatar_url'], '/');
                    if (file_exists($old_avatar_path)) {
                        unlink($old_avatar_path);
                    }
                }
                
                // Mettre à jour la base de données
                $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Avatar supprimé avec succès']);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
            }
            break;
            
        case 'change_password':
            $current_password = $input['current_password'] ?? '';
            $new_password = $input['new_password'] ?? '';
            $confirm_password = $input['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas']);
                exit;
            }
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères']);
                exit;
            }
            
            try {
                // Vérifier l'ancien mot de passe
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($current_password, $user_data['password_hash'])) {
                    echo json_encode(['success' => false, 'message' => 'Mot de passe actuel incorrect']);
                    exit;
                }
                
                // Hasher et mettre à jour le nouveau mot de passe
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_password_hash, $user['id']]);
                
                echo json_encode(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du changement de mot de passe : ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 