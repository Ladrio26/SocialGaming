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
            $first_name = trim($input['first_name'] ?? '');
            $last_name = trim($input['last_name'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            // Validation : soit nom+prénom, soit pseudo obligatoire
            $has_names = !empty($first_name) && !empty($last_name);
            $has_username = !empty($username);
            
            if (!$has_names && !$has_username) {
                echo json_encode(['success' => false, 'message' => 'Vous devez remplir soit le pseudo, soit le nom et prénom']);
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
            
            // Validation des noms si fournis
            if ($has_names) {
                if (strlen($first_name) < 2) {
                    echo json_encode(['success' => false, 'message' => 'Le prénom doit contenir au moins 2 caractères']);
                    exit;
                }
                
                if (strlen($last_name) < 2) {
                    echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 2 caractères']);
                    exit;
                }
                
                if (strlen($first_name) > 30) {
                    echo json_encode(['success' => false, 'message' => 'Le prénom ne peut pas dépasser 30 caractères']);
                    exit;
                }
                
                if (strlen($last_name) > 30) {
                    echo json_encode(['success' => false, 'message' => 'Le nom ne peut pas dépasser 30 caractères']);
                    exit;
                }
            }
            
            $result = $auth->registerManual($username, $email, $password, $first_name, $last_name);
            echo json_encode($result);
            break;
            
        case 'login':
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
                exit;
            }
            
            $result = $auth->loginManual($email, $password);
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