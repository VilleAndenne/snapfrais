#!/bin/bash

echo "🚀 Démarrage de SnapFrais Mobile..."
echo ""

# Tuer les anciens processus
echo "1️⃣ Nettoyage des anciens processus..."
pkill -f "expo start" 2>/dev/null || true
lsof -ti:8081 | xargs kill -9 2>/dev/null || true

# Nettoyer le cache
echo "2️⃣ Nettoyage du cache..."
rm -rf .expo 2>/dev/null || true
rm -rf node_modules/.cache 2>/dev/null || true

# Démarrer Expo
echo "3️⃣ Démarrage d'Expo..."
echo ""
echo "📱 Une fois le QR code affiché:"
echo "  - Appuie sur 'i' pour iOS"
echo "  - Appuie sur 'a' pour Android"
echo "  - Appuie sur 'r' pour recharger"
echo "  - Appuie sur 'q' pour quitter"
echo ""

npx expo start --clear
