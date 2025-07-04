<?php
require_once 'config.php';
require_once 'config_steam.php';
require_once 'classes/Steam.php';
require_once 'classes/Auth.php';

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

// Rediriger vers la page d'accueil si non connecté
if (!$user) {
    echo "<p>Vous devez être connecté pour voir cette page.</p>";
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
    <title>Jeux en commun avec la communauté</title>
    <style>
        body { font-family: Arial, sans-serif; background: #181c24; color: #eee; }
        h1 { text-align: center; }
        .user-list { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; }
        .user-card {
            background: #23283a;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0006;
            padding: 1.5rem;
            min-width: 260px;
            max-width: 320px;
            margin: 1rem 0;
            text-align: center;
            transition: transform 0.2s;
        }
        .user-card:hover { transform: scale(1.04); }
        .avatar { border-radius: 50%; width: 64px; height: 64px; margin-bottom: 0.5rem; }
        .pseudo { font-size: 1.2em; font-weight: bold; margin-bottom: 0.3em; }
        .nb-jeux { color: #7ec699; font-weight: bold; }
        .shared-games { margin: 0.7em 0 0 0; font-size: 0.97em; }
        .shared-games li { margin-bottom: 0.2em; }
        a { color: #7ec6ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Joueurs avec jeux Steam en commun</h1>
    <?php if (empty($users)) { ?>
        <p style="text-align:center;">Aucun autre membre ne possède de jeux en commun avec vous pour l'instant.</p>
    <?php } else { ?>
    <div class="user-list">
        <?php foreach ($users as $user) {
            // Récupérer la liste des jeux partagés avec cet utilisateur
            $sql2 = "SELECT name, playtime_forever FROM steam_games WHERE steam_id = ? AND app_id IN ($placeholders) ORDER BY playtime_forever DESC LIMIT 5";
            $params2 = array_merge([$user['steam_id']], $my_games);
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute($params2);
            $shared_games = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="user-card">
            <img class="avatar" src="<?= htmlspecialchars($user['avatar'] ?? '') ?>" alt="Avatar Steam">
            <div class="pseudo"><?= htmlspecialchars($user['pseudo']) ?></div>
            <div class="nb-jeux"><?= $user['nb_jeux_communs'] ?> jeu<?= $user['nb_jeux_communs'] > 1 ? 'x' : '' ?> en commun</div>
            <ul class="shared-games">
                <?php foreach ($shared_games as $game) {
                    $hours = round($game['playtime_forever'] / 60, 1);
                    echo "<li>" . htmlspecialchars($game['name']) . " <span style='color:#aaa;'>({$hours}h)</span></li>";
                } ?>
            </ul>
            <a href="profile.php?user_id=<?= $user['user_id'] ?>">Voir le profil</a>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
    <p style="text-align:center;margin-top:2em;"><a href="index.php">← Retour</a></p>
</body>
</html> 