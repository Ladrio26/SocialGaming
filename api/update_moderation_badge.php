<?php
require_once '../config.php';
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

if (!$roleManager->hasPermission($user['id'], 'moderate_categories')) {
    echo json_encode(['success' => false, 'error' => 'Pas de permission']);
    exit;
}

try {
    // Compter les propositions en attente
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM category_proposals WHERE status = 'pending'");
    $stmt->execute();
    $pending_count = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'pending_count' => $pending_count,
        'has_pending' => $pending_count > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 