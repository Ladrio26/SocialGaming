<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'includes/RoleManager.php'; // Added for RoleManager

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();
if (!$user) {
    header('Location: index.php');
    exit;
}

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$category_id) {
    echo 'Catégorie introuvable.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    echo 'Catégorie introuvable.';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Catégorie</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header compact */
        .page-header {
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .home-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .home-btn:hover {
            background: var(--primary-hover);
            text-decoration: none;
            color: white;
        }
        
        .header-center {
            flex: 1;
            text-align: center;
            margin: 0 20px;
        }
        
        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .page-desc {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 2px 0 0 0;
        }
        
        .header-right {
            width: 60px; /* Même largeur que le bouton accueil pour centrer */
        }
        
        /* Bouton de basculement pour modérateurs */
        .view-toggle-btn {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 6px 10px;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-toggle-btn:hover {
            background: var(--primary-color);
            opacity: 0.9;
        }
        
        .view-toggle-btn.friends-mode {
            background: var(--primary-color);
        }
        
        .view-toggle-btn.friends-mode:hover {
            background: var(--primary-color);
            opacity: 0.8;
        }
        
        /* Zone de contenu principal */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: var(--bg-color);
        }
        
        .category-posts-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .category-post {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            position: relative;
        }
        
        .category-post .post-image {
            max-width: 400px;
            max-height: 400px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .category-post .post-image:hover {
            transform: scale(1.02);
        }
        
        .category-post .post-content {
            flex: 1;
            min-width: 0;
        }
        
        .category-post .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .category-post .post-author {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.95rem;
        }
        
        .category-post .post-date {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .category-post .post-text {
            font-size: 1rem;
            line-height: 1.5;
            color: var(--text-color);
            word-wrap: break-word;
        }
        
        .category-post .post-actions {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        .category-post .btn-delete {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .category-post .btn-delete:hover {
            background: #e53e3e;
        }
        
        /* Formulaire en bas */
        .category-share-form {
            background: var(--white);
            border-top: 1px solid var(--border-color);
            padding: 15px 20px;
            flex-shrink: 0;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }
        
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .category-share-form textarea {
            width: 100%;
            min-height: 50px;
            max-height: 120px;
            resize: vertical;
            border-radius: 8px;
            border: 1.5px solid var(--border-color);
            padding: 12px;
            font-size: 1rem;
            margin-bottom: 10px;
            background: var(--white);
            color: var(--text-color);
            font-family: inherit;
        }
        
        .category-share-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .category-share-form .image-preview {
            margin-bottom: 10px;
        }
        
        .category-share-form .image-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            display: block;
        }
        
        .category-share-form .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }
        
        .category-share-form .btn {
            padding: 8px 16px !important;
            font-size: 0.9rem !important;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .category-share-form .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px !important;
            font-size: 0.9rem !important;
        }
        
        .category-share-form .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        /* Style spécifique pour le bouton Partager */
        .share-btn {
            padding: 6px 12px !important;
            font-size: 0.85rem !important;
            min-height: auto !important;
            line-height: 1.2 !important;
        }
        
        /* CSS très spécifique pour forcer l'application */
        .category-share-form .form-actions .btn.btn-primary.share-btn {
            padding: 6px 12px !important;
            font-size: 0.85rem !important;
            min-height: auto !important;
            line-height: 1.2 !important;
            height: auto !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .category-share-form .upload-label {
            cursor: pointer;
            color: var(--text-color);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 4px;
            background: var(--bg-secondary);
            transition: background-color 0.2s;
        }
        
        .category-share-form .upload-label:hover {
            background: var(--border-color);
            text-decoration: none;
        }
        
        .upload-hint {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        /* Modal pour les images */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .image-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .image-modal img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        
        .image-modal-close:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .image-modal-info {
            color: white;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* États de chargement */
        .loading-state {
            text-align: center;
            color: var(--text-muted);
            padding: 40px 20px;
        }
        
        .empty-state {
            text-align: center;
            color: var(--text-muted);
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 8px 15px;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .page-desc {
                font-size: 0.8rem;
            }
            
            .content-area {
                padding: 15px;
            }
            
            .category-post {
                padding: 12px;
                gap: 12px;
            }
            
            .category-post .post-image {
                max-width: 300px;
                max-height: 300px;
            }
            
            .category-share-form {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header compact -->
    <div class="page-header">
        <div class="header-left">
            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i> Accueil
            </a>
        </div>
        
        <div class="header-center">
            <h1 class="page-title" style="color:<?php echo htmlspecialchars($category['color'] ?? '#007bff'); ?>;">
                <?php echo htmlspecialchars($category['name']); ?>
            </h1>
            <?php if ($category['description']): ?>
                <p class="page-desc"><?php echo htmlspecialchars($category['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="header-right">
            <?php 
            // Vérifier si l'utilisateur est modérateur ou admin
            $roleManager = new RoleManager($pdo);
            $is_moderator = $roleManager->hasPermission($user['id'], 'moderate_categories') || 
                           $roleManager->hasPermission($user['id'], 'access_admin_panel');
            
            if ($is_moderator): ?>
            <button id="viewToggleBtn" class="btn view-toggle-btn" onclick="toggleViewMode()">
                <i class="fas fa-eye"></i> <span id="viewToggleText">Voir mes amis</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Zone de contenu principal -->
    <div class="content-area">
        <div class="category-posts-list" id="postsList">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i> Chargement des posts...
            </div>
        </div>
    </div>
    

    
    <!-- Formulaire en bas -->
    <form class="category-share-form" id="shareForm" enctype="multipart/form-data">
        <div class="form-container">
            <textarea name="content" id="postContent" placeholder="Partagez un message ou une image..."></textarea>
            <div class="image-preview" id="imagePreview" style="display:none;"></div>
            <div class="form-actions">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <label class="upload-label">
                        <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;">
                        <i class="fas fa-upload"></i> Image
                    </label>
                    <span class="upload-hint">ou Ctrl+V</span>
                </div>
                <button type="submit" class="btn btn-primary share-btn">Partager</button>
            </div>
        </div>
    </form>
    
    <!-- Modal pour les images -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <div class="image-modal-content">
            <img id="modalImage" src="" alt="Image en plein écran">
            <div class="image-modal-info">Cliquez en dehors de l'image ou sur X pour fermer</div>
        </div>
    </div>
    
    <script>
    // JS pour gestion collage/upload image et affichage preview
    let imageFile = null;
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const postContent = document.getElementById('postContent');
    const shareForm = document.getElementById('shareForm');
    const postsList = document.getElementById('postsList');
    const viewToggleBtn = document.getElementById('viewToggleBtn');
    const viewToggleText = document.getElementById('viewToggleText');

    let currentViewMode = 'all'; // 'all' or 'friends'

    function toggleViewMode() {
        currentViewMode = currentViewMode === 'all' ? 'friends' : 'all';
        viewToggleText.textContent = currentViewMode === 'all' ? 'Voir mes amis' : 'Voir tout';
        
        // Mettre à jour les styles du bouton
        if (currentViewMode === 'friends') {
            viewToggleBtn.classList.add('friends-mode');
        } else {
            viewToggleBtn.classList.remove('friends-mode');
        }
        
        loadPosts();
    }
    
    // Collage direct d'image
    postContent.addEventListener('paste', function(e) {
        if (e.clipboardData && e.clipboardData.items) {
            for (let item of e.clipboardData.items) {
                if (item.type.indexOf('image') !== -1) {
                    const file = item.getAsFile();
                    showImagePreview(file);
                    imageFile = file;
                    e.preventDefault();
                }
            }
        }
    });
    
    // Drag & drop sur la zone de texte
    postContent.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            if (file.type.startsWith('image/')) {
                showImagePreview(file);
                imageFile = file;
                e.preventDefault();
            }
        }
    });
    
    // Upload classique
    imageInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            showImagePreview(this.files[0]);
            imageFile = this.files[0];
        }
    });
    
    // Envoi avec Entrée, retour à la ligne avec Shift+Entrée
    postContent.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            shareForm.requestSubmit();
        }
    });
    
    function showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.innerHTML = `<img src="${e.target.result}" alt="Aperçu">`;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
    
    // Soumission du post
    shareForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Vérifier qu'il y a au moins du texte ou une image
        if (!postContent.value.trim() && !imageFile) {
            alert('Veuillez ajouter un message ou une image');
            return;
        }
        
        const formData = new FormData();
        formData.append('content', postContent.value);
        if (imageFile) formData.append('image', imageFile);
        formData.append('category_id', <?php echo $category_id; ?>);
        
        // Désactiver le bouton pendant l'envoi
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Envoi...';
        submitBtn.disabled = true;
        
        fetch('api/category_post.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                postContent.value = '';
                imagePreview.innerHTML = '';
                imagePreview.style.display = 'none';
                imageFile = null;
                loadPosts();
            } else {
                alert(data.error || 'Erreur lors du partage');
            }
        })
        .catch(() => alert('Erreur réseau'))
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Variables pour les posts
    let posts = [];
    
    // Chargement des posts
    function loadPosts() {
        postsList.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
        const url = currentViewMode === 'friends' 
            ? 'api/category_post.php?category_id=<?php echo $category_id; ?>&friends_only=true'
            : 'api/category_post.php?category_id=<?php echo $category_id; ?>';
        fetch(url)
            .then(res => res.json())
            .then(data => {
                console.log('API Response:', data); // Debug
                if (data.success) {
                    posts = data.posts || [];
                    if (posts.length > 0) {
                        displayPosts(posts);
                    } else {
                        postsList.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <p>Aucun post pour le moment.</p>
                                <p>Soyez le premier à partager quelque chose !</p>
                            </div>
                        `;
                    }
                } else {
                    postsList.innerHTML = '<div class="loading-state" style="color:red;"><i class="fas fa-exclamation-triangle"></i> Erreur: ' + (data.error || 'Erreur inconnue') + '</div>';
                }
            })
            .catch((error) => {
                console.error('Fetch error:', error); // Debug
                postsList.innerHTML = '<div class="loading-state" style="color:red;"><i class="fas fa-exclamation-triangle"></i> Erreur de chargement</div>';
            });
    }
    
    // Affichage des posts
    function displayPosts(postsToDisplay) {
        if (postsToDisplay.length === 0) {
            postsList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments"></i>
                    <p>Aucun post pour le moment.</p>
                    <p>Soyez le premier à partager quelque chose !</p>
                </div>
            `;
            return;
        }
        
        postsList.innerHTML = postsToDisplay.map(post => `
            <div class="category-post" data-post-id="${post.id}">
                ${post.image_url ? `<img src="${post.image_url}" class="post-image" alt="Image" onclick="openImageModal('${post.image_url}')">` : ''}
                <div class="post-content">
                    <div class="post-header">
                        <div class="post-author">${post.author}</div>
                        <div class="post-date">${post.created_at}</div>
                    </div>
                    <div class="post-text">${post.content ? post.content.replace(/\n/g, '<br>') : ''}</div>
                </div>
                ${post.can_delete ? `<div class="post-actions"><button class="btn-delete" onclick="deletePost(${post.id})"><i class="fas fa-trash"></i></button></div>` : ''}
            </div>
        `).join('');
    }
    

    
    // Suppression d'un post
    function deletePost(postId) {
        fetch('api/category_post.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadPosts();
            } else {
                alert(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(() => alert('Erreur réseau'));
    }
    
    // Fonctions pour le modal d'image
    function openImageModal(imageUrl) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        modalImage.src = imageUrl;
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Empêcher le scroll
    }
    
    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restaurer le scroll
    }
    
    // Fermer le modal en cliquant en dehors de l'image
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeImageModal();
        }
    });
    
    // Fermer le modal avec la touche Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
    
    // Charger les posts au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        loadPosts();
        
        // Focus sur le textarea
        postContent.focus();
        
        // Marquer la catégorie comme visitée
        setTimeout(() => {
            fetch('api/category_unread.php?action=mark_visited', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'category_id=<?php echo $category_id; ?>'
            }).catch(error => {
                console.log('Erreur lors du marquage de visite:', error);
            });
        }, 1000);
        
        // Configurer les callbacks pour les mises à jour en temps réel
        if (window.realtimeUpdates) {
            window.realtimeUpdates.onUpdate('category_posts', function(count) {
                // Recharger les posts de la catégorie
                loadPosts();
            });
        }
    });
    </script>
    
    <!-- Script pour les mises à jour en temps réel -->
    <script src="assets/js/realtime-updates.js"></script>
</body>
</html> 