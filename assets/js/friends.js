// Gestion des amis et demandes d'amis
class FriendsManager {
    constructor() {
        this.friendsList = document.getElementById('friendsList');
        this.receivedRequests = document.getElementById('receivedRequests');
        
        // Utiliser les données passées depuis PHP
        this.isOwnFriends = window.friendsData ? window.friendsData.isOwnFriends : true;
        this.currentUserId = window.friendsData ? window.friendsData.currentUserId : null;
        this.targetUserId = window.friendsData ? window.friendsData.targetUserId : null;
        
        this.init();
    }
    
    init() {
        if (this.friendsList) {
            this.loadFriends();
        }
        
        // Charger les demandes reçues seulement si c'est ses propres amis
        if (this.receivedRequests && this.isOwnFriends) {
            this.loadReceivedRequests();
        }
    }
    
    async loadFriends() {
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_friends',
                    user_id: this.targetUserId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayFriends(data.friends);
            } else {
                this.showFriendsError(data.message);
            }
            
        } catch (error) {
            this.showFriendsError('Erreur de connexion au serveur');
        }
    }
    
    async loadReceivedRequests() {
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_received_requests'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.displayReceivedRequests(data.requests);
            } else {
                this.showRequestsError(data.message);
            }
            
        } catch (error) {
            this.showRequestsError('Erreur de connexion au serveur');
        }
    }
    
    displayFriends(friends) {
        if (friends.length === 0) {
            const message = this.isOwnFriends 
                ? '<p>Vous n\'avez pas encore d\'amis</p><small>Recherchez des utilisateurs pour les ajouter</small>'
                : '<p>Cet utilisateur n\'a pas encore d\'amis</p>';
            
            this.friendsList.innerHTML = `
                <div class="empty">
                    <i class="fas fa-users"></i>
                    ${message}
                </div>
            `;
            return;
        }
        
        let html = '';
        friends.forEach(friend => {
            html += this.createFriendItem(friend);
        });
        
        this.friendsList.innerHTML = html;
    }
    
    displayReceivedRequests(requests) {
        if (requests.length === 0) {
            this.receivedRequests.innerHTML = `
                <div class="empty">
                    <i class="fas fa-inbox"></i>
                    <p>Aucune demande d'ami en attente</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        requests.forEach(request => {
            html += this.createRequestItem(request);
        });
        
        this.receivedRequests.innerHTML = html;
    }
    
    createFriendItem(friend) {
        const avatar = friend.avatar_url 
            ? `<img src="${this.escapeHtml(friend.avatar_url)}" alt="Avatar" class="friend-avatar">`
            : `<div class="friend-avatar-placeholder"><i class="fas fa-user"></i></div>`;
        
        const friendshipDate = new Date(friend.friendship_date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const displayName = this.getDisplayName(friend);
        
        // Actions différentes selon le type d'affichage
        const actions = this.isOwnFriends 
            ? `<button class="btn btn-sm btn-danger" onclick="friendsManager.removeFriend(${friend.id})">
                 <i class="fas fa-user-minus"></i> Retirer
               </button>`
            : `<a href="profile.php?user_id=${friend.id}" class="btn btn-sm btn-secondary">
                 <i class="fas fa-user"></i> Voir profil
               </a>`;
        
        return `
            <div class="friend-item" data-friend-id="${friend.id}">
                ${avatar}
                <div class="friend-info">
                    <div class="friend-name">${displayName}</div>
                    <div class="friend-details">
                        <span class="friend-since">Ami depuis le ${friendshipDate}</span>
                    </div>
                </div>
                <div class="friend-actions">
                    ${actions}
                </div>
            </div>
        `;
    }
    
    createRequestItem(request) {
        const avatar = request.avatar_url 
            ? `<img src="${this.escapeHtml(request.avatar_url)}" alt="Avatar" class="request-avatar">`
            : `<div class="request-avatar-placeholder"><i class="fas fa-user"></i></div>`;
        
        const requestDate = new Date(request.created_at).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const displayName = this.getDisplayName(request);
        
        return `
            <div class="request-item" data-request-id="${request.id}">
                ${avatar}
                <div class="request-info">
                    <div class="request-name">${displayName}</div>
                    <div class="request-details">
                        <span class="request-date">Demande reçue le ${requestDate}</span>
                    </div>
                </div>
                <div class="request-actions">
                    <button class="btn btn-sm btn-success" onclick="friendsManager.acceptRequest(${request.id})">
                        <i class="fas fa-check"></i> Accepter
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="friendsManager.rejectRequest(${request.id})">
                        <i class="fas fa-times"></i> Refuser
                    </button>
                </div>
            </div>
        `;
    }
    

    
    async removeFriend(friendId) {
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
                    friend_id: friendId
                })
            });
            
            const data = await response.json();
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.loadFriends();
                // Recharger aussi la recherche si elle est active
                if (window.userSearch) {
                    window.userSearch.performSearch();
                }
            }
            
        } catch (error) {
            this.showMessage('Erreur lors de la suppression', 'error');
        }
    }
    
    async acceptRequest(requestId) {
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'accept_request',
                    request_id: requestId
                })
            });
            
            const data = await response.json();
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.loadFriends();
                this.loadReceivedRequests();
                // Recharger aussi la recherche si elle est active
                if (window.userSearch) {
                    window.userSearch.performSearch();
                }
            }
            
        } catch (error) {
            this.showMessage('Erreur lors de l\'acceptation', 'error');
        }
    }
    
    async rejectRequest(requestId) {
        try {
            const response = await fetch('api/friends.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reject_request',
                    request_id: requestId
                })
            });
            
            const data = await response.json();
            this.showMessage(data.message, data.success ? 'success' : 'error');
            
            if (data.success) {
                this.loadReceivedRequests();
                // Recharger aussi la recherche si elle est active
                if (window.userSearch) {
                    window.userSearch.performSearch();
                }
            }
            
        } catch (error) {
            this.showMessage('Erreur lors du refus', 'error');
        }
    }
    
    showFriendsError(message) {
        this.friendsList.innerHTML = `
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i>
                ${this.escapeHtml(message)}
            </div>
        `;
    }
    
    showRequestsError(message) {
        this.receivedRequests.innerHTML = `
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i>
                ${this.escapeHtml(message)}
            </div>
        `;
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
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    getDisplayName(user) {
        // Utiliser le format d'affichage choisi par l'utilisateur
        const first_name = user.first_name || '';
        const last_name = user.last_name || '';
        const username = user.username || '';
        const display_format = user.display_format || 'full_with_pseudo';
        
        switch (display_format) {
            case 'full_name':
                // Prénom & Nom
                if (first_name && last_name) {
                    return `${this.escapeHtml(first_name)} ${this.escapeHtml(last_name)}`.trim();
                } else if (first_name) {
                    return this.escapeHtml(first_name);
                } else if (last_name) {
                    return this.escapeHtml(last_name);
                } else {
                    return this.escapeHtml(username || 'Utilisateur');
                }
                
            case 'first_name_only':
                // Juste Prénom
                if (first_name) {
                    return this.escapeHtml(first_name);
                } else if (username) {
                    return this.escapeHtml(username);
                } else {
                    return 'Utilisateur';
                }
                
            case 'last_name_only':
                // Juste Nom
                if (last_name) {
                    return this.escapeHtml(last_name);
                } else if (username) {
                    return this.escapeHtml(username);
                } else {
                    return 'Utilisateur';
                }
                
            case 'username_only':
                // Juste Pseudo
                if (username) {
                    return this.escapeHtml(username);
                } else if (first_name) {
                    return this.escapeHtml(first_name);
                } else {
                    return 'Utilisateur';
                }
                
            case 'full_with_pseudo':
            default:
                // Prénom 'Pseudo' Nom
                if (first_name && last_name) {
                    const pseudo = username ? `'${this.escapeHtml(username)}'` : '';
                    return `${this.escapeHtml(first_name)} ${pseudo} ${this.escapeHtml(last_name)}`.trim();
                } else if (first_name) {
                    return this.escapeHtml(first_name);
                } else if (last_name) {
                    return this.escapeHtml(last_name);
                } else if (username) {
                    return this.escapeHtml(username);
                } else {
                    return 'Utilisateur';
                }
        }
    }
}

// Rendre la classe accessible globalement
window.friendsManager = null;

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si on est sur la page dashboard (utilisateur connecté)
    const friendsSection = document.querySelector('.friends-section');
    if (friendsSection) {
        window.friendsManager = new FriendsManager();
    }
}); 