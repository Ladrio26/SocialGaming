// Gestionnaire de notifications
class NotificationManager {
    constructor() {
        this.notificationsBtn = document.getElementById('notificationsBtn');
        this.notificationBadge = document.getElementById('notificationBadge');
        this.notificationDropdown = document.getElementById('notificationDropdown');
        this.notificationList = document.getElementById('notificationList');
        this.markAllReadBtn = document.getElementById('markAllReadBtn');
        this.viewAllNotifications = document.getElementById('viewAllNotifications');
        this.deleteAllBtn = document.getElementById('deleteAllBtn');
        
        this.unreadCount = 0;
        this.pollingInterval = null;
        
        this.init();
    }
    
    init() {
        if (!this.notificationsBtn) return;
        
        // Gestionnaire pour ouvrir/fermer le dropdown
        this.notificationsBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Fermer le dropdown en cliquant ailleurs
        document.addEventListener('click', (e) => {
            if (!this.notificationDropdown.contains(e.target) && !this.notificationsBtn.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Marquer toutes comme lues
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }
        
        // Bouton tout supprimer
        if (this.deleteAllBtn) {
            this.deleteAllBtn.addEventListener('click', () => {
                this.deleteAllNotifications();
            });
        }
        
        // Charger les notifications initiales
        this.loadNotifications();
        this.updateUnreadCount();
        
        // Démarrer le polling pour les nouvelles notifications
        this.startPolling();
        
        // Ajouter le bouton de test (pour le développement)
        this.addTestButton();
    }
    
    // Ouvrir/fermer le dropdown
    toggleDropdown() {
        if (this.notificationDropdown.classList.contains('active')) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        this.notificationDropdown.classList.add('active');
        this.loadNotifications();
    }
    
    closeDropdown() {
        this.notificationDropdown.classList.remove('active');
    }
    
    // Charger les notifications
    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=list&limit=10');
            const data = await response.json();
            
            if (data.success) {
                this.displayNotifications(data.notifications);
            } else {
                this.showError('Erreur lors du chargement des notifications');
            }
        } catch (error) {
            this.showError('Erreur de connexion');
        }
    }
    
    // Afficher les notifications
    displayNotifications(notifications) {
        if (!this.notificationList) return;
        
        if (notifications.length === 0) {
            this.notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Aucune notification</p>
                </div>
            `;
            return;
        }
        
        this.notificationList.innerHTML = notifications.map(notification => {
            const timeAgo = this.getTimeAgo(notification.created_at);
            const unreadClass = !notification.is_read ? 'unread' : '';
            
            return `
                <div class="notification-item ${unreadClass}" data-id="${notification.id}">
                    <div class="notification-item-header">
                        <h5 class="notification-title">${this.escapeHtml(notification.title)}</h5>
                        <span class="notification-time">${timeAgo}</span>
                    </div>
                    <p class="notification-message">${this.escapeHtml(notification.message)}</p>
                    <div class="notification-actions">
                        ${!notification.is_read ? `
                            <button class="btn btn-sm btn-primary mark-read-btn" data-id="${notification.id}">
                                <i class="fas fa-check"></i> Marquer comme lu
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-danger delete-notification-btn" data-id="${notification.id}">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        // Ajouter les gestionnaires d'événements
        this.addNotificationEventListeners();
    }
    
    // Ajouter les gestionnaires d'événements aux notifications
    addNotificationEventListeners() {
        // Marquer comme lu
        const markReadBtns = this.notificationList.querySelectorAll('.mark-read-btn');
        markReadBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const notificationId = btn.dataset.id;
                this.markAsRead(notificationId);
            });
        });
        
        // Supprimer notification
        const deleteBtns = this.notificationList.querySelectorAll('.delete-notification-btn');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const notificationId = btn.dataset.id;
                this.deleteNotification(notificationId);
            });
        });
    }
    
    // Marquer une notification comme lue
    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    notification_id: notificationId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Mettre à jour l'affichage
                const notificationItem = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                    const markReadBtn = notificationItem.querySelector('.mark-read-btn');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                }
                
                // Mettre à jour le compteur
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Erreur lors du marquage comme lu:', error);
        }
    }
    
    // Marquer toutes les notifications comme lues
    async markAllAsRead() {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_all_read'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Mettre à jour l'affichage
                this.notificationList.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                
                this.notificationList.querySelectorAll('.mark-read-btn').forEach(btn => {
                    btn.remove();
                });
                
                // Mettre à jour le compteur
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Erreur lors du marquage de toutes comme lues:', error);
        }
    }
    
    // Supprimer une notification
    async deleteNotification(notificationId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
            return;
        }
        
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    notification_id: notificationId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Supprimer l'élément du DOM
                const notificationItem = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.remove();
                }
                
                // Mettre à jour le compteur
                this.updateUnreadCount();
                
                // Si plus de notifications, afficher le message vide
                if (this.notificationList.children.length === 0) {
                    this.notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Aucune notification</p>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
        }
    }
    
    // Supprimer toutes les notifications
    async deleteAllNotifications() {
        if (!confirm('Voulez-vous vraiment supprimer toutes vos notifications ?')) return;
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_all' })
            });
            const data = await response.json();
            if (data.success) {
                this.notificationList.innerHTML = `<div class="notification-empty"><i class="fas fa-bell-slash"></i><p>Aucune notification</p></div>`;
                this.updateUnreadCount();
            }
        } catch (error) {
            this.showError('Erreur lors de la suppression');
        }
    }
    
    // Mettre à jour le compteur de notifications non lues
    async updateUnreadCount() {
        try {
            const response = await fetch('api/notifications.php?action=count');
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.count;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du compteur:', error);
        }
    }
    
    // Mettre à jour l'affichage du badge
    updateBadge() {
        if (!this.notificationBadge) return;
        
        if (this.unreadCount > 0) {
            this.notificationBadge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            this.notificationBadge.style.display = 'flex';
        } else {
            this.notificationBadge.style.display = 'none';
        }
    }
    
    // Démarrer le polling pour les nouvelles notifications
    startPolling() {
        // Vérifier toutes les 30 secondes
        this.pollingInterval = setInterval(() => {
            this.updateUnreadCount();
        }, 30000);
    }
    
    // Arrêter le polling
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
    
    // Calculer le temps écoulé
    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'À l\'instant';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `Il y a ${minutes} min`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `Il y a ${hours}h`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `Il y a ${days}j`;
        }
    }
    
    // Échapper le HTML pour éviter les injections
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Afficher une erreur
    showError(message) {
        if (this.notificationList) {
            this.notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }
    
    // Créer une notification de test (pour le développement)
    createTestNotification(type = 'test') {
        fetch('api/test_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ type: type })
        }).then(response => response.json()).then(data => {
            if (data.success) {
                this.updateUnreadCount();
                if (this.notificationDropdown.classList.contains('active')) {
                    this.loadNotifications();
                }
            }
        });
    }
    
    // Ajouter un bouton de test dans le footer (pour le développement)
    addTestButton() {
        if (this.notificationFooter) {
            const testBtn = document.createElement('button');
            testBtn.className = 'btn btn-sm btn-warning';
            testBtn.innerHTML = '<i class="fas fa-vial"></i> Test';
            testBtn.style.marginTop = '5px';
            testBtn.onclick = () => {
                const types = ['test', 'friend_request', 'friend_accepted', 'profile_visit', 'common_game'];
                const randomType = types[Math.floor(Math.random() * types.length)];
                this.createTestNotification(randomType);
            };
            this.notificationFooter.appendChild(testBtn);
        }
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    new NotificationManager();
}); 