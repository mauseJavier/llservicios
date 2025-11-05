#!/bin/bash

# ============================================================
# Script para Verificar Permisos de MercadoPago
# ============================================================

echo "🔍 Verificando permisos de MercadoPago..."
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Solicitar ACCESS_TOKEN
echo -e "${YELLOW}Ingresa tu ACCESS_TOKEN de MercadoPago:${NC}"
read -p "ACCESS_TOKEN: " ACCESS_TOKEN

if [ -z "$ACCESS_TOKEN" ]; then
    echo -e "${RED}❌ ACCESS_TOKEN no puede estar vacío${NC}"
    exit 1
fi

echo ""
echo "Consultando información de la aplicación..."
echo ""

# ============================================================
# 1. OBTENER INFORMACIÓN DEL USUARIO
# ============================================================
USER_INFO=$(curl -s -X GET "https://api.mercadopago.com/users/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

if command -v jq &> /dev/null; then
    USER_ID=$(echo $USER_INFO | jq -r '.id')
    NICKNAME=$(echo $USER_INFO | jq -r '.nickname')
    EMAIL=$(echo $USER_INFO | jq -r '.email')
    SITE_ID=$(echo $USER_INFO | jq -r '.site_id')
    
    echo -e "${GREEN}✅ Información del Usuario:${NC}"
    echo "   USER_ID: $USER_ID"
    echo "   Nickname: $NICKNAME"
    echo "   Email: $EMAIL"
    echo "   País: $SITE_ID"
    echo ""
else
    echo -e "${YELLOW}⚠️  'jq' no está instalado${NC}"
    echo "Respuesta completa:"
    echo "$USER_INFO"
fi

# ============================================================
# 2. VERIFICAR TIPO DE TOKEN
# ============================================================
echo ""
echo -e "${YELLOW}🔑 Verificando tipo de credencial...${NC}"

if [[ $ACCESS_TOKEN == TEST-* ]]; then
    echo -e "${GREEN}✅ Token de TEST/SANDBOX detectado${NC}"
    echo "   Este token tiene todos los permisos habilitados"
    TOKEN_TYPE="TEST"
elif [[ $ACCESS_TOKEN == APP_USR-* ]]; then
    echo -e "${YELLOW}⚠️  Token de PRODUCCIÓN detectado${NC}"
    echo "   Este token necesita permisos específicos habilitados"
    TOKEN_TYPE="PRODUCTION"
else
    echo -e "${RED}❌ Formato de token no reconocido${NC}"
    TOKEN_TYPE="UNKNOWN"
fi

# ============================================================
# 3. INTENTAR CREAR SUCURSAL DE PRUEBA
# ============================================================
echo ""
echo -e "${YELLOW}🧪 Intentando crear una sucursal de prueba...${NC}"

if [ -z "$USER_ID" ] || [ "$USER_ID" = "null" ]; then
    echo -e "${RED}❌ No se pudo obtener USER_ID. Verifica tu ACCESS_TOKEN${NC}"
    exit 1
fi

RANDOM_ID=$(date +%s)
TEST_STORE=$(curl -s -X POST "https://api.mercadopago.com/users/$USER_ID/stores" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Store - Delete Me",
    "external_id": "test_'$RANDOM_ID'",
    "location": {
      "street_number": "123",
      "street_name": "Test Street",
      "city_name": "Test City",
      "state_name": "Test State",
      "latitude": -34.603722,
      "longitude": -58.381592,
      "reference": "Test"
    }
  }')

echo ""

if command -v jq &> /dev/null; then
    ERROR_MSG=$(echo $TEST_STORE | jq -r '.message // empty')
    ERROR_CODE=$(echo $TEST_STORE | jq -r '.error // empty')
    STORE_ID=$(echo $TEST_STORE | jq -r '.id // empty')
    
    if [ -n "$STORE_ID" ] && [ "$STORE_ID" != "null" ]; then
        echo -e "${GREEN}✅ ¡ÉXITO! La sucursal de prueba se creó correctamente${NC}"
        echo "   Store ID: $STORE_ID"
        echo ""
        echo "   Tu cuenta tiene permisos para usar QR Code API"
        echo ""
        
        # Eliminar sucursal de prueba
        echo "   Eliminando sucursal de prueba..."
        curl -s -X DELETE "https://api.mercadopago.com/users/$USER_ID/stores/$STORE_ID" \
          -H "Authorization: Bearer $ACCESS_TOKEN" > /dev/null
        echo -e "${GREEN}   ✅ Sucursal de prueba eliminada${NC}"
        
    elif [ -n "$ERROR_CODE" ]; then
        echo -e "${RED}❌ ERROR: No tienes permisos para crear sucursales${NC}"
        echo ""
        echo "   Error: $ERROR_CODE"
        echo "   Mensaje: $ERROR_MSG"
        echo ""
        
        if [ "$ERROR_CODE" = "forbidden" ]; then
            echo -e "${YELLOW}📋 SOLUCIÓN:${NC}"
            echo ""
            
            if [ "$TOKEN_TYPE" = "PRODUCTION" ]; then
                echo "   Tu token de PRODUCCIÓN no tiene habilitado QR Code API."
                echo ""
                echo "   OPCIÓN 1 - Habilitar QR Code en Producción:"
                echo "   1. Ve a: https://www.mercadopago.com.ar/developers/panel/app"
                echo "   2. Selecciona tu aplicación"
                echo "   3. En 'Productos y servicios', habilita 'Código QR'"
                echo "   4. Si no ves la opción, contacta al soporte de MercadoPago"
                echo ""
                echo "   OPCIÓN 2 - Usar credenciales de TEST (Recomendado para desarrollo):"
                echo "   1. Ve a: https://www.mercadopago.com.ar/developers/panel/app"
                echo "   2. Selecciona tu aplicación"
                echo "   3. Ve a la pestaña 'Credenciales de prueba'"
                echo "   4. Copia el 'Access Token de prueba' (empieza con TEST-)"
                echo "   5. Actualiza tu base de datos con estas credenciales"
                echo ""
            else
                echo "   Contacta al soporte de MercadoPago para habilitar QR Code API"
                echo "   Soporte: https://www.mercadopago.com.ar/developers/es/support"
                echo ""
            fi
        fi
    else
        echo -e "${RED}❌ Respuesta inesperada de la API${NC}"
        echo "Respuesta completa:"
        echo "$TEST_STORE"
    fi
else
    echo "Respuesta completa de la API:"
    echo "$TEST_STORE"
fi

# ============================================================
# 4. RESUMEN
# ============================================================
echo ""
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo -e "${GREEN}RESUMEN${NC}"
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo ""
echo "Tipo de credencial: $TOKEN_TYPE"
echo "USER_ID: $USER_ID"
echo ""

if [ -n "$STORE_ID" ] && [ "$STORE_ID" != "null" ]; then
    echo -e "${GREEN}✅ Tu cuenta está configurada correctamente para usar QR Code${NC}"
else
    echo -e "${RED}❌ Tu cuenta NO tiene permisos para usar QR Code${NC}"
    echo ""
    echo "Usa credenciales de TEST o contacta al soporte de MercadoPago"
fi

echo ""
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
