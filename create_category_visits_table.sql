-- Table pour suivre les visites des utilisateurs dans les catégories
CREATE TABLE IF NOT EXISTS category_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    last_visit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index pour optimiser les requêtes
    INDEX idx_user_category (user_id, category_id),
    INDEX idx_last_visit (last_visit_at),
    
    -- Contrainte d'unicité : un utilisateur ne peut avoir qu'une entrée par catégorie
    UNIQUE KEY unique_user_category (user_id, category_id),
    
    -- Clés étrangères
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commentaire pour expliquer l'usage
ALTER TABLE category_visits COMMENT = 'Table pour suivre la dernière visite d\'un utilisateur dans chaque catégorie'; 