<?php
require_once 'config.php';
require_once 'config_discord.php';
require_once 'classes/Auth.php';

// Vérifier si le code est présent
if (!isset($_GET['code'])) {
    die('Erreur : Code d\'autorisation non fourni par Discord');
}

// 1. Échanger le code contre un token d'accès
$token_url = DISCORD_TOKEN_URL;
$data = [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'scope' => DISCORD_SCOPES
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($token_url, false, $context);

if ($response === FALSE) {
    die('Erreur lors de la récupération du token Discord. Vérifiez votre connexion internet.');
}

$token = json_decode($response, true);

if (isset($token['error'])) {
    die('Erreur Discord : ' . htmlspecialchars($token['error_description'] ?? $token['error']));
}

// 2. Récupérer les informations de l'utilisateur
$user_url = DISCORD_USER_URL;
$opts = [
    'http' => [
        'header' => "Authorization: Bearer " . $token['access_token'] . "\r\n"
    ]
];

$user_context = stream_context_create($opts);
$user_response = file_get_contents($user_url, false, $user_context);

if ($user_response === FALSE) {
    die('Erreur lors de la récupération des informations utilisateur Discord');
}

$user_data = json_decode($user_response, true);

if (isset($user_data['error'])) {
    die('Erreur Discord : ' . htmlspecialchars($user_data['error']));
}

// 3. Préparer les données pour la méthode handleDiscordAuth
$discord_data = [
    'id' => $user_data['id'],
    'username' => $user_data['username'] . (isset($user_data['discriminator']) && $user_data['discriminator'] !== '0' ? '#' . $user_data['discriminator'] : ''),
    'email' => $user_data['email'] ?? '',
    'avatar' => isset($user_data['avatar']) ? 'https://cdn.discordapp.com/avatars/' . $user_data['id'] . '/' . $user_data['avatar'] . '.png' : null
];

// 4. Authentifier ou inscrire l'utilisateur
$auth = new Auth($pdo);
$result = $auth->handleDiscordAuth($discord_data);

if ($result['success']) {
    // Redirection vers la page principale avec un message de succès
    header('Location: index.php?auth=success&provider=discord');
    exit;
} else {
    // En cas d'erreur, afficher un message d'erreur
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur d\'authentification Discord</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #e74c3c; background: #fdf2f2; padding: 20px; border-radius: 8px; margin: 20px; }
            .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Erreur d\'authentification</h1>
        <div class="error">
            <p>Erreur lors de la connexion avec Discord : ' . htmlspecialchars($result['message']) . '</p>
        </div>
        <a href="index.php" class="btn">Retour à l\'accueil</a>
    </body>
    </html>';
}
?> 