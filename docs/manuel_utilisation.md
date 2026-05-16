# Manuel d'utilisation - Esportify

Version : 2.0  
Date : Mai 2026

## 1. Presentation de l'application

Esportify est une plateforme web dediee aux tournois et competitions e-sport. La plateforme permet de consulter les evenements publics, de proposer de nouveaux tournois, de gerer les inscriptions et de suivre les scores.

Quatre profils principaux coexistent :

- **Visiteur** : consultation de l'accueil, du catalogue public et de la page contact ;
- **Joueur** : inscriptions aux evenements, gestion des favoris et consultation des scores ;
- **Organisateur** : gestion des inscriptions, supervision des evenements proposes et demarrage des sessions ;
- **Administrateur** : moderation des evenements, gestion des roles et pilotage global de la plateforme.

## 2. Acces a la plateforme

L'acces se fait via l'URL locale ou deployee du projet. Le menu principal donne acces :

- a la page d'accueil ;
- au catalogue des evenements ;
- a la connexion ;
- a la page contact.

### Comptes de test

| Role | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@esportify.fr | Password123! |
| Organisateur | org1@esportify.fr | Password123! |
| Organisateur | org2@esportify.fr | Password123! |
| Joueur | joueur1@esportify.fr | Password123! |
| Joueur | joueur2@esportify.fr | Password123! |

## 3. Parcours visiteur

Un visiteur non connecte peut :

1. consulter la page d'accueil et le diaporama ;
2. parcourir les evenements visibles ;
3. ouvrir le detail d'un evenement ;
4. utiliser la page contact.

L'inscription a un evenement n'est pas disponible sans connexion.

## 4. Parcours joueur

Le parcours joueur suit en general les etapes suivantes :

1. creation d'un compte via la page d'inscription ;
2. connexion avec email et mot de passe ;
3. consultation du catalogue d'evenements ;
4. inscription a un evenement visible ;
5. ajout d'un evenement aux favoris si besoin ;
6. consultation de l'espace personnel.

L'espace joueur affiche notamment :

- les inscriptions ;
- les favoris ;
- l'historique des evenements proposes ;
- les scores obtenus ;
- le formulaire de creation d'evenement.

Le bouton **Rejoindre** apparait uniquement si :

- l'inscription a ete acceptee ;
- l'organisateur a demarre la session ;
- l'heure de debut a ete atteinte.

## 5. Parcours organisateur

Le role organisateur permet de :

1. gerer les inscriptions recues ;
2. accepter ou refuser les joueurs ;
3. modifier les evenements encore a venir ;
4. relancer une validation apres modification ;
5. demarrer l'evenement jusqu'a 30 minutes avant l'heure de debut.

L'evenement demarre en amont peut donc etre prepare avant le debut reel, mais l'acces joueur reste conditionne a l'heure de debut effective.

## 6. Parcours administrateur

Le role administrateur donne acces :

1. au tableau de bord ;
2. a la validation ou suspension des evenements ;
3. a la promotion d'un joueur en organisateur ;
4. a la gestion des droits et des comptes ;
5. a l'ajout de scores pour les evenements passes.

L'administrateur conserve les droits les plus etendus sur la plateforme.

## 7. Regles de fonctionnement importantes

- seuls les evenements valides sont visibles publiquement ;
- un evenement modifie repart en validation ;
- un evenement peut comporter plusieurs images ;
- un joueur refuse sur un evenement ne peut plus s'y reinscrire ;
- la page contact reste publique ;
- le chat est reserve aux joueurs acceptes pendant la fenetre effective de l'evenement.

## 8. Securite et donnees

La plateforme integre plusieurs mesures de base :

- hashage des mots de passe avec bcrypt ;
- tokens CSRF sur les actions sensibles ;
- requetes preparees contre les injections SQL ;
- echappement HTML contre les scripts injectes ;
- secrets MongoDB gardes hors depot Git.

Pour une demande de suppression de compte ou une question de donnees personnelles, le canal prevu reste la page contact.

## 9. Rappel de deploiement

Le projet repose sur PHP, MySQL et, en option, MongoDB Atlas. La mise en ligne suit en general :

1. l'envoi du code sur un hebergement compatible ;
2. l'import de `sql/esportify.sql` ;
3. la verification de `DATABASE_URL` ou de la configuration locale ;
4. la configuration de `MONGODB_URI` et `MONGODB_DB` si le chat MongoDB est utilise.
