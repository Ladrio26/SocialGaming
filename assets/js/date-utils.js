/**
 * Utilitaires pour la gestion des dates avec le fuseau horaire de Paris (côté client)
 */

class DateUtils {
    /**
     * Formate une date en français avec le fuseau horaire de Paris
     * @param {string} dateString Date au format ISO ou MySQL
     * @param {string} format Format de sortie ('short', 'long', 'datetime', 'relative')
     * @returns {string} Date formatée
     */
    static formatDateParis(dateString, format = 'short') {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        const options = {
            timeZone: 'Europe/Paris'
        };
        
        switch (format) {
            case 'short':
                return date.toLocaleDateString('fr-FR', {
                    ...options,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });
                
            case 'long':
                return date.toLocaleDateString('fr-FR', {
                    ...options,
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
            case 'datetime':
                return date.toLocaleString('fr-FR', {
                    ...options,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
            case 'relative':
                return this.getRelativeTime(date);
                
            default:
                return date.toLocaleDateString('fr-FR', options);
        }
    }
    
    /**
     * Obtient le temps relatif (il y a X temps)
     * @param {Date} date Date à comparer
     * @returns {string} Temps relatif
     */
    static getRelativeTime(date) {
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
        } else if (diffInSeconds < 2592000) { // 30 jours
            const days = Math.floor(diffInSeconds / 86400);
            return `Il y a ${days}j`;
        } else if (diffInSeconds < 31536000) { // 1 an
            const months = Math.floor(diffInSeconds / 2592000);
            return `Il y a ${months} mois`;
        } else {
            const years = Math.floor(diffInSeconds / 31536000);
            return `Il y a ${years} an${years > 1 ? 's' : ''}`;
        }
    }
    
    /**
     * Formate la durée d'un stream Twitch
     * @param {string} startTimeString Date de début du stream
     * @returns {string} Durée formatée
     */
    static formatStreamDuration(startTimeString) {
        const startTime = new Date(startTimeString);
        const now = new Date();
        const diff = now - startTime;
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else {
            return `${minutes}m`;
        }
    }
    
    /**
     * Convertit une date UTC vers le fuseau horaire de Paris
     * @param {string} utcDateString Date UTC
     * @returns {Date} Date dans le fuseau horaire de Paris
     */
    static utcToParis(utcDateString) {
        const date = new Date(utcDateString);
        const parisDate = new Date(date.toLocaleString('en-US', { timeZone: 'Europe/Paris' }));
        return parisDate;
    }
}

// Fonction globale pour la compatibilité
window.formatDateParis = DateUtils.formatDateParis;
window.getRelativeTime = DateUtils.getRelativeTime;
window.formatStreamDuration = DateUtils.formatStreamDuration; 