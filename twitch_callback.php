<?php
require_once 'config.php';
require_once 'classes/Auth.php';

session_start();

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

// Déterminer quel callback utiliser selon le contexte
if ($user) {
    // Utilisateur connecté = liaison de compte
    $callback_url = 'oauth2callback_twitch.php';
} else {
    // Utilisateur non connecté = authentification
    $callback_url = 'oauth2callback_twitch_auth.php';
}

// Rediriger vers le bon callback avec tous les paramètres
$params = $_GET;
$redirect_url = $callback_url . '?' . http_build_query($params);

header('Location: ' . $redirect_url);
exit;
?> 