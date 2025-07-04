<?php
class Steam {
    private $pdo;
    private $api_key;
    
    public function __construct($pdo, $api_key = null) {
        $this->pdo = $pdo;
        
        // Si la clé API n'est pas fournie, essayer de la récupérer depuis la configuration
        if ($api_key === null) {
            // Inclure la configuration Steam si elle n'est pas déjà incluse
            if (!defined('STEAM_API_KEY')) {
                $config_file = __DIR__ . '/../config_steam_oauth.php';
                if (file_exists($config_file)) {
                    include $config_file;
                    if (isset($steam_api_key)) {
                        $this->api_key = $steam_api_key;
                    }
                }
            } else {
                $this->api_key = STEAM_API_KEY;
            }
        } else {
            $this->api_key = $api_key;
        }
    }
    
    // Lier un compte Steam à un utilisateur
    public function linkSteamAccount($user_id, $steam_id, $steam_avatar = null) {
        try {
            // Log pour déboguer
            error_log("Steam::linkSteamAccount - user_id: $user_id, steam_id: $steam_id, steam_avatar: " . ($steam_avatar ?? 'null'));
            
            // Vérifier si le Steam ID est déjà lié à un autre compte
            $stmt = $this->pdo->prepare("SELECT user_id FROM steam_accounts WHERE steam_id = ? AND user_id != ?");
            $stmt->execute([$steam_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Steam::linkSteamAccount - Steam ID déjà lié à un autre utilisateur");
                return ['success' => false, 'message' => 'Ce compte Steam est déjà lié à un autre utilisateur'];
            }
            
            // Vérifier si l'utilisateur a déjà un compte Steam lié
            $stmt = $this->pdo->prepare("SELECT id FROM steam_accounts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Mettre à jour le compte existant
                error_log("Steam::linkSteamAccount - Mise à jour du lien existant");
                $stmt = $this->pdo->prepare("UPDATE steam_accounts SET steam_id = ?, steam_avatar = ?, linked_at = NOW() WHERE user_id = ?");
                $stmt->execute([$steam_id, $steam_avatar, $user_id]);
            } else {
                // Créer un nouveau lien
                error_log("Steam::linkSteamAccount - Création d'un nouveau lien");
                $stmt = $this->pdo->prepare("INSERT INTO steam_accounts (user_id, steam_id, steam_avatar, linked_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $steam_id, $steam_avatar]);
            }
            
            error_log("Steam::linkSteamAccount - Lien créé/mis à jour avec succès");
            
            // Récupérer et sauvegarder les informations Steam (optionnel pour le moment)
            // $this->updateSteamInfo($user_id, $steam_id);
            
            return ['success' => true, 'message' => 'Compte Steam lié avec succès !'];
            
        } catch (PDOException $e) {
            error_log("Steam::linkSteamAccount - Erreur PDO: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la liaison : ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Steam::linkSteamAccount - Erreur générale: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la liaison : ' . $e->getMessage()];
        }
    }
    
    // Vérifier la propriété d'un compte Steam
    public function verifySteamOwnership($steam_id) {
        if (!$this->api_key) {
            return ['success' => false, 'message' => 'Clé API Steam non configurée'];
        }
        
        try {
            // Récupérer les informations du profil Steam
            $profile_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$this->api_key}&steamids={$steam_id}";
            $profile_data = file_get_contents($profile_url);
            $profile_json = json_decode($profile_data, true);
            
            if (!isset($profile_json['response']['players'][0])) {
                return ['success' => false, 'message' => 'Profil Steam introuvable'];
            }
            
            $player = $profile_json['response']['players'][0];
            
            // Vérifier si le profil est public
            if ($player['communityvisibilitystate'] != 3) {
                return ['success' => false, 'message' => 'Le profil Steam doit être public pour la vérification'];
            }
            
            // Générer un code de vérification unique
            $verification_code = $this->generateVerificationCode();
            
            // Sauvegarder le code de vérification temporairement
            $stmt = $this->pdo->prepare("
                INSERT INTO steam_verification_codes (steam_id, verification_code, created_at, expires_at) 
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))
                ON DUPLICATE KEY UPDATE 
                verification_code = VALUES(verification_code), 
                created_at = NOW(), 
                expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute([$steam_id, $verification_code]);
            
            return [
                'success' => true, 
                'message' => 'Code de vérification généré',
                'verification_code' => $verification_code,
                'steam_profile' => $player
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la vérification : ' . $e->getMessage()];
        }
    }
    
    // Vérifier le code de vérification
    public function checkVerificationCode($steam_id, $verification_code) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM steam_verification_codes 
                WHERE steam_id = ? AND verification_code = ? AND expires_at > NOW()
            ");
            $stmt->execute([$steam_id, $verification_code]);
            $code_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$code_data) {
                return ['success' => false, 'message' => 'Code de vérification invalide ou expiré'];
            }
            
            // Vérifier que le nom Steam contient le code
            if (!$this->api_key) {
                return ['success' => false, 'message' => 'Clé API Steam non configurée'];
            }
            
            $profile_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$this->api_key}&steamids={$steam_id}";
            $profile_data = file_get_contents($profile_url);
            $profile_json = json_decode($profile_data, true);
            
            if (!isset($profile_json['response']['players'][0])) {
                return ['success' => false, 'message' => 'Profil Steam introuvable'];
            }
            
            $player = $profile_json['response']['players'][0];
            $steam_name = $player['personaname'];
            
            if (strpos($steam_name, $verification_code) === false) {
                return ['success' => false, 'message' => 'Le code de vérification n\'est pas présent dans votre nom Steam'];
            }
            
            // Supprimer le code de vérification utilisé
            $stmt = $this->pdo->prepare("DELETE FROM steam_verification_codes WHERE steam_id = ? AND verification_code = ?");
            $stmt->execute([$steam_id, $verification_code]);
            
            return ['success' => true, 'message' => 'Vérification réussie !'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la vérification : ' . $e->getMessage()];
        }
    }
    
    // Générer un code de vérification
    private function generateVerificationCode() {
        return 'LADRIO' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
    
    // Délier un compte Steam
    public function unlinkSteamAccount($user_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM steam_accounts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            return ['success' => true, 'message' => 'Compte Steam délié avec succès'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la déliaison : ' . $e->getMessage()];
        }
    }
    
    // Récupérer les informations Steam d'un utilisateur
    public function getSteamInfo($user_id) {
        try {
            error_log("Steam::getSteamInfo - Recherche pour user_id: $user_id");
            
            $stmt = $this->pdo->prepare("
                SELECT sa.*, sp.* 
                FROM steam_accounts sa 
                LEFT JOIN steam_profiles sp ON sa.steam_id = sp.steam_id 
                WHERE sa.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $steam_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Steam::getSteamInfo - Résultat: " . json_encode($steam_info));
            
            if ($steam_info) {
                // Récupérer les jeux
                $games = $this->getSteamGames($user_id);
                $steam_info['games'] = $games;
                
                error_log("Steam::getSteamInfo - Steam ID trouvé: " . ($steam_info['steam_id'] ?? 'NULL'));
            } else {
                error_log("Steam::getSteamInfo - Aucune information trouvée");
            }
            
            return $steam_info;
            
        } catch (PDOException $e) {
            error_log("Steam::getSteamInfo - Erreur PDO: " . $e->getMessage());
            return false;
        }
    }
    
    // Mettre à jour les informations Steam
    public function updateSteamInfo($user_id, $steam_id) {
        if (!$this->api_key) {
            error_log("Steam::updateSteamInfo - Pas de clé API");
            return false;
        }
        
        try {
            error_log("Steam::updateSteamInfo - Début pour user_id: $user_id, steam_id: $steam_id");
            
            // Récupérer les informations du profil Steam
            $profile_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$this->api_key}&steamids={$steam_id}";
            $profile_data = file_get_contents($profile_url);
            $profile_json = json_decode($profile_data, true);
            
            if (isset($profile_json['response']['players'][0])) {
                $player = $profile_json['response']['players'][0];
                error_log("Steam::updateSteamInfo - Profil Steam récupéré: " . $player['personaname']);
                
                // Sauvegarder les informations du profil
                $stmt = $this->pdo->prepare("
                    INSERT INTO steam_profiles (steam_id, username, realname, avatar, profile_url, last_updated) 
                    VALUES (?, ?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    username = VALUES(username), 
                    realname = VALUES(realname), 
                    avatar = VALUES(avatar), 
                    profile_url = VALUES(profile_url), 
                    last_updated = NOW()
                ");
                $stmt->execute([
                    $steam_id,
                    $player['personaname'] ?? '',
                    $player['realname'] ?? '',
                    $player['avatarfull'] ?? '',
                    $player['profileurl'] ?? ''
                ]);
                
                // Mettre à jour l'avatar dans la table steam_accounts
                $steam_avatar = $player['avatarfull'] ?? null;
                if ($steam_avatar) {
                    $stmt = $this->pdo->prepare("UPDATE steam_accounts SET steam_avatar = ? WHERE steam_id = ?");
                    $stmt->execute([$steam_avatar, $steam_id]);
                }
                
                error_log("Steam::updateSteamInfo - Profil et avatar sauvegardés");
            } else {
                error_log("Steam::updateSteamInfo - Aucun profil Steam trouvé");
            }
            
            // Récupérer et sauvegarder les jeux
            $games_result = $this->updateSteamGames($user_id, $steam_id);
            error_log("Steam::updateSteamInfo - Résultat updateSteamGames: " . ($games_result ? 'succès' : 'échec'));
            
            return true;
            
        } catch (Exception $e) {
            error_log("Steam::updateSteamInfo - Erreur: " . $e->getMessage());
            return false;
        }
    }
    
    // Récupérer les jeux Steam d'un utilisateur
    public function getSteamGames($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sg.* 
                FROM steam_games sg 
                JOIN steam_accounts sa ON sg.steam_id = sa.steam_id 
                WHERE sa.user_id = ? 
                ORDER BY sg.playtime_forever DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Mettre à jour les jeux Steam
    private function updateSteamGames($user_id, $steam_id) {
        if (!$this->api_key) {
            return false;
        }
        
        try {
            // Récupérer la liste des jeux
            $games_url = "http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$this->api_key}&steamid={$steam_id}&include_appinfo=1&include_played_free_games=1";
            $games_data = file_get_contents($games_url);
            $games_json = json_decode($games_data, true);
            
            if (isset($games_json['response']['games'])) {
                // Supprimer les anciens jeux
                $stmt = $this->pdo->prepare("DELETE FROM steam_games WHERE steam_id = ?");
                $stmt->execute([$steam_id]);
                
                // Insérer les nouveaux jeux
                $stmt = $this->pdo->prepare("
                    INSERT INTO steam_games (steam_id, app_id, name, playtime_forever, playtime_2weeks, img_icon_url, img_logo_url) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($games_json['response']['games'] as $game) {
                    $stmt->execute([
                        $steam_id,
                        $game['appid'],
                        $game['name'],
                        $game['playtime_forever'] ?? 0,
                        $game['playtime_2weeks'] ?? 0,
                        $game['img_icon_url'] ?? '',
                        $game['img_logo_url'] ?? ''
                    ]);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Récupérer le code ami Steam
    public function getSteamFriendCode($steam_id) {
        // Steam a remplacé les anciens codes ami par des Friend Codes
        // Ces codes ne peuvent pas être générés directement depuis l'API publique
        // Nous affichons donc le Steam ID et un lien vers le profil
        
        $steam_id = (string)$steam_id;
        
        // Debug: afficher le Steam ID reçu
        error_log("Steam::getSteamFriendCode - Steam ID reçu: '$steam_id'");
        
        // Vérifier que c'est un Steam ID valide
        if (!preg_match('/^7656119\d{10}$/', $steam_id)) {
            error_log("Steam::getSteamFriendCode - Steam ID invalide: '$steam_id'");
            return "Steam ID invalide: $steam_id";
        }
        
        // Retourner le Steam ID et un lien vers le profil
        return "Steam ID: $steam_id";
    }
    
    // Vérifier si un Steam ID est valide
    public function validateSteamId($steam_id) {
        // Un Steam ID valide doit être un nombre de 17 chiffres commençant par 7656119
        return preg_match('/^7656119\d{10}$/', $steam_id);
    }
    
    // Convertir différents formats d'ID Steam
    public function convertSteamId($input) {
        // Si c'est déjà un Steam ID 64-bit
        if (preg_match('/^7656119\d{10}$/', $input)) {
            return $input;
        }
        
        // Si c'est un Steam ID 32-bit
        if (preg_match('/^STEAM_0:(\d):(\d+)$/', $input, $matches)) {
            $y = intval($matches[1]);
            $z = intval($matches[2]);
            return 76561197960265728 + ($z * 2) + $y;
        }
        
        // Si c'est un Steam ID 3
        if (preg_match('/^\[U:1:(\d+)\]$/', $input, $matches)) {
            $account_id = intval($matches[1]);
            return 76561197960265728 + $account_id;
        }
        
        return false;
    }
}
?> 