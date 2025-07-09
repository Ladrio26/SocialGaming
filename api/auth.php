<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'register':
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            // Validation du pseudo obligatoire
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Le pseudo est obligatoire']);
                exit;
            }
            
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Email et mot de passe sont obligatoires']);
                exit;
            }
            
            // Validation du nom d'utilisateur si fourni
            if ($has_username) {
                if (strlen($username) < 3) {
                    echo json_encode(['success' => false, 'message' => 'Le pseudo doit contenir au moins 3 caractères']);
                    exit;
                }
                
                if (strlen($username) > 20) {
                    echo json_encode(['success' => false, 'message' => 'Le pseudo ne peut pas dépasser 20 caractères']);
                    exit;
                }
                
                // Vérifier que le nom d'utilisateur ne contient que des caractères autorisés
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                    echo json_encode(['success' => false, 'message' => 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores']);
                    exit;
                }
            }
            

            
            $result = $auth->registerManual($username, $email, $password, '', '');
            echo json_encode($result);
            break;
            
        case 'login':
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Pseudo et mot de passe requis']);
                exit;
            }
            
            $result = $auth->loginManual($username, $password);
            echo json_encode($result);
            break;
            
        case 'logout':
            $result = $auth->logout();
            echo json_encode($result);
            break;
            
        case 'check_auth':
            $user = $auth->isLoggedIn();
            if ($user) {
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Non connecté']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?> 