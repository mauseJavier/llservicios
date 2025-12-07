#!/bin/bash

# Script de prueba para la funcionalidad de eliminar clientes
# Este script crea un cliente de prueba con servicios pagos e impagos
# y verifica que la eliminación funcione correctamente

echo "================================================"
echo "  TEST: Funcionalidad Eliminar Cliente"
echo "================================================"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
function print_step() {
    echo -e "${YELLOW}[PASO]${NC} $1"
}

function print_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

function print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 1. Verificar que Laravel esté funcionando
print_step "Verificando que Laravel esté funcionando..."
php artisan --version > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "Laravel funcionando correctamente"
else
    print_error "Laravel no está funcionando"
    exit 1
fi

echo ""

# 2. Crear un cliente de prueba
print_step "Creando cliente de prueba..."

CLIENTE_ID=$(php artisan tinker --execute="
    use App\Models\Cliente;
    use App\Models\Empresa;
    
    \$cliente = Cliente::create([
        'nombre' => 'Cliente TEST ELIMINAR',
        'correo' => 'test_eliminar@test.com',
        'telefono' => '5491100000000',
        'dni' => '99999999',
        'domicilio' => 'Calle Test 123'
    ]);
    
    // Asociar con una empresa (usar la primera disponible)
    \$empresa = Empresa::first();
    if (\$empresa) {
        \$empresa->clientes()->attach(\$cliente->id);
    }
    
    echo \$cliente->id;
" 2>/dev/null)

if [ ! -z "$CLIENTE_ID" ]; then
    print_success "Cliente creado con ID: $CLIENTE_ID"
else
    print_error "No se pudo crear el cliente"
    exit 1
fi

echo ""

# 3. Crear servicios de prueba
print_step "Creando servicios de prueba..."

php artisan tinker --execute="
    use App\Models\Servicio;
    use App\Models\Empresa;
    use Illuminate\Support\Facades\DB;
    
    \$empresa = Empresa::first();
    
    \$servicio1 = Servicio::firstOrCreate(
        ['nombre' => 'Servicio Test 1', 'empresa_id' => \$empresa->id],
        [
            'descripcion' => 'Servicio de prueba 1',
            'precio' => 100,
            'tiempo' => 'mes'
        ]
    );
    
    \$servicio2 = Servicio::firstOrCreate(
        ['nombre' => 'Servicio Test 2', 'empresa_id' => \$empresa->id],
        [
            'descripcion' => 'Servicio de prueba 2',
            'precio' => 200,
            'tiempo' => 'mes'
        ]
    );
    
    // Vincular servicios al cliente
    DB::table('cliente_servicio')->insert([
        'cliente_id' => $CLIENTE_ID,
        'servicio_id' => \$servicio1->id,
        'cantidad' => 1,
        'vencimiento' => now()->addDays(30),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    DB::table('cliente_servicio')->insert([
        'cliente_id' => $CLIENTE_ID,
        'servicio_id' => \$servicio2->id,
        'cantidad' => 1,
        'vencimiento' => now()->addDays(30),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo 'OK';
" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    print_success "Servicios vinculados al cliente"
else
    print_error "Error al vincular servicios"
fi

echo ""

# 4. Crear servicios a pagar (impagos y pagos)
print_step "Creando servicios impagos y pagos..."

php artisan tinker --execute="
    use App\Models\ServicioPagar;
    use App\Models\Servicio;
    use App\Models\Empresa;
    
    \$empresa = Empresa::first();
    \$servicios = Servicio::where('empresa_id', \$empresa->id)->limit(2)->get();
    
    foreach (\$servicios as \$servicio) {
        // Crear un servicio impago
        ServicioPagar::create([
            'cliente_id' => $CLIENTE_ID,
            'servicio_id' => \$servicio->id,
            'cantidad' => 1,
            'precio' => \$servicio->precio,
            'estado' => 'impago'
        ]);
        
        // Crear un servicio pago
        ServicioPagar::create([
            'cliente_id' => $CLIENTE_ID,
            'servicio_id' => \$servicio->id,
            'cantidad' => 1,
            'precio' => \$servicio->precio,
            'estado' => 'pago'
        ]);
    }
    
    echo 'OK';
" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    print_success "Servicios pagos e impagos creados"
else
    print_error "Error al crear servicios pagos/impagos"
fi

echo ""

# 5. Verificar datos creados
print_step "Verificando datos creados..."

php artisan tinker --execute="
    use App\Models\Cliente;
    use App\Models\ServicioPagar;
    use Illuminate\Support\Facades\DB;
    
    \$cliente = Cliente::find($CLIENTE_ID);
    \$serviciosPagar = ServicioPagar::where('cliente_id', $CLIENTE_ID)->count();
    \$clienteServicio = DB::table('cliente_servicio')->where('cliente_id', $CLIENTE_ID)->count();
    
    echo \"Cliente: {\$cliente->nombre}\n\";
    echo \"Servicios a pagar: \$serviciosPagar\n\";
    echo \"Vinculaciones cliente-servicio: \$clienteServicio\n\";
"

echo ""
echo "================================================"
echo "  CLIENTE DE PRUEBA CREADO"
echo "================================================"
echo ""
echo -e "${GREEN}ID del Cliente: $CLIENTE_ID${NC}"
echo -e "${GREEN}Nombre: Cliente TEST ELIMINAR${NC}"
echo -e "${GREEN}DNI: 99999999${NC}"
echo ""
echo "Ahora puedes probar la funcionalidad de eliminación:"
echo "1. Accede a la lista de clientes en la aplicación web"
echo "2. Busca el cliente 'Cliente TEST ELIMINAR' (DNI: 99999999)"
echo "3. Haz clic en el botón 'Eliminar'"
echo "4. Verifica el modal de confirmación"
echo "5. Confirma la eliminación"
echo ""
echo "Para verificar que todo fue eliminado correctamente, ejecuta:"
echo ""
echo -e "${YELLOW}php artisan tinker --execute=\""
echo "    use App\Models\Cliente;"
echo "    use App\Models\ServicioPagar;"
echo "    use Illuminate\Support\Facades\DB;"
echo "    "
echo "    echo 'Cliente existe: ' . (Cliente::find($CLIENTE_ID) ? 'SI' : 'NO') . PHP_EOL;"
echo "    echo 'Servicios pagar: ' . ServicioPagar::where('cliente_id', $CLIENTE_ID)->count() . PHP_EOL;"
echo "    echo 'Vinculaciones: ' . DB::table('cliente_servicio')->where('cliente_id', $CLIENTE_ID)->count() . PHP_EOL;"
echo "\"${NC}"
echo ""
echo "================================================"
