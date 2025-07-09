# SocialGaming

Une plateforme sociale de gaming moderne qui permet aux joueurs de se connecter, partager leurs jeux Steam, intégrer leurs comptes Discord et Twitch, et interagir avec d'autres joueurs dans un environnement communautaire riche.

## 🎮 Fonctionnalités

### 🔐 Authentification Multi-Plateforme
- Connexion/Inscription classique (email/mot de passe)
- Authentification OAuth2 avec Discord (complet)
- Authentification OAuth2 avec Steam
- Authentification OAuth2 avec Twitch
- Gestion des avatars depuis les comptes liés
- Système de rôles et permissions avancé

### 👤 Profils Utilisateurs Avancés
- Profils personnalisables avec avatars
- Affichage des jeux Steam avec temps de jeu
- Système d'amis complet
- Visites de profils avec notifications
- Badges de modération et rôles
- Visibilité des profils configurable

### 🤝 Système d'Amis
- Envoi/Acceptation de demandes d'amis
- Liste d'amis avec gestion complète
- Consultation des amis d'autres utilisateurs
- Recherche de joueurs avec jeux en commun

### 🔔 Système de Notifications Avancé
- Notifications en temps réel
- Notifications pour demandes d'amis, visites de profil, etc.
- Interface de gestion des notifications (marquer comme lu, supprimer)
- Polling automatique pour nouvelles notifications
- Système de notifications optimisé

### 🎯 Système de Catégories
- Création et gestion de catégories de discussion
- Propositions de nouvelles catégories par les utilisateurs
- Système de modération des catégories
- Suivi des posts non lus par catégorie
- Interface de gestion des préférences

### 🎥 Intégration Twitch
- Liaison de compte Twitch via OAuth2
- Affichage des streams en direct
- Intégration des streams dans l'interface
- Mise à jour automatique des statuts de stream

### 🎮 Intégration Steam Complète
- Liaison de compte Steam via OAuth2
- Récupération automatique des jeux et statistiques
- Recherche de joueurs avec jeux en commun
- Affichage des temps de jeu détaillés
- Mise à jour automatique des informations Steam

### 🔍 Recherche Intelligente
- Recherche d'utilisateurs en temps réel
- Navigation clavier dans les résultats
- Accès direct aux profils depuis la recherche
- Recherche optimisée avec indexation

### 🎨 Interface Utilisateur Moderne
- **Thème sombre** par défaut (respecte vos préférences)
- Interface responsive et moderne
- Contrôles en temps réel
- Animations fluides et transitions
- Design cohérent sur toutes les pages

### 🛡️ Système de Modération
- Interface d'administration complète
- Gestion des rôles utilisateurs
- Système de badges de modération
- Outils de modération avancés
- Logs d'activité et audit

### 📱 Fonctionnalités Avancées
- Mises à jour en temps réel
- Système de posts récents
- Gestion des avatars avec upload
- Optimisations de base de données
- Système de cache intelligent

## 🛠️ Technologies

- **Backend** : PHP 8.4, MariaDB/MySQL
- **Frontend** : HTML5, CSS3, JavaScript (ES6+)
- **Authentification** : OAuth2 (Discord, Steam, Twitch)
- **Base de données** : MariaDB avec PDO
- **API** : RESTful APIs en PHP
- **Temps réel** : Polling et WebSockets
- **Upload** : Gestion d'images et avatars

## 📁 Structure du Projet

```
SocialGaming/
├── api/                    # APIs REST
│   ├── auth.php           # Authentification
│   ├── friends.php        # Gestion des amis
│   ├── notifications.php  # Système de notifications
│   ├── steam.php          # Intégration Steam
│   ├── discord.php        # Intégration Discord
│   ├── twitch.php         # Intégration Twitch
│   ├── categories.php     # Système de catégories
│   ├── admin_change_role.php # Administration
│   └── ...
├── assets/
│   ├── css/               # Styles CSS (thème sombre)
│   └── js/                # JavaScript modulaire
│       ├── auth.js        # Authentification
│       ├── friends.js     # Gestion amis
│       ├── notifications.js # Notifications
│       ├── categories.js  # Système catégories
│       ├── twitch.js      # Intégration Twitch
│       ├── discord.js     # Intégration Discord
│       └── theme.js       # Gestion thème
├── classes/               # Classes PHP
│   ├── Auth.php          # Gestion authentification
│   ├── Notification.php  # Système notifications
│   ├── Friends.php       # Gestion amis
│   ├── Discord.php       # Intégration Discord
│   ├── Twitch.php        # Intégration Twitch
│   ├── Steam.php         # Intégration Steam
│   └── ...
├── includes/              # Fichiers inclus
│   ├── header.php        # En-tête commun
│   ├── RoleManager.php   # Gestion des rôles
│   └── ...
├── database/              # Scripts de base de données
│   ├── categories.sql    # Tables catégories
│   ├── discord_integration.sql # Tables Discord
│   └── twitch_integration.sql  # Tables Twitch
├── admin.php             # Interface d'administration
├── moderation.php        # Outils de modération
├── category.php          # Gestion des catégories
├── config.php            # Configuration DB
├── index.php             # Page principale
├── profile.php           # Gestion profils
├── friends.php           # Page amis
└── README.md
```

## 🚀 Installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/Ladrio26/SocialGaming.git
   cd SocialGaming
   ```

2. **Configuration de la base de données**
   - Créer une base de données MariaDB/MySQL
   - Exécuter les scripts SQL dans le dossier `database/`
   - Copier `config.php.example` vers `config.php`
   - Modifier les paramètres de connexion dans `config.php`

3. **Configuration OAuth**
   - **Discord** : Créer une application Discord et configurer OAuth2
   - **Steam** : Obtenir une clé API Steam
   - **Twitch** : Créer une application Twitch pour OAuth2
   - Configurer les URLs de redirection pour chaque plateforme

4. **Configuration des permissions**
   - Exécuter `setup_roles.php` pour créer les rôles de base
   - Configurer les permissions d'administration

5. **Lancer le serveur**
   ```bash
   php -S localhost:8000
   ```

## 📊 Base de Données

Le projet utilise plusieurs tables organisées :

### Tables Utilisateurs
- `users` : Utilisateurs et leurs informations
- `user_roles` : Rôles et permissions
- `user_avatars` : Gestion des avatars

### Tables Steam
- `steam_accounts` : Comptes Steam liés
- `steam_profiles` : Profils Steam
- `steam_games` : Jeux Steam

### Tables Discord
- `discord_accounts` : Comptes Discord liés
- `discord_guilds` : Serveurs Discord

### Tables Twitch
- `twitch_accounts` : Comptes Twitch liés
- `twitch_streams` : Informations de stream

### Tables Sociales
- `friends` : Relations d'amis
- `friend_requests` : Demandes d'amis
- `notifications` : Système de notifications

### Tables Catégories
- `categories` : Catégories de discussion
- `category_posts` : Posts dans les catégories
- `category_proposals` : Propositions de catégories

## 🔧 Configuration

### Variables d'environnement requises

#### Discord
- `DISCORD_CLIENT_ID` : ID client Discord OAuth2
- `DISCORD_CLIENT_SECRET` : Secret client Discord OAuth2
- `DISCORD_REDIRECT_URI` : URL de redirection Discord

#### Steam
- `STEAM_API_KEY` : Clé API Steam
- `STEAM_REDIRECT_URI` : URL de redirection Steam

#### Twitch
- `TWITCH_CLIENT_ID` : ID client Twitch OAuth2
- `TWITCH_CLIENT_SECRET` : Secret client Twitch OAuth2
- `TWITCH_REDIRECT_URI` : URL de redirection Twitch

### Permissions OAuth2

#### Discord
- `identify` : Récupérer les informations de base
- `email` : Récupérer l'email (optionnel)
- `guilds` : Accès aux serveurs Discord

#### Twitch
- `user:read:email` : Accès à l'email
- `channel:read:subscriptions` : Accès aux abonnements

## 📚 Documentation

- `DISCORD_SETUP.md` : Guide de configuration Discord
- `TWITCH_INTEGRATION.md` : Guide d'intégration Twitch
- `TIMEZONE_MIGRATION.md` : Migration des fuseaux horaires

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🎯 Roadmap

- [x] Intégration Discord complète
- [x] Intégration Twitch
- [x] Système de catégories
- [x] Interface de modération
- [x] Thème sombre
- [x] Système de rôles avancé
- [ ] Chat en temps réel
- [ ] Système de groupes
- [ ] Intégration YouTube
- [ ] Application mobile
- [ ] API publique
- [ ] Système de recommandations
- [ ] Statistiques avancées
- [ ] Thèmes personnalisables

## 📞 Support

Pour toute question ou problème, n'hésitez pas à ouvrir une issue sur GitHub.

---

**SocialGaming** - Connectez-vous, partagez, jouez ensemble ! 🎮✨ 