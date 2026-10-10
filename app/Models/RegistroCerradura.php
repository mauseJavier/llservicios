<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroCerradura extends Model
{
    use HasFactory;

    protected $table = 'registros_cerradura';

    protected $guarded = [];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'recibido_en' => 'datetime',
        'datos_extra' => 'array',
    ];

    /**
     * La empresa a la que pertenece el registro.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * El cliente asociado al registro (si existe).
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
