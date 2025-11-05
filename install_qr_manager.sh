#!/bin/bash

# ============================================================
# Script de Instalación: Componente Livewire QR Manager
# ============================================================

echo "🚀 Instalando Componente Livewire MercadoPago QR Manager..."
echo ""

# Colores para los mensajes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ============================================================
# 1. EJECUTAR MIGRACIONES
# ============================================================
echo -e "${YELLOW}📋 Paso 1: Ejecutando migraciones...${NC}"
php artisan migrate

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones ejecutadas exitosamente${NC}"
else
    echo -e "${RED}❌ Error al ejecutar migraciones${NC}"
    exit 1
fi

echo ""

# ============================================================
# 2. LIMPIAR CACHE
# ============================================================
echo -e "${YELLOW}🧹 Paso 2: Limpiando cache de Laravel...${NC}"

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Cache limpiado exitosamente${NC}"
else
    echo -e "${RED}❌ Error al limpiar cache${NC}"
    exit 1
fi

echo ""

# ============================================================
# 3. VERIFICAR CONFIGURACIÓN
# ============================================================
echo -e "${YELLOW}🔍 Paso 3: Verificando configuración...${NC}"

# Verificar que Livewire esté instalado
if php artisan | grep -q "livewire"; then
    echo -e "${GREEN}✅ Livewire está instalado${NC}"
else
    echo -e "${RED}❌ Livewire NO está instalado${NC}"
    echo -e "${YELLOW}   Instalar con: composer require livewire/livewire${NC}"
fi

# Verificar las tablas creadas
php artisan db:table mercadopago_stores > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Tabla mercadopago_stores creada${NC}"
else
    echo -e "${RED}❌ Tabla mercadopago_stores NO encontrada${NC}"
fi

php artisan db:table mercadopago_pos > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Tabla mercadopago_pos creada${NC}"
else
    echo -e "${RED}❌ Tabla mercadopago_pos NO encontrada${NC}"
fi

echo ""

# ============================================================
# 4. INSTRUCCIONES FINALES
# ============================================================
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ INSTALACIÓN COMPLETADA${NC}"
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📋 PRÓXIMOS PASOS:${NC}"
echo ""
echo "1. Configurar credenciales de MercadoPago en la base de datos:"
echo ""
echo -e "${YELLOW}   UPDATE empresas SET${NC}"
echo -e "${YELLOW}     MP_ACCESS_TOKEN = 'APP_USR-xxxxxxxxxxxx',${NC}"
echo -e "${YELLOW}     MP_PUBLIC_KEY = 'APP_USR-xxxxxxxxxxxx'${NC}"
echo -e "${YELLOW}   WHERE id = 1;${NC}"
echo ""
echo "2. Acceder al componente en:"
echo -e "${GREEN}   http://localhost:8000/mercadopago/qr-manager${NC}"
echo ""
echo "3. Crear tu primera tienda y caja"
echo ""
echo "4. Ver la documentación completa en:"
echo -e "${GREEN}   - COMPONENTE_LIVEWIRE_QR_MANAGER.md${NC}"
echo -e "${GREEN}   - MERCADOPAGO_QR_DOCUMENTATION.md${NC}"
echo -e "${GREEN}   - MERCADOPAGO_QR_QUICK_START.md${NC}"
echo ""
echo -e "${YELLOW}🔐 IMPORTANTE:${NC}"
echo "   - Usa credenciales de SANDBOX en desarrollo"
echo "   - Usa credenciales de PRODUCCIÓN para cobros reales"
echo ""
echo -e "${GREEN}════════════════════════════════════════════════${NC}"
