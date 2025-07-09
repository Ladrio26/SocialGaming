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

// Validation des données
if ($targetUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

// Vérifier que l'utilisateur cible existe
$stmt = $pdo->prepare("SELECT id, username, avatar_url FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    exit;
}

// Vérifier les permissions : l'utilisateur peut supprimer son propre avatar OU il a les permissions de modération
if ($targetUserId !== $user['id'] && !$roleManager->hasPermission($user['id'], 'delete_avatars')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissions insuffisantes']);
    exit;
}

try {
    // Supprimer le fichier avatar s'il existe et n'est pas un avatar Discord
    if ($targetUser['avatar_url'] && !strpos($targetUser['avatar_url'], 'discordapp.com')) {
        $avatar_path = '/home/ladrio/Websites/src/ladrio2.goodloss.fr/' . ltrim($targetUser['avatar_url'], '/');
        if (file_exists($avatar_path)) {
            unlink($avatar_path);
        }
    }
    
    // Mettre à jour la base de données
    $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
    $stmt->execute([$targetUserId]);
    
    // Log de l'action si c'est un modérateur
    if ($targetUserId !== $user['id']) {
        $moderatorInfo = $roleManager->getUserRole($user['id']);
        error_log("MODERATION ACTION: User {$moderatorInfo['username']} (ID: {$user['id']}) deleted avatar of user {$targetUser['username']} (ID: {$targetUserId})");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Avatar supprimé avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting avatar: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'avatar']);
}
?> 