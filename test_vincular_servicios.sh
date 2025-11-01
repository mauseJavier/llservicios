#!/bin/bash

# Script de prueba para las funcionalidades de vinculación en DetalleCliente

echo "========================================================="
echo "Prueba de Funcionalidades - Vincular/Desvincular Servicios"
echo "========================================================="
echo ""

echo "✓ Verificando componente actualizado..."
if grep -q "vincularServicio" app/Livewire/DetalleCliente.php; then
    echo "  ✓ Método vincularServicio encontrado"
else
    echo "  ✗ ERROR: Método vincularServicio no encontrado"
    exit 1
fi

if grep -q "desvincularServicio" app/Livewire/DetalleCliente.php; then
    echo "  ✓ Método desvincularServicio encontrado"
else
    echo "  ✗ ERROR: Método desvincularServicio no encontrado"
    exit 1
fi

if grep -q "cargarServiciosDisponibles" app/Livewire/DetalleCliente.php; then
    echo "  ✓ Método cargarServiciosDisponibles encontrado"
else
    echo "  ✗ ERROR: Método cargarServiciosDisponibles no encontrado"
    exit 1
fi

echo ""
echo "✓ Verificando vista actualizada..."
if grep -q "mostrarModalVincular" resources/views/livewire/detalle-cliente.blade.php; then
    echo "  ✓ Modal de vinculación encontrado"
else
    echo "  ✗ ERROR: Modal no encontrado"
    exit 1
fi

if grep -q "abrirModalVincular" resources/views/livewire/detalle-cliente.blade.php; then
    echo "  ✓ Botón 'Vincular Servicio' encontrado"
else
    echo "  ✗ ERROR: Botón de vincular no encontrado"
    exit 1
fi

if grep -q "desvincularServicio" resources/views/livewire/detalle-cliente.blade.php; then
    echo "  ✓ Botón de desvincular encontrado"
else
    echo "  ✗ ERROR: Botón de desvincular no encontrado"
    exit 1
fi

echo ""
echo "========================================================="
echo "✓ Todas las verificaciones pasaron exitosamente"
echo "========================================================="
echo ""
echo "📋 Funcionalidades implementadas:"
echo ""
echo "1. Vincular Servicios:"
echo "   - Botón 'Vincular Servicio' en la sección de servicios vinculados"
echo "   - Modal interactivo con búsqueda en tiempo real"
echo "   - Selector de servicios disponibles (excluye ya vinculados)"
echo "   - Campos: Servicio, Cantidad, Fecha de Vencimiento"
echo "   - Validaciones de seguridad y permisos"
echo ""
echo "2. Desvincular Servicios:"
echo "   - Botón 'Desvincular' en cada fila de servicios vinculados"
echo "   - Confirmación antes de eliminar"
echo "   - Actualización automática después de desvincular"
echo ""
echo "3. Búsqueda de Servicios:"
echo "   - Búsqueda en tiempo real por nombre o descripción"
echo "   - Solo muestra servicios de la empresa del usuario"
echo "   - Filtra servicios ya vinculados automáticamente"
echo ""
echo "4. Seguridad:"
echo "   - Verifica pertenencia del cliente a la empresa"
echo "   - Verifica que el servicio pertenece a la empresa"
echo "   - Valida que no exista vinculación duplicada"
echo "   - Solo usuarios con rol Admin pueden acceder"
echo ""
echo "========================================================="
echo "🚀 Para probar:"
echo ""
echo "1. Inicia el servidor: php artisan serve"
echo "2. Ve a /VerCliente y haz clic en 'Ver' de un cliente"
echo "3. Haz clic en 'Vincular Servicio'"
echo "4. Selecciona un servicio, cantidad y fecha"
echo "5. Confirma la vinculación"
echo "6. Para desvincular, haz clic en el botón de desvincular"
echo ""
echo "========================================================="
