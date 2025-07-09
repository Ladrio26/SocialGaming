<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'includes/RoleManager.php';

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    header('Location: login.php');
    exit;
}

// Initialisation du gestionnaire de rôles
$roleManager = new RoleManager($pdo);

// Vérification du bannissement
if ($roleManager->isBanned($user['id'])) {
    header('Location: banned.php');
    exit;
}

$pageTitle = "Gérer mes catégories";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Ladrio</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        .manage-categories-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .manage-categories-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-primary);
        }
        
        .categories-list {
            display: grid;
            gap: 15px;
        }
        
        .category-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: var(--bg-primary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .category-item.active {
            background: rgba(62, 196, 109, 0.25);
            border-color: rgba(62, 196, 109, 0.5);
        }
        
        .category-item.inactive {
            background: rgba(231, 76, 60, 0.1);
            border-color: rgba(231, 76, 60, 0.3);
        }
        
        .category-item:hover {
            border-color: var(--accent-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            background: rgba(60, 60, 80, 0.15); /* Surbrillance douce adaptée au dark */
        }
        
        .category-item.active:hover {
            background: rgba(62, 196, 109, 0.4);
        }
        
        .category-item.inactive:hover {
            background: rgba(231, 76, 60, 0.2);
        }
        
        .category-info {
            flex: 1;
        }
        
        .category-name {
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .category-description {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .category-toggle {
            position: relative;
            width: 60px;
            height: 30px;
            background: var(--bg-tertiary);
            border-radius: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .category-toggle.active {
            background: var(--accent-color);
        }
        
        .category-toggle::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }
        
        .category-toggle.active::after {
            transform: translateX(30px);
        }
        
        .save-button {
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 30px auto 0;
            padding: 12px 24px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .save-button:hover {
            background: var(--accent-hover);
        }
        
        .save-button:disabled {
            background: var(--bg-tertiary);
            cursor: not-allowed;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        
        .message.error {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border: 1px solid #F44336;
        }
        
        .loading {
            text-align: center;
            color: var(--text-secondary);
            margin: 20px 0;
        }

        .category-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-left: 18px;
            border: 2px solid var(--border-color);
            box-shadow: 0 0 2px rgba(0,0,0,0.15);
            transition: background 0.3s, border 0.3s;
        }
        .category-indicator.active {
            background: #3ec46d;
            border-color: #3ec46d;
        }
        .category-indicator.inactive {
            background: #e74c3c;
            border-color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="manage-categories-container">
            <h1 class="manage-categories-title">Gérer mes catégories</h1>
            
            <div id="message-container"></div>
            
            <div id="loading" class="loading">
                Chargement des catégories...
            </div>
            
            <div id="categories-container" class="categories-list" style="display: none;">
                <!-- Les catégories seront chargées ici -->
            </div>
            
            <button id="save-button" class="save-button" style="display: none;">
                Sauvegarder mes préférences
            </button>
        </div>
    </div>
    
    <script>
        let categories = [];
        let hasChanges = false;
        
        // Charger les catégories au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
        });
        
        function loadCategories() {
            fetch('api/category_preferences.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        categories = data.categories;
                        displayCategories();
                    } else {
                        showMessage('Erreur lors du chargement des catégories: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Erreur de connexion: ' + error.message, 'error');
                });
        }
        
        function displayCategories() {
            const container = document.getElementById('categories-container');
            const loading = document.getElementById('loading');
            const saveButton = document.getElementById('save-button');
            
            loading.style.display = 'none';
            container.style.display = 'block';
            saveButton.style.display = 'block';
            
            container.innerHTML = categories.map(category => `
                <div class="category-item ${category.is_visible ? 'active' : 'inactive'}" 
                     id="category-${category.id}" 
                     onclick="toggleCategory(${category.id})" 
                     style="cursor: pointer;">
                    <div class="category-info">
                        <div class="category-name">${category.name}</div>
                        <div class="category-description">${category.description || 'Aucune description'}</div>
                    </div>
                    <div class="category-toggle ${category.is_visible ? 'active' : ''}" 
                         data-category-id="${category.id}">
                    </div>
                </div>
            `).join('');
        }
        
        function toggleCategory(categoryId) {
            const toggle = document.querySelector(`[data-category-id="${categoryId}"]`);
            const category = categories.find(c => c.id == categoryId);
            const categoryItem = document.getElementById('category-' + categoryId);
            
            if (category) {
                category.is_visible = !category.is_visible;
                toggle.classList.toggle('active', category.is_visible);
                categoryItem.className = 'category-item ' + (category.is_visible ? 'active' : 'inactive');
                hasChanges = true;
                
                // Activer le bouton de sauvegarde
                document.getElementById('save-button').disabled = false;
            }
        }
        
        function savePreferences() {
            const saveButton = document.getElementById('save-button');
            saveButton.disabled = true;
            saveButton.textContent = 'Sauvegarde...';
            
            const preferences = categories.map(category => ({
                category_id: category.id,
                is_visible: category.is_visible
            }));
            
            fetch('api/category_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ preferences: preferences })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Préférences sauvegardées avec succès !', 'success');
                    hasChanges = false;
                } else {
                    showMessage('Erreur lors de la sauvegarde: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Erreur de connexion: ' + error.message, 'error');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.textContent = 'Sauvegarder mes préférences';
            });
        }
        
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            container.innerHTML = `<div class="message ${type}">${message}</div>`;
            
            // Masquer le message après 5 secondes
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
        
        // Gestionnaire pour le bouton de sauvegarde
        document.getElementById('save-button').addEventListener('click', savePreferences);
        
        // Avertir l'utilisateur s'il essaie de quitter avec des changements non sauvegardés
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = 'Vous avez des changements non sauvegardés. Êtes-vous sûr de vouloir quitter ?';
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 