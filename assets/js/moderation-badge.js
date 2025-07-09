// Mise à jour du badge de modération
function updateModerationBadge() {
    const moderationBtn = document.querySelector('.moderation-btn');
    if (!moderationBtn) return;
    
    fetch('api/update_moderation_badge.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let badge = moderationBtn.querySelector('.moderation-badge');
                
                if (data.has_pending) {
                    // Créer ou mettre à jour le badge
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'moderation-badge';
                        moderationBtn.appendChild(badge);
                    }
                    badge.textContent = data.pending_count;
                    badge.style.display = 'flex';
                } else {
                    // Masquer le badge s'il n'y a pas de propositions en attente
                    if (badge) {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du badge de modération:', error);
        });
}

// Mettre à jour le badge au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateModerationBadge();
    
    // Mettre à jour le badge toutes les 30 secondes
    setInterval(updateModerationBadge, 30000);
});

// Mettre à jour le badge après une action de modération
function refreshModerationBadge() {
    setTimeout(updateModerationBadge, 1000);
}

// Exposer la fonction globalement pour pouvoir l'appeler depuis d'autres scripts
window.refreshModerationBadge = refreshModerationBadge; 