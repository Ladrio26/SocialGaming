<?php
// Configuration pour deux applications Discord séparées

// Application 1 : Connexion (Ladrio2GLconnect)
define('DISCORD_LOGIN_CLIENT_ID', '1390168348205121687');
define('DISCORD_LOGIN_CLIENT_SECRET', 'uMz-0XSET0UyCcjqxeIb1gPvBstEURRn');
define('DISCORD_LOGIN_REDIRECT_URI', 'https://ladrio2.goodloss.fr/oauth2callback_discord.php');

// Application 2 : Liaison (Ladrio2GLlink)
define('DISCORD_LINK_CLIENT_ID', '1391204717467668550');
define('DISCORD_LINK_CLIENT_SECRET', 'QQ6K3ZNEczmclzAaN6qHgI6NG0_Hp5IY');
define('DISCORD_LINK_REDIRECT_URI', 'https://ladrio2.goodloss.fr/oauth2callback_discord_link.php');

// URLs communes
define('DISCORD_TOKEN_URL', 'https://discord.com/api/oauth2/token');
define('DISCORD_USER_URL', 'https://discord.com/api/users/@me');

// Scopes
define('DISCORD_LOGIN_SCOPES', 'identify email');
define('DISCORD_LINK_SCOPES', 'identify email connections');

// Configuration par défaut (pour compatibilité)
define('DISCORD_CLIENT_ID', DISCORD_LOGIN_CLIENT_ID);
define('DISCORD_CLIENT_SECRET', DISCORD_LOGIN_CLIENT_SECRET);
define('DISCORD_REDIRECT_URI', DISCORD_LOGIN_REDIRECT_URI);
define('DISCORD_SCOPES', DISCORD_LOGIN_SCOPES);
?> 