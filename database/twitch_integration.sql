-- Intégration Twitch
-- Tables pour stocker les comptes Twitch liés et les streams

-- Table pour les comptes Twitch liés
CREATE TABLE IF NOT EXISTS twitch_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    twitch_user_id VARCHAR(50) NOT NULL UNIQUE,
    twitch_username VARCHAR(100) NOT NULL,
    twitch_display_name VARCHAR(100) NOT NULL,
    twitch_profile_image_url VARCHAR(500),
    twitch_access_token VARCHAR(500) NOT NULL,
    twitch_refresh_token VARCHAR(500) NOT NULL,
    twitch_token_expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_twitch (user_id, twitch_user_id)
);

-- Table pour les streams en direct (cache)
CREATE TABLE IF NOT EXISTS twitch_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    twitch_user_id VARCHAR(50) NOT NULL,
    twitch_username VARCHAR(100) NOT NULL,
    twitch_display_name VARCHAR(100) NOT NULL,
    twitch_profile_image_url VARCHAR(500),
    stream_id VARCHAR(50) NOT NULL,
    stream_title VARCHAR(200),
    stream_game_name VARCHAR(100),
    stream_viewer_count INT DEFAULT 0,
    stream_started_at TIMESTAMP,
    stream_thumbnail_url VARCHAR(500),
    stream_language VARCHAR(10),
    is_live BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_stream (twitch_user_id, stream_id)
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_twitch_accounts_user_id ON twitch_accounts(user_id);
CREATE INDEX idx_twitch_accounts_active ON twitch_accounts(is_active);
CREATE INDEX idx_twitch_streams_live ON twitch_streams(is_live);
CREATE INDEX idx_twitch_streams_updated ON twitch_streams(last_updated); 