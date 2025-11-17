# Structure de l'Application Mobile SnapFrais

## 📱 Vue d'ensemble

Application mobile React Native (Expo) pour la gestion des notes de frais. L'application permet aux utilisateurs d'encoder des notes de frais, de les soumettre pour validation, et aux validateurs d'approuver ou rejeter ces notes.

## 🎨 Écrans Créés

### 1. Écran principal - Liste des notes (`app/(tabs)/index.tsx`)
**Fonctionnalités :**
- Affiche toutes les notes de frais de l'utilisateur
- Badge de statut coloré (Brouillon, En attente, Approuvée, Rejetée)
- Bouton flottant pour créer une nouvelle note
- Navigation vers le détail en cliquant sur une carte
- Affichage : titre, montant, date, catégorie, statut

**Design :**
- Liste scrollable avec cartes arrondies
- Ombres subtiles pour la profondeur
- Icônes SF Symbols pour iOS / Material pour Android
- Adaptable thème clair/sombre

---

### 2. Écran de validation (`app/(tabs)/validate.tsx`)
**Fonctionnalités :**
- Liste des notes en attente de validation
- Badge compteur dans le header
- Informations utilisateur (avatar, nom, email)
- Actions rapides : Approuver / Rejeter
- État vide avec message encourageant

**Design :**
- Cartes plus grandes avec profil utilisateur
- Boutons d'action colorés (vert/rouge)
- Animation et retours visuels
- Confirmation avant validation/rejet

---

### 3. Écran de détail (`app/expense/[id].tsx`)
**Fonctionnalités :**
- Vue complète d'une note de frais
- Bannière de statut colorée
- Informations détaillées (date, catégorie, commerçant, paiement)
- Galerie de justificatifs scrollable
- Raison du rejet si applicable
- Actions conditionnelles selon le statut
  - Brouillon : Supprimer / Soumettre
  - Rejetée : Modifier
  - En attente/Approuvée : Lecture seule

**Design :**
- Présentation modale
- Mise en page spacieuse et lisible
- Cartes groupées par type d'information
- Montant affiché en grand

---

### 4. Écran de création (`app/expense/new.tsx`)
**Fonctionnalités :**
- Formulaire complet de saisie
- Champs : titre*, montant*, date, catégorie*, commerçant, description
- Sélection visuelle de catégorie (5 options avec icônes)
- Upload de justificatifs (photo/galerie)
- Actions : Enregistrer en brouillon / Soumettre

**Design :**
- Champs de formulaire modernes
- Grille de catégories interactive
- Boutons upload avec icônes
- Validation des champs obligatoires

---

### 5. Écran d'édition (`app/expense/edit/[id].tsx`)
**Fonctionnalités :**
- Modification d'une note existante
- Pré-remplissage avec données actuelles
- Bouton "Enregistrer" dans le header
- Identique au formulaire de création

**Design :**
- Interface cohérente avec l'écran de création
- Bouton d'enregistrement visible en haut

---

## 🗂 Structure des fichiers

```
app_mobile/
├── app/
│   ├── (tabs)/                      # Navigation par onglets
│   │   ├── _layout.tsx             # Configuration des tabs
│   │   ├── index.tsx               # 📋 Liste des notes
│   │   └── validate.tsx            # ✅ Notes à valider
│   │
│   ├── expense/
│   │   ├── [id].tsx                # 🔍 Détail d'une note
│   │   ├── new.tsx                 # ➕ Nouvelle note
│   │   └── edit/
│   │       └── [id].tsx            # ✏️ Modifier une note
│   │
│   └── _layout.tsx                 # Layout racine + Stack Navigator
│
├── components/
│   ├── ui/
│   │   ├── IconSymbol.tsx          # Icônes adaptatives iOS/Android
│   │   ├── IconSymbol.ios.tsx      # Implémentation iOS
│   │   ├── TabBarBackground.tsx    # Fond de tab bar avec blur
│   │   └── collapsible.tsx         # Composant pliable
│   │
│   ├── ExpenseCard.tsx             # 🎴 Carte de note réutilisable
│   ├── themed-text.tsx             # Texte adaptatif thème
│   ├── themed-view.tsx             # Vue adaptative thème
│   ├── haptic-tab.tsx              # Tab avec retour haptique
│   ├── parallax-scroll-view.tsx    # ScrollView avec parallax
│   ├── hello-wave.tsx              # Animation de salutation
│   └── external-link.tsx           # Lien externe
│
├── constants/
│   └── theme.ts                    # 🎨 Couleurs et polices
│
├── hooks/
│   ├── useColorScheme.ts           # Hook thème clair/sombre
│   ├── useColorScheme.web.ts       # Version web
│   └── useThemeColor.ts            # Helper couleurs thématiques
│
└── assets/
    ├── fonts/
    │   └── SpaceMono-Regular.ttf
    └── images/
```

---

## 🎨 Design System

### Couleurs
```typescript
Colors = {
  light: {
    text: '#11181C',
    background: '#fff',
    tint: '#0a7ea4',        // Bleu principal
    icon: '#687076',
    tabIconDefault: '#687076',
    tabIconSelected: '#0a7ea4',
  },
  dark: {
    text: '#ECEDEE',
    background: '#151718',
    tint: '#fff',
    icon: '#9BA1A6',
    tabIconDefault: '#9BA1A6',
    tabIconSelected: '#fff',
  },
}
```

### Couleurs de statut
- **Brouillon** : Gris (#666 dark / #999 light)
- **En attente** : Orange (#FF9500)
- **Approuvée** : Vert (#34C759)
- **Rejetée** : Rouge (#FF3B30)

### Typographie
- **Titres** : 28px, Bold
- **Sous-titres** : 20px, Semi-bold
- **Corps** : 16px, Regular
- **Détails** : 14px, Regular
- **Montants** : 22-48px, Bold

### Espacements
- Padding écran : 20px
- Gap cartes : 12px
- Padding cartes : 16px
- Border radius : 12px

---

## 🔄 Navigation

### Structure de navigation
```
Root (Stack)
├── (tabs) - Tab Navigator
│   ├── index - Mes Notes
│   └── validate - À Valider
│
└── Modal Screens
    ├── expense/[id] - Détail (modal)
    ├── expense/new - Nouvelle note (modal)
    └── expense/edit/[id] - Modifier (modal)
```

### Types de navigation
- **Tabs** : 2 onglets principaux avec icônes
- **Modal** : Écrans de détail et formulaires
- **Stack** : Navigation hiérarchique

---

## 📊 Modèles de données

### Expense (Liste)
```typescript
interface Expense {
  id: string;
  title: string;
  amount: number;
  date: string;              // ISO format
  category: string;
  status: 'draft' | 'pending' | 'approved' | 'rejected';
}
```

### ExpenseDetail
```typescript
interface ExpenseDetail extends Expense {
  description?: string;
  receipts?: string[];       // URLs des images
  merchant?: string;
  paymentMethod?: string;
  rejectionReason?: string;
}
```

### ExpenseToValidate
```typescript
interface ExpenseToValidate extends Expense {
  userName: string;
  userEmail: string;
}
```

---

## 🎯 Catégories disponibles

1. **Repas** - `fork.knife`
2. **Transport** - `car.fill`
3. **Hébergement** - `bed.double.fill`
4. **Fournitures** - `cart.fill`
5. **Autre** - `ellipsis.circle.fill`

---

## 🚀 Prochaines étapes (Intégration backend)

### À implémenter :
1. **API Client** : Appels HTTP vers le backend
2. **Authentification** : Connexion utilisateur
3. **État global** : Context API ou Redux
4. **Upload images** : Envoi des justificatifs
5. **Notifications** : Push notifications
6. **Offline mode** : Persistance locale
7. **Validation formulaires** : Règles métier
8. **Date picker** : Sélection de date native
9. **Camera** : Capture de justificatifs
10. **Recherche/Filtres** : Sur les listes

### Endpoints suggérés :
```
GET    /api/expenses                # Liste des notes
GET    /api/expenses/:id            # Détail
POST   /api/expenses                # Créer
PUT    /api/expenses/:id            # Modifier
DELETE /api/expenses/:id            # Supprimer
POST   /api/expenses/:id/submit     # Soumettre
GET    /api/expenses/to-validate    # À valider
POST   /api/expenses/:id/approve    # Approuver
POST   /api/expenses/:id/reject     # Rejeter
POST   /api/expenses/:id/receipts   # Upload justificatif
```

---

## 📱 Commandes utiles

```bash
# Démarrer l'app
npm start

# Lancer sur iOS
npm run ios

# Lancer sur Android
npm run android

# Lancer sur Web
npm run web

# Linter
npm run lint
```

---

## ✅ Features implémentées

- ✅ Navigation par onglets
- ✅ Liste des notes de frais
- ✅ Liste des notes à valider
- ✅ Détail d'une note
- ✅ Création d'une note
- ✅ Modification d'une note
- ✅ Statuts colorés
- ✅ Thème clair/sombre
- ✅ Icônes adaptatives iOS/Android
- ✅ Animations et transitions
- ✅ Retours haptiques (iOS)
- ✅ Composants réutilisables

## ❌ Non implémenté (par design)

- ❌ Modification des paramètres utilisateur
- ❌ Configuration des formulaires
- ❌ Gestion des utilisateurs
- ❌ Administration

---

**Version** : 1.0.0
**Framework** : Expo 54 + React Native 0.81
**Routing** : Expo Router 6
**Langage** : TypeScript 5.9
