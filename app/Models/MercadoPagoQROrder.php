<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoQROrder extends Model
{
    protected $table = 'mercadopago_qr_orders';

    protected $fillable = [
        'mercadopago_pos_id',
        'external_reference',
        'in_store_order_id',
        'total_amount',
        'status',
        'payment_id',
        'payment_status',
        'items',
        'notification_data',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items' => 'array',
        'notification_data' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function pos(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoPOS::class, 'mercadopago_pos_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markAsPaid(string $paymentId, array $paymentData = []): void
    {
        $this->update([
            'status' => 'paid',
            'payment_id' => $paymentId,
            'payment_status' => $paymentData['status'] ?? 'approved',
            'notification_data' => $paymentData,
            'paid_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
