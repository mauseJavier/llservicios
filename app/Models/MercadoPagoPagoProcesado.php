<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoPagoProcesado extends Model
{
    use HasFactory;

    protected $table = 'mp_pagos_procesados';

    protected $fillable = [
        'payment_id',
        'external_reference',
        'intento_id',
        'monto',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function intento(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoPagoIntento::class, 'intento_id');
    }
}
