-- Ajout du système de rôles utilisateurs
-- 1. Création de la table des rôles
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Ajout de la colonne role_id à la table users
ALTER TABLE users ADD COLUMN role_id INT DEFAULT 3;

-- 3. Insertion des rôles par défaut
INSERT INTO user_roles (id, name, description, permissions) VALUES
(1, 'Admin', 'Administrateur avec tous les droits', '{"manage_users": true, "manage_roles": true, "delete_posts": true, "delete_avatars": true, "edit_usernames": true, "ban_users": true, "access_admin_panel": true}'),
(2, 'Modérateur', 'Modérateur avec droits de modération', '{"delete_posts": true, "delete_avatars": true, "edit_usernames": true, "ban_users": false, "access_admin_panel": false}'),
(3, 'Gamer', 'Utilisateur standard', '{"delete_posts": false, "delete_avatars": false, "edit_usernames": false, "ban_users": false, "access_admin_panel": false}'),
(4, 'Banni', 'Utilisateur banni', '{"delete_posts": false, "delete_avatars": false, "edit_usernames": false, "ban_users": false, "access_admin_panel": false}');

-- 4. Mise à jour des utilisateurs existants pour leur donner le rôle Gamer par défaut
UPDATE users SET role_id = 3 WHERE role_id IS NULL OR role_id = 0;

-- 5. Ajout d'une contrainte de clé étrangère
ALTER TABLE users ADD CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES user_roles(id);

-- 6. Création d'un index pour optimiser les requêtes
CREATE INDEX idx_users_role ON users(role_id); 