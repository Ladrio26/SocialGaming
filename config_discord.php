<?php
// Configuration Discord OAuth2
define('DISCORD_CLIENT_ID', '1254392492481318944');
define('DISCORD_CLIENT_SECRET', 'Mfl9MPRQLDxTDmAhyDxtPOydnKC9zJ9i');
define('DISCORD_REDIRECT_URI', 'https://ladrio2.goodloss.fr/oauth2callback_discord.php');

// URL d'autorisation Discord
define('DISCORD_AUTH_URL', 'https://discord.com/api/oauth2/authorize');
define('DISCORD_TOKEN_URL', 'https://discord.com/api/oauth2/token');
define('DISCORD_USER_URL', 'https://discord.com/api/users/@me');

// Scopes requis
define('DISCORD_SCOPES', 'identify email');
?> 