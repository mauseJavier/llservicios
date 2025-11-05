<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoPOS extends Model
{
    use HasFactory;

    protected $table = 'mercadopago_pos';

    protected $fillable = [
        'mercadopago_store_id',
        'external_id',
        'mp_pos_id',
        'name',
        'fixed_amount',
        'category',
        'qr_code',
        'qr_url',
        'uuid',
        'status',
        'qr_data',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Relación con la tienda
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoStore::class, 'mercadopago_store_id', 'id');
    }

    /**
     * Scope para cajas activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get empresa through store relationship
     */
    public function getEmpresaAttribute()
    {
        return $this->store->empresa;
    }
}
