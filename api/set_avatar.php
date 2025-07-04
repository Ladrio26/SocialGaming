<?php
require_once '../config.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$currentUser = $auth->isLoggedIn();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$avatarId = $input['avatar_id'] ?? null;

if (!$avatarId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID d\'avatar manquant']);
    exit;
}

try {
    $newAvatarUrl = null;
    
    switch ($avatarId) {
        case 'discord':
            if ($currentUser['auth_provider'] === 'discord' && !empty($currentUser['discord_avatar'])) {
                $newAvatarUrl = $currentUser['discord_avatar'];
            }
            break;
            
        case 'steam':
            $steamStmt = $pdo->prepare("SELECT steam_avatar FROM steam_accounts WHERE user_id = ?");
            $steamStmt->execute([$currentUser['id']]);
            $steamAccount = $steamStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($steamAccount && !empty($steamAccount['steam_avatar'])) {
                $newAvatarUrl = $steamAccount['steam_avatar'];
            }
            break;
            
        case 'current':
            if (!empty($currentUser['avatar_url'])) {
                $newAvatarUrl = $currentUser['avatar_url'];
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Avatar invalide']);
            exit;
    }
    
    if (!$newAvatarUrl) {
        http_response_code(400);
        echo json_encode(['error' => 'Avatar non disponible']);
        exit;
    }
    
    // Mettre à jour l'avatar dans la base de données
    $updateStmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
    $updateStmt->execute([$newAvatarUrl, $currentUser['id']]);
    
    echo json_encode([
        'success' => true,
        'avatar_url' => $newAvatarUrl,
        'message' => 'Avatar mis à jour avec succès'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?> 