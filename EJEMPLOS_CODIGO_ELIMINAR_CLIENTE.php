<?php

/**
 * EJEMPLOS DE CÓDIGO - Funcionalidad Eliminar Cliente
 * 
 * Este archivo contiene ejemplos de cómo usar y extender
 * la funcionalidad de eliminación de clientes.
 */

// ============================================
// EJEMPLO 1: Verificar datos antes de eliminar
// ============================================

use App\Models\Cliente;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\DB;

// Obtener estadísticas de un cliente antes de eliminar
function obtenerEstadisticasCliente($clienteId)
{
    $cliente = Cliente::find($clienteId);
    
    if (!$cliente) {
        return null;
    }
    
    return [
        'cliente' => $cliente,
        'servicios_pagar_total' => ServicioPagar::where('cliente_id', $clienteId)->count(),
        'servicios_impagos' => ServicioPagar::where('cliente_id', $clienteId)->where('estado', 'impago')->count(),
        'servicios_pagos' => ServicioPagar::where('cliente_id', $clienteId)->where('estado', 'pago')->count(),
        'total_deuda' => ServicioPagar::where('cliente_id', $clienteId)
            ->where('estado', 'impago')
            ->get()
            ->sum(function($servicio) {
                return $servicio->precio * $servicio->cantidad;
            }),
        'vinculaciones_servicios' => DB::table('cliente_servicio')->where('cliente_id', $clienteId)->count(),
        'empresas_asociadas' => DB::table('cliente_empresa')->where('cliente_id', $clienteId)->count(),
    ];
}

// Uso:
// $stats = obtenerEstadisticasCliente(1);
// dd($stats);


// ============================================
// EJEMPLO 2: Exportar datos antes de eliminar
// ============================================

function exportarDatosClienteAntesDeEliminar($clienteId)
{
    $cliente = Cliente::with(['serviciosPagar', 'servicios'])->find($clienteId);
    
    if (!$cliente) {
        return null;
    }
    
    $datos = [
        'fecha_exportacion' => now()->toDateTimeString(),
        'cliente' => [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'correo' => $cliente->correo,
            'telefono' => $cliente->telefono,
            'dni' => $cliente->dni,
            'domicilio' => $cliente->domicilio,
        ],
        'servicios_pagar' => $cliente->serviciosPagar->map(function($sp) {
            return [
                'id' => $sp->id,
                'servicio_id' => $sp->servicio_id,
                'cantidad' => $sp->cantidad,
                'precio' => $sp->precio,
                'estado' => $sp->estado,
                'total' => $sp->cantidad * $sp->precio,
                'fecha' => $sp->created_at->toDateTimeString(),
            ];
        }),
        'servicios_vinculados' => $cliente->servicios->map(function($s) {
            return [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'precio' => $s->pivot->precio ?? $s->precio,
                'vencimiento' => $s->pivot->vencimiento,
            ];
        }),
    ];
    
    // Guardar como JSON
    $nombreArchivo = "cliente_{$clienteId}_backup_" . now()->format('Y-m-d_His') . ".json";
    file_put_contents(storage_path("app/backups_clientes/{$nombreArchivo}"), json_encode($datos, JSON_PRETTY_PRINT));
    
    return $nombreArchivo;
}

// Uso:
// $archivo = exportarDatosClienteAntesDeEliminar(1);
// echo "Backup guardado en: " . $archivo;


// ============================================
// EJEMPLO 3: Eliminar con auditoría
// ============================================

use App\Models\User;

function eliminarClienteConAuditoria($clienteId, $usuarioId, $motivo = null)
{
    DB::beginTransaction();
    
    try {
        $cliente = Cliente::find($clienteId);
        
        if (!$cliente) {
            throw new \Exception("Cliente no encontrado");
        }
        
        // 1. Registrar en tabla de auditoría (crear esta tabla si no existe)
        DB::table('auditoria_eliminaciones')->insert([
            'tipo_entidad' => 'cliente',
            'entidad_id' => $clienteId,
            'entidad_datos' => json_encode($cliente->toArray()),
            'usuario_id' => $usuarioId,
            'motivo' => $motivo,
            'fecha_eliminacion' => now(),
        ]);
        
        // 2. Contar registros antes de eliminar
        $serviciosPagar = ServicioPagar::where('cliente_id', $clienteId)->count();
        $pagos = DB::table('pagos')
            ->whereIn('id_servicio_pagar', function($query) use ($clienteId) {
                $query->select('id')
                    ->from('servicio_pagar')
                    ->where('cliente_id', $clienteId);
            })
            ->count();
        
        // 3. Eliminar (mismo proceso que en el componente)
        $serviciosPagarIds = ServicioPagar::where('cliente_id', $clienteId)->pluck('id');
        DB::table('pagos')->whereIn('id_servicio_pagar', $serviciosPagarIds)->delete();
        ServicioPagar::where('cliente_id', $clienteId)->delete();
        DB::table('cliente_servicio')->where('cliente_id', $clienteId)->delete();
        DB::table('cliente_empresa')->where('cliente_id', $clienteId)->delete();
        Cliente::destroy($clienteId);
        
        DB::commit();
        
        return [
            'success' => true,
            'cliente' => $cliente->nombre,
            'registros_eliminados' => [
                'servicios_pagar' => $serviciosPagar,
                'pagos' => $pagos,
            ]
        ];
        
    } catch (\Exception $e) {
        DB::rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Uso:
// $resultado = eliminarClienteConAuditoria(1, Auth::id(), 'Cliente duplicado');


// ============================================
// EJEMPLO 4: Soft Delete (eliminación suave)
// ============================================

/**
 * Para implementar soft deletes:
 * 1. Agregar a la migración de clientes:
 *    $table->softDeletes();
 * 
 * 2. Agregar al modelo Cliente:
 *    use Illuminate\Database\Eloquent\SoftDeletes;
 *    use SoftDeletes;
 * 
 * 3. Modificar el método eliminarCliente en el componente Livewire:
 */

function eliminarClienteSuave($clienteId)
{
    DB::beginTransaction();
    
    try {
        $cliente = Cliente::find($clienteId);
        
        // En lugar de eliminar, marcar como eliminado
        $cliente->delete(); // Soft delete automático si está configurado
        
        // También marcar servicios como "cancelado" en lugar de eliminar
        ServicioPagar::where('cliente_id', $clienteId)
            ->update(['estado' => 'cancelado']);
        
        DB::commit();
        
        return true;
        
    } catch (\Exception $e) {
        DB::rollBack();
        return false;
    }
}

// Restaurar cliente eliminado suavemente:
function restaurarCliente($clienteId)
{
    $cliente = Cliente::withTrashed()->find($clienteId);
    
    if ($cliente) {
        $cliente->restore();
        
        // Restaurar servicios cancelados a impago
        ServicioPagar::where('cliente_id', $clienteId)
            ->where('estado', 'cancelado')
            ->update(['estado' => 'impago']);
            
        return true;
    }
    
    return false;
}


// ============================================
// EJEMPLO 5: Validación de permisos
// ============================================

use Illuminate\Support\Facades\Auth;

function puedeEliminarCliente($usuarioId, $clienteId)
{
    $usuario = User::find($usuarioId);
    
    // Ejemplo 1: Solo admin puede eliminar
    if ($usuario->rol !== 'admin') {
        return false;
    }
    
    // Ejemplo 2: Verificar que el cliente pertenece a la empresa del usuario
    $cliente = Cliente::find($clienteId);
    $empresaIds = $cliente->empresas()->pluck('empresas.id')->toArray();
    
    if (!in_array($usuario->empresa_id, $empresaIds)) {
        return false;
    }
    
    // Ejemplo 3: No permitir eliminar si tiene servicios pagos recientes (último mes)
    $serviciosPagosRecientes = ServicioPagar::where('cliente_id', $clienteId)
        ->where('estado', 'pago')
        ->where('updated_at', '>=', now()->subMonth())
        ->count();
    
    if ($serviciosPagosRecientes > 0) {
        return false; // No permitir eliminar si hay actividad reciente
    }
    
    return true;
}

// Integrar en el componente Livewire:
/*
public function eliminarCliente()
{
    if (!$this->clienteAEliminar) {
        session()->flash('error', 'No se pudo encontrar el cliente a eliminar.');
        return;
    }
    
    // AGREGAR VALIDACIÓN DE PERMISOS
    if (!puedeEliminarCliente(Auth::id(), $this->clienteAEliminar->id)) {
        session()->flash('error', 'No tienes permisos para eliminar este cliente.');
        $this->mostrarModalConfirmacion = false;
        $this->clienteAEliminar = null;
        return;
    }
    
    // ... resto del código de eliminación
}
*/


// ============================================
// EJEMPLO 6: Notificación por email al eliminar
// ============================================

use Illuminate\Support\Facades\Mail;

function enviarNotificacionEliminacion($clienteId, $usuarioId)
{
    $cliente = Cliente::find($clienteId);
    $usuario = User::find($usuarioId);
    
    $datosEmail = [
        'cliente_nombre' => $cliente->nombre,
        'cliente_dni' => $cliente->dni,
        'usuario_nombre' => $usuario->name,
        'fecha' => now()->format('d/m/Y H:i:s'),
    ];
    
    // Enviar email al admin
    Mail::send('emails.cliente-eliminado', $datosEmail, function($message) {
        $message->to('admin@empresa.com')
                ->subject('Cliente Eliminado - Notificación');
    });
}


// ============================================
// EJEMPLO 7: Verificación de integridad
// ============================================

function verificarIntegridadDespuesDeEliminar($clienteId)
{
    $errores = [];
    
    // Verificar que no queden registros huérfanos
    if (Cliente::find($clienteId)) {
        $errores[] = "El cliente aún existe en la base de datos";
    }
    
    if (ServicioPagar::where('cliente_id', $clienteId)->count() > 0) {
        $errores[] = "Aún existen servicios a pagar del cliente";
    }
    
    if (DB::table('cliente_servicio')->where('cliente_id', $clienteId)->count() > 0) {
        $errores[] = "Aún existen vinculaciones cliente-servicio";
    }
    
    if (DB::table('cliente_empresa')->where('cliente_id', $clienteId)->count() > 0) {
        $errores[] = "Aún existen vinculaciones cliente-empresa";
    }
    
    return [
        'integridad_ok' => empty($errores),
        'errores' => $errores
    ];
}

// Uso:
// $verificacion = verificarIntegridadDespuesDeEliminar(1);
// if (!$verificacion['integridad_ok']) {
//     Log::error('Problemas de integridad al eliminar cliente', $verificacion['errores']);
// }


// ============================================
// EJEMPLO 8: Comando Artisan personalizado
// ============================================

/**
 * Crear comando: php artisan make:command EliminarClienteCommand
 * 
 * Luego modificar app/Console/Commands/EliminarClienteCommand.php:
 */

/*
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\DB;

class EliminarClienteCommand extends Command
{
    protected $signature = 'cliente:eliminar {cliente_id} {--force : Forzar eliminación sin confirmación}';
    protected $description = 'Elimina un cliente y todos sus datos asociados';

    public function handle()
    {
        $clienteId = $this->argument('cliente_id');
        $force = $this->option('force');
        
        $cliente = Cliente::find($clienteId);
        
        if (!$cliente) {
            $this->error("Cliente con ID {$clienteId} no encontrado");
            return 1;
        }
        
        $this->info("Cliente: {$cliente->nombre}");
        $this->info("DNI: {$cliente->dni}");
        
        if (!$force) {
            if (!$this->confirm('¿Está seguro de eliminar este cliente?')) {
                $this->info('Operación cancelada');
                return 0;
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Eliminar registros
            $serviciosPagarIds = ServicioPagar::where('cliente_id', $clienteId)->pluck('id');
            DB::table('pagos')->whereIn('id_servicio_pagar', $serviciosPagarIds)->delete();
            ServicioPagar::where('cliente_id', $clienteId)->delete();
            DB::table('cliente_servicio')->where('cliente_id', $clienteId)->delete();
            DB::table('cliente_empresa')->where('cliente_id', $clienteId)->delete();
            Cliente::destroy($clienteId);
            
            DB::commit();
            
            $this->info("✓ Cliente eliminado exitosamente");
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
*/

// Uso desde terminal:
// php artisan cliente:eliminar 123
// php artisan cliente:eliminar 123 --force

?>
