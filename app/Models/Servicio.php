<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Servicio extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function empresa():BelongsTo
    {
        return $this->belongsTo(Empresa::class,'empresa_id','id');
    }

    /**
     * The Clientes that belong to the Servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function Clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'cliente_servicio', 'servicio_id', 'cliente_id')->withPivot('cantidad','vencimiento');
    }

    /**
     * Los servicios a pagar relacionados con este servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serviciosPagar(): HasMany
    {
        return $this->hasMany(ServicioPagar::class, 'servicio_id');
    }

    /**
     * Los servicios a pagar que están impagos
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serviciosImpagos(): HasMany
    {
        return $this->hasMany(ServicioPagar::class, 'servicio_id')->where('estado', 'impago');
    }

    /**
     * Los servicios a pagar que están pagos
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serviciosPagos(): HasMany
    {
        return $this->hasMany(ServicioPagar::class, 'servicio_id')->where('estado', 'pago');
    }

    /**
     * Scope para filtrar solo servicios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar solo servicios inactivos
     */
    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Verificar si el servicio está activo
     */
    public function estaActivo(): bool
    {
        return $this->activo === true;
    }

    /**
     * Activar el servicio
     */
    public function activar(): bool
    {
        return $this->update(['activo' => true]);
    }

    /**
     * Desactivar el servicio y desvincular todos los clientes
     * Esto evita que los schedules generen nuevos servicios impagos
     * 
     * @return bool
     */
    public function desactivar(): bool
    {
        try {
            \DB::beginTransaction();

            // 1. Desactivar el servicio
            $this->update(['activo' => false]);

            // 2. Desvincular todos los clientes de este servicio
            // Elimina los registros de la tabla pivot cliente_servicio
            $this->Clientes()->detach();

            \DB::commit();

            \Log::info("Servicio ID {$this->id} desactivado y desvinculado de todos los clientes");

            return true;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error("Error al desactivar servicio ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    
}
