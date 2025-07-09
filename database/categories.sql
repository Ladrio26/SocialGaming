-- Tables pour le système de catégories et partage

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#667eea',
    icon VARCHAR(50) DEFAULT 'fas fa-folder',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des posts dans les catégories
CREATE TABLE IF NOT EXISTS category_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200),
    content TEXT,
    image_url VARCHAR(500),
    image_filename VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des commentaires sur les posts
CREATE TABLE IF NOT EXISTS category_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES category_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des likes sur les posts
CREATE TABLE IF NOT EXISTS category_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES category_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
);

-- Insertion d'une catégorie de test
INSERT INTO categories (name, description, color, icon, created_by) VALUES 
('Gaming Memes', 'Partagez vos meilleurs memes de gaming !', '#ff6b6b', 'fas fa-laugh-squint', 1),
('Screenshots', 'Vos plus beaux moments de jeu en capture d''écran', '#4ecdc4', 'fas fa-camera', 1),
('Astuces & Guides', 'Partagez vos conseils et tutoriels de jeu', '#45b7d1', 'fas fa-lightbulb', 1),
('Setup Gaming', 'Montrez votre setup de gaming', '#96ceb4', 'fas fa-desktop', 1); 