<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);

// Vérifier si l'utilisateur est connecté
$user = $auth->isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Aucun fichier sélectionné';
        if (isset($_FILES['avatar'])) {
            switch ($_FILES['avatar']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'Le fichier est trop volumineux';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'Le fichier n\'a été que partiellement uploadé';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'Aucun fichier n\'a été uploadé';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = 'Dossier temporaire manquant';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = 'Erreur d\'écriture sur le disque';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = 'Une extension PHP a arrêté l\'upload';
                    break;
            }
        }
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }
    
    $file = $_FILES['avatar'];
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_type = $file['type'];
    
    // Validation du type de fichier
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF']);
        exit;
    }
    
    // Validation de la taille (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file_size > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux. Taille maximum : 5MB']);
        exit;
    }
    
    // Validation de l'extension
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Extension de fichier non autorisée']);
        exit;
    }
    
    try {
        // Vérifier que c'est bien une image
        $image_info = getimagesize($file_tmp);
        if ($image_info === false) {
            echo json_encode(['success' => false, 'message' => 'Le fichier n\'est pas une image valide']);
            exit;
        }
        
        // Créer un nom de fichier unique
        $new_file_name = 'avatar_' . $user['id'] . '_' . time() . '.' . $file_extension;
        $upload_path = '/home/ladrio/Websites/src/ladrio2.goodloss.fr/uploads/avatars/' . $new_file_name;
        
        // Debug: vérifier les informations du fichier
        error_log("Fichier temporaire: " . $file_tmp);
        error_log("Chemin de destination: " . $upload_path);
        error_log("Fichier temporaire existe: " . (file_exists($file_tmp) ? 'Oui' : 'Non'));
        error_log("Dossier de destination existe: " . (is_dir(dirname($upload_path)) ? 'Oui' : 'Non'));
        error_log("Dossier de destination accessible en écriture: " . (is_writable(dirname($upload_path)) ? 'Oui' : 'Non'));
        
        // Copier le fichier tel quel (sans redimensionnement car GD n'est pas disponible)
        if (!copy($file_tmp, $upload_path)) {
            $error = error_get_last();
            $error_message = 'Erreur lors de la sauvegarde de l\'image';
            if ($error) {
                $error_message .= ' : ' . $error['message'];
            }
            echo json_encode([
                'success' => false, 
                'message' => $error_message,
                'debug' => [
                    'tmp_name' => $file_tmp,
                    'upload_path' => $upload_path,
                    'tmp_exists' => file_exists($file_tmp),
                    'dir_exists' => is_dir(dirname($upload_path)),
                    'dir_writable' => is_writable(dirname($upload_path)),
                    'php_error' => $error
                ]
            ]);
            exit;
        }
        
        // Supprimer l'ancien avatar s'il existe
        if ($user['avatar_url'] && !strpos($user['avatar_url'], 'discordapp.com')) {
            $old_avatar_path = '/home/ladrio/Websites/src/ladrio2.goodloss.fr/' . ltrim($user['avatar_url'], '/');
            if (file_exists($old_avatar_path)) {
                unlink($old_avatar_path);
            }
        }
        
        // Mettre à jour la base de données
        $avatar_url = 'uploads/avatars/' . $new_file_name;
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatar_url, $user['id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar mis à jour avec succès',
            'avatar_url' => $avatar_url
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du traitement de l\'image : ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 