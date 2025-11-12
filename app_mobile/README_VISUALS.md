# 📱 SnapFrais - Application Mobile de Notes de Frais

## ✅ État du projet: Visuels terminés

Tous les écrans et composants visuels ont été créés et sont prêts à l'emploi.

---

## 🎯 Écrans disponibles

| Écran | Route | Type | Statut |
|-------|-------|------|--------|
| **Liste des notes** | `/(tabs)/` | Tab | ✅ Terminé |
| **À valider** | `/(tabs)/validate` | Tab | ✅ Terminé |
| **Détail** | `/expense/[id]` | Modal | ✅ Terminé |
| **Nouvelle note** | `/expense/new` | Modal | ✅ Terminé |
| **Modifier** | `/expense/edit/[id]` | Modal | ✅ Terminé |

---

## 🎨 Palette de couleurs

### Thème Clair
```
Fond principal:   #ffffff
Texte principal:  #11181C
Couleur primaire: #0a7ea4 (bleu clair)
Icônes:          #687076
```

### Thème Sombre
```
Fond principal:   #151718
Texte principal:  #ECEDEE
Couleur primaire: #ffffff
Icônes:          #9BA1A6
```

### Statuts
```
🔵 Brouillon:  #666666 / #999999
🟠 En attente: #FF9500
🟢 Approuvée:  #34C759
🔴 Rejetée:    #FF3B30
```

---

## 🧩 Composants créés

- ✅ ExpenseCard - Carte de note réutilisable
- ✅ ThemedText - Texte adaptatif thème
- ✅ ThemedView - Vue adaptative thème
- ✅ IconSymbol - Icônes iOS/Android
- ✅ TabBarBackground - Fond blur pour tabs
- ✅ HapticTab - Tab avec retour haptique

---

## 📐 Conventions de design

### Espacements
- Padding écran: `20px`
- Gap entre cartes: `12px`
- Padding cartes: `16px`
- Border radius: `12px`

### Typographie
- Titre principal: `28px` Bold
- Sous-titre: `20px` Semi-Bold
- Corps de texte: `16px` Regular
- Détails: `14px` Regular
- Montants: `22-48px` Bold

### Ombres
```css
shadowColor: '#000'
shadowOffset: { width: 0, height: 1-2 }
shadowOpacity: 0.1
shadowRadius: 3-4
elevation: 2-3
```

---

## 🔄 Flux utilisateur

### Création d'une note
```
Liste → Clic [+] → Formulaire → Brouillon OU Soumettre → Retour liste
```

### Validation
```
À Valider → Clic carte → Détail → Approuver/Rejeter → Confirmation → Liste mise à jour
```

### Modification
```
Liste → Clic carte (brouillon/rejetée) → Détail → Clic [✏️] → Modifier → Enregistrer
```

---

## 📊 Données mockées

Actuellement, l'app utilise des données de démonstration:

- **5 notes** dans "Mes Notes"
- **4 notes** dans "À Valider"
- Tous les statuts représentés
- 5 catégories disponibles

---

## 🚀 Commandes

```bash
# Démarrer le serveur de dev
npm start

# Lancer sur iOS
npm run ios

# Lancer sur Android
npm run android

# Lancer sur Web
npm run web

# Vérifier le code
npm run lint
```

---

## 📚 Documentation

- `APP_STRUCTURE.md` - Architecture détaillée
- `SCREENS_OVERVIEW.md` - Vue d'ensemble des écrans
- `VISUELS_CREES.md` - Résumé de ce qui a été créé

---

## ⚠️ Limitations actuelles

### Non implémenté (volontairement)
- ❌ Modification des paramètres utilisateur
- ❌ Configuration des formulaires
- ❌ Gestion des utilisateurs/rôles

### À développer (intégration backend)
- ⏳ Appels API
- ⏳ Authentification
- ⏳ Upload de fichiers
- ⏳ Notifications push
- ⏳ Persistance locale

---

## ✨ Features implémentées

- ✅ Navigation complète (tabs + modals)
- ✅ Design adaptatif clair/sombre
- ✅ Icônes adaptatives iOS/Android
- ✅ Animations et transitions
- ✅ Composants réutilisables
- ✅ Formulaires interactifs
- ✅ États de chargement
- ✅ Confirmations utilisateur

---

## 🎯 Prochaines étapes suggérées

1. **Backend**: Créer les endpoints API
2. **Auth**: Implémenter l'authentification
3. **Upload**: Gérer l'upload de justificatifs
4. **Tests**: Tester sur appareils réels
5. **Feedback**: Itérer selon retours utilisateurs

---

**Version**: 1.0.0  
**Framework**: Expo 54 + React Native 0.81  
**Langage**: TypeScript 5.9  
**État**: ✅ Visuels complets et fonctionnels
