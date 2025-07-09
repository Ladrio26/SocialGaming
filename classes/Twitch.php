<?php
require_once __DIR__ . '/../includes/date_utils.php';

class Twitch {
    private $pdo;
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $api_base = 'https://api.twitch.tv/helix/';
    
    public function __construct($pdo, $client_id = null, $client_secret = null) {
        $this->pdo = $pdo;
        $this->client_id = $client_id ?? TWITCH_CLIENT_ID ?? null;
        $this->client_secret = $client_secret ?? TWITCH_CLIENT_SECRET ?? null;
        $this->redirect_uri = TWITCH_REDIRECT_URI ?? 'http://localhost:8000/twitch_callback.php';
    }
    
    /**
     * Génère l'URL d'autorisation OAuth2
     */
    public function getAuthUrl($state = null, $redirect_uri = null) {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $redirect_uri ?? $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'user:read:email channel:read:subscriptions',
            'state' => $state ?? bin2hex(random_bytes(16))
        ];
        
        return 'https://id.twitch.tv/oauth2/authorize?' . http_build_query($params);
    }
    
    /**
     * Échange le code d'autorisation contre un token d'accès
     */
    public function exchangeCodeForToken($code, $redirect_uri = null) {
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_uri ?? $this->redirect_uri
        ];
        
        $response = $this->makeRequest('https://id.twitch.tv/oauth2/token', 'POST', $data);
        
        if (isset($response['access_token'])) {
            return $response;
        }
        
        throw new Exception('Erreur lors de l\'échange du token: ' . json_encode($response));
    }
    
    /**
     * Rafraîchit un token d'accès
     */
    public function refreshToken($refresh_token) {
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ];
        
        $response = $this->makeRequest('https://id.twitch.tv/oauth2/token', 'POST', $data);
        
        if (isset($response['access_token'])) {
            return $response;
        }
        
        throw new Exception('Erreur lors du rafraîchissement du token: ' . json_encode($response));
    }
    
    /**
     * Récupère les informations de l'utilisateur Twitch
     */
    public function getUserInfo($access_token) {
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Client-Id: ' . $this->client_id
        ];
        
        $response = $this->makeRequest($this->api_base . 'users', 'GET', null, $headers);
        
        if (isset($response['data']) && !empty($response['data'])) {
            return $response['data'][0];
        }
        
        throw new Exception('Erreur lors de la récupération des informations utilisateur');
    }
    
    /**
     * Récupère les streams en direct des utilisateurs suivis
     */
    public function getFollowedStreams($access_token, $user_id, $limit = 20) {
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Client-Id: ' . $this->client_id
        ];
        
        $params = [
            'user_id' => $user_id,
            'first' => $limit
        ];
        
        $response = $this->makeRequest($this->api_base . 'streams/followed?' . http_build_query($params), 'GET', null, $headers);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Récupère les streams en direct par nom d'utilisateur
     */
    public function getStreamsByUsernames($usernames) {
        if (empty($usernames)) {
            return [];
        }
        
        $params = [];
        foreach ($usernames as $username) {
            $params[] = 'user_login=' . urlencode($username);
        }
        
        $url = $this->api_base . 'streams?' . implode('&', $params);
        
        $headers = [
            'Client-Id: ' . $this->client_id
        ];
        
        // Utiliser un token d'application pour les requêtes publiques
        if (defined('TWITCH_APP_ACCESS_TOKEN')) {
            $headers[] = 'Authorization: Bearer ' . TWITCH_APP_ACCESS_TOKEN;
        }
        
        $response = $this->makeRequest($url, 'GET', null, $headers);
        
        return $response['data'] ?? [];
    }
    
    /**
     * Lie un compte Twitch à un utilisateur
     */
    public function linkAccount($user_id, $twitch_user_id, $twitch_username, $twitch_display_name, $twitch_profile_image_url, $access_token, $refresh_token, $expires_in) {
        $expires_at = getCurrentDateParis();
        $expires_at = date('Y-m-d H:i:s', strtotime($expires_at) + $expires_in);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO twitch_accounts (user_id, twitch_user_id, twitch_username, twitch_display_name, twitch_profile_image_url, twitch_access_token, twitch_refresh_token, twitch_token_expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            twitch_username = VALUES(twitch_username),
            twitch_display_name = VALUES(twitch_display_name),
            twitch_profile_image_url = VALUES(twitch_profile_image_url),
            twitch_access_token = VALUES(twitch_access_token),
            twitch_refresh_token = VALUES(twitch_refresh_token),
            twitch_token_expires_at = VALUES(twitch_token_expires_at),
            is_active = TRUE,
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $user_id, $twitch_user_id, $twitch_username, $twitch_display_name, 
            $twitch_profile_image_url, $access_token, $refresh_token, $expires_at
        ]);
    }
    
    /**
     * Délie un compte Twitch
     */
    public function unlinkAccount($user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE twitch_accounts 
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ");
        
        return $stmt->execute([$user_id]);
    }
    
    /**
     * Récupère le compte Twitch lié d'un utilisateur
     */
    public function getLinkedAccount($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM twitch_accounts 
            WHERE user_id = ? AND is_active = TRUE
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour les tokens d'un compte Twitch
     */
    public function updateTokens($user_id, $access_token, $refresh_token, $expires_in) {
        $expires_at = getCurrentDateParis();
        $expires_at = date('Y-m-d H:i:s', strtotime($expires_at) + $expires_in);
        
        $stmt = $this->pdo->prepare("
            UPDATE twitch_accounts 
            SET twitch_access_token = ?, 
                twitch_refresh_token = ?, 
                twitch_token_expires_at = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        
        return $stmt->execute([$access_token, $refresh_token, $expires_at, $user_id]);
    }
    
    /**
     * Met à jour les streams en cache
     */
    public function updateStreamsCache($streams) {
        // Supprimer les anciens streams
        $stmt = $this->pdo->prepare("
            UPDATE twitch_streams 
            SET is_live = FALSE, last_updated = CURRENT_TIMESTAMP 
            WHERE last_updated < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute();
        
        // Insérer/mettre à jour les nouveaux streams
        foreach ($streams as $stream) {
            $stmt = $this->pdo->prepare("
                INSERT INTO twitch_streams (
                    twitch_user_id, twitch_username, twitch_display_name, twitch_profile_image_url,
                    stream_id, stream_title, stream_game_name, stream_viewer_count,
                    stream_started_at, stream_thumbnail_url, stream_language, is_live
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE
                stream_title = VALUES(stream_title),
                stream_game_name = VALUES(stream_game_name),
                stream_viewer_count = VALUES(stream_viewer_count),
                stream_thumbnail_url = VALUES(stream_thumbnail_url),
                is_live = TRUE,
                last_updated = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $stream['user_id'],
                $stream['user_login'],
                $stream['user_name'],
                $stream['user_profile_image_url'] ?? null,
                $stream['id'],
                $stream['title'],
                $stream['game_name'],
                $stream['viewer_count'],
                $stream['started_at'],
                $stream['thumbnail_url'],
                $stream['language']
            ]);
        }
    }
    
    /**
     * Récupère les streams en direct depuis le cache
     */
    public function getLiveStreamsFromCache($limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM twitch_streams 
            WHERE is_live = TRUE 
            ORDER BY stream_viewer_count DESC 
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Effectue une requête HTTP
     */
    private function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 400) {
            throw new Exception('Erreur HTTP ' . $http_code . ': ' . $response);
        }
        
        return json_decode($response, true);
    }
} 