<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MercadoPagoStore extends Model
{
    use HasFactory;

    protected $table = 'mercadopago_stores';

    protected $fillable = [
        'empresa_id',
        'external_id',
        'mp_store_id',
        'name',
        'location',
        'address_street_name',
        'address_street_number',
        'address_city',
        'address_state',
        'address_zip_code',
        'address_country',
        'address_latitude',
        'address_longitude',
    ];

    protected $casts = [
        'location' => 'array',
        'address_latitude' => 'decimal:8',
        'address_longitude' => 'decimal:8',
    ];

    /**
     * Relación con Empresa
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id');
    }

    /**
     * Relación con las cajas (POS)
     */
    public function pos(): HasMany
    {
        return $this->hasMany(MercadoPagoPOS::class, 'mercadopago_store_id', 'id');
    }

    /**
     * Get full address as string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_street_name,
            $this->address_street_number,
            $this->address_city,
            $this->address_state,
            $this->address_zip_code,
        ]);
        
        return implode(', ', $parts);
    }
}
