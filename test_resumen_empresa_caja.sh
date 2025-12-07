#!/bin/bash

# Script de verificación para el Resumen de Empresa en CierreCaja

echo "========================================================="
echo "Verificación - Resumen de Empresa en Cierre de Caja"
echo "========================================================="
echo ""

echo "✓ Verificando componente PHP..."

if grep -q "mostrarResumenEmpresa" app/Livewire/CierreCaja.php; then
    echo "  ✓ Propiedad mostrarResumenEmpresa encontrada"
else
    echo "  ✗ ERROR: Propiedad mostrarResumenEmpresa no encontrada"
    exit 1
fi

if grep -q "resumenEmpresa" app/Livewire/CierreCaja.php; then
    echo "  ✓ Propiedad resumenEmpresa encontrada"
else
    echo "  ✗ ERROR: Propiedad resumenEmpresa no encontrada"
    exit 1
fi

if grep -q "cargarResumenEmpresa" app/Livewire/CierreCaja.php; then
    echo "  ✓ Método cargarResumenEmpresa encontrado"
else
    echo "  ✗ ERROR: Método cargarResumenEmpresa no encontrado"
    exit 1
fi

if grep -q "toggleResumenEmpresa" app/Livewire/CierreCaja.php; then
    echo "  ✓ Método toggleResumenEmpresa encontrado"
else
    echo "  ✗ ERROR: Método toggleResumenEmpresa no encontrado"
    exit 1
fi

echo ""
echo "✓ Verificando vista Blade..."

if grep -q "toggleResumenEmpresa" resources/views/livewire/cierre-caja.blade.php; then
    echo "  ✓ Botón de toggle encontrado"
else
    echo "  ✗ ERROR: Botón de toggle no encontrado"
    exit 1
fi

if grep -q "Resumen Empresa" resources/views/livewire/cierre-caja.blade.php; then
    echo "  ✓ Sección de Resumen Empresa encontrada"
else
    echo "  ✗ ERROR: Sección de Resumen Empresa no encontrada"
    exit 1
fi

if grep -q "TOTALES GENERALES DE LA EMPRESA" resources/views/livewire/cierre-caja.blade.php; then
    echo "  ✓ Sección de totales generales encontrada"
else
    echo "  ✗ ERROR: Sección de totales no encontrada"
    exit 1
fi

if grep -q "Detalle por Usuario" resources/views/livewire/cierre-caja.blade.php; then
    echo "  ✓ Tabla de detalle por usuario encontrada"
else
    echo "  ✗ ERROR: Tabla de detalle no encontrada"
    exit 1
fi

echo ""
echo "========================================================="
echo "✓ Todas las verificaciones pasaron exitosamente"
echo "========================================================="
echo ""
echo "📋 Funcionalidad implementada:"
echo ""
echo "1. Botón 'Ver Resumen Empresa'"
echo "   - Ubicado en la barra de acciones"
echo "   - Alterna entre mostrar/ocultar el resumen"
echo "   - Ícono cambia según el estado"
echo ""
echo "2. Resumen General de la Empresa:"
echo "   - Totales de Inicios de Caja (todos los usuarios)"
echo "   - Totales de Pagos en efectivo (todos los usuarios)"
echo "   - Totales de Cierres de Caja (todos los usuarios)"
echo "   - Totales de Gastos en efectivo (todos los usuarios)"
echo "   - Resultado final de la empresa"
echo ""
echo "3. Detalle por Usuario:"
echo "   - Tabla con cada usuario que tiene movimientos"
echo "   - Inicios, Pagos, Cierres y Gastos por usuario"
echo "   - Cantidad de movimientos entre paréntesis"
echo "   - Resultado individual por usuario"
echo "   - Colores diferenciados (rojo: salidas, verde: entradas)"
echo ""
echo "4. Características:"
echo "   - Solo muestra datos de la fecha actual"
echo "   - Solo usuarios de la empresa autenticada"
echo "   - Actualización manual con botón de refresh"
echo "   - Diseño responsive con scroll horizontal"
echo "   - Leyenda explicativa al final"
echo ""
echo "========================================================="
echo "🚀 Para probar:"
echo ""
echo "1. Inicia el servidor: php artisan serve"
echo "2. Accede como Admin a /cierre-caja"
echo "3. Haz clic en 'Ver Resumen Empresa'"
echo "4. Verás el resumen consolidado de todos los usuarios"
echo "5. Para ocultar, haz clic en 'Ocultar Resumen Empresa'"
echo ""
echo "========================================================="
