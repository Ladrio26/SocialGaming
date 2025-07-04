<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/UserDisplay.php';

$auth = new Auth($pdo);
$currentUser = $auth->isLoggedIn();

// Rediriger vers la page d'accueil si non connecté
if (!$currentUser) {
    header('Location: index.php');
    exit;
}

// Déterminer quel utilisateur consulter
$targetUserId = $currentUser['id']; // Par défaut, l'utilisateur connecté
$isOwnFriends = true;

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $requestedUserId = (int)$_GET['user_id'];
    
    // Vérifier que l'utilisateur demandé existe
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, username, display_format FROM users WHERE id = ?");
    $stmt->execute([$requestedUserId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($targetUser) {
        $targetUserId = $requestedUserId;
        $isOwnFriends = ($targetUserId === $currentUser['id']);
    } else {
        // Utilisateur non trouvé, rediriger vers ses propres amis
        header('Location: friends.php');
        exit;
    }
} else {
    $targetUser = $currentUser;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Amis - MonSite</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <!-- Header compact -->
            <div class="compact-header">
                <div class="header-left">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Accueil
                    </a>
                </div>
                
                <div class="header-center">
                    <h2><?php echo $isOwnFriends ? '👥 Mes Amis' : '👥 Amis de ' . UserDisplay::formatDisplayName($targetUser); ?></h2>
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
                                            <i class="fas fa-check-double"></i> <span>Tout lu</span>
                                        </button>
                                        <button id="deleteAllBtn" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> <span>Tout suppr.</span>
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
                            <?php if ($currentUser['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar_url']); ?>" alt="Avatar" class="header-avatar">
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
            
            <?php if ($isOwnFriends): ?>
            <!-- Section de recherche d'amis (seulement pour ses propres amis) -->
            <div class="search-section">
                <h3>🔍 Rechercher des amis</h3>
                <div class="search-container">
                    <div class="search-input-group">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Rechercher par pseudo, prénom ou nom..." minlength="1">
                            <div class="search-loading" id="searchLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <button id="searchBtn" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                    <div class="search-tips">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Tapez au moins 1 caractère pour commencer la recherche
                        </small>
                    </div>
                    <div id="searchResults" class="search-results"></div>
                </div>
            </div>
            
            <!-- Section des demandes d'amis (seulement pour ses propres amis) -->
            <div class="requests-section">
                <h3>📨 Demandes d'amis</h3>
                <div class="requests-container">
                    <div id="receivedRequests" class="requests-list">
                        <div class="loading">Chargement des demandes...</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Section des amis -->
            <div class="friends-section">
                <h3><?php echo $isOwnFriends ? '👥 Mes amis' : '👥 Amis de ' . UserDisplay::formatDisplayName($targetUser); ?></h3>
                <div class="friends-container">
                    <div id="friendsList" class="friends-list">
                        <div class="loading">Chargement des amis...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Passer les informations PHP au JavaScript
        window.friendsData = {
            isOwnFriends: <?php echo $isOwnFriends ? 'true' : 'false'; ?>,
            currentUserId: <?php echo $currentUser['id']; ?>,
            targetUserId: <?php echo $targetUserId; ?>
        };
    </script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/search.js"></script>
    <script src="assets/js/friends.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>
</html> 