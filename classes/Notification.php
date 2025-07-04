<?php
class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Créer une nouvelle notification
    public function create($user_id, $type, $title, $message, $data = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, data) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $jsonData = $data ? json_encode($data) : null;
            $stmt->execute([$user_id, $type, $title, $message, $jsonData]);
            
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Notification::create - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création de la notification'];
        }
    }
    
    // Récupérer les notifications d'un utilisateur
    public function getForUser($user_id, $limit = 20, $offset = 0, $unread_only = false) {
        try {
            $whereClause = $unread_only ? "WHERE user_id = ? AND is_read = FALSE" : "WHERE user_id = ?";
            $stmt = $this->pdo->prepare("
                SELECT id, type, title, message, data, is_read, created_at 
                FROM notifications 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
            ");
            
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Décoder les données JSON
            foreach ($notifications as &$notification) {
                if ($notification['data']) {
                    $notification['data'] = json_decode($notification['data'], true);
                }
            }
            
            return $notifications;
        } catch (PDOException $e) {
            error_log("Notification::getForUser - Erreur: " . $e->getMessage());
            return [];
        }
    }
    
    // Compter les notifications non lues
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Notification::getUnreadCount - Erreur: " . $e->getMessage());
            return 0;
        }
    }
    
    // Marquer une notification comme lue
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Notification::markAsRead - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
        }
    }
    
    // Marquer toutes les notifications comme lues
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$user_id]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Notification::markAllAsRead - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
        }
    }
    
    // Supprimer une notification
    public function delete($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Notification::delete - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
    
    // Supprimer toutes les notifications d'un utilisateur
    public function deleteAll($user_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Notification::deleteAll - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression de toutes les notifications'];
        }
    }
    
    // Méthodes pour créer des notifications spécifiques
    
    // Notification de nouvelle demande d'ami
    public function createFriendRequest($receiver_id, $sender_name) {
        return $this->create(
            $receiver_id,
            'friend_request',
            'Nouvelle demande d\'ami',
            $sender_name . ' vous a envoyé une demande d\'ami',
            ['sender_name' => $sender_name]
        );
    }
    
    // Notification d'ami accepté
    public function createFriendAccepted($user_id, $friend_name) {
        return $this->create(
            $user_id,
            'friend_accepted',
            'Demande d\'ami acceptée',
            $friend_name . ' a accepté votre demande d\'ami',
            ['friend_name' => $friend_name]
        );
    }
    
    // Notification de visite de profil
    public function createProfileVisit($visited_user_id, $visitor_name) {
        return $this->create(
            $visited_user_id,
            'profile_visit',
            'Nouvelle visite de profil',
            $visitor_name . ' a visité votre profil',
            ['visitor_name' => $visitor_name]
        );
    }
    
    // Notification de nouveau jeu en commun
    public function createCommonGame($user_id, $friend_name, $game_name) {
        return $this->create(
            $user_id,
            'common_game',
            'Nouveau jeu en commun',
            $friend_name . ' joue aussi à ' . $game_name,
            ['friend_name' => $friend_name, 'game_name' => $game_name]
        );
    }
    
    // Nettoyer les anciennes notifications (plus de 30 jours)
    public function cleanupOldNotifications() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Notification::cleanupOldNotifications - Erreur: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du nettoyage'];
        }
    }
}
?> 