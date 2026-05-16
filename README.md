# Esportify - Plateforme de tournois e-sport

**Esportify** est une application web dediee a l'organisation et a la gestion d'evenements e-sport. La plateforme permet de consulter les tournois publics, de creer des evenements, de gerer les inscriptions, de suivre les scores et d'administrer la moderation.

## Stack technique

- **Front-end** : HTML5, CSS3, Bootstrap 5, JavaScript natif
- **Back-end** : PHP 7.4+ avec PDO
- **Base de donnees relationnelle** : MySQL / MariaDB
- **Base de donnees NoSQL** : MongoDB Atlas en option pour le fil de discussion
- **Deploiement** : compatible avec les hebergeurs PHP classiques et Railway

## Prerequis

- un serveur web Apache ou Nginx avec PHP 7.4+ ;
- l'extension PHP `pdo_mysql` activee ;
- l'extension PHP `mongodb` si le chat MongoDB est active ;
- MySQL ou MariaDB ;
- MongoDB Atlas en option ;
- Git pour le versionnement.

---

## 1. Installation en local

### 1.1. Recuperation du projet

Une installation locale type suit les etapes suivantes :

```bash
git clone https://github.com/herreros9195/esportify.git
cd esportify
```

### 1.2. Configuration MySQL

1. Ouvrir **phpMyAdmin** ou un client SQL equivalent.
2. Creer une base de donnees nommee `esportify`.
3. Importer le fichier `sql/esportify.sql`.
4. Verifier que les tables et les donnees de test ont bien ete chargees.

### 1.3. Configuration de la connexion PDO

La configuration relationnelle par defaut se trouve dans `config/database.php` :

```php
$host = 'localhost';
$dbname = 'esportify';
$username = 'root';
$password = '';
```

### 1.4. Fuseau horaire PHP

Le projet suppose un fuseau `Europe/Paris`. Une configuration locale classique ajoute donc :

```ini
date.timezone = Europe/Paris
```

puis un redemarrage des services PHP / Apache.

---

## 2. Configuration MongoDB Atlas

Le chat fonctionne aussi sans MongoDB grace au fallback MySQL, mais MongoDB Atlas reste la solution prevue pour la partie NoSQL.

### 2.1. Preparation Atlas

La mise en place type comprend :

1. la creation d'un compte gratuit sur [MongoDB Atlas](https://www.mongodb.com/atlas) ;
2. la creation d'un cluster ;
3. l'ajout d'une regle reseau autorisant l'acces depuis l'environnement de test ;
4. la creation d'un utilisateur de base de donnees ;
5. la recuperation de l'URI de connexion de type :

```text
mongodb+srv://nom_utilisateur:mot_de_passe@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority
```

### 2.2. Configuration locale et production

Deux approches propres sont prevues :

- **en local** : dupliquer `config/mongodb.local.example.php` en `config/mongodb.local.php` ;
- **en production** : definir `MONGODB_URI` et `MONGODB_DB` comme variables d'environnement.

Exemple de fichier local :

```php
<?php
return [
    'uri' => 'mongodb+srv://nom_utilisateur:mot_de_passe@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority',
    'db' => 'esportify_nosql',
];
```

> `config/mongodb.local.php` est ignore par Git. Aucun secret Atlas n'a vocation a etre committe ou recopie dans la copie a rendre.

### 2.3. Extension PHP MongoDB

Une configuration locale classique comprend :

1. l'installation du module `mongodb` compatible avec la version de PHP ;
2. l'activation de l'extension dans `php.ini` ;
3. le redemarrage du serveur web ;
4. une verification rapide dans `phpinfo()`.

---

## 3. Workflow Git

### 3.1. Branches

```text
main        : branche stable
develop     : branche d'integration
feature/*   : une branche par fonctionnalite
```

### 3.2. Cycle de travail type

```bash
git checkout develop
git pull origin develop
git checkout -b feature/nom-fonctionnalite

git add .
git commit -m "Ajout de la fonctionnalite"

git checkout develop
git merge feature/nom-fonctionnalite
git push origin develop
```

### 3.3. Premier push GitHub

```bash
git remote add origin https://github.com/herreros9195/esportify.git
git branch -M main
git push -u origin main
git push -u origin develop
git push origin --all
```

---

## 4. Lancement local

Le lancement local suit en general cette logique :

1. placer le dossier `esportify/` dans le repertoire web local ;
2. verifier la base SQL ;
3. acceder a `http://localhost/esportify/` ;
4. tester l'affichage de l'accueil, la connexion et le catalogue des evenements.

---

## 5. Comptes de test

| Role | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@esportify.fr | Password123! |
| Organisateur | org1@esportify.fr | Password123! |
| Organisateur | org2@esportify.fr | Password123! |
| Joueur | joueur1@esportify.fr | Password123! |
| Joueur | joueur2@esportify.fr | Password123! |

---

## 6. Structure du projet

```text
esportify/
|-- api/              # Endpoints AJAX
|-- assets/           # CSS, JS, images
|-- config/           # Configuration SQL et MongoDB
|-- docs/             # Documentation de remise
|-- includes/         # Header, footer, fonctions communes
|-- logs/             # Sorties locales et mails simules
|-- public/           # Vues appelees par le routeur
|-- sql/              # Creation et chargement SQL
|-- vendor/           # Dependances Composer
|-- index.php         # Routeur principal
```

---

## 7. Fonctionnalites principales

- page d'accueil avec diaporama et evenements visibles ;
- vue globale des evenements avec filtres asynchrones ;
- authentification securisee avec hashage bcrypt et tokens CSRF ;
- espace joueur avec inscriptions, favoris, historique et scores ;
- creation et modification d'evenements a venir depuis l'espace joueur ;
- espace organisateur pour la gestion des inscriptions et le demarrage de session ;
- espace administrateur pour la moderation, les roles et les statistiques ;
- support de plusieurs images par evenement ;
- page contact publique ;
- acces a la session joueur uniquement a l'heure de debut effective si la session a ete demarree.

---

## 8. Securite

- hashage des mots de passe avec `password_hash()` ;
- requetes preparees PDO contre les injections SQL ;
- tokens CSRF sur les actions sensibles ;
- echappement HTML avec `htmlspecialchars()` ;
- controle de role sur les espaces proteges ;
- stockage des secrets MongoDB hors depot Git.

---

## 9. Documentation disponible

Le dossier `docs/` contient :

- `charte_graphique.pdf`
- `manuel_utilisation.pdf`
- `documentation_technique.md` et `documentation_technique.pdf`
- `gestion_projet.md` et `gestion_projet.pdf`
- `diagramme_mcd.png`
- `diagramme_utilisation.png`
- `diagramme_sequence.png`
- `maquettes/`

## 10. Auteur

Projet realise dans le cadre d'une evaluation DWWM.
