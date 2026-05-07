# Documentation de Gestion de Projet - Esportify

## Méthodologie

Ce projet a été réalisé selon une approche inspirée de la méthode **Agile / Scrum**, adaptée à un développement individuel en formation.

## Planification

### Sprint 0 : Analyse et conception (1-2 jours)
- Lecture du cahier des charges et extraction des besoins fonctionnels
- Définition de l'architecture technique (stack PHP/MySQL/MongoDB)
- Création du MCD et des diagrammes UML
- Choix de la charte graphique et création des wireframes

### Sprint 1 : Mise en place technique (1 jour)
- Création de la structure de dossiers
- Configuration de la base de données relationnelle (MySQL)
- Mise en place du routeur PHP et des templates
- Configuration des sessions et sécurité de base (CSRF, bcrypt)

### Sprint 2 : Authentification et rôles (2 jours)
- Développement de l'inscription et de la connexion
- Gestion des rôles (Joueur, Organisateur, Administrateur)
- Sécurisation des routes et contrôle d'accès
- Création des données de test (fixtures SQL)

### Sprint 3 : Gestion des événements (3-4 jours)
- CRUD des événements (création, modification, suppression logique)
- Système de validation par l'administrateur
- Filtrage asynchrone des événements (AJAX + API PHP)
- Modal de détails et page événement complète

### Sprint 4 : Inscriptions et espace joueur (2-3 jours)
- Inscription/désinscription aux événements
- Gestion des favoris
- Historique des événements et des scores
- Bouton "Rejoindre" avec conditions temporelles

### Sprint 5 : Espace organisateur (2 jours)
- Interface de gestion des inscriptions (accepter/refuser)
- Bouton de démarrage de l'événement (30 min avant)
- Modification d'événement avec re-soumission à validation

### Sprint 6 : Espace administrateur (2 jours)
- Tableau de bord statistique
- Modération des événements (valider, rejeter, suspendre)
- Gestion des droits utilisateurs (promotion/rétrogradation)
- Ajout manuel de scores

### Sprint 7 : Front-end et responsive (2 jours)
- Intégration Bootstrap 5
- Diaporama page d'accueil
- Affichage responsive mobile/tablette/desktop
- Polishing UX (alertes, loaders, états vides)

### Sprint 8 : Documentation et déploiement (2 jours)
- Rédaction du README et documentation technique
- Création de la charte graphique et du manuel d'utilisation
- Préparation du dépôt Git avec branches main/develop
- Tests finaux et déploiement

## Gestion des versions (Git)

```
main        : branche de production, stable
develop     : branche d'intégration, tests en cours
feature/*   : une branche par fonctionnalité (ex: feature/auth, feature/events-filter)
```

Flux de travail :
1. Création d'une branche `feature/nom` depuis `develop`
2. Développement et commits réguliers
3. Merge de `feature/nom` vers `develop` après tests locaux
4. Merge de `develop` vers `main` une fois le sprint validé

## Livrables

| Livrable | Statut | Emplacement |
|----------|--------|-------------|
| Code source | ✅ | Dépôt GitHub |
| Fichiers SQL | ✅ | `/sql/esportify.sql` |
| README.md | ✅ | `/README.md` |
| Charte graphique | ✅ | `/docs/charte_graphique.html` |
| Manuel d'utilisation | ✅ | `/docs/manuel_utilisation.html` |
| Documentation technique | ✅ | `/docs/documentation_technique.md` |
| Documentation gestion projet | ✅ | `/docs/gestion_projet.md` |
| Application déployée | ⏳ | À configurer par l'utilisateur |

## Bilan et axes d'amélioration

**Points forts :**
- Architecture claire et maintenable
- Sécurité renforcée (CSRF, PDO, XSS)
- Responsive design
- Séparation des rôles bien définie

**Améliorations futures :**
- Intégration complète de MongoDB pour le chat temps réel
- API REST complète pour une future application mobile
- Système de notifications email
- Upload d'images pour les événements
- Tests unitaires (PHPUnit)
