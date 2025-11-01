Un projet __Symfony__ 7.2 prêt pour le développement local, idéal pour démarrer un site e-commerce ou tester des fonctionnalités.

---

## 📋 Prérequis

| Outil         | Version/Info                                                                 |
|---------------|-----------------------------------------------------------------------------|
| PHP           | ≥ 8.2                                                                       |
| Composer      | Dernière version                                                            |
| Base de données | MySQL/MariaDB ou PostgreSQL                                                 |
| Node          | Optionnel (pour les outils frontend, mais pas requis pour importmap)       |
| Docker        | Optionnel (pour lancer les services via `docker compose`)                  |

---

## 🚀 Installation rapide (local)

### 1. Installer les dépendances PHP
```bash
composer install
```

### 2. Configurer l’environnement
- Copier `.env` vers `.env.local` et adapter les valeurs (ne pas commiter les secrets).
- Exemple de configuration de base de données dans `.env` (`DATABASE_URL`).

### 3. Lancer le serveur web (au choix)
- **Symfony CLI** (recommandé) :
  ```bash
  symfony server\:start --dir=public
  ```
- **Serveur PHP intégré** :
  ```bash
  php -S 127.0.0.1:8000 -t public
  ```
- **Apache/XAMPP** : pointer la racine du document vers `public/`.

---

## 🐳 Installation rapide (Docker)

Utilise `compose.yaml` et `compose.override.yaml` :
```bash
docker compose -f compose.yaml -f compose.override.yaml up -d
```
- Vérifier les variables d’environnement (`POSTGRES_*`, etc.) avant de démarrer.

---

## 🗃 Base de données & Migrations

| Commande                                      | Description                                  |
|-----------------------------------------------|----------------------------------------------|
| `php bin/console doctrine:database:create`    | Créer la base de données                     |
| `php bin/console doctrine:migrations:migrate`| Appliquer les migrations                      |
| `php bin/console doctrine:migrations:rollback`| Revenir à la migration précédente            |

---

## 🎨 Assets

- Les assets sources sont dans `assets/`, les assets compilés dans `public/assets/`.
- Les scripts Composer (`assets:install`, `importmap:install`) s’exécutent automatiquement à l’installation.
- **Important** : le serveur web doit servir `public/` comme racine pour éviter les 404 sur `/assets/...`.

---

## 🧪 Tests

Lancer la suite de tests :
```bash
./bin/phpunit
```

---

## 🔧 Commandes utiles

| Commande                                      | Description                                  |
|-----------------------------------------------|----------------------------------------------|
| `php bin/console cache:clear`                 | Vider le cache                               |
| `symfony server:start --dir=public`          | Lancer le serveur de développement           |
| `php bin/console doctrine:migrations:migrate`| Appliquer les migrations                      |
| `php bin/console make:*`                      | Générer du code (entité, contrôleur, etc.)   |

---

## ⚠️ Dépannage

| Problème                          | Solution                                                                 |
|-----------------------------------|--------------------------------------------------------------------------|
| 404 sur `/assets/...`             | Vérifier que la racine du serveur est bien `public/`                    |
| Erreur de connexion à la base     | Vérifier `DATABASE_URL` dans `.env.local` ou `config/secrets/`          |
| Assets obsolètes                 | Supprimer `public/assets` et relancer `assets:install`                   |

---

## 📂 Structure du projet

| Dossier       | Contenu                                                                 |
|---------------|-------------------------------------------------------------------------|
| `config/`     | Configuration Symfony, routes, packages                                |
| `src/`        | Code source (Controller, Entity, Form, Repository, Security, Twig)     |
| `public/`     | Racine du document web (index.php, assets compilés)                    |
| `templates/`  | Templates Twig                                                          |
| `migrations/` | Migrations Doctrine                                                     |
| `tests/`      | Tests PHPUnit                                                            |

---

## 📜 Licence

Propriétaire (voir `composer.json`).

---
