// Gestion de la page de profil
class ProfileManager {
    constructor() {
        this.profileForm = document.getElementById('profileForm');
        this.passwordForm = document.getElementById('passwordForm');
        this.profileMessage = document.getElementById('profileMessage');
        this.avatarFileInput = document.getElementById('avatarFileInput');
        this.uploadAvatarBtn = document.getElementById('uploadAvatarBtn');
        this.removeAvatarBtn = document.getElementById('removeAvatarBtn');
        
        // Utiliser les données passées depuis PHP
        this.isOwnProfile = window.profileData ? window.profileData.isOwnProfile : true;
        this.currentUserId = window.profileData ? window.profileData.currentUserId : null;
        this.targetUserId = window.profileData ? window.profileData.targetUserId : null;
        
        this.init();
    }
    
    init() {
        // Configuration commune
        this.setupCommonActions();
        
        if (this.isOwnProfile) {
            // Configuration pour son propre profil
            this.setupTabSwitching();
            this.setupFormSubmissions();
            this.setupPasswordValidation();
            this.setupAvatarUpload();
        } else {
            // Configuration pour le profil d'un autre utilisateur
            this.setupFriendActions();
        }
        
        // Configuration Steam pour tous les profils
        this.setupSteamIntegration();
    }
    
    // Gestion des onglets
    setupTabSwitching() {
        const tabButtons = document.querySelectorAll('.profile-tab-btn');
        const profileForms = document.querySelectorAll('.profile-form');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.dataset.tab;
                
                // Mettre à jour les boutons
                tabButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Mettre à jour les formulaires
                profileForms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === `${targetTab}-form`) {
                        form.classList.add('active');
                    }
                });
                
                // Effacer les messages
                this.clearMessage();
            });
        });
    }
    
    // Gestion des soumissions de formulaires
    setupFormSubmissions() {
        if (this.profileForm) {
            this.profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfileUpdate();
            });
        }
        
        if (this.passwordForm) {
            this.passwordForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handlePasswordChange();
            });
        }
    }
    
    // Validation en temps réel du mot de passe
    setupPasswordValidation() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (newPassword && confirmPassword) {
            const validatePasswords = () => {
                const password = newPassword.value;
                const confirm = confirmPassword.value;
                
                // Validation de la longueur
                const reqLength = document.getElementById('req-length');
                if (reqLength) {
                    if (password.length >= 6) {
                        reqLength.classList.add('valid');
                        reqLength.classList.remove('invalid');
                    } else {
                        reqLength.classList.add('invalid');
                        reqLength.classList.remove('valid');
                    }
                }
                
                // Validation de la correspondance
                const reqMatch = document.getElementById('req-match');
                if (reqMatch) {
                    if (password && confirm && password === confirm) {
                        reqMatch.classList.add('valid');
                        reqMatch.classList.remove('invalid');
                    } else if (password && confirm) {
                        reqMatch.classList.add('invalid');
                        reqMatch.classList.remove('valid');
                    } else {
                        reqMatch.classList.remove('valid', 'invalid');
                    }
                }
            };
            
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }
    }
    
    // Mise à jour du profil
    async handleProfileUpdate() {
        const formData = new FormData(this.profileForm);
        const username = formData.get('username').trim();
        const first_name = formData.get('first_name').trim();
        const last_name = formData.get('last_name').trim();
        const email = formData.get('email').trim();
        const display_format = formData.get('display_format');
        
        // Validation côté client
        const has_names = first_name && last_name;
        const has_username = username;
        
        if (!has_names && !has_username) {
            this.showMessage('Vous devez remplir soit le pseudo, soit le nom et prénom', 'error');
            return;
        }
        
        if (!email) {
            this.showMessage('L\'email est obligatoire', 'error');
            return;
        }
        
        const submitBtn = this.profileForm.querySelector('button[type="submit"]');
        this.setLoading(submitBtn, true);
        
        try {
            const response = await fetch('api/profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_profile',
                    username,
                    first_name,
                    last_name,
                    email,
                    display_format
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message, 'success');
                // Recharger la page après 2 secondes pour mettre à jour l'affichage
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showMessage(data.message, 'error');
            }
            
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }
    
    // Changement de mot de passe
    async handlePasswordChange() {
        const formData = new FormData(this.passwordForm);
        const current_password = formData.get('current_password');
        const new_password = formData.get('new_password');
        const confirm_password = formData.get('confirm_password');
        
        // Validation côté client
        if (!current_password || !new_password || !confirm_password) {
            this.showMessage('Tous les champs sont requis', 'error');
            return;
        }
        
        if (new_password !== confirm_password) {
            this.showMessage('Les nouveaux mots de passe ne correspondent pas', 'error');
            return;
        }
        
        if (new_password.length < 6) {
            this.showMessage('Le nouveau mot de passe doit contenir au moins 6 caractères', 'error');
            return;
        }
        
        const submitBtn = this.passwordForm.querySelector('button[type="submit"]');
        this.setLoading(submitBtn, true);
        
        try {
            const response = await fetch('api/profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'change_password',
                    current_password,
                    new_password,
                    confirm_password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message, 'success');
                // Effacer le formulaire
                this.passwordForm.reset();
                // Réinitialiser les indicateurs de validation
                document.querySelectorAll('.password-requirements li').forEach(li => {
                    li.classList.remove('valid', 'invalid');
                });
            } else {
                this.showMessage(data.message, 'error');
            }
            
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }
    
    // Affichage des messages
    showMessage(message, type = 'success') {
        if (this.profileMessage) {
            this.profileMessage.textContent = message;
            this.profileMessage.className = `message ${type}`;
            
            // Auto-hide après 5 secondes
            setTimeout(() => {
                this.clearMessage();
            }, 5000);
        }
    }
    
    // Effacer les messages
    clearMessage() {
        if (this.profileMessage) {
            this.profileMessage.className = 'message';
            this.profileMessage.textContent = '';
        }
    }
    
    // Gestion des avatars disponibles
    setupAvatarUpload() {
        // Charger les avatars disponibles quand l'onglet avatar est ouvert
        const avatarTab = document.querySelector('.profile-tab-btn[data-tab="avatar"]');
        if (avatarTab) {
            avatarTab.addEventListener('click', () => {
                this.loadAvailableAvatars();
            });
        }
        
        // Charger les avatars au chargement de la page si l'onglet avatar est actif
        const avatarForm = document.getElementById('avatar-form');
        if (avatarForm && avatarForm.classList.contains('active')) {
            this.loadAvailableAvatars();
        }
    }
    
    async loadAvailableAvatars() {
        const avatarsLoading = document.getElementById('avatarsLoading');
        const availableAvatars = document.getElementById('availableAvatars');
        const noAvatarsMessage = document.getElementById('noAvatarsMessage');
        
        if (avatarsLoading) avatarsLoading.style.display = 'block';
        if (availableAvatars) availableAvatars.style.display = 'none';
        if (noAvatarsMessage) noAvatarsMessage.style.display = 'none';
        
        try {
            const response = await fetch('api/avatars.php');
            const data = await response.json();
            
            if (data.success) {
                this.displayAvailableAvatars(data.avatars, data.current_avatar);
            } else {
                this.showMessage(data.error || 'Erreur lors du chargement des avatars', 'error');
            }
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        } finally {
            if (avatarsLoading) avatarsLoading.style.display = 'none';
        }
    }
    
    displayAvailableAvatars(avatars, currentAvatar) {
        const availableAvatars = document.getElementById('availableAvatars');
        const noAvatarsMessage = document.getElementById('noAvatarsMessage');
        
        if (!availableAvatars) return;
        
        if (avatars.length === 0) {
            availableAvatars.style.display = 'none';
            if (noAvatarsMessage) noAvatarsMessage.style.display = 'block';
            return;
        }
        
        availableAvatars.innerHTML = '';
        
        avatars.forEach(avatar => {
            const avatarOption = document.createElement('div');
            avatarOption.className = 'avatar-option';
            avatarOption.dataset.avatarId = avatar.id;
            
            // Marquer comme sélectionné si c'est l'avatar actuel
            if (avatar.url === currentAvatar) {
                avatarOption.classList.add('selected');
            }
            
            avatarOption.innerHTML = `
                ${avatar.url ? 
                    `<img src="${avatar.url}" alt="${avatar.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` : 
                    ''
                }
                <div class="avatar-placeholder" style="${avatar.url ? 'display: none;' : ''}">
                    <i class="fas fa-user"></i>
                </div>
                <div class="avatar-option-name">${avatar.name}</div>
                <div class="avatar-option-provider">
                    <i class="${avatar.provider_icon}"></i>
                    ${avatar.provider}
                </div>
            `;
            
            avatarOption.addEventListener('click', () => {
                this.selectAvatar(avatar.id, avatar.url);
            });
            
            availableAvatars.appendChild(avatarOption);
        });
        
        availableAvatars.style.display = 'grid';
        if (noAvatarsMessage) noAvatarsMessage.style.display = 'none';
    }
    
    async selectAvatar(avatarId, avatarUrl) {
        try {
            const response = await fetch('api/set_avatar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    avatar_id: avatarId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message, 'success');
                this.updateAvatarDisplay(data.avatar_url);
                
                // Mettre à jour la sélection visuelle
                const avatarOptions = document.querySelectorAll('.avatar-option');
                avatarOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (option.dataset.avatarId === avatarId) {
                        option.classList.add('selected');
                    }
                });
                
                // Recharger la page après 2 secondes pour mettre à jour l'affichage
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showMessage(data.error || 'Erreur lors de la mise à jour de l\'avatar', 'error');
            }
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        }
    }
    
    // Validation et prévisualisation de l'avatar
    validateAndPreviewAvatar(file) {
        // Validation du type de fichier
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            this.showMessage('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF', 'error');
            return;
        }
        
        // Validation de la taille (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showMessage('Le fichier est trop volumineux. Taille maximum : 5MB', 'error');
            return;
        }
        
        // Prévisualisation
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('avatarPreview');
            if (preview) {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    // Remplacer le placeholder par une image
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Avatar preview';
                    img.style.width = '120px';
                    img.style.height = '120px';
                    img.style.borderRadius = '50%';
                    img.style.objectFit = 'cover';
                    img.style.border = '3px solid var(--primary-color)';
                    preview.parentNode.replaceChild(img, preview);
                    img.id = 'avatarPreview';
                }
            }
            
            // Activer le bouton d'upload
            if (this.uploadAvatarBtn) {
                this.uploadAvatarBtn.disabled = false;
            }
        };
        reader.readAsDataURL(file);
    }
    
    // Upload de l'avatar
    async uploadAvatar() {
        const file = this.avatarFileInput.files[0];
        if (!file) {
            this.showMessage('Aucun fichier sélectionné', 'error');
            return;
        }
        
        this.uploadAvatarFile(file);
    }
    
    // Upload d'un fichier avatar
    async uploadAvatarFile(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        
        // Afficher la barre de progression
        const progressBar = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        if (progressBar) {
            progressBar.style.display = 'block';
        }
        
        // Désactiver les boutons
        if (this.uploadAvatarBtn) {
            this.uploadAvatarBtn.disabled = true;
        }
        if (this.removeAvatarBtn) {
            this.removeAvatarBtn.disabled = true;
        }
        
        try {
            const response = await fetch('api/upload_avatar.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message, 'success');
                
                // Mettre à jour les avatars affichés
                this.updateAvatarDisplay(data.avatar_url);
                
                // Réinitialiser le formulaire
                if (this.avatarFileInput) {
                    this.avatarFileInput.value = '';
                }
                if (this.avatarInput) {
                    this.avatarInput.value = '';
                }
                
                // Désactiver le bouton d'upload
                if (this.uploadAvatarBtn) {
                    this.uploadAvatarBtn.disabled = true;
                }
                
                // Activer le bouton de suppression
                if (this.removeAvatarBtn) {
                    this.removeAvatarBtn.disabled = false;
                }
                
            } else {
                this.showMessage(data.message, 'error');
            }
            
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        } finally {
            // Masquer la barre de progression
            if (progressBar) {
                progressBar.style.display = 'none';
            }
            if (progressFill) {
                progressFill.style.width = '0%';
            }
            if (progressText) {
                progressText.textContent = '0%';
            }
        }
    }
    
    // Suppression de l'avatar
    async removeAvatar() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer votre avatar ?')) {
            return;
        }
        
        try {
            const response = await fetch('api/profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove_avatar'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message, 'success');
                this.updateAvatarDisplay(null);
                
                // Désactiver le bouton de suppression
                if (this.removeAvatarBtn) {
                    this.removeAvatarBtn.disabled = true;
                }
                
            } else {
                this.showMessage(data.message, 'error');
            }
            
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        }
    }
    
    // Mise à jour de l'affichage de l'avatar
    updateAvatarDisplay(avatarUrl) {
        // Mettre à jour l'avatar dans l'en-tête
        const currentAvatar = document.getElementById('currentAvatar');
        if (currentAvatar) {
            if (avatarUrl) {
                if (currentAvatar.tagName === 'IMG') {
                    currentAvatar.src = avatarUrl;
                } else {
                    const img = document.createElement('img');
                    img.src = avatarUrl;
                    img.alt = 'Avatar';
                    img.id = 'currentAvatar';
                    currentAvatar.parentNode.replaceChild(img, currentAvatar);
                }
            } else {
                if (currentAvatar.tagName !== 'DIV') {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'avatar-placeholder large';
                    placeholder.id = 'currentAvatar';
                    placeholder.innerHTML = '<i class="fas fa-user"></i>';
                    currentAvatar.parentNode.replaceChild(placeholder, currentAvatar);
                }
            }
        }
        
        // Mettre à jour l'avatar dans l'onglet avatar
        const avatarPreview = document.getElementById('avatarPreview');
        if (avatarPreview) {
            if (avatarUrl) {
                if (avatarPreview.tagName === 'IMG') {
                    avatarPreview.src = avatarUrl;
                } else {
                    const img = document.createElement('img');
                    img.src = avatarUrl;
                    img.alt = 'Avatar actuel';
                    img.id = 'avatarPreview';
                    img.style.width = '120px';
                    img.style.height = '120px';
                    img.style.borderRadius = '50%';
                    img.style.objectFit = 'cover';
                    img.style.border = '3px solid var(--primary-color)';
                    avatarPreview.parentNode.replaceChild(img, avatarPreview);
                }
            } else {
                if (avatarPreview.tagName !== 'DIV') {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'avatar-placeholder medium';
                    placeholder.id = 'avatarPreview';
                    placeholder.innerHTML = '<i class="fas fa-user"></i>';
                    avatarPreview.parentNode.replaceChild(placeholder, avatarPreview);
                }
            }
        }
    }
    
    // Gestion du chargement
    setLoading(element, loading) {
        if (loading) {
            element.classList.add('loading');
            element.disabled = true;
            const originalText = element.innerHTML;
            element.dataset.originalText = originalText;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
        } else {
            element.classList.remove('loading');
            element.disabled = false;
            if (element.dataset.originalText) {
                element.innerHTML = element.dataset.originalText;
                delete element.dataset.originalText;
            }
        }
    }
    
    // Gestion Steam
    setupSteamIntegration() {
        this.steamStatus = document.getElementById('steamStatus');
        this.steamLinkForm = document.getElementById('steamLinkForm');
        this.steamInfo = document.getElementById('steamInfo');
        this.unlinkSteamBtn = document.getElementById('unlinkSteamBtn');
        this.refreshSteamBtn = document.getElementById('refreshSteamBtn');
        this.gamesSearch = document.getElementById('gamesSearch');
        this.steamConnectBtn = document.getElementById('steamConnectBtn');
        
        if (this.unlinkSteamBtn) {
            this.unlinkSteamBtn.addEventListener('click', () => this.handleUnlinkSteam());
        }
        
        if (this.refreshSteamBtn) {
            this.refreshSteamBtn.addEventListener('click', () => this.handleRefreshSteam());
        }
        
        if (this.gamesSearch) {
            this.gamesSearch.addEventListener('input', (e) => this.filterGames(e.target.value));
        }
        
        if (this.steamConnectBtn) {
            this.steamConnectBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSteamAuth();
            });
        }
        
        // Charger les informations Steam au démarrage
        this.loadSteamInfo();
    }
    
    async loadSteamInfo() {
        this.showSteamLoading(true);
        
        try {
            // Récupérer l'ID de l'utilisateur cible depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const targetUserId = urlParams.get('user_id');
            
            let url = 'api/steam.php?action=info';
            if (targetUserId && !this.isOwnProfile) {
                url += `&user_id=${targetUserId}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.displaySteamInfo(data.data);
            } else {
                if (this.isOwnProfile) {
                    this.showSteamLinkForm();
                } else {
                    this.showSteamError('Aucune information Steam disponible pour cet utilisateur');
                }
            }
        } catch (error) {
            this.showSteamError('Erreur lors du chargement des informations Steam');
        } finally {
            this.showSteamLoading(false);
        }
    }
    
    displaySteamInfo(steamData) {
        // Afficher les informations du profil
        const steamAvatar = document.getElementById('steamAvatar');
        const steamUsername = document.getElementById('steamUsername');
        const steamRealName = document.getElementById('steamRealName');
        const steamFriendCode = document.getElementById('steamFriendCode');
        
        if (steamAvatar && steamData.avatar) {
            steamAvatar.src = steamData.avatar;
        }
        
        if (steamUsername) {
            steamUsername.textContent = steamData.username || 'Utilisateur Steam';
        }
        
        if (steamRealName) {
            steamRealName.textContent = steamData.realname || '';
        }
        
        if (steamFriendCode) {
            steamFriendCode.textContent = steamData.friend_code || '';
        }
        
        // Afficher les jeux
        this.displayGames(steamData.games || []);
        
        // Afficher la section Steam
        this.steamInfo.style.display = 'block';
        this.steamLinkForm.style.display = 'none';
        this.steamStatus.innerHTML = '';
    }
    
    displayGames(games) {
        const gamesList = document.getElementById('gamesList');
        const gamesCount = document.getElementById('gamesCount');
        
        if (gamesCount) {
            gamesCount.textContent = games.length;
        }
        
        if (gamesList) {
            gamesList.innerHTML = '';
            
            if (games.length === 0) {
                gamesList.innerHTML = '<p class="no-games">Aucun jeu trouvé</p>';
                return;
            }
            
            games.forEach(game => {
                const gameElement = this.createGameElement(game);
                gamesList.appendChild(gameElement);
            });
        }
    }
    
    createGameElement(game) {
        const gameDiv = document.createElement('div');
        gameDiv.className = 'game-item';
        gameDiv.dataset.gameName = game.name.toLowerCase();
        
        const gameImage = game.img_logo_url 
            ? `https://media.steampowered.com/steamcommunity/public/images/apps/${game.app_id}/${game.img_logo_url}.jpg`
            : 'assets/images/default-game.svg';
        
        gameDiv.innerHTML = `
            <div class="game-image">
                <img src="${gameImage}" alt="${game.name}" onerror="this.src='assets/images/default-game.svg'">
            </div>
            <div class="game-info">
                <h5>${game.name}</h5>
                <div class="game-stats">
                    <span class="playtime-total">
                        <i class="fas fa-clock"></i> ${game.playtime_formatted || '0 min'}
                    </span>
                    ${game.playtime_2weeks > 0 ? `
                        <span class="playtime-recent">
                            <i class="fas fa-calendar-week"></i> ${game.playtime_2weeks_formatted || '0 min'} (2 semaines)
                        </span>
                    ` : ''}
                </div>
            </div>
        `;
        
        return gameDiv;
    }
    
    filterGames(searchTerm) {
        const gameItems = document.querySelectorAll('.game-item');
        const searchLower = searchTerm.toLowerCase();
        
        gameItems.forEach(item => {
            const gameName = item.dataset.gameName;
            if (gameName.includes(searchLower)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    

    
    async handleUnlinkSteam() {
        if (!confirm('Êtes-vous sûr de vouloir délier votre compte Steam ?')) {
            return;
        }
        
        this.setLoading(this.unlinkSteamBtn, true);
        
        try {
            const response = await fetch('api/steam.php?action=unlink', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSteamSuccess(data.message);
                setTimeout(() => this.showSteamLinkForm(), 1000);
            } else {
                this.showSteamError(data.message);
            }
        } catch (error) {
            this.showSteamError('Erreur lors de la déliaison du compte Steam');
        } finally {
            this.setLoading(this.unlinkSteamBtn, false);
        }
    }
    
    async handleRefreshSteam() {
        this.setLoading(this.refreshSteamBtn, true);
        
        try {
            const response = await fetch('api/steam.php?action=refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSteamSuccess(data.message);
                this.displaySteamInfo(data.data);
            } else {
                this.showSteamError(data.message);
            }
        } catch (error) {
            this.showSteamError('Erreur lors de l\'actualisation des informations Steam');
        } finally {
            this.setLoading(this.refreshSteamBtn, false);
        }
    }
    
    showSteamLoading(show) {
        if (show) {
            this.steamStatus.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
        } else {
            this.steamStatus.innerHTML = '';
        }
    }
    
    showSteamLinkForm() {
        this.steamInfo.style.display = 'none';
        this.steamLinkForm.style.display = 'block';
        this.steamStatus.innerHTML = '';
    }
    
    // Gestion de l'authentification Steam
    handleSteamAuth() {
        // Construire l'URL d'authentification Steam OpenID
        const steamAuthUrl = 'https://steamcommunity.com/openid/login';
        const params = new URLSearchParams({
            'openid.ns': 'http://specs.openid.net/auth/2.0',
            'openid.mode': 'checkid_setup',
            'openid.return_to': 'https://ladrio2.goodloss.fr/oauth2callback_steam.php',
            'openid.realm': 'https://ladrio2.goodloss.fr',
            'openid.identity': 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id': 'http://specs.openid.net/auth/2.0/identifier_select'
        });
        
        // Rediriger vers Steam
        window.location.href = steamAuthUrl + '?' + params.toString();
    }
    
    showSteamSuccess(message) {
        this.steamStatus.innerHTML = `<div class="message success"><i class="fas fa-check"></i> ${message}</div>`;
    }
    
    showSteamError(message) {
        this.steamStatus.innerHTML = `<div class="message error"><i class="fas fa-exclamation-triangle"></i> ${message}</div>`;
    }
    
    // Configuration des actions communes
    setupCommonActions() {
        // Gestion du bouton profil dans le header principal
        const profileBtn = document.getElementById('profileBtn');
        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                window.location.href = 'profile.php';
            });
        }
        
        // Gestion du bouton amis
        const friendsBtn = document.getElementById('friendsBtn');
        if (friendsBtn) {
            friendsBtn.addEventListener('click', () => {
                window.location.href = 'friends.php';
            });
        }
        
        // Gestion du bouton déconnexion
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async () => {
                if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                    try {
                        const response = await fetch('api/auth.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'logout'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Redirection vers la page d'accueil après déconnexion
                            window.location.href = 'index.php';
                        } else {
                            // En cas d'erreur, redirection quand même
                            window.location.href = 'index.php';
                        }
                    } catch (error) {
                        // En cas d'erreur réseau, redirection quand même
                        window.location.href = 'index.php';
                    }
                }
            });
        }
    }
    
    // Gestion des actions d'amis pour les profils d'autres utilisateurs
    setupFriendActions() {
        const addFriendBtn = document.getElementById('addFriendBtn');
        const viewGamesBtn = document.getElementById('viewGamesBtn');
        const viewFriendsBtn = document.getElementById('viewFriendsBtn');
        
        if (addFriendBtn) {
            addFriendBtn.addEventListener('click', () => {
                this.handleAddFriend();
            });
        }
        
        if (viewGamesBtn) {
            viewGamesBtn.addEventListener('click', () => {
                this.handleViewGames();
            });
        }
        
        if (viewFriendsBtn) {
            viewFriendsBtn.addEventListener('click', () => {
                this.handleViewFriends();
            });
        }
    }
    
    // Gérer l'ajout d'ami
    async handleAddFriend() {
        const addFriendBtn = document.getElementById('addFriendBtn');
        const userId = addFriendBtn.dataset.userId;
        
        if (!userId) {
            this.showMessage('Erreur: ID utilisateur manquant', 'error');
            return;
        }
        
        this.setLoading(addFriendBtn, true);
        
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'send_request',
                    receiver_id: userId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message, 'success');
                // Changer le bouton pour indiquer que la demande a été envoyée
                addFriendBtn.innerHTML = '<i class="fas fa-clock"></i> Demande envoyée';
                addFriendBtn.disabled = true;
                addFriendBtn.classList.remove('btn-primary');
                addFriendBtn.classList.add('btn-secondary');
            } else {
                this.showMessage(data.message, 'error');
            }
            
        } catch (error) {
            this.showMessage('Erreur lors de l\'envoi de la demande d\'ami', 'error');
        } finally {
            this.setLoading(addFriendBtn, false);
        }
    }
    
    // Gérer l'affichage des jeux
    handleViewGames() {
        // Basculer vers l'onglet Steam qui contient les jeux
        const steamTab = document.querySelector('.profile-tab-btn[data-tab="steam"]');
        if (steamTab) {
            steamTab.click();
        } else {
            // Si pas d'onglets (profil d'autre utilisateur), afficher directement la section Steam
            const steamForm = document.getElementById('steam-form');
            if (steamForm) {
                // Masquer toutes les sections
                document.querySelectorAll('.profile-form').forEach(form => {
                    form.classList.remove('active');
                });
                // Afficher la section Steam
                steamForm.classList.add('active');
            }
        }
        
        // Scroll vers la section Steam
        setTimeout(() => {
            const steamSection = document.querySelector('.steam-section');
            if (steamSection) {
                steamSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }
    
    // Gérer l'affichage des amis
    handleViewFriends() {
        // Rediriger vers la page des amis avec l'ID de l'utilisateur
        const userId = this.targetUserId;
        if (userId) {
            window.location.href = `friends.php?user_id=${userId}`;
        } else {
            this.showMessage('Erreur: Impossible d\'accéder aux amis', 'error');
        }
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    new ProfileManager();
});
