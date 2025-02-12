<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Servicio extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function empresa():BelongsTo
    {
        return $this->belongsTo(empresa::class,'empresa_id','id');
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

    
}
