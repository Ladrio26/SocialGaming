<?php
require_once 'config.php';

echo "<h1>🧹 Nettoyage du système de propositions de catégories</h1>";

// Liste des fichiers à supprimer après vérification
$files_to_cleanup = [
    'setup_category_proposals.php',
    'update_moderation_permissions.php',
    'test_category_system.php',
    'cleanup_category_system.php',
    'database/category_proposals.sql'
];

echo "<h2>Fichiers à supprimer :</h2>";
echo "<ul>";
foreach ($files_to_cleanup as $file) {
    if (file_exists($file)) {
        echo "<li>🗑️ " . htmlspecialchars($file) . "</li>";
    } else {
        echo "<li>❌ " . htmlspecialchars($file) . " (n'existe pas)</li>";
    }
}
echo "</ul>";

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    echo "<h2>Suppression en cours...</h2>";
    
    $deleted_count = 0;
    foreach ($files_to_cleanup as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<p>✅ " . htmlspecialchars($file) . " supprimé</p>";
                $deleted_count++;
            } else {
                echo "<p>❌ Erreur lors de la suppression de " . htmlspecialchars($file) . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Nettoyage terminé !</h3>";
    echo "<p>$deleted_count fichiers supprimés.</p>";
    echo "<p><a href='index.php'>← Retour à l'accueil</a></p>";
    
} else {
    echo "<h2>⚠️ Attention</h2>";
    echo "<p>Cette action supprimera définitivement les fichiers de configuration temporaires.</p>";
    echo "<p>Assurez-vous que le système fonctionne correctement avant de procéder.</p>";
    
    echo "<h3>Vérifications recommandées :</h3>";
    echo "<ul>";
    echo "<li>✅ La table 'category_proposals' existe</li>";
    echo "<li>✅ Les permissions 'moderate_categories' sont configurées</li>";
    echo "<li>✅ La page de proposition fonctionne</li>";
    echo "<li>✅ La page de modération fonctionne</li>";
    echo "<li>✅ Le bouton de proposition apparaît dans la sidebar</li>";
    echo "<li>✅ Le lien de modération apparaît dans le header</li>";
    echo "</ul>";
    
    echo "<p><strong>Êtes-vous sûr de vouloir supprimer ces fichiers ?</strong></p>";
    echo "<p><a href='?confirm=yes' class='btn btn-danger'>Oui, supprimer les fichiers</a></p>";
    echo "<p><a href='index.php' class='btn btn-secondary'>Non, retour à l'accueil</a></p>";
}
?> 