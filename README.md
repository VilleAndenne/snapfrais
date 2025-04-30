
# SnapFrais

Ce projet est une application web de gestion des notes de frais, développée dans le cadre d’un travail de fin d’études à la Ville d’ANDENNE. Il vise à moderniser un processus administratif jusque-là géré de manière entièrement manuelle, en proposant une solution numérique intuitive, sécurisée et centralisée.

Conçue pour les agents, les responsables hiérarchiques et le Service des Ressources humaines (SRH), cette application permet :

- La création, soumission et validation des notes de frais via un formulaire numérique dynamique.

- L’automatisation du calcul des distances pour les frais kilométriques à l’aide de l’API Google Maps.

- La gestion des justificatifs par téléversement de pièces annexes directement dans le formulaire.

- La validation hiérarchique en ligne, avec notifications intégrées.

- L’export des notes en PDF ou Excel, et leur archivage numérique sécurisé.

Techniquement, l'application repose sur Laravel pour le backend et Vue.js avec Tailwind CSS pour le frontend, garantissant une interface fluide et responsive. Le projet suit une architecture MVC claire, avec une gestion des rôles utilisateur et des workflows de validation adaptés aux besoins réels du terrain.

En plus de répondre aux attentes spécifiques de la Ville d’ANDENNE, ce système a été conçu pour être réutilisable et transférable dans d’autres administrations publiques, avec un haut degré de configuration possible (règles de remboursement, taux variables, types de frais, etc.).

Ce projet représente l’aboutissement de trois années de formation, combinant compétences techniques, utilité publique et engagement personnel dans une démarche de transformation numérique locale.

## 👨💻 Fonctionnalités principales

L’application a été conçue pour répondre aux besoins concrets de la Ville d’ANDENNE en matière de gestion des notes de frais. Elle offre une série de fonctionnalités avancées, tout en garantissant simplicité d’usage et sécurité des données :

- 🔐 **Authentification sécurisée** avec gestion des rôles (Agent, Responsable, Service RH)
- 🧾 **Création de notes de frais** selon plusieurs types :
  - **Kilométrique** : calcul automatique via Google Maps
  - **Forfaitaire** : remboursement à montant fixe
  - **En pourcentage** : remboursement basé sur un ticket (ex. transports)
- 🗺️ **Calcul des trajets** par intégration à l’API Google Maps, avec gestion des étapes intermédiaires
- 📎 **Téléversement des justificatifs** (tickets, attestations, reçus)
- ✅ **Workflow de validation** intégré :
  - Soumission par l’agent
  - Validation/refus/commentaire par le responsable
  - Transmission automatique au SRH
- 🔔 **Notifications automatiques** par rôle à chaque changement d’état
- 📊 **Tableau de bord personnalisé** selon le rôle : suivi des demandes, filtres par statut/période/type de frais
- 📤 **Export** des notes validées en **PDF ou Excel**
- 🗃️ **Archivage numérique sécurisé** des demandes traitées
- 🧩 **Gestion dynamique des taux de remboursement** selon la date du déplacement
- ✍️ **Modification manuelle du kilométrage** avec justification possible en cas d’erreur (travaux, itinéraire particulier)

---

## ⚙️ Technologies utilisées

Le projet repose sur une architecture web moderne et modulaire, utilisant des outils robustes et performants :

### 🛠️ Backend
- [Laravel](https://laravel.com/) – Framework PHP MVC pour une base solide, sécurisée et évolutive
- **Laravel Cloud** – Hébergement, déploiement automatisé via GitHub Actions, monitoring intégré
- **Eloquent ORM** – Gestion fluide des modèles relationnels et migrations

### 💻 Frontend
- [Vue.js](https://vuejs.org/) – Framework JavaScript progressif pour une interface utilisateur dynamique
- [Tailwind CSS](https://tailwindcss.com/) – Framework CSS utilitaire pour un design responsive et épuré

### 🌐 APIs externes
- [Google Maps API](https://developers.google.com/maps) – Pour le calcul des distances kilométriques

### 🔒 Sécurité et accessibilité
- Connexions HTTPS via SSL
- Variables sensibles dans `.env` (ex. `VITE_API_GOOGLE`)
- Protection des routes avec `policies` Laravel
- Gestion des fichiers via `FormData` (upload fiable, multipart)

### 📁 Formats d'export
- Génération de fichiers PDF (justificatifs, récapitulatifs)
- Génération de fichiers Excel pour l’analyse RH

## 🛠️ Configuration

Avant d’utiliser l’application, certaines variables doivent être configurées dans le fichier `.env`.

### Variables essentielles :

```env
APP_NAME=GestionNotesFrais
APP_URL=http://localhost
GOOGLE_MAPS_API_KEY=your_api_key_here
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_notes
DB_USERNAME=root
DB_PASSWORD=
```

🔐**Important :** la clé `GOOGLE_MAPS_API_KEY` doit être obtenue via la console développeur Google et correctement configurée dans la console pour autoriser les requêtes de distance. Attention à la facturation associée.

# 🧪 Tests
L'application dispose de tests automatisés pour garantir la fiabilité du code.

## 💻 Lancer les tests

```
php artisan test
```

| Pour les tests fonctionnels spécifiques aux interactions utilisateur, des scénarios de validation sont également disponibles via des jeux de données (factories, seeders) et des tests HTTP Laravel.

# 💡 Évolutions prévues

Le projet est conçu pour évoluer en fonction des besoins de la Ville d’ANDENNE et potentiellement d’autres administrations. Voici les pistes envisagées :

- ✍️ Intégration d’une signature électronique (eID / Itsme) pour valider les notes de manière juridiquement opposable

- 🔗 Connexion avec le logiciel de paie (ex : PERSEE) pour automatiser le transfert des données

- 📱 Version mobile (PWA ou native) permettant :

La création de notes en mobilité

La prise de photos des justificatifs

- 🧾 Extension à d'autres flux RH :

    - Demandes de congés

    - Remboursements télétravail

    - Demandes de matériel
