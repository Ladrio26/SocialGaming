# Migration vers le fuseau horaire de Paris

## Résumé des changements

Ce document décrit les modifications apportées pour convertir tous les affichages d'heure de GMT vers le fuseau horaire de Paris/France.

## Fichiers modifiés

### Configuration
- **`config.php`** : Ajout de `date_default_timezone_set('Europe/Paris')`

### Nouveaux fichiers utilitaires
- **`includes/date_utils.php`** : Fonctions PHP pour formater les dates avec le fuseau horaire de Paris
- **`assets/js/date-utils.js`** : Fonctions JavaScript pour formater les dates côté client
- **`test_timezone.php`** : Script de test pour vérifier la conversion

### Fichiers PHP modifiés
- **`profile.php`** : Remplacement des affichages de dates
- **`api/category_post.php`** : Utilisation des nouvelles fonctions de formatage
- **`api/friends.php`** : Conversion des logs de debug
- **`debug_posts.php`** : Affichage des dates avec le bon fuseau horaire
- **`classes/Twitch.php`** : Conversion des dates d'expiration des tokens
- **`classes/Auth.php`** : Conversion des dates d'expiration des sessions

### Fichiers JavaScript modifiés
- **`assets/js/friends.js`** : Utilisation des nouvelles fonctions de formatage
- **`assets/js/search.js`** : Conversion des dates d'inscription
- **`assets/js/notifications.js`** : Simplification du calcul de temps relatif
- **`assets/js/twitch.js`** : Utilisation de la fonction de formatage de durée
- **`assets/js/twitch-live.js`** : Utilisation de la fonction de formatage de durée

### Pages HTML modifiées
- **`index.php`** : Inclusion du fichier `date-utils.js`
- **`profile.php`** : Inclusion du fichier `date-utils.js`
- **`friends.php`** : Inclusion du fichier `date-utils.js`
- **`common_games.php`** : Inclusion du fichier `date-utils.js`

## Fonctions disponibles

### Côté PHP (`includes/date_utils.php`)

- `formatDateParis($dateString, $format)` : Formate une date avec le fuseau horaire de Paris
- `formatDateShort($dateString)` : Format court (dd/mm/yyyy)
- `formatDateLong($dateString)` : Format long avec nom du mois en français (15 janvier 2024)
- `formatDateTime($dateString)` : Format avec heure (dd/mm/yyyy hh:mm)
- `formatDateRelative($dateString)` : Format relatif ("il y a X temps")
- `getCurrentDateParis()` : Obtient la date actuelle au format MySQL avec le fuseau horaire de Paris

### Côté JavaScript (`assets/js/date-utils.js`)

- `DateUtils.formatDateParis(dateString, format)` : Formate une date côté client
- `DateUtils.getRelativeTime(date)` : Calcule le temps relatif
- `DateUtils.formatStreamDuration(startTimeString)` : Formate la durée d'un stream
- `DateUtils.utcToParis(utcDateString)` : Convertit UTC vers Paris

## Formats disponibles

- `'short'` : 15/01/2024
- `'long'` : 15 janvier 2024 (avec noms des mois en français)
- `'datetime'` : 15/01/2024 14:30
- `'relative'` : Il y a 2h, Il y a 3j, etc.

## Test de la migration

Pour tester que la conversion fonctionne correctement, accédez à :
```
https://ladrio2.goodloss.fr/test_timezone.php
```

Ce script affichera :
- Des exemples de conversion de dates UTC vers Paris
- La configuration actuelle du fuseau horaire
- Des tests de formatage relatif

## Impact sur les utilisateurs

✅ **Avantages :**
- Toutes les dates sont maintenant affichées dans le fuseau horaire de Paris
- Cohérence dans l'affichage des heures sur tout le site
- Meilleure expérience utilisateur pour les utilisateurs français

⚠️ **Points d'attention :**
- Les dates stockées en base restent en UTC (bonne pratique)
- Seul l'affichage est converti vers Paris
- Les nouveaux utilisateurs verront immédiatement les bonnes heures
- Les utilisateurs existants verront les heures corrigées lors de leur prochaine visite

## Maintenance

Pour ajouter de nouvelles fonctionnalités de date :

1. **Côté PHP** : Utilisez les fonctions de `includes/date_utils.php`
2. **Côté JavaScript** : Utilisez les méthodes de la classe `DateUtils`
3. **N'oubliez pas** d'inclure `date-utils.js` dans les nouvelles pages

## Exemple d'utilisation

```php
// PHP
echo formatDateShort($user['created_at']); // 15/01/2024
echo formatDateLong($user['created_at']); // 15 janvier 2024
echo formatDateTime($post['created_at']); // 15/01/2024 14:30
echo formatDateRelative($notification['created_at']); // Il y a 2h
```

```javascript
// JavaScript
const date = DateUtils.formatDateParis(user.created_at, 'long'); // 15 janvier 2024
const relative = DateUtils.getRelativeTime(new Date(user.created_at)); // Il y a 3j
const duration = DateUtils.formatStreamDuration(stream.started_at); // 2h 30m
``` 