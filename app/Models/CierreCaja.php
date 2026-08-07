<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CierreCaja extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'cierre_caja';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'usuario_id',
        'usuario_nombre',
        'importe',
        'empresa_id',
        'movimiento',
        'comentario'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'importe' => 'decimal:2',
        'usuario_id' => 'integer',
        'empresa_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el modelo User (usuario).
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con el modelo Empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para filtrar por tipo de movimiento.
     */
    public function scopePorMovimiento($query, $movimiento)
    {
        return $query->where('movimiento', $movimiento);
    }

    /**
     * Scope para filtrar por empresa.
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope para filtrar por usuario.
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Obtiene el último cierre de caja para una empresa específica.
     */
    public static function ultimoCierre($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('movimiento', 'cierre')
                    ->latest()
                    ->first();
    }

    /**
     * Obtiene el último inicio de caja para una empresa específica.
     */
    public static function ultimoInicio($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('movimiento', 'inicio')
                    ->latest()
                    ->first();
    }

    /**
     * Calcula el resumen de caja de un usuario para una fecha determinada.
     * Incluye el cálculo, el resumen del día y los movimientos detallados.
     *
     * @param int $empresaId
     * @param int $usuarioId
     * @param Carbon|\DateTimeInterface|string $fecha
     * @return array
     */
    public static function resumenUsuario($empresaId, $usuarioId, $fecha)
    {
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        $totalInicioCaja = static::where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('movimiento', 'inicio')
            ->whereDate('created_at', $fecha)
            ->sum('importe');

        $totalCierreCaja = static::where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('movimiento', 'cierre')
            ->whereDate('created_at', $fecha)
            ->sum('importe');

        $cantidadInicios = static::where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('movimiento', 'inicio')
            ->whereDate('created_at', $fecha)
            ->count();

        $cantidadCierres = static::where('empresa_id', $empresaId)
            ->where('usuario_id', $usuarioId)
            ->where('movimiento', 'cierre')
            ->whereDate('created_at', $fecha)
            ->count();

        $totalPagos = Pagos::where('id_usuario', $usuarioId)
            ->where('forma_pago', '=', 1)
            ->whereDate('created_at', $fecha)
            ->sum('importe');

        $totalGastos = Expense::where('usuario_id', $usuarioId)
            ->where('estado', 'pago')
            ->where('forma_pago_id', '=', 1)
            ->whereDate('created_at', $fecha)
            ->sum('importe');

        $calculoCaja = [
            'inicio_caja' => $totalInicioCaja,
            'total_pagos' => $totalPagos,
            'total_gastos' => $totalGastos,
            'cierre_caja' => $totalCierreCaja,
            'calculo_final' => (-$totalInicioCaja) + (-$totalPagos) + $totalCierreCaja + $totalGastos
        ];

        $resumenDia = [
            'fecha' => $fecha->format('d/m/Y'),
            'inicio_registrado' => $cantidadInicios > 0,
            'cierre_registrado' => $cantidadCierres > 0,
            'cantidad_inicios' => $cantidadInicios,
            'cantidad_cierres' => $cantidadCierres,
            'total_movimientos_pagos' => Pagos::where('id_usuario', $usuarioId)->whereDate('created_at', $fecha)->count(),
            'total_movimientos_gastos' => Expense::where('usuario_id', $usuarioId)->whereDate('created_at', $fecha)->count()
        ];

        return [
            'calculoCaja' => $calculoCaja,
            'resumenDia' => $resumenDia,
            'movimientosInicio' => static::where('empresa_id', $empresaId)
                ->where('usuario_id', $usuarioId)
                ->where('movimiento', 'inicio')
                ->whereDate('created_at', $fecha)
                ->orderBy('created_at')
                ->get(),
            'movimientosCierre' => static::where('empresa_id', $empresaId)
                ->where('usuario_id', $usuarioId)
                ->where('movimiento', 'cierre')
                ->whereDate('created_at', $fecha)
                ->orderBy('created_at')
                ->get(),
            'pagosDia' => Pagos::where('id_usuario', $usuarioId)
                ->where('forma_pago', '=', 1)
                ->whereDate('created_at', $fecha)
                ->orderBy('created_at')
                ->get(),
            'gastosDia' => Expense::where('usuario_id', $usuarioId)
                ->whereDate('created_at', $fecha)
                ->orderBy('created_at')
                ->get(),
        ];
    }

    /**
     * Calcula el resumen de caja de toda una empresa para una fecha determinada.
     *
     * @param int $empresaId
     * @param Carbon|\DateTimeInterface|string $fecha
     * @return array
     */
    public static function resumenEmpresa($empresaId, $fecha)
    {
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);

        $usuariosConMovimientos = static::where('empresa_id', $empresaId)
            ->whereDate('created_at', $fecha)
            ->select('usuario_id', 'usuario_nombre')
            ->distinct()
            ->get()
            ->pluck('usuario_nombre', 'usuario_id');

        $resumenPorUsuario = [];
        $totalesEmpresa = [
            'inicio_caja' => 0,
            'total_pagos' => 0,
            'total_gastos' => 0,
            'cierre_caja' => 0,
            'calculo_final' => 0
        ];

        foreach ($usuariosConMovimientos as $usuarioId => $usuarioNombre) {
            $totalInicioCaja = static::where('empresa_id', $empresaId)
                ->where('usuario_id', $usuarioId)
                ->where('movimiento', 'inicio')
                ->whereDate('created_at', $fecha)
                ->sum('importe');

            $totalCierreCaja = static::where('empresa_id', $empresaId)
                ->where('usuario_id', $usuarioId)
                ->where('movimiento', 'cierre')
                ->whereDate('created_at', $fecha)
                ->sum('importe');

            $totalPagos = Pagos::where('id_usuario', $usuarioId)
                ->where('forma_pago', '=', 1)
                ->whereDate('created_at', $fecha)
                ->sum('importe');

            $totalGastos = Expense::where('usuario_id', $usuarioId)
                ->where('estado', 'pago')
                ->where('forma_pago_id', '=', 1)
                ->whereDate('created_at', $fecha)
                ->sum('importe');

            $calculoFinal = (-$totalInicioCaja) + (-$totalPagos) + $totalCierreCaja + $totalGastos;

            $resumenPorUsuario[] = [
                'usuario_id' => $usuarioId,
                'usuario_nombre' => $usuarioNombre,
                'inicio_caja' => $totalInicioCaja,
                'total_pagos' => $totalPagos,
                'total_gastos' => $totalGastos,
                'cierre_caja' => $totalCierreCaja,
                'calculo_final' => $calculoFinal,
                'cantidad_inicios' => static::where('empresa_id', $empresaId)
                    ->where('usuario_id', $usuarioId)
                    ->where('movimiento', 'inicio')
                    ->whereDate('created_at', $fecha)
                    ->count(),
                'cantidad_cierres' => static::where('empresa_id', $empresaId)
                    ->where('usuario_id', $usuarioId)
                    ->where('movimiento', 'cierre')
                    ->whereDate('created_at', $fecha)
                    ->count(),
                'cantidad_pagos' => Pagos::where('id_usuario', $usuarioId)
                    ->where('forma_pago', '=', 1)
                    ->whereDate('created_at', $fecha)
                    ->count(),
                'cantidad_gastos' => Expense::where('usuario_id', $usuarioId)
                    ->where('estado', 'pago')
                    ->where('forma_pago_id', '=', 1)
                    ->whereDate('created_at', $fecha)
                    ->count(),
            ];

            $totalesEmpresa['inicio_caja'] += $totalInicioCaja;
            $totalesEmpresa['total_pagos'] += $totalPagos;
            $totalesEmpresa['total_gastos'] += $totalGastos;
            $totalesEmpresa['cierre_caja'] += $totalCierreCaja;
            $totalesEmpresa['calculo_final'] += $calculoFinal;
        }

        return [
            'fecha' => $fecha->format('d/m/Y'),
            'usuarios' => $resumenPorUsuario,
            'totales' => $totalesEmpresa,
            'cantidad_usuarios' => count($resumenPorUsuario)
        ];
    }

    /**
     * Obtiene los usuarios de una empresa con movimientos de caja en un rango de fechas.
     *
     * @param int $empresaId
     * @param Carbon|\DateTimeInterface|string $desde
     * @param Carbon|\DateTimeInterface|string $hasta
     * @return Collection
     */
    public static function usuariosConCierres($empresaId, $desde, $hasta)
    {
        $desde = $desde instanceof Carbon ? $desde : Carbon::parse($desde);
        $hasta = $hasta instanceof Carbon ? $hasta : Carbon::parse($hasta);

        return static::where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$desde->startOfDay(), $hasta->endOfDay()])
            ->select('usuario_id', 'usuario_nombre')
            ->distinct()
            ->orderBy('usuario_nombre')
            ->get();
    }
}
