<?php
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/Twitch.php';

session_start();

$auth = new Auth($pdo);
$twitch = new Twitch($pdo);

try {
    // Vérifier si nous avons un code d'autorisation
    if (!isset($_GET['code'])) {
        throw new Exception('Code d\'autorisation manquant');
    }
    
    // Vérifier le state pour la sécurité
    if (isset($_GET['state'])) {
        // Le state sera vérifié côté client via JavaScript
        // Pour l'instant, on accepte le state
        $state = $_GET['state'];
    }
    
    $code = $_GET['code'];
    
    // Échanger le code contre un token d'accès
    $token_response = $twitch->exchangeCodeForToken($code);
    
    // Récupérer les informations de l'utilisateur Twitch
    $twitch_user_info = $twitch->getUserInfo($token_response['access_token']);
    
    // Vérifier si un utilisateur avec ce compte Twitch existe déjà
    $stmt = $pdo->prepare("SELECT user_id FROM twitch_accounts WHERE twitch_user_id = ?");
    $stmt->execute([$twitch_user_info['id']]);
    $existing_user_id = $stmt->fetchColumn();
    
    if ($existing_user_id) {
        // L'utilisateur existe déjà, le connecter
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$existing_user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Mettre à jour les tokens Twitch
            $twitch->updateTokens(
                $existing_user_id,
                $token_response['access_token'],
                $token_response['refresh_token'],
                $token_response['expires_in']
            );
            
            // Connecter l'utilisateur
            $session_result = $auth->createSession($user['id']);
            
            // Nettoyer le state de session
            // Le state sera nettoyé côté client
            
            // Rediriger vers la page d'accueil
            header('Location: index.php?auth_success=Connexion réussie avec Twitch');
            exit;
        }
    }
    
    // Créer un nouvel utilisateur
    $username = $twitch_user_info['login'];
    $email = $twitch_user_info['email'] ?? null;
    
    // Vérifier si le nom d'utilisateur est disponible
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        // Le nom d'utilisateur existe déjà, ajouter un suffixe
        $counter = 1;
        $original_username = $username;
        do {
            $username = $original_username . '_' . $counter;
            $stmt->execute([$username]);
            $counter++;
        } while ($stmt->fetch());
    }
    
    // Créer l'utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, auth_provider, avatar_url, created_at) 
        VALUES (?, ?, 'twitch', ?, NOW())
    ");
    $stmt->execute([
        $username,
        $email,
        $twitch_user_info['profile_image_url'] ?? null
    ]);
    
    $user_id = $pdo->lastInsertId();
    
    // Lier le compte Twitch
    $success = $twitch->linkAccount(
        $user_id,
        $twitch_user_info['id'],
        $twitch_user_info['login'],
        $twitch_user_info['display_name'],
        $twitch_user_info['profile_image_url'] ?? null,
        $token_response['access_token'],
        $token_response['refresh_token'],
        $token_response['expires_in']
    );
    
    if ($success) {
        // Connecter l'utilisateur
        $session_result = $auth->createSession($user_id);
        
        // Nettoyer le state de session
        // Le state sera nettoyé côté client
        
        // Rediriger vers la page d'accueil
        header('Location: index.php?auth_success=Inscription et connexion réussies avec Twitch');
        exit;
    } else {
        throw new Exception('Erreur lors de la création du compte');
    }
    
} catch (Exception $e) {
    // Nettoyer le state de session
    // Le state sera nettoyé côté client
    
    // Afficher l'erreur pour le débogage
    echo "<h1>Erreur d'authentification Twitch</h1>";
    echo "<p><strong>Erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code :</strong> " . htmlspecialchars($_GET['code'] ?? 'Aucun') . "</p>";
    echo "<p><strong>State :</strong> " . htmlspecialchars($_GET['state'] ?? 'Aucun') . "</p>";
    echo "<p><a href='index.php'>Retour à l'accueil</a></p>";
    exit;
}
?> 