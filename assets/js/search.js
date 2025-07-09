// Gestion de la recherche d'utilisateurs
class UserSearch {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.searchBtn = document.getElementById('searchBtn');
        this.searchResults = document.getElementById('searchResults');
        this.searchLoading = document.getElementById('searchLoading');
        this.searchTimeout = null;
        this.isSearching = false;
        
        this.init();
    }
    
    init() {
        if (this.searchInput && this.searchBtn && this.searchResults) {
            this.setupEventListeners();
            this.showEmptyState();
        }
    }
    
    setupEventListeners() {
        // Recherche au clic sur le bouton
        this.searchBtn.addEventListener('click', () => {
            this.performSearch();
        });
        
        // Recherche à la frappe (avec délai)
        this.searchInput.addEventListener('input', () => {
            this.debounceSearch();
        });
        
        // Recherche avec la touche Entrée
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.performSearch();
            }
        });
        
        // Focus sur l'input de recherche
        this.searchInput.addEventListener('focus', () => {
            this.searchInput.parentNode.style.borderColor = 'var(--primary-color)';
        });
        
        this.searchInput.addEventListener('blur', () => {
            this.searchInput.parentNode.style.borderColor = '';
        });
    }
    
    debounceSearch() {
        clearTimeout(this.searchTimeout);
        
        const query = this.searchInput.value.trim();
        
        if (query.length === 0) {
            this.showEmptyState();
            return;
        }
        
        if (query.length < 1) {
            this.showEmptyState();
            return;
        }
        
        this.searchTimeout = setTimeout(() => {
            this.performSearch();
        }, 300); // Délai réduit à 300ms pour plus de réactivité
    }
    
    async performSearch() {
        const query = this.searchInput.value.trim();
        
        if (query.length < 1) {
            this.showEmptyState();
            return;
        }
        
        if (this.isSearching) return;
        
        this.isSearching = true;
        this.showLoadingState();
        
        try {
            const response = await fetch('api/search.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'search_users',
                    query: query
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayResults(data.users, data.count);
            } else {
                this.showError(data.message);
            }
            
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
            this.showError('Erreur de connexion au serveur');
        } finally {
            this.isSearching = false;
        }
    }
    
    displayResults(users, count) {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'none';
        }
        
        if (users.length === 0) {
            this.showNoResults();
            return;
        }
        
        let html = `<div class="search-info">${count} utilisateur(s) trouvé(s)</div>`;
        
        users.forEach(user => {
            html += this.createUserCard(user);
        });
        
        this.searchResults.innerHTML = html;
        this.searchResults.className = 'search-results';
    }
    
    createUserCard(user) {
        const avatar = user.avatar_url 
            ? `<img src="${this.escapeHtml(user.avatar_url)}" alt="Avatar" class="user-avatar">`
            : `<div class="user-avatar-placeholder"><i class="fas fa-user"></i></div>`;
        
        const joinDate = DateUtils.formatDateParis(user.created_at, 'long');
        
        const friendButton = this.createFriendButton(user);
        const displayName = this.getDisplayName(user);
        const searchTerm = this.searchInput.value.trim();
        const highlightedName = this.highlightSearchTerm(displayName, searchTerm);
        
        return `
            <div class="user-card" data-user-id="${user.id}">
                <a href="profile.php?user_id=${user.id}" class="user-card-link">
                    ${avatar}
                    <div class="user-info">
                        <div class="user-name">${highlightedName}</div>
                        <div class="user-details">
                            <span class="user-join-date">Inscrit le ${joinDate}</span>
                        </div>
                    </div>
                </a>
                <div class="user-actions">
                    ${friendButton}
                </div>
            </div>
        `;
    }
    
    createFriendButton(user) {
        switch (user.relationship_status) {
            case 'friends':
                return `<button class="btn btn-sm btn-danger" onclick="userSearch.removeFriend(${user.id})">
                    <i class="fas fa-user-minus"></i> Retirer
                </button>`;
            case 'request_sent':
                return `<button class="btn btn-sm btn-secondary" disabled>
                    <i class="fas fa-clock"></i> En attente
                </button>`;
            case 'request_received':
                return `<div class="btn-group">
                    <button class="btn btn-sm btn-success" onclick="userSearch.acceptRequest(${user.id})">
                        <i class="fas fa-check"></i> Accepter
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="userSearch.rejectRequest(${user.id})">
                        <i class="fas fa-times"></i> Refuser
                    </button>
                </div>`;
            default:
                return `<button class="btn btn-sm btn-primary" onclick="userSearch.sendFriendRequest(${user.id})">
                    <i class="fas fa-user-plus"></i> Ajouter
                </button>`;
        }
    }
    

    
    showLoadingState() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'block';
        }
        this.searchResults.innerHTML = '';
        this.searchResults.className = 'search-results loading';
    }
    
    showEmptyState() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'none';
        }
        this.searchResults.innerHTML = `
            <div class="search-info">
                <div class="search-tips">
                    <h4>🔍 Rechercher des amis :</h4>
                    <ul>
                        <li>Tapez un <strong>pseudo</strong> pour commencer la recherche</li>
                        <li>La recherche est insensible à la casse</li>
                        <li>Vous pouvez taper seulement une partie du pseudo</li>
                        <li>Les résultats s'affichent automatiquement</li>
                    </ul>
                </div>
            </div>
        `;
        this.searchResults.className = 'search-results empty';
    }
    
    showNoResults() {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'none';
        }
        this.searchResults.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>Aucun utilisateur trouvé</p>
                <small>Essayez avec un autre terme de recherche</small>
            </div>
        `;
        this.searchResults.className = 'search-results';
    }
    
    showError(message) {
        if (this.searchLoading) {
            this.searchLoading.style.display = 'none';
        }
        this.searchResults.innerHTML = `
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i>
                ${this.escapeHtml(message)}
            </div>
        `;
        this.searchResults.className = 'search-results';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    getDisplayName(user) {
        // Utiliser uniquement le pseudo
        const username = user.username || 'Utilisateur';
        return this.escapeHtml(username);
    }
    
    highlightSearchTerm(text, searchTerm) {
        if (!searchTerm) return text;
        
        const regex = new RegExp(`(${this.escapeRegex(searchTerm)})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    

    
    // Méthodes pour gérer les amis
    async sendFriendRequest(userId) {
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
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                // Recharger les résultats pour mettre à jour les boutons
                this.performSearch();
            }
            
        } catch (error) {
            this.showMessage('Erreur lors de l\'envoi de la demande', 'error');
        }
    }
    
    async acceptRequest(userId) {
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'accept_request',
                    request_id: userId
                })
            });
            
            const data = await response.json();
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.performSearch();
            }
            
        } catch (error) {
            this.showMessage('Erreur lors de l\'acceptation', 'error');
        }
    }
    
    async rejectRequest(userId) {
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reject_request',
                    request_id: userId
                })
            });
            
            const data = await response.json();
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.performSearch();
            }
            
        } catch (error) {
            this.showMessage('Erreur lors du refus', 'error');
        }
    }
    
    async removeFriend(userId) {
        if (!confirm('Êtes-vous sûr de vouloir retirer cet ami ?')) {
            return;
        }
        
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove_friend',
                    friend_id: userId
                })
            });
            
            const data = await response.json();
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.performSearch();
            }
            
        } catch (error) {
            this.showMessage('Erreur lors de la suppression', 'error');
        }
    }
    
    showMessage(message, type = 'success') {
        // Créer un message temporaire
        const messageEl = document.createElement('div');
        messageEl.className = `message ${type}`;
        messageEl.textContent = message;
        messageEl.style.position = 'fixed';
        messageEl.style.top = '20px';
        messageEl.style.right = '20px';
        messageEl.style.zIndex = '1000';
        messageEl.style.animation = 'slideIn 0.3s ease-out';
        
        document.body.appendChild(messageEl);
        
        setTimeout(() => {
            messageEl.remove();
        }, 3000);
    }
}

// Rendre la classe accessible globalement pour les boutons
window.userSearch = null;

// Initialisation de la recherche quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si on est sur la page dashboard (utilisateur connecté)
    const searchSection = document.querySelector('.search-section');
    if (searchSection) {
        window.userSearch = new UserSearch();
    }
});

// Amélioration de l'expérience utilisateur
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // Focus automatique sur le champ de recherche
        setTimeout(() => {
            searchInput.focus();
        }, 100);
        
        // Auto-complétion visuelle
        searchInput.addEventListener('input', (e) => {
            const value = e.target.value;
            if (value.length >= 2) {
                e.target.style.borderColor = 'var(--success-color)';
            } else {
                e.target.style.borderColor = '';
            }
        });
    }
}); 