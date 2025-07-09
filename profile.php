<?php
require_once 'config.php';
require_once 'includes/date_utils.php';
require_once 'classes/Auth.php';
require_once 'classes/UserDisplay.php';
require_once 'classes/Notification.php';
require_once 'classes/Twitch.php';
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
    $stmt = $pdo->prepare("SELECT id, username, email, avatar_url, discord_avatar, auth_provider, display_format, profile_visibility, created_at FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        // Utilisateur non trouvé, rediriger vers son propre profil
        header('Location: profile.php');
        exit;
    }
    
    // Vérifier si l'utilisateur connecté peut voir les informations privées
    $canViewPrivateInfo = false;
    if ($targetUser['profile_visibility'] === 'public') {
        $canViewPrivateInfo = true;
    } else {
        // Vérifier si les utilisateurs sont amis
        $stmt = $pdo->prepare("SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$currentUser['id'], $targetUserId, $targetUserId, $currentUser['id']]);
        $canViewPrivateInfo = $stmt->fetch() !== false;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container profile-page">
                    <div class="profile-container profile-dashboard">
                <?php include 'includes/header.php'; ?>
                
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
                                    <i class="fas fa-calendar"></i> Membre depuis <?php echo formatDateLong($targetUser['created_at']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-user-actions">
                        <div class="profile-user-buttons">
                            <?php if ($canViewPrivateInfo): ?>
                            <button type="button" id="viewGamesBtn" class="btn btn-secondary" data-user-id="<?php echo $targetUser['id']; ?>">
                                <i class="fas fa-gamepad"></i> Jeux
                            </button>
                            <button type="button" id="viewFriendsBtn" class="btn btn-secondary" data-user-id="<?php echo $targetUser['id']; ?>">
                                <i class="fas fa-users"></i> Amis
                            </button>
                            <?php endif; ?>
                            <button type="button" id="addFriendBtn" class="btn btn-primary" data-user-id="<?php echo $targetUser['id']; ?>">
                                <i class="fas fa-user-plus"></i> Ajouter en ami
                            </button>
                            
                            <?php if ($roleManager->hasPermission($currentUser['id'], 'delete_avatars') || $roleManager->hasPermission($currentUser['id'], 'edit_usernames')): ?>
                            <div class="moderation-actions" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                                <h4 style="margin: 0 0 10px 0; color: var(--danger-color); font-size: 0.9rem;">
                                    <i class="fas fa-shield-alt"></i> Actions de modération
                                </h4>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <?php if ($roleManager->hasPermission($currentUser['id'], 'edit_usernames')): ?>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editUsername(<?php echo $targetUser['id']; ?>, '<?php echo htmlspecialchars($targetUser['username']); ?>')">
                                        <i class="fas fa-edit"></i> Modifier pseudo
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($roleManager->hasPermission($currentUser['id'], 'delete_avatars') && $targetUser['avatar_url']): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteAvatar(<?php echo $targetUser['id']; ?>)">
                                        <i class="fas fa-trash"></i> Supprimer avatar
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
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
                    <button class="profile-tab-btn" data-tab="discord">
                        <i class="fab fa-discord"></i> Discord
                    </button>
                    <button class="profile-tab-btn" data-tab="steam">
                        <i class="fab fa-steam"></i> Steam
                    </button>
                    <button class="profile-tab-btn" data-tab="twitch">
                        <i class="fab fa-twitch"></i> Twitch
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
                        <div class="form-group">
                            <label for="profileUsername">Pseudo</label>
                            <input type="text" id="profileUsername" name="username" pattern="[a-zA-Z0-9_-]{3,20}" title="3-20 caractères, lettres, chiffres, tirets et underscores uniquement" value="<?php echo htmlspecialchars($targetUser['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profileEmail">Email</label>
                            <input type="email" id="profileEmail" name="email" required value="<?php echo htmlspecialchars($targetUser['email']); ?>">
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
                                <?php echo formatDateLong($targetUser['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profileVisibility">Visibilité du profil</label>
                            <select id="profileVisibility" name="profile_visibility" required>
                                <option value="public" <?php echo ($targetUser['profile_visibility'] ?? 'private') === 'public' ? 'selected' : ''; ?>>
                                    <i class="fas fa-globe"></i> Public - Tout le monde peut voir mes informations
                                </option>
                                <option value="private" <?php echo ($targetUser['profile_visibility'] ?? 'private') === 'private' ? 'selected' : ''; ?>>
                                    <i class="fas fa-lock"></i> Privé - Seuls mes amis peuvent voir mes informations
                                </option>
                            </select>
                            <small class="form-help">Choisissez qui peut voir vos informations détaillées (Steam, Twitch, etc.)</small>
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
                        <div class="form-group">
                            <label>Pseudo</label>
                            <div class="readonly-value"><?php echo htmlspecialchars($targetUser['username'] ?? 'Non renseigné'); ?></div>
                        </div>
                        
                        <?php if ($canViewPrivateInfo): ?>
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
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Membre depuis</label>
                            <div class="join-date">
                                <?php echo formatDateLong($targetUser['created_at']); ?>
                            </div>
                        </div>
                        
                        <?php if (!$canViewPrivateInfo): ?>
                        <div class="profile-privacy-notice">
                            <div class="privacy-message">
                                <i class="fas fa-lock"></i>
                                <p>Ce profil est privé. Seuls les amis peuvent voir les informations détaillées.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($isOwnProfile): ?>
                <!-- Formulaire de gestion d'avatar -->
                <div class="profile-form" id="avatar-form">
                    <div class="avatar-section">
                        <h3>Gestion de l'avatar</h3>
                        <p>Choisissez votre avatar parmi ceux disponibles depuis vos comptes liés ou uploadez votre propre image.</p>
                        
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
                        
                        <!-- Section upload d'avatar -->
                        <div class="avatar-upload-section">
                            <h4>Uploader un avatar :</h4>
                            <form id="avatarUploadForm" enctype="multipart/form-data">
                                <div class="avatar-upload-preview" id="avatarUploadPreview" style="display: none;">
                                    <img id="uploadPreviewImg" src="" alt="Aperçu">
                                </div>
                                <div class="avatar-upload-controls">
                                    <label class="upload-label">
                                        <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
                                        <i class="fas fa-upload"></i> Choisir une image
                                    </label>
                                    <span class="upload-info">ou collez une image (Ctrl+V)</span>
                                    <button type="submit" id="uploadAvatarBtn" class="btn btn-primary" style="display: none;">
                                        <i class="fas fa-save"></i> Sauvegarder l'avatar
                                    </button>
                                </div>
                                <div class="upload-requirements">
                                    <small>Formats acceptés : JPG, PNG, GIF, WebP - Maximum 5 Mo</small>
                                </div>
                            </form>
                        </div>
                        
                        <div class="available-avatars-section">
                            <h4>Avatars disponibles depuis vos comptes :</h4>
                            <div class="loading-spinner" id="avatarsLoading">
                                <i class="fas fa-spinner fa-spin"></i> Chargement des avatars...
                            </div>
                            <div id="availableAvatars" class="available-avatars" style="display: none;">
                                <!-- Les avatars disponibles seront chargés ici -->
                            </div>
                            <div id="noAvatarsMessage" class="no-avatars-message" style="display: none;">
                                <p>Aucun avatar disponible depuis vos comptes liés.</p>
                                <p>Liez vos comptes Discord, Steam et Twitch pour avoir plus d'options d'avatars.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Section Steam -->
                <div class="profile-form <?php echo (!$isOwnProfile && $canViewPrivateInfo) ? 'active' : ''; ?>" id="steam-form">
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
                
                <!-- Section Twitch -->
                <div class="profile-form <?php echo (!$isOwnProfile && $canViewPrivateInfo) ? 'active' : ''; ?>" id="twitch-form">
                    <div class="twitch-section">
                        <h3>Intégration Twitch</h3>
                        <?php if ($isOwnProfile): ?>
                        <p>Liez votre compte Twitch pour afficher vos streams et suivre les streams de vos amis.</p>
                        <?php else: ?>
                        <p>Informations Twitch de <?php echo UserDisplay::formatDisplayName($targetUser); ?></p>
                        <?php endif; ?>
                        
                        <div id="twitchStatus" class="twitch-status">
                            <div class="loading-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        
                        <?php if ($isOwnProfile): ?>
                        <div id="twitchLinkForm" class="twitch-link-form" style="display: none;">
                            <div class="twitch-not-connected">
                                <div class="twitch-notice">
                                    <i class="fab fa-twitch"></i>
                                    <h4>Compte Twitch non connecté</h4>
                                    <p>Pour lier votre compte Twitch et voir vos streams, vous devez d'abord vous connecter avec Twitch.</p>
                                    <p>Une fois connecté, vos informations de stream devraient s'afficher automatiquement.</p>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-primary" id="twitchConnectBtn">
                                        <i class="fab fa-twitch"></i> Se connecter avec Twitch
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div id="twitchInfo" class="twitch-info" style="display: none;">
                            <div class="twitch-profile">
                                <div class="twitch-avatar">
                                    <img id="twitchAvatar" src="" alt="Avatar Twitch">
                                </div>
                                <div class="twitch-details">
                                    <h4 id="twitchUsername"></h4>
                                    <p id="twitchDisplayName"></p>
                                    <div class="twitch-channel">
                                        <strong>Canal :</strong> 
                                        <a href="#" id="twitchChannelLink" target="_blank">
                                            <i class="fab fa-twitch"></i> Accéder à la chaîne
                                        </a>
                                    </div>
                                    <?php if ($isOwnProfile): ?>
                                    <div class="twitch-actions">
                                        <button type="button" id="refreshTwitchBtn" class="btn btn-secondary">
                                            <i class="fas fa-sync-alt"></i> Actualiser
                                        </button>
                                        <button type="button" id="unlinkTwitchBtn" class="btn btn-danger">
                                            <i class="fas fa-unlink"></i> Délier
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Discord -->
                <div class="profile-form <?php echo (!$isOwnProfile && $canViewPrivateInfo) ? 'active' : ''; ?>" id="discord-form">
                    <div class="discord-section">
                        <h3>Intégration Discord</h3>
                        <?php if ($isOwnProfile): ?>
                        <p>Liez votre compte Discord pour afficher votre profil et vos informations Discord.</p>
                        <?php else: ?>
                        <p>Informations Discord de <?php echo UserDisplay::formatDisplayName($targetUser); ?></p>
                        <?php endif; ?>
                        
                        <div id="discordStatus" class="discord-status">
                            <div class="loading-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                        
                        <?php if ($isOwnProfile): ?>
                        <div id="discordLinkForm" class="discord-link-form" style="display: none;">
                            <div class="discord-not-connected">
                                <div class="discord-notice">
                                    <i class="fab fa-discord"></i>
                                    <h4>Compte Discord non connecté</h4>
                                    <p>Pour lier votre compte Discord et voir vos informations, vous devez d'abord vous connecter avec Discord.</p>
                                    <p>Une fois connecté, vos informations Discord devraient s'afficher automatiquement.</p>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-primary" id="discordConnectBtn">
                                        <i class="fab fa-discord"></i> Se connecter avec Discord
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div id="discordInfo" class="discord-info" style="display: none;">
                            <div class="discord-profile">
                                <div class="discord-avatar">
                                    <img id="discordAvatar" src="" alt="Avatar Discord">
                                </div>
                                <div class="discord-details">
                                    <h4 id="discordDisplayName"></h4>

                                    <?php if ($isOwnProfile): ?>
                                    <div class="discord-actions">
                                        <button type="button" id="refreshDiscordBtn" class="btn btn-secondary">
                                            <i class="fas fa-sync-alt"></i> Actualiser
                                        </button>
                                        <button type="button" id="unlinkDiscordBtn" class="btn btn-danger">
                                            <i class="fas fa-unlink"></i> Délier
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
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
        
        // Fonctions de modération
        function editUsername(userId, currentUsername) {
            const newUsername = prompt('Nouveau pseudo pour cet utilisateur:', currentUsername);
            if (newUsername && newUsername.trim() !== '' && newUsername !== currentUsername) {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('username', newUsername.trim());
                
                fetch('api/edit_username.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Pseudo modifié avec succès !');
                        location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la modification du pseudo');
                    }
                })
                .catch(() => {
                    alert('Erreur réseau');
                });
            }
        }
        
        function deleteAvatar(userId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer l\'avatar de cet utilisateur ?')) {
                const formData = new FormData();
                formData.append('user_id', userId);
                
                fetch('api/delete_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Avatar supprimé avec succès !');
                        location.reload();
                    } else {
                        alert(data.message || 'Erreur lors de la suppression de l\'avatar');
                    }
                })
                .catch(() => {
                    alert('Erreur réseau');
                });
            }
        }
    </script>
    <script src="assets/js/date-utils.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/profile.js"></script>
    <script src="assets/js/discord.js"></script>
    <script src="assets/js/twitch.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/notifications.js"></script>
</body>
</html> 