# Configuration de l'Authentification Discord OAuth2

## 🎯 Vue d'ensemble

Ce guide explique comment configurer l'authentification Discord OAuth2 pour votre site web.

## 📋 Prérequis

- Un domaine HTTPS (obligatoire pour Discord OAuth2)
- Un compte Discord
- Accès au [Discord Developer Portal](https://discord.com/developers/applications)

## 🛠️ Configuration Discord

### 1. Créer une application Discord

1. Allez sur [Discord Developer Portal](https://discord.com/developers/applications)
2. Cliquez sur **"New Application"**
3. Donnez un nom à votre application (ex: "MonSite Auth")
4. Cliquez sur **"Create"**

### 2. Configurer OAuth2

1. Dans le menu de gauche, cliquez sur **"OAuth2"**
2. Allez dans l'onglet **"Redirects"**
3. Cliquez sur **"Add Redirect"**
4. Ajoutez l'URL de callback :
   ```
   https://ladrio2.goodloss.fr/oauth2callback_discord.php
   ```
5. Cliquez sur **"Save Changes"**

### 3. Récupérer les identifiants

1. Dans l'onglet **"General Information"**
2. Notez le **"Application ID"** (c'est votre Client ID)
3. Dans l'onglet **"OAuth2"**
4. Cliquez sur **"Reset Secret"** pour générer un nouveau Client Secret
5. Notez le **"Client Secret"**

### 4. Configurer les scopes

1. Dans l'onglet **"OAuth2"**
2. Cochez les scopes suivants :
   - ✅ `identify` - Pour récupérer l'ID et le nom d'utilisateur
   - ✅ `email` - Pour récupérer l'adresse email

## 🔧 Configuration du code

### 1. Mettre à jour config_discord.php

Remplacez les valeurs dans `config_discord.php` :

```php
define('DISCORD_CLIENT_ID', 'VOTRE_CLIENT_ID');
define('DISCORD_CLIENT_SECRET', 'VOTRE_CLIENT_SECRET');
```

### 2. Vérifier l'URL de redirection

Assurez-vous que l'URL de redirection dans `config_discord.php` correspond exactement à celle configurée dans Discord :

```php
define('DISCORD_REDIRECT_URI', 'https://ladrio2.goodloss.fr/oauth2callback_discord.php');
```

## 🚀 Test de l'authentification

### 1. Test manuel

1. Allez sur votre site : `https://ladrio2.goodloss.fr/`
2. Cliquez sur **"Continuer avec Discord"**
3. Vous devriez être redirigé vers Discord
4. Autorisez l'application
5. Vous devriez être redirigé vers votre site et connecté

### 2. Vérification des données

Après connexion, vérifiez dans votre base de données que :
- Un nouvel utilisateur a été créé dans la table `users`
- Le champ `auth_provider` est défini à `'discord'`
- Le champ `provider_id` contient l'ID Discord de l'utilisateur
- Le champ `avatar_url` contient l'URL de l'avatar Discord (si disponible)

## 🔍 Dépannage

### Erreur "Invalid redirect_uri"

- Vérifiez que l'URL de redirection dans Discord correspond exactement à celle dans votre code
- Assurez-vous que le protocole est HTTPS

### Erreur "Invalid client"

- Vérifiez que le Client ID et Client Secret sont corrects
- Assurez-vous qu'ils sont bien définis dans `config_discord.php`

### Erreur "Code expired"

- Le code d'autorisation expire rapidement
- L'utilisateur doit cliquer sur le bouton Discord rapidement après l'affichage

### Erreur de base de données

- Vérifiez que les tables `users` et `user_sessions` existent
- Vérifiez les permissions de la base de données

## 🔐 Sécurité

### Bonnes pratiques

1. **Ne jamais exposer le Client Secret** dans le code côté client
2. **Utiliser HTTPS** pour toutes les communications
3. **Valider les données** reçues de Discord
4. **Gérer les erreurs** de manière appropriée

### Protection des données

- Les données Discord sont stockées de manière sécurisée
- Les mots de passe ne sont pas stockés pour les utilisateurs Discord
- Les sessions sont sécurisées avec des tokens aléatoires

## 📝 Notes importantes

- L'authentification Discord nécessite HTTPS
- Les utilisateurs doivent avoir un compte Discord
- L'email Discord est requis pour l'authentification
- L'avatar Discord est automatiquement récupéré si disponible

## 🆘 Support

En cas de problème :

1. Vérifiez les logs d'erreur PHP
2. Vérifiez la console du navigateur (F12)
3. Testez l'URL de callback directement
4. Vérifiez la configuration Discord Developer Portal 