#!/bin/bash

# Script para instalar la librería AFIP SDK
# Ejecutar: bash install_afip.sh

echo "==================================="
echo "  Instalación AFIP SDK para PHP"
echo "==================================="
echo ""

# Detectar si estamos en Docker
DOCKER_CONTAINER="llservicios"
IS_DOCKER=false

if docker ps --format '{{.Names}}' | grep -q "^${DOCKER_CONTAINER}$"; then
    echo "✓ Contenedor Docker detectado: ${DOCKER_CONTAINER}"
    IS_DOCKER=true
else
    echo "ℹ Ejecutando en modo local"
fi

echo ""

# Función para ejecutar comandos según el entorno
run_command() {
    if [ "$IS_DOCKER" = true ]; then
        docker exec -it $DOCKER_CONTAINER $@
    else
        $@
    fi
}

# Verificar que composer esté disponible
if [ "$IS_DOCKER" = true ]; then
    if ! docker exec $DOCKER_CONTAINER composer --version &> /dev/null; then
        echo "❌ Error: Composer no está disponible en el contenedor"
        exit 1
    fi
else
    if ! command -v composer &> /dev/null; then
        echo "❌ Error: Composer no está instalado"
        echo "Por favor, instala Composer primero: https://getcomposer.org/"
        exit 1
    fi
fi

echo "✓ Composer encontrado"
echo ""

# Instalar el paquete AFIP SDK
echo "📦 Instalando afipsdk/afip.php..."
run_command composer require afipsdk/afip.php

if [ $? -eq 0 ]; then
    echo ""
    echo "✓ AFIP SDK instalado exitosamente"
    echo ""
    
    # Crear estructura de directorios
    echo "📁 Creando estructura de directorios..."
    run_command mkdir -p storage/app/afip/empresas
    
    echo "✓ Directorios creados"
    echo ""
    
    # Ajustar permisos (importante en Docker)
    echo "🔐 Ajustando permisos..."
    run_command chmod -R 775 storage/app/afip
    
    if [ "$IS_DOCKER" = true ]; then
        run_command chown -R www-data:www-data storage/app/afip
    fi
    
    echo "✓ Permisos configurados"
    echo ""
    
    # Ejecutar migración
    echo "🔄 Ejecutando migraciones..."
    run_command php artisan migrate --force
    
    if [ $? -eq 0 ]; then
        echo "✓ Migraciones ejecutadas"
    else
        echo "⚠ Error al ejecutar migraciones (puede ser normal si ya están aplicadas)"
    fi
    
    echo ""
    echo "==================================="
    echo "  ✓ Instalación completada"
    echo "==================================="
    echo ""
    echo "📋 Próximos pasos:"
    echo ""
    echo "1. Configura las variables de entorno en .env:"
    echo "   AFIP_PRODUCTION=false"
    echo "   AFIP_PUNTO_VENTA=1"
    echo "   AFIP_TIPO_COMPROBANTE=6"
    echo "   AFIP_IVA_DEFAULT=21"
    echo ""
    
    if [ "$IS_DOCKER" = true ]; then
        echo "2. Reinicia el contenedor para aplicar cambios:"
        echo "   docker restart ${DOCKER_CONTAINER}"
        echo ""
    fi
    
    echo "3. Obtén tus certificados de AFIP:"
    echo "   - Testing: https://www.afip.gob.ar/ws/WSAA/alias.aspx"
    echo "   - Producción: https://www.afip.gob.ar/ws/"
    echo ""
    echo "4. Sube los certificados desde el panel de AFIP:"
    echo "   http://localllservicios/afip"
    echo ""
else
    echo ""
    echo "❌ Error al instalar AFIP SDK"
    exit 1
fi
