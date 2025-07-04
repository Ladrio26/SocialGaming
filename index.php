<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/UserDisplay.php';



$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

// Messages d'authentification
$auth_message = '';
$auth_message_type = 'success';

if (isset($_GET['auth_success'])) {
    switch ($_GET['auth_success']) {
        case 'discord':
            $auth_message = 'Connexion avec Discord réussie !';
            break;
        case 'steam':
            $auth_message = 'Connexion avec Steam réussie !';
            break;
    }
} elseif (isset($_GET['auth_error'])) {
    $auth_message = htmlspecialchars($_GET['auth_error']);
    $auth_message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MonSite - Authentification</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php if ($user): ?>
            <!-- Interface utilisateur connecté -->
            <div class="dashboard">
                <!-- Header compact -->
                <div class="compact-header">
                    <div class="header-left">
                        <div class="search-mini">
                            <input type="text" id="searchInputMini" placeholder="Rechercher un utilisateur..." minlength="1">
                            <button id="searchBtnMini" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <!-- Résultats de recherche mini -->
                            <div id="searchResultsMini" class="search-results-mini"></div>
                        </div>
                    </div>
                    
                    <div class="header-center">
                        <h2 class="user-display-name"><?php echo UserDisplay::formatDisplayName($user); ?></h2>
                    </div>
                    
                    <div class="header-right">
                        <button id="friendsBtn" class="btn btn-sm btn-secondary">
                            <i class="fas fa-users"></i> Amis
                        </button>
                        <div class="notification-container">
                            <button id="notificationsBtn" class="btn btn-sm btn-secondary notification-btn">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                            </button>
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notification-header">
                                    <h4>Notifications</h4>
                                    <div style="display: flex; gap: 8px;">
                                        <button id="markAllReadBtn" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-check-double"></i> Tout marquer comme lu
                                        </button>
                                        <button id="deleteAllBtn" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Tout supprimer
                                        </button>
                                    </div>
                                </div>
                                <div class="notification-list" id="notificationList">
                                    <div class="notification-loading">
                                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                                    </div>
                                </div>
                                <div class="notification-footer">
                                    <a href="#" id="viewAllNotifications">Voir toutes les notifications</a>
                                </div>
                            </div>
                        </div>
                        <button id="profileBtn" class="btn btn-sm btn-secondary profile-avatar-btn">
                            <?php if ($user['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar" class="header-avatar">
                            <?php else: ?>
                                <div class="header-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </button>
                        <button id="logoutBtn" class="btn btn-sm btn-danger">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Message de bienvenue temporaire -->
                <div class="welcome-message-temp" id="welcomeMessage">
                    <p>🎉 Bienvenue ! Vous êtes connecté.</p>
                </div>
                
                <?php
                // Vérifier si l'utilisateur a un compte Steam lié
                $stmt = $pdo->prepare("SELECT steam_id FROM steam_accounts WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $steam_id = $stmt->fetchColumn();
                
                if ($steam_id): ?>
                <!-- Section Steam -->
                <div class="steam-section">
                    <h3>🎮 Communauté Steam</h3>
                    <div class="steam-container">
                        <p>Découvrez les membres de la communauté qui partagent vos jeux Steam !</p>
                        <a href="common_games.php" class="btn btn-primary btn-large">
                            <i class="fas fa-users"></i> Voir les joueurs avec jeux en commun
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Interface d'authentification -->
            <div class="auth-container">
                <div class="auth-header">
                    <h1>Bienvenue sur MonSite</h1>
                    <p>Connectez-vous ou créez votre compte</p>
                </div>
                
                <div class="auth-tabs">
                    <button class="tab-btn active" data-tab="login">Connexion</button>
                    <button class="tab-btn" data-tab="register">Inscription</button>
                </div>
                
                <!-- Formulaire de connexion -->
                <div class="auth-form active" id="login-form">
                    <form id="loginForm">
                        <div class="form-group">
                            <label for="loginEmail">Email</label>
                            <input type="email" id="loginEmail" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Mot de passe</label>
                            <input type="password" id="loginPassword" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </button>
                    </form>
                </div>
                
                <!-- Formulaire d'inscription -->
                <div class="auth-form" id="register-form">
                    <form id="registerForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="registerFirstName">Prénom</label>
                                <input type="text" id="registerFirstName" name="first_name" minlength="2" maxlength="30">
                            </div>
                            <div class="form-group">
                                <label for="registerLastName">Nom</label>
                                <input type="text" id="registerLastName" name="last_name" minlength="2" maxlength="30">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="registerUsername">Pseudo (optionnel si nom/prénom fournis)</label>
                            <input type="text" id="registerUsername" name="username" pattern="[a-zA-Z0-9_-]{3,20}" title="3-20 caractères, lettres, chiffres, tirets et underscores uniquement">
                        </div>
                        <div class="form-group">
                            <label for="registerEmail">Email</label>
                            <input type="email" id="registerEmail" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="registerPassword">Mot de passe</label>
                            <input type="password" id="registerPassword" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="registerPasswordConfirm">Confirmer le mot de passe</label>
                            <input type="password" id="registerPasswordConfirm" name="password_confirm" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-user-plus"></i> S'inscrire
                        </button>
                    </form>
                </div>
                
                <!-- Séparateur -->
                <div class="separator">
                    <span>ou</span>
                </div>
                
                <!-- Boutons d'authentification sociale -->
                <div class="social-auth">
                    <button class="btn btn-social btn-discord" id="discordBtn">
                        <i class="fab fa-discord"></i> Continuer avec Discord
                    </button>
                    <button class="btn btn-social btn-steam" id="steamBtn">
                        <i class="fab fa-steam"></i> Continuer avec Steam
                    </button>
                </div>
                
                <!-- Messages d'erreur/succès -->
                <div id="message" class="message">
                    <?php if ($auth_message): ?>
                        <div class="message <?php echo $auth_message_type; ?>"><?php echo $auth_message; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/search.js"></script>
    <script src="assets/js/friends.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>
</html> 