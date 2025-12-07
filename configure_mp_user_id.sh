#!/bin/bash

# ============================================================
# Script para Configurar USER_ID de MercadoPago
# ============================================================

echo "🔧 Configurando USER_ID de MercadoPago..."
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ============================================================
# 1. EJECUTAR MIGRACIÓN
# ============================================================
echo -e "${YELLOW}📋 Paso 1: Ejecutando migración para agregar MP_USER_ID...${NC}"
php artisan migrate --path=database/migrations/2025_11_02_123803_add_mp_user_id_to_empresas_table.php

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migración ejecutada exitosamente${NC}"
else
    echo -e "${RED}❌ Error al ejecutar migración${NC}"
    exit 1
fi

echo ""

# ============================================================
# 2. OBTENER USER_ID
# ============================================================
echo -e "${YELLOW}🔍 Paso 2: Obteniendo USER_ID de MercadoPago...${NC}"
echo ""
echo -e "${YELLOW}Por favor, ingresa tu ACCESS_TOKEN de MercadoPago:${NC}"
read -p "ACCESS_TOKEN: " ACCESS_TOKEN

if [ -z "$ACCESS_TOKEN" ]; then
    echo -e "${RED}❌ ACCESS_TOKEN no puede estar vacío${NC}"
    exit 1
fi

echo ""
echo "Consultando API de MercadoPago..."

# Hacer request a la API
USER_INFO=$(curl -s -X GET "https://api.mercadopago.com/users/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

# Extraer USER_ID usando jq (si está disponible)
if command -v jq &> /dev/null; then
    USER_ID=$(echo $USER_INFO | jq -r '.id')
    NICKNAME=$(echo $USER_INFO | jq -r '.nickname')
    EMAIL=$(echo $USER_INFO | jq -r '.email')
    
    if [ "$USER_ID" != "null" ] && [ -n "$USER_ID" ]; then
        echo -e "${GREEN}✅ USER_ID obtenido exitosamente${NC}"
        echo ""
        echo "   USER_ID: $USER_ID"
        echo "   Nickname: $NICKNAME"
        echo "   Email: $EMAIL"
        echo ""
    else
        echo -e "${RED}❌ Error al obtener USER_ID. Verifica tu ACCESS_TOKEN${NC}"
        echo "Respuesta de la API:"
        echo "$USER_INFO"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠️  'jq' no está instalado. Mostrando respuesta completa:${NC}"
    echo "$USER_INFO"
    echo ""
    echo -e "${YELLOW}Por favor, ingresa manualmente tu USER_ID:${NC}"
    read -p "USER_ID: " USER_ID
fi

# ============================================================
# 3. ACTUALIZAR BASE DE DATOS
# ============================================================
echo ""
echo -e "${YELLOW}💾 Paso 3: Actualizando base de datos...${NC}"
echo ""
echo "Ingresa el ID de la empresa a configurar (normalmente 1):"
read -p "Empresa ID: " EMPRESA_ID

if [ -z "$EMPRESA_ID" ]; then
    EMPRESA_ID=1
    echo "Usando empresa ID por defecto: 1"
fi

# Generar SQL
SQL_UPDATE="UPDATE empresas SET MP_USER_ID = '$USER_ID' WHERE id = $EMPRESA_ID;"

echo ""
echo -e "${YELLOW}Se ejecutará el siguiente SQL:${NC}"
echo "$SQL_UPDATE"
echo ""
read -p "¿Confirmar actualización? (s/n): " CONFIRM

if [ "$CONFIRM" = "s" ] || [ "$CONFIRM" = "S" ]; then
    # Intentar actualizar usando tinker
    php artisan tinker <<EOF
\$empresa = App\Models\Empresa::find($EMPRESA_ID);
if (\$empresa) {
    \$empresa->MP_USER_ID = '$USER_ID';
    \$empresa->save();
    echo "✅ Empresa actualizada exitosamente\n";
} else {
    echo "❌ Empresa no encontrada\n";
}
exit
EOF
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Base de datos actualizada${NC}"
    else
        echo -e "${RED}❌ Error al actualizar base de datos${NC}"
        echo ""
        echo "Ejecuta manualmente este SQL:"
        echo "$SQL_UPDATE"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠️  Actualización cancelada${NC}"
    echo ""
    echo "Ejecuta manualmente este SQL:"
    echo "$SQL_UPDATE"
    exit 0
fi

# ============================================================
# 4. VERIFICAR CONFIGURACIÓN
# ============================================================
echo ""
echo -e "${YELLOW}🔍 Paso 4: Verificando configuración...${NC}"

php artisan tinker <<EOF
\$empresa = App\Models\Empresa::find($EMPRESA_ID);
if (\$empresa) {
    echo "\n";
    echo "Empresa: " . \$empresa->name . "\n";
    echo "MP_ACCESS_TOKEN: " . (!empty(\$empresa->MP_ACCESS_TOKEN) ? "✅ Configurado" : "❌ No configurado") . "\n";
    echo "MP_PUBLIC_KEY: " . (!empty(\$empresa->MP_PUBLIC_KEY) ? "✅ Configurado" : "❌ No configurado") . "\n";
    echo "MP_USER_ID: " . (!empty(\$empresa->MP_USER_ID) ? "✅ " . \$empresa->MP_USER_ID : "❌ No configurado") . "\n";
} else {
    echo "❌ Empresa no encontrada\n";
}
exit
EOF

# ============================================================
# FINALIZADO
# ============================================================
echo ""
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ CONFIGURACIÓN COMPLETADA${NC}"
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📋 PRÓXIMOS PASOS:${NC}"
echo ""
echo "1. Accede al componente QR Manager:"
echo -e "${GREEN}   http://localhost:8000/mercadopago/qr-manager${NC}"
echo ""
echo "2. Intenta crear una nueva tienda"
echo ""
echo "3. El error 403 debería estar resuelto"
echo ""
echo -e "${YELLOW}Si sigues teniendo problemas:${NC}"
echo "   - Verifica que el ACCESS_TOKEN sea correcto"
echo "   - Verifica que el USER_ID sea correcto"
echo "   - Revisa los logs: storage/logs/laravel.log"
echo ""
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
