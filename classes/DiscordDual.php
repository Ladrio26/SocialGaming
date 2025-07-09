<?php
require_once 'config_discord_dual.php';

class DiscordDual {
    private $pdo;
    private $api_base = 'https://discord.com/api/v10/';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Génère l'URL d'autorisation pour la connexion
     */
    public function getLoginAuthUrl($state = null) {
        $params = [
            'client_id' => DISCORD_LOGIN_CLIENT_ID,
            'redirect_uri' => DISCORD_LOGIN_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => DISCORD_LOGIN_SCOPES,
            'state' => $state ?? bin2hex(random_bytes(16))
        ];
        
        return 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
    }
    
    /**
     * Génère l'URL d'autorisation pour la liaison
     */
    public function getLinkAuthUrl($state = null) {
        $params = [
            'client_id' => DISCORD_LINK_CLIENT_ID,
            'redirect_uri' => DISCORD_LINK_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => DISCORD_LINK_SCOPES,
            'state' => $state ?? bin2hex(random_bytes(16))
        ];
        
        return 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
    }
    
    /**
     * Échange le code contre un token pour la connexion
     */
    public function exchangeLoginCodeForToken($code) {
        return $this->exchangeCodeForToken($code, DISCORD_LOGIN_CLIENT_ID, DISCORD_LOGIN_CLIENT_SECRET, DISCORD_LOGIN_REDIRECT_URI);
    }
    
    /**
     * Échange le code contre un token pour la liaison
     */
    public function exchangeLinkCodeForToken($code) {
        return $this->exchangeCodeForToken($code, DISCORD_LINK_CLIENT_ID, DISCORD_LINK_CLIENT_SECRET, DISCORD_LINK_REDIRECT_URI);
    }
    
    /**
     * Échange le code contre un token d'accès
     */
    private function exchangeCodeForToken($code, $client_id, $client_secret, $redirect_uri) {
        $data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_uri,
            'scope' => $redirect_uri === DISCORD_LOGIN_REDIRECT_URI ? DISCORD_LOGIN_SCOPES : DISCORD_LINK_SCOPES
        ];
        
        $response = $this->makeRequest('https://discord.com/api/oauth2/token', 'POST', $data);
        
        if (isset($response['access_token'])) {
            return $response;
        }
        
        throw new Exception('Erreur lors de l\'échange du token: ' . json_encode($response));
    }
    
    /**
     * Récupère les informations de l'utilisateur Discord
     */
    public function getUserInfo($access_token) {
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        
        $response = $this->makeRequest($this->api_base . 'users/@me', 'GET', null, $headers);
        
        if (isset($response['id'])) {
            return $response;
        }
        
        throw new Exception('Erreur lors de la récupération des informations utilisateur');
    }
    
    /**
     * Lie un compte Discord à un utilisateur
     */
    public function linkAccount($user_id, $discord_user_id, $discord_username, $discord_display_name, $discord_avatar_url, $access_token, $refresh_token, $expires_in) {
        $expires_at = getCurrentDateParis();
        $expires_at = date('Y-m-d H:i:s', strtotime($expires_at) + $expires_in);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO discord_accounts (user_id, discord_user_id, discord_username, discord_display_name, discord_avatar_url, discord_access_token, discord_refresh_token, discord_token_expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            discord_username = VALUES(discord_username),
            discord_display_name = VALUES(discord_display_name),
            discord_avatar_url = VALUES(discord_avatar_url),
            discord_access_token = VALUES(discord_access_token),
            discord_refresh_token = VALUES(discord_refresh_token),
            discord_token_expires_at = VALUES(discord_token_expires_at),
            is_active = TRUE,
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $user_id, $discord_user_id, $discord_username, $discord_display_name, 
            $discord_avatar_url, $access_token, $refresh_token, $expires_at
        ]);
    }
    
    /**
     * Délie un compte Discord
     */
    public function unlinkAccount($user_id) {
        $stmt = $this->pdo->prepare("
            UPDATE discord_accounts 
            SET is_active = FALSE, updated_at = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ");
        
        return $stmt->execute([$user_id]);
    }
    
    /**
     * Récupère le compte Discord lié d'un utilisateur
     */
    public function getLinkedAccount($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM discord_accounts 
            WHERE user_id = ? AND is_active = TRUE
        ");
        
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Effectue une requête HTTP
     */
    private function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erreur cURL: ' . $error);
        }
        
        if ($http_code >= 400) {
            throw new Exception('Erreur HTTP ' . $http_code . ': ' . $response);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg());
        }
        
        return $decoded;
    }
}
?> 