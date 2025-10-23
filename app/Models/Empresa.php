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
}