-- Intégration Discord - Table pour les comptes Discord liés
CREATE TABLE IF NOT EXISTS discord_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    discord_user_id VARCHAR(50) NOT NULL UNIQUE,
    discord_username VARCHAR(100) NOT NULL,
    discord_display_name VARCHAR(100) NOT NULL,
    discord_avatar_url VARCHAR(500),
    discord_access_token VARCHAR(500) NOT NULL,
    discord_refresh_token VARCHAR(500) NOT NULL,
    discord_token_expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_discord (user_id, discord_user_id)
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_discord_accounts_user_id ON discord_accounts(user_id);
CREATE INDEX idx_discord_accounts_active ON discord_accounts(is_active); 