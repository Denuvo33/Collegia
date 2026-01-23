<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Notification extends Model
{
    protected $table = 'notification';

    protected $fillable = [
        'message',
        'post_id',
        'user_id',
    ];

    protected $appends = ['hash_id'];

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->post_id);
    }
}
