# Esportify - Plateforme de Tournois E-sport

**Esportify** est une application web dédiée à l'organisation et à la gestion d'événements e-sport. Elle permet aux joueurs de s'inscrire aux tournois, aux organisateurs de créer et gérer leurs compétitions, et aux administrateurs de modérer la plateforme.

## Stack technique

- **Front-end** : HTML5, CSS3, Bootstrap 5, JavaScript (vanilla)
- **Back-end** : PHP 7.4+ avec PDO
- **Base de données relationnelle** : MySQL / MariaDB
- **Base de données NoSQL** : MongoDB (fil de discussion asynchrone - optionnel)
- **Déploiement** : Compatible avec la plupart des hébergeurs PHP (Alwaysdata, OVH, etc.)

## Prérequis

- Serveur web Apache ou Nginx avec PHP 7.4+
- Extension PHP `pdo_mysql` activée
- Extension PHP `mongodb` (optionnel, pour le chat)
- MySQL / MariaDB
- MongoDB (optionnel)
- Composer (pour l'autoload MongoDB)

## Installation en local

1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/votre-compte/esportify.git
   cd esportify
   ```

2. **Installer les dépendances Composer** (si MongoDB est utilisé) :
   ```bash
   composer install
   ```

3. **Créer la base de données** :
   - Importer le fichier `sql/esportify.sql` dans votre serveur MySQL.
   - Ce fichier crée la base `esportify`, les tables, les contraintes et les données de test.

4. **Configurer la connexion BDD** :
   - Modifier `config/database.php` avec vos identifiants MySQL.
   - Modifier `config/mongodb.php` si vous utilisez MongoDB.

5. **Lancer l'application** :
   - Placer le dossier `esportify/` dans votre répertoire web local (`htdocs`, `www`, etc.).
   - Accéder à `http://localhost/esportify/`.

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@esportify.fr | Password123! |
| Organisateur | org1@esportify.fr | Password123! |
| Organisateur | org2@esportify.fr | Password123! |
| Joueur | joueur1@esportify.fr | Password123! |
| Joueur | joueur2@esportify.fr | Password123! |

## Structure du projet

```
esportify/
├── assets/           # CSS, JS, images
├── config/           # Configuration BDD (PDO + MongoDB)
├── includes/         # Header, footer, fonctions utilitaires
├── pages/            # Pages du site (MVC simplifié)
├── api/              # Endpoints API (filtres asynchrones)
├── sql/              # Fichiers SQL de création de BDD
├── docs/             # Documentation, charte graphique, manuel
├── composer.json     # Dépendances PHP
└── index.php         # Routeur principal
```

## Fonctionnalités principales

- **Page d'accueil** avec diaporama et événements à venir
- **Vue globale des événements** avec filtres asynchrones (tri, organisateur, date, joueurs)
- **Système d'authentification** sécurisé avec hashage bcrypt et tokens CSRF
- **Espace Joueur** : inscriptions, favoris, historique, scores
- **Espace Organisateur** : création/modération d'événements, gestion des inscriptions, démarrage de session
- **Espace Administrateur** : validation/suspension d'événements, gestion des rôles, tableau de bord statistique
- **Respect RGPD** : pas de stockage de données sensibles en clair, droit d'accès et de suppression

## Sécurité

- Hashage des mots de passe avec `password_hash()` (bcrypt)
- Requêtes préparées PDO pour prévenir les injections SQL
- Tokens CSRF sur tous les formulaires et actions sensibles
- Échappement des sorties HTML avec `htmlspecialchars()`
- Gestion des rôles et contrôle d'accès sur chaque page

## Auteur

Projet réalisé dans le cadre de l'évaluation ECF DWWM.
