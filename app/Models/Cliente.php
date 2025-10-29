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
         * Las empresas a las que pertenece el cliente
         */
        public function empresas(): BelongsToMany
        {
            return $this->belongsToMany(Empresa::class, 'cliente_empresa', 'cliente_id', 'empresa_id');
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


}
