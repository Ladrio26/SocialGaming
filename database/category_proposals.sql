-- Table pour les propositions de catégories
CREATE TABLE IF NOT EXISTS category_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected', 'modified') DEFAULT 'pending',
    moderator_id INT NULL,
    moderator_notes TEXT,
    original_name VARCHAR(100) NULL, -- Pour les propositions modifiées
    original_description TEXT NULL, -- Pour les propositions modifiées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    moderated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_category_proposals_status ON category_proposals(status);
CREATE INDEX idx_category_proposals_user_id ON category_proposals(user_id);
CREATE INDEX idx_category_proposals_created_at ON category_proposals(created_at); 