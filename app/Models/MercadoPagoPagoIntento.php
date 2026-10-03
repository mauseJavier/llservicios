<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MercadoPagoPagoIntento extends Model
{
    use HasFactory;

    protected $table = 'mp_pago_intentos';

    protected $fillable = [
        'token',
        'referencia',
        'cliente_id',
        'empresa_id',
        'mp_preference_id',
        'estado',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function serviciosPagar(): BelongsToMany
    {
        return $this->belongsToMany(
            ServicioPagar::class,
            'mp_intento_servicio',
            'intento_id',
            'servicio_pagar_id'
        );
    }

    /**
     * Resolver el intento a partir de una referencia "lote_{token}".
     */
    public static function porReferencia(?string $referencia): ?self
    {
        if (empty($referencia) || ! preg_match('/^lote_([A-Za-z0-9\-]+)$/', $referencia, $matches)) {
            return null;
        }

        return static::where('token', $matches[1])->first();
    }
}
