/**
 * Gestionnaire de mises à jour en temps réel
 * Utilise un polling intelligent pour détecter les changements
 */
class RealtimeUpdates {
    constructor() {
        this.lastCheck = Math.floor(Date.now() / 1000);
        this.currentCategoryId = this.getCurrentCategoryId();
        this.isActive = true;
        this.checkInterval = null;
        this.updateCallbacks = new Map();
        this.showPopups = false; // Désactiver les popups par défaut
        
        // Démarrer le polling
        this.startPolling();
        
        // Arrêter le polling quand la page n'est plus visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
        
        // Arrêter le polling avant que l'utilisateur quitte la page
        window.addEventListener('beforeunload', () => {
            this.stopPolling();
        });
    }
    
    /**
     * Démarrer le polling
     */
    startPolling() {
        if (this.checkInterval) return;
        
        this.checkInterval = setInterval(() => {
            this.checkForUpdates();
        }, 5000); // Vérifier toutes les 5 secondes
    }
    
    /**
     * Arrêter le polling
     */
    stopPolling() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }
    
    /**
     * Vérifier les mises à jour
     */
    async checkForUpdates() {
        if (!this.isActive) return;
        
        try {
            const url = `api/check_updates.php?last_check=${this.lastCheck}&category_id=${this.currentCategoryId}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.has_updates) {
                this.handleUpdates(data.updates);
                this.lastCheck = data.timestamp;
            }
        } catch (error) {
            console.log('Erreur lors de la vérification des mises à jour:', error);
        }
    }
    
    /**
     * Gérer les mises à jour reçues
     */
    handleUpdates(updates) {
        console.log('Mises à jour détectées:', updates);
        
        // 1. Nouveaux posts dans la catégorie actuelle
        if (updates.category_posts) {
            this.showCategoryUpdateNotification(updates.category_posts);
            this.triggerCallback('category_posts', updates.category_posts);
        }
        
        // 2. Nouvelles catégories non lues
        if (updates.unread_categories) {
            this.updateUnreadBadges(updates.unread_categories);
            this.triggerCallback('unread_categories', updates.unread_categories);
        }
        
        // 3. Nouveaux posts récents (page d'accueil)
        if (updates.recent_posts) {
            this.showRecentPostsUpdateNotification(updates.recent_posts);
            this.triggerCallback('recent_posts', updates.recent_posts);
        }
        
        // 4. Nouvelles demandes d'amis
        if (updates.friend_requests) {
            this.showFriendRequestNotification(updates.friend_requests);
            this.triggerCallback('friend_requests', updates.friend_requests);
        }
        
        // 5. Nouvelles notifications
        if (updates.notifications) {
            this.showNotificationBadge(updates.notifications);
            this.triggerCallback('notifications', updates.notifications);
        }
    }
    
    /**
     * Afficher une notification pour les nouveaux posts de catégorie
     */
    showCategoryUpdateNotification(count) {
        if (this.currentCategoryId > 0) {
            // Mise à jour fluide des posts de catégorie
            this.updateCategoryPosts();
        }
    }
    
    /**
     * Afficher une notification pour les nouveaux posts récents
     */
    showRecentPostsUpdateNotification(count) {
        // Mise à jour fluide des posts récents
        this.updateRecentPosts();
    }
    
    /**
     * Afficher une notification pour les nouvelles demandes d'amis
     */
    showFriendRequestNotification(count) {
        // Mettre à jour le badge automatiquement (pas de popup)
        this.showNotificationBadge(count);
        
        // Optionnel : notification discrète
        this.showNotification(
            `${count} nouvelle(s) demande(s) d'ami(s)`,
            'Badge mis à jour',
            () => {
                const friendRequestsLink = document.querySelector('a[href*="friend_requests"]');
                if (friendRequestsLink) {
                    friendRequestsLink.click();
                }
            }
        );
    }
    
    /**
     * Mettre à jour les badges de catégories non lues
     */
    updateUnreadBadges(unreadCategories) {
        unreadCategories.forEach(category => {
            const categoryElement = document.querySelector(`[data-category-id="${category.category_id}"]`);
            if (categoryElement) {
                let badge = categoryElement.querySelector('.unread-badge');
                if (!badge) {
                    badge = document.createElement('div');
                    badge.className = 'unread-badge';
                    categoryElement.appendChild(badge);
                }
                badge.textContent = category.unread_count;
            }
        });
    }
    
    /**
     * Afficher le badge de notifications
     */
    showNotificationBadge(count) {
        const notificationLink = document.querySelector('a[href*="notifications"]');
        if (notificationLink) {
            let badge = notificationLink.querySelector('.notification-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                notificationLink.appendChild(badge);
            }
            badge.textContent = count;
        }
    }
    
    /**
     * Afficher une notification toast
     */
    showNotification(title, message, onClick) {
        // Ne pas afficher de popup si désactivé
        if (!this.showPopups) {
            return;
        }
        
        // Créer la notification
        const notification = document.createElement('div');
        notification.className = 'realtime-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close">&times;</button>
        `;
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Gestionnaires d'événements
        notification.addEventListener('click', (e) => {
            if (!e.target.classList.contains('notification-close')) {
                onClick();
            }
        });
        
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.hideNotification(notification);
        });
        
        // Auto-fermeture après 5 secondes
        setTimeout(() => {
            this.hideNotification(notification);
        }, 5000);
    }
    
    /**
     * Masquer une notification
     */
    hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    /**
     * Obtenir l'ID de la catégorie actuelle
     */
    getCurrentCategoryId() {
        const urlParams = new URLSearchParams(window.location.search);
        return parseInt(urlParams.get('id')) || 0;
    }
    
    /**
     * Enregistrer un callback pour un type de mise à jour
     */
    onUpdate(type, callback) {
        this.updateCallbacks.set(type, callback);
    }
    
    /**
     * Déclencher un callback
     */
    triggerCallback(type, data) {
        const callback = this.updateCallbacks.get(type);
        if (callback) {
            callback(data);
        }
    }
    
    /**
     * Forcer une vérification immédiate
     */
    forceCheck() {
        this.checkForUpdates();
    }
    
    /**
     * Désactiver les mises à jour
     */
    disable() {
        this.isActive = false;
        this.stopPolling();
    }
    
    /**
     * Réactiver les mises à jour
     */
    enable() {
        this.isActive = true;
        this.startPolling();
    }
    
    /**
     * Mise à jour fluide des posts de catégorie
     */
    async updateCategoryPosts() {
        try {
            const url = `api/get_updated_content.php?type=category_posts&category_id=${this.currentCategoryId}&last_check=${this.lastCheck}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.posts.length > 0) {
                // Ajouter les nouveaux posts avec animation
                this.addNewCategoryPosts(data.posts);
            }
        } catch (error) {
            console.log('Erreur lors de la mise à jour des posts de catégorie:', error);
        }
    }
    
    /**
     * Mise à jour fluide des posts récents
     */
    async updateRecentPosts() {
        try {
            const url = `api/get_updated_content.php?type=recent_posts&last_check=${this.lastCheck}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.posts.length > 0) {
                // Mettre à jour les posts récents avec animation
                this.addNewRecentPosts(data.posts);
            }
        } catch (error) {
            console.log('Erreur lors de la mise à jour des posts récents:', error);
        }
    }
    
    /**
     * Ajouter de nouveaux posts de catégorie avec animation
     */
    addNewCategoryPosts(newPosts) {
        const postsList = document.getElementById('postsList');
        if (!postsList) return;
        
        // Supprimer l'état de chargement s'il existe
        const loadingState = postsList.querySelector('.loading-state');
        if (loadingState) {
            loadingState.remove();
        }
        
        // Supprimer l'état vide s'il existe
        const emptyState = postsList.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }
        
        // Ajouter chaque nouveau post en haut avec animation
        newPosts.reverse().forEach(post => {
            const postHtml = this.createCategoryPostHTML(post);
            const postElement = document.createElement('div');
            postElement.innerHTML = postHtml;
            postElement.firstElementChild.classList.add('new-post-animation');
            
            postsList.insertBefore(postElement.firstElementChild, postsList.firstChild);
            
            // Animation d'entrée
            setTimeout(() => {
                postElement.firstElementChild.classList.remove('new-post-animation');
            }, 500);
        });
    }
    
    /**
     * Ajouter de nouveaux posts récents avec animation
     */
    addNewRecentPosts(newPosts) {
        // Si le gestionnaire de posts récents existe, l'utiliser
        if (window.recentPostsManager) {
            newPosts.forEach(post => {
                window.recentPostsManager.addNewPost(post);
            });
        } else {
            // Sinon, recharger la section des posts récents
            const recentPostsContainer = document.querySelector('.recent-posts-container');
            if (recentPostsContainer) {
                this.loadRecentPostsSection();
            }
        }
    }
    
    /**
     * Créer le HTML d'un post de catégorie
     */
    createCategoryPostHTML(post) {
        const imageHtml = post.image_url ? 
            `<img src="${post.image_url}" class="post-image" alt="Image" onclick="openImageModal('${post.image_url}')">` : '';
        
        const deleteButton = post.can_delete ? 
            `<div class="post-actions"><button class="btn-delete" onclick="deletePost(${post.id})"><i class="fas fa-trash"></i></button></div>` : '';
        
        return `
            <div class="category-post" data-post-id="${post.id}">
                ${imageHtml}
                <div class="post-content">
                    <div class="post-header">
                        <div class="post-author">${post.author}</div>
                        <div class="post-date">${post.created_at}</div>
                    </div>
                    <div class="post-text">${post.content ? post.content.replace(/\n/g, '<br>') : ''}</div>
                </div>
                ${deleteButton}
            </div>
        `;
    }
    
    /**
     * Charger la section des posts récents
     */
    async loadRecentPostsSection() {
        try {
            const response = await fetch('api/recent_posts.php');
            const data = await response.json();
            
            if (data.success) {
                const container = document.querySelector('.recent-posts-container');
                if (container && window.recentPostsManager) {
                    window.recentPostsManager.updatePosts(data.posts);
                }
            }
        } catch (error) {
            console.log('Erreur lors du chargement des posts récents:', error);
        }
    }
}

// Créer l'instance globale
window.realtimeUpdates = new RealtimeUpdates();

/**
 * Fonction utilitaire pour activer/désactiver les popups
 */
function toggleRealtimePopups(enable) {
    if (window.realtimeUpdates) {
        window.realtimeUpdates.showPopups = enable;
    }
}

/**
 * Fonction utilitaire pour activer/désactiver les mises à jour
 */
function toggleRealtimeUpdates(enable) {
    if (window.realtimeUpdates) {
        if (enable) {
            window.realtimeUpdates.enable();
        } else {
            window.realtimeUpdates.disable();
        }
    }
} 