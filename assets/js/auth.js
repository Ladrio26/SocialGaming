// Gestion de l'authentification côté client
class AuthManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupTabSwitching();
        this.setupFormSubmissions();
        this.setupSocialAuth();
        this.setupLogout();
        this.setupProfile();
    }

    // Gestion des onglets
    setupTabSwitching() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const authForms = document.querySelectorAll('.auth-form');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;
                
                // Mettre à jour les boutons
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Mettre à jour les formulaires
                authForms.forEach(form => {
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
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleRegister();
            });
        }
    }

    // Gestion de la connexion
    async handleLogin() {
        const username = document.getElementById('loginUsername').value.trim();
        const password = document.getElementById('loginPassword').value;

        if (!username || !password) {
            this.showMessage('Pseudo et mot de passe requis', 'error');
            return;
        }

        const loginBtn = document.querySelector('#loginForm .btn');
        this.setLoading(loginBtn, true);

        try {
            const response = await this.apiCall('login', { username, password });
            
            if (response.success) {
                this.showMessage(response.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showMessage(response.message, 'error');
            }
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        } finally {
            this.setLoading(loginBtn, false);
        }
    }

    // Gestion de l'inscription
    async handleRegister() {
        const username = document.getElementById('registerUsername').value.trim();
        const email = document.getElementById('registerEmail').value.trim();
        const password = document.getElementById('registerPassword').value;
        const passwordConfirm = document.getElementById('registerPasswordConfirm').value;

        // Validation du pseudo obligatoire
        if (!username) {
            this.showMessage('Le pseudo est obligatoire', 'error');
            return;
        }
        
        if (!email || !password || !passwordConfirm) {
            this.showMessage('Email et mot de passe sont obligatoires', 'error');
            return;
        }
        
        // Validation du pseudo
        if (username.length < 3) {
            this.showMessage('Le pseudo doit contenir au moins 3 caractères', 'error');
            return;
        }
        
        if (username.length > 20) {
            this.showMessage('Le pseudo ne peut pas dépasser 20 caractères', 'error');
            return;
        }
        
        // Vérifier que le pseudo ne contient que des caractères autorisés
        if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
            this.showMessage('Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores', 'error');
            return;
        }
        


        if (password !== passwordConfirm) {
            this.showMessage('Les mots de passe ne correspondent pas', 'error');
            return;
        }

        if (password.length < 6) {
            this.showMessage('Le mot de passe doit contenir au moins 6 caractères', 'error');
            return;
        }

        const registerBtn = document.querySelector('#registerForm .btn');
        this.setLoading(registerBtn, true);

        try {
            const response = await this.apiCall('register', { username, email, password });
            
            if (response.success) {
                this.showMessage(response.message, 'success');
                
                // Si l'inscription réussit et que l'utilisateur est connecté, rediriger vers la page d'accueil
                if (response.message.includes('connecté')) {
                    // Attendre un peu pour que l'utilisateur voie le message de succès
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    // Si la connexion automatique a échoué, basculer vers l'onglet de connexion
                    document.querySelector('[data-tab="login"]').click();
                    // Effacer le formulaire d'inscription
                    document.getElementById('registerForm').reset();
                }
            } else {
                this.showMessage(response.message, 'error');
            }
        } catch (error) {
            this.showMessage('Erreur de connexion au serveur', 'error');
        } finally {
            this.setLoading(registerBtn, false);
        }
    }

    // Gestion de l'authentification sociale
    setupSocialAuth() {
        const discordBtn = document.getElementById('discordBtn');
        const steamBtn = document.getElementById('steamBtn');
        const twitchBtn = document.getElementById('twitchBtn');

        if (discordBtn) {
            discordBtn.addEventListener('click', () => {
                // Générer un state aléatoire pour la sécurité CSRF
                const state = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                
                // Stocker le state dans sessionStorage pour la vérification côté serveur
                sessionStorage.setItem('discord_state', state);
                
                // Construire l'URL d'authentification Discord
                const discordAuthUrl = 'https://discord.com/api/oauth2/authorize';
                const params = new URLSearchParams({
                    'client_id': '1390168348205121687',
                    'redirect_uri': 'https://ladrio2.goodloss.fr/oauth2callback_discord.php',
                    'response_type': 'code',
                    'scope': 'identify email',
                    'state': state
                });
                
                window.location.href = discordAuthUrl + '?' + params.toString();
            });
        }

        if (steamBtn) {
            steamBtn.addEventListener('click', () => {
                this.handleSteamAuth();
            });
        }

        if (twitchBtn) {
            twitchBtn.addEventListener('click', () => {
                this.handleTwitchAuth();
            });
        }
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

    // Gestion de l'authentification Twitch
    handleTwitchAuth() {
        // Générer un state aléatoire pour la sécurité
        const state = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        
        // Stocker le state dans sessionStorage pour la vérification
        sessionStorage.setItem('twitch_state', state);
        
        // Construire l'URL d'authentification Twitch OAuth2
        const twitchAuthUrl = 'https://id.twitch.tv/oauth2/authorize';
        const params = new URLSearchParams({
            'client_id': 'jzojy4wm2z60g0hzoyab9czujkx82b',
            'redirect_uri': 'https://ladrio2.goodloss.fr/oauth2callback_twitch_auth.php',
            'response_type': 'code',
            'scope': 'user:read:email',
            'state': state
        });
        
        // Rediriger vers Twitch
        window.location.href = twitchAuthUrl + '?' + params.toString();
    }

    // Gestion de la déconnexion
    setupLogout() {
        const logoutBtn = document.getElementById('logoutBtn');
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async () => {
                try {
                    const response = await this.apiCall('logout');
                    if (response.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Erreur lors de la déconnexion:', error);
                    window.location.reload();
                }
            });
        }
    }
    
    // Gestion du bouton de profil
    setupProfile() {
        const profileBtn = document.getElementById('profileBtn');
        const friendsBtn = document.getElementById('friendsBtn');
        
        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                window.location.href = 'profile.php';
            });
        }
        
        if (friendsBtn) {
            friendsBtn.addEventListener('click', () => {
                window.location.href = 'friends.php';
            });
        }
        
        // Gestion du message de bienvenue temporaire
        const welcomeMessage = document.getElementById('welcomeMessage');
        if (welcomeMessage) {
            setTimeout(() => {
                welcomeMessage.classList.add('fade-out');
                setTimeout(() => {
                    welcomeMessage.style.display = 'none';
                }, 500);
            }, 3000); // Disparaît après 3 secondes
        }
        
        // Gestion de la recherche mini
        this.setupMiniSearch();
    }
    
    // Gestion de la recherche mini
    setupMiniSearch() {
        const searchInput = document.getElementById('searchInputMini');
        const searchResults = document.getElementById('searchResultsMini');
        
        if (searchInput && searchResults) {
            let searchTimeout;
            let selectedIndex = -1;
            let currentResults = [];
            
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                const query = searchInput.value.trim();
                selectedIndex = -1; // Reset selection
                
                if (query.length >= 1) {
                    searchTimeout = setTimeout(() => {
                        this.performMiniSearch(query, searchResults);
                    }, 200); // Réduit à 200ms pour plus de réactivité
                } else {
                    searchResults.classList.remove('active');
                }
            });
            
            searchInput.addEventListener('focus', () => {
                const query = searchInput.value.trim();
                if (query.length >= 1) {
                    this.performMiniSearch(query, searchResults);
                }
            });
            
            // Fermer les résultats en cliquant ailleurs
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.remove('active');
                    selectedIndex = -1;
                }
            });
            
            // Gestion des touches clavier
            searchInput.addEventListener('keydown', (e) => {
                const userCards = searchResults.querySelectorAll('.user-card-mini');
                
                if (e.key === 'Escape') {
                    searchResults.classList.remove('active');
                    searchInput.blur();
                    selectedIndex = -1;
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (userCards.length > 0) {
                        selectedIndex = Math.min(selectedIndex + 1, userCards.length - 1);
                        this.updateSelection(userCards, selectedIndex);
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (userCards.length > 0) {
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        this.updateSelection(userCards, selectedIndex);
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && userCards[selectedIndex]) {
                        // Cliquer sur l'élément sélectionné
                        userCards[selectedIndex].click();
                    } else if (userCards.length > 0) {
                        // Si aucun élément n'est sélectionné, cliquer sur le premier
                        userCards[0].click();
                    }
                }
            });
        }
    }
    
    // Mettre à jour la sélection visuelle
    updateSelection(userCards, selectedIndex) {
        userCards.forEach((card, index) => {
            if (index === selectedIndex) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }
    
    // Effectuer la recherche mini
    async performMiniSearch(query, resultsContainer) {
        try {
            const response = await fetch('api/search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'search_users',
                    query: query 
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.users.length > 0) {
                resultsContainer.innerHTML = data.users.map(user => {
                    // Utiliser uniquement le pseudo
                    const displayName = user.username || 'Utilisateur';
                    
                    return `
                        <div class="user-card-mini" onclick="window.location.href='profile.php?user_id=${user.id}'">
                            <div class="user-avatar-mini">
                                ${user.avatar_url ? 
                                    `<img src="${user.avatar_url}" alt="Avatar">` : 
                                    `<div class="avatar-placeholder-mini"><i class="fas fa-user"></i></div>`
                                }
                            </div>
                            <div class="user-info-mini">
                                <div class="user-name-mini">${displayName}</div>
                            </div>
                            <div class="user-actions-mini">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    `;
                }).join('');
                resultsContainer.classList.add('active');
            } else {
                resultsContainer.innerHTML = '<div class="no-results-mini">Aucun utilisateur trouvé</div>';
                resultsContainer.classList.add('active');
            }
        } catch (error) {
            console.error('Erreur de recherche:', error);
            resultsContainer.innerHTML = '<div class="error-mini">Erreur de recherche</div>';
            resultsContainer.classList.add('active');
        }
    }

    // Appel API générique
    async apiCall(action, data = {}) {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action,
                ...data
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    // Affichage des messages
    showMessage(message, type = 'success') {
        const messageEl = document.getElementById('message');
        if (messageEl) {
            messageEl.textContent = message;
            messageEl.className = `message ${type}`;
            
            // Auto-hide après 5 secondes
            setTimeout(() => {
                this.clearMessage();
            }, 5000);
        }
    }

    // Effacer les messages
    clearMessage() {
        const messageEl = document.getElementById('message');
        if (messageEl) {
            messageEl.className = 'message';
            messageEl.textContent = '';
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
}

// Validation en temps réel des formulaires
class FormValidator {
    constructor() {
        this.setupRealTimeValidation();
    }

    setupRealTimeValidation() {
        const inputs = document.querySelectorAll('input[type="email"], input[type="password"], input[type="text"]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        switch (field.type) {
            case 'email':
                if (value && !this.isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Format d\'email invalide';
                }
                break;
            
            case 'password':
                if (value && value.length < 6) {
                    isValid = false;
                    errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
                }
                break;
            
            case 'text':
                if (field.name === 'username') {
                    if (value && value.length < 3) {
                        isValid = false;
                        errorMessage = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
                    } else if (value && value.length > 20) {
                        isValid = false;
                        errorMessage = 'Le nom d\'utilisateur ne peut pas dépasser 20 caractères';
                    } else if (value && !/^[a-zA-Z0-9_-]+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'Caractères non autorisés (lettres, chiffres, tirets et underscores uniquement)';
                    }
                }
                break;
        }

        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }

        return isValid;
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.style.borderColor = 'var(--danger-color)';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.color = 'var(--danger-color)';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '5px';
        
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.style.borderColor = '';
        
        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
}

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
    new FormValidator();
});

// Amélioration de l'expérience utilisateur
document.addEventListener('DOMContentLoaded', () => {
    // Focus automatique sur le premier champ
    const firstInput = document.querySelector('input');
    if (firstInput) {
        firstInput.focus();
    }

    // Navigation au clavier dans les onglets
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach((btn, index) => {
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btn.click();
            }
        });
    });

    // Validation en temps réel du mot de passe de confirmation
    const passwordInput = document.getElementById('registerPassword');
    const confirmInput = document.getElementById('registerPasswordConfirm');
    
    if (passwordInput && confirmInput) {
        const validatePasswordMatch = () => {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm && password !== confirm) {
                confirmInput.style.borderColor = 'var(--danger-color)';
            } else {
                confirmInput.style.borderColor = '';
            }
        };
        
        passwordInput.addEventListener('input', validatePasswordMatch);
        confirmInput.addEventListener('input', validatePasswordMatch);
    }
}); 