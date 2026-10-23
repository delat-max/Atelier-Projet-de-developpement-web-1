# Tableau de bord du projet NRV.net

SAE "Atelier-Projet de développement web 1" / 2026, BUT 3 Informatique DWM, IUT Nancy-Charlemagne

## L'équipe

| Membre | Compte GitHub |
|---|---|
| Kenan Keiser | LeVraiKenan |
| Enzo Poirson | PoirsonEnzo |
| Léo Feral | bunnaii |
| *(à compléter)* | delat-max |
| Alexis Couturier | Alexis Couturier |

## Fonctionnalités de base

| N° | Fonctionnalité | État | Réalisé par |
|---|---|---|---|
| 1 | Liste des spectacles (titre, date, horaire, image) | Fait | Kenan |
| 2 | Détail d'une soirée et de ses spectacles | Fait | Kenan |
| 3 | Clic sur un spectacle vers le détail de sa soirée | Fait | delat-max |
| 4 | Filtrage des spectacles par date | Fait | Enzo |
| 5 | Filtrage des spectacles par style de musique | Fait | Enzo |
| 6 | Ajout de billets dans le panier depuis une soirée | Fait | delat-max (front), Enzo (API et base), Kenan (intégration) |
| 7 | Affichage du panier et calcul du total | Fait | delat-max (front), Enzo (API et base), Kenan (intégration) |
| 8 | Validation du panier et transformation en commande | À faire | |
| 9 | Paiement simulé par carte, contrôle et mise à jour des places | À faire | |
| 10 | Création des billets imprimables | À faire | |
| 11 | Backoffice : jauge des places vendues et recette par soirée | À faire | Léo (prévu) |

## Fonctionnalités étendues

| N° | Fonctionnalité | État | Réalisé par |
|---|---|---|---|
| 1 | Pagination de la liste des spectacles | Fait | Léo, intégrée par Kenan |
| 2 | Modification du nombre de billets dans le panier | Fait | delat-max |
| 3 | Backoffice : ajout de spectacles et de soirées | À faire | Léo (prévu) |
| 4 | Vérification des places disponibles à l'ajout au panier | Fait | Enzo |

## Travaux transverses

| Sujet | Réalisé par |
|---|---|
| Squelette du projet, base de données, jeu de données, Docker | Kenan |
| Refonte du back en architecture hexagonale | Kenan |
| Comptes utilisateurs : inscription, connexion, profil, mot de passe | Kenan |
| Maquette Figma et intégration du CSS | Kenan |
| Diagrammes de conception (cas d'utilisation, séquences, activité, états, architecture) | Alexis |
| Schéma de la base de données et scénario d'usage | Alexis |
| Tests unitaires PHPUnit (95 tests sur les cas d'utilisation et les contrôleurs) | Alexis |
| README d'installation | *(à compléter)* |

## Contribution de chaque membre

**Kenan Keiser** : mise en place du projet (structure, Docker, base et données de test), fonctionnalités 1 et 2, passage du back en architecture hexagonale, gestion des comptes utilisateurs, intégration de la maquette, de la pagination et du panier dans la version principale.

**Enzo Poirson** : filtres par date et par style (fonc. 4 et 5), partie serveur du panier (API, enregistrement en base, calcul des prix selon le tarif) avec vérification des places disponibles.

**Léo Feral** : pagination de la liste des spectacles. Prend en charge le backoffice (fonc. 11 et étendue 3).

**delat-max** : lien entre un spectacle et sa soirée (fonc. 3), interface du panier (fonc. 6 et 7) et modification du nombre de billets.

**Alexis Couturier** : diagrammes de conception, schéma de la base de données, scénario d'usage, tests unitaires du back.

## Reste à faire

- Validation du panier en commande, paiement simulé et création des billets (fonc. 8, 9, 10)
- Backoffice (fonc. 11 et étendue 3)
- Page artiste avec image et lien vers les vidéos
- README d'installation, identifiants de test, URL de test de l'application
