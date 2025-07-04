<?php
require_once '../config.php';
require_once '../classes/Auth.php';
require_once '../classes/Steam.php';
require_once '../config_steam.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$user = $auth->isLoggedIn();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$steam = new Steam($pdo, $steam_api_key);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'info':
                // Déterminer quel utilisateur consulter
                $target_user_id = $user['id']; // Par défaut, l'utilisateur connecté
                
                // Si un user_id est spécifié et que c'est différent de l'utilisateur connecté
                if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
                    $requested_user_id = (int)$_GET['user_id'];
                    
                    // Vérifier que l'utilisateur demandé existe
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt->execute([$requested_user_id]);
                    if ($stmt->fetch()) {
                        $target_user_id = $requested_user_id;
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
                        exit;
                    }
                }
                
                // Récupérer les informations Steam de l'utilisateur cible
                $steam_info = $steam->getSteamInfo($target_user_id);
                
                if ($steam_info) {
                    // Ajouter le code ami
                    $steam_info['friend_code'] = $steam->getSteamFriendCode($steam_info['steam_id']);
                    
                    // Formater les temps de jeu
                    if (isset($steam_info['games'])) {
                        foreach ($steam_info['games'] as &$game) {
                            $game['playtime_formatted'] = formatPlaytime($game['playtime_forever']);
                            $game['playtime_2weeks_formatted'] = formatPlaytime($game['playtime_2weeks']);
                        }
                    }
                    
                    echo json_encode(['success' => true, 'data' => $steam_info]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Aucun compte Steam lié']);
                }
                break;
                
            case 'games':
                // Récupérer uniquement les jeux
                $games = $steam->getSteamGames($user['id']);
                
                // Formater les temps de jeu
                foreach ($games as &$game) {
                    $game['playtime_formatted'] = formatPlaytime($game['playtime_forever']);
                    $game['playtime_2weeks_formatted'] = formatPlaytime($game['playtime_2weeks']);
                }
                
                echo json_encode(['success' => true, 'data' => $games]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'verify':
                // Vérifier la propriété d'un compte Steam
                $input = json_decode(file_get_contents('php://input'), true);
                $steam_id_input = $input['steam_id'] ?? '';
                
                if (empty($steam_id_input)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Steam ID requis']);
                    exit;
                }
                
                // Convertir le Steam ID si nécessaire
                $steam_id = $steam->convertSteamId($steam_id_input);
                
                if (!$steam_id) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Format de Steam ID invalide']);
                    exit;
                }
                
                // Valider le Steam ID
                if (!$steam->validateSteamId($steam_id)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Steam ID invalide']);
                    exit;
                }
                
                $result = $steam->verifySteamOwnership($steam_id);
                echo json_encode($result);
                break;
                
            case 'check_verification':
                // Vérifier le code de vérification
                $input = json_decode(file_get_contents('php://input'), true);
                $steam_id_input = $input['steam_id'] ?? '';
                $verification_code = $input['verification_code'] ?? '';
                
                if (empty($steam_id_input) || empty($verification_code)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Steam ID et code de vérification requis']);
                    exit;
                }
                
                // Convertir le Steam ID si nécessaire
                $steam_id = $steam->convertSteamId($steam_id_input);
                
                if (!$steam_id) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Format de Steam ID invalide']);
                    exit;
                }
                
                $result = $steam->checkVerificationCode($steam_id, $verification_code);
                
                if ($result['success']) {
                    // Si la vérification réussit, lier automatiquement le compte
                    $link_result = $steam->linkSteamAccount($user['id'], $steam_id);
                    if ($link_result['success']) {
                        echo json_encode(['success' => true, 'message' => 'Compte Steam vérifié et lié avec succès !']);
                    } else {
                        echo json_encode($link_result);
                    }
                } else {
                    echo json_encode($result);
                }
                break;
                
            case 'link':
                // Lier un compte Steam (maintenant obsolète, remplacé par verify + check_verification)
                echo json_encode(['success' => false, 'message' => 'Utilisez d\'abord la vérification de propriété']);
                break;
                
            case 'unlink':
                // Délier le compte Steam
                $result = $steam->unlinkSteamAccount($user['id']);
                echo json_encode($result);
                break;
                
            case 'refresh':
                // Actualiser les informations Steam
                $steam_info = $steam->getSteamInfo($user['id']);
                
                if ($steam_info) {
                    // Forcer la mise à jour des informations
                    $steam->updateSteamInfo($user['id'], $steam_info['steam_id']);
                    
                    // Récupérer les nouvelles informations
                    $updated_info = $steam->getSteamInfo($user['id']);
                    $updated_info['friend_code'] = $steam->getSteamFriendCode($updated_info['steam_id']);
                    
                    // Formater les temps de jeu
                    if (isset($updated_info['games'])) {
                        foreach ($updated_info['games'] as &$game) {
                            $game['playtime_formatted'] = formatPlaytime($game['playtime_forever']);
                            $game['playtime_2weeks_formatted'] = formatPlaytime($game['playtime_2weeks']);
                        }
                    }
                    
                    echo json_encode(['success' => true, 'data' => $updated_info, 'message' => 'Informations Steam actualisées']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Aucun compte Steam lié']);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

// Fonction pour formater le temps de jeu
function formatPlaytime($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    } elseif ($minutes < 1440) {
        $hours = floor($minutes / 60);
        return $hours . 'h ' . ($minutes % 60) . 'min';
    } else {
        $days = floor($minutes / 1440);
        $hours = floor(($minutes % 1440) / 60);
        return $days . 'j ' . $hours . 'h';
    }
}
?> 