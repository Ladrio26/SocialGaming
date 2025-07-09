<?php
require_once 'config.php';

echo "<h2>Diagnostic et correction de la colonne auth_provider</h2>";

try {
    // Vérifier la structure actuelle de la table users
    echo "<h3>1. Structure actuelle de la table users :</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    
    $authProviderColumn = null;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'auth_provider') {
            $authProviderColumn = $column;
        }
    }
    echo "</table>";
    
    // Analyser la colonne auth_provider
    if ($authProviderColumn) {
        echo "<h3>2. Analyse de la colonne auth_provider :</h3>";
        echo "<p><strong>Type actuel :</strong> {$authProviderColumn['Type']}</p>";
        
        // Extraire la taille du type (ex: varchar(10) -> 10)
        preg_match('/\((\d+)\)/', $authProviderColumn['Type'], $matches);
        $currentSize = isset($matches[1]) ? (int)$matches[1] : 0;
        
        echo "<p><strong>Taille actuelle :</strong> {$currentSize} caractères</p>";
        
        // Vérifier les valeurs actuelles
        echo "<h3>3. Valeurs actuelles dans auth_provider :</h3>";
        $stmt = $pdo->query("SELECT auth_provider, COUNT(*) as count FROM users GROUP BY auth_provider");
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr><th>Valeur</th><th>Nombre d'utilisateurs</th><th>Longueur</th></tr>";
        foreach ($values as $value) {
            $length = strlen($value['auth_provider']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($value['auth_provider']) . "</td>";
            echo "<td>{$value['count']}</td>";
            echo "<td>{$length}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Déterminer la taille nécessaire
        $requiredSize = 20; // Suffisant pour "twitch", "discord", "steam", etc.
        $maxLength = 0;
        foreach ($values as $value) {
            $maxLength = max($maxLength, strlen($value['auth_provider']));
        }
        $requiredSize = max($requiredSize, $maxLength + 5); // Marge de sécurité
        
        echo "<h3>4. Recommandation :</h3>";
        echo "<p><strong>Taille recommandée :</strong> {$requiredSize} caractères</p>";
        
        if ($currentSize < $requiredSize) {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px;'>";
            echo "❌ La colonne est trop petite ! Elle doit être agrandie.";
            echo "</div>";
            
            echo "<h3>5. Correction :</h3>";
            if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
                // Effectuer la correction
                $sql = "ALTER TABLE users MODIFY COLUMN auth_provider VARCHAR({$requiredSize})";
                $pdo->exec($sql);
                
                echo "<div style='color: green; padding: 10px; border: 1px solid green; border-radius: 5px;'>";
                echo "✅ Colonne auth_provider modifiée avec succès !";
                echo "</div>";
                
                echo "<p><strong>Nouvelle structure :</strong></p>";
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
                foreach ($columns as $column) {
                    if ($column['Field'] === 'auth_provider') {
                        echo "<tr style='background-color: #d4edda;'>";
                    } else {
                        echo "<tr>";
                    }
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "<td>{$column['Extra']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } else {
                echo "<div style='color: orange; padding: 10px; border: 1px solid orange; border-radius: 5px;'>";
                echo "⚠️ Pour corriger automatiquement, cliquez sur le bouton ci-dessous :";
                echo "</div>";
                
                echo "<p><a href='?fix=yes' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0;'>";
                echo "🔧 Corriger automatiquement la colonne auth_provider";
                echo "</a></p>";
                
                echo "<p><strong>SQL qui sera exécuté :</strong></p>";
                echo "<code style='background: #f8f9fa; padding: 10px; border-radius: 5px; display: block;'>";
                echo "ALTER TABLE users MODIFY COLUMN auth_provider VARCHAR({$requiredSize});";
                echo "</code>";
            }
        } else {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; border-radius: 5px;'>";
            echo "✅ La colonne auth_provider a une taille suffisante.";
            echo "</div>";
        }
        
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px;'>";
        echo "❌ La colonne auth_provider n'existe pas dans la table users !";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; border-radius: 5px;'>";
    echo "❌ Erreur : " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<h3>6. Test de l'authentification Twitch :</h3>";
echo "<p>Après la correction, testez l'authentification Twitch en visitant :</p>";
echo "<a href='oauth2callback_twitch.php' style='color: #007bff;'>oauth2callback_twitch.php</a>";
?> 