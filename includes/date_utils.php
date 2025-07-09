<?php
/**
 * Utilitaires pour la gestion des dates avec le fuseau horaire de Paris
 */

/**
 * Formate une date en français avec le fuseau horaire de Paris
 * @param string $dateString Date au format MySQL (Y-m-d H:i:s)
 * @param string $format Format de sortie (par défaut: d/m/Y H:i)
 * @return string Date formatée
 */
function formatDateParis($dateString, $format = 'd/m/Y H:i') {
    if (empty($dateString)) {
        return '';
    }
    
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Paris'));
    
    // Configuration pour l'affichage en français
    setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'french', 'fra');
    
    return $date->format($format);
}

/**
 * Formate une date courte (jour/mois/année) en français
 * @param string $dateString Date au format MySQL
 * @return string Date formatée
 */
function formatDateShort($dateString) {
    if (empty($dateString)) {
        return '';
    }
    
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Paris'));
    
    return $date->format('d/m/Y');
}

/**
 * Formate une date avec heure en français
 * @param string $dateString Date au format MySQL
 * @return string Date formatée
 */
function formatDateTime($dateString) {
    if (empty($dateString)) {
        return '';
    }
    
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Paris'));
    
    return $date->format('d/m/Y H:i');
}

/**
 * Formate une date relative (il y a X temps)
 * @param string $dateString Date au format MySQL
 * @return string Date relative
 */
function formatDateRelative($dateString) {
    if (empty($dateString)) {
        return '';
    }
    
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Paris'));
    $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
    
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return 'Il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    } elseif ($diff->m > 0) {
        return 'Il y a ' . $diff->m . ' mois';
    } elseif ($diff->d > 0) {
        return 'Il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return 'Il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return 'Il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    } else {
        return 'À l\'instant';
    }
}

/**
 * Formate une date avec le nom du mois en français
 * @param string $dateString Date au format MySQL
 * @return string Date formatée
 */
function formatDateLong($dateString) {
    if (empty($dateString)) {
        return '';
    }
    
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Europe/Paris'));
    
    $mois = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];
    
    $jour = $date->format('j');
    $mois_num = (int)$date->format('n');
    $annee = $date->format('Y');
    
    return $jour . ' ' . $mois[$mois_num] . ' ' . $annee;
}

/**
 * Obtient la date actuelle au format MySQL avec le fuseau horaire de Paris
 * @return string Date au format Y-m-d H:i:s
 */
function getCurrentDateParis() {
    $date = new DateTime('now', new DateTimeZone('Europe/Paris'));
    return $date->format('Y-m-d H:i:s');
} 