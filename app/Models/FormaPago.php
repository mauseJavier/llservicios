<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    /**
     * Relación con Expenses
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'forma_pago_id');
    }
}
