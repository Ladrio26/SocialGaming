<?php
require_once 'config.php';
require_once 'classes/Steam.php';
require_once 'config_steam_oauth.php';

echo "🔧 Correction des Comptes Steam\n\n";

if (!isset($steam_api_key) || empty($steam_api_key)) {
    echo "❌ Erreur: Clé API Steam non configurée dans config_steam_oauth.php\n";
    exit;
}

try {
    // 1. Récupérer tous les utilisateurs avec des comptes Steam
    echo "1. Recherche des utilisateurs avec comptes Steam...\n";
    $stmt = $pdo->query("
        SELECT u.id, u.username, sa.steam_id, sa.linked_at,
               (SELECT COUNT(*) FROM steam_games WHERE steam_id = sa.steam_id) as games_count
        FROM users u 
        JOIN steam_accounts sa ON u.id = sa.user_id
        ORDER BY u.id
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ Aucun utilisateur avec compte Steam trouvé\n";
        exit;
    }
    
    echo "✅ " . count($users) . " utilisateurs trouvés\n\n";
    
    // 2. Analyser chaque utilisateur
    $steam = new Steam($pdo, $steam_api_key);
    $fixed_count = 0;
    $error_count = 0;
    
    foreach ($users as $user) {
        echo "Utilisateur {$user['id']} ({$user['username']}) - Steam ID: {$user['steam_id']}\n";
        echo "  Jeux actuels: {$user['games_count']}\n";
        
        // Vérifier si le Steam ID est valide
        if (!$steam->validateSteamId($user['steam_id'])) {
            echo "  ❌ Steam ID invalide: {$user['steam_id']}\n";
            $error_count++;
            continue;
        }
        
        // Tester l'API Steam pour ce Steam ID
        $profile_url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steam_api_key}&steamids={$user['steam_id']}";
        $profile_data = file_get_contents($profile_url);
        $profile_json = json_decode($profile_data, true);
        
        if (!isset($profile_json['response']['players'][0])) {
            echo "  ❌ Profil Steam introuvable ou API inaccessible\n";
            $error_count++;
            continue;
        }
        
        $player = $profile_json['response']['players'][0];
        echo "  ✅ Profil Steam trouvé: {$player['personaname']}\n";
        
        // Vérifier si le profil est public
        if ($player['communityvisibilitystate'] != 3) {
            echo "  ⚠️  Profil Steam privé - impossible de récupérer les jeux\n";
            $error_count++;
            continue;
        }
        
        // Mettre à jour les informations Steam
        echo "  🔄 Mise à jour des informations Steam...\n";
        $result = $steam->updateSteamInfo($user['id'], $user['steam_id']);
        
        if ($result) {
            // Vérifier le nombre de jeux après mise à jour
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM steam_games WHERE steam_id = ?");
            $stmt->execute([$user['steam_id']]);
            $new_games_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "  ✅ Mise à jour réussie - {$new_games_count} jeux trouvés\n";
            $fixed_count++;
        } else {
            echo "  ❌ Échec de la mise à jour\n";
            $error_count++;
        }
        
        echo "\n";
        
        // Pause pour éviter de surcharger l'API Steam
        sleep(1);
    }
    
    // 3. Résumé
    echo "📊 Résumé de la correction:\n";
    echo "  ✅ Utilisateurs corrigés: $fixed_count\n";
    echo "  ❌ Erreurs: $error_count\n";
    echo "  📈 Total traité: " . count($users) . "\n\n";
    
    // 4. Statistiques finales
    echo "📈 Statistiques finales:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT sa.user_id) as users_with_steam,
            COUNT(sg.id) as total_games,
            AVG(games_per_user.count) as avg_games_per_user
        FROM steam_accounts sa
        LEFT JOIN steam_games sg ON sa.steam_id = sg.steam_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as count
            FROM steam_accounts sa2
            JOIN steam_games sg2 ON sa2.steam_id = sg2.steam_id
            GROUP BY user_id
        ) games_per_user ON sa.user_id = games_per_user.user_id
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  👥 Utilisateurs avec Steam: {$stats['users_with_steam']}\n";
    echo "  🎮 Total de jeux: {$stats['total_games']}\n";
    echo "  📊 Moyenne de jeux par utilisateur: " . round($stats['avg_games_per_user'], 1) . "\n\n";
    
    echo "🎉 Correction terminée !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la correction: " . $e->getMessage() . "\n";
}
?> 