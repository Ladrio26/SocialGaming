// Gestion des streams Twitch en direct sur la page principale
class TwitchLiveManager {
    constructor() {
        this.init();
    }

    init() {
        // Charger les streams en direct au chargement de la page
        this.loadLiveStreams();
        
        // Actualiser les streams toutes les 5 minutes
        setInterval(() => {
            this.loadLiveStreams();
        }, 5 * 60 * 1000);
    }

    async loadLiveStreams() {
        const streamsContainer = document.getElementById('twitchLiveStreams');

        if (!streamsContainer) return;

        try {
            const response = await fetch('api/twitch.php?action=streams&limit=12');
            const data = await response.json();

            if (data.streams && data.streams.length > 0) {
                this.displayLiveStreams(data.streams);
                streamsContainer.style.display = 'flex';
            } else {
                streamsContainer.style.display = 'none';
            }
        } catch (error) {
            console.error('Erreur lors du chargement des streams en direct:', error);
            streamsContainer.style.display = 'none';
        }
    }

    displayLiveStreams(streams) {
        const streamsContainer = document.getElementById('twitchLiveStreams');
        
        const streamsHTML = streams.map(stream => {
            const startTime = new Date(stream.stream_started_at);
            const duration = this.formatDuration(startTime);
            const thumbnailUrl = stream.stream_thumbnail_url.replace('{width}', '320').replace('{height}', '180');
            const viewerCount = this.formatViewers(stream.stream_viewer_count);

            return `
                <div class="twitch-live-stream-card">
                    <div class="twitch-live-stream-thumbnail">
                        <img src="${thumbnailUrl}" alt="Stream thumbnail" loading="lazy">
                        <div class="twitch-live-stream-live-badge">
                            <i class="fas fa-circle"></i> EN DIRECT
                        </div>
                        <div class="twitch-live-stream-viewers">
                            <i class="fas fa-eye"></i> ${viewerCount}
                        </div>
                    </div>
                    <div class="twitch-live-stream-content">
                        <div class="twitch-live-stream-title">${stream.stream_title}</div>
                        <div class="twitch-live-stream-game">
                            <i class="fas fa-gamepad"></i> ${stream.stream_game_name || 'Jeu non spécifié'}
                        </div>
                        <div class="twitch-live-stream-streamer">
                            <div class="twitch-live-stream-streamer-avatar">
                                <img src="${stream.twitch_profile_image_url || 'assets/images/default-avatar.png'}" alt="${stream.twitch_display_name}">
                            </div>
                            <div class="twitch-live-stream-streamer-name">${stream.twitch_display_name}</div>
                        </div>
                        <div class="twitch-live-stream-actions">
                            <a href="https://twitch.tv/${stream.twitch_username}" target="_blank" class="btn btn-primary">
                                <i class="fab fa-twitch"></i> Regarder
                            </a>
                            <button class="btn btn-secondary" onclick="this.showStreamerProfile('${stream.twitch_username}')">
                                <i class="fas fa-user"></i> Profil
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        streamsContainer.innerHTML = streamsHTML;
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

    showStreamerProfile(username) {
        // Rediriger vers le profil Twitch du streamer
        window.open(`https://twitch.tv/${username}`, '_blank');
    }
}

// Initialiser le gestionnaire des streams en direct
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si l'utilisateur est connecté et si la section existe
    const twitchSidebar = document.querySelector('.twitch-sidebar');
    if (twitchSidebar) {
        window.twitchLiveManager = new TwitchLiveManager();
    }
}); 