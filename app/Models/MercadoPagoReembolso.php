<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoReembolso extends Model
{
    use HasFactory;

    protected $table = 'mp_reembolsos';

    protected $fillable = [
        'payment_id',
        'external_reference',
        'empresa_id',
        'monto',
        'motivo',
        'estado',
        'mp_refund_id',
        'respuesta',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'respuesta' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
