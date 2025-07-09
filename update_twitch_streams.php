<?php
// Script de mise à jour automatique des streams Twitch
// À exécuter toutes les 5 minutes via un cron job
// 
// Exemple de cron job :
// */5 * * * * php /path/to/your/project/update_twitch_streams.php

require_once 'config.php';
require_once 'classes/Twitch.php';

// Vérifier le secret pour la sécurité
$secret = $_GET['secret'] ?? $_ENV['TWITCH_UPDATE_SECRET'] ?? null;
if (!$secret || $secret !== TWITCH_UPDATE_SECRET) {
    http_response_code(403);
    echo "Accès refusé\n";
    exit;
}

try {
    $twitch = new Twitch($pdo);
    
    // Récupérer tous les comptes Twitch actifs
    $stmt = $pdo->prepare("
        SELECT twitch_username FROM twitch_accounts 
        WHERE is_active = TRUE
    ");
    $stmt->execute();
    $usernames = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($usernames)) {
        echo "Aucun compte Twitch actif trouvé\n";
        exit;
    }
    
    echo "Mise à jour des streams pour " . count($usernames) . " comptes Twitch...\n";
    
    // Récupérer les streams en direct
    $streams = $twitch->getStreamsByUsernames($usernames);
    
    // Mettre à jour le cache
    $twitch->updateStreamsCache($streams);
    
    echo "Mise à jour terminée. " . count($streams) . " streams en direct trouvés.\n";
    
} catch (Exception $e) {
    echo "Erreur lors de la mise à jour des streams: " . $e->getMessage() . "\n";
    http_response_code(500);
    exit;
}
?> 