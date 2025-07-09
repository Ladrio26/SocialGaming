<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'includes/date_utils.php';

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    header('Location: index.php?auth_error=Vous devez être connecté pour proposer une catégorie');
    exit;
}

$message = '';
$message_type = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $message = 'Le nom de la catégorie est obligatoire';
        $message_type = 'error';
    } elseif (strlen($name) > 100) {
        $message = 'Le nom de la catégorie ne peut pas dépasser 100 caractères';
        $message_type = 'error';
    } else {
        try {
            // Vérifier si une proposition similaire existe déjà
            $stmt = $pdo->prepare("SELECT id FROM category_proposals WHERE name = ? AND status = 'pending'");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $message = 'Une proposition avec ce nom existe déjà et est en attente de modération';
                $message_type = 'error';
            } else {
                // Vérifier si la catégorie existe déjà
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetch()) {
                    $message = 'Cette catégorie existe déjà';
                    $message_type = 'error';
                } else {
                    // Insérer la proposition
                    $stmt = $pdo->prepare("
                        INSERT INTO category_proposals (user_id, name, description) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user['id'], $name, $description]);
                    
                    $message = 'Votre proposition a été soumise avec succès ! Elle sera examinée par l\'équipe de modération.';
                    $message_type = 'success';
                    
                    // Vider le formulaire
                    $name = '';
                    $description = '';
                }
            }
        } catch (Exception $e) {
            $message = 'Erreur lors de la soumission de la proposition';
            $message_type = 'error';
        }
    }
}

// Récupérer les propositions de l'utilisateur
$stmt = $pdo->prepare("
    SELECT cp.*, u.username as user_username 
    FROM category_proposals cp 
    JOIN users u ON cp.user_id = u.id 
    WHERE cp.user_id = ? 
    ORDER BY cp.created_at DESC
");
$stmt->execute([$user['id']]);
$user_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposer une catégorie - Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="propose-category-container">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Proposer une catégorie</h1>
                <p>Proposez une nouvelle catégorie pour organiser les discussions sur le site</p>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="propose-form-section">
                <h2>Nouvelle proposition</h2>
                <form method="POST" class="propose-form">
                    <div class="form-group">
                        <label for="name">Nom de la catégorie *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                               maxlength="100" required placeholder="Ex: Jeux de stratégie">
                        <small>Nom court et descriptif (max 100 caractères)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (optionnel)</label>
                        <textarea id="description" name="description" rows="4" 
                                  placeholder="Décrivez brièvement le thème de cette catégorie..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        <small>Description détaillée pour aider les modérateurs à comprendre votre proposition</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Soumettre la proposition
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour à l'accueil
                        </a>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($user_proposals)): ?>
            <div class="user-proposals-section">
                <h2>Mes propositions</h2>
                <div class="proposals-list">
                    <?php foreach ($user_proposals as $proposal): ?>
                        <div class="proposal-card status-<?php echo $proposal['status']; ?>">
                            <div class="proposal-header">
                                <h3><?php echo htmlspecialchars($proposal['name']); ?></h3>
                                <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                    <?php 
                                    switch($proposal['status']) {
                                        case 'pending': echo '<i class="fas fa-clock"></i> En attente'; break;
                                        case 'approved': echo '<i class="fas fa-check"></i> Approuvée'; break;
                                        case 'rejected': echo '<i class="fas fa-times"></i> Refusée'; break;
                                        case 'modified': echo '<i class="fas fa-edit"></i> Modifiée'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($proposal['description']): ?>
                                <p class="proposal-description"><?php echo htmlspecialchars($proposal['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="proposal-meta">
                                <span class="proposal-date">
                                    <i class="fas fa-calendar"></i> 
                                    Proposée le <?php echo formatDateLong($proposal['created_at']); ?>
                                </span>
                                
                                <?php if ($proposal['moderated_at']): ?>
                                    <span class="moderation-date">
                                        <i class="fas fa-gavel"></i> 
                                        Modérée le <?php echo formatDateLong($proposal['moderated_at']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($proposal['moderator_notes']): ?>
                                    <div class="moderator-notes">
                                        <strong>Note du modérateur :</strong>
                                        <?php echo htmlspecialchars($proposal['moderator_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/date-utils.js"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html> 