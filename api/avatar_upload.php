<?php
require_once '../config.php';
require_once '../classes/Auth.php';
header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Vous devez être connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $max_size = 5 * 1024 * 1024; // 5 Mo
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Vérifier qu'une image a été uploadée
    if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Aucune image sélectionnée ou erreur d\'upload']);
        exit;
    }
    
    $file = $_FILES['avatar'];
    
    // Vérifier le type de fichier
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP']);
        exit;
    }
    
    // Vérifier la taille
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'Image trop volumineuse (maximum 5 Mo)']);
        exit;
    }
    
    // Créer le dossier d'upload s'il n'existe pas
    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier d\'upload']);
            exit;
        }
    }
    
    // Vérifier que le dossier est accessible en écriture
    if (!is_writable($upload_dir)) {
        echo json_encode(['success' => false, 'error' => 'Le dossier d\'upload n\'est pas accessible en écriture']);
        exit;
    }
    
    // Générer un nom de fichier unique
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'avatar_' . $user['id'] . '_' . uniqid() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    // Supprimer l'ancien avatar s'il existe
    if ($user['avatar_url'] && !strpos($user['avatar_url'], 'discordapp.com')) {
        $old_avatar_path = '../' . ltrim($user['avatar_url'], '/');
        if (file_exists($old_avatar_path)) {
            unlink($old_avatar_path);
        }
    }
    
    // Déplacer le nouveau fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $error_details = error_get_last();
        $upload_error = "Erreur lors de l'enregistrement de l'image";
        
        // Log détaillé pour debug
        error_log("Avatar upload error - File: " . $file['name'] . ", Size: " . $file['size'] . ", Temp: " . $file['tmp_name'] . ", Dest: " . $filepath);
        error_log("PHP Error: " . print_r($error_details, true));
        
        // Vérifier les permissions
        if (!is_writable($upload_dir)) {
            $upload_error .= " - Dossier non accessible en écriture";
        }
        
        echo json_encode(['success' => false, 'error' => $upload_error]);
        exit;
    }
    
    // Mettre à jour la base de données
    $avatar_url = 'uploads/avatars/' . $filename;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatar_url, $user['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar mis à jour avec succès',
            'avatar_url' => $avatar_url
        ]);
        
    } catch (PDOException $e) {
        // Supprimer le fichier si l'update en base échoue
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour du profil']);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
?> 