<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Cliente extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function empresa():BelongsTo
    {
        return $this->belongsTo(empresa::class,'empresa_id','id');
    }

     /**
     * The servicios that belong to the Cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'cliente_servicio', 'cliente_id', 'servicio_id')->withPivot('precio','vencimiento');
    }


}
