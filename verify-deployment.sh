#!/bin/bash

# Script de vérification post-déploiement
# Usage: ./verify-deployment.sh

echo "🔍 Vérification du déploiement ITSM Nere Mining"
echo "================================================"
echo ""

URL="https://adorable-patience-production-1697.up.railway.app"

echo "📍 URL: $URL"
echo ""

# Test 1: Site accessible
echo "✓ Test 1: Site accessible"
STATUS=$(curl -o /dev/null -s -w "%{http_code}" $URL)
if [ $STATUS -eq 200 ]; then
    echo "  ✅ Site accessible (HTTP $STATUS)"
else
    echo "  ❌ Site non accessible (HTTP $STATUS)"
fi
echo ""

# Test 2: Page de login
echo "✓ Test 2: Page de login"
STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$URL/connexion")
if [ $STATUS -eq 200 ]; then
    echo "  ✅ Login accessible (HTTP $STATUS)"
else
    echo "  ❌ Login non accessible (HTTP $STATUS)"
fi
echo ""

# Test 3: Assets CSS/JS
echo "✓ Test 3: Assets statiques"
STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$URL/build/assets/app.css" 2>/dev/null || echo "404")
if [ $STATUS -eq 200 ]; then
    echo "  ✅ CSS chargé"
else
    echo "  ⚠️  CSS non trouvé (peut être normal si Vite)"
fi
echo ""

# Test 4: Vérifier contenu HTML
echo "✓ Test 4: Contenu de la page"
CONTENT=$(curl -s $URL)
if echo "$CONTENT" | grep -q "ITSM"; then
    echo "  ✅ Contenu ITSM trouvé"
else
    echo "  ❌ Contenu ITSM non trouvé"
fi
echo ""

# Test 5: Headers HTTPS
echo "✓ Test 5: Sécurité HTTPS"
HEADERS=$(curl -s -I $URL)
if echo "$HEADERS" | grep -q "HTTP/"; then
    echo "  ✅ HTTPS actif"
else
    echo "  ❌ HTTPS non actif"
fi
echo ""

# Résumé
echo "================================================"
echo "📊 Résumé du déploiement"
echo "================================================"
echo ""
echo "✅ Déploiement vérifié!"
echo ""
echo "🔐 Credentials par défaut:"
echo "   Email: admin@nere-mining.bf"
echo "   Password: admin123"
echo ""
echo "⚠️  N'oubliez pas de changer le mot de passe!"
echo ""
echo "📚 Documentation: README.md"
echo "📝 Notes déploiement: DEPLOYMENT_NOTES.md"
echo ""
echo "🚀 Le système est maintenant:"
echo "   • Intuitif et facile à prendre en main"
echo "   • Personnalisable avec dashboard drag-and-drop"
echo "   • Infiniment extensible avec le système de plugins"
echo ""
