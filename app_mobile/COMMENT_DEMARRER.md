# 🚀 Comment démarrer l'application SnapFrais

## ✅ Corrections effectuées

1. ✅ **Suppression du chargement de police** qui bloquait au splash screen
2. ✅ **Ajout de tous les mappings d'icônes**
3. ✅ **Nettoyage du cache**
4. ✅ **Correction des erreurs TypeScript**

---

## 🎯 Démarrage rapide

### Méthode 1: Script automatique

```bash
./START_APP.sh
```

### Méthode 2: Manuel

```bash
# 1. Nettoyer
pkill -f "expo start"
rm -rf .expo node_modules/.cache

# 2. Démarrer
npx expo start --clear

# 3. Dans le simulateur iOS
# Appuie sur 'i' dans le terminal
```

---

## 🐛 Si ça reste bloqué sur le splash screen

### Étape 1: Tester avec la version ultra-simple

Remplace temporairement le contenu de `app/(tabs)/index.tsx` par :

```typescript
import { StyleSheet, View, Text } from 'react-native';

export default function ExpenseListScreen() {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>🎉 ÇA MARCHE !</Text>
      <Text style={styles.subtitle}>L'app SnapFrais fonctionne</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  subtitle: {
    fontSize: 18,
    color: '#666',
  },
});
```

**Recharge l'app** (appuie sur 'r' dans le terminal Expo)

Si **ça marche**, remets progressivement le contenu complet depuis `index.backup.tsx`

---

### Étape 2: Vérifier les logs

```bash
# Dans un terminal, vérifie les erreurs
npx expo start --clear

# Dans le simulateur iOS
# Cmd + D → Open JS Debugger
# Ou Cmd + Shift + D → Dev Menu → Remote JS Debugging
```

---

### Étape 3: Reset complet

```bash
# Supprimer TOUT
rm -rf .expo
rm -rf node_modules/.cache
rm -rf ios/build
rm -rf android/build

# Redémarrer le simulateur iOS
# Puis relancer l'app
npx expo start --clear
```

---

## 📁 Fichiers de backup créés

Si tu veux revenir en arrière :

- `app/_layout.backup.tsx` - Version avec police (peut causer problème)
- `app/(tabs)/index.backup.tsx` - Version complète de l'écran
- `app/(tabs)/index.simple.tsx` - Version ultra-minimaliste pour tester

---

## 🔍 Diagnostics

### Le splash screen ne disparaît jamais
**Cause**: Problème dans `app/_layout.tsx`
**Solution**: Version sans police déjà installée ✅

### L'app crash au démarrage
**Cause**: Erreur dans un composant
**Solution**: Utilise la version simple de `index.tsx` pour tester

### "Unable to resolve module"
**Cause**: Import manquant
**Solution**: Vérifie les imports dans les fichiers

---

## 🎯 Ordre de test

1. ✅ **Teste avec la version actuelle** (police supprimée)
2. Si bloqué → Utilise `index.simple.tsx`
3. Si ça marche → Remets progressivement les fonctionnalités

---

## 📱 Une fois que ça marche

Pour restaurer la version complète avec toutes les fonctionnalités :

```bash
# Copier le backup complet
cp app/\(tabs\)/index.backup.tsx app/\(tabs\)/index.tsx

# Recharger
# Appuie sur 'r' dans le terminal Expo
```

---

## ⚡ Commandes utiles

```bash
# Recharger l'app
r

# Ouvrir sur iOS
i

# Ouvrir sur Android
a

# Nettoyer et redémarrer
# Ctrl+C pour quitter, puis:
npx expo start --clear

# Voir les logs détaillés
npx expo start --clear --verbose
```

---

## 🆘 Si rien ne marche

1. **Ferme complètement le simulateur iOS**
2. **Tue tous les processus**:
   ```bash
   pkill -f "expo start"
   pkill -f "Metro"
   ```
3. **Redémarre ton Mac** (en dernier recours)
4. **Vérifie qu'il n'y a qu'un seul processus Expo**:
   ```bash
   ps aux | grep expo
   ```

---

## ✅ Checklist de démarrage

- [ ] Tous les anciens processus Expo sont arrêtés
- [ ] Cache .expo supprimé
- [ ] Un seul terminal avec `npx expo start`
- [ ] Simulateur iOS ouvert
- [ ] App rechargée (appuie sur 'r')

---

**Note**: La version actuelle n'utilise plus de police personnalisée, ce qui devrait résoudre le problème du splash screen !
