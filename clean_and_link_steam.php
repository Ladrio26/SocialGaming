<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Steam.php';
require_once 'config_steam_oauth.php';

echo "<h2>Nettoyage et liaison Steam</h2>";

// 1. Nettoyer tous les liens Steam existants
echo "<h3>1. Nettoyage des liens Steam existants</h3>";
$stmt = $pdo->prepare("DELETE FROM steam_accounts");
$stmt->execute();
$deleted_links = $stmt->rowCount();
echo "<p>✅ $deleted_links liens Steam supprimés</p>";

// 2. Nettoyer les profils Steam
echo "<h3>2. Nettoyage des profils Steam</h3>";
$stmt = $pdo->prepare("DELETE FROM steam_profiles");
$stmt->execute();
$deleted_profiles = $stmt->rowCount();
echo "<p>✅ $deleted_profiles profils Steam supprimés</p>";

// 3. Nettoyer les jeux Steam
echo "<h3>3. Nettoyage des jeux Steam</h3>";
$stmt = $pdo->prepare("DELETE FROM steam_games");
$stmt->execute();
$deleted_games = $stmt->rowCount();
echo "<p>✅ $deleted_games jeux Steam supprimés</p>";

// 4. Nettoyer les codes de vérification Steam
echo "<h3>4. Nettoyage des codes de vérification</h3>";
$stmt = $pdo->prepare("DELETE FROM steam_verification_codes");
$stmt->execute();
$deleted_codes = $stmt->rowCount();
echo "<p>✅ $deleted_codes codes de vérification supprimés</p>";

// 5. Supprimer les utilisateurs Steam de test
echo "<h3>5. Suppression des utilisateurs Steam de test</h3>";
$stmt = $pdo->prepare("DELETE FROM users WHERE auth_provider = 'steam'");
$stmt->execute();
$deleted_users = $stmt->rowCount();
echo "<p>✅ $deleted_users utilisateurs Steam supprimés</p>";

// 6. Vérifier votre compte Discord
echo "<h3>6. Vérification de votre compte Discord</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 2");
$stmt->execute();
$discord_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($discord_user) {
    echo "<p>✅ Compte Discord trouvé : {$discord_user['username']} (ID: {$discord_user['id']})</p>";
} else {
    echo "<p>❌ Compte Discord non trouvé</p>";
    exit;
}

// 7. État final de la base de données
echo "<h3>7. État final de la base de données</h3>";

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM steam_accounts");
$stmt->execute();
$steam_accounts_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM steam_profiles");
$stmt->execute();
$steam_profiles_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM steam_games");
$stmt->execute();
$steam_games_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE auth_provider = 'steam'");
$stmt->execute();
$steam_users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "<p>Liens Steam : $steam_accounts_count</p>";
echo "<p>Profils Steam : $steam_profiles_count</p>";
echo "<p>Jeux Steam : $steam_games_count</p>";
echo "<p>Utilisateurs Steam : $steam_users_count</p>";

echo "<h3>8. Instructions pour lier votre vrai compte Steam</h3>";
echo "<p>✅ La base de données a été nettoyée avec succès !</p>";
echo "<p>Maintenant, pour lier votre vrai compte Steam :</p>";
echo "<ol>";
echo "<li>Allez sur <a href='https://ladrio2.goodloss.fr' target='_blank'>votre site</a></li>";
echo "<li>Assurez-vous d'être connecté avec Discord</li>";
echo "<li>Cliquez sur 'Continuer avec Steam'</li>";
echo "<li>Connectez-vous avec votre vrai compte Steam</li>";
echo "<li>Votre compte Discord sera automatiquement lié à votre vrai compte Steam</li>";
echo "</ol>";

echo "<p><strong>Note :</strong> Assurez-vous que votre profil Steam est public pour que l'API puisse récupérer vos informations de jeux.</p>";

// 9. Test de la classe Steam
echo "<h3>9. Test de la classe Steam</h3>";
$steam = new Steam($pdo, $steam_api_key);
echo "<p>Clé API dans la classe : " . (isset($steam->api_key) ? "✅ Définie" : "❌ Non définie") . "</p>";

if (isset($steam->api_key)) {
    echo "<p>✅ La classe Steam est prête à fonctionner</p>";
} else {
    echo "<p>❌ Problème avec la classe Steam</p>";
}
?> 