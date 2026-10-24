# Lancer les tests PHPUnit

Depuis la racine du projet, construis puis lance le conteneur dédié aux tests :

```bash
docker build -f docker/Dockerfile.tests -t nrv-tests .
docker run --rm nrv-tests
```

Le conteneur de test installe PHPUnit et les extensions PHP nécessaires sans modifier l'image Docker de production. Les tests sont dans `backend/tests/Unit/`.

Pour lancer les tests en local, depuis `backend/` (avec PHP 8.3 et les extensions nécessaires installées) :

```bash
composer install
composer test
```
