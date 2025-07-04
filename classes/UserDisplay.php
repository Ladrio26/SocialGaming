<?php
class UserDisplay {
    
    /**
     * Formate le nom d'affichage d'un utilisateur selon son format préféré
     * 
     * @param array $user Données de l'utilisateur (doit contenir first_name, last_name, username, display_format)
     * @return string Nom formaté pour l'affichage
     */
    public static function formatDisplayName($user) {
        $first_name = $user['first_name'] ?? '';
        $last_name = $user['last_name'] ?? '';
        $username = $user['username'] ?? '';
        $display_format = $user['display_format'] ?? 'full_with_pseudo';
        
        // Échapper les caractères HTML pour la sécurité
        $first_name = htmlspecialchars($first_name);
        $last_name = htmlspecialchars($last_name);
        $username = htmlspecialchars($username);
        
        switch ($display_format) {
            case 'full_name':
                // Prénom & Nom
                if ($first_name && $last_name) {
                    return trim("$first_name $last_name");
                } elseif ($first_name) {
                    return $first_name;
                } elseif ($last_name) {
                    return $last_name;
                } else {
                    return $username ?: 'Utilisateur';
                }
                
            case 'first_name_only':
                // Juste Prénom
                if ($first_name) {
                    return $first_name;
                } elseif ($username) {
                    return $username;
                } else {
                    return 'Utilisateur';
                }
                
            case 'last_name_only':
                // Juste Nom
                if ($last_name) {
                    return $last_name;
                } elseif ($username) {
                    return $username;
                } else {
                    return 'Utilisateur';
                }
                
            case 'username_only':
                // Juste Pseudo
                if ($username) {
                    return $username;
                } elseif ($first_name) {
                    return $first_name;
                } else {
                    return 'Utilisateur';
                }
                
            case 'full_with_pseudo':
            default:
                // Prénom 'Pseudo' Nom
                if ($first_name && $last_name) {
                    $pseudo = $username ? " '$username'" : '';
                    return trim("$first_name$pseudo $last_name");
                } elseif ($first_name) {
                    return $first_name;
                } elseif ($last_name) {
                    return $last_name;
                } elseif ($username) {
                    return $username;
                } else {
                    return 'Utilisateur';
                }
        }
    }
    
    /**
     * Formate le nom d'affichage pour JavaScript (sans échappement HTML)
     * 
     * @param array $user Données de l'utilisateur
     * @return string Nom formaté pour JavaScript
     */
    public static function formatDisplayNameForJS($user) {
        $first_name = $user['first_name'] ?? '';
        $last_name = $user['last_name'] ?? '';
        $username = $user['username'] ?? '';
        $display_format = $user['display_format'] ?? 'full_with_pseudo';
        
        switch ($display_format) {
            case 'full_name':
                // Prénom & Nom
                if ($first_name && $last_name) {
                    return trim("$first_name $last_name");
                } elseif ($first_name) {
                    return $first_name;
                } elseif ($last_name) {
                    return $last_name;
                } else {
                    return $username ?: 'Utilisateur';
                }
                
            case 'first_name_only':
                // Juste Prénom
                if ($first_name) {
                    return $first_name;
                } elseif ($username) {
                    return $username;
                } else {
                    return 'Utilisateur';
                }
                
            case 'last_name_only':
                // Juste Nom
                if ($last_name) {
                    return $last_name;
                } elseif ($username) {
                    return $username;
                } else {
                    return 'Utilisateur';
                }
                
            case 'username_only':
                // Juste Pseudo
                if ($username) {
                    return $username;
                } elseif ($first_name) {
                    return $first_name;
                } else {
                    return 'Utilisateur';
                }
                
            case 'full_with_pseudo':
            default:
                // Prénom 'Pseudo' Nom
                if ($first_name && $last_name) {
                    $pseudo = $username ? " '$username'" : '';
                    return trim("$first_name$pseudo $last_name");
                } elseif ($first_name) {
                    return $first_name;
                } elseif ($last_name) {
                    return $last_name;
                } elseif ($username) {
                    return $username;
                } else {
                    return 'Utilisateur';
                }
        }
    }
}
?> 