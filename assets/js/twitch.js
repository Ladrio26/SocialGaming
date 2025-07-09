// Gestion de l'intégration Twitch
class TwitchManager {
    constructor() {
        this.init();
    }

    init() {
        // Gestionnaire pour l'onglet Twitch
        const twitchTab = document.querySelector('[data-tab="twitch"]');
        if (twitchTab) {
            twitchTab.addEventListener('click', () => this.loadTwitchStatus());
        }

        // Gestionnaires pour les boutons Twitch
        const twitchConnectBtn = document.getElementById('twitchConnectBtn');
        if (twitchConnectBtn) {
            twitchConnectBtn.addEventListener('click', () => this.connectTwitch());
        }

        const refreshTwitchBtn = document.getElementById('refreshTwitchBtn');
        if (refreshTwitchBtn) {
            refreshTwitchBtn.addEventListener('click', () => this.refreshTwitch());
        }

        const unlinkTwitchBtn = document.getElementById('unlinkTwitchBtn');
        if (unlinkTwitchBtn) {
            unlinkTwitchBtn.addEventListener('click', () => this.unlinkTwitch());
        }

        // Charger le statut Twitch si l'onglet est actif
        if (document.getElementById('twitch-form').classList.contains('active')) {
            this.loadTwitchStatus();
        }
    }

    async loadTwitchStatus() {
        const twitchStatus = document.getElementById('twitchStatus');
        const twitchLinkForm = document.getElementById('twitchLinkForm');
        const twitchInfo = document.getElementById('twitchInfo');

        // Afficher le loading
        twitchStatus.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

        try {
            const response = await fetch('api/twitch.php?action=status');
            const data = await response.json();

            if (data.linked) {
                // Compte Twitch lié
                this.displayTwitchInfo(data.account);
                twitchLinkForm.style.display = 'none';
                twitchInfo.style.display = 'block';
                twitchStatus.innerHTML = '';
            } else {
                // Compte Twitch non lié
                twitchLinkForm.style.display = 'block';
                twitchInfo.style.display = 'none';
                twitchStatus.innerHTML = '';
            }
        } catch (error) {
            console.error('Erreur lors du chargement du statut Twitch:', error);
            twitchStatus.innerHTML = '<div class="error">Erreur lors du chargement du statut Twitch</div>';
        }
    }

    async connectTwitch() {
        try {
            const response = await fetch('api/twitch.php?action=link');
            const data = await response.json();

            if (data.auth_url) {
                // Rediriger vers l'authentification Twitch
                window.location.href = data.auth_url;
            } else {
                throw new Error('URL d\'authentification non reçue');
            }
        } catch (error) {
            console.error('Erreur lors de la connexion Twitch:', error);
            alert('Erreur lors de la connexion avec Twitch. Veuillez réessayer.');
        }
    }

    async refreshTwitch() {
        const refreshBtn = document.getElementById('refreshTwitchBtn');
        const originalText = refreshBtn.innerHTML;
        
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualisation...';
        refreshBtn.disabled = true;

        try {
            await this.loadTwitchStatus();
            this.showMessage('Compte Twitch actualisé avec succès !', 'success');
        } catch (error) {
            console.error('Erreur lors de l\'actualisation Twitch:', error);
            this.showMessage('Erreur lors de l\'actualisation du compte Twitch', 'error');
        } finally {
            refreshBtn.innerHTML = originalText;
            refreshBtn.disabled = false;
        }
    }

    async unlinkTwitch() {
        if (!confirm('Êtes-vous sûr de vouloir délier votre compte Twitch ?')) {
            return;
        }

        try {
            const response = await fetch('api/twitch.php?action=unlink');
            const data = await response.json();

            if (data.success) {
                this.showMessage('Compte Twitch délié avec succès !', 'success');
                await this.loadTwitchStatus();
            } else {
                throw new Error('Erreur lors du déliage');
            }
        } catch (error) {
            console.error('Erreur lors du déliage Twitch:', error);
            this.showMessage('Erreur lors du déliage du compte Twitch', 'error');
        }
    }

    displayTwitchInfo(account) {
        // Afficher les informations du profil Twitch
        const twitchAvatar = document.getElementById('twitchAvatar');
        const twitchUsername = document.getElementById('twitchUsername');
        const twitchDisplayName = document.getElementById('twitchDisplayName');
        const twitchChannelLink = document.getElementById('twitchChannelLink');

        if (account.twitch_profile_image_url) {
            twitchAvatar.src = account.twitch_profile_image_url;
            twitchAvatar.style.display = 'block';
        } else {
            twitchAvatar.style.display = 'none';
        }

        twitchUsername.textContent = account.twitch_username;
        twitchDisplayName.textContent = account.twitch_display_name;
        twitchChannelLink.href = `https://twitch.tv/${account.twitch_username}`;

        // Charger les streams
        this.loadTwitchStreams(account.twitch_username);
    }

    async loadTwitchStreams(username) {
        const streamsList = document.getElementById('twitchStreamsList');
        streamsList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement des streams...</div>';

        try {
            const response = await fetch(`api/twitch.php?action=streams&limit=5`);
            const data = await response.json();

            if (data.streams && data.streams.length > 0) {
                // Filtrer les streams de cet utilisateur
                const userStreams = data.streams.filter(stream => 
                    stream.twitch_username.toLowerCase() === username.toLowerCase()
                );

                if (userStreams.length > 0) {
                    this.displayStreams(userStreams);
                } else {
                    streamsList.innerHTML = `
                        <div class="twitch-no-streams">
                            <i class="fas fa-video-slash"></i>
                            <p>Aucun stream en direct actuellement</p>
                        </div>
                    `;
                }
            } else {
                streamsList.innerHTML = `
                    <div class="twitch-no-streams">
                        <i class="fas fa-video-slash"></i>
                        <p>Aucun stream en direct actuellement</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des streams:', error);
            streamsList.innerHTML = `
                <div class="twitch-no-streams">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur lors du chargement des streams</p>
                </div>
            `;
        }
    }

    displayStreams(streams) {
        const streamsList = document.getElementById('twitchStreamsList');
        
        const streamsHTML = streams.map(stream => {
            const startTime = new Date(stream.stream_started_at);
            const duration = this.formatDuration(startTime);
            const thumbnailUrl = stream.stream_thumbnail_url.replace('{width}', '320').replace('{height}', '180');

            return `
                <div class="twitch-stream-item">
                    <div class="twitch-stream-thumbnail">
                        <img src="${thumbnailUrl}" alt="Stream thumbnail">
                    </div>
                    <div class="twitch-stream-info">
                        <div class="twitch-stream-title">${stream.stream_title}</div>
                        <div class="twitch-stream-game">${stream.stream_game_name || 'Jeu non spécifié'}</div>
                        <div class="twitch-stream-stats">
                            <div class="twitch-stream-viewers">
                                <i class="fas fa-eye"></i> ${this.formatViewers(stream.stream_viewer_count)}
                            </div>
                            <div class="twitch-stream-duration">
                                <i class="fas fa-clock"></i> ${duration}
                            </div>
                        </div>
                    </div>
                    <div class="twitch-stream-actions">
                        <a href="https://twitch.tv/${stream.twitch_username}" target="_blank" class="btn btn-primary">
                            <i class="fab fa-twitch"></i> Regarder
                        </a>
                    </div>
                </div>
            `;
        }).join('');

        streamsList.innerHTML = streamsHTML;
    }

    formatDuration(startTime) {
        return DateUtils.formatStreamDuration(startTime);
    }

    formatViewers(count) {
        if (count >= 1000000) {
            return (count / 1000000).toFixed(1) + 'M';
        } else if (count >= 1000) {
            return (count / 1000).toFixed(1) + 'K';
        } else {
            return count.toString();
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.getElementById('profileMessage');
        if (messageDiv) {
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';

            // Masquer le message après 5 secondes
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }
}

// Initialiser le gestionnaire Twitch quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    if (window.profileData && window.profileData.isOwnProfile) {
        window.twitchManager = new TwitchManager();
    }
}); 