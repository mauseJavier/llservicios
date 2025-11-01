#!/bin/bash

# Script de verificación del componente DetalleCliente

echo "======================================"
echo "Verificando Componente DetalleCliente"
echo "======================================"
echo ""

# Verificar que los archivos existen
echo "1. Verificando archivos..."
echo ""

if [ -f "app/Livewire/DetalleCliente.php" ]; then
    echo "✓ Componente PHP existe: app/Livewire/DetalleCliente.php"
else
    echo "✗ ERROR: Componente PHP no encontrado"
    exit 1
fi

if [ -f "resources/views/livewire/detalle-cliente.blade.php" ]; then
    echo "✓ Vista Blade existe: resources/views/livewire/detalle-cliente.blade.php"
else
    echo "✗ ERROR: Vista Blade no encontrada"
    exit 1
fi

echo ""
echo "2. Verificando ruta en web.php..."
if grep -q "DetalleCliente" routes/web.php; then
    echo "✓ Ruta configurada en routes/web.php"
else
    echo "✗ ERROR: Ruta no encontrada en routes/web.php"
    exit 1
fi

echo ""
echo "3. Verificando modificaciones en VerCliente..."
if grep -q "DetalleCliente" resources/views/livewire/ver-cliente/ver-cliente.blade.php; then
    echo "✓ Vista VerCliente actualizada con botón de detalle"
else
    echo "✗ ERROR: Vista VerCliente no actualizada"
    exit 1
fi

echo ""
echo "======================================"
echo "✓ Todas las verificaciones pasaron"
echo "======================================"
echo ""
echo "Para probar el componente:"
echo "1. Asegúrate de que el servidor esté corriendo"
echo "2. Navega a /VerCliente"
echo "3. Haz clic en 'Ver' en cualquier cliente"
echo "4. O accede directamente a /DetalleCliente/{id}"
echo ""
echo "======================================"
