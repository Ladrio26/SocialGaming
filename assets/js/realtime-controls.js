/**
 * Contrôles pour les mises à jour en temps réel
 * Ajoute des boutons pour activer/désactiver les popups
 */

document.addEventListener('DOMContentLoaded', function() {
    // Créer le panneau de contrôle
    createRealtimeControls();
});

function createRealtimeControls() {
    // Vérifier si on est sur une page avec le header
    const header = document.querySelector('.header-right');
    if (!header) return;
    
    // Créer le bouton de contrôle
    const controlBtn = document.createElement('button');
    controlBtn.className = 'btn btn-sm btn-secondary realtime-control-btn';
    controlBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
    controlBtn.title = 'Mises à jour en temps réel';
    
    // Ajouter au header
    header.insertBefore(controlBtn, header.firstChild);
    
    // Créer le menu déroulant
    const dropdown = document.createElement('div');
    dropdown.className = 'realtime-control-dropdown';
    dropdown.style.display = 'none';
    dropdown.innerHTML = `
        <div class="realtime-control-header">
            <h4>Mises à jour en temps réel</h4>
        </div>
        <div class="realtime-control-options">
            <label class="realtime-control-option">
                <input type="checkbox" id="realtimeEnabled" checked>
                <span>Activer les mises à jour</span>
            </label>
            <label class="realtime-control-option">
                <input type="checkbox" id="realtimePopups">
                <span>Afficher les popups</span>
            </label>
        </div>
        <div class="realtime-control-footer">
            <button class="btn btn-sm btn-primary" onclick="forceCheckUpdates()">
                <i class="fas fa-sync"></i> Vérifier maintenant
            </button>
        </div>
    `;
    
    // Ajouter au body
    document.body.appendChild(dropdown);
    
    // Gestionnaires d'événements
    controlBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });
    
    // Fermer le dropdown en cliquant ailleurs
    document.addEventListener('click', function() {
        dropdown.style.display = 'none';
    });
    
    // Empêcher la fermeture en cliquant dans le dropdown
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Gestionnaires pour les checkboxes
    const enabledCheckbox = document.getElementById('realtimeEnabled');
    const popupsCheckbox = document.getElementById('realtimePopups');
    
    if (enabledCheckbox) {
        enabledCheckbox.addEventListener('change', function() {
            toggleRealtimeUpdates(this.checked);
        });
    }
    
    if (popupsCheckbox) {
        popupsCheckbox.addEventListener('change', function() {
            toggleRealtimePopups(this.checked);
        });
    }
    
    // Initialiser l'état
    if (window.realtimeUpdates) {
        enabledCheckbox.checked = window.realtimeUpdates.isActive;
        popupsCheckbox.checked = window.realtimeUpdates.showPopups;
    }
} 