<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/UserDisplay.php';
require_once 'classes/Notification.php';

$auth = new Auth($pdo);
$currentUser = $auth->isLoggedIn();

// Rediriger vers la page d'accueil si non connecté
if (!$currentUser) {
    header('Location: index.php');
    exit;
}

// Déterminer quel utilisateur afficher
$targetUserId = null;
$isOwnProfile = true;

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $targetUserId = (int)$_GET['user_id'];
    $isOwnProfile = ($targetUserId === $currentUser['id']);
}

// Récupérer les informations de l'utilisateur cible
if ($isOwnProfile) {
    $targetUser = $currentUser;
} else {
    // Récupérer les informations de l'autre utilisateur
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email, avatar_url, discord_avatar, auth_provider, display_format, created_at FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        // Utilisateur non trouvé, rediriger vers son propre profil
        header('Location: profile.php');
        exit;
    }
    
    // Créer une notification de visite de profil
    $notification = new Notification($pdo);
    $visitor_name = UserDisplay::formatDisplayName($currentUser);
    $notification->createProfileVisit($targetUser['id'], $visitor_name);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - MonSite</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
                    <div class="profile-container">
                <!-- Header principal (toujours celui de l'utilisateur connecté) -->
                <div class="compact-header">
                    <div class="header-left">
                        <a href="index.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Accueil
                        </a>
                    </div>
                    
                    <div class="header-center">
                        <h2><?php echo $isOwnProfile ? 'Mon Profil' : 'Profil de ' . UserDisplay::formatDisplayName($targetUser); ?></h2>
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
                
                <?php if (!$isOwnProfile): ?>
                <!-- Header secondaire pour le profil consulté -->
                <div class="profile-user-header">
                    <div class="profile-user-info">
                        <div class="profile-user-avatar">
                            <?php if ($targetUser['avatar_url']): ?>
                                <img src="<?php echo htmlspecialchars($targetUser['avatar_url']); ?>" alt="Avatar de <?php echo UserDisplay::formatDisplayName($targetUser); ?>">
                            <?php else: ?>
                                <div class="profile-user-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-user-details">
                            <h3><?php echo UserDisplay::formatDisplayName($targetUser); ?></h3>
                            <div class="profile-user-meta">
                                <span class="profile-user-provider">
                                    <?php 
                                    switch($targetUser['auth_provider']) {
                                        case 'manual': echo '<i class="fas fa-user"></i> Inscription manuelle'; break;
                                        case 'discord': echo '<i class="fab fa-discord"></i> Discord'; break;
                                        default: echo '<i class="fas fa-user"></i> ' . htmlspecialchars($targetUser['auth_provider']);
                                    }
                                    ?>
                                </span>
                                <span class="profile-user-join-date">
                                    <i class="fas fa-calendar"></i> Membre depuis <?php echo date('d/m/Y', strtotime($targetUser['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-user-actions">
                        <div class="profile-user-buttons">
                            <button type="button" id="viewGamesBtn" class="btn btn-secondary" data-user-id="<?php echo $targetUser['id']; ?>">
                                <i class="fas fa-gamepad"></i> Jeux
                            </button>
                            <button type="button" id="viewFriendsBtn" class="btn btn-secondary" data-user-id="<?php echo $targetUser['id']; ?>">
                                <i class="fas fa-users"></i> Amis
                            </button>
                            <button type="button" id="addFriendBtn" class="btn btn-primary" data-user-id="<?php echo $targetUser['id']; ?>">
                                <i class="fas fa-user-plus"></i> Ajouter en ami
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            
            <div class="profile-avatar">
                <?php if ($targetUser['avatar_url']): ?>
                    <img src="<?php echo htmlspecialchars($targetUser['avatar_url']); ?>" alt="Avatar" id="currentAvatar">
                <?php else: ?>
                    <div class="avatar-placeholder large" id="currentAvatar">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-content">
                <?php if ($isOwnProfile): ?>
                <!-- Onglets du profil (seulement pour son propre profil) -->
                <div class="profile-tabs">
                    <button class="profile-tab-btn active" data-tab="info">
                        <i class="fas fa-user"></i> Informations
                    </button>
                    <button class="profile-tab-btn" data-tab="avatar">
                        <i class="fas fa-image"></i> Avatar
                    </button>
                    <button class="profile-tab-btn" data-tab="steam">
                        <i class="fab fa-steam"></i> Steam
                    </button>
                    <button class="profile-tab-btn" data-tab="password">
                        <i class="fas fa-lock"></i> Mot de passe
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Formulaire d'informations -->
                <div class="profile-form <?php echo $isOwnProfile ? 'active' : ''; ?>" id="info-form">
                    <?php if ($isOwnProfile): ?>
                    <form id="profileForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="profileFirstName">Prénom</label>
                                <input type="text" id="profileFirstName" name="first_name" minlength="2" maxlength="30" value="<?php echo htmlspecialchars($targetUser['first_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="profileLastName">Nom</label>
                                <input type="text" id="profileLastName" name="last_name" minlength="2" maxlength="30" value="<?php echo htmlspecialchars($targetUser['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profileUsername">Pseudo</label>
                            <input type="text" id="profileUsername" name="username" pattern="[a-zA-Z0-9_-]{3,20}" title="3-20 caractères, lettres, chiffres, tirets et underscores uniquement" value="<?php echo htmlspecialchars($targetUser['username'] ?? ''); ?>">
                            <small>Optionnel si nom et prénom fournis</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="profileEmail">Email</label>
                            <input type="email" id="profileEmail" name="email" required value="<?php echo htmlspecialchars($targetUser['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="profileDisplayFormat">Format d'affichage</label>
                            <select id="profileDisplayFormat" name="display_format">
                                <option value="full_with_pseudo" <?php echo ($targetUser['display_format'] ?? 'full_with_pseudo') === 'full_with_pseudo' ? 'selected' : ''; ?>>
                                    Prénom 'Pseudo' Nom
                                </option>
                                <option value="full_name" <?php echo ($targetUser['display_format'] ?? '') === 'full_name' ? 'selected' : ''; ?>>
                                    Prénom & Nom
                                </option>
                                <option value="first_name_only" <?php echo ($targetUser['display_format'] ?? '') === 'first_name_only' ? 'selected' : ''; ?>>
                                    Juste Prénom
                                </option>
                                <option value="last_name_only" <?php echo ($targetUser['display_format'] ?? '') === 'last_name_only' ? 'selected' : ''; ?>>
                                    Juste Nom
                                </option>
                                <option value="username_only" <?php echo ($targetUser['display_format'] ?? '') === 'username_only' ? 'selected' : ''; ?>>
                                    Juste Pseudo
                                </option>
                            </select>
                            <small>Choisissez comment vous apparaissez auprès des autres utilisateurs</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Méthode d'authentification</label>
                            <div class="auth-provider-display">
                                <?php 
                                switch($targetUser['auth_provider']) {
                                    case 'manual': echo '<span class="badge manual"><i class="fas fa-user"></i> Inscription manuelle</span>'; break;
                                    case 'discord': echo '<span class="badge discord"><i class="fab fa-discord"></i> Discord</span>'; break;
                                    default: echo '<span class="badge">' . htmlspecialchars($targetUser['auth_provider']) . '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Membre depuis</label>
                            <div class="join-date">
                                <?php echo date('d/m/Y', strtotime($targetUser['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Sauvegarder les modifications
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <!-- Affichage en lecture seule pour les autres utilisateurs -->
                    <div class="profile-info-readonly">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Prénom</label>
                                <div class="readonly-value"><?php echo htmlspecialchars($targetUser['first_name'] ?? 'Non renseigné'); ?></div>
                            </div>
                            <div class="form-group">
                                <label>Nom</label>
                                <div class="readonly-value"><?php echo htmlspecialchars($targetUser['last_name'] ?? 'Non renseigné'); ?></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Pseudo</label>
                            <div class="readonly-value"><?php echo htmlspecialchars($targetUser['username'] ?? 'Non renseigné'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Méthode d'authentification</label>
                            <div class="auth-provider-display">
                                <?php 
                                switch($targetUser['auth_provider']) {
                                    case 'manual': echo '<span class="badge manual"><i class="fas fa-user"></i> Inscription manuelle</span>'; break;
                                    case 'discord': echo '<span class="badge discord"><i class="fab fa-discord"></i> Discord</span>'; break;
                                    default: echo '<span class="badge">' . htmlspecialchars($targetUser['auth_provider']) . '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Membre depuis</label>
                            <div class="join-date">
                                <?php echo date('d/m/Y', strtotime($targetUser['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($isOwnProfile): ?>
                <!-- Formulaire de gestion d'avatar -->
                <div class="profile-form" id="avatar-form">
                    <div class="avatar-section">
                        <h3>Gestion de l'avatar</h3>
                        <p>Choisissez votre avatar parmi ceux disponibles depuis vos comptes liés.</p>
                        
                        <div class="avatar-preview">
                            <div class="current-avatar">
                                <h4>Avatar actuel :</h4>
                                <?php if ($targetUser['avatar_url']): ?>
                                    <img src="<?php echo htmlspecialchars($targetUser['avatar_url']); ?>" alt="Avatar actuel" id="avatarPreview">
                                <?php else: ?>
                                    <div class="avatar-placeholder medium" id="avatarPreview">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="available-avatars-section">
                            <h4>Avatars disponibles :</h4>
                            <div class="loading-spinner" id="avatarsLoading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement des avatars...
                            </div>
                            <div id="availableAvatars" class="available-avatars" style="display: none;">
                                <!-- Les avatars disponibles seront chargés ici -->
                            </div>
                            <div id="noAvatarsMessage" class="no-avatars-message" style="display: none;">
                                <p>Aucun avatar disponible depuis vos comptes liés.</p>
                                <p>Liez vos comptes Discord et Steam pour avoir plus d'options d'avatars.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Section Steam -->
                <div class="profile-form <?php echo !$isOwnProfile ? 'active' : ''; ?>" id="steam-form">
                    <div class="steam-section">
                        <h3>Intégration Steam</h3>
                        <?php if ($isOwnProfile): ?>
                        <p>Liez votre compte Steam pour afficher vos jeux, votre code ami et vos statistiques de jeu.</p>
                        <?php else: ?>
                        <p>Informations Steam de <?php echo UserDisplay::formatDisplayName($targetUser); ?></p>
                        <?php endif; ?>
                        
                        <div id="steamStatus" class="steam-status">
                            <div class="loading-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        
                        <?php if ($isOwnProfile): ?>
                        <div id="steamLinkForm" class="steam-link-form" style="display: none;">
                            <div class="steam-not-connected">
                                <div class="steam-notice">
                                    <i class="fab fa-steam"></i>
                                    <h4>Compte Steam non connecté</h4>
                                    <p>Pour lier votre compte Steam et voir vos jeux, vous devez d'abord vous connecter avec Steam.</p>
                                    <p>Si vous vous êtes déjà connecté avec Steam, vos informations de jeu devraient s'afficher automatiquement.</p>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-primary" id="steamConnectBtn">
                                        <i class="fab fa-steam"></i> Se connecter avec Steam
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div id="steamInfo" class="steam-info" style="display: none;">
                            <div class="steam-profile">
                                <div class="steam-avatar">
                                    <img id="steamAvatar" src="" alt="Avatar Steam">
                                </div>
                                <div class="steam-details">
                                    <h4 id="steamUsername"></h4>
                                    <p id="steamRealName"></p>
                                    <div class="steam-friend-code">
                                        <strong>Steam ID :</strong> <span id="steamFriendCode"></span>
                                        <?php if ($isOwnProfile): ?>
                                        <button type="button" class="btn-copy" data-clipboard-target="#steamFriendCode">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isOwnProfile): ?>
                                    <div class="steam-actions">
                                        <button type="button" id="refreshSteamBtn" class="btn btn-secondary">
                                            <i class="fas fa-sync-alt"></i> Actualiser
                                        </button>
                                        <button type="button" id="unlinkSteamBtn" class="btn btn-danger">
                                            <i class="fas fa-unlink"></i> Délier
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="steam-games">
                                <h4><?php echo $isOwnProfile ? 'Mes jeux' : 'Jeux'; ?> (<span id="gamesCount">0</span>)</h4>
                                <div class="games-filter">
                                    <input type="text" id="gamesSearch" placeholder="Rechercher un jeu..." class="form-control">
                                </div>
                                <div id="gamesList" class="games-list">
                                    <!-- Les jeux seront chargés ici -->
                                </div>
                            </div>
                            
                            <?php if ($isOwnProfile): ?>
                            <div class="steam-community">
                                <h4>Communauté</h4>
                                <div class="community-actions">
                                    <a href="common_games.php" class="btn btn-primary">
                                        <i class="fas fa-users"></i> Voir les joueurs avec jeux en commun
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($isOwnProfile): ?>
                <!-- Formulaire de changement de mot de passe -->
                <div class="profile-form" id="password-form">
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="currentPassword">Mot de passe actuel</label>
                            <input type="password" id="currentPassword" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">Nouveau mot de passe</label>
                            <input type="password" id="newPassword" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">
                        </div>
                        
                        <div class="password-requirements">
                            <h4>Exigences du mot de passe :</h4>
                            <ul>
                                <li id="req-length">Au moins 6 caractères</li>
                                <li id="req-match">Les mots de passe correspondent</li>
                            </ul>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Messages d'erreur/succès -->
                <div id="profileMessage" class="message"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Passer les informations PHP au JavaScript
        window.profileData = {
            isOwnProfile: <?php echo $isOwnProfile ? 'true' : 'false'; ?>,
            currentUserId: <?php echo $currentUser['id']; ?>,
            targetUserId: <?php echo $targetUser['id']; ?>
        };
    </script>
    <script src="assets/js/profile.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>
</html> 