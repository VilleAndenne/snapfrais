# Configuration de Google Maps API

## Prérequis

Pour utiliser la fonctionnalité de calcul kilométrique dans l'application mobile, vous devez configurer une clé API Google Maps.

## Étapes de configuration

### 1. Créer un projet Google Cloud

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un nouveau projet ou sélectionnez un projet existant
3. Notez l'ID du projet

### 2. Activer les APIs nécessaires

Dans la console Google Cloud, activez les APIs suivantes :

1. **Places API** - Pour l'autocomplétion des adresses
   - [Lien direct](https://console.cloud.google.com/apis/library/places-backend.googleapis.com)

2. **Distance Matrix API** - Pour le calcul des distances
   - [Lien direct](https://console.cloud.google.com/apis/library/distance-matrix-backend.googleapis.com)

### 3. Créer une clé API

1. Dans la console Google Cloud, allez dans **APIs & Services > Credentials**
2. Cliquez sur **Create Credentials > API Key**
3. Copiez la clé API générée
4. (Recommandé) Restreignez la clé API :
   - Restrictions d'application : Aucune (ou selon votre besoin)
   - Restrictions d'API : Sélectionnez uniquement "Places API" et "Distance Matrix API"

### 4. Configurer l'application

1. Créez un fichier `.env` à la racine du dossier `app_mobile` :

```bash
cp .env.example .env
```

2. Éditez le fichier `.env` et ajoutez votre clé API :

```
EXPO_PUBLIC_GOOGLE_MAPS_API_KEY=AIzaSy...votre_clé_ici
```

### 5. Redémarrer l'application

Après avoir configuré la clé API, redémarrez le serveur de développement :

```bash
npm start
```

## Coûts

Google Maps API offre un quota gratuit mensuel :

- **Places API** : $200 de crédit gratuit par mois
  - Autocomplétion : ~$2.83 par 1000 requêtes (après le quota gratuit)

- **Distance Matrix API** : $200 de crédit gratuit par mois
  - Calcul de distance : ~$5 par 1000 éléments (après le quota gratuit)

**Note :** Le quota gratuit de $200/mois couvre environ :
- 70,000 autocompletions d'adresses
- 40,000 calculs de distance

## Sécurité

⚠️ **Important** :

1. Ne committez JAMAIS le fichier `.env` dans Git
2. Le fichier `.env` est déjà dans `.gitignore`
3. Restreignez votre clé API aux seules APIs nécessaires
4. En production, utilisez des restrictions d'API key par domaine/bundle ID

## Dépannage

### L'autocomplétion ne fonctionne pas

- Vérifiez que la Places API est bien activée
- Vérifiez que votre clé API est correcte dans le fichier `.env`
- Redémarrez l'application Expo

### Le calcul de distance ne fonctionne pas

- Vérifiez que la Distance Matrix API est bien activée
- Consultez les logs de la console pour voir les erreurs
- Vérifiez que vous avez un compte de facturation actif (même avec le quota gratuit)

### Erreur "API key not configured"

- Le fichier `.env` n'existe pas ou est mal configuré
- La variable `EXPO_PUBLIC_GOOGLE_MAPS_API_KEY` n'est pas définie
- Redémarrez le serveur de développement après avoir créé le fichier `.env`

## Support

Pour plus d'informations :
- [Documentation Google Maps Platform](https://developers.google.com/maps/documentation)
- [Tarification Google Maps](https://mapsplatform.google.com/pricing/)
