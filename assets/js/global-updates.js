/**
 * Script global pour les mises à jour en temps réel
 * S'initialise automatiquement sur toutes les pages
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Charger le script de mises à jour en temps réel
    const script = document.createElement('script');
    script.src = '/assets/js/realtime-updates.js';
    script.onload = function() {
        // Configurer les callbacks globaux une fois le script chargé
        if (window.realtimeUpdates) {
            console.log('Système de mises à jour en temps réel initialisé');
            
            // Callback pour les notifications
            window.realtimeUpdates.onUpdate('notifications', function(count) {
                updateNotificationBadge(count);
            });
            
            // Callback pour les demandes d'amis
            window.realtimeUpdates.onUpdate('friend_requests', function(count) {
                updateFriendRequestBadge(count);
            });
            
            // Callback pour les catégories non lues
            window.realtimeUpdates.onUpdate('unread_categories', function(categories) {
                updateCategoryBadges(categories);
            });
        }
    };
    document.head.appendChild(script);
});

/**
 * Mettre à jour le badge de notifications
 */
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Mettre à jour le badge de demandes d'amis
 */
function updateFriendRequestBadge(count) {
    const friendsBtn = document.getElementById('friendsBtn');
    if (friendsBtn) {
        let badge = friendsBtn.querySelector('.friend-request-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'friend-request-badge';
            friendsBtn.appendChild(badge);
        }
        
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Mettre à jour les badges de catégories non lues
 */
function updateCategoryBadges(categories) {
    categories.forEach(category => {
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
 * Fonction utilitaire pour forcer une vérification des mises à jour
 */
function forceCheckUpdates() {
    if (window.realtimeUpdates) {
        window.realtimeUpdates.forceCheck();
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