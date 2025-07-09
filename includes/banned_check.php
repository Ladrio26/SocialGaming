<?php
/**
 * Vérification du bannissement - à inclure dans toutes les pages
 * Redirige automatiquement vers la page principale si l'utilisateur est banni
 */

// Vérifier si l'utilisateur est connecté et banni
if ($user && $roleManager->isBanned($user['id'])) {
    // Si on n'est pas déjà sur la page principale, rediriger
    if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
        header('Location: index.php');
        exit;
    }
}
?> 