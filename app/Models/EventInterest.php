<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInterest extends Model
{
    protected $table = 'event_interests';

    protected $fillable = [
        'user_id',
        'event_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function event()
    {
        return $this->belongsTo(EventModel::class, 'event_id');
    }
}
