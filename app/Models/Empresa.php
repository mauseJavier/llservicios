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

    protected $hidden = [
        'api_token',
    ];

    protected $casts = [
        'dia_notificacion' => 'integer',
    ];

    /**
     * Algoritmo usado para hashear los tokens de API de las empresas.
     */
    public const API_TOKEN_HASH_ALGO = 'sha256';

    /**
     * Devuelve el hash almacenable del token de API.
     */
    public static function hashApiToken(string $token): string
    {
        return hash(self::API_TOKEN_HASH_ALGO, $token);
    }

    /**
     * Busca una empresa por su token de API en claro.
     */
    public static function findByApiToken(string $token): ?self
    {
        if (empty($token)) {
            return null;
        }

        return self::where('api_token', self::hashApiToken($token))->first();
    }

    /**
     * Genera un nuevo token de API para la empresa, lo guarda hasheado
     * y devuelve el token en claro (solo disponible en este momento).
     */
    public function generateApiToken(): string
    {
        $plainToken = \Illuminate\Support\Str::random(64);
        $this->api_token = self::hashApiToken($plainToken);
        $this->save();

        return $plainToken;
    }

    /**
     * Registros de cerradura de la empresa.
     */
    public function registrosCerradura(): HasMany
    {
        return $this->hasMany(RegistroCerradura::class, 'empresa_id', 'id');
    }

    /**
     * Indica si la empresa debe notificarse en el día del mes indicado.
     */
    public function notificaElDia(int $dia): bool
    {
        return $this->dia_notificacion !== null && (int) $this->dia_notificacion === $dia;
    }

    // protected $fillable = [
    //     'nombre',
    //     'cuit',
    //     'correo',
    //     'logo',
    //     'condicion_iva_id',
    //     'instanciaWS',
    //     'tokenWS'
    // ];
    
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'cliente_empresa', 'empresa_id', 'cliente_id');
    }

    /**
     * Accessor que devuelve la descripción de la condición frente al IVA.
     * Compatible con las vistas PDF que usan $empresa->condicion_iva.
     *
     * @return string|null
     */
    public function getCondicionIvaAttribute(): ?string
    {
        $condiciones = \App\Services\AfipService::tiposContribuyentes();

        return $condiciones[(int) ($this->condicion_iva_id ?? 1)]['Desc'] ?? null;
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

    /**
     * Relación con servicios - Una empresa puede tener muchos servicios
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'empresa_id', 'id');
    }

    /**
     * Relación con segmentos - Una empresa puede tener muchos segmentos
     */
    public function segmentos(): HasMany
    {
        return $this->hasMany(Segmento::class, 'empresa_id', 'id');
    }

}