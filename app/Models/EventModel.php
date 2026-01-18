<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class EventModel extends Model
{
    protected $table = 'event';

    protected $fillable = [
        'user_id',
        'organizer',
        'date_event',
        'image',
        'title',
        'content',
        'price',
        'location',
        'status',
        'promoted_till',
        'is_visible',
        'user_pay_transaction_id',
    ];

    protected $appends = ['hash_id',];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'user_pay_transaction_id');
    }

    public function interests()
    {
        return $this->hasMany(EventInterest::class, 'event_id');
    }

    public function interestedUsers()
    {
        return $this->belongsToMany(User::class, 'event_interests', 'event_id', 'user_id')->withTimestamps();
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
