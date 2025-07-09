<?php
class UserDisplay {
    
    /**
     * Formate le nom d'affichage d'un utilisateur (pseudo uniquement)
     * 
     * @param array $user Données de l'utilisateur (doit contenir username)
     * @return string Nom formaté pour l'affichage
     */
    public static function formatDisplayName($user) {
        $username = $user['username'] ?? '';
        
        // Échapper les caractères HTML pour la sécurité
        $username = htmlspecialchars($username);
        
        return $username ?: 'Utilisateur';
    }
    
    /**
     * Formate le nom d'affichage pour JavaScript (pseudo uniquement)
     * 
     * @param array $user Données de l'utilisateur
     * @return string Nom formaté pour JavaScript
     */
    public static function formatDisplayNameForJS($user) {
        $username = $user['username'] ?? '';
        return $username ?: 'Utilisateur';
    }
}
?> 