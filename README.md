# NRV-net
SAE "Atelier-Projet de développement web 1" / 2026 La billetterie du festival Nancy Rock Vibration ~ NRV.net

Maxence DELATTRE / Enzo POIRSON / Kenan KEISER / Leo FERAL / Alexis COUTURIER
BUT 3 DWM

*Lancer le projet

**Prérequis

- Docker Desktop

**Démarrage

Depuis la racine du dépôt, placez-vous dans le dossier de l’application puis démarrez les services :

cd nrv
docker compose up --build

Une fois les conteneurs démarrés, ouvrez 'http://localhost:8080'.
Laissez la commande tourner pendant l’utilisation du site. 

Pour arrêter les services exécutez :
docker compose down

*Architecture du dépôt

README.md
diagrammes_nrv/              # Diagrammes du projet
documents_et_maquette/       # Maquette et documents de synthèse
nrv/                         # Code de l’application
    docker-compose.yml       # Orchestration du front, de l’API et de MySQL
    docker/
        api/                 # Image et configuration PHP de l’API
        db/                  # Création du schéma et données initiales
        front/               # Configuration Nginx et routage de /api
    backend/
        config/              # Configuration, initialisation et routes de l’API
        public/              # Point d’entrée HTTP de l’API
        rrc/                 # Code PHP, dont l’accès au catalogue
        composer.json        # Dépendances PHP
    frontend/
        assets/              # Images et ressources graphiques
        css/                 # Feuilles de style
        js/                  # Scripts de l’interface et appels à l’API
        *.html               # Pages du site (accueil, panier et profils)
