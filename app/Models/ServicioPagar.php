<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioPagar extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'servicio_pagar';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'cliente_id',
        'servicio_id',
        'cantidad',
        'precio',
        'estado',
        'mp_preference_id',
        'mp_payment_id'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el modelo Cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con el modelo Servicio.
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    /**
     * Accessor para calcular el total automáticamente.
     */
    public function getTotalAttribute()
    {
        return $this->cantidad * $this->precio;
    }

    /**
     * Scope para filtrar servicios impagos.
     */
    public function scopeImpagos($query)
    {
        return $query->where('estado', 'impago');
    }

    /**
     * Scope para filtrar servicios pagos.
     */
    public function scopePagos($query)
    {
        return $query->where('estado', 'pago');
    }
}
