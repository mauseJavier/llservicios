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

    /**
     * Relación con Pagos (forma_pago principal)
     */
    public function pagos()
    {
        return $this->hasMany(Pagos::class, 'forma_pago');
    }

    /**
     * Relación con Pagos (forma_pago secundaria)
     */
    public function pagosSecundarios()
    {
        return $this->hasMany(Pagos::class, 'forma_pago2');
    }
}
