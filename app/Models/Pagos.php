<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pagos extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Relación con ServicioPagar
     */
    public function servicioPagar(): BelongsTo
    {
        return $this->belongsTo(ServicioPagar::class, 'id_servicio_pagar');
    }

    /**
     * Relación con Usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Relación con FormaPago principal
     */
    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago');
    }

    /**
     * Relación con FormaPago secundaria (pago dividido)
     */
    public function formaPago2(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago2');
    }

    /**
     * Nota de Crédito emitida a partir de este pago (si existe)
     */
    public function notaCredito(): HasOne
    {
        return $this->hasOne(Pagos::class, 'afip_nc_de_pago_id');
    }

    /**
     * Verifica si el pago tiene factura AFIP generada
     */
    public function tieneFacturaAfip(): bool
    {
        return !empty($this->afip_cae);
    }

    /**
     * Obtiene el total del pago (incluye segunda forma de pago si existe)
     */
    public function getTotalAttribute()
    {
        return $this->importe + ($this->importe2 ?? 0);
    }

    /**
     * Obtiene el nombre del tipo de comprobante AFIP
     */
    public function getTipoComprobanteNombreAttribute()
    {
        $tipos = [
            1 => 'Factura A',
            2 => 'Nota de Débito A',
            3 => 'Nota de Crédito A',
            6 => 'Factura B',
            7 => 'Nota de Débito B',
            8 => 'Nota de Crédito B',
            11 => 'Factura C',
            12 => 'Nota de Débito C',
            13 => 'Nota de Crédito C',
        ];

        return $tipos[$this->afip_tipo_comprobante] ?? 'Desconocido';
    }
}
