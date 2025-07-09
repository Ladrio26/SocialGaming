<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'includes/RoleManager.php';

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    header('Location: index.php');
    exit;
}

$roleManager = new RoleManager($pdo);

if (!$roleManager->hasPermission($user['id'], 'access_admin_panel')) {
    header('Location: index.php');
    exit;
}

// Récupération des données
$search = $_GET['search'] ?? '';
$roleFilter = isset($_GET['role']) && $_GET['role'] !== '' ? (int)$_GET['role'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$users = $roleManager->searchUsers($search, $roleFilter, $limit, $offset);
$totalUsers = $roleManager->countUsers($search, $roleFilter);
$totalPages = ceil($totalUsers / $limit);
$roles = $roleManager->getAllRoles();
$adminInfo = $roleManager->getUserRole($user['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Social Gaming</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .admin-header h1 {
            color: #333;
            margin: 0;
        }
        .admin-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #ddd;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 2rem;
        }
        .stat-card p {
            margin: 0;
            color: #666;
            font-weight: 600;
        }
        .search-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .search-filters form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .users-table tr:hover {
            background: #f8f9fa;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .role-id-1 { background: #dc3545; color: white; }
        .role-id-2 { background: #ffc107; color: #212529; }
        .role-id-3 { background: #28a745; color: white; }
        .role-id-4 { background: #6c757d; color: white; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background: #f8f9fa;
        }
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .debug-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="debug-info">
            <h3>Debug Info</h3>
            <p><strong>Utilisateur connecté :</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Rôle :</strong> <?php echo htmlspecialchars($adminInfo['role_name']); ?></p>
            <p><strong>Total utilisateurs :</strong> <?php echo $totalUsers; ?></p>
            <p><strong>Utilisateurs récupérés :</strong> <?php echo count($users); ?></p>
            <p><strong>Page actuelle :</strong> <?php echo $page; ?> / <?php echo $totalPages; ?></p>
        </div>

        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1><i class="fas fa-shield-alt"></i> Administration</h1>
                <div class="admin-info">
                    Connecté en tant que <strong><?php echo htmlspecialchars($adminInfo['username']); ?></strong> 
                    (<?php echo htmlspecialchars($adminInfo['role_name']); ?>)
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Retour à l'accueil
            </a>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $totalUsers; ?></h3>
                <p>Utilisateurs total</p>
            </div>
            <?php foreach ($roles as $role): ?>
            <div class="stat-card">
                <h3><?php echo $roleManager->countUsers('', $role['id']); ?></h3>
                <p><?php echo htmlspecialchars($role['name']); ?>s</p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recherche et filtres -->
        <div class="search-filters">
            <form method="GET" action="admin_no_header.php">
                <div class="form-group">
                    <label for="search">Rechercher un utilisateur</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Pseudo ou email...">
                </div>
                <div class="form-group">
                    <label for="role">Filtrer par rôle</label>
                    <select id="role" name="role">
                        <option value="">Tous les rôles</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" 
                                <?php echo $roleFilter === $role['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <a href="admin_no_header.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Tableau des utilisateurs -->
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                            Aucun utilisateur trouvé
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if ($user['avatar_url']): ?>
                                        <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" 
                                             alt="Avatar" class="user-avatar">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 600;">
                                            <a href="profile.php?user_id=<?php echo $user['id']; ?>" 
                                               style="color: #007bff; text-decoration: none;">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #666;">ID: <?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-id-<?php echo $user['role_id']; ?>">
                                    <?php echo htmlspecialchars($user['role_name']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> Voir profil
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
            <h3>Test de comparaison :</h3>
            <p>Si cette page s'affiche correctement, le problème vient du header.</p>
            <a href="admin.php" target="_blank" class="btn btn-primary">Tester admin.php original</a>
            <a href="admin_simple.php" target="_blank" class="btn btn-secondary">Tester admin_simple.php</a>
        </div>
    </div>
</body>
</html> 