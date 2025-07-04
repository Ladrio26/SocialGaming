# SocialGaming

Une plateforme sociale de gaming qui permet aux joueurs de se connecter, partager leurs jeux Steam, et interagir avec d'autres joueurs.

## 🎮 Fonctionnalités

### Authentification
- Connexion/Inscription classique (email/mot de passe)
- Authentification OAuth2 avec Discord
- Authentification OAuth2 avec Steam
- Gestion des avatars depuis les comptes liés

### Profils Utilisateurs
- Profils personnalisables avec avatars
- Affichage des jeux Steam
- Système d'amis complet
- Visites de profils

### Système d'Amis
- Envoi/Acceptation de demandes d'amis
- Liste d'amis avec gestion
- Consultation des amis d'autres utilisateurs

### Notifications
- Système de notifications en temps réel
- Notifications pour demandes d'amis, visites de profil, etc.
- Interface de gestion des notifications (marquer comme lu, supprimer)
- Polling automatique pour nouvelles notifications

### Intégration Steam
- Liaison de compte Steam via OAuth2
- Récupération automatique des jeux et statistiques
- Recherche de joueurs avec jeux en commun
- Affichage des temps de jeu

### Recherche
- Recherche d'utilisateurs en temps réel
- Navigation clavier dans les résultats
- Accès direct aux profils depuis la recherche

## 🛠️ Technologies

- **Backend** : PHP 8.4, MariaDB/MySQL
- **Frontend** : HTML5, CSS3, JavaScript (ES6+)
- **Authentification** : OAuth2 (Discord, Steam)
- **Base de données** : MariaDB avec PDO
- **API** : RESTful APIs en PHP

## 📁 Structure du Projet

```
SocialGaming/
├── api/                    # APIs REST
│   ├── auth.php           # Authentification
│   ├── friends.php        # Gestion des amis
│   ├── notifications.php  # Système de notifications
│   ├── steam.php          # Intégration Steam
│   └── ...
├── assets/
│   ├── css/               # Styles CSS
│   └── js/                # JavaScript
├── classes/               # Classes PHP
│   ├── Auth.php          # Gestion authentification
│   ├── Notification.php  # Système notifications
│   ├── Friends.php       # Gestion amis
│   └── ...
├── config.php            # Configuration DB (non commité)
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
   - Copier `config.php.example` vers `config.php`
   - Modifier les paramètres de connexion dans `config.php`

3. **Configuration OAuth**
   - Créer une application Discord (pour OAuth2 Discord)
   - Créer une application Steam (pour OAuth2 Steam)
   - Configurer les URLs de redirection

4. **Lancer le serveur**
   ```bash
   php -S localhost:8000
   ```

## 📊 Base de Données

Le projet utilise plusieurs tables :
- `users` : Utilisateurs et leurs informations
- `steam_accounts` : Comptes Steam liés
- `steam_profiles` : Profils Steam
- `steam_games` : Jeux Steam
- `friends` : Relations d'amis
- `friend_requests` : Demandes d'amis
- `notifications` : Système de notifications

## 🔧 Configuration

### Variables d'environnement requises
- `DISCORD_CLIENT_ID` : ID client Discord OAuth2
- `DISCORD_CLIENT_SECRET` : Secret client Discord OAuth2
- `STEAM_API_KEY` : Clé API Steam

### Permissions Discord
- `identify` : Récupérer les informations de base
- `email` : Récupérer l'email (optionnel)

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

- [ ] Chat en temps réel
- [ ] Système de groupes
- [ ] Intégration Twitch/YouTube
- [ ] Application mobile
- [ ] API publique
- [ ] Système de recommandations
- [ ] Statistiques avancées
- [ ] Thèmes personnalisables

## 📞 Support

Pour toute question ou problème, n'hésitez pas à ouvrir une issue sur GitHub. 