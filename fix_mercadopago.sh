#!/bin/bash

# Script de ayuda para configurar credenciales de MercadoPago
# Autor: Sistema de Gestión
# Fecha: 2025-11-02

echo "================================================"
echo "  CONFIGURACIÓN DE MERCADOPAGO - GUÍA RÁPIDA"
echo "================================================"
echo ""

# Colores para mejor visualización
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}PROBLEMA DETECTADO:${NC}"
echo "El Access Token configurado es incorrecto:"
echo "  ❌ Token actual: APP_USR-85928651-152e-4be2-8327-47c2f494d2a1"
echo "  ❌ Este token es muy corto (solo un identificador)"
echo ""

echo -e "${GREEN}SOLUCIÓN:${NC}"
echo "Necesitas el Access Token COMPLETO, no solo el identificador."
echo "El token correcto se ve así:"
echo "  ✅ APP_USR-6598778765213486-062114-8e3eecbc8aedfbc47ea79811539567fc-105046639"
echo "  (Debe tener al menos 60 caracteres)"
echo ""

echo "================================================"
echo "  PASOS PARA RESOLVER EL PROBLEMA"
echo "================================================"
echo ""

echo "1️⃣  OBTENER TUS CREDENCIALES CORRECTAS:"
echo "   - Ve a: https://www.mercadopago.com/developers/panel/app"
echo "   - Selecciona tu aplicación"
echo "   - Ve a 'Credenciales de producción' o 'Credenciales de prueba'"
echo "   - Copia el Access Token COMPLETO"
echo ""

echo "2️⃣  VERIFICAR TUS CREDENCIALES ACTUALES:"
echo "   Ejecuta el siguiente comando (reemplaza 1 con tu ID de empresa):"
echo ""
echo -e "   ${GREEN}php artisan mp:verify 1${NC}"
echo ""

echo "3️⃣  ACTUALIZAR TUS CREDENCIALES:"
echo "   Ejecuta el siguiente comando (reemplaza 1 con tu ID de empresa):"
echo ""
echo -e "   ${GREEN}php artisan mp:update-credentials 1${NC}"
echo ""
echo "   El comando te pedirá:"
echo "   - Access Token (el token largo completo)"
echo "   - Public Key (APP_USR-...)"
echo "   - User ID (solo números, ejemplo: 105046639)"
echo ""

echo "4️⃣  VERIFICAR QUE FUNCIONE:"
echo "   Después de actualizar, vuelve a verificar:"
echo ""
echo -e "   ${GREEN}php artisan mp:verify 1${NC}"
echo ""

echo "================================================"
echo "  INFORMACIÓN ADICIONAL"
echo "================================================"
echo ""

echo "📌 Dónde encontrar cada credencial:"
echo ""
echo "   Access Token:"
echo "   - Panel de Mercado Pago > Tu aplicación > Credenciales"
echo "   - Es el token MÁS LARGO (60+ caracteres)"
echo "   - Ejemplo: APP_USR-6598778765213486-062114-8e3eecbc8aedfbc47ea79811539567fc-105046639"
echo ""

echo "   Public Key:"
echo "   - Mismo lugar que el Access Token"
echo "   - También comienza con APP_USR-"
echo "   - Es más corto que el Access Token"
echo ""

echo "   User ID:"
echo "   - En el mismo panel de credenciales"
echo "   - Es solo un número (sin APP_USR-)"
echo "   - Ejemplo: 105046639"
echo ""

echo "================================================"
echo "  COMANDOS DISPONIBLES"
echo "================================================"
echo ""
echo "  php artisan mp:verify {empresa_id}             - Verificar credenciales"
echo "  php artisan mp:update-credentials {empresa_id} - Actualizar credenciales"
echo ""

echo -e "${YELLOW}¿Necesitas ayuda?${NC}"
echo "  Si tienes problemas, revisa el log de Laravel:"
echo "  tail -f storage/logs/laravel.log"
echo ""

echo "================================================"
