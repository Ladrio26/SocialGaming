# Intégration Twitch - SocialGaming

## 🎯 Fonctionnalités

L'intégration Twitch permet aux utilisateurs de :

1. **Lier leur compte Twitch** à leur profil SocialGaming
2. **Voir les streams en direct** de leurs amis sur la page principale
3. **Afficher leurs propres streams** dans leur profil
4. **Actualiser automatiquement** les informations de stream

## 🔧 Configuration

### Variables d'environnement requises

Les variables suivantes sont déjà configurées dans `config.php` :

```php
define('TWITCH_CLIENT_ID', 'jzojy4wm2z60g0hzoyab9czujkx82b');
define('TWITCH_CLIENT_SECRET', 'otdxdmkmdv4zwsfnz29awldb7y6crl');
define('TWITCH_REDIRECT_URI', 'http://localhost:8000/twitch_callback.php');
define('TWITCH_UPDATE_SECRET', '393f716e99723bba2a60aee9384bdc5934cf8ad738855212fd7bef2a4409f192');
```

### Configuration Twitch Developer Console

1. Allez sur [Twitch Developer Console](https://dev.twitch.tv/console)
2. Créez une nouvelle application ou modifiez l'existante
3. Configurez l'URL de redirection : `http://localhost:8000/twitch_callback.php`
4. Notez le Client ID et Client Secret

## 📊 Base de données

### Tables créées

- `twitch_accounts` : Comptes Twitch liés aux utilisateurs
- `twitch_streams` : Cache des streams en direct

### Structure des tables

```sql
-- Comptes Twitch liés
CREATE TABLE twitch_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    twitch_user_id VARCHAR(50) NOT NULL UNIQUE,
    twitch_username VARCHAR(100) NOT NULL,
    twitch_display_name VARCHAR(100) NOT NULL,
    twitch_profile_image_url VARCHAR(500),
    twitch_access_token VARCHAR(500) NOT NULL,
    twitch_refresh_token VARCHAR(500) NOT NULL,
    twitch_token_expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Streams en direct (cache)
CREATE TABLE twitch_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    twitch_user_id VARCHAR(50) NOT NULL,
    twitch_username VARCHAR(100) NOT NULL,
    twitch_display_name VARCHAR(100) NOT NULL,
    twitch_profile_image_url VARCHAR(500),
    stream_id VARCHAR(50) NOT NULL,
    stream_title VARCHAR(200),
    stream_game_name VARCHAR(100),
    stream_viewer_count INT DEFAULT 0,
    stream_started_at TIMESTAMP,
    stream_thumbnail_url VARCHAR(500),
    stream_language VARCHAR(10),
    is_live BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🚀 Utilisation

### Pour les utilisateurs

1. **Connexion** : Allez sur votre profil → Onglet "Twitch" → "Se connecter avec Twitch"
2. **Voir les streams** : Les streams en direct apparaissent sur la page principale
3. **Gérer le compte** : Actualiser ou délier le compte depuis le profil

### Pour les développeurs

#### API Endpoints

- `GET /api/twitch.php?action=status` : Statut du compte lié
- `GET /api/twitch.php?action=link` : Générer l'URL d'authentification
- `GET /api/twitch.php?action=unlink` : Délier le compte
- `GET /api/twitch.php?action=streams&limit=10` : Récupérer les streams
- `GET /api/twitch.php?action=update_streams&secret=XXX` : Mettre à jour le cache

#### Classes PHP

- `Twitch` : Gestion de l'API Twitch et des tokens
- `TwitchManager` : Gestion de l'interface utilisateur (JavaScript)

## 🔄 Mise à jour automatique

### Script de mise à jour

Le script `update_twitch_streams.php` met à jour automatiquement le cache des streams.

### Configuration Cron Job

Ajoutez cette ligne à votre crontab pour une mise à jour toutes les 5 minutes :

```bash
*/5 * * * * php /path/to/your/project/update_twitch_streams.php?secret=393f716e99723bba2a60aee9384bdc5934cf8ad738855212fd7bef2a4409f192
```

### Test manuel

```bash
php update_twitch_streams.php?secret=393f716e99723bba2a60aee9384bdc5934cf8ad738855212fd7bef2a4409f192
```

## 🎨 Interface utilisateur

### Page principale
- Section "Streams en direct" avec cartes des streams
- Actualisation automatique toutes les 5 minutes
- Liens directs vers les streams Twitch

### Page profil
- Onglet "Twitch" pour gérer le compte
- Affichage des informations du profil Twitch
- Boutons pour actualiser/délier le compte

### Styles CSS
- Design cohérent avec le thème sombre
- Animations et effets hover
- Responsive design

## 🔒 Sécurité

- **OAuth2** : Authentification sécurisée via Twitch
- **Tokens** : Stockage sécurisé des tokens d'accès
- **Refresh** : Renouvellement automatique des tokens
- **Secret** : Protection du script de mise à jour

## 🐛 Dépannage

### Erreurs courantes

1. **"Accès refusé"** : Vérifiez le secret dans l'URL
2. **"Token expiré"** : Le refresh automatique devrait résoudre le problème
3. **"Aucun stream"** : Vérifiez que les comptes sont bien liés

### Logs

Vérifiez les logs PHP pour les erreurs d'API :
```bash
tail -f /var/log/php_errors.log
```

## 📈 Améliorations futures

- [ ] Notifications de nouveaux streams
- [ ] Intégration avec les jeux Steam
- [ ] Statistiques de streaming
- [ ] Chat intégré
- [ ] Clips et VODs 