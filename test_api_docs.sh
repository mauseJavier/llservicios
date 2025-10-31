#!/bin/bash

# Script de prueba para la documentación de API
# Ejecutar: bash test_api_docs.sh

echo "=========================================="
echo "🧪 TEST DE DOCUMENTACIÓN DE API"
echo "=========================================="
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# URL base (cambiar según tu entorno)
BASE_URL="http://localhost:8000"

# Función para hacer requests y verificar respuesta
test_endpoint() {
    local name=$1
    local url=$2
    local expected_code=$3
    
    echo -n "Testing: $name ... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    
    if [ "$response" -eq "$expected_code" ]; then
        echo -e "${GREEN}✓ OK${NC} (HTTP $response)"
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (HTTP $response, esperado $expected_code)"
        return 1
    fi
}

# Contador de tests
total=0
passed=0

echo "1️⃣  Testeando Vista HTML de Documentación"
echo "─────────────────────────────────────────"
test_endpoint "Vista HTML (/api-docs)" "$BASE_URL/api-docs" 200
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))
echo ""

echo "2️⃣  Testeando Endpoints JSON"
echo "─────────────────────────────────────────"

test_endpoint "Documentación completa (/api/docs)" "$BASE_URL/api/docs" 200
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))

test_endpoint "Lista de grupos (/api/docs/groups)" "$BASE_URL/api/docs/groups" 200
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))

test_endpoint "Docs de Cliente (/api/docs/cliente)" "$BASE_URL/api/docs/cliente" 200
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))

test_endpoint "Docs de MercadoPago (/api/docs/mercadopago)" "$BASE_URL/api/docs/mercadopago" 200
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))

test_endpoint "Docs de WhatsApp (/api/docs/whatsapp)" "$BASE_URL/api/docs/whatsapp" 200
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))

test_endpoint "Grupo inexistente (debe fallar)" "$BASE_URL/api/docs/noexiste" 404
if [ $? -eq 0 ]; then ((passed++)); fi
((total++))

echo ""
echo "3️⃣  Verificando Estructura JSON"
echo "─────────────────────────────────────────"

# Verificar que el JSON tenga la estructura correcta
json_response=$(curl -s "$BASE_URL/api/docs")

if echo "$json_response" | grep -q '"api_name"'; then
    echo -e "${GREEN}✓${NC} Campo 'api_name' presente"
    ((passed++))
else
    echo -e "${RED}✗${NC} Campo 'api_name' ausente"
fi
((total++))

if echo "$json_response" | grep -q '"endpoints"'; then
    echo -e "${GREEN}✓${NC} Campo 'endpoints' presente"
    ((passed++))
else
    echo -e "${RED}✗${NC} Campo 'endpoints' ausente"
fi
((total++))

if echo "$json_response" | grep -q '"version"'; then
    echo -e "${GREEN}✓${NC} Campo 'version' presente"
    ((passed++))
else
    echo -e "${RED}✗${NC} Campo 'version' ausente"
fi
((total++))

echo ""
echo "4️⃣  Verificando Grupos Disponibles"
echo "─────────────────────────────────────────"

groups_response=$(curl -s "$BASE_URL/api/docs/groups")

if echo "$groups_response" | grep -q '"cliente"'; then
    echo -e "${GREEN}✓${NC} Grupo 'cliente' disponible"
    ((passed++))
else
    echo -e "${RED}✗${NC} Grupo 'cliente' no encontrado"
fi
((total++))

if echo "$groups_response" | grep -q '"mercadopago"'; then
    echo -e "${GREEN}✓${NC} Grupo 'mercadopago' disponible"
    ((passed++))
else
    echo -e "${RED}✗${NC} Grupo 'mercadopago' no encontrado"
fi
((total++))

if echo "$groups_response" | grep -q '"whatsapp"'; then
    echo -e "${GREEN}✓${NC} Grupo 'whatsapp' disponible"
    ((passed++))
else
    echo -e "${RED}✗${NC} Grupo 'whatsapp' no encontrado"
fi
((total++))

echo ""
echo "=========================================="
echo "📊 RESUMEN DE TESTS"
echo "=========================================="
echo "Total de tests: $total"
echo -e "Tests pasados: ${GREEN}$passed${NC}"
echo -e "Tests fallidos: ${RED}$((total - passed))${NC}"

percentage=$((passed * 100 / total))
echo "Porcentaje de éxito: $percentage%"

if [ $passed -eq $total ]; then
    echo ""
    echo -e "${GREEN}🎉 ¡TODOS LOS TESTS PASARON!${NC}"
    echo ""
    echo "Tu documentación está funcionando correctamente."
    echo ""
    echo "Accede a la vista HTML en:"
    echo -e "${YELLOW}$BASE_URL/api-docs${NC}"
    echo ""
    exit 0
else
    echo ""
    echo -e "${RED}⚠️  ALGUNOS TESTS FALLARON${NC}"
    echo ""
    echo "Por favor, revisa:"
    echo "1. Que el servidor esté corriendo (php artisan serve)"
    echo "2. Los logs en storage/logs/laravel.log"
    echo "3. Que las rutas estén registradas (php artisan route:list | grep docs)"
    echo ""
    exit 1
fi
