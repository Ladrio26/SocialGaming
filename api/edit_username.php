<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../includes/RoleManager.php';

$auth = new Auth($pdo);
$roleManager = new RoleManager($pdo);

// Vérifier si l'utilisateur est connecté
$user = $auth->isLoggedIn();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données
$targetUserId = (int)($_POST['user_id'] ?? 0);
$newUsername = trim($_POST['username'] ?? '');

// Validation des données
if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

if (empty($newUsername)) {
    echo json_encode(['success' => false, 'message' => 'Nouveau pseudo requis']);
    exit;
}

// Validation du format du pseudo
if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $newUsername)) {
    echo json_encode(['success' => false, 'message' => 'Format de pseudo invalide (3-20 caractères, lettres, chiffres, tirets et underscores uniquement)']);
    exit;
}

// Vérifier que l'utilisateur cible existe
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    exit;
}

// Vérifier les permissions : l'utilisateur peut modifier son propre pseudo OU il a les permissions de modération
if ($targetUserId !== $user['id'] && !$roleManager->hasPermission($user['id'], 'edit_usernames')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    exit;
}

// Vérifier que le nouveau pseudo n'est pas déjà utilisé
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$newUsername, $targetUserId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Ce pseudo est déjà utilisé']);
    exit;
}

try {
    // Mettre à jour le pseudo
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$newUsername, $targetUserId]);
    
    // Log de l'action si c'est un modérateur
    if ($targetUserId !== $user['id']) {
        $moderatorInfo = $roleManager->getUserRole($user['id']);
        error_log("MODERATION ACTION: User {$moderatorInfo['username']} (ID: {$user['id']}) changed username of user {$targetUser['username']} (ID: {$targetUserId}) to {$newUsername}");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pseudo modifié avec succès',
        'new_username' => $newUsername
    ]);
    
} catch (Exception $e) {
    error_log("Error editing username: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du pseudo']);
}
?> 