# Documentation Technique - Esportify

## 1. Reflexions technologiques initiales

Pour repondre au besoin d'Esportify, plusieurs options techniques ont ete comparees avant de retenir une architecture simple, lisible et facile a deployer dans le cadre du projet.

- **Framework PHP complet (Symfony / Laravel)** : solution robuste mais trop lourde pour un MVP d'ECF.
- **PHP structure sans framework** : solution retenue pour garder un projet lisible, deployable partout et simple a justifier.
- **Base relationnelle MySQL / MariaDB** : indispensable pour les utilisateurs, les evenements, les inscriptions, les favoris et les scores.
- **Base NoSQL MongoDB** : utilisee pour le fil de discussion asynchrone quand elle est disponible.
- **Front-end HTML / CSS / Bootstrap / JavaScript natif** : choix suffisant pour une interface responsive et des interactions simples sans surcouche inutile.

## 2. Configuration de l'environnement de travail

### Local

L'environnement local retenu repose sur :

- Apache 2.4 + PHP 8.1 sous WAMP / XAMPP ;
- MySQL 8.0 via phpMyAdmin ;
- MongoDB Community Edition ou MongoDB Atlas en option ;
- VS Code avec extensions PHP et Git ;
- Git avec branches `main`, `develop` et `feature/*` ;
- une configuration Mongo locale possible via `config/mongodb.local.php`, fichier ignore par Git.

### Production

En production, le projet prend en compte :

- un hebergement PHP / MySQL mutualise ou cloud ;
- la lecture de `DATABASE_URL` pour la base relationnelle ;
- la lecture de `MONGODB_URI` et `MONGODB_DB` si le chat MongoDB est active ;
- l'absence de secrets Atlas dans le depot et dans les livrables PDF.

## 3. Modele Conceptuel de Donnees

Le schema principal s'appuie sur les tables suivantes :

- `users`
- `events`
- `event_images`
- `event_registrations`
- `favorites`
- `scores`
- `chat_messages`

Les relations principales sont les suivantes :

- un utilisateur peut organiser plusieurs evenements ;
- un evenement peut avoir plusieurs images ;
- un utilisateur peut avoir plusieurs inscriptions, favoris, scores et messages ;
- un evenement peut etre relie a plusieurs inscriptions, favoris, scores et messages.

Un export visuel du MCD accompagne cette documentation :

- `docs/diagramme_mcd.png`

## 4. Diagrammes UML

### Diagramme d'utilisation

Le diagramme d'utilisation met en scene les acteurs et leurs actions principales :

- le **visiteur** qui consulte l'accueil, les evenements et la page contact ;
- le **joueur** qui se connecte, s'inscrit aux tournois, gere ses favoris et consulte ses scores ;
- l'**organisateur** qui publie des evenements, gere les inscriptions et demarre les sessions ;
- l'**administrateur** qui valide, suspend, gere les roles et alimente les scores.

Un export visuel du diagramme accompagne cette documentation :

- `docs/diagramme_utilisation.png`

### Diagramme de sequence

Le diagramme de sequence retenu illustre le parcours d'inscription a un evenement :

1. le joueur ouvre la fiche evenement ;
2. le front transmet l'action avec le token CSRF ;
3. la page metier verifie le role, les dates et la disponibilite ;
4. l'inscription est creee en base ;
5. un retour utilisateur est affiche avec redirection.

Un export visuel du diagramme accompagne cette documentation :

- `docs/diagramme_sequence.png`

## 5. Architecture applicative

Le projet suit une organisation MVC legere :

- **Modele** : acces PDO aux donnees ;
- **Vue** : templates PHP, composants communs, Bootstrap ;
- **Controleur** : vues PHP dans `public/` pilotees par `index.php`.

Les points d'entree principaux sont les suivants :

- `index.php`
- `api/filter_events.php`
- `api/chat.php`

## 6. Regles metier principales

- tout utilisateur connecte peut proposer un evenement depuis son espace joueur ;
- un evenement cree ou modifie passe au statut `en_attente` et doit etre revalide avant de redevenir public ;
- un evenement peut contenir plusieurs images grace a la table `event_images` ;
- l'organisateur peut demarrer sa session jusqu'a 30 minutes avant l'heure de debut ;
- le joueur ne peut **rejoindre** l'evenement qu'a partir de l'heure de debut reelle et seulement si l'organisateur a demarre la session ;
- la page contact reste publique et ne demande pas de compte ;
- le chat asynchrone MongoDB, avec fallback MySQL, reste accessible uniquement aux joueurs acceptes et pendant la fenetre effective de l'evenement.

## 7. Securite implemente

| Menace | Contre-mesure |
|--------|---------------|
| Injection SQL | Requetes preparees PDO |
| XSS | Echappement avec `htmlspecialchars()` |
| CSRF | Token de session verifie sur les actions sensibles |
| Vol de session | Controle des droits et gestion stricte des espaces |
| Fuites de donnees | Hashage bcrypt des mots de passe |
| Elevation de privileges | Verification des roles sur les parcours proteges |
| Exposition de secrets | Variables d'environnement ou fichier local ignore par Git pour Atlas |

## 8. Deploiement

La demarche de deploiement retenue suit les etapes principales suivantes :

1. deployer le dossier `esportify/` ;
2. creer la base MySQL et importer `sql/esportify.sql` ;
3. configurer `config/database.php` ou `DATABASE_URL` ;
4. configurer `MONGODB_URI` et `MONGODB_DB` si le chat NoSQL est active ;
5. tester la connexion, les filtres, la creation d'evenements et les permissions ;
6. activer HTTPS et desactiver `display_errors` en production.
