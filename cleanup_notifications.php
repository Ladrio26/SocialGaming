<?php
require_once 'config.php';

echo "🧹 Nettoyage des Notifications et Demandes d'Amis\n\n";

try {
    // 1. Compter les éléments avant suppression
    echo "1. Comptage des éléments existants...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $notificationsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM friend_requests WHERE status = 'pending'");
    $pendingRequestsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM friend_requests");
    $totalRequestsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   📊 Notifications: {$notificationsCount}\n";
    echo "   📊 Demandes d'amis en attente: {$pendingRequestsCount}\n";
    echo "   📊 Total demandes d'amis: {$totalRequestsCount}\n\n";
    
    // 2. Supprimer toutes les notifications
    echo "2. Suppression de toutes les notifications...\n";
    
    $stmt = $pdo->prepare("DELETE FROM notifications");
    $result = $stmt->execute();
    
    if ($result) {
        $deletedNotifications = $stmt->rowCount();
        echo "   ✅ {$deletedNotifications} notification(s) supprimée(s)\n";
    } else {
        echo "   ❌ Erreur lors de la suppression des notifications\n";
    }
    
    // 3. Supprimer toutes les demandes d'amis en attente
    echo "3. Suppression des demandes d'amis en attente...\n";
    
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE status = 'pending'");
    $result = $stmt->execute();
    
    if ($result) {
        $deletedRequests = $stmt->rowCount();
        echo "   ✅ {$deletedRequests} demande(s) d'ami en attente supprimée(s)\n";
    } else {
        echo "   ❌ Erreur lors de la suppression des demandes d'amis\n";
    }
    
    // 4. Vérifier le résultat
    echo "4. Vérification du nettoyage...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $remainingNotifications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM friend_requests WHERE status = 'pending'");
    $remainingRequests = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   📊 Notifications restantes: {$remainingNotifications}\n";
    echo "   📊 Demandes d'amis en attente restantes: {$remainingRequests}\n";
    
    // 5. Vérifier les relations d'amis existantes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM friends");
    $friendsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "   📊 Relations d'amis existantes: {$friendsCount}\n";
    
    // 6. Résumé
    echo "\n📊 Résumé du nettoyage:\n";
    echo "   ✅ Notifications supprimées: {$deletedNotifications}\n";
    echo "   ✅ Demandes d'amis supprimées: {$deletedRequests}\n";
    echo "   ✅ Relations d'amis préservées: {$friendsCount}\n";
    
    if ($remainingNotifications == 0 && $remainingRequests == 0) {
        echo "\n🎉 Nettoyage terminé avec succès !\n";
        echo "   - Toutes les notifications ont été supprimées\n";
        echo "   - Toutes les demandes d'amis en attente ont été supprimées\n";
        echo "   - Les relations d'amis existantes ont été préservées\n";
    } else {
        echo "\n⚠️ Nettoyage partiel - certains éléments restent\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du nettoyage: " . $e->getMessage() . "\n";
}

echo "\n✅ Nettoyage terminé !\n";
?> 