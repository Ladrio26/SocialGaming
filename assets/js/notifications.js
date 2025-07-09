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
            const response = await fetch('api/notifications.php?action=list&limit=10', {
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            // Vérifier le code de statut HTTP
            if (!response.ok) {
                if (response.status === 401) {
                    this.showError('Session expirée. Veuillez vous reconnecter.');
                    // Rediriger vers la page de connexion après un délai
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    this.showError(`Erreur serveur: ${response.status}`);
                }
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.displayNotifications(data.notifications);
            } else {
                this.showError(data.error || 'Erreur lors du chargement des notifications');
            }
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            this.showError('Erreur de connexion au serveur');
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
            
            // Actions spécifiques selon le type de notification
            let specificActions = '';
            
            if (notification.type === 'friend_request' && !notification.is_read) {
                // Extraire l'ID de l'expéditeur depuis les données JSON
                let notificationData = {};
                if (notification.data) {
                    if (typeof notification.data === 'string') {
                        try {
                            notificationData = JSON.parse(notification.data);
                        } catch (e) {
                            notificationData = {};
                        }
                    } else if (typeof notification.data === 'object') {
                        notificationData = notification.data;
                    }
                }
                const senderId = notificationData.sender_id;
                
                specificActions = `
                    <div class="notification-friend-actions">
                        <button class="btn btn-sm btn-success accept-friend-btn" data-notification-id="${notification.id}" data-sender-id="${senderId}">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                        <button class="btn btn-sm btn-danger reject-friend-btn" data-notification-id="${notification.id}" data-sender-id="${senderId}">
                            <i class="fas fa-times"></i> Refuser
                        </button>
                    </div>
                `;
            }
            
            // Déterminer l'ID de l'utilisateur concerné selon le type de notification
            let targetUserId = null;
            if (notification.type === 'friend_request') {
                let notificationData = {};
                if (notification.data) {
                    if (typeof notification.data === 'string') {
                        try {
                            notificationData = JSON.parse(notification.data);
                        } catch (e) {
                            notificationData = {};
                        }
                    } else if (typeof notification.data === 'object') {
                        notificationData = notification.data;
                    }
                }
                targetUserId = notificationData.sender_id;
            } else if (notification.type === 'friend_accepted') {
                let notificationData = {};
                if (notification.data) {
                    if (typeof notification.data === 'string') {
                        try {
                            notificationData = JSON.parse(notification.data);
                        } catch (e) {
                            notificationData = {};
                        }
                    } else if (typeof notification.data === 'object') {
                        notificationData = notification.data;
                    }
                }
                // Pour friend_accepted, on peut utiliser friend_id ou chercher par friend_name
                targetUserId = notificationData.friend_id;
                if (!targetUserId && notificationData.friend_name) {
                    // Si pas d'ID, on peut chercher l'utilisateur par son nom
                    // Pour l'instant, on ne rendra pas cliquable si pas d'ID
                    targetUserId = null;
                }
            } else if (notification.type === 'profile_visit') {
                let notificationData = {};
                if (notification.data) {
                    if (typeof notification.data === 'string') {
                        try {
                            notificationData = JSON.parse(notification.data);
                        } catch (e) {
                            notificationData = {};
                        }
                    } else if (typeof notification.data === 'object') {
                        notificationData = notification.data;
                    }
                }
                targetUserId = notificationData.visitor_id;
            }

            return `
                <div class="notification-item ${unreadClass}" data-id="${notification.id}" data-type="${notification.type}" ${targetUserId ? `data-target-user="${targetUserId}"` : ''}>
                    <div class="notification-content" ${targetUserId ? 'style="cursor: pointer;"' : ''}>
                        <div class="notification-item-header">
                            <h5 class="notification-title">${this.escapeHtml(notification.title)}</h5>
                            <span class="notification-time">${timeAgo}</span>
                        </div>
                        <p class="notification-message">${this.escapeHtml(notification.message)}</p>
                    </div>
                    ${specificActions}
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
        
        // Accepter demande d'ami
        const acceptFriendBtns = this.notificationList.querySelectorAll('.accept-friend-btn');
        acceptFriendBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const notificationId = btn.dataset.notificationId;
                const senderId = btn.dataset.senderId;
                this.acceptFriendRequest(notificationId, senderId);
            });
        });
        
        // Refuser demande d'ami
        const rejectFriendBtns = this.notificationList.querySelectorAll('.reject-friend-btn');
        rejectFriendBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const notificationId = btn.dataset.notificationId;
                const senderId = btn.dataset.senderId;
                this.rejectFriendRequest(notificationId, senderId);
            });
        });

        // Navigation vers le profil utilisateur
        const notificationItems = this.notificationList.querySelectorAll('.notification-item[data-target-user]');
        console.log('Notifications cliquables trouvées:', notificationItems.length);
        
        notificationItems.forEach(item => {
            const notificationContent = item.querySelector('.notification-content');
            const targetUserId = item.dataset.targetUser;
            console.log('Notification cliquable:', targetUserId);
            
            if (notificationContent) {
                notificationContent.addEventListener('click', (e) => {
                    console.log('Clic sur notification, targetUserId:', targetUserId);
                    
                    // Ne pas déclencher si on clique sur un bouton d'action
                    if (e.target.closest('.notification-actions') || e.target.closest('.notification-friend-actions')) {
                        console.log('Clic sur bouton d\'action, navigation annulée');
                        return;
                    }
                    
                    if (targetUserId) {
                        console.log('Navigation vers profile.php?user_id=' + targetUserId);
                        window.location.href = `profile.php?user_id=${targetUserId}`;
                    }
                });
            }
        });
    }
    
    // Marquer une notification comme lue
    async markAsRead(notificationId) {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                credentials: 'same-origin',
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
                credentials: 'same-origin',
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
                credentials: 'same-origin',
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
                credentials: 'same-origin',
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
    
    // Accepter une demande d'ami depuis la notification
    async acceptFriendRequest(notificationId, senderId) {
        try {
            // Désactiver les boutons pendant le traitement
            const notificationItem = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
            const acceptBtn = notificationItem.querySelector('.accept-friend-btn');
            const rejectBtn = notificationItem.querySelector('.reject-friend-btn');
            
            if (acceptBtn) acceptBtn.disabled = true;
            if (rejectBtn) rejectBtn.disabled = true;
            
            // Accepter la demande d'ami
            const response = await fetch('api/friends.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'accept_request',
                    sender_id: senderId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Marquer la notification comme lue
                await this.markAsRead(notificationId);
                
                // Mettre à jour l'affichage de la notification
                if (notificationItem) {
                    // Remplacer les boutons par un message de succès
                    const friendActions = notificationItem.querySelector('.notification-friend-actions');
                    if (friendActions) {
                        friendActions.innerHTML = `
                            <div class="notification-success">
                                <i class="fas fa-check-circle"></i> Demande acceptée !
                            </div>
                        `;
                    }
                    
                    // Supprimer les boutons d'action après un délai
                    setTimeout(() => {
                        if (notificationItem) {
                            notificationItem.remove();
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
                    }, 3000);
                }
                
                // Mettre à jour le compteur
                this.updateUnreadCount();
                
                // Afficher un message de succès
                this.showSuccessMessage('Demande d\'ami acceptée !');
            } else {
                this.showError(data.message || 'Erreur lors de l\'acceptation de la demande');
                
                // Réactiver les boutons en cas d'erreur
                if (acceptBtn) acceptBtn.disabled = false;
                if (rejectBtn) rejectBtn.disabled = false;
            }
        } catch (error) {
            console.error('Erreur lors de l\'acceptation de la demande d\'ami:', error);
            this.showError('Erreur de connexion');
            
            // Réactiver les boutons en cas d'erreur
            const notificationItem = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
            const acceptBtn = notificationItem.querySelector('.accept-friend-btn');
            const rejectBtn = notificationItem.querySelector('.reject-friend-btn');
            if (acceptBtn) acceptBtn.disabled = false;
            if (rejectBtn) rejectBtn.disabled = false;
        }
    }
    
    // Refuser une demande d'ami depuis la notification
    async rejectFriendRequest(notificationId, senderId) {
        if (!confirm('Êtes-vous sûr de vouloir refuser cette demande d\'ami ?')) {
            return;
        }
        
        try {
            // Désactiver les boutons pendant le traitement
            const notificationItem = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
            const acceptBtn = notificationItem.querySelector('.accept-friend-btn');
            const rejectBtn = notificationItem.querySelector('.reject-friend-btn');
            
            if (acceptBtn) acceptBtn.disabled = true;
            if (rejectBtn) rejectBtn.disabled = true;
            
            // Refuser la demande d'ami
            const response = await fetch('api/friends.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reject_request',
                    sender_id: senderId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Marquer la notification comme lue
                await this.markAsRead(notificationId);
                
                // Mettre à jour l'affichage de la notification
                if (notificationItem) {
                    // Remplacer les boutons par un message de confirmation
                    const friendActions = notificationItem.querySelector('.notification-friend-actions');
                    if (friendActions) {
                        friendActions.innerHTML = `
                            <div class="notification-info">
                                <i class="fas fa-times-circle"></i> Demande refusée
                            </div>
                        `;
                    }
                    
                    // Supprimer les boutons d'action après un délai
                    setTimeout(() => {
                        if (notificationItem) {
                            notificationItem.remove();
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
                    }, 3000);
                }
                
                // Mettre à jour le compteur
                this.updateUnreadCount();
                
                // Afficher un message de confirmation
                this.showSuccessMessage('Demande d\'ami refusée');
            } else {
                this.showError(data.message || 'Erreur lors du refus de la demande');
                
                // Réactiver les boutons en cas d'erreur
                if (acceptBtn) acceptBtn.disabled = false;
                if (rejectBtn) rejectBtn.disabled = false;
            }
        } catch (error) {
            console.error('Erreur lors du refus de la demande d\'ami:', error);
            this.showError('Erreur de connexion');
            
            // Réactiver les boutons en cas d'erreur
            const notificationItem = this.notificationList.querySelector(`[data-id="${notificationId}"]`);
            const acceptBtn = notificationItem.querySelector('.accept-friend-btn');
            const rejectBtn = notificationItem.querySelector('.reject-friend-btn');
            if (acceptBtn) acceptBtn.disabled = false;
            if (rejectBtn) rejectBtn.disabled = false;
        }
    }
    
    // Afficher un message de succès
    showSuccessMessage(message) {
        // Créer un élément de message temporaire
        const messageEl = document.createElement('div');
        messageEl.className = 'notification-toast success';
        messageEl.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        
        // Ajouter au body
        document.body.appendChild(messageEl);
        
        // Afficher avec animation
        setTimeout(() => {
            messageEl.classList.add('show');
        }, 100);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            messageEl.classList.remove('show');
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.parentNode.removeChild(messageEl);
                }
            }, 300);
        }, 3000);
    }
    
    // Mettre à jour le compteur de notifications non lues
    async updateUnreadCount() {
        try {
            const response = await fetch('api/notifications.php?action=count', {
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            // Vérifier le code de statut HTTP
            if (!response.ok) {
                if (response.status === 401) {
                    // Session expirée, masquer le badge
                    this.unreadCount = 0;
                    this.updateBadge();
                    return;
                }
                console.error('Erreur lors de la mise à jour du compteur:', response.status);
                return;
            }
            
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
        return DateUtils.getRelativeTime(new Date(dateString));
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