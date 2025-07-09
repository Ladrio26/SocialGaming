<?php
class RoleManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Récupère les informations de rôle d'un utilisateur
     */
    public function getUserRole($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, r.id as role_id, r.name as role_name, r.permissions
            FROM users u
            JOIN user_roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si un utilisateur a une permission spécifique
     */
    public function hasPermission($userId, $permission) {
        $userRole = $this->getUserRole($userId);
        if (!$userRole) {
            return false;
        }
        
        $permissions = json_decode($userRole['permissions'], true);
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    /**
     * Vérifie si un utilisateur est admin
     */
    public function isAdmin($userId) {
        $userRole = $this->getUserRole($userId);
        return $userRole && $userRole['role_name'] === 'Admin';
    }
    
    /**
     * Vérifie si un utilisateur est modérateur
     */
    public function isModerator($userId) {
        $userRole = $this->getUserRole($userId);
        return $userRole && ($userRole['role_name'] === 'Modérateur' || $userRole['role_name'] === 'Admin');
    }
    
    /**
     * Vérifie si un utilisateur est banni
     */
    public function isBanned($userId) {
        $userRole = $this->getUserRole($userId);
        return $userRole && $userRole['role_name'] === 'Banni';
    }
    
    /**
     * Récupère tous les rôles disponibles
     */
    public function getAllRoles() {
        $stmt = $this->pdo->query("SELECT * FROM user_roles ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Change le rôle d'un utilisateur
     */
    public function changeUserRole($userId, $roleId) {
        $stmt = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        return $stmt->execute([$roleId, $userId]);
    }
    
    /**
     * Recherche des utilisateurs avec filtres
     */
    public function searchUsers($search = '', $roleId = null, $limit = 50, $offset = 0) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($roleId !== null) {
            $whereConditions[] = "u.role_id = ?";
            $params[] = $roleId;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Intégrer LIMIT et OFFSET directement dans la requête car ils ne supportent pas les paramètres préparés
        $sql = "
            SELECT u.id, u.username, u.email, u.created_at, u.avatar_url,
                   r.id as role_id, r.name as role_name
            FROM users u
            JOIN user_roles r ON u.role_id = r.id
            $whereClause
            ORDER BY u.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compte le nombre total d'utilisateurs avec filtres
     */
    public function countUsers($search = '', $roleId = null) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($roleId !== null) {
            $whereConditions[] = "u.role_id = ?";
            $params[] = $roleId;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "
            SELECT COUNT(*) as count
            FROM users u
            JOIN user_roles r ON u.role_id = r.id
            $whereClause
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
} 