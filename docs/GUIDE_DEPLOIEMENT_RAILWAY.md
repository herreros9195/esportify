# Guide de deploiement - Railway

Ce document presente une demarche de deploiement d'Esportify sur **Railway**.

> Railway convient bien au projet car la plateforme prend facilement en charge un depot GitHub, une base MySQL et des variables d'environnement.

---

## 1. Prerequis

Le deploiement suppose en general :

- un compte Railway ;
- un compte GitHub ;
- un depot GitHub public contenant le projet ;
- le script SQL `sql/esportify.sql`.

---

## 2. Preparation du depot GitHub

Une mise en place type suit les commandes suivantes :

```bash
git remote add origin https://github.com/herreros9195/esportify.git
git branch -M main
git push -u origin main
git push -u origin develop
```

Le depot public simplifie l'integration Railway dans le cadre d'une remise de projet.

---

## 3. Creation du projet Railway

La procedure type suit les etapes suivantes :

1. ouvrir le dashboard Railway ;
2. creer un nouveau projet ;
3. choisir le deploiement depuis un depot GitHub ;
4. selectionner le depot `esportify`.

Railway detecte ensuite automatiquement le projet PHP grace a `composer.json`.

---

## 4. Ajout de la base MySQL

La base relationnelle s'ajoute en general directement dans Railway :

1. ajouter un service **MySQL** ;
2. attendre la creation de la base ;
3. verifier la variable `DATABASE_URL` generee automatiquement.

---

## 5. Variables d'environnement

Les variables utiles au projet sont les suivantes :

```text
DATABASE_URL = mysql://...
MONGODB_URI = mongodb+srv://nom_utilisateur:mot_de_passe@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority
MONGODB_DB = esportify_nosql
PHP_TIMEZONE = Europe/Paris
```

`MONGODB_URI` et `MONGODB_DB` restent optionnelles si le chat MongoDB n'est pas active.

---

## 6. Import SQL

Le chargement de la base suit ensuite l'une des deux approches classiques :

### Methode 1 - Client graphique

1. recuperer les informations de connexion MySQL dans Railway ;
2. ouvrir un client type MySQL Workbench ou DBeaver ;
3. se connecter a la base ;
4. executer `sql/esportify.sql`.

### Methode 2 - Ligne de commande

```bash
mysql -u USER -p -h HOST -P PORT DBNAME < sql/esportify.sql
```

---

## 7. Configuration MongoDB Atlas

Si le chat MongoDB est active, une configuration Atlas classique comprend :

1. l'autorisation de l'acces reseau depuis l'environnement Railway ;
2. la verification de `MONGODB_URI` ;
3. la verification de `MONGODB_DB`.

Une configuration ouverte de test peut autoriser `0.0.0.0/0`, avec durcissement ulterieur si necessaire.

---

## 8. Deploiement de l'application

Le deploiement suit en general cette sequence :

1. verifier le service web principal ;
2. verifier le dossier racine ;
3. lancer le deploiement si Railway ne l'a pas fait automatiquement ;
4. generer un domaine public.

L'URL obtenue sert ensuite de lien de deploiement dans la copie a rendre.

---

## 9. Verifications apres deploiement

Les controles utiles portent en general sur :

- l'affichage de la page d'accueil ;
- la connexion avec les comptes de test ;
- l'affichage des evenements ;
- les filtres asynchrones ;
- la creation d'evenement ;
- les droits selon le role ;
- le chat si MongoDB est active.

---

## 10. Diagnostic rapide

En cas d'erreur, la verification suit en priorite :

- l'import SQL ;
- la presence de `DATABASE_URL` ;
- la presence de `MONGODB_URI` / `MONGODB_DB` si necessaire ;
- les logs Railway.

---

## 11. Mise a jour du projet

Une mise a jour classique suit ensuite :

```bash
git add .
git commit -m "Mise a jour"
git push origin main
```

Railway redeploie ensuite automatiquement la branche suivie.
