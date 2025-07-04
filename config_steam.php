<?php
// Configuration de l'API Steam
// Obtenez votre clé API sur : https://steamcommunity.com/dev/apikey

// Clé API Steam (optionnelle - certaines fonctionnalités fonctionnent sans)
$steam_api_key = 'CA11C5E6C51B7CF1702E274DB4BE5664'; // Clé API Steam

// URL de base de l'API Steam
$steam_api_base_url = 'http://api.steampowered.com';

// Configuration des limites de requêtes
$steam_rate_limit = [
    'requests_per_minute' => 10,
    'requests_per_hour' => 100
];

// Configuration du cache
$steam_cache_duration = 3600; // 1 heure en secondes
?> 