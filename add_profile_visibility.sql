-- Ajouter le champ profile_visibility à la table users
ALTER TABLE users ADD COLUMN profile_visibility ENUM('public', 'private') DEFAULT 'private' AFTER display_format; 