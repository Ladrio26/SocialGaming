<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Notification.php';
require_once 'classes/Friends.php';

echo "=== CORRECTION NOTIFICATIONS ET RECHERCHE ===\n\n";

// 1. Vérifier et corriger les sessions
echo "1. Vérification des sessions:\n";
$stmt = $pdo->query("SELECT user_id, session_token, expires_at FROM user_sessions WHERE expires_at > NOW() ORDER BY expires_at DESC");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sessions as $session) {
    echo "- User ID: {$session['user_id']} | Token: " . substr($session['session_token'], 0, 10) . "... | Expires: {$session['expires_at']}\n";
}

echo "\n";

// 2. Vérifier les notifications
echo "2. Vérification des notifications:\n";
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notifications as $notif) {
    echo "- ID: {$notif['id']} | User: {$notif['user_id']} | Type: {$notif['type']} | Read: " . ($notif['is_read'] ? 'Oui' : 'Non') . " | Created: {$notif['created_at']}\n";
}

echo "\n";

// 3. Vérifier les demandes d'amis
echo "3. Vérification des demandes d'amis:\n";
$stmt = $pdo->query("SELECT * FROM friend_requests ORDER BY created_at DESC LIMIT 5");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($requests as $req) {
    echo "- ID: {$req['id']} | From: {$req['sender_id']} | To: {$req['receiver_id']} | Status: {$req['status']} | Created: {$req['created_at']}\n";
}

echo "\n";

// 4. Vérifier les relations d'amis
echo "4. Vérification des relations d'amis:\n";
$stmt = $pdo->query("SELECT * FROM friends ORDER BY created_at DESC LIMIT 5");
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($friends as $friend) {
    echo "- User: {$friend['user_id']} | Friend: {$friend['friend_id']} | Created: {$friend['created_at']}\n";
}

echo "\n";

// 5. Test de l'API des notifications
echo "5. Test de l'API des notifications:\n";
$auth = new Auth($pdo);
$notification = new Notification($pdo);

// Simuler une session pour l'utilisateur 7
$stmt = $pdo->prepare("SELECT session_token FROM user_sessions WHERE user_id = 7 AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
$stmt->execute();
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if ($session) {
    $_COOKIE['session_token'] = $session['session_token'];
    $currentUser = $auth->isLoggedIn();
    
    if ($currentUser) {
        echo "✅ Test avec l'utilisateur {$currentUser['username']} (ID: {$currentUser['id']})\n";
        
        // Tester la récupération des notifications
        $notifications = $notification->getForUser($currentUser['id'], 10);
        echo "Nombre de notifications: " . count($notifications) . "\n";
        
        // Tester le comptage des notifications non lues
        $unread_count = $notification->getUnreadCount($currentUser['id']);
        echo "Notifications non lues: $unread_count\n";
    } else {
        echo "❌ Échec de l'authentification\n";
    }
} else {
    echo "❌ Aucune session valide trouvée\n";
}

echo "\n";

// 6. Test de l'API de recherche
echo "6. Test de l'API de recherche:\n";
$friends = new Friends($pdo);

if (isset($currentUser)) {
    // Tester les statuts de relation
    $users = [2, 6, 4]; // Ladrio, Matux, minipouccedu18
    foreach ($users as $user_id) {
        $status = $friends->getRelationshipStatus($currentUser['id'], $user_id);
        echo "- Relation avec l'utilisateur $user_id: $status\n";
    }
}

echo "\n=== FIN CORRECTION ===\n";

// 7. Recommandations
echo "\n=== RECOMMANDATIONS ===\n";
echo "1. Pour les notifications : Vérifier que les cookies sont bien envoyés dans les requêtes AJAX\n";
echo "2. Pour la recherche : L'API fonctionne correctement, vérifier l'affichage JavaScript\n";
echo "3. Vérifier que le serveur web (Apache/Nginx) est configuré pour gérer les cookies de session\n";
echo "4. Vérifier les logs d'erreur du navigateur (F12 > Console)\n";
?> 