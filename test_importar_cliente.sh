#!/bin/bash

# Script de prueba para el componente ImportarCliente
# Autor: Sistema
# Fecha: Noviembre 2025

echo "=========================================="
echo "  TEST: Componente ImportarCliente"
echo "=========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: Este script debe ejecutarse desde el directorio raíz del proyecto Laravel${NC}"
    exit 1
fi

echo -e "${YELLOW}1. Verificando archivos del componente...${NC}"

# Verificar que los archivos existen
FILES=(
    "app/Livewire/ImportarCliente.php"
    "resources/views/livewire/importar-cliente.blade.php"
    "plantilla_importar_clientes.csv"
    "IMPORTAR_CLIENTE_DOCUMENTACION.md"
    "IMPORTAR_CLIENTE_RESUMEN.md"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file existe"
    else
        echo -e "${RED}✗${NC} $file NO EXISTE"
    fi
done

echo ""
echo -e "${YELLOW}2. Verificando rutas en web.php...${NC}"

if grep -q "ImportarClientesCSV" routes/web.php; then
    echo -e "${GREEN}✓${NC} Ruta ImportarClientesCSV encontrada en web.php"
else
    echo -e "${RED}✗${NC} Ruta ImportarClientesCSV NO encontrada en web.php"
fi

echo ""
echo -e "${YELLOW}3. Verificando modelo Cliente...${NC}"

if [ -f "app/Models/Cliente.php" ]; then
    echo -e "${GREEN}✓${NC} Modelo Cliente existe"
    
    # Verificar relación con empresas
    if grep -q "empresas()" app/Models/Cliente.php; then
        echo -e "${GREEN}✓${NC} Relación empresas() encontrada"
    else
        echo -e "${RED}✗${NC} Relación empresas() NO encontrada"
    fi
else
    echo -e "${RED}✗${NC} Modelo Cliente NO existe"
fi

echo ""
echo -e "${YELLOW}4. Verificando tabla cliente_empresa...${NC}"

# Buscar migración de tabla pivot
if find database/migrations -name "*cliente_empresa*" | grep -q .; then
    echo -e "${GREEN}✓${NC} Migración cliente_empresa encontrada"
else
    echo -e "${YELLOW}⚠${NC} Migración cliente_empresa no encontrada (puede estar en otra migración)"
fi

echo ""
echo -e "${YELLOW}5. Verificando configuración de Livewire...${NC}"

if [ -f "config/livewire.php" ]; then
    echo -e "${GREEN}✓${NC} Configuración de Livewire existe"
else
    echo -e "${YELLOW}⚠${NC} Archivo config/livewire.php no encontrado"
fi

echo ""
echo -e "${YELLOW}6. Generando archivo CSV de prueba...${NC}"

# Crear directorio temporal si no existe
mkdir -p storage/app/test

# Generar archivo CSV de prueba con datos válidos
cat > storage/app/test/clientes_validos.csv << EOF
nombre,correo,telefono,dni,domicilio
Test Usuario 1,test1@example.com,3516111111,11111111,Calle Test 111
Test Usuario 2,test2@example.com,3516222222,22222222,Calle Test 222
Test Usuario 3,test3@example.com,3516333333,33333333,Calle Test 333
EOF

if [ -f "storage/app/test/clientes_validos.csv" ]; then
    echo -e "${GREEN}✓${NC} Archivo de prueba creado: storage/app/test/clientes_validos.csv"
else
    echo -e "${RED}✗${NC} No se pudo crear el archivo de prueba"
fi

# Generar archivo CSV con errores para testing
cat > storage/app/test/clientes_con_errores.csv << EOF
nombre,correo,telefono,dni,domicilio
Juan Pérez,email-invalido,3516123456,12345678,Av. Test 123
,test@example.com,3516654321,87654321,Calle Test 456
María López,maria@test.com,3517890123,123,Barrio Test 789
EOF

if [ -f "storage/app/test/clientes_con_errores.csv" ]; then
    echo -e "${GREEN}✓${NC} Archivo con errores creado: storage/app/test/clientes_con_errores.csv"
else
    echo -e "${RED}✗${NC} No se pudo crear el archivo con errores"
fi

echo ""
echo -e "${YELLOW}7. Verificando permisos de escritura...${NC}"

if [ -w "storage/app" ]; then
    echo -e "${GREEN}✓${NC} Directorio storage/app tiene permisos de escritura"
else
    echo -e "${RED}✗${NC} Directorio storage/app NO tiene permisos de escritura"
    echo "   Ejecutar: chmod -R 775 storage"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}  Resumen de Verificación${NC}"
echo "=========================================="
echo ""
echo "Archivos de prueba generados:"
echo "  - storage/app/test/clientes_validos.csv"
echo "  - storage/app/test/clientes_con_errores.csv"
echo ""
echo "Para probar manualmente:"
echo "  1. Iniciar servidor: php artisan serve"
echo "  2. Acceder a: http://localhost:8000/ImportarClientesCSV"
echo "  3. Usar archivos de prueba generados"
echo ""
echo "Documentación disponible en:"
echo "  - IMPORTAR_CLIENTE_DOCUMENTACION.md"
echo "  - IMPORTAR_CLIENTE_RESUMEN.md"
echo ""
echo -e "${GREEN}¡Verificación completada!${NC}"
echo ""
