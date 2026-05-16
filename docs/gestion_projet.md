# Documentation de Gestion de Projet - Esportify

## Methodologie

Le projet a ete mene selon une logique Agile / Scrum adaptee a un travail individuel. La demarche retenue consistait a avancer par blocs fonctionnels courts, avec une verification reguliere des parcours principaux et des livrables.

## Planification

### Sprint 0 - Analyse et conception

- lecture du cahier des charges ;
- extraction des besoins fonctionnels ;
- creation du MCD et des diagrammes UML ;
- definition de la charte graphique et des wireframes.

### Sprint 1 - Mise en place technique

- structure du projet ;
- configuration de la base de donnees et du routeur ;
- sessions et securite de base.

### Sprint 2 - Authentification et roles

- inscription et connexion ;
- gestion des roles joueur / organisateur / administrateur ;
- premiers controles d'acces.

### Sprint 3 - Gestion des evenements

- CRUD evenement ;
- moderation administrateur ;
- filtres asynchrones et detail evenement.

### Sprint 4 - Espace joueur

- inscriptions ;
- favoris ;
- historique et scores ;
- proposition et modification d'evenements a venir.

### Sprint 5 - Espace organisateur

- gestion des inscriptions ;
- lancement de session ;
- modification et revalidation des evenements.

### Sprint 6 - Espace administrateur

- tableau de bord ;
- moderation des evenements ;
- gestion des droits et des scores.

### Sprint 7 - Front-end et responsive

- integration Bootstrap ;
- stabilisation UX ;
- adaptation mobile / tablette / desktop.

### Sprint 8 - Documentation et deploiement

- README ;
- documentation technique ;
- charte graphique ;
- manuel d'utilisation ;
- verification des livrables.

## Gestion des versions

```text
main        : branche stable
develop     : integration
feature/*   : une branche par fonctionnalite
```

Le flux de travail retenu est le suivant :

1. creation d'une branche `feature/nom` depuis `develop` ;
2. developpement et tests locaux ;
3. merge vers `develop` apres validation ;
4. merge vers `main` en fin de cycle.

## Livrables

| Livrable | Statut | Emplacement |
|----------|--------|-------------|
| Code source | OK | Depot GitHub |
| SQL | OK | `/sql/esportify.sql` |
| README | OK | `/README.md` |
| Charte graphique PDF | OK | `/docs/charte_graphique.pdf` |
| Manuel d'utilisation PDF | OK | `/docs/manuel_utilisation.pdf` |
| Wireframes et mockups | OK | `/docs/maquettes/` |
| Diagrammes MCD / UML | OK | `/docs/diagramme_mcd.png`, `/docs/diagramme_utilisation.png`, `/docs/diagramme_sequence.png` |
| Documentation technique | OK | `/docs/documentation_technique.md` et `/docs/documentation_technique.pdf` |
| Documentation gestion projet | OK | `/docs/gestion_projet.md` et `/docs/gestion_projet.pdf` |
| Application deployee | A completer | URL publique a renseigner |

## Bilan

### Points forts

- architecture simple a deployer ;
- separation claire des roles ;
- base de securite propre ;
- livrables visuels complets ;
- documentation de remise structuree.

### Axes d'amelioration

- notifications mail reelles ;
- tests automatises ;
- extension du temps reel autour du chat.
