<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/UserDisplay.php';
require_once 'includes/RoleManager.php';

$auth = new Auth($pdo);
$currentUser = $auth->isLoggedIn();

// Initialisation du gestionnaire de rôles
$roleManager = new RoleManager($pdo);

// Rediriger vers la page d'accueil si non connecté
if (!$currentUser) {
    header('Location: index.php');
    exit;
}

// Vérification du bannissement
if ($roleManager->isBanned($currentUser['id'])) {
    header('Location: index.php');
    exit;
}

// Déterminer quel utilisateur consulter
$targetUserId = $currentUser['id']; // Par défaut, l'utilisateur connecté
$isOwnFriends = true;

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $requestedUserId = (int)$_GET['user_id'];
    
    // Vérifier que l'utilisateur demandé existe
    $stmt = $pdo->prepare("SELECT id, username, display_format FROM users WHERE id = ?");
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
    <title>Amis - Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container friends-page">
        <div class="dashboard friends-dashboard">
            <?php include 'includes/header.php'; ?>
            
            <!-- Titre de la page -->
            <div style="text-align: center; margin: 20px 0;">
                <h1><?php echo $isOwnFriends ? '👥 Mes Amis' : '👥 Amis de ' . UserDisplay::formatDisplayName($targetUser); ?></h1>
                <?php if (!$isOwnFriends): ?>
                <a href="profile.php?user_id=<?php echo $targetUserId; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-user"></i> Retour au profil
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Layout en 3 colonnes -->
            <div class="friends-layout">
                <!-- Colonne gauche : Liste d'amis -->
                <div class="friends-column">
                    <div class="friends-section">
                        <h3><?php echo $isOwnFriends ? '👥 Mes amis' : '👥 Amis de ' . UserDisplay::formatDisplayName($targetUser); ?></h3>
                        <div class="friends-container">
                            <div id="friendsList" class="friends-list">
                                <div class="loading">Chargement des amis...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne centrale : Recherche d'amis (seulement pour ses propres amis) -->
                <?php if ($isOwnFriends): ?>
                <div class="search-column">
                    <div class="search-section">
                        <h3>🔍 Rechercher des amis</h3>
                        <div class="search-container">
                            <div class="search-input-group">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="searchInput" placeholder="Rechercher par pseudo..." minlength="1">
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
                    
                    <!-- Section des demandes d'amis -->
                    <div class="requests-section">
                        <h3>📨 Demandes d'amis</h3>
                        <div class="requests-container">
                            <div id="receivedRequests" class="requests-list">
                                <div class="loading">Chargement des demandes...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Colonne droite : Derniers inscrits -->
                <div class="recent-column">
                    <div class="recent-section">
                        <h3>🆕 Derniers inscrits</h3>
                        <div class="recent-container">
                            <div id="recentUsers" class="recent-users-list">
                                <div class="loading">Chargement des derniers inscrits...</div>
                            </div>
                        </div>
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
    <script src="assets/js/date-utils.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/search.js"></script>
    <script src="assets/js/friends.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>
</html> 