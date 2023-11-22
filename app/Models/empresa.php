<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\User;

class empresa extends Model
{
    use HasFactory;
    protected $guarded = [];
    // protected $fillable = ['*'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
