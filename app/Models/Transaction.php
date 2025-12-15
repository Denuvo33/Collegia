<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_type',
        'user_id',
        'event_id',
        'amount',
        'payment_type',
        'transaction_status',
        'snap_token',
        'response_midtrans',
        'paid_at',
        'promotion_days',
        'promotion_start_date',
        'promotion_end_date',
    ];

    protected $casts = [
        'response_midtrans' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'promotion_start_date' => 'date',
        'promotion_end_date' => 'date',
    ];

    // Transaction status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Transaction type constants
    const TYPE_EVENT_CREATION = 'event_creation';
    const TYPE_EVENT_PROMOTION = 'event_promotion';

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
