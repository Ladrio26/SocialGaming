<?php
require_once __DIR__ . '/../includes/date_utils.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Inscription manuelle
    public function registerManual($username, $email, $password, $first_name = null, $last_name = null) {
        try {
            // Vérifier si l'utilisateur existe déjà
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Un utilisateur avec cet email ou nom d\'utilisateur existe déjà'];
            }
            
            // Hasher le mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer l'utilisateur
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, auth_provider, display_format) VALUES (?, ?, ?, 'manual', 'username_only')");
            $stmt->execute([$username, $email, $password_hash]);
            
            // Récupérer l'ID de l'utilisateur créé
            $user_id = $this->pdo->lastInsertId();
            
            // Créer automatiquement une session pour connecter l'utilisateur
            $session_result = $this->createSession($user_id);
            
            if ($session_result['success']) {
                return ['success' => true, 'message' => 'Inscription réussie ! Vous êtes maintenant connecté.'];
            } else {
                return ['success' => true, 'message' => 'Inscription réussie ! Veuillez vous connecter.'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription : ' . $e->getMessage()];
        }
    }
    
    // Connexion manuelle
    public function loginManual($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? AND auth_provider = 'manual'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                return $this->createSession($user['id']);
            } else {
                return ['success' => false, 'message' => 'Pseudo ou mot de passe incorrect'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la connexion : ' . $e->getMessage()];
        }
    }
    
    // Inscription/connexion avec Discord
    public function handleDiscordAuth($discord_data) {
        try {
            // Vérifier si l'utilisateur existe déjà
            $stmt = $this->pdo->prepare("SELECT id, username, email FROM users WHERE provider_id = ? AND auth_provider = 'discord'");
            $stmt->execute([$discord_data['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Utilisateur existe, mettre à jour l'avatar Discord si nécessaire
                if (!empty($discord_data['avatar'])) {
                    $stmt = $this->pdo->prepare("UPDATE users SET discord_avatar = ? WHERE id = ?");
                    $stmt->execute([$discord_data['avatar'], $user['id']]);
                }
                
                // Créer une session
                return $this->createSession($user['id']);
            } else {
                // Vérifier si le nom d'utilisateur est déjà pris
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$discord_data['username']]);
                if ($stmt->fetch()) {
                    // Le nom d'utilisateur existe déjà, ajouter un suffixe
                    $counter = 1;
                    $original_username = $discord_data['username'];
                    do {
                        $new_username = $original_username . '_' . $counter;
                        $stmt->execute([$new_username]);
                        $counter++;
                    } while ($stmt->fetch());
                    $discord_data['username'] = $new_username;
                }
                
                // Nouvel utilisateur, l'inscrire
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, auth_provider, provider_id, discord_avatar, display_format) VALUES (?, ?, 'discord', ?, ?, 'username_only')");
                $stmt->execute([$discord_data['username'], $discord_data['email'], $discord_data['id'], $discord_data['avatar']]);
                
                $user_id = $this->pdo->lastInsertId();
                return $this->createSession($user_id);
            }
            
        } catch (PDOException $e) {
            error_log('Erreur PDO lors de l\'authentification Discord: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'authentification Discord : ' . $e->getMessage()];
        }
    }
    
    // Inscription/connexion avec Steam
    public function handleSteamAuth($steam_data) {
        try {
            error_log("Auth::handleSteamAuth - steam_data: " . json_encode($steam_data));
            
            // D'abord, vérifier si ce Steam ID est déjà lié à un compte existant
            $stmt = $this->pdo->prepare("SELECT user_id FROM steam_accounts WHERE steam_id = ?");
            $stmt->execute([$steam_data['id']]);
            $steam_link = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($steam_link) {
                // Ce Steam ID est déjà lié à un compte, connecter cet utilisateur
                error_log("Auth::handleSteamAuth - Steam ID déjà lié à l'utilisateur: " . $steam_link['user_id']);
                
                // Mettre à jour les informations Steam
                $steam = new Steam($this->pdo);
                $steam->updateSteamInfo($steam_link['user_id'], $steam_data['id']);
                
                return $this->createSession($steam_link['user_id']);
            }
            
            // Vérifier si l'utilisateur existe déjà avec auth_provider = 'steam'
            $stmt = $this->pdo->prepare("SELECT id, username, email FROM users WHERE provider_id = ? AND auth_provider = 'steam'");
            $stmt->execute([$steam_data['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Utilisateur existe, s'assurer que le lien Steam existe
                error_log("Auth::handleSteamAuth - Utilisateur existant trouvé: " . $user['id']);
                $steam = new Steam($this->pdo);
                $link_result = $steam->linkSteamAccount($user['id'], $steam_data['id'], $steam_data['avatar'] ?? null);
                error_log("Auth::handleSteamAuth - Résultat du lien Steam: " . json_encode($link_result));
                
                // Mettre à jour les informations Steam
                $steam->updateSteamInfo($user['id'], $steam_data['id']);
                
                // Créer une session
                return $this->createSession($user['id']);
            } else {
                // Vérifier s'il y a un utilisateur connecté actuellement
                $current_user = $this->isLoggedIn();
                
                if ($current_user) {
                    // Utilisateur connecté, lier Steam à son compte existant
                    error_log("Auth::handleSteamAuth - Liaison Steam à l'utilisateur connecté: " . $current_user['id']);
                    $steam = new Steam($this->pdo);
                    $link_result = $steam->linkSteamAccount($current_user['id'], $steam_data['id'], $steam_data['avatar'] ?? null);
                    error_log("Auth::handleSteamAuth - Résultat du lien Steam: " . json_encode($link_result));
                    
                    if ($link_result['success']) {
                        // Mettre à jour les informations Steam
                        $steam->updateSteamInfo($current_user['id'], $steam_data['id']);
                        return ['success' => true, 'message' => 'Compte Steam lié avec succès !'];
                    } else {
                        return $link_result;
                    }
                } else {
                    // Nouvel utilisateur, l'inscrire
                    error_log("Auth::handleSteamAuth - Création d'un nouvel utilisateur");
                    $stmt = $this->pdo->prepare("INSERT INTO users (username, email, auth_provider, provider_id, avatar_url, display_format) VALUES (?, ?, 'steam', ?, ?, 'username_only')");
                    $stmt->execute([$steam_data['username'], $steam_data['email'], $steam_data['id'], $steam_data['avatar']]);
                    
                    $user_id = $this->pdo->lastInsertId();
                    error_log("Auth::handleSteamAuth - Nouvel utilisateur créé: $user_id");
                    
                    // Lier automatiquement le compte Steam
                    $steam = new Steam($this->pdo);
                    $link_result = $steam->linkSteamAccount($user_id, $steam_data['id'], $steam_data['avatar'] ?? null);
                    error_log("Auth::handleSteamAuth - Résultat du lien Steam: " . json_encode($link_result));
                    
                    // Mettre à jour les informations Steam
                    $steam->updateSteamInfo($user_id, $steam_data['id']);
                    
                    return $this->createSession($user_id);
                }
            }
            
        } catch (PDOException $e) {
            error_log("Auth::handleSteamAuth - Erreur PDO: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'authentification Steam : ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Auth::handleSteamAuth - Erreur générale: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'authentification Steam : ' . $e->getMessage()];
        }
    }
    
    // Créer une session utilisateur
    public function createSession($user_id) {
        try {
            $session_token = bin2hex(random_bytes(32));
            $expires_at = getCurrentDateParis();
        $expires_at = date('Y-m-d H:i:s', strtotime($expires_at . ' +30 days'));
            
            $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $session_token, $expires_at]);
            
            // Définir le cookie de session
            setcookie('session_token', $session_token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            
            return ['success' => true, 'message' => 'Connexion réussie !'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la création de session : ' . $e->getMessage()];
        }
    }
    
    // Vérifier si l'utilisateur est connecté
    public function isLoggedIn() {
        if (!isset($_COOKIE['session_token'])) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.avatar_url, u.discord_avatar, u.auth_provider, u.display_format, u.provider_id 
                FROM users u 
                JOIN user_sessions s ON u.id = s.user_id 
                WHERE s.session_token = ? AND s.expires_at > NOW()
            ");
            $stmt->execute([$_COOKIE['session_token']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ? $user : false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Déconnexion
    public function logout() {
        if (isset($_COOKIE['session_token'])) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                $stmt->execute([$_COOKIE['session_token']]);
                
                setcookie('session_token', '', time() - 3600, '/');
                
                return ['success' => true, 'message' => 'Déconnexion réussie'];
                
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Erreur lors de la déconnexion'];
            }
        }
        
        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }
}