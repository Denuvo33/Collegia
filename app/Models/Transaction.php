<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'event_id',
        'amount',
        'payment_type',
        'transaction_status',
        'snap_token',
        'response_midtrans',
        'paid_at',
    ];

    protected $casts = [
        'response_midtrans' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // Transaction status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(EventModel::class, 'event_id');
    }
}
