# Système de Logging d'Activité - SnapFrais

## Vue d'ensemble

Un système de logging d'activité a été implémenté sur les modèles sensibles de l'application SnapFrais en utilisant le package `spatie/laravel-activitylog`. Ce système permet de tracer toutes les modifications importantes effectuées sur les données critiques.

## Modèles avec logging activé

### 1. User (app/Models/User.php)
**Champs loggés :**
- `name` - Nom de l'utilisateur
- `email` - Email de l'utilisateur
- `is_admin` - Statut administrateur
- `super_admin` - Statut super administrateur
- `notify_expense_sheet_to_approval` - Préférence de notification
- `notify_receipt_expense_sheet` - Préférence de notification
- `notify_remind_approval` - Préférence de notification
- `deleted_at` - Suppression soft delete

**Log name :** `user`

**Descriptions :**
- Création : "Utilisateur créé"
- Modification : "Utilisateur modifié"
- Suppression : "Utilisateur supprimé"

### 2. ExpenseSheet (app/Models/ExpenseSheet.php)
**Champs loggés :**
- `user_id` - Utilisateur propriétaire
- `distance` - Distance parcourue
- `route` - Itinéraire
- `total` - Montant total
- `status` - Statut de la feuille de frais
- `form_id` - Formulaire associé
- `department_id` - Département
- `validated_by` - Validateur
- `validated_at` - Date de validation
- `approved` - Approbation
- `refusal_reason` - Raison du refus
- `created_by` - Créateur
- `is_draft` - Brouillon
- `deleted_at` - Suppression soft delete

**Log name :** `expense_sheet`

**Descriptions :**
- Création : "Feuille de frais créée"
- Modification : "Feuille de frais modifiée"
- Suppression : "Feuille de frais supprimée"

### 3. ExpenseSheetCost (app/Models/ExpenseSheetCost.php)
**Champs loggés :**
- `expense_sheet_id` - Feuille de frais associée
- `form_cost_id` - Type de coût
- `type` - Type de remboursement (km, fixed, percentage)
- `distance` - Distance
- `google_distance` - Distance Google Maps
- `route` - Itinéraire
- `requirements` - Justificatifs
- `total` - Montant total
- `amount` - Montant de la dépense
- `date` - Date du coût

**Log name :** `expense_sheet_cost`

**Descriptions :**
- Création : "Coût de feuille de frais créé"
- Modification : "Coût de feuille de frais modifié"
- Suppression : "Coût de feuille de frais supprimé"

### 4. Form (app/Models/Form.php)
**Champs loggés :**
- `name` - Nom du formulaire
- `description` - Description
- `organization_id` - Organisation
- `deleted_at` - Suppression soft delete

**Log name :** `form`

**Descriptions :**
- Création : "Formulaire créé"
- Modification : "Formulaire modifié"
- Suppression : "Formulaire supprimé"

### 5. Department (app/Models/Department.php)
**Champs loggés :**
- `name` - Nom du département
- `parent_id` - Département parent
- `deleted_at` - Suppression soft delete

**Log name :** `department`

**Descriptions :**
- Création : "Département créé"
- Modification : "Département modifié"
- Suppression : "Département supprimé"

## Événements d'authentification loggés

En plus des modifications sur les modèles, tous les événements d'authentification sont automatiquement loggés pour des raisons de sécurité et d'audit.

### Connexion réussie (Login)
- **Event :** `login`
- **Log name :** `authentication`
- **Description :** "Connexion réussie"
- **Causer :** L'utilisateur connecté
- **Propriétés loggées :**
  - `ip_address` - Adresse IP de connexion
  - `user_agent` - User agent du navigateur
  - `guard` - Guard utilisé (web, api, etc.)

### Tentative de connexion échouée (Failed Login)
- **Event :** `failed_login`
- **Log name :** `authentication`
- **Description :** "Tentative de connexion échouée"
- **Causer :** Anonyme
- **Propriétés loggées :**
  - `email` - Email utilisé pour la tentative
  - `ip_address` - Adresse IP de la tentative
  - `user_agent` - User agent du navigateur
  - `guard` - Guard utilisé

### Déconnexion (Logout)
- **Event :** `logout`
- **Log name :** `authentication`
- **Description :** "Déconnexion"
- **Causer :** L'utilisateur déconnecté
- **Propriétés loggées :**
  - `ip_address` - Adresse IP de déconnexion
  - `user_agent` - User agent du navigateur
  - `guard` - Guard utilisé

### Réinitialisation de mot de passe (Password Reset)
- **Event :** `password_reset`
- **Log name :** `authentication`
- **Description :** "Mot de passe réinitialisé"
- **Causer :** L'utilisateur concerné
- **Propriétés loggées :**
  - `ip_address` - Adresse IP
  - `user_agent` - User agent du navigateur

**⚠️ Important pour la sécurité :**
Les logs d'authentification sont essentiels pour :
- Détecter les tentatives d'intrusion
- Tracer les accès non autorisés
- Auditer les connexions suspectes
- Identifier les patterns d'attaques (brute force, etc.)

## Fonctionnalités du logging

### 1. Logging automatique
Toutes les opérations CRUD (Create, Read, Update, Delete) sur les modèles configurés sont automatiquement loggées.

### 2. Logging uniquement des changements (Dirty)
Grâce à `logOnlyDirty()`, seuls les attributs réellement modifiés sont enregistrés dans les logs. Si aucun changement n'est détecté, aucun log n'est créé.

### 3. Causer automatique
L'utilisateur authentifié est automatiquement associé comme "causer" (auteur) de chaque activité. Cela permet de savoir qui a effectué quelle modification.

### 4. Propriétés old & attributes
Pour chaque modification, le système enregistre :
- `old` : Les anciennes valeurs des champs
- `attributes` : Les nouvelles valeurs des champs

Exemple d'activité JSON :
```json
{
  "old": {
    "status": "En attente",
    "validated_by": null
  },
  "attributes": {
    "status": "Approuvé",
    "validated_by": 5
  }
}
```

## Accès aux logs

### Via le modèle Activity

```php
use Spatie\Activitylog\Models\Activity;

// Récupérer toutes les activités
$activities = Activity::all();

// Récupérer les activités d'un utilisateur spécifique
$userActivities = Activity::where('subject_type', User::class)
    ->where('subject_id', $userId)
    ->get();

// Récupérer les activités par log name
$expenseSheetLogs = Activity::where('log_name', 'expense_sheet')->get();

// Récupérer les activités avec leur causer (auteur)
$activities = Activity::with('causer')->get();

foreach ($activities as $activity) {
    echo $activity->description;
    echo $activity->causer->name; // Nom de l'utilisateur qui a effectué l'action
}
```

### Via le helper activity()

```php
// Logger une activité manuelle
activity()
    ->causedBy($user)
    ->performedOn($expenseSheet)
    ->withProperties(['key' => 'value'])
    ->log('Description personnalisée');
```

## Tests

Un test complet a été créé dans `tests/Feature/ActivityLogTest.php` qui vérifie :

1. ✅ La création d'utilisateur est loggée
2. ✅ La modification d'utilisateur est loggée
3. ✅ La suppression d'utilisateur est loggée
4. ✅ La création de feuille de frais est loggée
5. ✅ La modification de feuille de frais est loggée
6. ✅ La création de coût de feuille de frais est loggée
7. ✅ La création de formulaire est loggée
8. ✅ La création de département est loggée
9. ✅ Seuls les attributs modifiés sont loggés (dirty)
10. ✅ L'utilisateur authentifié est enregistré comme auteur

Pour exécuter les tests :
```bash
php artisan test --filter=ActivityLogTest
```

## Configuration

La configuration du package se trouve dans `config/activitylog.php`.

### Paramètres importants :

- `enabled` : Active/désactive le logging (par défaut: `true`)
- `delete_records_older_than_days` : Durée de conservation des logs (par défaut: 365 jours)
- `default_log_name` : Nom de log par défaut
- `table_name` : Nom de la table des logs (par défaut: `activity_log`)

### Variables d'environnement :

```env
ACTIVITY_LOGGER_ENABLED=true
ACTIVITY_LOGGER_TABLE_NAME=activity_log
```

## Base de données

### Table activity_log

Les logs sont stockés dans la table `activity_log` avec les colonnes suivantes :

- `id` : Identifiant unique
- `log_name` : Nom du log (user, expense_sheet, etc.)
- `description` : Description de l'action
- `subject_type` : Type du modèle concerné (App\Models\User, etc.)
- `subject_id` : ID du modèle concerné
- `causer_type` : Type de l'auteur de l'action
- `causer_id` : ID de l'auteur de l'action
- `properties` : JSON contenant les anciennes et nouvelles valeurs
- `event` : Type d'événement (created, updated, deleted)
- `batch_uuid` : UUID pour regrouper plusieurs actions
- `created_at` : Date de création du log

## Maintenance

### Nettoyage des anciens logs

Pour nettoyer les logs de plus de X jours :

```bash
php artisan activitylog:clean
```

Cette commande supprime tous les logs plus anciens que le nombre de jours défini dans `config/activitylog.php`.

## Sécurité et confidentialité

⚠️ **Important :**
- Les logs contiennent des informations sensibles (emails, montants, etc.)
- L'accès à la table `activity_log` doit être strictement contrôlé
- Les mots de passe ne sont PAS loggés (exclus via `$hidden` dans le modèle User)
- Considérer une politique de rétention des logs conforme au RGPD

## Prochaines étapes possibles

1. **Interface d'administration** : Créer une interface pour consulter les logs
2. **Notifications** : Alerter les admins sur certaines actions critiques
3. **Exports** : Permettre l'export des logs pour audit
4. **Filtres avancés** : Recherche par date, utilisateur, type d'action
5. **Intégration avec Telescope** : Visualisation des logs dans Telescope

## Ressources

- [Documentation Spatie Laravel Activity Log](https://spatie.be/docs/laravel-activitylog)
- [Package sur GitHub](https://github.com/spatie/laravel-activitylog)
