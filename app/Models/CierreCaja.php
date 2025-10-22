<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreCaja extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'cierre_caja';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'usuario_id',
        'usuario_nombre',
        'importe',
        'empresa_id',
        'movimiento',
        'comentario'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'importe' => 'decimal:2',
        'usuario_id' => 'integer',
        'empresa_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el modelo User (usuario).
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con el modelo Empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para filtrar por tipo de movimiento.
     */
    public function scopePorMovimiento($query, $movimiento)
    {
        return $query->where('movimiento', $movimiento);
    }

    /**
     * Scope para filtrar por empresa.
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope para filtrar por usuario.
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Obtiene el último cierre de caja para una empresa específica.
     */
    public static function ultimoCierre($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('movimiento', 'cierre')
                    ->latest()
                    ->first();
    }

    /**
     * Obtiene el último inicio de caja para una empresa específica.
     */
    public static function ultimoInicio($empresaId)
    {
        return static::where('empresa_id', $empresaId)
                    ->where('movimiento', 'inicio')
                    ->latest()
                    ->first();
    }
}
