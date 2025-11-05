<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\User;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $guarded = [];

    // protected $fillable = ['*'];
    
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'cliente_empresa', 'empresa_id', 'cliente_id');
    }

    /**
     * Relación con tiendas de MercadoPago
     */
    public function mercadopagoStores(): HasMany
    {
        return $this->hasMany(MercadoPagoStore::class, 'empresa_id', 'id');
    }

    /**
     * Verificar si tiene credenciales de MercadoPago configuradas
     */
    public function hasMercadoPagoConfigured(): bool
    {
        return !empty($this->MP_ACCESS_TOKEN) && !empty($this->MP_PUBLIC_KEY);
    }

        /**
     * Relación con gastos (expenses)
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'empresa_id', 'id');
    }

}