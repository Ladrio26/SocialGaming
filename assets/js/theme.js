// Gestionnaire de thème
class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'dark';
        this.init();
    }

    init() {
        // Appliquer le thème sauvegardé ou le thème par défaut (sombre)
        this.setTheme(this.currentTheme);
        
        // Créer le bouton de basculement
        this.createThemeToggle();
    }

    setTheme(theme) {
        this.currentTheme = theme;
        
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
        
        // Sauvegarder dans localStorage
        localStorage.setItem('theme', theme);
        
        // Mettre à jour l'icône du bouton
        this.updateToggleIcon();
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    createThemeToggle() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('theme-toggle')) {
            return;
        }

        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'theme-toggle';
        toggleBtn.className = 'btn theme-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        toggleBtn.title = 'Basculer le thème';
        toggleBtn.addEventListener('click', () => this.toggleTheme());

        // Ajouter le bouton au header si il existe (utilisateur connecté)
        const headerRight = document.querySelector('.header-right');
        if (headerRight) {
            headerRight.insertBefore(toggleBtn, headerRight.firstChild);
        } else {
            // Si pas de header (page d'authentification), ajouter le bouton dans le conteneur d'auth
            const authContainer = document.querySelector('.auth-container');
            if (authContainer) {
                // Créer un conteneur pour le bouton en haut à droite
                const themeContainer = document.createElement('div');
                themeContainer.style.cssText = 'position: absolute; top: 20px; right: 20px; z-index: 10;';
                themeContainer.appendChild(toggleBtn);
                authContainer.style.position = 'relative';
                authContainer.appendChild(themeContainer);
            }
        }
    }

    updateToggleIcon() {
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('i');
            if (this.currentTheme === 'dark') {
                icon.className = 'fas fa-sun';
                toggleBtn.title = 'Passer au mode clair';
            } else {
                icon.className = 'fas fa-moon';
                toggleBtn.title = 'Passer au mode sombre';
            }
        }
    }
}

// Initialiser le gestionnaire de thème quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
}); 