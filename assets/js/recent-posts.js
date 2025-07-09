class RecentPostsManager {
    constructor() {
        console.log('RecentPostsManager: Constructeur appelé');
        this.postsContainer = document.getElementById('recentPostsContainer');
        this.loadingElement = document.getElementById('recentPostsLoading');
        this.emptyElement = document.getElementById('recentPostsEmpty');
        
        console.log('RecentPostsManager: Éléments trouvés:', {
            postsContainer: !!this.postsContainer,
            loadingElement: !!this.loadingElement,
            emptyElement: !!this.emptyElement
        });
        
        this.init();
    }

    init() {
        console.log('RecentPostsManager: Init appelé');
        if (this.postsContainer) {
            console.log('RecentPostsManager: Chargement des posts...');
            this.loadRecentPosts();
        } else {
            console.error('RecentPostsManager: postsContainer non trouvé');
        }
    }

    async loadRecentPosts() {
        try {
            console.log('RecentPostsManager: Début du chargement des posts');
            this.showLoading();
            
            const response = await fetch('api/recent_posts.php');
            console.log('RecentPostsManager: Réponse reçue:', response.status);
            
            const data = await response.json();
            console.log('RecentPostsManager: Données reçues:', data);
            
            if (data.success) {
                if (data.posts && data.posts.length > 0) {
                    console.log('RecentPostsManager: Affichage de', data.posts.length, 'posts');
                    this.displayPosts(data.posts);
                } else {
                    console.log('RecentPostsManager: Aucun post trouvé');
                    this.showEmpty(data.message || 'Aucun post récent trouvé');
                }
            } else {
                console.error('RecentPostsManager: Erreur API:', data.error);
                this.showError(data.error || 'Erreur lors du chargement');
            }
        } catch (error) {
            console.error('RecentPostsManager: Erreur lors du chargement des posts récents:', error);
            this.showError('Erreur de connexion');
        }
    }

    displayPosts(posts) {
        this.hideLoading();
        this.hideEmpty();
        
        const postsHTML = posts.map(post => this.createPostHTML(post)).join('');
        this.postsContainer.innerHTML = postsHTML;
        
        // Ajouter les événements pour les images
        this.setupImageModals();
    }

    createPostHTML(post) {
        const avatar = post.author.avatar_url 
            ? `<img src="${this.escapeHtml(post.author.avatar_url)}" alt="Avatar" class="recent-post-avatar">`
            : `<div class="recent-post-avatar-placeholder"><i class="fas fa-user"></i></div>`;
        
        return `
            <div class="recent-post-card" data-post-id="${post.id}">
                <div class="recent-post-header">
                    <a href="profile.php?user_id=${post.author.id}" class="recent-post-author">
                        ${avatar}
                        <div class="recent-post-author-info">
                            <div class="recent-post-author-name">${post.author.username}</div>
                            <div class="recent-post-meta">
                                <span class="recent-post-category">${post.category_name}</span>
                                <span class="recent-post-date">${post.created_at}</span>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="recent-post-content">
                    ${post.content ? `<div class="recent-post-text">${post.content}</div>` : ''}
                    <div class="recent-post-image-container">
                        <img src="${this.escapeHtml(post.image_url)}" 
                             alt="Image partagée" 
                             class="recent-post-image"
                             onclick="recentPostsManager.openImageModal('${this.escapeHtml(post.image_url)}')">
                    </div>
                </div>
                
                <div class="recent-post-actions">
                    <a href="category.php?id=${post.category_id}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-folder"></i> Voir la catégorie
                    </a>
                </div>
            </div>
        `;
    }

    setupImageModals() {
        // Les modals sont gérés par la fonction openImageModal
    }

    openImageModal(imageUrl) {
        // Créer le modal s'il n'existe pas
        let modal = document.getElementById('recentPostsImageModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'recentPostsImageModal';
            modal.className = 'image-modal';
            modal.innerHTML = `
                <span class="image-modal-close" onclick="recentPostsManager.closeImageModal()">&times;</span>
                <div class="image-modal-content">
                    <img id="recentPostsModalImage" src="" alt="Image en plein écran">
                    <div class="image-modal-info">Cliquez en dehors de l'image ou sur X pour fermer</div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Fermer le modal en cliquant en dehors
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeImageModal();
                }
            });
        }
        
        // Afficher l'image
        const modalImage = document.getElementById('recentPostsModalImage');
        modalImage.src = imageUrl;
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    closeImageModal() {
        const modal = document.getElementById('recentPostsImageModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    showLoading() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'block';
        }
        if (this.postsContainer) {
            this.postsContainer.style.display = 'none';
        }
        if (this.emptyElement) {
            this.emptyElement.style.display = 'none';
        }
    }

    hideLoading() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'none';
        }
        if (this.postsContainer) {
            this.postsContainer.style.display = 'block';
        }
    }

    showEmpty(message) {
        this.hideLoading();
        if (this.emptyElement) {
            this.emptyElement.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <p>${message}</p>
                    <p>Vos amis n'ont pas encore partagé d'images récemment.</p>
                </div>
            `;
            this.emptyElement.style.display = 'block';
        }
        if (this.postsContainer) {
            this.postsContainer.style.display = 'none';
        }
    }

    hideEmpty() {
        if (this.emptyElement) {
            this.emptyElement.style.display = 'none';
        }
    }

    showError(message) {
        this.hideLoading();
        if (this.emptyElement) {
            this.emptyElement.innerHTML = `
                <div class="empty-state error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur: ${message}</p>
                </div>
            `;
            this.emptyElement.style.display = 'block';
        }
        if (this.postsContainer) {
            this.postsContainer.style.display = 'none';
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Ajouter un nouveau post avec animation
     */
    addNewPost(post) {
        if (!this.postsContainer) return;
        
        // Créer le HTML du nouveau post
        const postHTML = this.createPostHTML(post);
        const postElement = document.createElement('div');
        postElement.innerHTML = postHTML;
        
        // Ajouter la classe d'animation
        const postCard = postElement.firstElementChild;
        postCard.classList.add('new-post');
        
        // Insérer en haut de la liste
        this.postsContainer.insertBefore(postCard, this.postsContainer.firstChild);
        
        // Supprimer la classe d'animation après l'animation
        setTimeout(() => {
            postCard.classList.remove('new-post');
        }, 400);
        
        // Supprimer l'état vide s'il existe
        this.hideEmpty();
        
        // Limiter le nombre de posts affichés (garder les 10 plus récents)
        const posts = this.postsContainer.querySelectorAll('.recent-post-card');
        if (posts.length > 10) {
            for (let i = 10; i < posts.length; i++) {
                posts[i].remove();
            }
        }
    }
    
    /**
     * Mettre à jour tous les posts
     */
    updatePosts(posts) {
        this.displayPosts(posts);
    }
}

// Test simple pour vérifier que le script se charge
console.log('recent-posts.js chargé');

// Initialiser le gestionnaire de posts récents
let recentPostsManager;

// Fonction d'initialisation
function initRecentPosts() {
    console.log('RecentPostsManager: Initialisation...');
    try {
        recentPostsManager = new RecentPostsManager();
        // Rendre accessible globalement pour les mises à jour fluides
        window.recentPostsManager = recentPostsManager;
        console.log('RecentPostsManager: Initialisation réussie');
    } catch (error) {
        console.error('RecentPostsManager: Erreur lors de l\'initialisation:', error);
    }
}

// Attendre que le DOM soit prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRecentPosts);
} else {
    // Le DOM est déjà prêt
    initRecentPosts();
} 