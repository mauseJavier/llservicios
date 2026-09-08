<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Cliente extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Verifica si el cliente tiene habilitados los recargos por mora
     * para una empresa determinada (por defecto, la del usuario autenticado).
     *
     * @param int|null $empresaId
     * @return bool
     */
    public function aplicaRecargos(?int $empresaId = null): bool
    {
        $empresaId = $empresaId ?? auth()->user()->empresa_id ?? null;

        if ($empresaId === null) {
            return false;
        }

        return $this->empresas()
            ->wherePivot('empresa_id', $empresaId)
            ->wherePivot('aplicar_recargos', true)
            ->exists();
    }

    /**
     * Accessor que expone el valor del pivot para la empresa del usuario autenticado.
     * Mantiene compatibilidad con las vistas que usan $cliente->aplicar_recargos.
     *
     * @return bool
     */
    public function getAplicarRecargosAttribute(): bool
    {
        return $this->aplicaRecargos();
    }

    /**
     * Accessor que devuelve la descripción de la condición frente al IVA.
     * Compatible con las vistas PDF que usan $cliente->condicion_iva.
     *
     * @return string|null
     */
    public function getCondicionIvaAttribute(): ?string
    {
        $condiciones = \App\Services\AfipService::tiposContribuyentes();

        return $condiciones[(int) ($this->condicion_iva_id ?? 5)]['Desc'] ?? null;
    }

        /**
         * Las empresas a las que pertenece el cliente
         */
        public function empresas(): BelongsToMany
        {
            return $this->belongsToMany(Empresa::class, 'cliente_empresa', 'cliente_id', 'empresa_id')->withPivot('aplicar_recargos');
        }

     /**
     * The servicios that belong to the Cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'cliente_servicio', 'cliente_id', 'servicio_id')->withPivot('cantidad','vencimiento');
    }

    /**
     * Los servicios a pagar del cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serviciosPagar(): HasMany
    {
        return $this->hasMany(ServicioPagar::class, 'cliente_id');
    }

    /**
     * Los servicios impagos del cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serviciosImpagos(): HasMany
    {
        return $this->hasMany(ServicioPagar::class, 'cliente_id')->where('estado', 'impago');
    }

    /**
     * Los servicios pagos del cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serviciosPagos(): HasMany
    {
        return $this->hasMany(ServicioPagar::class, 'cliente_id')->where('estado', 'pago');
    }

    /**
     * Los segmentos del cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function segmentos(): BelongsToMany
    {
        return $this->belongsToMany(Segmento::class, 'cliente_segmento', 'cliente_id', 'segmento_id');
    }

}
