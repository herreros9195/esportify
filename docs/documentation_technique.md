# Documentation Technique - Esportify

## 1. Réflexions technologiques initiales

Pour répondre aux besoins du client Esportify, plusieurs technologies ont été envisagées :

- **Framework PHP (Symfony/Laravel)** : trop lourd pour un MVP, mais envisageable pour la scalabilité future.
- **PHP procédural structuré** : choisi pour sa simplicité de déploiement sur hébergement mutualisé et sa lisibilité pour un projet de formation.
- **Base relationnelle (MySQL)** : obligatoire pour la gestion transactionnelle des inscriptions, événements et utilisateurs.
- **Base NoSQL (MongoDB)** : utilisée pour le fil de discussion asynchrone, permettant une structure flexible des messages.
- **Front-end vanilla** : HTML5, CSS3, Bootstrap 5 et JS natif suffisent pour les besoins actuels sans complexité inutile de React/Vue.

## 2. Configuration de l'environnement de travail

### Local (développement)
- **Serveur** : Apache 2.4 + PHP 8.1 (XAMPP/WAMP)
- **BDD relationnelle** : MySQL 8.0 via phpMyAdmin
- **BDD NoSQL** : MongoDB Community Edition + MongoDB Compass (optionnel)
- **IDE** : VS Code avec extensions PHP Intelephense
- **Versionning** : Git avec branches `main`, `develop` et feature branches

### Production
- Hébergement PHP/MySQL mutualisé ou cloud (Alwaysdata, OVH, PlanetHoster)
- MongoDB Atlas si le chat est activé

## 3. Modèle Conceptuel de Données (MCD)

```
[USERS] 1--* [EVENTS] (organise)
[USERS] 1--* [EVENT_REGISTRATIONS] *--1 [EVENTS]
[USERS] 1--* [FAVORITES] *--1 [EVENTS]
[USERS] 1--* [SCORES] *--1 [EVENTS]
[EVENTS] 1--* [EVENT_IMAGES]
```

Entités principales :
- **users** : stockage des comptes (pseudo, email, password_hash, role)
- **events** : caractéristiques des tournois (titre, description, dates, jauge, statut, visible)
- **event_registrations** : liaison N-N entre utilisateurs et événements avec statut
- **favorites** : liaison N-N pour les événements favoris
- **scores** : liaison N-N avec valeur de score
- **event_images** : images associées aux événements (1-N)

## 4. Diagrammes UML

### Diagramme de cas d'utilisation

```
        +------------------+
        |   Visiteur       |
        +------------------+
               | Consulter événements
               v
        +------------------+
        |   Joueur         |---> S'inscrire à un événement
        +------------------+---> Gérer ses favoris
               |                Consulter ses scores
               v
        +------------------+
        |   Organisateur   |---> Créer/modifier un événement
        +------------------+---> Gérer les inscriptions
               |                Démarrer un événement
               v
        +------------------+
        |  Administrateur  |---> Valider/suspendre événements
        +------------------+---> Gérer les rôles utilisateurs
                                 Accéder au tableau de bord
```

### Diagramme de séquence (Inscription à un événement)

```
Joueur         Frontend         Backend (PHP)       MySQL
  |                |                  |                |
  |-- Clic détails ----------------->|                |
  |                |<-- Modal HTML ---|                |
  |-- Clic inscrire ---------------->|                |
  |                |                  |-- INSERT reg --|
  |                |                  |<-- OK ---------|
  |                |<-- Redirection --|                |
  |<-- Flash succès|                  |                |
```

## 5. Architecture applicative

Le projet suit une architecture **MVC simplifiée** :

- **Modèle** : requêtes PDO directes dans les pages (pas de ORM pour garder la maîtrise SQL)
- **Vue** : templates PHP inclus (header/footer) avec Bootstrap
- **Contrôleur** : pages PHP dans `/pages/` qui traitent les requêtes et incluent les vues

### Points d'entrée
- `index.php` : routeur frontal basé sur `?page=`
- `api/filter_events.php` : endpoint API REST-like pour les filtres asynchrones

## 6. Sécurité implémentée

| Menace | Contre-mesure |
|--------|---------------|
| Injection SQL | Requêtes préparées PDO avec paramètres nommés |
| XSS | Échappement systématique avec `htmlspecialchars()` |
| CSRF | Tokens générés par session et vérifiés sur chaque action POST/GET sensible |
| Vol de session | Cookies de session sécurisés, régénération possible |
| Fuites de données | Pas de stockage de données bancaires, hashage bcrypt des mots de passe |
| Élévation de privilèges | Vérification du rôle sur chaque page protégée |

## 7. Déploiement

### Étapes de déploiement

1. **Préparation du serveur**
   - Vérifier la version PHP (>= 7.4)
   - Activer les extensions `pdo_mysql` et `mongodb` (optionnel)

2. **Transfert des fichiers**
   - Déployer l'ensemble du dossier `esportify/` via FTP/SFTP ou Git

3. **Base de données**
   - Créer une base MySQL sur l'hébergeur
   - Importer `sql/esportify.sql`
   - Adapter `config/database.php` avec les nouveaux identifiants

4. **Configuration**
   - Si MongoDB Atlas est utilisé, mettre à jour `config/mongodb.php`
   - Configurer les sessions PHP si nécessaire (`php.ini`)

5. **Tests**
   - Vérifier la connexion (login/logout)
   - Tester la création d'un événement
   - Vérifier les filtres asynchrones
   - Contrôler les permissions (accès admin/organisateur)

6. **Mise en production**
   - Activer HTTPS (certificat SSL)
   - Désactiver l'affichage des erreurs PHP (`display_errors = Off`)
   - Configurer les logs serveur
