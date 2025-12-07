#!/bin/bash

# Script de prueba para NotificacionMensual con WhatsApp
# Uso: ./test_notificacion_mensual.sh

echo "╔══════════════════════════════════════════════════════╗"
echo "║   TEST - Notificación Mensual con WhatsApp          ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para verificar configuración
check_config() {
    echo -e "${BLUE}📋 Verificando configuración...${NC}"
    
    if grep -q "WHATSAPP_API_URL" .env; then
        echo -e "  ${GREEN}✓${NC} WHATSAPP_API_URL configurada"
    else
        echo -e "  ${RED}✗${NC} WHATSAPP_API_URL NO configurada"
        echo -e "    ${YELLOW}Agrega: WHATSAPP_API_URL=... al archivo .env${NC}"
    fi
    
    if grep -q "WHATSAPP_INSTANCE_ID" .env; then
        echo -e "  ${GREEN}✓${NC} WHATSAPP_INSTANCE_ID configurada"
    else
        echo -e "  ${RED}✗${NC} WHATSAPP_INSTANCE_ID NO configurada"
        echo -e "    ${YELLOW}Agrega: WHATSAPP_INSTANCE_ID=... al archivo .env${NC}"
    fi
    
    echo ""
}

# Función para verificar queue worker
check_queue() {
    echo -e "${BLUE}🔄 Verificando queue worker...${NC}"
    
    if pgrep -f "queue:work" > /dev/null; then
        echo -e "  ${GREEN}✓${NC} Queue worker está corriendo"
        echo -e "    PID: $(pgrep -f 'queue:work')"
    else
        echo -e "  ${YELLOW}⚠${NC}  Queue worker NO está corriendo"
        echo -e "    ${YELLOW}Inicia el worker en otra terminal:${NC}"
        echo -e "    php artisan queue:work"
    fi
    
    echo ""
}

# Función para verificar clientes con servicios impagos
check_clientes() {
    echo -e "${BLUE}👥 Verificando clientes con servicios impagos...${NC}"
    
    php artisan tinker --execute="
        \$count = DB::table('servicio_pagar')
            ->where('estado', 'impago')
            ->distinct('cliente_id')
            ->count('cliente_id');
        echo \$count . ' cliente(s) con servicios impagos';
    "
    
    echo ""
}

# Función para ejecutar el comando
run_command() {
    echo -e "${BLUE}🚀 Ejecutando comando de notificación...${NC}"
    echo ""
    
    php artisan app:notificacion-mensual
    
    echo ""
}

# Función para mostrar logs recientes
show_logs() {
    echo -e "${BLUE}📝 Últimas líneas del log...${NC}"
    echo ""
    
    if [ -f "storage/logs/laravel.log" ]; then
        tail -20 storage/logs/laravel.log | grep -E "(WhatsApp|Notificacion|ERROR)" || echo "No hay logs relevantes recientes"
    else
        echo -e "${YELLOW}No se encontró el archivo de log${NC}"
    fi
    
    echo ""
}

# Función para mostrar log de notificación mensual
show_notificacion_log() {
    echo -e "${BLUE}📋 Log de notificación mensual...${NC}"
    echo ""
    
    if [ -f "storage/app/logs/NotificacionMailMensual.txt" ]; then
        tail -50 storage/app/logs/NotificacionMailMensual.txt
    else
        echo -e "${YELLOW}No se encontró el log de notificación mensual${NC}"
    fi
    
    echo ""
}

# Función para mostrar opciones
show_menu() {
    echo "Opciones:"
    echo "  1) Verificar configuración completa"
    echo "  2) Ejecutar notificación mensual"
    echo "  3) Ver logs de Laravel"
    echo "  4) Ver log de notificación mensual"
    echo "  5) Ver jobs en cola"
    echo "  6) Test completo (todo lo anterior)"
    echo "  0) Salir"
    echo ""
    read -p "Selecciona una opción: " option
    
    case $option in
        1)
            check_config
            check_queue
            check_clientes
            ;;
        2)
            run_command
            ;;
        3)
            show_logs
            ;;
        4)
            show_notificacion_log
            ;;
        5)
            echo -e "${BLUE}📊 Jobs en cola...${NC}"
            php artisan queue:listen --once
            ;;
        6)
            check_config
            check_queue
            check_clientes
            run_command
            show_logs
            show_notificacion_log
            ;;
        0)
            echo -e "${GREEN}Saliendo...${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Opción inválida${NC}"
            ;;
    esac
    
    echo ""
    read -p "Presiona Enter para continuar..."
    clear
    show_menu
}

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: No se encontró el archivo artisan${NC}"
    echo "Este script debe ejecutarse desde la raíz del proyecto Laravel"
    exit 1
fi

# Iniciar menú
clear
show_menu
