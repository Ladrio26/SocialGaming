document.addEventListener('DOMContentLoaded', () => {
    const categoriesList = document.getElementById('categoriesList');
    if (!categoriesList) return;

    // Ajouter le bouton de proposition en haut
    const proposeButton = document.createElement('div');
    proposeButton.className = 'propose-category-btn';
    proposeButton.innerHTML = `
        <a href="propose_category.php" class="btn btn-primary btn-sm btn-full">
            <i class="fas fa-plus"></i> Proposer une catégorie
        </a>
    `;
    categoriesList.appendChild(proposeButton);

    // Ajouter le bouton de gestion des catégories
    const manageButton = document.createElement('div');
    manageButton.className = 'manage-categories-btn';
    manageButton.innerHTML = `
        <a href="manage_categories.php" class="btn btn-secondary btn-sm btn-full">
            <i class="fas fa-folder-open"></i> Gérer mes catégories
        </a>
    `;
    categoriesList.appendChild(manageButton);

    // Ajouter un séparateur
    const separator = document.createElement('div');
    separator.className = 'categories-separator';
    separator.innerHTML = '<hr>';
    categoriesList.appendChild(separator);

    // Conteneur pour les catégories
    const categoriesContainer = document.createElement('div');
    categoriesContainer.className = 'categories-container';
    categoriesList.appendChild(categoriesContainer);

    fetch('api/categories.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.categories.length > 0) {
                categoriesContainer.innerHTML = data.categories.map(cat => `
                    <a class="category-item" href="category.php?id=${cat.id}" style="border-left: 6px solid ${cat.color}">
                        <span class="category-icon"><i class="${cat.icon}"></i></span>
                        <span class="category-info">
                            <span class="category-name">${cat.name}</span>
                            <span class="category-desc">${cat.description || ''}</span>
                        </span>
                        ${cat.unread_count > 0 ? `<span class="unread-badge">${cat.unread_count}</span>` : ''}
                    </a>
                `).join('');
            } else {
                categoriesContainer.innerHTML = '<p style="color:var(--text-muted);text-align:center">Aucune catégorie</p>';
            }
        })
        .catch(() => {
            categoriesContainer.innerHTML = '<p style="color:red;text-align:center">Erreur de chargement</p>';
        });
}); 