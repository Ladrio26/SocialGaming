<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'includes/RoleManager.php';
require_once 'includes/date_utils.php';

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    header('Location: index.php?auth_error=Vous devez être connecté pour accéder à la modération');
    exit;
}

$roleManager = new RoleManager($pdo);

// Vérifier les permissions de modération
if (!$roleManager->hasPermission($user['id'], 'moderate_categories')) {
    header('Location: index.php?auth_error=Vous n\'avez pas les permissions pour accéder à la modération');
    exit;
}

$message = '';
$message_type = '';

// Traitement des actions de modération
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proposal_id = (int)($_POST['proposal_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Vérifier que l'action est valide
    if ($proposal_id && in_array($action, ['approve', 'reject', 'modify'])) {
        try {
            // Récupérer la proposition
            $stmt = $pdo->prepare("SELECT * FROM category_proposals WHERE id = ? AND status = 'pending'");
            $stmt->execute([$proposal_id]);
            $proposal = $stmt->fetch();
            
            if (!$proposal) {
                $message = 'Proposition non trouvée ou déjà traitée';
                $message_type = 'error';
            } else {
                $pdo->beginTransaction();
                
                switch ($action) {
                    case 'approve':
                        // Créer la catégorie
                        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                        $stmt->execute([$proposal['name'], $proposal['description']]);
                        
                        // Marquer la proposition comme approuvée
                        $stmt = $pdo->prepare("
                            UPDATE category_proposals 
                            SET status = 'approved', moderator_id = ?, moderator_notes = ?, moderated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$user['id'], $notes, $proposal_id]);
                        
                        $message = 'Catégorie approuvée et créée avec succès !';
                        $message_type = 'success';
                        break;
                        
                    case 'reject':
                        // Marquer la proposition comme refusée
                        $stmt = $pdo->prepare("
                            UPDATE category_proposals 
                            SET status = 'rejected', moderator_id = ?, moderator_notes = ?, moderated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$user['id'], $notes, $proposal_id]);
                        
                        $message = 'Proposition refusée';
                        $message_type = 'success';
                        break;
                        
                    case 'modify':
                        // Vérifier que les champs de modification sont présents
                        if (!isset($_POST['modified_name']) || !isset($_POST['modified_description'])) {
                            throw new Exception('Champs de modification manquants');
                        }
                        
                        $modified_name = trim($_POST['modified_name']);
                        $modified_description = trim($_POST['modified_description']);
                        
                        if (empty($modified_name)) {
                            throw new Exception('Le nom modifié est obligatoire');
                        }
                        
                        // Créer la catégorie avec le nom modifié
                        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                        $stmt->execute([$modified_name, $modified_description]);
                        
                        // Marquer la proposition comme modifiée
                        $stmt = $pdo->prepare("
                            UPDATE category_proposals 
                            SET status = 'modified', moderator_id = ?, moderator_notes = ?, 
                                original_name = ?, original_description = ?, moderated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$user['id'], $notes, $proposal['name'], $proposal['description'], $proposal_id]);
                        
                        $message = 'Catégorie modifiée et créée avec succès !';
                        $message_type = 'success';
                        break;
                }
                
                $pdo->commit();
                
                // Rediriger pour éviter la soumission multiple du formulaire
                header('Location: moderation.php?success=1');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Erreur lors de la modération : ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    // Afficher le message de succès après redirection
    if (isset($_GET['success'])) {
        $message = 'Action de modération effectuée avec succès !';
        $message_type = 'success';
    }
}

// Récupérer les propositions en attente
$stmt = $pdo->prepare("
    SELECT cp.*, u.username as user_username, u.avatar_url as user_avatar
    FROM category_proposals cp 
    JOIN users u ON cp.user_id = u.id 
    WHERE cp.status = 'pending' 
    ORDER BY cp.created_at ASC
");
$stmt->execute();
$pending_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les catégories existantes
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(p.id) as posts_count
    FROM categories c 
    LEFT JOIN category_posts p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$stmt->execute();
$existing_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération des catégories - Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 0;
            margin: 0;
            background: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .container {
            max-width: 100%;
            margin: 0;
            background: var(--bg-color);
            padding: 0;
        }
        .page-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--white);
            border-bottom: 2px solid var(--border-color);
            padding: 15px 20px;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .page-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 100%;
        }
        .page-header-text h1 {
            color: var(--text-color);
            margin: 0;
            font-size: 1.5rem;
        }
        .page-header-text p {
            color: var(--text-muted);
            margin: 5px 0 0 0;
            font-size: 0.9rem;
        }
        .moderation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 100px;
            padding: 20px;
        }
        .moderation-column {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 20px;
            min-height: 600px;
        }
        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        .column-header h2 {
            margin: 0;
            color: var(--text-color);
            font-size: 1.3rem;
        }
        .column-count {
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .proposal-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .proposal-card.pending {
            border-left: 4px solid #ffc107;
        }
        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-placeholder {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 0.8rem;
        }
        .status-badge {
            padding: 3px 6px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .proposal-description {
            margin: 10px 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .proposal-meta {
            margin: 10px 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .moderation-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.8rem;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .category-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .category-name {
            font-weight: 600;
            color: var(--text-color);
        }
        .category-stats {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .category-description {
            margin: 10px 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .category-actions {
            display: flex;
            gap: 5px;
        }
        .future-column {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-style: italic;
            text-align: center;
        }
        .future-column i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .no-items {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        .no-items i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-20px);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-header-text">
                    <h1><i class="fas fa-gavel"></i> Modération des catégories</h1>
                    <p>Gérez les propositions et les catégories existantes</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button id="themeToggle" class="btn btn-secondary" style="padding: 8px 12px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    <a href="index.php" class="btn btn-secondary" style="padding: 8px 12px; height: 36px; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                        <i class="fas fa-home"></i>Accueil
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="moderation-grid">
            <!-- Colonne 1: Propositions en attente -->
            <div class="moderation-column">
                <div class="column-header">
                    <h2><i class="fas fa-clock"></i> Propositions en attente</h2>
                    <span class="column-count"><?php echo count($pending_proposals); ?></span>
                </div>
                
                <?php if (empty($pending_proposals)): ?>
                    <div class="no-items">
                        <i class="fas fa-check-circle"></i>
                        <p>Aucune proposition en attente</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_proposals as $proposal): ?>
                        <div class="proposal-card pending">
                            <div class="proposal-header">
                                <div class="user-info">
                                    <?php if ($proposal['user_avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($proposal['user_avatar']); ?>" alt="Avatar" class="user-avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="category-name"><?php echo htmlspecialchars($proposal['name']); ?></div>
                                        <div class="category-stats">Proposé par <?php echo htmlspecialchars($proposal['user_username']); ?></div>
                                    </div>
                                </div>
                                <span class="status-badge pending">
                                    <i class="fas fa-clock"></i> En attente
                                </span>
                            </div>
                            
                            <?php if ($proposal['description']): ?>
                                <p class="proposal-description"><?php echo htmlspecialchars($proposal['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="proposal-meta">
                                <i class="fas fa-calendar"></i> 
                                Proposée le <?php echo formatDateLong($proposal['created_at']); ?>
                            </div>
                            
                            <div class="moderation-actions">
                                <button class="btn btn-success" onclick="showModerationModal(<?php echo $proposal['id']; ?>, 'approve', '<?php echo htmlspecialchars($proposal['name']); ?>', '<?php echo htmlspecialchars($proposal['description']); ?>')">
                                    <i class="fas fa-check"></i> Approuver
                                </button>
                                <button class="btn btn-warning" onclick="showModerationModal(<?php echo $proposal['id']; ?>, 'modify', '<?php echo htmlspecialchars($proposal['name']); ?>', '<?php echo htmlspecialchars($proposal['description']); ?>')">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button class="btn btn-danger" onclick="showModerationModal(<?php echo $proposal['id']; ?>, 'reject', '<?php echo htmlspecialchars($proposal['name']); ?>', '<?php echo htmlspecialchars($proposal['description']); ?>')">
                                    <i class="fas fa-times"></i> Refuser
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Colonne 2: Catégories existantes -->
            <div class="moderation-column">
                <div class="column-header">
                    <h2><i class="fas fa-list"></i> Catégories existantes</h2>
                    <span class="column-count"><?php echo count($existing_categories); ?></span>
                </div>
                
                <?php if (empty($existing_categories)): ?>
                    <div class="no-items">
                        <i class="fas fa-folder-open"></i>
                        <p>Aucune catégorie existante</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($existing_categories as $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                <div class="category-stats">
                                    <i class="fas fa-file-alt"></i> <?php echo $category['posts_count']; ?> posts
                                </div>
                            </div>
                            
                            <?php if ($category['description']): ?>
                                <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="category-actions">
                                <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <?php if ($category['posts_count'] == 0): ?>
                                    <button class="btn btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                <?php else: ?>
                                    <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;" title="Impossible de supprimer une catégorie contenant des posts">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Colonne 3: Usage futur -->
            <div class="moderation-column">
                <div class="column-header">
                    <h2><i class="fas fa-cog"></i> Outils futurs</h2>
                    <span class="column-count">0</span>
                </div>
                
                <div class="future-column">
                    <div>
                        <i class="fas fa-tools"></i>
                        <p>Espace réservé pour de futures fonctionnalités</p>
                        <p style="font-size: 0.9rem; margin-top: 10px;">
                            Suggestions :<br>
                            • Statistiques avancées<br>
                            • Modération en masse<br>
                            • Historique des actions<br>
                            • Paramètres de modération
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de modération -->
    <div id="moderationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Modération</h3>
                <span class="close" onclick="closeModerationModal()">&times;</span>
            </div>
            <form method="POST" id="moderationForm">
                <input type="hidden" id="modalProposalId" name="proposal_id">
                <input type="hidden" id="modalAction" name="action">
                
                <div id="modifyFields" style="display: none;">
                    <div class="form-group">
                        <label for="modifiedName">Nom modifié *</label>
                        <input type="text" id="modifiedName" name="modified_name" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="modifiedDescription">Description modifiée</label>
                        <textarea id="modifiedDescription" name="modified_description" rows="4"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="moderatorNotes">Notes du modérateur (optionnel)</label>
                    <textarea id="moderatorNotes" name="notes" rows="3" 
                              placeholder="Expliquez votre décision..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Confirmer</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModerationModal()">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/date-utils.js"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        // Gestion du thème pour la page de modération
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            
            // Mettre à jour l'icône selon le thème actuel
            function updateThemeIcon() {
                const currentTheme = localStorage.getItem('theme') || 'light';
                if (currentTheme === 'dark') {
                    themeIcon.className = 'fas fa-sun';
                } else {
                    themeIcon.className = 'fas fa-moon';
                }
            }
            
            // Initialiser l'icône
            updateThemeIcon();
            
            // Gérer le clic sur le bouton de thème
            themeToggle.addEventListener('click', function() {
                const currentTheme = localStorage.getItem('theme') || 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                localStorage.setItem('theme', newTheme);
                document.documentElement.setAttribute('data-theme', newTheme);
                updateThemeIcon();
            });
        });

        function showModerationModal(proposalId, action, name, description) {
            document.getElementById('modalProposalId').value = proposalId;
            document.getElementById('modalAction').value = action;
            
            const modalTitle = document.getElementById('modalTitle');
            const modifyFields = document.getElementById('modifyFields');
            const modifiedName = document.getElementById('modifiedName');
            const modifiedDescription = document.getElementById('modifiedDescription');
            const submitBtn = document.getElementById('modalSubmitBtn');
            
            switch(action) {
                case 'approve':
                    modalTitle.textContent = 'Approuver la proposition';
                    submitBtn.textContent = 'Approuver';
                    submitBtn.className = 'btn btn-success';
                    modifyFields.style.display = 'none';
                    break;
                case 'reject':
                    modalTitle.textContent = 'Refuser la proposition';
                    submitBtn.textContent = 'Refuser';
                    submitBtn.className = 'btn btn-danger';
                    modifyFields.style.display = 'none';
                    break;
                case 'modify':
                    modalTitle.textContent = 'Modifier la proposition';
                    submitBtn.textContent = 'Modifier et approuver';
                    submitBtn.className = 'btn btn-warning';
                    modifyFields.style.display = 'block';
                    modifiedName.value = name;
                    modifiedDescription.value = description;
                    break;
            }
            
            document.getElementById('moderationModal').style.display = 'block';
        }
        
        function closeModerationModal() {
            document.getElementById('moderationModal').style.display = 'none';
        }
        
        function deleteCategory(categoryId, categoryName) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer la catégorie "${categoryName}" ?\n\nCette action est irréversible.`)) {
                // Désactiver le bouton pendant la requête
                const button = event.target.closest('.btn');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';
                button.disabled = true;
                
                // Préparer les données
                const formData = new FormData();
                formData.append('category_id', categoryId);
                
                // Envoyer la requête
                fetch('api/delete_category.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Afficher un message de succès
                        showMessage(data.message, 'success');
                        
                        // Supprimer la carte de la catégorie du DOM
                        const categoryCard = button.closest('.category-card');
                        if (categoryCard) {
                            categoryCard.style.animation = 'fadeOut 0.3s ease';
                            setTimeout(() => {
                                categoryCard.remove();
                                
                                // Mettre à jour le compteur
                                const countElement = document.querySelector('.moderation-column:nth-child(2) .column-count');
                                if (countElement) {
                                    const currentCount = parseInt(countElement.textContent);
                                    countElement.textContent = currentCount - 1;
                                }
                                
                                // Vérifier s'il reste des catégories
                                const remainingCategories = document.querySelectorAll('.moderation-column:nth-child(2) .category-card');
                                if (remainingCategories.length === 0) {
                                    const column = document.querySelector('.moderation-column:nth-child(2)');
                                    const noItemsDiv = document.createElement('div');
                                    noItemsDiv.className = 'no-items';
                                    noItemsDiv.innerHTML = `
                                        <i class="fas fa-folder-open"></i>
                                        <p>Aucune catégorie existante</p>
                                    `;
                                    column.appendChild(noItemsDiv);
                                }
                            }, 300);
                        }
                    } else {
                        showMessage(data.message, 'error');
                        // Restaurer le bouton
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showMessage('Erreur lors de la suppression de la catégorie', 'error');
                    // Restaurer le bouton
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
        
        function showMessage(message, type) {
            // Supprimer les messages existants
            const existingMessages = document.querySelectorAll('.message');
            existingMessages.forEach(msg => msg.remove());
            
            // Créer le nouveau message
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            
            // Insérer le message après le header
            const container = document.querySelector('.container');
            const moderationGrid = document.querySelector('.moderation-grid');
            container.insertBefore(messageDiv, moderationGrid);
            
            // Supprimer le message après 5 secondes
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
        
        // Fermer le modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('moderationModal');
            if (event.target === modal) {
                closeModerationModal();
            }
        }
    </script>
</body>
</html> 