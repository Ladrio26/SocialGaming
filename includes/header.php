<?php
// Sécurisation du header : inclusions et vérifications robustes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } else {
        echo '<div style="color:red">Erreur : $pdo non défini et config.php introuvable.</div>';
        return;
    }
}

if (!class_exists('Auth')) {
    if (file_exists(__DIR__ . '/../classes/Auth.php')) {
        require_once __DIR__ . '/../classes/Auth.php';
    } else {
        echo '<div style="color:red">Erreur : classe Auth introuvable.</div>';
        return;
    }
}
if (!class_exists('UserDisplay')) {
    if (file_exists(__DIR__ . '/../classes/UserDisplay.php')) {
        require_once __DIR__ . '/../classes/UserDisplay.php';
    } else {
        echo '<div style="color:red">Erreur : classe UserDisplay introuvable.</div>';
        return;
    }
}
if (!class_exists('RoleManager')) {
    if (file_exists(__DIR__ . '/../includes/RoleManager.php')) {
        require_once __DIR__ . '/../includes/RoleManager.php';
    } else {
        echo '<div style="color:red">Erreur : classe RoleManager introuvable.</div>';
        return;
    }
}

$auth = isset($auth) && $auth instanceof Auth ? $auth : new Auth($pdo);
$user = $auth->isLoggedIn();
$roleManager = isset($roleManager) && $roleManager instanceof RoleManager ? $roleManager : new RoleManager($pdo);

// Compter les propositions en attente pour l'indicateur
$pending_proposals_count = 0;
if ($user && isset($user['id']) && $roleManager->hasPermission($user['id'], 'moderate_categories')) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM category_proposals WHERE status = 'pending'");
    $stmt->execute();
    $pending_proposals_count = $stmt->fetchColumn();
}

// Vérifier si l'utilisateur a un compte Steam lié et des jeux
$has_steam_games = false;
if ($user && isset($user['id'])) {
    $stmt = $pdo->prepare("SELECT steam_id FROM steam_accounts WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $steam_id = $stmt->fetchColumn();
    
    if ($steam_id) {
        // Vérifier s'il y a des jeux Steam
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM steam_games WHERE steam_id = ?");
        $stmt->execute([$steam_id]);
        $has_steam_games = $stmt->fetchColumn() > 0;
    }
}
?>

<header class="compact-header">
    <div class="header-left">
        <a href="index.php" class="btn btn-sm btn-secondary home-btn">
            <i class="fas fa-home"></i> Accueil
        </a>
        <?php if ($user): ?>
        <div class="search-mini">
            <input type="text" id="searchInputMini" placeholder="Rechercher des utilisateurs...">
            <div class="search-results-mini" id="searchResultsMini"></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="header-center">
        <?php if ($user): ?>
            <h2 class="user-display-name"><?php echo UserDisplay::formatDisplayName($user); ?></h2>
        <?php endif; ?>
    </div>
    
    <div class="header-right">
        <?php if ($user): ?>
            <?php if ($roleManager->hasPermission($user['id'], 'access_admin_panel')): ?>
            <a href="admin.php" class="btn btn-sm btn-danger">
                <i class="fas fa-shield-alt"></i> Admin
            </a>
            <?php endif; ?>
            
            <?php if ($roleManager->hasPermission($user['id'], 'moderate_categories')): ?>
            <a href="moderation.php" class="btn btn-sm btn-warning moderation-btn">
                <i class="fas fa-gavel"></i> Modération
                <?php if ($pending_proposals_count > 0): ?>
                    <span class="moderation-badge"><?php echo $pending_proposals_count; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            
            <?php if ($has_steam_games): ?>
            <a href="common_games.php" class="btn btn-sm btn-primary">
                <i class="fab fa-steam"></i> Steam
            </a>
            <?php endif; ?>
            
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
        <?php endif; ?>
    </div>
</header> 