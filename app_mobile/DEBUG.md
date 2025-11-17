# 🐛 Problème : Splash Screen bloqué

## Diagnostic

Le splash screen reste bloqué, ce qui indique généralement que :
1. ❌ Une erreur empêche le chargement initial
2. ❌ Les polices ne se chargent pas
3. ❌ Un composant plante au démarrage

## Solutions testées

### ✅ 1. Erreurs TypeScript corrigées
- Ajout de tous les mappings d'icônes
- Correction du type de `name` dans IconSymbol
- Plus d'erreurs TypeScript

### ✅ 2. Cache nettoyé
```bash
rm -rf .expo
rm -rf node_modules/.cache
```

## 🔍 Étapes de débogage suivantes

### Vérifier les erreurs dans le simulateur
1. Ouvrir la console du simulateur
2. Chercher les erreurs JavaScript

### Vérifier le terminal Expo
Regarder les logs dans le terminal où `npm start` tourne

### Tester une version minimale
Si le problème persiste, créer un écran de test minimal :

```typescript
// app/(tabs)/index.tsx - VERSION MINIMALE
import { View, Text } from 'react-native';

export default function TestScreen() {
  return (
    <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
      <Text>Test</Text>
    </View>
  );
}
```

## 🛠️ Commandes utiles

```bash
# Redémarrer complètement
npx expo start --clear

# Voir les logs détaillés
npx expo start --clear --verbose

# Rebuild complet
rm -rf .expo node_modules/.cache
npm start
```

## ⚠️ Problèmes courants

### Polices
Si les polices ne se chargent pas :
```typescript
// Dans app/_layout.tsx
const [loaded] = useFonts({
  SpaceMono: require('../assets/fonts/SpaceMono-Regular.ttf'),
});
```

### Imports
Vérifier que tous les imports sont corrects :
- ✅ Chemins avec @/
- ✅ Extensions .tsx omises  
- ✅ Casse correcte (kebab-case)

## 🎯 Prochaine étape

Regarder les logs dans :
1. Le terminal Expo
2. La console du simulateur iOS
3. Metro bundler output
