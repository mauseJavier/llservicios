<?php

namespace Database\Seeders;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Expense;
use App\Models\Pagos;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCierreCajaSeeder extends Seeder
{
    /**
     * Seeder de datos de demostración para visualizar el cierre de caja
     * de varios usuarios de la misma empresa.
     *
     * NO está registrado en DatabaseSeeder: se ejecuta manualmente con
     *  php artisan db:seed --class=DemoCierreCajaSeeder
     */
    public function run(): void
    {
        $empresa = Empresa::find(13) ?? Empresa::first();

        $cajeros = [
            ['name' => 'Cajero Uno Demo', 'email' => 'cajero1@demo.com', 'dni' => '10000001'],
            ['name' => 'Cajero Dos Demo', 'email' => 'cajero2@demo.com', 'dni' => '10000002'],
            ['name' => 'Cajero Tres Demo', 'email' => 'cajero3@demo.com', 'dni' => '10000003'],
        ];

        $usuarios = [];

        foreach ($cajeros as $cajero) {
            $usuario = User::firstOrCreate(
                ['email' => $cajero['email']],
                [
                    'name' => $cajero['name'],
                    'dni' => $cajero['dni'],
                    'password' => Hash::make(1234),
                    'role_id' => 2,
                    'empresa_id' => $empresa->id,
                    'email_verified_at' => now(),
                ]
            );

            $usuarios[] = $usuario;
        }

        $fechas = ['2026-09-03', '2026-09-02', '2026-09-01'];

        $baseInicio = [40000, 50000, 45000];
        $baseCierre = [152400, 201350, 178920];

        foreach ($fechas as $i => $fecha) {
            $extraInicio = ($i + 1) * 2000;

            foreach ($usuarios as $j => $usuario) {
                $horaInicio = Carbon::parse($fecha)->setTime(9, 0);
                $horaCierre = Carbon::parse($fecha)->setTime(20, 30);

                $this->crearMovimiento($usuario, $empresa, 'inicio', $baseInicio[$j] + $extraInicio, 'Apertura de caja diaria - '.$fecha, $horaInicio);
                $this->crearMovimiento($usuario, $empresa, 'cierre', $baseCierre[$j] + $extraInicio * 3, 'Cierre de caja diario - '.$fecha, $horaCierre);
            }
        }

        // Movimientos de hoy para los usuarios adicionales existentes (1 y 2)
        $hoy = Carbon::parse('2026-09-03');

        $adicionales = [
            [1, 8, 30, 5000, 21, 0, 35600],
            [2, 8, 45, 8000, 20, 45, 42900],
        ];

        foreach ($adicionales as [$userId, $hIni, $mIni, $ini, $hCie, $mCie, $cie]) {
            $usuario = User::find($userId);

            if (! $usuario || $usuario->empresa_id !== $empresa->id) {
                continue;
            }

            $this->crearMovimiento($usuario, $empresa, 'inicio', $ini, 'Apertura de caja diaria', $hoy->copy()->setTime($hIni, $mIni));
            $this->crearMovimiento($usuario, $empresa, 'cierre', $cie, 'Cierre de caja diario', $hoy->copy()->setTime($hCie, $mCie));
        }

        // Gastos de hoy para los cajeros
        $gastos = [
            ['detalle' => 'Compra de insumos de oficina', 'importe' => 3500.50],
            ['detalle' => 'Café y viáticos', 'importe' => 1500.00],
            ['detalle' => 'Limpieza del local', 'importe' => 2200.00],
        ];

        foreach ($usuarios as $j => $usuario) {
            Expense::create([
                'empresa_id' => $empresa->id,
                'detalle' => $gastos[$j]['detalle'],
                'forma_pago_id' => 1,
                'estado' => 'pago',
                'importe' => $gastos[$j]['importe'],
                'comentario' => 'Gasto de demostración',
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->name,
                'created_at' => $hoy->copy()->setTime(13, 0),
                'updated_at' => $hoy->copy()->setTime(13, 0),
            ]);
        }

        // Pagos de hoy para los cajeros
        $cliente = Cliente::find(575) ?? Cliente::first();
        $servicio1 = Servicio::find(109) ?? Servicio::first();
        $servicio2 = Servicio::find(110) ?? Servicio::first();

        $pagosDemo = [
            [$servicio1, 1, 100.00, 3500.00],
            [$servicio2, 2, 50.00, 2450.00],
        ];

        foreach ($usuarios as $usuario) {
            foreach ($pagosDemo as $i => [$servicio, $cantidad, $precio, $importe]) {
                $yaExiste = Pagos::where('id_usuario', $usuario->id)
                    ->whereDate('created_at', $hoy->toDateString())
                    ->where('importe', $importe)
                    ->exists();

                if ($yaExiste) {
                    continue;
                }

                $horaPago = $hoy->copy()->setTime(11, $i);

                $servicioPagar = ServicioPagar::create([
                    'cliente_id' => $cliente->id,
                    'servicio_id' => $servicio->id,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'estado' => 'pago',
                ]);

                $servicioPagar->created_at = $horaPago;
                $servicioPagar->updated_at = $horaPago;
                $servicioPagar->save();

                Pagos::create([
                    'id_servicio_pagar' => $servicioPagar->id,
                    'id_usuario' => $usuario->id,
                    'forma_pago' => 1,
                    'importe' => $importe,
                    'comentario' => 'Pago de demostración',
                    'created_at' => $horaPago,
                    'updated_at' => $horaPago,
                ]);
            }
        }

        if ($this->command) {
            $this->command->info('Datos de demostración de cierre de caja creados para '.$empresa->nombre.'.');
        }
    }

    private function crearMovimiento(User $usuario, Empresa $empresa, string $movimiento, float $importe, string $comentario, Carbon $fecha): void
    {
        $existe = CierreCaja::where('usuario_id', $usuario->id)
            ->where('empresa_id', $empresa->id)
            ->where('movimiento', $movimiento)
            ->where('created_at', $fecha)
            ->exists();

        if ($existe) {
            return;
        }

        $registro = CierreCaja::create([
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->name,
            'importe' => $importe,
            'empresa_id' => $empresa->id,
            'movimiento' => $movimiento,
            'comentario' => $comentario,
        ]);

        $registro->created_at = $fecha;
        $registro->updated_at = $fecha;
        $registro->save();
    }
}
