<?php
require_once 'config.php';
require_once 'config_steam.php';
require_once 'classes/Steam.php';
require_once 'classes/Auth.php';
require_once 'includes/RoleManager.php';

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

// Initialisation du gestionnaire de rôles
$roleManager = new RoleManager($pdo);

// Rediriger vers la page d'accueil si non connecté
if (!$user) {
    echo "<p>Vous devez être connecté pour voir cette page.</p>";
    exit;
}

// Vérification du bannissement
if ($roleManager->isBanned($user['id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $user['id'];
$steam = new Steam($pdo, $steam_api_key);

// Récupérer le Steam ID de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT steam_id FROM steam_accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$my_steam_id = $stmt->fetchColumn();

if (!$my_steam_id) {
    echo "<p>Vous n'avez pas lié votre compte Steam.</p>";
    exit;
}

// Récupérer la liste des jeux de l'utilisateur
$stmt = $pdo->prepare("SELECT app_id FROM steam_games WHERE steam_id = ?");
$stmt->execute([$my_steam_id]);
$my_games = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($my_games)) {
    echo "<p>Aucun jeu trouvé sur votre compte Steam.</p>";
    exit;
}

// Trouver les autres utilisateurs ayant au moins un jeu en commun
$placeholders = implode(',', array_fill(0, count($my_games), '?'));
$sql = "
    SELECT sa.user_id, sa.steam_id, u.username AS pseudo, sp.avatar, COUNT(*) AS nb_jeux_communs
    FROM steam_games sg
    JOIN steam_accounts sa ON sg.steam_id = sa.steam_id
    JOIN users u ON sa.user_id = u.id
    LEFT JOIN steam_profiles sp ON sa.steam_id = sp.steam_id
    WHERE sg.app_id IN ($placeholders)
      AND sa.user_id != ?
    GROUP BY sa.user_id, sa.steam_id, u.username, sp.avatar
    ORDER BY nb_jeux_communs DESC, u.username ASC
";
$params = array_merge($my_games, [$user_id]);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Affichage HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeux en commun avec la communauté - Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header compact */
        .page-header {
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .home-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .home-btn:hover {
            background: var(--primary-hover);
            text-decoration: none;
            color: white;
        }
        
        .header-center {
            flex: 1;
            text-align: center;
            margin: 0 20px;
        }
        
        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .header-right {
            width: 60px; /* Même largeur que le bouton accueil pour centrer */
        }
        
        /* Zone de contenu principal */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--bg-color);
        }
        
        .games-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .games-content h1 {
            color: var(--text-color);
            margin-bottom: 30px;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 8px 15px;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .content-area {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header compact -->
    <div class="page-header">
        <div class="header-left">
            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i> Accueil
            </a>
        </div>
        
        <div class="header-center">
            <h1 class="page-title">Jeux en commun avec la communauté</h1>
        </div>
        
        <div class="header-right"></div>
    </div>
    
    <!-- Zone de contenu principal -->
    <div class="content-area">
        <div class="games-content">
            <?php if (empty($users)) { ?>
                <div class="empty-state">
                    <i class="fas fa-gamepad"></i>
                    <p>Aucun autre membre ne possède de jeux en commun avec vous pour l'instant.</p>
                </div>
            <?php } else { ?>
            <div class="games-grid">
                <?php foreach ($users as $user) {
                    // Récupérer la liste des jeux partagés avec cet utilisateur
                    $sql2 = "SELECT name, playtime_forever FROM steam_games WHERE steam_id = ? AND app_id IN ($placeholders) ORDER BY playtime_forever DESC LIMIT 5";
                    $params2 = array_merge([$user['steam_id']], $my_games);
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute($params2);
                    $shared_games = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="game-user-card">
                    <div class="game-user-avatar">
                        <img src="<?= htmlspecialchars($user['avatar'] ?? '') ?>" alt="Avatar Steam">
                    </div>
                    <div class="game-user-info">
                        <div class="game-user-name"><?= htmlspecialchars($user['pseudo']) ?></div>
                        <div class="game-user-stats"><?= $user['nb_jeux_communs'] ?> jeu<?= $user['nb_jeux_communs'] > 1 ? 'x' : '' ?> en commun</div>
                        <ul class="shared-games-list">
                            <?php foreach ($shared_games as $game) {
                                $hours = round($game['playtime_forever'] / 60, 1);
                                echo "<li>" . htmlspecialchars($game['name']) . " <span class='game-hours'>({$hours}h)</span></li>";
                            } ?>
                        </ul>
                    </div>
                    <div class="game-user-actions">
                        <a href="profile.php?user_id=<?= $user['user_id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-user"></i> Voir le profil
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>
    
    <script src="assets/js/date-utils.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>
</html> 