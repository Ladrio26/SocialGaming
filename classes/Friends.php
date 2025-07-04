<?php
class Friends {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Envoyer une demande d'ami
    public function sendFriendRequest($sender_id, $receiver_id) {
        try {
            // Vérifier que l'utilisateur n'essaie pas de s'ajouter lui-même
            if ($sender_id === $receiver_id) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas vous ajouter vous-même en ami'];
            }
            
            // Vérifier si une demande existe déjà
            $stmt = $this->pdo->prepare("SELECT id, status FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                if ($existing['status'] === 'pending') {
                    return ['success' => false, 'message' => 'Une demande d\'ami est déjà en attente'];
                } elseif ($existing['status'] === 'accepted') {
                    return ['success' => false, 'message' => 'Vous êtes déjà amis avec cet utilisateur'];
                }
            }
            
            // Envoyer la demande
            $stmt = $this->pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
            $stmt->execute([$sender_id, $receiver_id]);
            
            return ['success' => true, 'message' => 'Demande d\'ami envoyée avec succès'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'envoi de la demande : ' . $e->getMessage()];
        }
    }
    
    // Accepter une demande d'ami
    public function acceptFriendRequest($request_id, $user_id) {
        try {
            // Vérifier que la demande existe et appartient à l'utilisateur
            $stmt = $this->pdo->prepare("SELECT sender_id, receiver_id FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
            $stmt->execute([$request_id, $user_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return ['success' => false, 'message' => 'Demande d\'ami non trouvée'];
            }
            
            // Accepter la demande
            $stmt = $this->pdo->prepare("UPDATE friend_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$request_id]);
            
            // Ajouter dans la table friends (relation bidirectionnelle)
            $stmt = $this->pdo->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
            $stmt->execute([$request['sender_id'], $request['receiver_id'], $request['receiver_id'], $request['sender_id']]);
            
            return ['success' => true, 'message' => 'Demande d\'ami acceptée', 'sender_id' => $request['sender_id']];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'acceptation : ' . $e->getMessage()];
        }
    }
    
    // Refuser une demande d'ami
    public function rejectFriendRequest($request_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE friend_requests SET status = 'rejected', updated_at = NOW() WHERE id = ? AND receiver_id = ? AND status = 'pending'");
            $stmt->execute([$request_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Demande d\'ami refusée'];
            } else {
                return ['success' => false, 'message' => 'Demande d\'ami non trouvée'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors du refus : ' . $e->getMessage()];
        }
    }
    
    // Supprimer un ami
    public function removeFriend($user_id, $friend_id) {
        try {
            // Supprimer de la table friends
            $stmt = $this->pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
            
            // Supprimer les demandes associées
            $stmt = $this->pdo->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
            
            return ['success' => true, 'message' => 'Ami supprimé'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()];
        }
    }
    
    // Obtenir la liste des amis
    public function getFriends($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.first_name, u.last_name, u.avatar_url, u.auth_provider, u.display_format, f.created_at as friendship_date
                FROM friends f
                JOIN users u ON f.friend_id = u.id
                WHERE f.user_id = ? AND u.is_active = 1
                ORDER BY u.username ASC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Obtenir les demandes d'amis reçues
    public function getReceivedFriendRequests($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT fr.id, fr.created_at, u.id as sender_id, u.username, u.first_name, u.last_name, u.avatar_url, u.auth_provider, u.display_format
                FROM friend_requests fr
                JOIN users u ON fr.sender_id = u.id
                WHERE fr.receiver_id = ? AND fr.status = 'pending' AND u.is_active = 1
                ORDER BY fr.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Obtenir les demandes d'amis envoyées
    public function getSentFriendRequests($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT fr.id, fr.status, fr.created_at, u.id as receiver_id, u.username, u.first_name, u.last_name, u.avatar_url, u.auth_provider, u.display_format
                FROM friend_requests fr
                JOIN users u ON fr.receiver_id = u.id
                WHERE fr.sender_id = ? AND fr.status = 'pending' AND u.is_active = 1
                ORDER BY fr.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Vérifier si deux utilisateurs sont amis
    public function areFriends($user_id, $friend_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM friends WHERE user_id = ? AND friend_id = ?");
            $stmt->execute([$user_id, $friend_id]);
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Vérifier s'il y a une demande d'ami en attente
    public function hasPendingRequest($sender_id, $receiver_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
            $stmt->execute([$sender_id, $receiver_id]);
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Obtenir le statut de relation entre deux utilisateurs
    public function getRelationshipStatus($user_id, $other_id) {
        try {
            // Vérifier s'ils sont amis
            if ($this->areFriends($user_id, $other_id)) {
                return 'friends';
            }
            
            // Vérifier s'il y a une demande envoyée
            if ($this->hasPendingRequest($user_id, $other_id)) {
                return 'request_sent';
            }
            
            // Vérifier s'il y a une demande reçue
            if ($this->hasPendingRequest($other_id, $user_id)) {
                return 'request_received';
            }
            
            return 'none';
            
        } catch (PDOException $e) {
            return 'none';
        }
    }
}
?> 