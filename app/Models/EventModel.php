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
    ];

    protected $appends = ['hash_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
