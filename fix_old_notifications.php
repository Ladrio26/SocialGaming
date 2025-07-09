<?php
require_once 'config.php';
require_once 'classes/Notification.php';

echo "🔧 Correction des Anciennes Notifications\n\n";

try {
    // 1. Identifier les notifications problématiques
    echo "1. Recherche des notifications sans sender_id...\n";
    
    $stmt = $pdo->query("
        SELECT n.*, u.username as sender_name 
        FROM notifications n 
        LEFT JOIN users u ON JSON_EXTRACT(n.data, '$.sender_name') = u.username 
        WHERE n.type = 'friend_request' 
        AND (n.data IS NULL OR JSON_EXTRACT(n.data, '$.sender_id') IS NULL)
        ORDER BY n.created_at DESC
    ");
    
    $oldNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($oldNotifications)) {
        echo "✅ Aucune notification à corriger trouvée\n";
    } else {
        echo "📝 " . count($oldNotifications) . " notification(s) à corriger trouvée(s)\n\n";
        
        foreach ($oldNotifications as $notif) {
            echo "   Notification ID: {$notif['id']}\n";
            echo "   Message: {$notif['message']}\n";
            echo "   Données actuelles: " . ($notif['data'] ?: 'NULL') . "\n";
            
            // Extraire le nom de l'expéditeur du message
            if (preg_match('/(.+) vous a envoyé une demande d\'ami/', $notif['message'], $matches)) {
                $senderName = trim($matches[1]);
                echo "   Nom extrait: {$senderName}\n";
                
                // Trouver l'utilisateur par son nom
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
                $stmt->execute([$senderName]);
                $sender = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($sender) {
                    echo "   ✅ Utilisateur trouvé: ID {$sender['id']}\n";
                    
                    // Mettre à jour les données de la notification
                    $newData = [
                        'sender_id' => $sender['id'],
                        'sender_name' => $sender['username']
                    ];
                    
                    $stmt = $pdo->prepare("UPDATE notifications SET data = ? WHERE id = ?");
                    $result = $stmt->execute([json_encode($newData), $notif['id']]);
                    
                    if ($result) {
                        echo "   ✅ Notification corrigée avec succès\n";
                    } else {
                        echo "   ❌ Erreur lors de la correction\n";
                    }
                } else {
                    echo "   ❌ Utilisateur non trouvé pour: {$senderName}\n";
                }
            } else {
                echo "   ❌ Impossible d'extraire le nom de l'expéditeur\n";
            }
            echo "\n";
        }
    }
    
    // 2. Vérifier les demandes d'amis en attente
    echo "2. Vérification des demandes d'amis en attente...\n";
    
    $stmt = $pdo->query("
        SELECT fr.*, u.username as sender_name, u.id as sender_id
        FROM friend_requests fr
        JOIN users u ON fr.sender_id = u.id
        WHERE fr.status = 'pending'
        ORDER BY fr.created_at DESC
    ");
    
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingRequests)) {
        echo "✅ Aucune demande d'ami en attente\n";
    } else {
        echo "📝 " . count($pendingRequests) . " demande(s) d'ami en attente\n";
        
        foreach ($pendingRequests as $request) {
            echo "   Demande ID: {$request['id']}\n";
            echo "   De: {$request['sender_name']} (ID: {$request['sender_id']})\n";
            echo "   Vers: ID {$request['receiver_id']}\n";
            echo "   Date: {$request['created_at']}\n\n";
        }
    }
    
    // 3. Créer des notifications manquantes pour les demandes en attente
    echo "3. Création de notifications manquantes...\n";
    
    $notification = new Notification($pdo);
    $createdCount = 0;
    
    foreach ($pendingRequests as $request) {
        // Vérifier si une notification existe déjà pour cette demande
        $stmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND type = 'friend_request' 
            AND JSON_EXTRACT(data, '$.sender_id') = ?
            AND created_at >= ?
        ");
        $stmt->execute([$request['receiver_id'], $request['sender_id'], $request['created_at']]);
        
        if ($stmt->rowCount() == 0) {
            // Créer une nouvelle notification
            $result = $notification->createFriendRequest(
                $request['receiver_id'],
                $request['sender_id'],
                $request['sender_name']
            );
            
            if ($result['success']) {
                echo "   ✅ Notification créée pour la demande de {$request['sender_name']}\n";
                $createdCount++;
            } else {
                echo "   ❌ Erreur lors de la création de la notification\n";
            }
        } else {
            echo "   ℹ️ Notification déjà existante pour {$request['sender_name']}\n";
        }
    }
    
    echo "\n📊 Résumé:\n";
    echo "   - Notifications corrigées: " . count($oldNotifications) . "\n";
    echo "   - Demandes en attente: " . count($pendingRequests) . "\n";
    echo "   - Nouvelles notifications créées: {$createdCount}\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Correction terminée !\n";
?> 