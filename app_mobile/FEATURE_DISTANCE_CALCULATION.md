# Fonctionnalité : Calcul de Distance Kilométrique

## Vue d'ensemble

Cette fonctionnalité permet aux utilisateurs de saisir un trajet (départ et arrivée) lors de la création d'une note de frais avec un type de coût remboursé au kilomètre. La distance est calculée automatiquement via Google Maps Distance Matrix API et le montant du remboursement est calculé en fonction du tarif kilométrique applicable.

## Architecture

### Composants créés

1. **DistanceInput** (`app_mobile/components/expense/DistanceInput.tsx`)
   - Composant React Native avec deux champs de saisie d'adresse
   - Autocomplétion des adresses via Google Places API
   - Calcul automatique de la distance via Google Distance Matrix API
   - Affichage en temps réel de la distance calculée

2. **CostCard** (modifié - `app_mobile/components/expense/CostCard.tsx`)
   - Intégration du composant `DistanceInput` pour les coûts de type 'km'
   - Calcul automatique du montant remboursé : distance × tarif/km
   - Affichage détaillé : distance, tarif, et montant total

### Structure de données

```typescript
interface CostData {
  date: string;
  kmData?: {
    departure?: string;        // Adresse de départ
    arrival?: string;          // Adresse d'arrivée
    googleKm?: number;         // Distance calculée par Google (km)
    totalKm?: number;          // Distance totale (km)
    steps?: string[];          // Étapes intermédiaires (optionnel)
    manualKm?: number;         // Distance saisie manuellement (optionnel)
  };
  reimbursementAmount?: number; // Montant calculé du remboursement
  // ... autres types de coûts
}
```

## Flux utilisateur

1. L'utilisateur sélectionne un type de coût remboursé au kilomètre
2. Il saisit l'adresse de départ (min. 3 caractères)
3. Une liste de suggestions d'adresses apparaît
4. Il sélectionne l'adresse de départ dans la liste
5. Il répète l'opération pour l'adresse d'arrivée
6. La distance est calculée automatiquement
7. Le montant du remboursement s'affiche : `distance × tarif/km`

## Fonctionnalités techniques

### Autocomplétion d'adresses

- **API utilisée** : Google Places API (Autocomplete)
- **Endpoint** : `https://maps.googleapis.com/maps/api/place/autocomplete/json`
- **Paramètres** :
  - `input` : texte saisi par l'utilisateur
  - `language=fr` : résultats en français
  - `components=country:fr` : limité à la France
- **Debounce** : 300ms pour éviter trop de requêtes

### Calcul de distance

- **API utilisée** : Google Distance Matrix API
- **Endpoint** : `https://maps.googleapis.com/maps/api/distancematrix/json`
- **Paramètres** :
  - `origins` : adresse de départ
  - `destinations` : adresse d'arrivée
  - `language=fr` : résultats en français
  - `units=metric` : distances en kilomètres
- **Précision** : arrondi à 2 décimales

### Calcul du remboursement

```javascript
const rate = getActiveRate(date); // Tarif applicable à la date du coût
const totalAmount = distance * rate;
```

## Interface utilisateur

### Saisie de trajet

```
┌────────────────────────────────────┐
│ Adresse de départ                  │
│ ┌────────────────────────────────┐ │
│ │ 📍 Entrez l'adresse...         │ │
│ └────────────────────────────────┘ │
│   ┌──────────────────────────────┐ │
│   │ 📍 10 Rue de la Paix, Paris  │ │
│   │ 📍 10 Avenue des Champs...   │ │
│   └──────────────────────────────┘ │
└────────────────────────────────────┘

┌────────────────────────────────────┐
│ Adresse d'arrivée                  │
│ ┌────────────────────────────────┐ │
│ │ 📍 Entrez l'adresse...         │ │
│ └────────────────────────────────┘ │
└────────────────────────────────────┘
```

### Affichage du résultat

```
┌────────────────────────────────────┐
│ 🔄  Distance calculée              │
│     42.5 km                        │
└────────────────────────────────────┘

┌────────────────────────────────────┐
│ Distance:        42.5 km           │
│ Tarif:           0.50 €/km         │
│ ─────────────────────────────      │
│ Remboursement:   21.25 €           │
└────────────────────────────────────┘
```

## Configuration requise

### Variables d'environnement

Créez un fichier `.env` à la racine de `app_mobile/` :

```bash
EXPO_PUBLIC_GOOGLE_MAPS_API_KEY=votre_clé_api_ici
```

### APIs Google Cloud à activer

1. Places API (autocomplétion)
2. Distance Matrix API (calcul de distance)

Voir le fichier `GOOGLE_MAPS_SETUP.md` pour les instructions détaillées.

## Gestion des erreurs

### Clé API manquante

Si la clé API n'est pas configurée :
- Un warning est affiché dans la console : `"Google Maps API key not configured"`
- L'autocomplétion et le calcul de distance sont désactivés silencieusement
- L'utilisateur peut continuer sans ces fonctionnalités

### Erreurs réseau

- Les erreurs de requêtes sont loggées dans la console
- L'interface reste fonctionnelle
- Aucune alerte intrusive n'est affichée

### Adresses invalides

- Si une adresse n'est pas reconnue, aucune suggestion n'apparaît
- Le calcul de distance ne démarre pas tant que les deux adresses ne sont pas validées

## Améliorations futures possibles

1. **Saisie manuelle de la distance**
   - Permettre à l'utilisateur de saisir manuellement les km si Google Maps n'est pas disponible
   - Champ `manualKm` déjà prévu dans la structure de données

2. **Étapes intermédiaires**
   - Ajouter des étapes entre départ et arrivée
   - Calculer la distance totale de toutes les étapes
   - Champ `steps` déjà prévu dans la structure de données

3. **Historique des trajets**
   - Sauvegarder les trajets fréquemment utilisés
   - Proposer des raccourcis pour les trajets récurrents

4. **Carte visuelle**
   - Afficher le trajet sur une carte
   - Permettre de modifier le trajet visuellement

5. **Modes de transport**
   - Différencier voiture, vélo, transport en commun
   - Adapter les tarifs en fonction du mode

6. **Optimisation de trajet**
   - Proposer le trajet le plus court
   - Afficher les alternatives

## Fichiers modifiés/créés

### Créés
- `app_mobile/components/expense/DistanceInput.tsx` (nouveau composant)
- `app_mobile/.env.example` (exemple de configuration)
- `app_mobile/GOOGLE_MAPS_SETUP.md` (guide de configuration)
- `app_mobile/FEATURE_DISTANCE_CALCULATION.md` (cette documentation)

### Modifiés
- `app_mobile/components/expense/CostCard.tsx` (intégration du composant)

### Structure de données existante
Aucune modification de la base de données n'est nécessaire. Les champs suivants dans `ExpenseSheetCost` sont déjà présents :
- `distance` (nombre)
- `google_distance` (nombre)
- `route` (JSON)

## Tests recommandés

1. **Test de l'autocomplétion**
   - Saisir une adresse connue
   - Vérifier que les suggestions apparaissent
   - Sélectionner une suggestion

2. **Test du calcul de distance**
   - Saisir deux adresses valides
   - Vérifier que la distance s'affiche
   - Vérifier la cohérence de la distance

3. **Test du calcul de remboursement**
   - Vérifier que le tarif correct est appliqué
   - Vérifier que le calcul est correct (distance × tarif)

4. **Test sans connexion**
   - Désactiver la connexion réseau
   - Vérifier que l'application ne plante pas

5. **Test sans clé API**
   - Supprimer la clé API du .env
   - Vérifier que l'application fonctionne (sans autocomplétion)

## Support

Pour toute question ou problème :
- Consulter `GOOGLE_MAPS_SETUP.md` pour la configuration
- Vérifier les logs de la console pour les erreurs
- S'assurer que les APIs Google Cloud sont bien activées
