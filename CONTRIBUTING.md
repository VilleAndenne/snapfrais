# Contribuer au projet

Merci de votre intérêt pour ce projet ! Ce guide explique comment vous pouvez contribuer de manière constructive.

## 🧩 Pré-requis

Avant de commencer, assurez-vous d’avoir :

- PHP ≥ 8.1
- Composer
- Node.js ≥ 18.x
- npm ou yarn
- Une base de données locale (MySQL ou PostgreSQL)
- Clé API Google Maps si vous utilisez le module de distance

---

## 🚀 Installation locale

```bash
git clone https://github.com/votre-utilisateur/votre-repo.git
cd votre-repo

# Installation des dépendances Laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Installation des dépendances front-end
npm install
npm run dev
```

## 🧠 Bonnes pratiques de développement
- Utilisez des noms de commits clairs et explicites (ex. : fix: calcul du taux selon la date)

- Essayez de regrouper vos commits logiquement

- Testez vos modifications avant d’ouvrir une PR

- Commentez votre code si cela améliore la lisibilité

## 📦 Structure du projet
Backend : Laravel (routes API dans routes/api.php)

Frontend : Vue.js 3 (pages dans resources/js/Pages)

UI : Tailwind CSS

## 📥 Proposer une modification (pull request)

Créez une branche :

```bash
git checkout -b feat/ajout-module-pdf
```
Faites vos modifications

Poussez la branche :

```bash
git push origin feat/ajout-module-pdf
```
Ouvrez une Pull Request avec :
- Un titre clair
- Une description des changements
- Une indication de tout test ou vérification manuelle effectué

Pour toute suggestion, vous pouvez ouvrir une issue.
