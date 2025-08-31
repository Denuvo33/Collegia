<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
