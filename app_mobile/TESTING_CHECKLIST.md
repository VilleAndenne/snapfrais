# Liste de vérification - Calcul de distance kilométrique

## Configuration initiale

- [ ] Créer le fichier `.env` à partir de `.env.example`
- [ ] Ajouter une clé API Google Maps valide dans `.env`
- [ ] Activer les APIs suivantes dans Google Cloud Console :
  - [ ] Places API
  - [ ] Distance Matrix API
- [ ] Redémarrer le serveur de développement Expo

## Tests fonctionnels

### 1. Test de base - Autocomplétion d'adresses

- [ ] Ouvrir l'application mobile
- [ ] Naviguer vers la création d'une note de frais
- [ ] Sélectionner un formulaire
- [ ] Ajouter un type de coût remboursé au kilomètre
- [ ] Cliquer dans le champ "Adresse de départ"
- [ ] Saisir au moins 3 caractères (ex: "Par")
- [ ] **Résultat attendu** : Une liste de suggestions d'adresses apparaît
- [ ] Sélectionner une adresse dans la liste
- [ ] **Résultat attendu** : L'adresse sélectionnée remplit le champ

### 2. Test - Calcul de distance

- [ ] Saisir une adresse de départ valide (ex: "10 Rue de la Paix, Paris")
- [ ] Saisir une adresse d'arrivée valide (ex: "Tour Eiffel, Paris")
- [ ] **Résultat attendu** : Un indicateur "Calcul en cours..." apparaît brièvement
- [ ] **Résultat attendu** : La distance calculée s'affiche (ex: "3.2 km")
- [ ] **Résultat attendu** : Un encadré vert avec l'icône ↔️ montre la distance

### 3. Test - Calcul du remboursement

- [ ] Après le calcul de distance, vérifier l'affichage du détail
- [ ] **Résultat attendu** : Trois lignes s'affichent :
  - Distance: XX.XX km
  - Tarif: X.XX €/km
  - Remboursement: XX.XX € (en vert)
- [ ] Vérifier que le calcul est correct : `distance × tarif = remboursement`

### 4. Test - Changement de date

- [ ] Modifier la date du coût
- [ ] **Résultat attendu** : Le tarif est recalculé si différent
- [ ] **Résultat attendu** : Le montant du remboursement est recalculé

### 5. Test - Soumission du formulaire

- [ ] Remplir tous les champs requis du formulaire
- [ ] Ajouter au moins un coût kilométrique avec trajet
- [ ] Cliquer sur "Soumettre" ou "Enregistrer en brouillon"
- [ ] **Résultat attendu** : Message "Note de frais soumise avec succès"
- [ ] Vérifier dans le backend que les données sont bien sauvegardées :
  - `distance` (nombre de km)
  - `route` (tableau avec départ/arrivée)

### 6. Test - Duplication de coût

- [ ] Créer un coût kilométrique avec un trajet
- [ ] Cliquer sur l'icône de duplication 📋
- [ ] Saisir "2" copies
- [ ] **Résultat attendu** : 2 nouvelles cartes de coût sont créées
- [ ] **Résultat attendu** : Les trajets sont copiés dans les nouvelles cartes

## Tests d'erreur et cas limites

### 7. Test - Sans clé API

- [ ] Supprimer ou commenter `EXPO_PUBLIC_GOOGLE_MAPS_API_KEY` dans `.env`
- [ ] Redémarrer l'application
- [ ] Tenter d'utiliser l'autocomplétion
- [ ] **Résultat attendu** : Aucune suggestion n'apparaît
- [ ] **Résultat attendu** : Un warning apparaît dans la console : "Google Maps API key not configured"
- [ ] **Résultat attendu** : L'application ne plante pas

### 8. Test - Adresses invalides

- [ ] Saisir une adresse inexistante (ex: "azerty qwerty")
- [ ] **Résultat attendu** : Aucune suggestion n'apparaît
- [ ] **Résultat attendu** : Le calcul de distance ne démarre pas

### 9. Test - Sans connexion réseau

- [ ] Activer le mode avion sur l'appareil
- [ ] Tenter d'utiliser l'autocomplétion
- [ ] **Résultat attendu** : Aucune suggestion n'apparaît
- [ ] **Résultat attendu** : Un message d'erreur dans la console (optionnel)
- [ ] **Résultat attendu** : L'application ne plante pas

### 10. Test - Adresses très éloignées

- [ ] Saisir deux adresses très éloignées (ex: Paris → Marseille)
- [ ] **Résultat attendu** : Le calcul fonctionne (environ 775 km)
- [ ] **Résultat attendu** : Le montant du remboursement est calculé correctement

### 11. Test - Adresses identiques

- [ ] Saisir la même adresse pour départ et arrivée
- [ ] **Résultat attendu** : Distance = 0 km
- [ ] **Résultat attendu** : Remboursement = 0.00 €

## Tests d'interface

### 12. Test - Mode sombre

- [ ] Basculer l'appareil en mode sombre
- [ ] Vérifier que tous les éléments sont lisibles
- [ ] **Résultat attendu** : Les couleurs s'adaptent correctement
- [ ] **Résultat attendu** : Les bordures et le texte restent visibles

### 13. Test - Mode clair

- [ ] Basculer l'appareil en mode clair
- [ ] Vérifier que tous les éléments sont lisibles
- [ ] **Résultat attendu** : Les couleurs s'adaptent correctement

### 14. Test - Scroll et suggestions

- [ ] Saisir une adresse avec beaucoup de résultats (ex: "Rue")
- [ ] **Résultat attendu** : La liste de suggestions est scrollable
- [ ] **Résultat attendu** : Maximum 200px de hauteur pour la liste
- [ ] Scroller la liste
- [ ] Sélectionner une suggestion en bas de liste
- [ ] **Résultat attendu** : La suggestion est bien sélectionnée

### 15. Test - Performance

- [ ] Saisir rapidement plusieurs caractères
- [ ] **Résultat attendu** : Pas de lag visible
- [ ] **Résultat attendu** : La recherche attend 300ms avant de lancer la requête
- [ ] Effacer et re-saisir plusieurs fois
- [ ] **Résultat attendu** : Les requêtes précédentes sont annulées

## Tests de régression

### 16. Test - Autres types de coûts

- [ ] Ajouter un coût de type "fixed" (montant fixe)
- [ ] **Résultat attendu** : Le champ montant s'affiche normalement
- [ ] Ajouter un coût de type "percentage" (pourcentage)
- [ ] **Résultat attendu** : Les champs montant payé et pourcentage s'affichent
- [ ] **Résultat attendu** : Le calcul du remboursement fonctionne

### 17. Test - Suppression de coût

- [ ] Créer un coût kilométrique avec trajet
- [ ] Cliquer sur l'icône de suppression 🗑️
- [ ] **Résultat attendu** : Le coût est supprimé
- [ ] **Résultat attendu** : Aucune erreur dans la console

## Vérification backend

### 18. Test - Données envoyées au backend

Dans la console du navigateur ou les logs du serveur :

```javascript
// Structure attendue dans FormData
costs[0][cost_id]: 123
costs[0][date]: "2025-11-10"
costs[0][data][googleKm]: 42.5
costs[0][data][totalKm]: 42.5
costs[0][data][departure]: "10 Rue de la Paix, Paris, France"
costs[0][data][arrival]: "Tour Eiffel, Paris, France"
```

- [ ] Vérifier que `costs[X][data][googleKm]` est envoyé
- [ ] Vérifier que `costs[X][data][totalKm]` est envoyé
- [ ] Vérifier que `costs[X][data][departure]` est envoyé
- [ ] Vérifier que `costs[X][data][arrival]` est envoyé

### 19. Test - Données sauvegardées en base

Dans la base de données, table `expense_sheet_costs` :

- [ ] Vérifier que le champ `distance` contient la distance en km
- [ ] Vérifier que le champ `google_distance` contient la distance Google
- [ ] Vérifier que le champ `route` contient les adresses au format JSON
- [ ] Exemple de `route` attendu :
```json
[
  {"address": "10 Rue de la Paix, Paris, France", "type": "origin"},
  {"address": "Tour Eiffel, Paris, France", "type": "destination"}
]
```

## Résumé des résultats

**Date du test** : ___________

**Testeur** : ___________

**Version** : ___________

| Test | Statut | Commentaire |
|------|--------|-------------|
| 1. Autocomplétion | ✅ ❌ | |
| 2. Calcul distance | ✅ ❌ | |
| 3. Calcul remboursement | ✅ ❌ | |
| 4. Changement date | ✅ ❌ | |
| 5. Soumission | ✅ ❌ | |
| 6. Duplication | ✅ ❌ | |
| 7. Sans clé API | ✅ ❌ | |
| 8. Adresses invalides | ✅ ❌ | |
| 9. Sans réseau | ✅ ❌ | |
| 10. Adresses éloignées | ✅ ❌ | |
| 11. Adresses identiques | ✅ ❌ | |
| 12. Mode sombre | ✅ ❌ | |
| 13. Mode clair | ✅ ❌ | |
| 14. Scroll suggestions | ✅ ❌ | |
| 15. Performance | ✅ ❌ | |
| 16. Autres types coûts | ✅ ❌ | |
| 17. Suppression | ✅ ❌ | |
| 18. Données backend | ✅ ❌ | |
| 19. Base de données | ✅ ❌ | |

**Bugs identifiés** :

1. ___________________________________________
2. ___________________________________________
3. ___________________________________________

**Améliorations suggérées** :

1. ___________________________________________
2. ___________________________________________
3. ___________________________________________
