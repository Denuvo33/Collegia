<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollaborationInterest extends Model
{
    protected $table = 'collaboration_interests';

    protected $fillable = [
        'user_id',
        'collaboration_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function collaboration()
    {
        return $this->belongsTo(Collaboration::class, 'collaboration_id');
    }
}
