<?php
// Configuration des logs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/steam_debug.log');
error_reporting(E_ALL);

require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Steam.php';
require_once 'config_steam_oauth.php';

echo "<h2>Débogage Steam OAuth</h2>";

// Vérifier les paramètres GET
echo "<h3>Paramètres GET reçus :</h3>";
if (!empty($_GET)) {
    echo "<pre>" . htmlspecialchars(json_encode($_GET, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p>Aucun paramètre GET reçu</p>";
}

// Vérifier les paramètres POST
echo "<h3>Paramètres POST reçus :</h3>";
if (!empty($_POST)) {
    echo "<pre>" . htmlspecialchars(json_encode($_POST, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p>Aucun paramètre POST reçu</p>";
}

// Vérifier les cookies de session
echo "<h3>Cookies de session :</h3>";
if (!empty($_COOKIE)) {
    echo "<pre>" . htmlspecialchars(json_encode($_COOKIE, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p>Aucun cookie reçu</p>";
}

// Vérifier la configuration Steam
echo "<h3>Configuration Steam :</h3>";
echo "<p>Clé API : " . substr($steam_api_key, 0, 10) . "...</p>";
echo "<p>URL de redirection : $steam_redirect_uri</p>";

// Vérifier l'état de la base de données
echo "<h3>État de la base de données :</h3>";

// Vérifier les utilisateurs Steam
$stmt = $pdo->prepare("SELECT id, username, auth_provider, provider_id FROM users WHERE auth_provider = 'steam'");
$stmt->execute();
$steam_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Utilisateurs Steam dans la base :</p>";
if (empty($steam_users)) {
    echo "<p>Aucun utilisateur Steam trouvé</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Auth Provider</th><th>Provider ID</th></tr>";
    foreach ($steam_users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['auth_provider']}</td>";
        echo "<td>{$user['provider_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Vérifier les liens Steam
$stmt = $pdo->prepare("SELECT * FROM steam_accounts");
$stmt->execute();
$steam_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Liens Steam dans la base :</p>";
if (empty($steam_accounts)) {
    echo "<p>Aucun lien Steam trouvé</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Steam ID</th><th>Linked At</th></tr>";
    foreach ($steam_accounts as $account) {
        echo "<tr>";
        echo "<td>{$account['id']}</td>";
        echo "<td>{$account['user_id']}</td>";
        echo "<td>{$account['steam_id']}</td>";
        echo "<td>{$account['linked_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test de la classe Steam
echo "<h3>Test de la classe Steam :</h3>";
$steam = new Steam($pdo, $steam_api_key);
echo "<p>Clé API dans la classe : " . (isset($steam->api_key) ? "Définie" : "Non définie") . "</p>";

// Test de l'authentification
echo "<h3>Test d'authentification :</h3>";
$auth = new Auth($pdo);
$user = $auth->isLoggedIn();
if ($user) {
    echo "<p>✅ Utilisateur connecté : {$user['username']} (ID: {$user['id']})</p>";
    echo "<p>Auth Provider : {$user['auth_provider']}</p>";
    echo "<p>Provider ID : {$user['provider_id']}</p>";
} else {
    echo "<p>❌ Aucun utilisateur connecté</p>";
}

// Afficher les logs récents
echo "<h3>Logs récents :</h3>";
$log_file = '/tmp/steam_debug.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    $recent_lines = array_slice($lines, -20); // 20 dernières lignes
    echo "<pre>";
    foreach ($recent_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>Aucun fichier de log trouvé</p>";
}

// Test de l'URL de connexion Steam
echo "<h3>Test de l'URL de connexion Steam :</h3>";
$steam_auth_url = 'https://steamcommunity.com/openid/login';
$params = [
    'openid.ns' => 'http://specs.openid.net/auth/2.0',
    'openid.mode' => 'checkid_setup',
    'openid.return_to' => $steam_redirect_uri,
    'openid.realm' => 'https://ladrio2.goodloss.fr',
    'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
    'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
];

echo "<p>URL de connexion Steam :</p>";
echo "<p><a href='$steam_auth_url?" . http_build_query($params) . "' target='_blank'>Se connecter avec Steam</a></p>";
?> 