class DiscordManager {
    constructor() {
        this.init();
    }

    init() {
        // Gestionnaire pour l'onglet Discord
        const discordTab = document.querySelector('[data-tab="discord"]');
        if (discordTab) {
            discordTab.addEventListener('click', () => this.loadDiscordStatus());
        }

        // Gestionnaires pour les boutons Discord
        const discordConnectBtn = document.getElementById('discordConnectBtn');
        if (discordConnectBtn) {
            discordConnectBtn.addEventListener('click', () => this.connectDiscord());
        }

        const refreshDiscordBtn = document.getElementById('refreshDiscordBtn');
        if (refreshDiscordBtn) {
            refreshDiscordBtn.addEventListener('click', () => this.refreshDiscord());
        }

        const unlinkDiscordBtn = document.getElementById('unlinkDiscordBtn');
        if (unlinkDiscordBtn) {
            unlinkDiscordBtn.addEventListener('click', () => this.unlinkDiscord());
        }

        // Charger le statut Discord si l'onglet est actif
        if (document.getElementById('discord-form').classList.contains('active')) {
            this.loadDiscordStatus();
        }
    }

    async loadDiscordStatus() {
        const discordStatus = document.getElementById('discordStatus');
        const discordLinkForm = document.getElementById('discordLinkForm');
        const discordInfo = document.getElementById('discordInfo');

        // Afficher le loading
        discordStatus.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

        try {
            const response = await fetch('api/discord.php?action=status');
            const data = await response.json();

            if (data.linked) {
                // Compte Discord lié
                this.displayDiscordInfo(data.account);
                discordLinkForm.style.display = 'none';
                discordInfo.style.display = 'block';
                discordStatus.innerHTML = '';
            } else {
                // Compte Discord non lié
                discordLinkForm.style.display = 'block';
                discordInfo.style.display = 'none';
                discordStatus.innerHTML = '';
            }
        } catch (error) {
            console.error('Erreur lors du chargement du statut Discord:', error);
            discordStatus.innerHTML = '<div class="error">Erreur lors du chargement du statut Discord</div>';
        }
    }

    async connectDiscord() {
        try {
            const response = await fetch('api/discord.php?action=link');
            const data = await response.json();

            if (data.auth_url) {
                // Rediriger vers l'authentification Discord
                window.location.href = data.auth_url;
            } else {
                throw new Error('URL d\'authentification non reçue');
            }
        } catch (error) {
            console.error('Erreur lors de la connexion Discord:', error);
            alert('Erreur lors de la connexion avec Discord. Veuillez réessayer.');
        }
    }

    async refreshDiscord() {
        try {
            await this.loadDiscordStatus();
            this.showMessage('Statut Discord actualisé !', 'success');
        } catch (error) {
            console.error('Erreur lors de l\'actualisation Discord:', error);
            this.showMessage('Erreur lors de l\'actualisation', 'error');
        }
    }

    async unlinkDiscord() {
        if (!confirm('Êtes-vous sûr de vouloir délier votre compte Discord ?')) {
            return;
        }

        try {
            const response = await fetch('api/discord.php?action=unlink');
            const data = await response.json();

            if (data.success) {
                this.showMessage('Compte Discord délié avec succès !', 'success');
                await this.loadDiscordStatus();
            } else {
                throw new Error('Erreur lors du déliage');
            }
        } catch (error) {
            console.error('Erreur lors du déliage Discord:', error);
            this.showMessage('Erreur lors du déliage du compte Discord', 'error');
        }
    }

    displayDiscordInfo(account) {
        // Afficher les informations du profil Discord
        const discordAvatar = document.getElementById('discordAvatar');
        const discordDisplayName = document.getElementById('discordDisplayName');

        if (account.discord_avatar_url) {
            discordAvatar.src = account.discord_avatar_url;
            discordAvatar.style.display = 'block';
        } else {
            discordAvatar.style.display = 'none';
        }

        discordDisplayName.textContent = account.discord_display_name;
        
        // Si c'est le compte principal Discord, afficher un message spécial
        if (account.is_primary_account) {
            const discordInfo = document.getElementById('discordInfo');
            const primaryAccountNotice = document.createElement('div');
            primaryAccountNotice.className = 'primary-account-notice';
            primaryAccountNotice.innerHTML = `
                <div class="notice-info">
                    <i class="fas fa-info-circle"></i>
                    <p>Ce compte Discord est votre compte principal d'inscription. Vos informations Discord sont automatiquement synchronisées.</p>
                </div>
            `;
            
            // Insérer la notice après les détails Discord
            const discordDetails = discordInfo.querySelector('.discord-details');
            discordDetails.appendChild(primaryAccountNotice);
        }
    }

    showMessage(message, type = 'info') {
        // Afficher un message à l'utilisateur
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        
        // Insérer le message en haut de la page
        const container = document.querySelector('.profile-container') || document.body;
        container.insertBefore(messageDiv, container.firstChild);
        
        // Supprimer le message après 5 secondes
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 5000);
    }
}

// Initialiser le gestionnaire Discord quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new DiscordManager();
}); 