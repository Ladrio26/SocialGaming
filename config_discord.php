<?php
// Configuration Discord OAuth2 - Application Ladrio2GLconnect
define('DISCORD_CLIENT_ID', '1390168348205121687');
define('DISCORD_CLIENT_SECRET', 'uMz-0XSET0UyCcjqxeIb1gPvBstEURRn');
define('DISCORD_REDIRECT_URI', 'https://ladrio2.goodloss.fr/oauth2callback_discord.php');

// URL d'autorisation Discord
define('DISCORD_AUTH_URL', 'https://discord.com/api/oauth2/authorize');
define('DISCORD_TOKEN_URL', 'https://discord.com/api/oauth2/token');
define('DISCORD_USER_URL', 'https://discord.com/api/users/@me');

// Scopes requis
define('DISCORD_SCOPES', 'identify email connections');
?> 