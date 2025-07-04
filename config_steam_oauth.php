<?php
// Configuration OAuth2 Steam
// Obtenez votre clé API sur : https://steamcommunity.com/dev/apikey

// Clé API Steam
$steam_api_key = 'CA11C5E6C51B7CF1702E274DB4BE5664';

// URL de base Steam
$steam_base_url = 'https://steamcommunity.com';

// URL de redirection après authentification
$steam_redirect_uri = 'https://ladrio2.goodloss.fr/oauth2callback_steam.php';

// URL d'authentification Steam
$steam_auth_url = 'https://steamcommunity.com/openid/login';

// Paramètres OpenID pour Steam
$steam_openid_params = [
    'openid.ns' => 'http://specs.openid.net/auth/2.0',
    'openid.mode' => 'checkid_setup',
    'openid.return_to' => $steam_redirect_uri,
    'openid.realm' => 'https://ladrio2.goodloss.fr',
    'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
    'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
];
?> 