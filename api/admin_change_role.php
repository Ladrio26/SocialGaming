<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../includes/RoleManager.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);

// Vérification de l'authentification
$user = $auth->isLoggedIn();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$roleManager = new RoleManager($pdo);

// Vérification des permissions admin
if (!$roleManager->hasPermission($user['id'], 'access_admin_panel')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données
$userId = (int)($_POST['user_id'] ?? 0);
$roleId = (int)($_POST['role_id'] ?? 0);

// Validation des données
if ($userId <= 0 || $roleId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

// Vérification que l'utilisateur existe
$userRole = $roleManager->getUserRole($userId);
if (!$userRole) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
    exit;
}

// Vérification que le rôle existe
$roles = $roleManager->getAllRoles();
$roleExists = false;
foreach ($roles as $role) {
    if ($role['id'] == $roleId) {
        $roleExists = true;
        break;
    }
}

if (!$roleExists) {
    echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
    exit;
}

// Empêcher un admin de se bannir lui-même
if ($userId === $user['id'] && $roleId === 4) {
    echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas vous bannir vous-même']);
    exit;
}

try {
    // Changement du rôle
    $success = $roleManager->changeUserRole($userId, $roleId);
    
    if ($success) {
        // Log de l'action
        $adminInfo = $roleManager->getUserRole($user['id']);
        $newRoleInfo = null;
        foreach ($roles as $role) {
            if ($role['id'] == $roleId) {
                $newRoleInfo = $role;
                break;
            }
        }
        
        error_log("ADMIN ACTION: User {$adminInfo['username']} (ID: {$user['id']}) changed role of user {$userRole['username']} (ID: {$userId}) from {$userRole['role_name']} to {$newRoleInfo['name']}");
        
        echo json_encode([
            'success' => true, 
            'message' => "Rôle de {$userRole['username']} changé vers {$newRoleInfo['name']}"
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors du changement de rôle']);
    }
} catch (Exception $e) {
    error_log("Error changing user role: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur interne du serveur']);
}
?> 