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
        return $this->belongsToMany(Cliente::class, 'cliente_servicio', 'servicio_id', 'cliente_id')->withPivot('precio','vencimiento');
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

    
}
