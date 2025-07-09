<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/UserDisplay.php';
require_once 'classes/Twitch.php';
require_once 'includes/RoleManager.php';



$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

// Initialisation du gestionnaire de rôles
$roleManager = new RoleManager($pdo);

// Vérification si l'utilisateur est banni
$isBanned = false;
if ($user) {
    $isBanned = $roleManager->isBanned($user['id']);
}

// Messages d'authentification
$auth_message = '';
$auth_message_type = 'success';

if (isset($_GET['auth_success'])) {
    $auth_message = htmlspecialchars($_GET['auth_success']);
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
    <title>Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container home-page">
        <?php if ($user): ?>
            <?php if ($isBanned): ?>
                <!-- Interface utilisateur banni -->
                <div class="banned-container">
                    <div class="banned-message">
                        <div class="banned-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <h1>🚫 Vous êtes banni</h1>
                        <p>Votre compte a été suspendu par l'administration.</p>
                        <p>Vous n'avez plus accès aux fonctionnalités du site.</p>
                        <div class="banned-actions">
                            <button id="logoutBtn" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Se déconnecter
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Interface utilisateur connecté -->
                <div class="dashboard home-dashboard">
                <?php include 'includes/header.php'; ?>
                

                
                <!-- Layout principal avec bandeau Twitch -->
                <div class="home-layout">
                    <!-- Bandeau Twitch à gauche -->
                    <div class="twitch-sidebar">
                        <div class="twitch-sidebar-header">
                            <h3>📺 Streams en direct</h3>
                        </div>
                        <div class="twitch-sidebar-content">
                            <div id="twitchLiveStreams" class="twitch-live-streams" style="display: none;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenu principal à droite -->
                    <div class="main-content">
                        <!-- Section des posts récents avec images -->
                        <div class="recent-posts-section">
                            <div class="recent-posts-header">
                                <h3><i class="fas fa-images"></i> Screenshots récents</h3>
                                <p>Derniers partages d'images de vos amis</p>
                            </div>
                            
                            <div class="recent-posts-content">
                                <!-- État de chargement -->
                                <div id="recentPostsLoading" class="loading-state">
                                    <i class="fas fa-spinner fa-spin"></i> Chargement des posts récents...
                                </div>
                                
                                <!-- État vide -->
                                <div id="recentPostsEmpty" class="empty-state" style="display: none;">
                                    <!-- Contenu dynamique -->
                                </div>
                                
                                <!-- Conteneur des posts -->
                                <div id="recentPostsContainer" class="recent-posts-container" style="display: none;">
                                    <!-- Posts chargés dynamiquement -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Colonne Catégories sticky à droite -->
                    <aside class="categories-sidebar">
                        <div class="categories-sidebar-header">
                            <h3><i class="fas fa-folder"></i> Catégories</h3>
                        </div>
                        <div class="categories-sidebar-content" id="categoriesList">
                            <!-- Les catégories seront chargées ici en JS -->
                        </div>
                    </aside>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Interface d'authentification -->
            <div class="auth-container">
                <div class="auth-header">
                    <h1>Bienvenue sur Social Gaming</h1>
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
                            <label for="loginUsername">Pseudo</label>
                            <input type="text" id="loginUsername" name="username" required>
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
                        <div class="form-group">
                            <label for="registerUsername">Pseudo</label>
                            <input type="text" id="registerUsername" name="username" pattern="[a-zA-Z0-9_-]{3,20}" title="3-20 caractères, lettres, chiffres, tirets et underscores uniquement" required>
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
                    <button class="btn btn-social btn-twitch" id="twitchBtn">
                        <i class="fab fa-twitch"></i> Continuer avec Twitch
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
    
    <script src="assets/js/date-utils.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/search.js"></script>
    <script src="assets/js/friends.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/twitch-live.js"></script>
    <script src="assets/js/categories.js"></script>
    <script src="assets/js/moderation-badge.js"></script>
    <script src="/assets/js/recent-posts.js"></script>
    <script src="/assets/js/realtime-updates.js"></script>
    <script src="/assets/js/global-updates.js"></script>
    <script src="/assets/js/realtime-controls.js"></script>
    <script>
        // Initialiser le gestionnaire de posts récents
        document.addEventListener('DOMContentLoaded', function() {
            recentPostsManager = new RecentPostsManager();
            
            // Configurer les callbacks pour les mises à jour en temps réel
            if (window.realtimeUpdates) {
                window.realtimeUpdates.onUpdate('recent_posts', function(count) {
                    // Recharger les posts récents
                    if (recentPostsManager) {
                        recentPostsManager.loadRecentPosts();
                    }
                });
            }
        });
    </script>
</body>
</html> 